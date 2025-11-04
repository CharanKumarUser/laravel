<?php

namespace App\Services\Reports;

use Barryvdh\DomPDF\Facade\Pdf as DomPdfFacade;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TCPDF;
use Throwable;

class PdfService
{
    /**
     * Generate a PDF.
     *
     * @param string $type 's' => dompdf, 'c' => tcpdf
     * @param array $pdfInfo required group: metadata + optional protection/signature
     * @param array $size required page settings
     * @param string $data required HTML (with Base64 assets embedded)
     * @param array|null $headerFooter optional header/footer config
     * @param array|null $layoutOptions optional layout settings
     * @param array|null $outputOptions optional output controls
     *
     * @return SymfonyResponse|Response|StreamedResponse|BinaryFileResponse
     *
     * @throws InvalidArgumentException|RuntimeException
     */
    public function generate(
        string $type,
        array $pdfInfo,
        array $size,
        string $data,
        ?array $headerFooter = null,
        ?array $layoutOptions = null,
        ?array $outputOptions = null
    ): SymfonyResponse {
        try {
            $this->validateRequired($type, $pdfInfo, $size, $data);

            $headerFooter = $headerFooter ?? [];
            $layoutOptions = $layoutOptions ?? [];
            $outputOptions = $outputOptions ?? [];

            $size = $this->normalizeSize($size);
            $pdfInfo = $this->normalizePdfInfo($pdfInfo);
            $headerFooter = $this->normalizeHeaderFooter($headerFooter);
            $layoutOptions = $this->normalizeLayoutOptions($layoutOptions);
            $outputOptions = $this->normalizeOutputOptions($outputOptions);

            $cacheKey = null;
            if ($outputOptions['cache']) {
                $cacheKey = 'pdf_' . md5(serialize([$type, $pdfInfo, $size, $data, $headerFooter, $layoutOptions, $outputOptions]));
                $cacheDriver = $outputOptions['cache_driver'] ?? 'file';
                $cacheTtl = $outputOptions['cache_ttl'] ?? 3600;

                try {
                    $cached = Cache::store($cacheDriver)->get($cacheKey);
                    if ($cached !== null) {
                        return $this->buildResponse($cached, $outputOptions);
                    }
                } catch (Throwable $e) {
                    Log::warning("PdfService caching failed: " . $e->getMessage(), ['exception' => $e]);
                }
            }

            if ($outputOptions['threads'] > 1 || !empty($outputOptions['orchestration'])) {
                throw new RuntimeException("Multithreading and orchestration not yet implemented.");
            }

            $pdfString = $type === 's'
                ? $this->renderWithDomPdf($data, $size, $pdfInfo, $headerFooter, $layoutOptions, $outputOptions)
                : $this->renderWithTcpdf($data, $size, $pdfInfo, $headerFooter, $layoutOptions, $outputOptions);

            if ($cacheKey) {
                try {
                    Cache::store($cacheDriver)->put($cacheKey, $pdfString, $cacheTtl);
                } catch (Throwable $e) {
                    Log::warning("PdfService caching put failed: " . $e->getMessage());
                }
            }

            if (Arr::get($outputOptions, 'streaming.enabled', false)) {
                return $this->streamResponse($pdfString, $outputOptions);
            }

            return $this->buildResponse($pdfString, $outputOptions);
        } catch (Throwable $e) {
            Log::error("PdfService::generate error: " . $e->getMessage(), [
                'exception' => $e,
                'type' => $type,
                'size' => $size,
                'pdfInfo' => $pdfInfo,
            ]);
            throw new RuntimeException("Failed to generate PDF: " . $e->getMessage(), 0, $e);
        }
    }

