<?php
declare(strict_types=1);
namespace App\Services;
use App\Events\FileManager\TemporaryFileCreated;
use App\Facades\{Data, Developer, Random, Skeleton};
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Cache, Storage};
use Illuminate\Support\{Collection, Str};
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\{ToCollection, WithCustomValueBinder};
use PhpOffice\PhpSpreadsheet\Cell\{CellValueBinder, DataType};
use ZipArchive;
use Exception;
use InvalidArgumentException;
use Intervention\Image\ImageManagerStatic as Image;
/**
 * Service for managing file operations including saving, importing, downloading, exporting, reporting, and zipping.
 */
class FileService
{
    private const CACHE_TTL = 7200; // 2 hours
    private const TEMP_FILE_TTL = 300; // 5 minutes
    private const DEFAULT_DOWNLOAD_LIMIT = 10;
    private const FILE_DISK_PRIVATE = 'files_private';
    private const FILE_DISK_PUBLIC = 'files_public';
    private const FILE_DISK_PUBLIC_COPY = 'files_public_copy';
    private string $FILE_SYSTEM;

    public function __construct()
    {
        $this->FILE_SYSTEM = Skeleton::authUser()->business_id;
    }
    /**
     * Save a file to storage with a formatted filename.
     */
    public function saveFile(Request $request, string $key, string $raw_filename, ?string $custom_filename, string $business_id, bool $is_public = false): array
    {
        return $this->handleOperation('saveFile', ['key' => $key, 'business_id' => $business_id], fn() => $this->saveFileLogic($request, $key, $raw_filename, $custom_filename, $business_id, $is_public));
    }
    /**
     * Import data from a file to a table.
     */
    public function importFile(Request $request, string $raw_filename, string $table, string $columns, string $business_id): array
    {
        return $this->handleOperation('importFile', ['table' => $table, 'business_id' => $business_id], fn() => $this->importFileLogic($request, $raw_filename, $table, $columns, $business_id));
    }
    /**
     * Download a file by log ID.
     */
    public function downloadFile(int $file_log_id, bool $get_content = false): array
    {
        return $this->handleOperation('downloadFile', ['file_log_id' => $file_log_id], fn() => $this->downloadFileLogic($file_log_id, $get_content));
    }
    /**
     * Export data to a file.
     */
    public function exportFile(string $table, array $params, string $business_id, string $format = 'xlsx', bool $is_public = false): array
    {
        return $this->handleFileOperation('exportFile', ['table' => $table], 'export', $table, $params, $business_id, $format, $is_public, fn($filename) => "exports\\{$filename}", 'File exported successfully');
    }
    /**
     * Generate a report file.
     */
    public function report(string $reportType, array $params, string $business_id, string $format = 'xlsx', bool $is_public = false): array
    {
        return $this->handleOperation('report', ['report_type' => $reportType], fn() => $this->reportLogic($reportType, $params, $business_id, $format, $is_public));
    }
    /**
     * Create a zip archive of files.
     */
    public function zip(array $file_ids, string $key, string $business_id, bool $is_public = false): array
    {
        return $this->handleOperation('zip', ['file_ids' => $file_ids], fn() => $this->zipLogic($file_ids, $key, $business_id, $is_public));
    }
    /**
     * Get folder paths recursively.
     */
    public function getFolderPaths(?array $folders = null, ?string $parentId = null, string $prefix = '', ?array $paths = null, bool $useKey = false): array
    {
        if ($paths === null) {
            $paths = [];
        }
        $folders ??= Data::query('central', 'skeleton_folders', [
            'select' => ['folder_id', 'key', 'name', 'parent_folder_id'],
            'where' => [['column' => 'is_approved', 'operator' => '=', 'value' => 1]]
        ]);
        foreach ($folders['data'] as $folder) {
            if (!isset($folder['folder_id'], $folder['name']) || ($useKey && !isset($folder['key']))) {
                continue;
            }
            if ($folder['parent_folder_id'] === $parentId) {
                $folderName = strtolower(preg_replace('/\s+/', '-', trim($folder['name'])));
                $fullPath = $prefix ? "{$prefix}\\{$folderName}" : $folderName;
                $key = $useKey ? $folder['key'] : $folder['folder_id'];
                $paths[$key] = "\\{$fullPath}";
                // Merge the child results instead of referencing
                $paths = array_merge(
                    $paths,
                    $this->getFolderPaths($folders, $folder['folder_id'], $fullPath, $paths, $useKey)
                );
            }
        }
        return $paths;
    }
    /**
     * Get the path for a folder by its key.
     */
    public function getFolderPath(string $key): string
    {
        if (empty($key)) {
            throw new InvalidArgumentException('Folder key is required.');
        }
        $cacheKey = 'folder_paths_all';
        $cachedPaths = Cache::get($cacheKey);
        if ($cachedPaths && isset($cachedPaths[$key])) {
            return $cachedPaths[$key];
        }
        $paths = $this->getFolderPaths(null, null, '', [], true);
        if (!isset($paths[$key])) {
            throw new Exception('Folder not found for key: ' . $key);
        }
        Cache::put($cacheKey, $paths, 3600);
        return $paths[$key];
    }
    /**
     * Check for circular references in folder hierarchy.
     */
    public function isCircularReference(string $parentId, ?string $currentFolderId): bool
    {
        if (empty($parentId) || $parentId === $currentFolderId) {
            return true;
        }
        $folders = Data::query('central', 'skeleton_folders', [
            'select' => ['folder_id', 'key', 'name', 'parent_folder_id'],
            'where' => [['column' => 'is_approved', 'operator' => '=', 'value' => 1]]
        ]);
        $visited = [$parentId];
        $current = $parentId;
        while ($current) {
            $found = false;
            foreach ($folders as $folder) {
                if ($folder['folder_id'] === $current && !empty($folder['parent_folder_id'])) {
                    if ($folder['parent_folder_id'] === $currentFolderId || in_array($folder['parent_folder_id'], $visited)) {
                        return true;
                    }
                    $visited[] = $folder['parent_folder_id'];
                    $current = $folder['parent_folder_id'];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                break;
            }
        }
        return false;
    }
    /**
     * Retrieve file content by file ID from storage.
     */
    public function getFile(?string $file_id, bool $output = false, ?string $business_id = null): string|array
    {
        if (empty($file_id)) {
            return '';
        }
        $connection = $this->FILE_SYSTEM;
        $where = [
            ['column' => 'file_id', 'operator' => '=', 'value' => $file_id],
        ];
        if ($business_id) {
            $where[] = ['column' => 'business_id', 'operator' => '=', 'value' => $business_id];
        }
        try {
            $fileResult = Data::query($connection, 'files', [
                'select' => ['file_id', 'name', 'path', 'extension', 'mime_type', 'size_bytes', 'is_public', 'deleted_at'],
                'where' => $where
            ]);
            if (!$fileResult['status'] || empty($fileResult['data'])) {
                return '';
            }
            $file = $fileResult['data'][0];
            $disk = $file['is_public'] ? self::FILE_DISK_PUBLIC : self::FILE_DISK_PRIVATE;
            $physicalPath = ltrim($file['path'], '\\');
            if (!Storage::disk($disk)->exists($physicalPath)) {
                return '';
            }
            $fileContent = Storage::disk($disk)->get($physicalPath);
            $base64Content = base64_encode($fileContent);
            if ($output) {
                return array_merge($file, ['content' => $base64Content]);
            }
            return in_array($file['mime_type'], ['image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/tiff', 'image/webp'])
                ? "data:{$file['mime_type']};base64,{$base64Content}"
                : $base64Content;
        } catch (\Throwable $e) {
            Developer::warning('getFile error', ['file_id' => $file_id, 'error' => $e->getMessage()]);
            return '';
        }
    }
    /**
     * Get the full file path by file ID.
     */
    public function getFilePath(string $file_id): string
    {
        if (empty($file_id)) {
            throw new InvalidArgumentException('File ID is required.');
        }
        $result = Data::query($this->FILE_SYSTEM, 'files', [
            'select' => ['path'],
            'where' => [['column' => 'file_id', 'operator' => '=', 'value' => $file_id]]
        ]);
        if (!$result['status'] || empty($result['data'])) {
            throw new InvalidArgumentException('File not found for ID: ' . $file_id);
        }
        return $result['data'][0]['path'];
    }
    /**
     * Convert a file to Base64 string.
     */
    public function fileToBase64(string $filePath, bool $includeMime = false): string
    {
        if (!file_exists($filePath)) {
            throw new Exception("File not found: $filePath");
        }
        $content = file_get_contents($filePath) ?: throw new Exception("Unable to read file: $filePath");
        $base64 = base64_encode($content);
        return $includeMime ? "data:{$this->getMimeTypeFile($filePath)};base64,{$base64}" : $base64;
    }
    /**
     * Convert Base64 string to a file.
     */
    public function base64ToFile(string $base64, string $outputPath): bool
    {
        $base64 = preg_replace('/^data:[\w\/-]+;base64,/', '', $base64);
        $data = base64_decode($base64, true) ?: throw new Exception('Invalid Base64 string');
        $this->ensureDirectoryExists(dirname($outputPath));
        return file_put_contents($outputPath, $data) !== false;
    }
    /**
     * Read file content as string.
     */
    public function fileToString(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new Exception("File not found: $filePath");
        }
        return file_get_contents($filePath) ?: throw new Exception("Unable to read file: $filePath");
    }
    /**
     * Write string to a file.
     */
    public function stringToFile(string $content, string $outputPath): bool
    {
        $this->ensureDirectoryExists(dirname($outputPath));
        return file_put_contents($outputPath, $content) !== false;
    }
    /**
     * Convert JSON data to file.
     */
    public function jsonToFile($json, string $outputPath): bool
    {
        return $this->stringToFile(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $outputPath);
    }
    /**
     * Read JSON file to array/object.
     */
    public function fileToJson(string $filePath)
    {
        return json_decode($this->fileToString($filePath), true, 512, JSON_THROW_ON_ERROR);
    }
    /**
     * Get file name with or without extension.
     */
    public function getFileName(string $filePath, bool $withExtension = true): string
    {
        return $withExtension ? basename($filePath) : pathinfo($filePath, PATHINFO_FILENAME);
    }
    /**
     * Get file extension.
     */
    public function getFileExtension(string $filePath): string
    {
        return strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    }
    /**
     * Get MIME type of a file.
     */
    public function getMimeTypeFile(string $filePath): string
    {
        return mime_content_type($filePath) ?: 'application/octet-stream';
    }
    /**
     * Get file size in specified unit.
     */
    public function getFileSize(string $filePath, string $unit = 'bytes'): float
    {
        $size = filesize($filePath) ?: throw new Exception("Unable to get file size: $filePath");
        return match (strtolower($unit)) {
            'kb' => $size / 1024,
            'mb' => $size / (1024 * 1024),
            'gb' => $size / (1024 * 1024 * 1024),
            default => (float) $size,
        };
    }

