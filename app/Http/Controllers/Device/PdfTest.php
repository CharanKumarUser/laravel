<?php

namespace App\Http\Controllers\Device;

use App\Http\Controllers\Controller;
use App\Services\Reports\PdfService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PdfTest extends Controller
{
    /**
     * Check PDF generation with 100 random attendance records in landscape with watermark.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function check(Request $request)
    {
        try {
            $pdfService = new PdfService();

            // Generate 100 random attendance records
            $attendanceData = [];
            $statuses = ['Present (PF)', 'Absent', 'Present (AB)', 'Present (CONF)', 'Abnormal (CINF)'];
            $shifts = ['Day Shift', 'Day 1', 'Day 2', 'Night Shift', 'N/A'];
            $names = ['Naveen Kumar', 'Sharath Kumar', 'Vinay A', 'M Ashok Nanda', 'Mohammed Suhail', 'Akram Basha', 'J S Chandra Sekar', 'M Santhosh Kumar', 'Tej Kiran', 'ANANGI JOSHNA'];

            for ($i = 1; $i <= 100; $i++) {
                $gottId = 'GT-' . str_pad(mt_rand(1000, 99999), 5, '0', STR_PAD_LEFT);
                $name = $names[array_rand($names)];
                $shift = $shifts[array_rand($shifts)];
                $checkIn = sprintf('%02d:%02d:%02d', mt_rand(8, 10), mt_rand(0, 59), mt_rand(0, 59));
                $lateIn = $checkIn > '09:00:00' ? sprintf('00:%02d:%02d', mt_rand(0, 59), mt_rand(0, 59)) : '';
                $checkOut = sprintf('%02d:%02d:%02d', mt_rand(17, 19), mt_rand(0, 59), mt_rand(0, 59));
                $earlyOut = $checkOut < '17:00:00' ? '' : '';
                $work = $checkIn && $checkOut ? gmdate('H:i:s', mt_rand(28800, 36000)) : '00:00:00';
                $overtime = $work > '08:00:00' ? gmdate('H:i:s', mt_rand(0, 3600)) : '00:00:00';
                $status = $statuses[array_rand($statuses)];

                $attendanceData[] = [
                    'SN' => $i,
                    'Gott-id' => $gottId,
                    'Name' => $name,
                    'Shift' => $shift,
                    'Check-In' => $checkIn,
                    'Late-In' => $lateIn,
                    'Check-Out' => $checkOut,
                    'Early-Out' => $earlyOut,
                    'Breaks' => '',
                    'Work' => $work,
                    'Overtime' => $overtime,
                    'Status' => $status,
                ];
            }

            // Generate HTML content
            $html = '<h2 style="text-align: center;">Attendance Report (Detailed View)</h2>';
            $html .= '<p style="text-align: center;">Date: ' . date('Y-m-d H:i A', strtotime('04:25 PM IST')) . '</p>';
            $html .= '<p>Department: Unassigned</p>';
            $html .= '<table style="width: 100%; border-collapse: collapse;">';
            $html .= '<thead>';
            $html .= '<tr style="background-color: #f2f2f2;">';
            $html .= '<th style="border: 1px solid #000; padding: 8px;">SN</th>';
            $html .= '<th style="border: 1px solid #000; padding: 8px;">Gott-id</th>';
            $html .= '<th style="border: 1px solid #000; padding: 8px;">Name</th>';
            $html .= '<th style="border: 1px solid #000; padding: 8px;">Shift</th>';
            $html .= '<th style="border: 1px solid #000; padding: 8px;">Check-In</th>';
            $html .= '<th style="border: 1px solid #000; padding: 8px;">Late-In</th>';
            $html .= '<th style="border: 1px solid #000; padding: 8px;">Check-Out</th>';
            $html .= '<th style="border: 1px solid #000; padding: 8px;">Early-Out</th>';
            $html .= '<th style="border: 1px solid #000; padding: 8px;">Breaks</th>';
            $html .= '<th style="border: 1px solid #000; padding: 8px;">Work</th>';
            $html .= '<th style="border: 1px solid #000; padding: 8px;">Overtime</th>';
            $html .= '<th style="border: 1px solid #000; padding: 8px;">Status</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';

            foreach ($attendanceData as $record) {
                $html .= '<tr>';
                $html .= '<td style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars($record['SN']) . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars($record['Gott-id']) . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars($record['Name']) . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars($record['Shift']) . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars($record['Check-In']) . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars($record['Late-In']) . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars($record['Check-Out']) . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars($record['Early-Out']) . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars($record['Breaks']) . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars($record['Work']) . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars($record['Overtime']) . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 8px;">' . htmlspecialchars($record['Status']) . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';
            $html .= '<p style="text-align: center;">Powered by Digital Kuppam</p>';

            // PDF configuration
            $pdfInfo = [
                'metadata' => [
                    'Title' => 'Attendance Report (Detailed View)',
                    'Author' => 'HR System',
                    'Subject' => 'Attendance Records',
                    'Keywords' => 'attendance, report, HR',
                    'Creator' => 'PdfTest',
                ],
            ];

            $size = [
                'format' => 'A4',
                'orientation' => 'landscape',
                'margins' => ['top' => 30, 'bottom' => 20, 'left' => 10, 'right' => 10],
            ];

            $headerFooter = [
                'header' => [
                    'left' => 'Organization: Digital Kuppam',
                    'center' => 'Attendance Report (Detailed View)<br>' . date('Y-m-d H:i A', strtotime('04:25 PM IST')) . ' To ' . date('Y-m-d H:i A', strtotime('04:25 PM IST +30 days')),
                    'right' => 'Printed On: ' . date('Y-m-d H:i:s'),
                ],
                'footer' => [
                    'left' => 'Generated by Got It',
                    'center' => '',
                    'right' => 'Page {PAGE_NUM} of {TOTAL_PAGES}',
                ],
                'height' => ['header' => 20, 'footer' => 15],
                'margin' => ['header' => 5, 'footer' => 5],
                'repeat_header' => true,
                'repeat_footer' => true,
            ];

            $layoutOptions = [
                'pagination' => true,
                'pagination_format' => 'Page {PAGE_NUM} of {TOTAL_PAGES}',
                'font_family' => 'helvetica',
                'font_size' => 10,
                'font_color' => '#000000',
                'align' => 'L',
                'table_styles' => ['border' => '1px solid #000', 'border-collapse' => 'collapse'],
                'auto_scale_images' => true,
                'watermark' => [
                    'image' => 'https://images.unsplash.com/photo-1501854140801-50d01698950b?fit=crop&w=1350&q=80',
                    'opacity' => 0.2,
                    'rotation' => 45,
                ],
            ];

            $outputOptions = [
                'download' => true,
                'filename' => 'attendance_report_' . date('YmdHis') . '.pdf',
                'compress' => true,
                'debug' => false,
            ];

            // Generate PDF
            $response = $pdfService->generate($pdfInfo, $size, $html, $headerFooter, $layoutOptions, $outputOptions);

            return $response;
        } catch (Exception $e) {
            Log::error('Error generating attendance report PDF: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Failed to generate PDF', 'message' => $e->getMessage()], 500);
        }
    }
}