    protected function validateRequired(string $type, array $pdfInfo, array $size, string $data): void
    {
        if (!in_array($type, ['s', 'c'], true)) {
            throw new InvalidArgumentException("Type must be 's' (DomPDF) or 'c' (TCPDF).");
        }

        if (empty($pdfInfo) || !is_array($pdfInfo) || !isset($pdfInfo['metadata']) || !is_array($pdfInfo['metadata'])) {
            throw new InvalidArgumentException('pdfInfo must be a non-empty array with a metadata sub-array.');
        }

        if (empty($size) || !is_array($size) || !isset($size['format'])) {
            throw new InvalidArgumentException('size must be a non-empty array with a format key.');
        }

        if (trim($data) === '') {
            throw new InvalidArgumentException('data (HTML string) cannot be empty.');
        }
    }

    protected function normalizeSize(array $size): array
    {
        $defaults = [
            'format' => 'A4',
            'orientation' => 'portrait',
            'margins' => ['top' => 20, 'bottom' => 20, 'left' => 15, 'right' => 15],
            'unit' => 'mm',
            'auto_page_break' => true,
            'break_margin' => 10,
            'columns' => 1,
        ];

        $result = array_merge($defaults, $size);

        if (is_array($result['format'])) {
            if (count($result['format']) !== 2 || !is_numeric($result['format'][0]) || !is_numeric($result['format'][1])) {
                throw new InvalidArgumentException('Custom format must be [width, height] with numeric values.');
            }
        } elseif (!in_array($result['format'], ['A0', 'A1', 'A2', 'A3', 'A4', 'A5', 'A6', 'B0', 'B1', 'B2', 'B3', 'B4', 'B5', 'Letter', 'Legal', 'Tabloid', 'Executive', 'Folio'], true)) {
            throw new InvalidArgumentException('Invalid page format.');
        }

        if (!is_array($result['margins']) || !isset($result['margins']['top'], $result['margins']['bottom'], $result['margins']['left'], $result['margins']['right'])) {
            throw new InvalidArgumentException('margins must be an array with top, bottom, left, right keys.');
        }

        return $result;
    }

    protected function normalizePdfInfo(array $pdfInfo): array
    {
        $defaults = [
            'metadata' => [
                'Title' => 'Document',
                'Author' => 'System',
                'Subject' => '',
                'Keywords' => '',
                'Creator' => 'PdfService',
            ],
            'protection' => null,
            'password' => '',
            'owner_password' => null,
            'encryption_level' => 128,
            'signature' => null,
        ];

        $result = array_merge($defaults, $pdfInfo);

        if (!is_array($result['metadata'])) {
            throw new InvalidArgumentException('metadata must be an array.');
        }

        if (isset($result['protection']) && !is_array($result['protection'])) {
            throw new InvalidArgumentException('protection must be an array of permissions.');
        }

        if (isset($result['signature']) && !is_array($result['signature'])) {
            throw new InvalidArgumentException('signature must be an array.');
        }

        return $result;
    }

    protected function normalizeHeaderFooter(array $hf): array
    {
        $defaults = [
            'header' => null,
            'footer' => null,
            'height' => ['header' => 20, 'footer' => 15], // Adjusted for better spacing
            'margin' => ['header' => 5, 'footer' => 5],
            'position' => ['header' => 'top', 'footer' => 'bottom'],
            'repeat_header' => true,
            'repeat_footer' => true,
            'override_per_page' => [],
        ];

        $result = array_merge($defaults, $hf);

        if (isset($result['height']) && !is_array($result['height'])) {
            throw new InvalidArgumentException('height must be an array with header and/or footer keys.');
        }

        if (isset($result['margin']) && !is_array($result['margin'])) {
            throw new InvalidArgumentException('margin must be an array with header and/or footer keys.');
        }

        return $result;
    }

