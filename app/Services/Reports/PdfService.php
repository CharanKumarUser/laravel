<?php

namespace App\Services\Reports;

use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TCPDF;
use Throwable;

class PdfService
{
    /**
     * Generate a PDF using TCPDF.
     *
     * @param array $pdfInfo required group: metadata + optional protection/signature
     * @param array $size required page settings
     * @param string $data required HTML (with Base64 assets embedded)
     * @param array|null $headerFooter optional header/footer config
     * @param array|null $layoutOptions optional layout settings
     * @param array|null $outputOptions optional output controls
     *
     * @return Response|StreamedResponse
     *
     * @throws InvalidArgumentException|RuntimeException
     */
    public function generate(
        array $pdfInfo,
        array $size,
        string $data,
        ?array $headerFooter = null,
        ?array $layoutOptions = null,
        ?array $outputOptions = null
    ): Response|StreamedResponse {
        try {
            $this->validateRequired($pdfInfo, $size, $data);

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
                $cacheKey = 'pdf_' . md5(serialize([$pdfInfo, $size, $data, $headerFooter, $layoutOptions, $outputOptions]));
                $cacheDriver = $outputOptions['cache_driver'] ?? 'file';
                $cacheTtl = $outputOptions['cache_ttl'] ?? 3600;

                if ($cached = Cache::store($cacheDriver)->get($cacheKey)) {
                    return $this->buildResponse($cached, $outputOptions);
                }
            }

            if ($outputOptions['threads'] > 1 || !empty($outputOptions['orchestration'])) {
                throw new RuntimeException('Multithreading and orchestration not implemented.');
            }

            $pdfString = $this->renderWithTcpdf($data, $size, $pdfInfo, $headerFooter, $layoutOptions, $outputOptions);

            if ($cacheKey) {
                Cache::store($cacheDriver)->put($cacheKey, $pdfString, $cacheTtl);
            }

            return $outputOptions['streaming']['enabled']
                ? $this->streamResponse($pdfString, $outputOptions)
                : $this->buildResponse($pdfString, $outputOptions);
        } catch (Throwable $e) {
            Log::error("PdfService::generate error: {$e->getMessage()}", [
                'exception' => $e,
                'size' => $size,
                'pdfInfo' => $pdfInfo,
            ]);
            throw new RuntimeException("Failed to generate PDF: {$e->getMessage()}", 0, $e);
        }
    }

    protected function validateRequired(array $pdfInfo, array $size, string $data): void
    {
        if (empty($pdfInfo['metadata']) || !is_array($pdfInfo['metadata'])) {
            throw new InvalidArgumentException('pdfInfo must have a metadata sub-array.');
        }

        if (empty($size['format'])) {
            throw new InvalidArgumentException('size must have a format key.');
        }

        if (trim($data) === '') {
            throw new InvalidArgumentException('data cannot be empty.');
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

        if (is_array($result['format']) && (count($result['format']) !== 2 || !is_numeric($result['format'][0]) || !is_numeric($result['format'][1]))) {
            throw new InvalidArgumentException('Custom format must be [width, height] numeric.');
        } elseif (!is_array($result['format']) && !in_array($result['format'], ['A0','A1','A2','A3','A4','A5','A6','B0','B1','B2','B3','B4','B5','Letter','Legal','Tabloid','Executive','Folio'], true)) {
            throw new InvalidArgumentException('Invalid page format.');
        }

        if (!is_array($result['margins']) || count(array_intersect_key(array_flip(['top','bottom','left','right']), $result['margins'])) !== 4) {
            throw new InvalidArgumentException('margins must have top, bottom, left, right.');
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

        if (isset($result['protection']) && !is_array($result['protection'])) {
            throw new InvalidArgumentException('protection must be array.');
        }

        if (isset($result['signature']) && !is_array($result['signature'])) {
            throw new InvalidArgumentException('signature must be array.');
        }

        return $result;
    }

    protected function normalizeHeaderFooter(array $hf): array
    {
        $defaults = [
            'header' => ['left' => '', 'center' => '', 'right' => ''],
            'footer' => ['left' => '', 'center' => '', 'right' => ''],
            'height' => ['header' => 20, 'footer' => 15],
            'margin' => ['header' => 5, 'footer' => 5],
            'repeat_header' => true,
            'repeat_footer' => true,
            'override_per_page' => [],
        ];

        $result = array_merge($defaults, $hf);

        if (!is_array($result['header'])) {
            $result['header'] = ['center' => $result['header'] ?? ''];
        }
        if (!is_array($result['footer'])) {
            $result['footer'] = ['center' => $result['footer'] ?? ''];
        }

        if (!is_array($result['height']) || !isset($result['height']['header'], $result['height']['footer'])) {
            throw new InvalidArgumentException('height must have header and footer.');
        }

        if (!is_array($result['margin']) || !isset($result['margin']['header'], $result['margin']['footer'])) {
            throw new InvalidArgumentException('margin must have header and footer.');
        }

        return $result;
    }

    protected function normalizeLayoutOptions(array $opts): array
    {
        $defaults = [
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
            'watermark' => null,
            'background' => null,
            'RTL' => false,
        ];

        $result = array_merge($defaults, $opts);

        $imageCss = $result['auto_scale_images'] ? 'img { max-width: 100%; height: auto; }' : '';
        if ($result['max_image_width']) $imageCss .= "img { max-width: {$result['max_image_width']}px; }";
        if ($result['max_image_height']) $imageCss .= "img { max-height: {$result['max_image_height']}px; }";
        if ($imageCss) {
            $result['custom_css'] = ($result['custom_css'] ?? '') . "<style>$imageCss</style>";
        }

        if (!empty($result['table_styles'])) {
            $tableCss = 'table { ' . implode('; ', $result['table_styles']) . '; }';
            $result['custom_css'] .= "<style>$tableCss</style>";
        }

        if (!empty($result['list_styles'])) {
            $listCss = 'ul, ol { ' . implode('; ', $result['list_styles']) . '; }';
            $result['custom_css'] .= "<style>$listCss</style>";
        }

        return $result;
    }

    protected function normalizeOutputOptions(array $opts): array
    {
        $defaults = [
            'download' => true,
            'filename' => 'document.pdf',
            'compress' => true,
            'cache' => false,
            'cache_driver' => 'file',
            'cache_ttl' => 3600,
            'threads' => 1,
            'orchestration' => [],
            'streaming' => ['enabled' => false, 'buffer_size' => 1024 * 1024, 'flush_interval' => 2],
            'debug' => false,
        ];

        $result = array_merge($defaults, $opts);

        return $result;
    }

    protected function buildResponse(string $pdfString, array $outputOptions): Response
    {
        $disposition = $outputOptions['download'] ? 'attachment' : 'inline';

        return new Response($pdfString, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Length' => strlen($pdfString),
            'Content-Disposition' => $disposition . '; filename="' . $outputOptions['filename'] . '"',
        ]);
    }

    protected function streamResponse(string $pdfString, array $outputOptions): StreamedResponse
    {
        $bufferSize = $outputOptions['streaming']['buffer_size'];
        $flushInterval = $outputOptions['streaming']['flush_interval'];
        $disposition = $outputOptions['download'] ? 'attachment' : 'inline';

        return new StreamedResponse(function () use ($pdfString, $bufferSize, $flushInterval) {
            $start = time();
            $len = strlen($pdfString);
            for ($i = 0; $i < $len; $i += $bufferSize) {
                echo substr($pdfString, $i, $bufferSize);
                flush();
                if (time() - $start >= $flushInterval) $start = time();
            }
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Transfer-Encoding' => 'chunked',
            'Content-Disposition' => $disposition . '; filename="' . $outputOptions['filename'] . '"',
        ]);
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
            $orientation = strtoupper($size['orientation'][0]) === 'L' ? 'L' : 'P';
            $unit = $size['unit'];
            $format = $size['format'];

            $pdf = new class($orientation, $unit, $format, true, 'UTF-8', false) extends TCPDF {
                public array $dynamic = [];
                public array $overridePerPage = [];

                protected function replacePlaceholders(string $text): string
                {
                    return str_replace(
                        ['{PAGE_NUM}', '{TOTAL_PAGES}'],
                        [$this->getAliasNumPage(), $this->getAliasNbPages()],
                        $text
                    );
                }

                public function Header(): void
                {
                    $page = $this->getPage();
                    if (!$this->dynamic['repeat_header'] && $page > 1) return;

                    $header = $this->dynamic['header'] ?? [];
                    if (isset($this->overridePerPage[$page]['header'])) {
                        $header = $this->overridePerPage[$page]['header'];
                    }

                    $contentWidth = $this->getPageWidth() - $this->lMargin - $this->rMargin;
                    $colWidth = $contentWidth / 3;
                    $y = $this->getHeaderMargin();
                    $h = $this->dynamic['header_height'];

                    $this->handleBackground();
                    $this->handleWatermark();

                    $this->SetFont($this->dynamic['font_family'], '', 10);

                    // Left
                    if ($left = $header['left'] ?? '') {
                        $this->SetXY($this->lMargin, $y);
                        $this->writeHTMLCell($colWidth, $h, '', '', $this->replacePlaceholders($left), 0, 1, false, true, 'L');
                    }

                    // Center
                    if ($center = $header['center'] ?? '') {
                        $this->SetXY($this->lMargin + $colWidth, $y);
                        $this->writeHTMLCell($colWidth, $h, '', '', $this->replacePlaceholders($center), 0, 1, false, true, 'C');
                    }

                    // Right
                    if ($right = $header['right'] ?? '') {
                        $this->SetXY($this->lMargin + 2 * $colWidth, $y);
                        $this->writeHTMLCell($colWidth, $h, '', '', $this->replacePlaceholders($right), 0, 1, false, true, 'R');
                    }
                }

                public function Footer(): void
                {
                    $page = $this->getPage();
                    if (!$this->dynamic['repeat_footer'] && $page > 1) return;

                    $footer = $this->dynamic['footer'] ?? [];
                    if (isset($this->overridePerPage[$page]['footer'])) {
                        $footer = $this->overridePerPage[$page]['footer'];
                    }

                    $contentWidth = $this->getPageWidth() - $this->lMargin - $this->rMargin;
                    $colWidth = $contentWidth / 3;
                    $y = - $this->dynamic['footer_height'];
                    $h = $this->dynamic['footer_height'];

                    $this->SetY($y);
                    $this->SetFont($this->dynamic['font_family'], '', 8);

                    // Left
                    if ($left = $footer['left'] ?? '') {
                        $this->SetX($this->lMargin);
                        $this->writeHTMLCell($colWidth, $h, '', '', $this->replacePlaceholders($left), 0, 1, false, true, 'L');
                    }

                    // Center
                    if ($center = $footer['center'] ?? '') {
                        $this->SetX($this->lMargin + $colWidth);
                        $this->writeHTMLCell($colWidth, $h, '', '', $this->replacePlaceholders($center), 0, 1, false, true, 'C');
                    }

                    // Right
                    if ($right = $footer['right'] ?? '') {
                        $this->SetX($this->lMargin + 2 * $colWidth);
                        $this->writeHTMLCell($colWidth, $h, '', '', $this->replacePlaceholders($right), 0, 1, false, true, 'R');
                    }
                }

                protected function handleBackground(): void
                {
                    if ($bg = $this->dynamic['background'] ?? null) {
                        if ($color = $bg['color'] ?? null) {
                            [$r, $g, $b] = self::convertHexToRgb($color);
                            $this->SetFillColor($r, $g, $b);
                            $this->Rect(0, 0, $this->getPageWidth(), $this->getPageHeight(), 'F');
                        }
                        if ($image = $bg['image'] ?? null) {
                            $repeat = $bg['repeat'] ?? false;
                            $this->Image($image, 0, 0, $this->getPageWidth(), $this->getPageHeight(), '', '', '', $repeat, 300, '', false, false, 0);
                        }
                    }
                }

                protected function handleWatermark(): void
                {
                    if ($wm = $this->dynamic['watermark'] ?? null) {
                        $opacity = $wm['opacity'] ?? 0.5;
                        $rotation = $wm['rotation'] ?? 45;
                        $this->SetAlpha($opacity);
                        if ($text = $wm['text'] ?? null) {
                            $this->SetTextColor(200, 200, 200);
                            $this->SetFontSize(50);
                            $this->StartTransform();
                            $this->Rotate($rotation, $this->getPageWidth() / 2, $this->getPageHeight() / 2);
                            $this->Text($this->getPageWidth() / 2 - 100, $this->getPageHeight() / 2, $text);
                            $this->StopTransform();
                        }
                        if ($image = $wm['image'] ?? null) {
                            $this->Image($image, 0, 0, $this->getPageWidth(), $this->getPageHeight());
                        }
                        $this->SetAlpha(1);
                    }
                }

                public static function convertHexToRgb(string $hex): array
                {
                    $hex = ltrim($hex, '#');
                    if (strlen($hex) == 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
                    return [hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2))];
                }
            };

            $meta = $pdfInfo['metadata'];
            $pdf->SetTitle($meta['Title'] ?? 'Document');
            $pdf->SetAuthor($meta['Author'] ?? 'System');
            $pdf->SetSubject($meta['Subject'] ?? '');
            $pdf->SetKeywords($meta['Keywords'] ?? '');
            $pdf->SetCreator($meta['Creator'] ?? 'PdfService');

            $m = $size['margins'];
            $pdf->SetMargins($m['left'], $m['top'], $m['right']);
            $pdf->SetHeaderMargin($headerFooter['margin']['header']);
            $pdf->SetFooterMargin($headerFooter['margin']['footer']);
            $pdf->SetAutoPageBreak($size['auto_page_break'], $size['break_margin']);
            $pdf->SetCompression($outputOptions['compress']);

            $pdf->dynamic = [
                'header' => $headerFooter['header'],
                'footer' => $headerFooter['footer'],
                'header_height' => $headerFooter['height']['header'],
                'footer_height' => $headerFooter['height']['footer'],
                'repeat_header' => $headerFooter['repeat_header'],
                'repeat_footer' => $headerFooter['repeat_footer'],
                'watermark' => $layoutOptions['watermark'],
                'background' => $layoutOptions['background'],
                'font_family' => $layoutOptions['font_family'],
            ];

            $pdf->overridePerPage = $headerFooter['override_per_page'];

            if ($layoutOptions['RTL']) $pdf->setRTL(true);

            $style = ($layoutOptions['bold'] ? 'B' : '') . ($layoutOptions['italic'] ? 'I' : '') . ($layoutOptions['underline'] ? 'U' : '');
            $pdf->SetFont($layoutOptions['font_family'], $style, $layoutOptions['font_size']);
            [$r, $g, $b] = $pdf->convertHexToRgb($layoutOptions['font_color']);
            $pdf->SetTextColor($r, $g, $b);

            if ($sig = $pdfInfo['signature'] ?? null) {
                $pdf->setSignature(
                    $sig['certificate'] ?? '',
                    $sig['private_key'] ?? '',
                    $sig['password'] ?? '',
                    $sig['extracerts'] ?? '',
                    $sig['cert_type'] ?? 2,
                    $sig['info'] ?? []
                );
            }

            $pdf->AddPage();

            if ($size['columns'] > 1) {
                $colWidth = $pdf->getPageWidth() / $size['columns'];
                $pdf->SetEqualColumns($size['columns'], $colWidth);
            }

            if ($layoutOptions['custom_css']) $html = $layoutOptions['custom_css'] . $html;

            $pdf->writeHTML($html, true, false, true, false, $layoutOptions['align']);

            if ($prot = $pdfInfo['protection'] ?? null) {
                $mode = $pdfInfo['encryption_level'] == 256 ? 3 : ($pdfInfo['encryption_level'] == 128 ? 1 : 0);
                $pdf->SetProtection($prot, $pdfInfo['password'], $pdfInfo['owner_password'], $mode);
            }

            if ($outputOptions['debug']) $pdf->SetDisplayMode('fullpage');

            return $pdf->Output('', 'S');
        } catch (Throwable $e) {
            Log::error("TCPDF rendering failed: {$e->getMessage()}", ['exception' => $e]);
            throw new RuntimeException("TCPDF rendering failed: {$e->getMessage()}", 0, $e);
        }
    }
}