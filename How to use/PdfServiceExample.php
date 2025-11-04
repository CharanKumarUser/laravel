<?php

namespace App\Services;

use App\Helpers\FileHelper;
use App\Services\PdfService;

class PdfServiceExamples
{
    protected PdfService $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Example 1: Generate a simple payslip with DomPDF
     */
    public function generatePayslipDomPdf(): \Symfony\Component\HttpFoundation\Response
    {
        $html = <<<HTML
<html>
<head><style>
    body { font-family: Arial, sans-serif; }
    .payslip { border: 1px solid #ccc; padding: 20px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style></head>
<body>
    <div class="payslip">
        <h1>Payslip for October 2025</h1>
        <p><strong>Employee:</strong> John Doe</p>
        <table>
            <tr><th>Earnings</th><th>Amount</th></tr>
            <tr><td>Basic Salary</td><td>$5000</td></tr>
            <tr><td>Bonus</td><td>$500</td></tr>
            <tr><td>Total</td><td>$5500</td></tr>
        </table>
    </div>
</body>
</html>
HTML;

        return $this->pdfService->generate(
            type: 's',
            pdfInfo: ['metadata' => ['Title' => 'Payslip October 2025', 'Author' => 'HRM System']],
            size: ['format' => 'A4', 'orientation' => 'portrait'],
            data: $html,
            outputOptions: ['filename' => 'payslip_oct2025.pdf', 'download' => true]
        );
    }

    /**
     * Example 2: Generate an invoice with TCPDF and watermark
     */
    public function generateInvoiceTcpdf(): \Symfony\Component\HttpFoundation\Response
    {
        $logoBase64 = FileHelper::fileToBase64(storage_path('app/public/logo.png'));

        $html = <<<HTML
<html>
<head><style>
    .invoice { font-family: Helvetica; margin: 20px; }
    .logo { width: 100px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #000; padding: 10px; }
</style></head>
<body>
    <div class="invoice">
        <img src="$logoBase64" class="logo"/>
        <h1>Invoice #INV123</h1>
        <p><strong>Client:</strong> Jane Smith</p>
        <table>
            <tr><th>Item</th><th>Qty</th><th>Price</th></tr>
            <tr><td>Product A</td><td>2</td><td>$100</td></tr>
            <tr><td>Product B</td><td>1</td><td>$150</td></tr>
        </table>
    </div>
</body>
</html>
HTML;

        return $this->pdfService->generate(
            type: 'c',
            pdfInfo: [
                'metadata' => ['Title' => 'Invoice INV123', 'Author' => 'HRM System'],
                'protection' => ['copy', 'print'],
                'password' => 'client123'
            ],
            size: ['format' => 'A4', 'orientation' => 'portrait'],
            data: $html,
            layoutOptions: [
                'watermark' => ['text' => 'CONFIDENTIAL', 'opacity' => 0.2, 'rotation' => 45]
            ],
            outputOptions: ['filename' => 'invoice_inv123.pdf']
        );
    }

    /**
     * Example 3: Generate a certificate with DomPDF
     */
    public function generateCertificateDomPdf(): \Symfony\Component\HttpFoundation\Response
    {
        $html = <<<HTML
<html>
<head><style>
    .certificate { text-align: center; font-family: Times; padding: 50px; }
    .border { border: 5px double #000; padding: 20px; }
    h1 { font-size: 36px; }
</style></head>
<body>
    <div class="certificate">
        <div class="border">
            <h1>Certificate of Achievement</h1>
            <p>Awarded to <strong>Alice Brown</strong></p>
            <p>For completing the Leadership Training Program</p>
            <p>Date: September 29, 2025</p>
        </div>
    </div>
</body>
</html>
HTML;

        return $this->pdfService->generate(
            type: 's',
            pdfInfo: ['metadata' => ['Title' => 'Certificate of Achievement']],
            size: ['format' => 'A4', 'orientation' => 'landscape'],
            data: $html,
            headerFooter: ['header' => '<h3>Company Name</h3>'],
            outputOptions: ['filename' => 'certificate_alice.pdf']
        );
    }

    /**
     * Example 4: Generate a formal letter with TCPDF
     */
    public function generateLetterTcpdf(): \Symfony\Component\HttpFoundation\Response
    {
        $html = <<<HTML
<html>
<head><style>
    body { font-family: Times; margin: 40px; }
    .letterhead { text-align: center; }
</style></head>
<body>
    <div class="letterhead">
        <h2>HRM Corp</h2>
        <p>123 Business St, City, Country</p>
    </div>
    <p>Dear Mr. Smith,</p>
    <p>We are pleased to offer you the position of Senior Manager...</p>
    <p>Sincerely,<br>HR Department</p>
</body>
</html>
HTML;

        return $this->pdfService->generate(
            type: 'c',
            pdfInfo: ['metadata' => ['Title' => 'Offer Letter', 'Author' => 'HRM Corp']],
            size: ['format' => 'Letter'],
            data: $html,
            outputOptions: ['filename' => 'offer_letter.pdf']
        );
    }

    /**
     * Example 5: Generate a report with pagination
     */
    public function generateReportDomPdf(): \Symfony\Component\HttpFoundation\Response
    {
        $html = <<<HTML
<html>
<head><style>
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #000; padding: 5px; }
</style></head>
<body>
    <h1>Employee Performance Report</h1>
    <table>
        <tr><th>Name</th><th>Score</th></tr>
        <tr><td>John</td><td>85</td></tr>
        <tr><td>Jane</td><td>90</td></tr>
    </table>
</body>
</html>
HTML;

        return $this->pdfService->generate(
            type: 's',
            pdfInfo: ['metadata' => ['Title' => 'Performance Report']],
            size: ['format' => 'A4'],
            data: $html,
            layoutOptions: ['pagination' => true, 'pagination_format' => 'Page {PAGE_NUM}/{TOTAL_PAGES}'],
            outputOptions: ['filename' => 'performance_report.pdf']
        );
    }

    /**
     * Example 6: Generate a contract with digital signature
     */
    public function generateContractTcpdf(): \Symfony\Component\HttpFoundation\Response
    {
        $html = <<<HTML
<html>
<head><style>
    body { font-family: Helvetica; }
    .contract { margin: 20px; }
</style></head>
<body>
    <div class="contract">
        <h1>Employment Contract</h1>
        <p>This agreement is between HRM Corp and Employee...</p>
    </div>
</body>
</html>
HTML;

        return $this->pdfService->generate(
            type: 'c',
            pdfInfo: [
                'metadata' => ['Title' => 'Employment Contract'],
                'signature' => [
                    'cert_file' => storage_path('app/cert.p12'),
                    'password' => 'certpass',
                    'info' => ['Name' => 'HRM Corp', 'Reason' => 'Agreement Signing'],
                    'visible' => true,
                    'x' => 150, 'y' => 250, 'width' => 50, 'height' => 50
                ]
            ],
            size: ['format' => 'A4'],
            data: $html,
            outputOptions: ['filename' => 'contract.pdf']
        );
    }

    /**
     * Example 7: Generate a memo with background image
     */
    public function generateMemoDomPdf(): \Symfony\Component\HttpFoundation\Response
    {
        $bgImage = FileHelper::fileToBase64(storage_path('app/public/background.png'));

        $html = <<<HTML
<html>
<head><style>
    body { font-family: Arial; }
    .memo { padding: 20px; }
</style></head>
<body>
    <div class="memo">
        <h1>Internal Memo</h1>
        <p>To: All Employees</p>
        <p>Subject: Office Closure</p>
        <p>The office will be closed on...</p>
    </div>
</body>
</html>
HTML;

        return $this->pdfService->generate(
            type: 's',
            pdfInfo: ['metadata' => ['Title' => 'Internal Memo']],
            size: ['format' => 'A4'],
            data: $html,
            layoutOptions: ['background' => ['image' => $bgImage, 'opacity' => 0.5]],
            outputOptions: ['filename' => 'memo.pdf']
        );
    }

    /**
     * Example 8: Generate a timesheet with table styling
     */
    public function generateTimesheetTcpdf(): \Symfony\Component\HttpFoundation\Response
    {
        $html = <<<HTML
<html>
<head><style>
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #000; padding: 8px; }
    th { background-color: #eee; }
</style></head>
<body>
    <h1>Weekly Timesheet</h1>
    <table>
        <tr><th>Date</th><th>Hours</th></tr>
        <tr><td>2025-09-29</td><td>8</td></tr>
        <tr><td>2025-09-30</td><td>7</td></tr>
    </table>
</body>
</html>
HTML;

        return $this->pdfService->generate(
            type: 'c',
            pdfInfo: ['metadata' => ['Title' => 'Weekly Timesheet']],
            size: ['format' => 'A4'],
            data: $html,
            layoutOptions: ['table_styles' => ['border' => 1, 'header_bg' => '#eee']],
            outputOptions: ['filename' => 'timesheet.pdf']
        );
    }

    /**
     * Example 9: Generate a leave request form
     */
    public function generateLeaveRequestDomPdf(): \Symfony\Component\HttpFoundation\Response
    {
        $html = <<<HTML
<html>
<head><style>
    .form { font-family: Arial; padding: 20px; }
    .field { margin-bottom: 10px; }
</style></head>
<body>
    <div class="form">
        <h1>Leave Request Form</h1>
        <div class="field"><strong>Name:</strong> John Doe</div>
        <div class="field"><strong>Start Date:</strong> 2025-10-01</div>
        <div class="field"><strong>End Date:</strong> 2025-10-05</div>
    </div>
</body>
</html>
HTML;

        return $this->pdfService->generate(
            type: 's',
            pdfInfo: ['metadata' => ['Title' => 'Leave Request']],
            size: ['format' => 'A4'],
            data: $html,
            outputOptions: ['filename' => 'leave_request.pdf']
        );
    }

    /**
     * Example 10: Generate a performance review
     */
    public function generatePerformanceReviewTcpdf(): \Symfony\Component\HttpFoundation\Response
    {
        $html = <<<HTML
<html>
<head><style>
    body { font-family: Helvetica; }
    .review { margin: 20px; }
</style></head>
<body>
    <div class="review">
        <h1>Performance Review</h1>
        <p><strong>Employee:</strong> Jane Doe</p>
        <p><strong>Score:</strong> 92/100</p>
        <p><strong>Comments:</strong> Excellent performance...</p>
    </div>
</body>
</html>
HTML;

        return $this->pdfService->generate(
            type: 'c',
            pdfInfo: ['metadata' => ['Title' => 'Performance Review']],
            size: ['format' => 'A4'],
            data: $html,
            outputOptions: ['filename' => 'performance_review.pdf']
        );
    }

    /**
     * Example 11: Generate a training schedule
     */
    public function generateTrainingScheduleDomPdf(): \Symfony\Component\HttpFoundation\Response
    {
        $html = <<<HTML
<html>
<head><style>
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #000; padding: 10px; }
</style></head>
<body>
    <h1>Training Schedule</h1>
    <table>
        <tr><th>Date</th><th>Topic</th></tr>
        <tr><td>2025-10-01</td><td>Leadership</td></tr>
        <tr><td>2025-10-02</td><td>Team Building</td></tr>
    </table>
</body>
</html>
HTML;

        return $this->pdfService->generate(
            type: 's',
            pdfInfo: ['metadata' => ['Title' => 'Training Schedule']],
            size: ['format' => 'A4'],
            data: $html,
            outputOptions: ['filename' => 'training_schedule.pdf']
        );
    }

    /**
     * Example 12: Generate an expense report
     */
    public function generateExpenseReportTcpdf(): \Symfony\Component\HttpFoundation\Response
    {
        $html = <<<HTML
<html>
<head><style>
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #000; padding: 8px; }
</style></head>
<body>
    <h1>Expense Report</h1>
    <table>
        <tr><th>Date</th><th>Item</th><th>Amount</th></tr>
        <tr><td>2025-09-29</td><td>Travel</td><td>$200</td></tr>
        <tr><td>2025-09-30</td><td>Meals</td><td>$50</td></tr>
    </table>
</body>
</html>
HTML;

        return $this->pdfService->generate(
            type: 'c',
            pdfInfo: ['metadata' => ['Title' => 'Expense Report']],
            size: ['format' => 'A4'],
            data: $html,
            outputOptions: ['filename' => 'expense_report.pdf']
        );
    }

    /**
     * Example 13: Generate a policy document
     */
    public function generatePolicyDocumentDomPdf(): \Symfony\Component\HttpFoundation\Response
    {
        $html = <<<HTML
<html>
<head><style>
    body { font-family: Times; }
    .policy { margin: 30px; }
</style></head>
<body>
    <div class="policy">
        <h1>Company Policy</h1>
        <p>Our company values integrity, teamwork...</p>
    </div>
</body>
</html>
HTML;

        return $this->pdfService->generate(
            type: 's',
            pdfInfo: ['metadata' => ['Title' => 'Company Policy']],
            size: ['format' => 'A4'],
            data: $html,
            outputOptions: ['filename' => 'policy_document.pdf']
        );
    }

    /**
     * Example 14: Generate a meeting agenda
     */
    public function generateMeetingAgendaTcpdf(): \Symfony\Component\HttpFoundation\Response
    {
        $html = <<<HTML
<html>
<head><style>
    ul { list-style-type: disc; margin-left: 20px; }
</style></head>
<body>
    <h1>Meeting Agenda</h1>
    <p>Date: 2025-10-01</p>
    <ul>
        <li>Project Updates</li>
        <li>Budget Review</li>
    </ul>
</body>
</html>
HTML;

        return $this->pdfService->generate(
            type: 'c',
            pdfInfo: ['metadata' => ['Title' => 'Meeting Agenda']],
            size: ['format' => 'A4'],
            data: $html,
            layoutOptions: ['list_styles' => ['type' => 'ul', 'bullet' => '•']],
            outputOptions: ['filename' => 'meeting_agenda.pdf']
        );
    }

    /**
     * Example 15: Generate a job offer letter
     */
    public function generateJobOfferDomPdf(): \Symfony\Component\HttpFoundation\Response
    {
        $html = <<<HTML
<html>
<head><style>
    body { font-family: Arial; }
    .letter { margin: 40px; }
</style></head>
<body>
    <div class="letter">
        <h1>Job Offer</h1>
        <p>Dear Candidate,</p>
        <p>We are pleased to offer you...</p>
    </div>
</body>
</html>
HTML;

        return $this->pdfService->generate(
            type: 's',
            pdfInfo: ['metadata' => ['Title' => 'Job Offer']],
            size: ['format' => 'Letter'],
            data: $html,
            outputOptions: ['filename' => 'job_offer.pdf']
        );
    }

    /**
     * Example 16: Generate a purchase order
     */
    public function generatePurchaseOrderTcpdf(): \Symfony\Component\HttpFoundation\Response
    {
        $html = <<<HTML
<html>
<head><style>
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #000; padding: 8px; }
</style></head>
<body>
    <h1>Purchase Order #PO456</h1>
    <table>
        <tr><th>Item</th><th>Qty</th><th>Price</th></tr>
        <tr><td>Item A</td><td>10</td><td>$20</td></tr>
    </table>
</body>
</html>
HTML;

        return $this->pdfService->generate(
            type: 'c',
            pdfInfo: ['metadata' => ['Title' => 'Purchase Order']],
            size: ['format' => 'A4'],
            data: $html,
            outputOptions: ['filename' => 'purchase_order.pdf']
        );
    }

    /**
     * Example 17: Generate a resume
     */
    public function generateResumeDomPdf(): \Symfony\Component\HttpFoundation\Response
    {
        $html = <<<HTML
<html>
<head><style>
    body { font-family: Arial; }
    .resume { margin: 20px; }
</style></head>
<body>
    <div class="resume">
        <h1>John Doe</h1>
        <h2>Experience</h2>
        <p>Senior Developer, XYZ Corp, 2020-2025</p>
    </div>
</body>
</html>
HTML;

        return $this->pdfService->generate(
            type: 's',
            pdfInfo: ['metadata' => ['Title' => 'Resume']],
            size: ['format' => 'A4'],
            data: $html,
            outputOptions: ['filename' => 'resume.pdf']
        );
    }

    /**
     * Example 18: Generate a newsletter
     */
    public function generateNewsletterTcpdf(): \Symfony\Component\HttpFoundation\Response
    {
        $imageBase64 = FileHelper::fileToBase64(storage_path('app/public/newsletter_image.png'));

        $html = <<<HTML
<html>
<head><style>
    .newsletter { font-family: Helvetica; margin: 20px; }
    img { max-width: 300px; }
</style></head>
<body>
    <div class="newsletter">
        <h1>Monthly Newsletter</h1>
        <img src="$imageBase64"/>
        <p>Welcome to our October edition...</p>
    </div>
</body>
</html>
HTML;

        return $this->pdfService->generate(
            type: 'c',
            pdfInfo: ['metadata' => ['Title' => 'Newsletter']],
            size: ['format' => 'A4'],
            data: $html,
            outputOptions: ['filename' => 'newsletter.pdf']
        );
    }

    /**
     * Example 19: Generate a barcode invoice
     */
    public function generateBarcodeInvoiceTcpdf(): \Symfony\Component\HttpFoundation\Response
    {
        $barcodeBase64 = FileHelper::fileToBase64(storage_path('app/public/barcode.png'));

        $html = <<<HTML
<html>
<head><style>
    .invoice { font-family: Helvetica; margin: 20px; }
    img { width: 100px; }
</style></head>
<body>
    <div class="invoice">
        <h1>Invoice with Barcode</h1>
        <img src="$barcodeBase64"/>
        <p>Invoice #INV789</p>
    </div>
</body>
</html>
HTML;

        return $this->pdfService->generate(
            type: 'c',
            pdfInfo: ['metadata' => ['Title' => 'Barcode Invoice']],
            size: ['format' => 'A4'],
            data: $html,
            outputOptions: ['filename' => 'barcode_invoice.pdf']
        );
    }

    /**
     * Example 20: Generate a compliance certificate
     */
    public function generateComplianceCertificateDomPdf(): \Symfony\Component\HttpFoundation\Response
    {
        $html = <<<HTML
<html>
<head><style>
    .certificate { text-align: center; font-family: Times; padding: 50px; }
</style></head>
<body>
    <div class="certificate">
        <h1>Compliance Certificate</h1>
        <p>This certifies that HRM Corp complies with...</p>
    </div>
</body>
</html>
HTML;

        return $this->pdfService->generate(
            type: 's',
            pdfInfo: ['metadata' => ['Title' => 'Compliance Certificate']],
            size: ['format' => 'A4', 'orientation' => 'landscape'],
            data: $html,
            outputOptions: ['filename' => 'compliance_certificate.pdf']
        );
    }

    /**
     * Example 21: Generate a streamed large report
     */
    public function generateStreamedReportTcpdf(): \Symfony\Component\HttpFoundation\Response
    {
        $html = str_repeat('<p>Large report content...</p>', 1000); // Simulate large content

        return $this->pdfService->generate(
            type: 'c',
            pdfInfo: ['metadata' => ['Title' => 'Large Report']],
            size: ['format' => 'A4'],
            data: '<html><body>' . $html . '</body></html>',
            outputOptions: [
                'filename' => 'large_report.pdf',
                'streaming' => ['enabled' => true, 'buffer_size' => 1024 * 1024, 'flush_interval' => 2]
            ]
        );
    }
}

<?php

namespace App\Services;

use App\Helpers\FileHelper;
use App\Services\PdfService;

class PdfServiceExamples
{
    protected PdfService $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Complex Example 1: Comprehensive Invoice with DomPDF - Includes metadata, custom size, header/footer, pagination, watermark, background, custom CSS, image scaling, caching, and streaming.
     */
    public function complexInvoiceDomPdf(): \Symfony\Component\HttpFoundation\Response
    {
        $logoBase64 = FileHelper::fileToBase64(storage_path('app/public/company_logo.png'));
        $bgImageBase64 = FileHelper::fileToBase64(storage_path('app/public/invoice_background.png'));

        $html = <<<HTML
<html>
<head><style>
    body { font-family: Arial, sans-serif; color: #333; }
    .invoice-header { text-align: center; margin-bottom: 20px; }
    .invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .invoice-table th, .invoice-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    .invoice-table th { background-color: #f2f2f2; }
    .total { font-weight: bold; text-align: right; }
    img.logo { max-width: 150px; }
</style></head>
<body>
    <div class="invoice-header">
        <img src="$logoBase64" class="logo" />
        <h1>Invoice #INV-2025-001</h1>
        <p>Billed to: Global Corp, 123 Business Ave, City, Country</p>
        <p>Date: September 29, 2025</p>
    </div>
    <table class="invoice-table">
        <thead>
            <tr><th>Item Description</th><th>Quantity</th><th>Unit Price</th><th>Subtotal</th></tr>
        </thead>
        <tbody>
            <tr><td>Consulting Services - Project Alpha</td><td>40 hours</td><td>$150/hr</td><td>$6,000</td></tr>
            <tr><td>Software License - Enterprise Edition</td><td>1</td><td>$2,500</td><td>$2,500</td></tr>
            <tr><td>Travel Expenses Reimbursement</td><td>1</td><td>$1,200</td><td>$1,200</td></tr>
        </tbody>
        <tfoot>
            <tr><td colspan="3" class="total">Total Amount Due:</td><td class="total">$9,700</td></tr>
        </tfoot>
    </table>
    <p>Payment Terms: Net 30 days. Please remit payment to HRM Bank Account.</p>
    <p>Thank you for your business!</p>
</body>
</html>
HTML;

        return $this->pdfService->generate(
            type: 's',
            pdfInfo: [
                'metadata' => [
                    'Title' => 'Comprehensive Invoice INV-2025-001',
                    'Author' => 'HRM Financial System',
                    'Subject' => 'Billing Invoice',
                    'Keywords' => 'invoice, billing, payment, HRM',
                    'Creator' => 'PdfService'
                ]
            ],
            size: [
                'format' => 'A4',
                'orientation' => 'portrait',
                'margins' => ['top' => 25, 'bottom' => 25, 'left' => 20, 'right' => 20],
                'unit' => 'mm',
                'auto_page_break' => true,
                'break_margin' => 15,
                'columns' => 1
            ],
            data: $html,
            headerFooter: [
                'header' => '<h3>HRM Corp - Confidential Invoice</h3>',
                'footer' => '<p>Page {PAGE_NUM} of {TOTAL_PAGES} - Generated on September 29, 2025</p>',
                'height' => ['header' => 20, 'footer' => 15],
                'margin' => ['header' => 5, 'footer' => 5],
                'repeat_header' => true,
                'repeat_footer' => true,
                'override_per_page' => [
                    1 => ['header' => '<h3>HRM Corp - Invoice Cover Page</h3>']
                ]
            ],
            layoutOptions: [
                'pagination' => true,
                'pagination_format' => 'Page {PAGE_NUM} of {TOTAL_PAGES}',
                'watermark' => [
                    'text' => 'PAID',
                    'opacity' => 0.15,
                    'rotation' => 45,
                    'position' => 'center',
                    'repeat' => true
                ],
                'background' => [
                    'image' => $bgImageBase64,
                    'repeat' => 'no-repeat',
                    'position' => 'center',
                    'opacity' => 0.05
                ],
                'RTL' => false,
                'font_family' => 'helvetica',
                'font_size' => 12,
                'font_color' => '#333333',
                'bold' => false,
                'italic' => false,
                'underline' => false,
                'align' => 'L',
                'line_height' => 1.5,
                'table_styles' => [
                    'border' => 1,
                    'cellpadding' => 5,
                    'cellspacing' => 0,
                    'align' => 'C',
                    'header_bg' => '#eeeeee',
                    'row_bg' => '#ffffff'
                ],
                'list_styles' => [
                    'type' => 'ul',
                    'bullet' => '•',
                    'indent' => 20
                ],
                'custom_css' => '<style>.total { color: #ff0000; }</style>',
                'max_image_width' => 150,
                'max_image_height' => 100,
                'auto_scale_images' => true
            ],
            outputOptions: [
                'download' => true,
                'filename' => 'comprehensive_invoice.pdf',
                'compress' => true,
                'cache' => true,
                'cache_driver' => 'redis',
                'cache_ttl' => 86400, // 24 hours
                'streaming' => ['enabled' => true, 'buffer_size' => 2048 * 1024, 'flush_interval' => 1],
                'debug' => false,
                'force_full_render' => true
            ]
        );
    }

    /**
     * Complex Example 2: Detailed Employee Contract with TCPDF - Includes protection, signature, RTL support, per-page overrides, multicolumn, caching.
     */
    public function complexContractTcpdf(): \Symfony\Component\HttpFoundation\Response
    {
        $watermarkImageBase64 = FileHelper::fileToBase64(storage_path('app/public/watermark_draft.png'));

        $html = <<<HTML
<html>
<head><style>
    body { font-family: dejavusans; direction: rtl; unicode-bidi: embed; }
    .contract-section { margin-bottom: 20px; }
    h2 { color: #0056b3; }
    ul { list-style-type: square; }
</style></head>
<body>
    <div class="contract-section">
        <h2>مقدمة العقد</h2>
        <p>هذا العقد بين شركة HRM و الموظف...</p>
    </div>
    <div class="contract-section">
        <h2>الشروط والأحكام</h2>
        <ul>
            <li>ساعات العمل: 40 ساعة أسبوعياً</li>
            <li>الراتب: 5000 دولار شهرياً</li>
            <li>مدة العقد: سنة واحدة</li>
        </ul>
    </div>
    <div class="contract-section">
        <h2>التوقيع</h2>
        <p>تاريخ: 29 سبتمبر 2025</p>
    </div>
</body>
</html>
HTML;

        return $this->pdfService->generate(
            type: 'c',
            pdfInfo: [
                'metadata' => [
                    'Title' => 'Employee Contract 2025',
                    'Author' => 'HRM Legal Department',
                    'Subject' => 'Employment Agreement',
                    'Keywords' => 'contract, employment, HRM',
                    'Creator' => 'PdfService'
                ],
                'protection' => ['copy', 'print', 'modify'],
                'password' => 'employee123',
                'owner_password' => 'admin456',
                'encryption_level' => 128,
                'signature' => [
                    'cert_file' => storage_path('app/cert.p12'),
                    'password' => 'signpass',
                    'info' => [
                        'Name' => 'HRM Director',
                        'Location' => 'Head Office',
                        'Reason' => 'Contract Approval',
                        'ContactInfo' => 'legal@hrm.com'
                    ],
                    'visible' => true,
                    'x' => 140,
                    'y' => 240,
                    'width' => 60,
                    'height' => 30
                ]
            ],
            size: [
                'format' => [210, 297], // Custom A4 in mm
                'orientation' => 'portrait',
                'margins' => ['top' => 30, 'bottom' => 30, 'left' => 25, 'right' => 25],
                'unit' => 'mm',
                'auto_page_break' => true,
                'break_margin' => 20,
                'columns' => 2
            ],
            data: $html,
            headerFooter: [
                'header' => '<h3>HRM Corp - Employee Contract</h3>',
                'footer' => '<p>Confidential - Do Not Distribute</p>',
                'height' => ['header' => 25, 'footer' => 20],
                'margin' => ['header' => 10, 'footer' => 10],
                'position' => ['header' => 'top', 'footer' => 'bottom'],
                'repeat_header' => true,
                'repeat_footer' => true,
                'override_per_page' => [
                    1 => ['header' => '<h3>Contract Cover - HRM Corp</h3>', 'footer' => '<p>Page 1 - Introduction</p>'],
                    2 => ['footer' => '<p>Page 2 - Terms and Conditions</p>']
                ]
            ],
            layoutOptions: [
                'pagination' => true,
                'pagination_format' => '{PAGE_NUM} / {TOTAL_PAGES}',
                'watermark' => [
                    'image' => $watermarkImageBase64,
                    'opacity' => 0.2,
                    'rotation' => 0,
                    'position' => 'center',
                    'repeat' => true,
                    'width' => 100,
                    'height' => 100
                ],
                'background' => [
                    'color' => '#f8f9fa',
                    'repeat' => 'no-repeat',
                    'position' => 'center',
                    'opacity' => 1
                ],
                'RTL' => true,
                'font_family' => 'dejavusans',
                'font_size' => 11,
                'font_color' => '#000000',
                'bold' => false,
                'italic' => false,
                'underline' => false,
                'align' => 'R',
                'line_height' => 1.4,
                'table_styles' => [],
                'list_styles' => ['type' => 'ul', 'bullet' => '■', 'indent' => 15],
                'custom_css' => '<style>h2 { text-decoration: underline; }</style>',
                'max_image_width' => 200,
                'max_image_height' => 150,
                'auto_scale_images' => true
            ],
            outputOptions: [
                'download' => true,
                'filename' => 'employee_contract_2025.pdf',
                'compress' => true,
                'lazy_render' => false,
                'cache' => true,
                'cache_driver' => 'file',
                'cache_ttl' => 7200,
                'threads' => 1, // Stub for future
                'streaming' => ['enabled' => false],
                'debug' => true,
                'force_full_render' => true
            ]
        );
    }

    /**
     * Complex Example 3: Annual Report with DomPDF - Features landscape orientation, background color, custom image scaling, list styles, per-page overrides, caching.
     */
    public function complexAnnualReportDomPdf(): \Symfony\Component\HttpFoundation\Response
    {
        $chartBase64 = FileHelper::fileToBase64(storage_path('app/public/annual_chart.png'));

        $html = <<<HTML
<html>
<head><style>
    body { font-family: times; line-height: 1.6; }
    .report-section { margin: 15px 0; }
    .chart { max-width: 400px; height: auto; }
    ul { list-style-type: circle; }
</style></head>
<body>
    <div class="report-section">
        <h1>Annual Report 2025</h1>
        <p>Overview: HRM Corp achieved 20% growth in revenue.</p>
    </div>
    <div class="report-section">
        <h2>Key Achievements</h2>
        <ul>
            <li>Expanded to 5 new markets</li>
            <li>Hired 100 new employees</li>
            <li>Launched AI-driven HRM tools</li>
        </ul>
    </div>
    <div class="report-section">
        <h2>Financial Summary</h2>
        <img src="$chartBase64" class="chart" />
        <p>Revenue: $10M, Expenses: $7M, Profit: $3M</p>
    </div>
</body>
</html>
HTML;

        return $this->pdfService->generate(
            type: 's',
            pdfInfo: [
                'metadata' => [
                    'Title' => 'HRM Annual Report 2025',
                    'Author' => 'Executive Team',
                    'Subject' => 'Yearly Review',
                    'Keywords' => 'annual report, HRM, 2025',
                    'Creator' => 'PdfService'
                ]
            ],
            size: [
                'format' => 'A3',
                'orientation' => 'landscape',
                'margins' => ['top' => 20, 'bottom' => 20, 'left' => 15, 'right' => 15],
                'unit' => 'mm',
                'auto_page_break' => true,
                'break_margin' => 10,
                'columns' => 1
            ],
            data: $html,
            headerFooter: [
                'header' => '<h4>HRM Corp Annual Report</h4>',
                'footer' => '<p>Confidential - For Internal Use Only</p>',
                'height' => ['header' => 15, 'footer' => 10],
                'margin' => ['header' => 5, 'footer' => 5],
                'repeat_header' => true,
                'repeat_footer' => true,
                'override_per_page' => [
                    1 => ['header' => '<h4>Annual Report Cover</h4>']
                ]
            ],
            layoutOptions: [
                'pagination' => true,
                'pagination_format' => 'Page {PAGE_NUM} of {TOTAL_PAGES}',
                'watermark' => [
                    'text' => 'DRAFT',
                    'opacity' => 0.1,
                    'rotation' => 30,
                    'position' => 'center',
                    'repeat' => true
                ],
                'background' => [
                    'color' => '#e6f2ff',
                    'opacity' => 1
                ],
                'RTL' => false,
                'font_family' => 'times',
                'font_size' => 12,
                'font_color' => '#000000',
                'bold' => false,
                'italic' => false,
                'underline' => false,
                'align' => 'J',
                'line_height' => 1.6,
                'table_styles' => [],
                'list_styles' => ['type' => 'ul', 'bullet' => '○', 'indent' => 25],
                'custom_css' => '<style>.report-section { border-bottom: 1px solid #ccc; }</style>',
                'max_image_width' => 400,
                'max_image_height' => 300,
                'auto_scale_images' => true
            ],
            outputOptions: [
                'download' => false, // Inline view
                'filename' => 'annual_report_2025.pdf',
                'compress' => true,
                'cache' => true,
                'cache_driver' => 'memcached',
                'cache_ttl' => 3600,
                'streaming' => ['enabled' => true, 'buffer_size' => 1024 * 1024, 'flush_interval' => 3],
                'debug' => false,
                'force_full_render' => false
            ]
        );
    }

    /**
     * Complex Example 4: Multilingual Payslip with TCPDF - RTL, protection, watermark text/image, background color, table/list styles, streaming.
     */
    public function complexPayslipTcpdf(): \Symfony\Component\HttpFoundation\Response
    {
        $barcodeBase64 = FileHelper::fileToBase64(storage_path('app/public/payslip_barcode.png'));

        $html = <<<HTML
<html>
<head><style>
    body { font-family: dejavusans; direction: rtl; }
    table.payslip-table { width: 100%; border-collapse: collapse; }
    table.payslip-table th, table.payslip-table td { border: 1px solid #000; padding: 10px; }
    ul.deductions { list-style-type: disc; }
    img.barcode { width: 120px; }
</style></head>
<body>
    <h1>كشف الراتب لشهر سبتمبر 2025</h1>
    <p><strong>الموظف:</strong> جون دو</p>
    <table class="payslip-table">
        <tr><th>البند</th><th>المبلغ</th></tr>
        <tr><td>الراتب الأساسي</td><td>5000 دولار</td></tr>
        <tr><td>المكافأة</td><td>500 دولار</td></tr>
        <tr><td>الإجمالي</td><td>5500 دولار</td></tr>
    </table>
    <h2>الخصومات</h2>
    <ul class="deductions">
        <li>ضرائب: 800 دولار</li>
        <li>تأمين: 200 دولار</li>
    </ul>
    <img src="$barcodeBase64" class="barcode" />
    <p>الصافي: 4500 دولار</p>
</body>
</html>
HTML;

        return $this->pdfService->generate(
            type: 'c',
            pdfInfo: [
                'metadata' => [
                    'Title' => 'Payslip September 2025',
                    'Author' => 'HRM Payroll System',
                    'Subject' => 'Monthly Payslip',
                    'Keywords' => 'payslip, salary, HRM',
                    'Creator' => 'PdfService'
                ],
                'protection' => ['print', 'modify'],
                'password' => 'payslip123',
                'owner_password' => 'hradmin',
                'encryption_level' => 128
            ],
            size: [
                'format' => 'A5',
                'orientation' => 'portrait',
                'margins' => ['top' => 15, 'bottom' => 15, 'left' => 10, 'right' => 10],
                'unit' => 'mm',
                'auto_page_break' => true,
                'break_margin' => 10,
                'columns' => 1
            ],
            data: $html,
            headerFooter: [
                'header' => '<h3>HRM Corp Payslip</h3>',
                'footer' => '<p>Generated: September 29, 2025</p>',
                'height' => ['header' => 15, 'footer' => 10],
                'margin' => ['header' => 5, 'footer' => 5],
                'repeat_header' => true,
                'repeat_footer' => true
            ],
            layoutOptions: [
                'pagination' => false,
                'watermark' => [
                    'text' => 'CONFIDENTIAL',
                    'image' => $barcodeBase64, // Reuse as example
                    'opacity' => 0.05,
                    'rotation' => 45,
                    'position' => 'center',
                    'repeat' => false,
                    'x' => 50,
                    'y' => 100,
                    'font_size' => 50
                ],
                'background' => [
                    'color' => '#fff3e6',
                    'opacity' => 1
                ],
                'RTL' => true,
                'font_family' => 'dejavusans',
                'font_size' => 10,
                'font_color' => '#333333',
                'bold' => false,
                'italic' => false,
                'underline' => false,
                'align' => 'C',
                'line_height' => 1.3,
                'table_styles' => [
                    'border' => 1,
                    'cellpadding' => 10,
                    'cellspacing' => 0,
                    'align' => 'R',
                    'header_bg' => '#f0f0f0',
                    'row_bg' => '#ffffff'
                ],
                'list_styles' => [
                    'type' => 'ul',
                    'bullet' => '►',
                    'indent' => 20
                ],
                'custom_css' => '<style>.payslip-table th { font-weight: bold; }</style>',
                'max_image_width' => 120,
                'max_image_height' => 50,
                'auto_scale_images' => true
            ],
            outputOptions: [
                'download' => true,
                'filename' => 'payslip_sept_2025.pdf',
                'compress' => true,
                'cache' => true,
                'cache_driver' => 'file',
                'cache_ttl' => 1800,
                'streaming' => ['enabled' => true, 'buffer_size' => 512 * 1024, 'flush_interval' => 2],
                'debug' => false,
                'force_full_render' => true
            ]
        );
    }

    /**
     * Complex Example 5: Certificate of Completion with DomPDF - Custom format, bold/italic/underline text, background image, watermark, pagination (for multi-page), debug mode.
     */
    public function complexCertificateDomPdf(): \Symfony\Component\HttpFoundation\Response
    {
        $sealBase64 = FileHelper::fileToBase64(storage_path('app/public/seal.png'));
        $bgCertBase64 = FileHelper::fileToBase64(storage_path('app/public/certificate_bg.png'));

        $html = <<<HTML
<html>
<head><style>
    body { font-family: courier; text-align: center; }
    .certificate { padding: 50px; border: 3px solid #gold; }
    h1 { font-size: 48px; font-weight: bold; font-style: italic; text-decoration: underline; color: #0056b3; }
    p { font-size: 24px; }
    img.seal { max-width: 200px; }
</style></head>
<body>
    <div class="certificate">
        <h1>Certificate of Completion</h1>
        <p>This certifies that</p>
        <p><strong>Alice Johnson</strong></p>
        <p>has successfully completed the Advanced HRM Training Program</p>
        <p>on September 29, 2025</p>
        <img src="$sealBase64" class="seal" />
        <p>Issued by: HRM Corp</p>
    </div>
</body>
</html>
HTML;

        return $this->pdfService->generate(
            type: 's',
            pdfInfo: [
                'metadata' => [
                    'Title' => 'Certificate of Completion - Alice Johnson',
                    'Author' => 'HRM Training Department',
                    'Subject' => 'Training Certification',
                    'Keywords' => 'certificate, completion, training, HRM',
                    'Creator' => 'PdfService'
                ]
            ],
            size: [
                'format' => [279, 216], // Letter size in mm (approx)
                'orientation' => 'landscape',
                'margins' => ['top' => 10, 'bottom' => 10, 'left' => 10, 'right' => 10],
                'unit' => 'mm',
                'auto_page_break' => false,
                'break_margin' => 5,
                'columns' => 1
            ],
            data: $html,
            headerFooter: [
                'header' => null,
                'footer' => '<p>Certificate ID: CERT-2025-001</p>',
                'height' => ['footer' => 10],
                'margin' => ['footer' => 5],
                'repeat_footer' => true
            ],
            layoutOptions: [
                'pagination' => true,
                'pagination_format' => 'Cert Page {PAGE_NUM}',
                'watermark' => [
                    'text' => 'OFFICIAL',
                    'opacity' => 0.2,
                    'rotation' => 0,
                    'position' => 'center',
                    'repeat' => false
                ],
                'background' => [
                    'image' => $bgCertBase64,
                    'repeat' => 'no-repeat',
                    'position' => 'center',
                    'opacity' => 0.8
                ],
                'RTL' => false,
                'font_family' => 'courier',
                'font_size' => 18,
                'font_color' => '#000000',
                'bold' => true,
                'italic' => true,
                'underline' => true,
                'align' => 'C',
                'line_height' => 1.8,
                'table_styles' => [],
                'list_styles' => [],
                'custom_css' => '<style>.certificate { background-color: rgba(255,255,255,0.7); }</style>',
                'max_image_width' => 200,
                'max_image_height' => 200,
                'auto_scale_images' => false
            ],
            outputOptions: [
                'download' => true,
                'filename' => 'certificate_completion.pdf',
                'compress' => true,
                'cache' => false,
                'streaming' => ['enabled' => false],
                'debug' => true,
                'force_full_render' => true
            ]
        );
    }
}