    protected function normalizeLayoutOptions(array $opts): array
    {
        $defaults = [
            'pagination' => false,
            'pagination_format' => 'Page {PAGE_NUM} of {TOTAL_PAGES}',
            'watermark' => null,
            'background' => null,
            'RTL' => false,
            'font_family' => 'helvetica',
            'font_size' => 10,
            'font_color' => '#000000',
            'bold' => false,
            'italic' => false,
            'underline' => false,
            'align' => 'L',
            'line_height' => 1.2,
            'table_styles' => [],
            'list_styles' => [],
            'custom_css' => null,
            'max_image_width' => null,
            'max_image_height' => null,
            'auto_scale_images' => true,
        ];

        $result = array_merge($defaults, $opts);

        if (!is_bool($result['pagination'])) {
            throw new InvalidArgumentException('pagination must be a boolean.');
        }

        $imageCss = '';
        if ($result['auto_scale_images']) {
            $imageCss .= 'img { max-width: 100%; height: auto; }';
        }
        if ($result['max_image_width']) {
            $imageCss .= "img { max-width: {$result['max_image_width']}px; }";
        }
        if ($result['max_image_height']) {
            $imageCss .= "img { max-height: {$result['max_image_height']}px; }";
        }
        if ($imageCss) {
            $result['custom_css'] = ($result['custom_css'] ?? '') . '<style>' . $imageCss . '</style>';
        }

        return $result;
    }

    protected function normalizeOutputOptions(array $opts): array
    {
        $defaults = [
            'download' => true,
            'filename' => 'document.pdf',
            'compress' => true,
            'lazy_render' => false,
            'cache' => false,
            'cache_driver' => 'file',
            'cache_ttl' => 3600,
            'threads' => 1,
            'orchestration' => [],
            'streaming' => ['enabled' => false, 'buffer_size' => 1024 * 1024, 'flush_interval' => 2],
            'debug' => false,
            'force_full_render' => false,
        ];

        $result = array_merge($defaults, $opts);

        if (!is_bool($result['download']) || !is_bool($result['compress']) || !is_bool($result['lazy_render']) || !is_bool($result['cache']) || !is_bool($result['debug']) || !is_bool($result['force_full_render'])) {
            throw new InvalidArgumentException('download, compress, lazy_render, cache, debug, and force_full_render must be booleans.');
        }

        if (!is_string($result['filename']) || empty($result['filename'])) {
            throw new InvalidArgumentException('filename must be a non-empty string.');
        }

        return $result;
    }

    protected function buildResponse(string $pdfString, array $outputOptions): SymfonyResponse
    {
        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Length' => strlen($pdfString),
        ];

        $filename = $outputOptions['filename'] ?? 'document.pdf';
        $headers['Content-Disposition'] = $outputOptions['download']
            ? 'attachment; filename="' . $filename . '"'
            : 'inline; filename="' . $filename . '"';