    /**
     * Get file type category.
     */
    public function getFileTypeCategory(string $filePath): string
    {
        $mime = $this->getMimeTypeFile($filePath);
        return match (true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'video/') => 'video',
            str_starts_with($mime, 'audio/') => 'audio',
            in_array($mime, ['application/pdf', 'text/plain', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']) => 'document',
            default => 'other',
        };
    }
    /**
     * Zip multiple files into a single archive.
     */
    public function zipFiles(array $filePaths, string $zipPath): bool
    {
        $this->ensureDirectoryExists(dirname($zipPath));
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Failed to create ZIP archive');
        }
        foreach ($filePaths as $file) {
            if (file_exists($file)) {
                $zip->addFile($file, basename($file));
            }
        }
        return $zip->close();
    }
    /**
     * Unzip an archive to a destination folder.
     */
    public function unzip(string $zipPath, string $destination): bool
    {
        $this->ensureDirectoryExists($destination);
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true || !$zip->extractTo($destination)) {
            throw new Exception('Failed to process ZIP archive');
        }
        return $zip->close();
    }
    /**
     * Copy a file to a new location.
     */
    public function copyFile(string $source, string $destination): bool
    {
        $this->ensureDirectoryExists(dirname($destination));
        return copy($source, $destination);
    }
    /**
     * Move a file to a new location.
     */
    public function moveFile(string $source, string $destination): bool
    {
        $this->ensureDirectoryExists(dirname($destination));
        return rename($source, $destination);
    }
    /**
     * Delete a file safely.
     */
    public function deleteFile(string $filePath): bool
    {
        return file_exists($filePath) && unlink($filePath);
    }
    /**
     * Check if file exists.
     */
    public function fileExists(string $filePath): bool
    {
        return file_exists($filePath);
    }
    /**
     * List all files in a directory.
     */
    public function listFiles(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }
        $files = scandir($directory) ?: [];
        return array_filter($files, fn($file) => !in_array($file, ['.', '..']) && is_file("{$directory}/{$file}"));
    }
    /**
     * Get image dimensions.
     */
    public function getImageDimensions(string $imagePath): array
    {
        if ($this->getFileTypeCategory($imagePath) !== 'image') {
            throw new Exception('File is not an image');
        }
        $image = Image::make($imagePath);
        return ['width' => $image->width(), 'height' => $image->height()];
    }
    /**
     * Convert image format.
     */
    public function convertImageFormat(string $source, string $outputPath, string $format): bool
    {
        $this->ensureDirectoryExists(dirname($outputPath));
        return (bool) Image::make($source)->save($outputPath, null, $format);
    }
    /**
     * Verify file type matches extension.
     */
    public function verifyFileType(string $filePath): bool
    {
        $extension = $this->getFileExtension($filePath);
        $mime = $this->getMimeTypeFile($filePath);
        $expectedMimes = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'pdf' => ['application/pdf'],
        ];
        return isset($expectedMimes[$extension]) && in_array($mime, $expectedMimes[$extension]);
    }
    /**
     * Store uploaded file in Laravel storage.
     */
    public function storeFileUploaded(UploadedFile $file, string $path, string $disk = 'public'): string
    {
        return $file->store($path, $disk);
    }
    /**
     * Get file URL from Laravel storage.
     */
    public function getFileUrl(string $path, string $disk = 'public'): string
    {
        return Storage::disk($disk)->url($path);
    }
    /**
     * Generate download response for a file.
     */
    public function downloadFileResponse(string $filePath, ?string $name = null)
    {
        return response()->download($filePath, $name);
    }
    private function saveFileLogic(Request $request, string $key, string $raw_filename, ?string $custom_filename, string $business_id, bool $is_public): array
    {
        $file = $request->file($raw_filename) ?? throw new InvalidArgumentException('Invalid or missing file for input: ' . $raw_filename);
        $this->validateRequired($key, $business_id, 'Folder key and business ID are required.');
        $fileData = $this->validateFile($file, $custom_filename ?? $raw_filename);
        $this->validateExtension($fileData['extension']);
        $folder_id = $this->getFolderId($key);
        $storedName = $this->generateUniqueFilename($custom_filename ?? $raw_filename, $fileData['extension'], $key, $is_public);
        $file_path = $this->storeFile($file, $storedName, $key, $is_public);
        return $this->createFile(
            [
                'file_id' => Random::unique(10, 'FLX'),
                'folder_id' => $folder_id,
                'business_id' => $business_id,
                'name' => $storedName,
                'path' => $file_path,
                'uploaded_by' => Skeleton::authUser()->id ?? null,
                'is_public' => $is_public,
            ] + $fileData,
            'store',
            [],
            $is_public
        );
    }
    private function importFileLogic(Request $request, string $raw_filename, string $table, string $columns, string $business_id): array
    {
        $file = $request->file($raw_filename) ?? throw new InvalidArgumentException('Invalid or missing file for input: ' . $raw_filename);
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['csv', 'xlsx', 'xls', 'tsv', 'ods'])) {
            throw new InvalidArgumentException('Import requires CSV, Excel, or ODS file.');
        }
        $this->validateRequired(null, $business_id, 'Business ID is required.');
        $columnArray = array_map('trim', explode(',', $columns));
        $importStats = ['imported' => 0, 'skipped' => 0, 'errors' => []];
        Excel::import(new class($this, $table, $columnArray, $importStats, $business_id) implements ToCollection, WithCustomValueBinder {
            public function __construct(
                private FileService $service,
                private string $table,
                private array $columns,
                private array &$importStats,
                private string $business_id
            ) {}
            public function bindValue(\PhpOffice\PhpSpreadsheet\Cell\Cell $cell, $value): bool
            {
                $cell->setValueExplicit($value, DataType::TYPE_STRING);
                return true;
            }
            public function collection(Collection $rows): void
            {
                if ($rows->isEmpty()) return;
                $headers = $rows->first()->toArray();
                $expectedHeaders = array_map('trim', $this->columns);
                if (count($headers) < count($expectedHeaders) || !empty(array_diff($expectedHeaders, array_map('trim', $headers)))) {
                    throw new InvalidArgumentException('Invalid or mismatched headers. Expected: ' . implode(',', $expectedHeaders));
                }
                $columnIndices = array_map(fn($col) => array_search($col, $headers), $expectedHeaders);
                $batch = [];
                foreach ($rows as $index => $row) {
                    if ($index === 0) continue;
                    $rowData = $row->toArray();
                    $data = array_combine($expectedHeaders, array_map(fn($i) => $rowData[$i] ?? null, $columnIndices));
                    if ($this->service->isDuplicate($data, $this->table, $this->business_id)) {
                        $this->importStats['errors'][] = ['row' => $index + 1, 'data' => $data, 'errors' => ['Duplicate record detected']];
                        $this->importStats['skipped']++;
                        continue;
                    }
                    $batch[] = $data;
                    if (count($batch) >= 1000) {
                        Data::insert($this->business_id, $this->table, $batch);
                        $this->importStats['imported'] += count($batch);
                        $batch = [];
                    }
                }
                if ($batch) {
                    Data::insert($this->business_id, $this->table, $batch);
                    $this->importStats['imported'] += count($batch);
                }
            }
        }, $file);
        $errorFileId = $importStats['errors'] ? $this->generateErrorFile($this->FILE_SYSTEM, $importStats['errors'], $table, $business_id) : null;
        return $this->formatResponse(true, [
            'imported_records' => $importStats['imported'],
            'skipped_records' => $importStats['skipped'],
            'error_file_id' => $errorFileId
        ], 'File imported successfully');
    }
    private function downloadFileLogic(int $file_log_id, bool $get_content): array
    {
        $fileLog = $this->getFileLog($file_log_id);
        $this->validateFileAccess($fileLog);
        $fileData = $this->getOrRecreateFile($fileLog);
        $disk = $fileData['is_public'] ? self::FILE_DISK_PUBLIC : self::FILE_DISK_PRIVATE;
        $cacheKey = "file_skeleton_{$fileLog['file_id']}_log_{$file_log_id}";
        if (!$get_content && $cachedData = Cache::get($cacheKey)) {
            Developer::debug('downloadFile: Cache hit', ['file_log_id' => $file_log_id]);
            return $this->formatResponse(true, array_merge($cachedData, ['public_url' => $fileData['is_public'] ? asset('storage/' . $fileData['path']) : null]), 'File retrieved successfully');
        }
        if ($get_content) {
            if ($fileLog['download_count'] >= $fileLog['download_limit']) {
                throw new InvalidArgumentException('Download limit exceeded for file log ID: ' . $file_log_id);
            }
            $physicalPath = ltrim($fileData['path'], '\\');
            if (!Storage::disk($disk)->exists($physicalPath)) {
                if ($fileLog['temp_file_path'] && in_array($fileLog['operation'], ['export', 'report', 'zip'])) {
                    $fileData = $this->recreateFile($this->FILE_SYSTEM, $fileLog);
                    $disk = $fileData['is_public'] ? self::FILE_DISK_PUBLIC : self::FILE_DISK_PRIVATE;
                    $physicalPath = ltrim($fileData['path'], '\\');
                } else {
                    throw new Exception('File not found in storage: ' . $fileData['path']);
                }
            }
            $this->trackDownload($this->FILE_SYSTEM, $file_log_id, Skeleton::authUser()->id ?? null, request()->ip());
            $this->incrementDownloadCount($file_log_id, $fileLog['download_count']);
            $fileData['content'] = Storage::disk($disk)->get($physicalPath);
            $fileData['content_type'] = $fileData['mime_type'];
            $fileData['public_url'] = $fileData['is_public'] ? asset('storage/' . $fileData['path']) : null;
        }
        if (!$get_content) {
            Cache::put($cacheKey, $fileData, self::CACHE_TTL);
        }
        return $this->formatResponse(true, $fileData, 'File retrieved successfully');
    }
    private function reportLogic(string $reportType, array $params, string $business_id, string $format, bool $is_public): array
    {
        $reportConfig = [
            'payslips' => ['table' => 'payslips', 'columns' => ['employee_id', 'name', 'amount', 'date']],
            'attendance' => ['table' => 'attendance', 'columns' => ['employee_id', 'date', 'status']],
        ];
        if (!isset($reportConfig[$reportType])) {
            throw new InvalidArgumentException('Invalid report type: ' . $reportType);
        }
        $table = $reportConfig[$reportType]['table'];
        $params['select'] = $params['columns'] ?? $reportConfig[$reportType]['columns'];
        return $this->handleFileOperation('report', ['report_type' => $reportType], 'report', $table, $params, $business_id, $format, $is_public, fn($filename) => "reports\\{$filename}", 'Report generated successfully');
    }
    private function zipLogic(array $file_ids, string $key, string $business_id, bool $is_public): array
    {
        $this->validateRequired($key, $business_id, 'Folder key and business ID are required.');
        if (empty($file_ids)) {
            throw new InvalidArgumentException('File IDs array cannot be empty.');
        }
        $filters = ['file_ids' => $file_ids];
        $existingLog = $this->findMatchingFileLog($this->FILE_SYSTEM, 'zip', null, $filters);
        if ($existingLog && $this->isValidExistingFile($existingLog)) {
            return $this->formatResponse(true, [
                'file_id' => $existingLog['file_id'],
                'file_log_id' => $existingLog['id'],
                'file_path' => $existingLog['temp_file_path'],
                'public_url' => $existingLog['is_public'] ? asset('storage/' . $existingLog['temp_file_path']) : null
            ], 'Existing zip file found');
        }
        $folder_id = $this->getFolderId($key);
        $files = Data::query($this->FILE_SYSTEM, 'files', [
            'select' => ['file_id', 'name', 'path', 'is_public'],
            'where' => [
                ['column' => 'file_id', 'operator' => 'IN', 'value' => $file_ids],
                ['column' => 'business_id', 'operator' => '=', 'value' => $business_id],
            ]
        ]);
        if (!$files['status'] || empty($files['data'])) {
            throw new InvalidArgumentException('No valid files found for provided IDs.');
        }
        $zipFileName = 'merged_' . Str::random(10) . '.zip';
        $zipPhysicalPath = "temp\\{$zipFileName}";
        $folderPath = $this->getFolderPath($key);
        $zipFilePath = "{$folderPath}\\{$zipFileName}";
        $disk = $is_public ? self::FILE_DISK_PUBLIC : self::FILE_DISK_PRIVATE;
        if ($is_public) {
            $this->ensureDirectoryExists(Storage::disk(self::FILE_DISK_PUBLIC)->path(ltrim($folderPath, '\\')));
        }
        if (!$this->archiveFiles($files['data'], $zipPhysicalPath, $disk)) {
            throw new Exception('Failed to create zip archive.');
        }
        return $this->createFile(
            [
                'file_id' => Random::unique(10, 'FLX'),
                'folder_id' => $folder_id,
                'business_id' => $business_id,
                'name' => $zipFileName,
                'path' => $zipFileName,
                'original_name' => $zipFileName,
                'extension' => 'zip',
                'mime_type' => $this->getMimeType('zip'),
                'size_bytes' => Storage::disk($disk)->size($zipPhysicalPath),
                'file_path' => $zipFilePath,
                'uploaded_by' => Skeleton::authUser()->id ?? null,
                'is_public' => $is_public,
            ],
            'zip',
            $filters,
            $is_public,
            $zipFilePath
        );
    }
    private function validateFile(UploadedFile $file, string $filename): array
    {
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $sizeBytes = $file->getSize() ?: throw new InvalidArgumentException('Failed to retrieve file size.');
        return [
            'name' => $filename,
            'original_name' => $originalName,
            'extension' => $extension,
            'mime_type' => $this->getMimeType($extension),
            'size_bytes' => $sizeBytes,
            'metadata' => json_encode($this->generateFileMetadata($file, $extension))
        ];
    }
    private function generateFileMetadata(UploadedFile $file, string $extension): array
    {
        $metadata = [];
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'webp'])) {
            try {
                [$width, $height] = getimagesize($file->path()) ?: [null, null];
                $metadata = ['width' => $width, 'height' => $height];
            } catch (Exception $e) {
                Developer::warning('Failed to extract image metadata', ['extension' => $extension, 'error' => $e->getMessage()]);
            }
        }
        return $metadata;
    }
    public function validateRowData(array $data, string $table, array $schemaColumns, string $system): array
    {
        $errors = [];
        foreach ($data as $column => $value) {
            if (!isset($schemaColumns[$column])) {
                $errors[] = "Column $column not found in table schema.";
                continue;
            }
            $schema = $schemaColumns[$column];
            if (is_null($value) && !($schema['nullable'] ?? false)) {
                $errors[] = "$column must not be null.";
            } elseif (!is_null($value)) {
                if (($schema['type'] ?? 'string') === 'integer' && !is_numeric($value)) {
                    $errors[] = "$column must be a number.";
                } elseif (($schema['type'] ?? 'string') === 'boolean' && !in_array(strtolower((string) $value), ['true', 'false', '1', '0'])) {
                    $errors[] = "$column must be true or false.";
                } elseif (($schema['length'] ?? null) && strlen((string) $value) > $schema['length']) {
                    $errors[] = "$column must not exceed {$schema['length']} characters.";
                }
            }
        }
        return $errors;
    }
    public function isDuplicate(array $data, string $table, string $system): bool
    {
        foreach (['id', 'email', 'key'] as $col) {
            if (isset($data[$col])) {
                $result = Data::query($system, $table, [
                    'select' => ['id'],
                    'where' => [['column' => $col, 'operator' => '=', 'value' => $data[$col]]]
                ]);
                if ($result['status'] && !empty($result['data'])) {
                    return true;
                }
            }
        }
        return false;
    }
    private function generateErrorFile(string $system, array $errors, string $table, string $business_id): ?string
    {
        $filename = "errors_" . Str::slug($table) . '_' . now()->format('YmdHis') . '.xlsx';
        $physicalPath = "errors\\{$filename}";
        return $this->createFile(
            [
                'file_id' => Random::unique(10, 'FLX'),
                'folder_id' => $this->getFolderId('errors'),
                'business_id' => $business_id,
                'name' => $filename,
                'path' => $this->getFolderPath('errors') . '\\' . $filename,
                'original_name' => $filename,
                'extension' => 'xlsx',
                'mime_type' => $this->getMimeType('xlsx'),
                'uploaded_by' => Skeleton::authUser()->id ?? null,
                'is_public' => false,
            ],
            'errors',
            ['table' => $table],
            false,
            null,
            fn() => Excel::store(new class($errors) implements \Maatwebsite\Excel\Concerns\FromArray {
                public function __construct(private array $data) {}
                public function array(): array
                {
                    return array_merge([['Row', 'Data', 'Errors']], array_map(fn($error) => [
                        $error['row'],
                        json_encode($error['data']),
                        implode('; ', $error['errors'])
                    ], $this->data));
                }
            }, $physicalPath, self::FILE_DISK_PRIVATE)
        )['data']['file_id'] ?? null;
    }
    private function recreateFile(string $system, array $fileLog): array
    {
        $filters = json_decode($fileLog['filters'], true) ?? throw new InvalidArgumentException('No filters available to recreate file for log ID: ' . $fileLog['id']);
        $operation = $fileLog['operation'];
        $is_public = $fileLog['is_public'];
        $folder_key = in_array($operation, ['export', 'report']) ? $operation . 's' : 'temp';
        $folderPath = $this->getFolderPath($folder_key);
        $folder_id = $this->getFolderId($folder_key);
        if ($is_public) {
            $this->ensureDirectoryExists(Storage::disk(self::FILE_DISK_PUBLIC)->path(ltrim($folderPath, '\\')));
        }
        $file_id = Random::unique(10, 'FLX');
        $filename = Str::random(10) . '.' . ($operation === 'zip' ? 'zip' : 'xlsx');
        $physicalPath = ltrim($folderPath, '\\') . '\\' . $filename;
        $file_path = "{$folderPath}\\{$filename}";
        $disk = $is_public ? self::FILE_DISK_PUBLIC : self::FILE_DISK_PRIVATE;
        if (in_array($operation, ['export', 'report'])) {
            $table = $filters['table'] ?? throw new InvalidArgumentException('Table not specified in filters for operation: ' . $operation);
            $result = Data::query($this->FILE_SYSTEM, $table, $filters);
            if (!$result['status']) {
                throw new Exception('Failed to fetch data for recreation: ' . $result['message']);
            }
            $exportData = [array_map(fn($col) => str_replace('.', '_', $col), $filters['select'] ?? [])];
            foreach ($result['data'] as $row) {
                $exportData[] = array_map(fn($col) => $row[str_replace('_', '.', $col)] ?? null, $filters['select'] ?? []);
            }
            Excel::store(new class($exportData) implements \Maatwebsite\Excel\Concerns\FromArray {
                public function __construct(private array $data) {}
                public function array(): array
                {
                    return $this->data;
                }
            }, $physicalPath, $disk);
        } elseif ($operation === 'zip') {
            $file_ids = $filters['file_ids'] ?? throw new InvalidArgumentException('No file IDs provided for zip recreation.');
            $files = Data::query($this->FILE_SYSTEM, 'files', [
                'select' => ['file_id', 'name', 'path', 'is_public'],
                'where' => [
                    ['column' => 'file_id', 'operator' => 'IN', 'value' => $file_ids],
                ]
            ]);
            if (!$files['status'] || empty($files['data'])) {
                throw new InvalidArgumentException('No valid files found for zip recreation.');
            }
            if (!$this->archiveFiles($files['data'], $physicalPath, $disk)) {
                throw new Exception('Failed to recreate zip archive.');
            }
        } else {
            throw new InvalidArgumentException('Unsupported operation for file recreation: ' . $operation);
        }
        return $this->createFile(
            [
                'file_id' => $file_id,
                'folder_id' => $folder_id,
                'business_id' => Skeleton::authUser()->business_id ?? 'BSN0000001',
                'name' => $filename,
                'path' => $file_path,
                'original_name' => $filename,
                'extension' => $operation === 'zip' ? 'zip' : 'xlsx',
                'mime_type' => $this->getMimeType($operation === 'zip' ? 'zip' : 'xlsx'),
                'size_bytes' => Storage::disk($disk)->size($physicalPath),
                'uploaded_by' => Skeleton::authUser()->id ?? null,
                'is_public' => $is_public,
            ],
            $operation,
            $filters,
            $is_public,
            null,
            null,
            'File recreated successfully'
        )['data'];
    }
    private function archiveFiles(array $files, string $zipPhysicalPath, string $disk): bool
    {
        $zip = new ZipArchive();
        $tempPath = Storage::disk($disk)->path($zipPhysicalPath);
        $this->ensureDirectoryExists(dirname($tempPath));
        if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Failed to create zip archive.');
        }
        foreach ($files as $file) {
            $filePath = ltrim($file['path'], '\\/');
            $sourceDisk = $file['is_public'] ? self::FILE_DISK_PUBLIC : self::FILE_DISK_PRIVATE;
            if (Storage::disk($sourceDisk)->exists($filePath)) {
                $zip->addFromString($file['name'], Storage::disk($sourceDisk)->get($filePath));
            }
        }
        return $zip->close();
    }
    private function trackDownload(string $system, int $file_log_id, ?string $user_id, ?string $ip_address): void
    {
        $result = Data::insert($system, 'download_logs', [
            'file_log_id' => $file_log_id,
            'downloaded_by' => $user_id,
            'ip_address' => $ip_address,
            'downloaded_at' => now(),
        ]);
        if (!$result['status']) {
            throw new Exception('Failed to log download: ' . $result['message']);
        }
    }
    private function findMatchingFileLog(string $system, string $operation, ?string $table, array $filters): ?array
    {
        $where = [
            ['column' => 'operation', 'operator' => '=', 'value' => $operation],
            ['column' => 'status', 'operator' => '=', 'value' => 'active'],
            ['column' => 'deleted_at', 'operator' => 'IS NULL'],
        ];
        if ($table) {
            $where[] = ['column' => 'filters->table', 'operator' => '=', 'value' => $table];
        }
        $logs = Data::query($system, 'file_logs', [
            'select' => ['id', 'file_id', 'filters', 'temp_file_path', 'is_public', 'expiration_at'],
            'where' => $where
        ]);
        if (!$logs['status'] || empty($logs['data'])) {
            return null;
        }
        $filtersJson = json_encode($filters);
        foreach ($logs['data'] as $log) {
            if ($log['filters'] === $filtersJson && (!isset($log['expiration_at']) || now()->lessThan($log['expiration_at']))) {
                return $log;
            }
        }
        return null;
    }
    private function getMimeType(string $extension): string
    {
        return config('filesystems.mime_types.' . strtolower($extension), 'application/octet-stream');
    }
    private function clearFileCache(string $system, string $file_id): void
    {
        Cache::forget("file_{$system}_{$file_id}");
        Cache::forget("file_logs_{$system}_{$file_id}");
        Cache::forget("folder_paths_{$system}");
    }
    private function formatResponse(bool $status, array $data, string $message): array
    {
        return ['status' => $status, 'data' => $data, 'message' => $message];
    }
    private function ensureDirectoryExists(string $path): void
    {
        if (!file_exists($path) && !mkdir($path, 0755, true)) {
            throw new Exception('Failed to create directory: ' . $path);
        }
    }
    private function handleFileOperation(string $method, array $logContext, string $operation, string $table, array $params, string $business_id, string $format, bool $is_public, callable $pathGenerator, string $successMessage): array
    {
        return $this->handleOperation($method, $logContext, function () use ($operation, $table, $params, $business_id, $format, $is_public, $pathGenerator, $successMessage) {
            if (!in_array($format, ['csv', 'xlsx', 'ods'])) {
                throw new InvalidArgumentException('Invalid format. Must be "csv", "xlsx", or "ods".');
            }
            $this->validateRequired(null, $business_id, 'Business ID is required.');
            $existingLog = $this->findMatchingFileLog($this->FILE_SYSTEM, $operation, $table, $params);
            if ($existingLog && $this->isValidExistingFile($existingLog)) {
                return $this->formatResponse(true, [
                    'file_id' => $existingLog['file_id'],
                    'file_log_id' => $existingLog['id'],
                    'file_path' => $existingLog['temp_file_path'],
                    'public_url' => $existingLog['is_public'] ? asset('storage/' . $existingLog['temp_file_path']) : null
                ], "Existing {$operation} file found");
            }
            $filename = Str::slug($table) . '_' . now()->format('YmdHis') . '.' . $format;
            $physicalPath = $pathGenerator($filename);
            $folder_key = $operation . 's';
            $disk = $is_public ? self::FILE_DISK_PUBLIC : self::FILE_DISK_PRIVATE;
            if ($is_public) {
                $this->ensureDirectoryExists(Storage::disk(self::FILE_DISK_PUBLIC)->path(ltrim($this->getFolderPath($folder_key), '\\')));
            }
            $result = Data::query($this->FILE_SYSTEM, $table, $params);
            if (!$result['status']) {
                throw new Exception('Failed to fetch data: ' . $result['message']);
            }
            $exportData = [array_map(fn($col) => str_replace('.', '_', $col), $params['select'] ?? [])];
            foreach ($result['data'] as $row) {
                $exportData[] = array_map(fn($col) => $row[$col] ?? null, $params['select'] ?? []);
            }
            Excel::store(new class($exportData) implements \Maatwebsite\Excel\Concerns\FromArray {
                public function __construct(private array $data) {}
                public function array(): array
                {
                    return $this->data;
                }
            }, $physicalPath, $disk);
            return $this->createFile(
                [
                    'file_id' => Random::unique(10, 'FLX'),
                    'folder_id' => $this->getFolderId($folder_key),
                    'business_id' => $business_id,
                    'name' => $filename,
                    'path' => $this->getFolderPath($folder_key) . '\\' . $filename,
                    'original_name' => $filename,
                    'extension' => $format,
                    'mime_type' => $this->getMimeType($format),
                    'size_bytes' => Storage::disk($disk)->size($physicalPath),
                    'uploaded_by' => Skeleton::authUser()->id ?? null,
                    'is_public' => $is_public,
                ],
                $operation,
                $params,
                $is_public,
                null,
                null,
                $successMessage
            );
        });
    }
    private function createFile(array $fileData, string $operation, array $filters, bool $is_public, ?string $temp_file_path = null, ?callable $storeCallback = null, string $successMessage = 'File created successfully'): array
    {
        return $this->handleOperation('createFile', ['operation' => $operation, 'file_id' => $fileData['file_id']], function () use ($fileData, $operation, $filters, $is_public, $temp_file_path, $storeCallback, $successMessage) {
            $disk = $is_public ? self::FILE_DISK_PUBLIC : self::FILE_DISK_PRIVATE;
            if ($storeCallback) {
                $storeCallback();
                $fileData['size_bytes'] = Storage::disk($disk)->size(ltrim($fileData['path'], '\\'));
            }
            $fileInsert = Data::insert($this->FILE_SYSTEM, 'files', array_filter($fileData, fn($value) => !is_null($value)));
            if (!$fileInsert['status']) {
                throw new Exception('Failed to insert file record: ' . $fileInsert['message']);
            }
            return $this->formatResponse(true, [
                'file_id' => $fileData['file_id'],
                'file_path' => $temp_file_path ?? $fileData['path'],
                'public_url' => $is_public ? asset('storage/' . ($temp_file_path ?? $fileData['path'])) : null,
            ], $successMessage);
        });
    }
    private function handleOperation(string $method, array $context, callable $callback, bool $returnRaw = false): mixed
    {
        try {
            $result = $callback();
            return $returnRaw ? $result : $result;
        } catch (InvalidArgumentException $e) {
            Developer::warning("Invalid argument in $method", array_merge($context, ['error' => $e->getMessage()]));
            return $returnRaw ? throw $e : $this->formatResponse(false, [], $e->getMessage());
        } catch (Exception $e) {
            Developer::error("Unexpected error in $method", array_merge($context, ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]));
            return $returnRaw ? throw $e : $this->formatResponse(false, [], $e->getMessage());
        }
    }
    private function validateRequired(?string $key, ?string $business_id, string $message): void
    {
        if ((empty($key) && $key !== null) || empty($business_id)) {
            throw new InvalidArgumentException($message);
        }
    }
    private function validateExtension(string $extension): void
    {
        if (!in_array(strtolower($extension), config('filesystems.allowed_extensions', []))) {
            throw new InvalidArgumentException('Unsupported file extension: ' . $extension);
        }
    }
    private function getFolderId(string $key): string
    {
        $folder = Data::query(
            'central',
            'skeleton_folders',
            [
                'select' => ['folder_id'],
                'where' => [
                    ['column' => 'key', 'operator' => '=', 'value' => $key],
                    ['column' => 'is_approved', 'operator' => '=', 'value' => 1]
                ]
            ]
        );
        if ($folder['status'] && !empty($folder['data'])) {
            return $folder['data'][0]['folder_id'];
        }
        throw new InvalidArgumentException('Folder not found for key: ' . $key);
    }
    private function generateUniqueFilename(string $base_filename, string $extension, string $key, bool $is_public): string
    {
        $counter = 1;
        $storedName = Str::ucfirst($base_filename) . '-' . sprintf('%03d', $counter) . '.' . $extension;
        $folderPath = $this->getFolderPath($key);
        $disk = $is_public ? self::FILE_DISK_PUBLIC : self::FILE_DISK_PRIVATE;
        while (Storage::disk($disk)->exists(ltrim($folderPath, '\\') . '\\' . $storedName)) {
            $counter++;
            $storedName = Str::ucfirst($base_filename) . '-' . sprintf('%03d', $counter) . '.' . $extension;
        }
        return $storedName;
    }
    private function storeFile(UploadedFile $file, string $storedName, string $key, bool $is_public): string
    {
        $folderPath = $this->getFolderPath($key);
        $disk = $is_public ? self::FILE_DISK_PUBLIC : self::FILE_DISK_PRIVATE;
        if ($is_public) {
            $this->ensureDirectoryExists(Storage::disk(self::FILE_DISK_PUBLIC)->path(ltrim($folderPath, '\\')));
            $this->ensureDirectoryExists(Storage::disk(self::FILE_DISK_PUBLIC_COPY)->path(ltrim($folderPath, '\\')));
        }
        return Storage::disk($disk)->putFileAs(ltrim($folderPath, '\\'), $file, $storedName);
    }
    private function getFileLog(int $file_log_id): array
    {
        $logResult = Data::query('central', 'file_logs', [
            'select' => ['id', 'file_id', 'operation', 'filters', 'temp_file_path', 'status', 'is_public', 'download_count', 'download_limit', 'created_by'],
            'where' => [
                ['column' => 'id', 'operator' => '=', 'value' => $file_log_id]
            ]
        ]);
        if (!$logResult['status'] || empty($logResult['data'])) {
            throw new InvalidArgumentException('File log not found for ID: ' . $file_log_id);
        }
        return $logResult['data'][0];
    }
    private function validateFileAccess(array $fileLog): void
    {
        $user = Skeleton::authUser();
        if (!$fileLog['is_public'] && (!$user || ($user->user_id !== $fileLog['created_by']))) {
            throw new InvalidArgumentException('Unauthorized access to private file.');
        }
    }
    private function getOrRecreateFile(array $fileLog): array
    {
        $fileResult = Data::query($this->FILE_SYSTEM, 'files', [
            'select' => ['file_id', 'folder_id', 'business_id', 'name', 'path', 'extension', 'mime_type', 'size_bytes', 'is_public', 'uploaded_by'],
            'where' => [
                ['column' => 'file_id', 'operator' => '=', 'value' => $fileLog['file_id']]
            ]
        ]);
        if (!$fileResult['status'] || empty($fileResult['data'])) {
            if (in_array($fileLog['operation'], ['export', 'report', 'zip'])) {
                return $this->recreateFile($this->FILE_SYSTEM, $fileLog);
            }
            throw new InvalidArgumentException('File not found for ID: ' . $fileLog['file_id']);
        }
        return $fileResult['data'][0];
    }
    private function incrementDownloadCount(int $file_log_id, int $currentCount): void
    {
        $updateResult = Data::update($this->FILE_SYSTEM, 'file_logs', ['download_count' => $currentCount + 1], [
            'where' => [['column' => 'id', 'operator' => '=', 'value' => $file_log_id]]
        ]);
        if (!$updateResult['status']) {
            throw new Exception('Failed to update download count.');
        }
    }
    private function isValidExistingFile(array $fileLog): bool
    {
        $fileResult = Data::query($this->FILE_SYSTEM, 'files', [
            'select' => ['file_id', 'path', 'is_public'],
            'where' => [
                ['column' => 'file_id', 'value' => $fileLog['file_id']],
            ]
        ]);
        return $fileResult['status'] && !empty($fileResult['data']) && Storage::disk($fileLog['is_public'] ? self::FILE_DISK_PUBLIC : self::FILE_DISK_PRIVATE)->exists(ltrim($fileLog['temp_file_path'], '\\'));
    }
}
