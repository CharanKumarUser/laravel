<?php

namespace App\Pdf;

use TCPDF;

class ReportTcpdf extends TCPDF
{
    /** @var array<string,mixed> */
    protected array $options = [];

    /**
     * Resolve a logo URL/path to a local filesystem path and type.
     *
     * @return array{type:string,path:string}|null
     */
    protected function resolveLogo(?string $logoUrl): ?array
    {
        if (empty($logoUrl)) return null;
        // Data URI
        if (str_starts_with($logoUrl, 'data:image')) {
            // Save to temp and use as raster
            $tmp = tempnam(sys_get_temp_dir(), 'tcpdf_logo_') . '.png';
            $data = explode(',', $logoUrl, 2)[1] ?? '';
            @file_put_contents($tmp, base64_decode($data));
            return is_file($tmp) ? ['type' => 'img', 'path' => $tmp] : null;
        }
        // HTTP(S) URL → try to map to local file; avoid remote SVG entirely
        if (str_starts_with($logoUrl, 'http://') || str_starts_with($logoUrl, 'https://')) {
            $parts = parse_url($logoUrl);
            $rel = isset($parts['path']) ? ltrim($parts['path'], '/') : '';
            if ($rel !== '') {
                $local = public_path($rel);
                if (is_file($local)) {
                    return [
                        'type' => preg_match('/\.svg$/i', $local) ? 'svg' : 'img',
                        'path' => $local,
                    ];
                }
                // If URL points to an SVG that doesn't exist locally, try PNG alternative
                if (preg_match('/\.svg$/i', $rel)) {
                    $pngRel = preg_replace('/\.svg$/i', '.png', $rel);
                    $pngLocal = public_path($pngRel);
                    if (is_file($pngLocal)) {
                        return ['type' => 'img', 'path' => $pngLocal];
                    }
                    // No safe local alternative → skip logo to prevent TCPDF error
                    return null;
                }
            }
            // For non-SVG remote images, try to download to temp file for reliable embedding
            if (!preg_match('/\.svg$/i', $logoUrl)) {
                $tmp = tempnam(sys_get_temp_dir(), 'tcpdf_logo_');
                $ext = pathinfo(parse_url($logoUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'png';
                $tmpPath = $tmp . '.' . $ext;
                @unlink($tmp);
                try {
                    $bin = @file_get_contents($logoUrl);
                    if ($bin !== false) {
                        @file_put_contents($tmpPath, $bin);
                        if (is_file($tmpPath)) {
                            return ['type' => 'img', 'path' => $tmpPath];
                        }
                    }
                } catch (\Throwable $e) {}
            }
            return null;
        }
        // Relative or absolute local path
        $candidate = is_file($logoUrl) ? $logoUrl : public_path(ltrim($logoUrl, '/'));
        if (is_file($candidate)) {
            return [
                'type' => preg_match('/\.svg$/i', $candidate) ? 'svg' : 'img',
                'path' => $candidate,
            ];
        }
        return null;
    }

    /**
     * @param array<string,mixed> $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    // Header
    public function Header(): void
    {
        $header = $this->options['header'] ?? [];
        if (!($header['enabled'] ?? true)) return;

        // Page border per page (ensures all pages have border)
        $borderMode = $this->options['page_border'] ?? 'none';
        if ($borderMode !== 'none') {
            $pad = 3; // minimal padding
            $w = $this->getPageWidth();
            $h = $this->getPageHeight();
            $left = $pad; $top = $pad; $right = $w - $pad; $bottom = $h - $pad;
            $this->SetDrawColor(0, 0, 0);
            $this->SetLineWidth(0.3);
            if ($borderMode === 'box') {
                $this->Rect($left, $top, $right - $left, $bottom - $top);
            } elseif ($borderMode === 'top') {
                $this->Line($left, $top, $right, $top);
            } elseif ($borderMode === 'bottom') {
                $this->Line($left, $bottom, $right, $bottom);
            }
        }

        $margins = $this->options['margins'] ?? [];
        $border = (int)($header['border'] ?? 0);

        // Logo (left) - robust resolution (SVG or raster)
        if (!empty($header['logo_url'])) {
            try {
                $resolved = $this->resolveLogo((string)$header['logo_url']);
                if ($resolved) {
                    if ($resolved['type'] === 'svg') {
                        // Prefer local file for SVG
                        $this->ImageSVG(
                            $resolved['path'],
                            $x = 12,
                            $y = 10,
                            $w = 18,
                            $h = '',
                            $link = '',
                            $align = '',
                            $palign = '',
                            $border = 0,
                            $fitonpage = false
                        );
                    } else {
                        $this->Image($resolved['path'], 12, 10, 18, 0, '', '', 'T');
                    }
                }
            } catch (\Throwable $e) {}
        }

        // Move down 3 line spaces
        $this->Ln(3);
        
        // Organization tag (perfect left)
        $this->SetFont($this->options['font']['family'] ?? 'dejavusans', '', (int)($this->options['font']['size_body'] ?? 9));
        $this->SetX($this->lMargin);
        $this->Cell(60, 5, 'Organization: Digital Kuppam', 0, 0, 'L');

        // Title (exact center) - "Leave Reports"
        $this->SetFont($this->options['font']['family'] ?? 'dejavusans', 'B', (int)($this->options['font']['size_title'] ?? 12));
        $pageWidth = $this->getPageWidth();
        $leftMargin = $this->lMargin;
        $rightMargin = $this->rMargin;
        $usableWidth = $pageWidth - $leftMargin - $rightMargin;
        $centerX = $leftMargin + ($usableWidth / 2);
        $this->SetX($centerX - 30); // Center the title text
        $this->Cell(60, 0, 'Leave Reports', $border, 0, 'C');

        // Date (perfect right)
        $this->SetFont($this->options['font']['family'] ?? 'dejavusans', '', (int)($this->options['font']['size_body'] ?? 9));
        $dateText = now()->format((string)($header['date_format'] ?? 'Y-m-d'));
        $this->SetX($pageWidth - $rightMargin - 50);
        $this->Cell(50, 8, $dateText, 0, 0, 'R');

        // Line under header (always draw a divider) – push it further below
        $lineY = max($this->GetY() + 6, 20);
        $this->Line($this->lMargin, $lineY, $this->getPageWidth() - $this->rMargin, $lineY);
        // Position content start just below the line
        $this->SetY($lineY + (int)($margins['header'] ?? 8));
    }

    // Footer
    public function Footer(): void
    {
        $footer = $this->options['footer'] ?? [];
        if (!($footer['enabled'] ?? true)) return;

        $this->SetY(-12);
        $this->SetFont($this->options['font']['family'] ?? 'dejavusans', '', (int)($this->options['font']['size_body'] ?? 9));

        // Precise alignment: Left / Center / Right
        $pageW = $this->getPageWidth();
        $leftMargin = $this->lMargin;
        $rightMargin = $this->rMargin;
        $usableW = $pageW - $leftMargin - $rightMargin;
        $colW = $usableW / 3.0;
        // Footer divider line (same as header)
        $this->SetY(-12);
        $this->Line($this->lMargin, $this->GetY(), $this->getPageWidth() - $this->rMargin, $this->GetY());
        $this->SetY(-9);

        // Left block (flush to left margin)
        $this->SetX($leftMargin);
        $this->Cell($colW, 6, 'Generated by Got It', 0, 0, 'L');

        // Center block (exact horizontal center)
        $this->SetX($leftMargin + $colW);
        $this->Cell($colW, 6, 'Powered by Digital Kuppam', 0, 0, 'C');

        // Right block: draw using full usable width to snap to right margin
        $pageText = 'Page no ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages();
        $this->SetX( $leftMargin);
        $this->Cell($usableW, 6, $pageText, 0, 0, 'R');
    }
}