        return new Response($pdfString, 200, $headers);
    }

    protected function streamResponse(string $pdfString, array $outputOptions): StreamedResponse
    {
        $bufferSize = Arr::get($outputOptions, 'streaming.buffer_size', 1024 * 1024);
        $flushInterval = Arr::get($outputOptions, 'streaming.flush_interval', 2);

        $headers = [
            'Content-Type' => 'application/pdf',
            'Transfer-Encoding' => 'chunked',
        ];

        $filename = $outputOptions['filename'] ?? 'document.pdf';
        $headers['Content-Disposition'] = $outputOptions['download']
            ? 'attachment; filename="' . $filename . '"'
            : 'inline; filename="' . $filename . '"';

        return new StreamedResponse(function () use ($pdfString, $bufferSize, $flushInterval) {
            $startTime = time();
            for ($i = 0; $i < strlen($pdfString); $i += $bufferSize) {
                echo substr($pdfString, $i, $bufferSize);
                flush();
                if (time() - $startTime >= $flushInterval) {
                    $startTime = time();
                }
            }
        }, 200, $headers);
    }

    protected function renderWithDomPdf(
        string $html,
        array $size,
        array $pdfInfo,
        array $headerFooter,
        array $layoutOptions,
        array $outputOptions
    ): string {
        try {
            // Ensure size is an array and contains valid format
            if (!is_array($size) || !isset($size['format']) || (!is_string($size['format']) && !is_array($size['format']))) {
                throw new InvalidArgumentException('Invalid size format in DomPDF rendering.');
            }

            // Ensure HTML has a head section
            if (stripos($html, '<head>') === false) {
                $html = str_replace('<html>', '<html><head></head>', $html);
            }

            // Apply RTL if enabled
            if ($layoutOptions['RTL']) {
                $html = preg_replace('/<body([^>]*)>/i', '<body$1 dir="rtl">', $html);
            }

            // Inject custom CSS
            if (!empty($layoutOptions['custom_css'])) {
                $html = preg_replace('/(<head[^>]*>)/i', "$1" . $layoutOptions['custom_css'], $html, 1);
            }

            // Handle header and footer with running elements
            $headerHtml = $headerFooter['header'] ?? '';
            $footerHtml = $headerFooter['footer'] ?? '';
            if ($headerHtml || $footerHtml) {
                $hfCss = '<style>';
                if ($headerHtml) {
                    $hfCss .= '@page { @top-center { content: element(header); } } #header { position: running(header); font-size: 10pt; text-align: left; }';
                    $html = '<div id="header">' . $headerHtml . '</div>' . $html;
                }
                if ($footerHtml) {
                    $hfCss .= '@page { @bottom-center { content: element(footer); } } #footer { position: running(footer); font-size: 8pt; text-align: center; }';
                    $html .= '<div id="footer">' . $footerHtml . '</div>';
                }
                $hfCss .= '</style>';
                $html = preg_replace('/(<head[^>]*>)/i', "$1" . $hfCss, $html, 1);
            }

            // Adjust margins for header/footer
            $hTop = Arr::get($headerFooter['height'], 'header', 0);
            $hBottom = Arr::get($headerFooter['height'], 'footer', 0);
            $mTop = Arr::get($size['margins'], 'top', 20) + $hTop;
            $mBottom = Arr::get($size['margins'], 'bottom', 20) + $hBottom;
            $css = "<style>@page { margin: {$mTop}mm {$size['margins']['right']}mm {$mBottom}mm {$size['margins']['left']}mm; }</style>";
            $html = preg_replace('/(<head[^>]*>)/i', "$1" . $css, $html, 1);

            // Handle watermark
            if (!empty($layoutOptions['watermark'])) {
                $wm = $layoutOptions['watermark'];
                $opacity = $wm['opacity'] ?? 0.1;
                $rotation = $wm['rotation'] ?? 45;
                $position = $wm['position'] ?? 'center';
                $style = "position: fixed; top: 0; left: 0; width: 100%; height: 100%; opacity: {$opacity}; transform: rotate({$rotation}deg); z-index: -1; text-align: center; font-size: 40pt; color: gray;";
                if ($position === 'center') {
                    $style .= ' display: flex; align-items: center; justify-content: center;';
                }
                $wmHtml = '';
                if (!empty($wm['text'])) {
                    $wmHtml .= "<div style=\"{$style}\">" . htmlspecialchars($wm['text']) . '</div>';
                }
                if (!empty($wm['image'])) {
                    $wmHtml .= "<img src=\"{$wm['image']}\" style=\"width: 100%; height: 100%; opacity: {$opacity};\">";
                }
                $html .= $wmHtml;
            }

            // Handle background
            if (!empty($layoutOptions['background'])) {
                $bg = $layoutOptions['background'];
                $bgCss = 'body { ';
                if (!empty($bg['color'])) {
                    $bgCss .= "background-color: {$bg['color']}; ";
                }
                if (!empty($bg['image'])) {
                    $repeat = $bg['repeat'] ?? 'no-repeat';
                    $position = $bg['position'] ?? 'center';
                    $opacity = $bg['opacity'] ?? 1;
                    $bgCss .= "background-image: url({$bg['image']}); background-repeat: {$repeat}; background-position: {$position}; opacity: {$opacity}; ";
                }
                $bgCss .= '}';
                $css = "<style>{$bgCss}</style>";
                $html = preg_replace('/(<head[^>]*>)/i', "$1" . $css, $html, 1);
            }

            // Handle pagination for DomPDF
            if ($layoutOptions['pagination']) {
                $format = str_replace('{PAGE_COUNT}', '{TOTAL_PAGES}', $layoutOptions['pagination_format']); // Normalize placeholder
                $font = $layoutOptions['font_family'] ?? 'helvetica';
                $size = 8; // Smaller font for pagination
                $y = $size['margins']['bottom'] + 5; // Position relative to bottom margin
                $pageScript = <<<PHP
<script type="text/php">
if (isset(\$pdf)) {
    \$font = \$fontMetrics->get_font('$font', 'normal');
    \$pdf->page_script('
        \$pdf->text(20, \$pdf->get_height() - $y, "$format", \$font, $size, array(0, 0, 0));
    ');
}
</script>
PHP;
                $html .= $pageScript;
            }

            // Log warning for unsupported features
            if (!empty($pdfInfo['protection']) || !empty($pdfInfo['signature'])) {
                Log::warning('Protection and signature are TCPDF-only features; ignored for DomPDF.');
            }

            // Initialize DomPDF with options
            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('isFontSubsettingEnabled', $outputOptions['compress']);
            $options->set('defaultFont', $layoutOptions['font_family']);
            $options->set('debug', $outputOptions['debug']);

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper($size['format'], $size['orientation']);
            $dompdf->render();

            // Set metadata
            foreach ($pdfInfo['metadata'] as $key => $value) {
                $dompdf->add_info($key, (string) $value);
            }

            return $dompdf->output();
        } catch (Throwable $e) {
            Log::error("DomPDF rendering failed: " . $e->getMessage(), [
                'exception' => $e,
                'size' => $size,
                'headerFooter' => $headerFooter,
                'layoutOptions' => $layoutOptions,
            ]);
            throw new RuntimeException("DomPDF rendering failed: " . $e->getMessage(), 0, $e);
        }
    }

    protected function renderWithTcpdf(
        string $html,
        array $size,
        array $pdfInfo,
        array $headerFooter,
        array $layoutOptions,
        array $outputOptions
    ): string {
        try {
            $orientation = strtoupper(substr($size['orientation'], 0, 1)) === 'L' ? 'L' : 'P';
            $unit = $size['unit'] ?? 'mm';
            $format = $size['format'] ?? 'A4';

            $pdf = new class($orientation, $unit, $format, true, 'UTF-8', false) extends TCPDF {
                public array $dynamic = [];
                public array $overridePerPage = [];

                public function Header(): void
                {
                    $page = $this->getPage();
                    if (!$this->dynamic['repeat_header'] && $page > 1) {
                        return;
                    }
                    $headerHtml = $this->dynamic['headerHtml'] ?? '';
                    if (isset($this->overridePerPage[$page]['header'])) {
                        $headerHtml = $this->overridePerPage[$page]['header'];
                    }
                    if ($headerHtml) {
                        $this->SetFont($this->dynamic['font_family'] ?? 'helvetica', 'B', 10);
                        $this->writeHTMLCell(0, 0, $this->getX(), $this->getY(), $headerHtml, 0, 1, false, true, 'L', true);
                    }
                }

                public function Footer(): void
                {
                    $page = $this->getPage();
                    $pagination = $this->dynamic['pagination'] ?? false;
                    $repeatFooter = $this->dynamic['repeat_footer'] ?? true;

                    if (!$repeatFooter && $page > 1 && !$pagination) {
                        return;
                    }

                    $this->SetY(-($this->dynamic['footer_space'] ?? 15));
                    $this->SetFont($this->dynamic['font_family'] ?? 'helvetica', 'I', 8);

                    $footerHtml = $this->dynamic['footerHtml'] ?? '';
                    if (isset($this->overridePerPage[$page]['footer'])) {
                        $footerHtml = $this->overridePerPage[$page]['footer'];
                    }
                    if (($repeatFooter || $page === 1) && $footerHtml) {
                        $this->writeHTMLCell(0, 0, '', '', $footerHtml, 0, 1, false, true, 'C', true);
                    }

                    if ($pagination) {
                        $format = $this->dynamic['pagination_format'] ?? 'Page {PAGE_NUM} of {TOTAL_PAGES}';
                        $text = str_replace(['{PAGE_NUM}', '{TOTAL_PAGES}'], [$this->getAliasNumPage(), $this->getAliasNbPages()], $format);
                        $this->Cell(0, 10, $text, 0, false, 'C', 0, '', 0, false, 'T', 'M');
                    }
                }

                public static function convertHexToRgb(string $hex): array
                {
                    $hex = ltrim($hex, '#');
                    if (strlen($hex) === 3) {
                        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
                    }
                    return [
                        hexdec(substr($hex, 0, 2)),
                        hexdec(substr($hex, 2, 2)),
                        hexdec(substr($hex, 4, 2))
                    ];
                }
            };

            $meta = $pdfInfo['metadata'] ?? [];
            $pdf->SetTitle($meta['Title'] ?? '');
            $pdf->SetAuthor($meta['Author'] ?? '');
            $pdf->SetSubject($meta['Subject'] ?? '');
            $pdf->SetKeywords($meta['Keywords'] ?? '');
            $pdf->SetCreator($meta['Creator'] ?? '');

            $m = $size['margins'] ?? ['left' => 15, 'top' => 20, 'right' => 15, 'bottom' => 20];
            $pdf->SetMargins($m['left'], $m['top'], $m['right']);
            $pdf->SetHeaderMargin(Arr::get($headerFooter['margin'], 'header', 5));
            $pdf->SetFooterMargin(Arr::get($headerFooter['margin'], 'footer', 5));
            $pdf->SetAutoPageBreak($size['auto_page_break'] ?? true, $size['break_margin'] ?? 10);

            $pdf->dynamic = [
                'headerHtml' => $headerFooter['header'] ?? null,
                'footerHtml' => $headerFooter['footer'] ?? null,
                'footer_space' => Arr::get($headerFooter['height'], 'footer', 15),
                'repeat_header' => $headerFooter['repeat_header'] ?? true,
                'repeat_footer' => $headerFooter['repeat_footer'] ?? true,
                'pagination' => $layoutOptions['pagination'] ?? false,
                'pagination_format' => $layoutOptions['pagination_format'] ?? 'Page {PAGE_NUM} of {TOTAL_PAGES}',
                'watermark' => $layoutOptions['watermark'] ?? null,
                'background' => $layoutOptions['background'] ?? null,
                'font_family' => $layoutOptions['font_family'] ?? 'helvetica',
            ];

            $pdf->overridePerPage = $headerFooter['override_per_page'] ?? [];

            if ($layoutOptions['RTL']) {
                $pdf->setRTL(true);
            }

            $style = ($layoutOptions['bold'] ? 'B' : '') . ($layoutOptions['italic'] ? 'I' : '') . ($layoutOptions['underline'] ? 'U' : '');
            $pdf->SetFont($layoutOptions['font_family'], $style, $layoutOptions['font_size']);
            [$r, $g, $b] = $pdf->convertHexToRgb($layoutOptions['font_color']);
            $pdf->SetTextColor($r, $g, $b);

            $pdf->AddPage();

            if ($size['columns'] > 1) {
                $colWidth = $pdf->getPageWidth() / $size['columns'];
                $pdf->SetEqualColumns($size['columns'], $colWidth);
            }

            if (!empty($layoutOptions['custom_css'])) {
                $html = $layoutOptions['custom_css'] . $html;
            }

            $pdf->writeHTML($html, true, false, true, false, $layoutOptions['align']);

            if ($outputOptions['debug']) {
                $pdf->SetDisplayMode('fullpage');
            }

            return $pdf->Output('', 'S');
        } catch (Throwable $e) {
            Log::error("TCPDF rendering failed: " . $e->getMessage(), ['exception' => $e]);
            throw new RuntimeException("TCPDF rendering failed: " . $e->getMessage(), 0, $e);
        }
    }
}