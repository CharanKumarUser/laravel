<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controller demonstrating use cases for FileService.
 */
class FileManagerController extends Controller
{
    private FileService $fileManager;

    public function __construct(FileService $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * Use Case 1: Save a file (e.g., upload an invoice PDF).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function saveFile(Request $request): JsonResponse
    {
        try {
            $result = $this->fileManager->saveFile(
                request: $request,
                key: 'invoices',
                raw_filename: 'invoice_file',
                custom_filename: 'client_invoice_2025',
                business_id: 'BSN1234567',
                is_public: false
            );

            if ($result['status']) {
                return response()->json([
                    'message' => $result['message'],
                    'file_id' => $result['data']['file_id'],
                    'file_path' => $result['data']['file_path'],
                    'public_url' => $result['data']['public_url']
                ], 200);
            }

            return response()->json(['error' => $result['message']], 400);
        } catch (\Exception $e) {
            Log::error('Save file failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Unexpected error occurred'], 500);
        }
    }

    /**
     * Use Case 2: Import data from a CSV file into a table.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function importFile(Request $request): JsonResponse
    {
        try {
            $result = $this->fileManager->importFile(
                request: $request,
                raw_filename: 'employee_data',
                table: 'employees',
                columns: 'name,email,department',
                business_id: 'BSN1234567'
            );

            if ($result['status']) {
                return response()->json([
                    'message' => $result['message'],
                    'imported' => $result['data']['imported_records'],
                    'skipped' => $result['data']['skipped_records'],
                    'error_file_id' => $result['data']['error_file_id']
                ], 200);
            }

            return response()->json(['error' => $result['message']], 400);
        } catch (\Exception $e) {
            Log::error('Import file failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Unexpected error occurred'], 500);
        }
    }

    /**
     * Use Case 3: Download a file by log ID.
     *
     * @param int $fileLogId
     * @return JsonResponse
     */
    public function downloadFile(int $fileLogId): JsonResponse
    {
        try {
            $result = $this->fileManager->downloadFile(
                file_log_id: $fileLogId,
                get_content: true
            );

            if ($result['status']) {
                return response($result['data']['content'], 200, [
                    'Content-Type' => $result['data']['content_type'],
                    'Content-Disposition' => 'attachment; filename="' . $result['data']['name'] . '"'
                ]);
            }

            return response()->json(['error' => $result['message']], 400);
        } catch (\Exception $e) {
            Log::error('Download file failed', ['file_log_id' => $fileLogId, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Unexpected error occurred'], 500);
        }
    }

    /**
     * Use Case 4: Export data to an Excel file.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exportFile(Request $request): JsonResponse
    {
        try {
            $params = [
                'columns' => ['id', 'name', 'email'],
                'where' => ['department' => 'HR']
            ];
            $result = $this->fileManager->exportFile(
                table: 'employees',
                params: $params,
                business_id: 'BSN1234567',
                format: 'xlsx',
                is_public: false
            );

            if ($result['status']) {
                return response()->json([
                    'message' => $result['message'],
                    'file_id' => $result['data']['file_id'],
                    'file_log_id' => $result['data']['file_log_id'],
                    'file_path' => $result['data']['file_path'],
                    'public_url' => $result['data']['public_url']
                ], 200);
            }

            return response()->json(['error' => $result['message']], 400);
        } catch (\Exception $e) {
            Log::error('Export file failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Unexpected error occurred'], 500);
        }
    }

    /**
     * Use Case 5: Generate a report (e.g., payslips).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateReport(Request $request): JsonResponse
    {
        try {
            $params = [
                'columns' => ['employee_id', 'name', 'amount', 'date'],
                'where' => ['month' => '2025-07']
            ];
            $result = $this->fileManager->report(
                reportType: 'payslips',
                params: $params,
                business_id: 'BSN1234567',
                format: 'xlsx',
                is_public: false
            );

            if ($result['status']) {
                return response()->json([
                    'message' => $result['message'],
                    'file_id' => $result['data']['file_id'],
                    'file_log_id' => $result['data']['file_log_id'],
                    'file_path' => $result['data']['file_path'],
                    'public_url' => $result['data']['public_url']
                ], 200);
            }

            return response()->json(['error' => $result['message']], 400);
        } catch (\Exception $e) {
            Log::error('Report generation failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Unexpected error occurred'], 500);
        }
    }

    /**
     * Use Case 6: Create a zip archive of files.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function zipFiles(Request $request): JsonResponse
    {
        try {
            $fileIds = ['FLX1234567890', 'FLX0987654321'];
            $result = $this->fileManager->zip(
                file_ids: $fileIds,
                key: 'archives',
                business_id: 'BSN1234567',
                is_public: false
            );

            if ($result['status']) {
                return response()->json([
                    'message' => $result['message'],
                    'file_id' => $result['data']['file_id'],
                    'file_log_id' => $result['data']['file_log_id'],
                    'file_path' => $result['data']['file_path'],
                    'public_url' => $result['data']['public_url']
                ], 200);
            }

            return response()->json(['error' => $result['message']], 400);
        } catch (\Exception $e) {
            Log::error('Zip creation failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Unexpected error occurred'], 500);
        }
    }

    /**
     * Use Case 7: Check for circular folder reference.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkCircularReference(Request $request): JsonResponse
    {
        try {
            $parentId = $request->input('parent_folder_id', 'FOLDER123');
            $currentFolderId = $request->input('current_folder_id', 'FOLDER456');
            $isCircular = $this->fileManager->isCircularReference(
                parentId: $parentId,
                currentFolderId: $currentFolderId
            );

            return response()->json([
                'message' => $isCircular ? 'Circular reference detected' : 'No circular reference',
                'is_circular' => $isCircular
            ], 200);
        } catch (\Exception $e) {
            Log::error('Circular reference check failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Unexpected error occurred'], 500);
        }
    }

    /**
     * Use Case 8: Get all folder paths.
     *
     * @return JsonResponse
     */
    public function getFolderPaths(): JsonResponse
    {
        try {
            $paths = $this->fileManager->getFolderPaths(useKey: true);

            return response()->json([
                'message' => 'Folder paths retrieved successfully',
                'paths' => $paths
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get folder paths failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Unexpected error occurred'], 500);
        }
    }

    /**
     * Use Case 9: Get file path by file ID.
     *
     * @param string $fileId
     * @return JsonResponse
     */
    public function getFilePath(string $fileId): JsonResponse
    {
        try {
            $result = $this->fileManager->getFilePath(file_id: $fileId);

            if ($result['status']) {
                return response()->json([
                    'message' => 'File path retrieved successfully',
                    'file_path' => $result
                ], 200);
            }

            return response()->json(['error' => $result['message']], 400);
        } catch (\Exception $e) {
            Log::error('Get file path failed', ['file_id' => $fileId, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Unexpected error occurred'], 500);
        }
    }
}


// Get real data
                    $loginTime = Carbon::now()->toDateTimeString(); // Current timestamp
                    $agent = new Agent();
                    $deviceInfo = $this->getDeviceInfo($agent); // Parse device and browser
                    $ipAddress = $request->ip(); // Get client IP
                    $location = $this->getLocation($ipAddress); // Get approximate location
                    // Prepare email data
                    $emailData = [
                        'login_time' => $loginTime,
                        'device_info' => $deviceInfo,
                        'ip_address' => $ipAddress,
                        'location' => $location,
                    ];
                    Notification::mail(
                        'successful_login_email',
                        'kirankumar.121.rkk@gmail.com',
                        $emailData,
                        [],
                        'high'
                    );
                    //                 Notification::mail(
                    //     key: 'welcome_mail',
                    //     to: 'rameshroyal4005@gmail.com',
                    //     pairs: ['name' => 'R Kiran Kumar'],
                    //     attachments: [
                    //         // ['type' => 'base64', 'content' => base64_encode(file_get_contents('welcome.pdf')), 'name' => 'welcome', 'extension' => 'pdf'],
                    //         // ['type' => 'file', 'content' => storage_path('app/docs/invoice.pdf'), 'name' => 'invoice', 'extension' => 'pdf'],
                    //         ['type' => 'url', 'content' => 'https://gstarelevators.com/treasury/images/common/brochure/G-Star-Elevators-Brochure.pdf', 'name' => 'guide', 'extension' => 'pdf'],
                    //     ],
                    //     priority: 'high'
                    // );


                    <form id="upload-form" class="container my-4">
  <!-- Profile -->
<div class="file-upload-container" 
     data-file="image" 
     data-name="profile_photo" 
     data-file-crop="profile" 
     data-label="Profile Photo" 
     data-crop-size="200:200" 
     data-recommended-size="200x200" 
     data-file-size="2" 
     data-src="{{ asset('default/preview-square.svg') }}">
</div>

<!-- Banner -->
<div class="file-upload-container" 
     data-file="image" 
     data-name="banner_photo" 
     data-file-crop="banner" 
     data-label="Banner Image" 
     data-crop-size="600:300" 
     data-recommended-size="600x300" 
     data-file-size="5" 
     data-src="{{ asset('default/preview-window.svg') }}">
</div>

<!-- Cover -->
<div class="file-upload-container" 
     data-file="image" 
     data-name="cover_photo" 
     data-file-crop="cover" 
     data-label="Cover Image" 
     data-crop-size="600:300" 
     data-recommended-size="600x300" 
     data-file-size="5" 
     data-src="{{ asset('default/preview-window.svg') }}">
</div>

<!-- Preview -->
<div class="file-upload-container" 
     data-file="preview" 
     data-name="preview_photo" 
     data-label="Preview Image" 
     data-recommended-size="Any" 
     data-file-size="3" 
     data-src="{{ asset('default/preview-window.svg') }}">
</div>

<!-- Documents -->
<div class="file-upload-container" 
     data-file="document" 
     data-name="documents" 
     data-label="Documents" 
     data-recommended-size=".pdf, .doc, .xls, .txt" 
     data-file-size="10" 
     data-multiple>
</div>

<!-- Videos -->
<div class="file-upload-container" 
     data-file="video" 
     data-name="videos" 
     data-label="Videos" 
     data-recommended-size="Any video format" 
     data-file-size="50" 
     data-multiple>
</div>

<!-- Audio -->
<div class="file-upload-container" 
     data-file="audio" 
     data-name="audio" 
     data-label="Audio" 
     data-recommended-size="Any audio format" 
     data-file-size="10">
</div>