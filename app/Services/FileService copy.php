<?php
declare(strict_types=1);
namespace App\Services;
use App\Events\FileManager\TemporaryFileCreated;
use App\Facades\{Developer, Random, Skeleton, Helper};
use App\Services\Data\DataService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Cache, Storage, DB};
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\{ToCollection, WithCustomValueBinder};
use PhpOffice\PhpSpreadsheet\Cell\{CellValueBinder, DataType};
use Illuminate\Support\Collection;
use ZipArchive;
use Exception;
use InvalidArgumentException;
/**
 * Service for managing file operations including saving, importing, downloading, exporting, reporting, and zipping.
 */
class FileManagerService
{
    private const CACHE_TTL = 7200; // Cache time-to-live in seconds (2 hours)
    private const TEMP_FILE_TTL = 300; // Temporary file expiration in seconds (5 minutes)
    private const DEFAULT_DOWNLOAD_LIMIT = 10; // Default download limit
    private const FILE_DISK_PRIVATE = 'files_private';
    private const FILE_DISK_PUBLIC = 'files_public';
    private const FILE_DISK_PUBLIC_COPY = 'files_public_copy';
    /**
     * Save a file to storage with a formatted filename.
     *
     * @param Request $request The HTTP request containing the file
     * @param string $key Folder key for storage
     * @param string $raw_filename Input name of the file in the request
     * @param string|null $custom_filename Custom filename prefix (optional)
     * @param string $business_id Business ID
     * @param bool $is_public Public or private file
     * @return array<string, mixed> Response with status, data, and message
     */
    public function saveFile(Request $request, string $key, string $raw_filename, ?string $custom_filename, string $business_id, bool $is_public = false): array
    {
        return $this->handleOperation(
            'saveFile',
            ['key' => $key, 'business_id' => $business_id],
            function () use ($request, $key, $raw_filename, $custom_filename, $business_id, $is_public) {
                if (!$request instanceof Request) {
                    throw new InvalidArgumentException('Invalid request object.');
                }
                $file = $request->file($raw_filename);
                if (!$file || !$file->isValid()) {
                    throw new InvalidArgumentException('Invalid or missing file for input: ' . $raw_filename);
                }
                $this->validateRequired($key, $business_id, 'Folder key and business ID are required.');
                $fileData = $this->validateFile($file, $custom_filename ?? $raw_filename);
                $this->validateExtension($fileData['extension']);
                $folder_id = $this->getFolderId($key);
                $this->checkDuplicateFile($fileData['checksum'], $folder_id, $key);
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
        );
    }
    /**
     * Import data from a file to a table.
     *
     * @param Request $request The HTTP request containing the file
     * @param string $raw_filename Input name of the file
     * @param string $table Target table
     * @param string $columns Comma-separated column names
     * @param string $business_id Business ID
     * @return array<string, mixed> Response with status, data, and message
     */
    public function importFile(Request $request, string $raw_filename, string $table, string $columns, string $business_id): array
    {
        return $this->handleOperation(
            'importFile',
            ['table' => $table, 'business_id' => $business_id],
            function () use ($request, $raw_filename, $table, $columns, $business_id) {
                if (!$request instanceof Request) {
                    throw new InvalidArgumentException('Invalid request object.');
                }
                $file = $request->file($raw_filename);
                if (!$file || !$file->isValid()) {
                    throw new InvalidArgumentException('Invalid or missing file for input: ' . $raw_filename);
                }
                $extension = strtolower($file->getClientOriginalExtension());
                if (!in_array($extension, ['csv', 'xlsx', 'xls', 'tsv', 'ods'])) {
                    throw new InvalidArgumentException('Import requires CSV, Excel, or ODS file.');
                }
                $this->validateRequired(null, $business_id, 'Business ID is required.');
                $columnArray = array_map('trim', explode(',', $columns));
                $importStats = ['imported' => 0, 'skipped' => 0, 'errors' => []];
                Excel::import(new class($this, $table, $columnArray, $importStats) implements ToCollection, WithCustomValueBinder {
                    private FileManagerService $service;
                    private string $table;
                    private array $columns;
                    private array $importStats;
                    public function __construct(FileManagerService $service, string $table, array $columns, array &$importStats)
                    {
                        $this->service = $service;
                        $this->table = $table;
                        $this->columns = $columns;
                        $this->importStats = &$importStats;
                    }
                    public function bindValue(\PhpOffice\PhpSpreadsheet\Cell\Cell $cell, $value): bool
                    {
                        $cell->setValueExplicit($value, DataType::TYPE_STRING);
                        return true;
                    }
                    public function collection(Collection $rows): void
                    {
                        if ($rows->isEmpty()) {
                            return;
                        }
                        $headers = $rows->first()->toArray();
                        $expectedHeaders = array_map('trim', $this->columns);
                        if (count($headers) < count($expectedHeaders) || !empty(array_diff($expectedHeaders, array_map('trim', $headers)))) {
                            throw new InvalidArgumentException('Invalid or mismatched headers. Expected: ' . implode(',', $expectedHeaders));
                        }
                        $columnIndices = array_map(fn($col) => array_search($col, $headers), $expectedHeaders);
                        $batch = [];
                        foreach ($rows as $index => $row) {
                            if ($index === 0) {
                                continue;
                            }
                            $rowData = $row->toArray();
                            $data = [];
                            foreach ($columnIndices as $colIndex => $headerIndex) {
                                $data[$expectedHeaders[$colIndex]] = $rowData[$headerIndex] ?? null;
                            }
                            $validationErrors = []; // Removed validation
                            if (!empty($validationErrors)) {
                                $this->importStats['errors'][] = [
                                    'row' => $index + 1,
                                    'data' => $data,
                                    'errors' => $validationErrors
                                ];
                                $this->importStats['skipped']++;
                                continue;
                            }
                            if ($this->service->isDuplicate($data, $this->table, 'central')) {
                                $this->importStats['errors'][] = [
                                    'row' => $index + 1,
                                    'data' => $data,
                                    'errors' => ['Duplicate record detected']
                                ];
                                $this->importStats['skipped']++;
                                continue;
                            }
                            $batch[] = $data; // Removed prepareData
                            if (count($batch) >= 1000) {
                                DB::connection('central')->table($this->table)->insert($batch);
                                $this->importStats['imported'] += count($batch);
                                $batch = [];
                            }
                        }
                        if (!empty($batch)) {
                            DB::connection('central')->table($this->table)->insert($batch);
                            $this->importStats['imported'] += count($batch);
                        }
                    }
                }, $file);
                $errorFileId = !empty($importStats['errors']) ? $this->generateErrorFile('central', $importStats['errors'], $table, $business_id) : null;
                return $this->formatResponse(true, [
                    'imported_records' => $importStats['imported'],
                    'skipped_records' => $importStats['skipped'],
                    'error_file_id' => $errorFileId
                ], 'File imported successfully');
            }
        );
    }
    /**
     * Download a file by log ID.
     *
     * @param int $file_log_id The file log ID
     * @param bool $get_content Return file content
     * @return array<string, mixed> Response with status, data, and message
     */
    public function downloadFile(int $file_log_id, bool $get_content = false): array
    {
        return $this->handleOperation(
            'downloadFile',
            ['file_log_id' => $file_log_id],
            function () use ($file_log_id, $get_content) {
                $fileLog = $this->getFileLog($file_log_id);
                $this->validateFileAccess($fileLog);
                $fileData = $this->getOrRecreateFile($fileLog);
                $disk = $fileData['is_public'] ? self::FILE_DISK_PUBLIC : self::FILE_DISK_PRIVATE;
                $cacheKey = "file_skeleton_{$fileLog['file_id']}_log_{$file_log_id}";
                if (!$get_content) {
                    $cachedData = Cache::get($cacheKey);
                    if ($cachedData) {
                        Developer::debug('downloadFile: Cache hit', ['file_log_id' => $file_log_id]);
                        return $this->formatResponse(true, array_merge($cachedData, [
                            'public_url' => $fileData['is_public'] ? asset('storage/' . $fileData['path']) : null
                        ]), 'File retrieved successfully');
                    }
                }
                if ($get_content) {
                    if ($fileLog['download_count'] >= $fileLog['download_limit']) {
                        throw new InvalidArgumentException('Download limit exceeded for file log ID: ' . $file_log_id);
                    }
                    $physicalPath = ltrim($fileData['path'], '\\');
                    if (!Storage::disk($disk)->exists($physicalPath)) {
                        if ($fileLog['temp_file_path'] && in_array($fileLog['operation'], ['export', 'report', 'zip'])) {
                            $fileData = $this->recreateFile('central', $fileLog);
                            $disk = $fileData['is_public'] ? self::FILE_DISK_PUBLIC : self::FILE_DISK_PRIVATE;
                            $physicalPath = ltrim($fileData['path'], '\\');
                        } else {
                            throw new Exception('File not found in storage: ' . $fileData['path']);
                        }
                    }
                    $this->trackDownload('central', $file_log_id, Skeleton::authUser()->id ?? null, request()->ip());
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
        );
    }
    /**
     * Export data to a file.
     *
     * @param string $table The source table
     * @param array<string, mixed> $params Query parameters
     * @param string $business_id Business ID
     * @param string $format Export format (csv, xlsx, ods)
     * @param bool $is_public Public or private file
     * @return array<string, mixed> Response with status, data, and message
     */
    public function exportFile(string $table, array $params, string $business_id, string $format = 'xlsx', bool $is_public = false): array
    {
        return $this->handleFileOperation(
            'exportFile',
            ['table' => $table],
            'export',
            $table,
            $params,
            $business_id,
            $format,
            $is_public,
            fn($filename) => "exports\\{$filename}",
            'File exported successfully'
        );
    }
    /**
     * Generate a report file.
     *
     * @param string $reportType The report type (e.g., payslips, attendance)
     * @param array<string, mixed> $params Query parameters
     * @param string $business_id Business ID
     * @param string $format Report format (csv, xlsx, ods)
     * @param bool $is_public Public or private file
     * @return array<string, mixed> Response with status, data, and message
     */
    public function report(string $reportType, array $params, string $business_id, string $format = 'xlsx', bool $is_public = false): array
    {
        return $this->handleOperation(
            'report',
            ['report_type' => $reportType],
            function () use ($reportType, $params, $business_id, $format, $is_public) {
                $reportConfig = [
                    'payslips' => ['table' => 'payslips', 'columns' => ['employee_id', 'name', 'amount', 'date']],
                    'attendance' => ['table' => 'attendance', 'columns' => ['employee_id', 'date', 'status']],
                ];
                if (!isset($reportConfig[$reportType])) {
                    throw new InvalidArgumentException('Invalid report type: ' . $reportType);
                }
                $table = $reportConfig[$reportType]['table'];
                $params['select'] = $params['columns'] ?? $reportConfig[$reportType]['columns'];
                return $this->handleFileOperation(
                    'report',
                    ['report_type' => $reportType],
                    'report',
                    $table,
                    $params,
                    $business_id,
                    $format,
                    $is_public,
                    fn($filename) => "reports\\{$filename}",
                    'Report generated successfully'
                );
            }
        );
    }
    /**
     * Create a zip archive of files.
     *
     * @param array<string> $file_ids Array of file IDs to zip
     * @param string $key Folder key for storage
     * @param string $business_id Business ID
     * @param bool $is_public Public or private file
     * @return array<string, mixed> Response with status, data, and message
     */
    public function zip(array $file_ids, string $key, string $business_id, bool $is_public = false): array
    {
        return $this->handleOperation(
            'zip',
            ['file_ids' => $file_ids],
            function () use ($file_ids, $key, $business_id, $is_public) {
                $this->validateRequired($key, $business_id, 'Folder key and business ID are required.');
                if (empty($file_ids)) {
                    throw new InvalidArgumentException('File IDs array cannot be empty.');
                }
                $filters = ['file_ids' => $file_ids];
                $existingLog = $this->findMatchingFileLog('central', 'zip', null, $filters);
                if ($existingLog && $this->isValidExistingFile($existingLog)) {
                    return $this->formatResponse(true, [
                        'file_id' => $existingLog['file_id'],
                        'file_log_id' => $existingLog['id'],
                        'file_path' => $existingLog['temp_file_path'],
                        'public_url' => $existingLog['is_public'] ? asset('storage/' . $existingLog['temp_file_path']) : null
                    ], 'Existing zip file found');
                }
                $folder_id = $this->getFolderId($key);
                $files = DataService::fetch('central', 'files', [
                    'select' => ['file_id', 'name', 'path', 'is_public'],
                    'file_id' => ['operator' => 'IN', 'value' => $file_ids],
                    'deleted_at' => ['operator' => 'IS NULL']
                ]);
                if (!$files['status'] || empty($files['data'])) {
                    throw new InvalidArgumentException('No valid files found for provided IDs.');
                }
                $zipFileName = 'merged_' . Str::random(10) . '.zip';
                $zipPhysicalPath = "temp\\{$zipFileName}";
                $folderPath = $this->getFolderPath($key);
                $zipFilePath = $folderPath . '\\' . $zipFileName;
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
                        'checksum' => hash_file('sha256', Storage::disk($disk)->path($zipPhysicalPath)),
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
        );
    }
    /**
     * Fetch data from a specified table with selected columns and conditions.
     *
     * @param string $table Table name
     * @param array|string $columns Columns to select ('all' or array of column names)
     * @param array $condition Where conditions (e.g., ['where' => ['status' => 'active'], 'search' => 'term'])
     * @param string $output Output format ('array' or 'json')
     * @return array|string Fetched data in the specified format
     * @throws Exception
     */
    public function fetch(string $table, $columns, array $condition, string $output)
    {
        try {
            if (empty($table)) {
                throw new Exception('Table name is required.');
            }
            if (!in_array($output, ['array', 'json'], true)) {
                throw new Exception('Invalid output format. Must be "array" or "json".');
            }
            if (!is_array($columns) && $columns !== 'all') {
                throw new Exception('Columns must be "all" or an array of column names.');
            }
            if ($table == "skeleton_folders") {
                $system = 'central';
            } else {
                $system = Skeleton::getUserSystem();
            }
            $data = DataService::fetch($system, $table, $condition);
            $results = [];
            foreach ($data['data'] ?? [] as $row) {
                $item = [];
                if ($columns === 'all') {
                    foreach ((array)$row as $key => $value) {
                        $item[$key] = htmlspecialchars((string)$value);
                    }
                } else {
                    foreach ($columns as $col) {
                        $item[$col] = array_key_exists($col, (array)$row) ? htmlspecialchars((string)$row[$col]) : '';
                    }
                }
                $results[] = $item;
            }
            if (config('skeleton.developer_mode')) {
                Developer::debug('SelectCtrl: Fetch data', [
                    'system' => $system,
                    'table' => $table,
                    'columns' => $columns,
                    'condition' => $condition,
                    'results_count' => count($results),
                    'sample_result' => $results ? array_slice($results, 0, 3) : [],
                ]);
            }
            return $output === 'json' ? json_encode($results) : $results;
        } catch (Exception $e) {
            Developer::error('SelectCtrl: Error fetching data', [
                'table' => $table,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    /**
     * Get folder paths recursively.
     *
     * @param array|null $folders List of folders
     * @param int|null $parentId Parent folder ID
     * @param string $prefix Path prefix
     * @param array|null $paths Accumulated paths
     * @param bool $useKey Use folder key instead of folder_id
     * @return array<string, string> Folder paths
     */
    public function getFolderPaths(?array $folders = null, $parentId = null, string $prefix = '', ?array &$paths = null, bool $useKey = false): array
    {
        if ($folders === null) {
            $folders = $this->fetch('skeleton_folders', ['folder_id', 'key', 'name', 'parent_folder_id'], [
                'deleted_at' => ['operator' => 'IS NULL'], 'is_approved' => ['operator' => '=', 'value' => 1]
            ], 'array');
            if (config('skeleton.developer_mode')) {
                Developer::debug('getFolderPaths: Fetched folders', [
                    'folder_count' => count($folders),
                    'folders' => $folders ? array_slice($folders, 0, 3) : [],
                ]);
            }
        }
        if ($paths === null) {
            $paths = [];
        }
        foreach ($folders as $folder) {
            if (!isset($folder['folder_id'], $folder['name'], $folder['parent_folder_id']) || ($useKey && !isset($folder['key']))) {
                Developer::debug('getFolderPaths: Invalid folder data', ['folder' => $folder]);
                continue;
            }
            $folderParentId = $folder['parent_folder_id'] === '' ? null : $folder['parent_folder_id'];
            if ($folderParentId === $parentId) {
                $folderName = strtolower(preg_replace('/\s+/', '-', trim($folder['name'])));
                $fullPath = $prefix !== '' ? $prefix . '\\' . $folderName : $folderName;
                $key = $useKey ? $folder['key'] : $folder['folder_id'];
                $paths[$key] = '\\' . $fullPath;
                Developer::debug('getFolderPaths: Path created', [
                    'key' => $key,
                    'path' => '\\' . $fullPath,
                ]);
                // Recurse
                $this->getFolderPaths($folders, $folder['folder_id'], $fullPath, $paths, $useKey);
            }
        }
        return $paths;
    }
    /**
     * Get the path for a folder by its key.
     *
     * @param string $key The folder's key
     * @return string The folder's path
     */
    public function getFolderPath(string $key): string
    {
        try {
            if (empty($key)) {
                throw new Exception('Folder key is required.');
            }
            $cacheKey = 'folder_paths_all';
            $cachedPaths = Cache::get($cacheKey);
            if ($cachedPaths !== null && array_key_exists($key, $cachedPaths)) {
                if (config('skeleton.developer_mode')) {
                    Developer::debug('getFolderPath: Cache hit', [
                        'key' => $key,
                        'path' => $cachedPaths[$key],
                    ]);
                }
                return $cachedPaths[$key];
            }
            $paths = [];
            $folderPaths = $this->getFolderPaths(null, null, '', $paths, true);
            if (!array_key_exists($key, $folderPaths)) {
                throw new Exception('Folder not found for key: ' . $key);
            }
            Cache::put($cacheKey, $folderPaths, 3600);
            if (config('skeleton.developer_mode')) {
                Developer::debug('getFolderPath: Paths fetched and cached', [
                    'key' => $key,
                    'path' => $folderPaths[$key],
                    'total_paths' => count($folderPaths),
                ]);
            }
            return $folderPaths[$key];
        } catch (Exception $e) {
            Developer::error('getFolderPath: Error fetching folder path', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    public function isCircularReference(string $parentId, ?string $currentFolderId): bool
    {
        if (empty($parentId) || $parentId === $currentFolderId) {
            return true; // Self-parenting prevention
        }
        $folders = $this->fetch('skeleton_folders', ['folder_id', 'parent_folder_id'], [
            'deleted_at' => ['operator' => 'IS NULL'], 'is_approved' => ['operator' => '=', 'value' => 1]
        ], 'array');
        $visited = [$parentId];
        $current = $parentId;
        while ($current) {
            $found = false;
            foreach ($folders as $folder) {
                if ($folder['folder_id'] === $current && !empty($folder['parent_folder_id'])) {
                    if ($folder['parent_folder_id'] === $currentFolderId) {
                        return true; // Circular reference detected
                    }
                    if (in_array($folder['parent_folder_id'], $visited)) {
                        return true; // Already visited parent, loop detected
                    }
                    $visited[] = $folder['parent_folder_id'];
                    $current = $folder['parent_folder_id'];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $current = null; // End of chain
            }
        }
        return false;
    }
    /**
     * Retrieve file content by file ID from storage, returning base64-encoded content or full file info.
     *
     * @param string|null $file_id The file ID
     * @param bool $output If true, return array with file info and base64 content; if false, return base64 string or data URI for images
     * @return string|array Empty string if not found, or Base64-encoded content/data URI/full file info
     */
    public function getFile(?string $file_id, bool $output = false)
    {
        // if file_id is null or empty string → return empty string immediately
        if (empty($file_id)) {
            return '';
        }
        try {
            // Fetch file data from the files table
            $fileResult = DataService::fetch('central', 'files', [
                'select' => [
                    'file_id',
                    'name',
                    'path',
                    'extension',
                    'mime_type',
                    'size_bytes',
                    'is_public',
                    'deleted_at'
                ],
                'file_id' => ['operator' => '=', 'value' => $file_id],
                'deleted_at' => ['operator' => 'IS NULL']
            ]);
            if (!$fileResult['status'] || empty($fileResult['data'])) {
                return ''; // no file found
            }
            $file = $fileResult['data'][0];
            $disk = $file['is_public'] ? self::FILE_DISK_PUBLIC : self::FILE_DISK_PRIVATE;
            $physicalPath = ltrim($file['path'], '\\');
            // Check if file exists in storage
            if (!Storage::disk($disk)->exists($physicalPath)) {
                return '';
            }
            // Retrieve file content and encode as base64
            $fileContent = Storage::disk($disk)->get($physicalPath);
            $base64Content = base64_encode($fileContent);
            if ($output) {
                return [
                    'file_id' => $file['file_id'],
                    'name' => $file['name'],
                    'extension' => $file['extension'],
                    'mime_type' => $file['mime_type'],
                    'size_bytes' => $file['size_bytes'],
                    'is_public' => $file['is_public'],
                    'content' => $base64Content,
                ];
            }
            $imageMimeTypes = [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/bmp',
                'image/tiff',
                'image/webp'
            ];
            if (in_array($file['mime_type'], $imageMimeTypes)) {
                return 'data:' . $file['mime_type'] . ';base64,' . $base64Content;
            }
            return $base64Content;
        } catch (\Throwable $e) {
            Developer::warning("getFile error", [
                'file_id' => $file_id,
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }
    /**
     * Get the full file path by file ID.
     *
     * @param string $file_id The file ID
     * @return string The full file path
     * @throws InvalidArgumentException|Exception
     */
    public function getFilePath(string $file_id): string
    {
        try {
            if (empty($file_id)) {
                throw new InvalidArgumentException('File ID is required.');
            }
            $result = DataService::fetch('central', 'files', [
                'select' => ['path'],
                'file_id' => ['operator' => '=', 'value' => $file_id],
                'deleted_at' => ['operator' => 'IS NULL']
            ]);
            if (!$result['status'] || empty($result['data'])) {
                throw new InvalidArgumentException('File not found for ID: ' . $file_id);
            }
            return $result['data'][0]['path'];
        } catch (InvalidArgumentException $e) {
            Developer::warning("Invalid argument in getFilePath", ['file_id' => $file_id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (Exception $e) {
            Developer::error("Unexpected error in getFilePath", [
                'file_id' => $file_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
    /**
     * Validate file metadata.
     *
     * @param UploadedFile $file The uploaded file
     * @param string $filename Custom filename prefix
     * @return array<string, mixed> File metadata
     */
    protected function validateFile(UploadedFile $file, string $filename): array
    {
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $this->getMimeType($extension);
        $sizeBytes = $file->getSize();
        $checksum = hash_file('sha256', $file->path());
        $metadata = $this->generateFileMetadata($file, $extension);
        if ($sizeBytes === false || $checksum === false) {
            throw new InvalidArgumentException('Failed to retrieve file size or checksum.');
        }
        return [
            'name' => $filename,
            'original_name' => $originalName,
            'extension' => $extension,
            'mime_type' => $mimeType,
            'size_bytes' => $sizeBytes,
            'checksum' => $checksum,
            'metadata' => json_encode($metadata)
        ];
    }
    /**
     * Generate additional metadata for the file.
     *
     * @param UploadedFile $file The uploaded file
     * @param string $extension File extension
     * @return array<string, mixed> Metadata array
     */
    protected function generateFileMetadata(UploadedFile $file, string $extension): array
    {
        $metadata = [];
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'webp'])) {
            try {
                [$width, $height] = getimagesize($file->path()) ?: [null, null];
                $metadata['width'] = $width;
                $metadata['height'] = $height;
            } catch (Exception $e) {
                Developer::warning('Failed to extract image metadata', ['extension' => $extension, 'error' => $e->getMessage()]);
            }
        }
        return $metadata;
    }
    /**
     * Validate row data for import.
     *
     * @param array<string, mixed> $data Row data
     * @param string $table Target table
     * @param array<string, mixed> $schemaColumns Table schema
     * @param string $system System identifier
     * @return array<string> Validation errors
     */
    public function validateRowData(array $data, string $table, array $schemaColumns, string $system): array
    {
        $errors = [];
        foreach ($data as $column => $value) {
            if (!isset($schemaColumns[$column])) {
                $errors[] = "Column $column not found in table schema.";
                continue;
            }
            $schema = $schemaColumns[$column];
            $type = $schema['type'] ?? 'string';
            $nullable = $schema['nullable'] ?? false;
            $maxLength = $schema['length'] ?? null;
            if (is_null($value) && !$nullable) {
                $errors[] = "$column must not be null.";
            } elseif (!is_null($value)) {
                if ($type === 'integer' && !is_numeric($value)) {
                    $errors[] = "$column must be a number.";
                } elseif ($type === 'boolean' && !in_array(strtolower((string)$value), ['true', 'false', '1', '0'])) {
                    $errors[] = "$column must be true or false.";
                } elseif ($maxLength && strlen((string)$value) > $maxLength) {
                    $errors[] = "$column must not exceed $maxLength characters.";
                }
            }
        }
        return $errors;
    }
    /**
     * Check for duplicate records.
     *
     * @param array<string, mixed> $data Row data
     * @param string $table Target table
     * @param string $system System identifier
     * @return bool True if duplicate exists
     */
    public function isDuplicate(array $data, string $table, string $system): bool
    {
        foreach (['id', 'email', 'key'] as $col) {
            if (isset($data[$col])) {
                $result = DataService::fetch($system, $table, [
                    'select' => ['id'],
                    $col => ['operator' => '=', 'value' => $data[$col]]
                ]);
                if ($result['status'] && !empty($result['data'])) {
                    return true;
                }
            }
        }
        return false;
    }
    /**
     * Generate an error file for import errors.
     *
     * @param string $system System identifier
     * @param array<array<string, mixed>> $errors Import errors
     * @param string $table Target table
     * @param string $business_id Business ID
     * @return string|null Error file ID
     */
    protected function generateErrorFile(string $system, array $errors, string $table, string $business_id): ?string
    {
        $filename = "errors_" . Str::slug($table) . '_' . now()->format('YmdHis') . '.xlsx';
        $physicalPath = "errors\\{$filename}";
        $folder_key = 'errors';
        return $this->createFile(
            [
                'file_id' => Random::unique(10, 'FLX'),
                'folder_id' => $this->getFolderId($folder_key),
                'business_id' => $business_id,
                'name' => $filename,
                'path' => $this->getFolderPath($folder_key) . '\\' . $filename,
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
            function () use ($errors, $physicalPath) {
                Excel::store(new class($errors) implements \Maatwebsite\Excel\Concerns\FromArray {
                    private array $data;
                    public function __construct(array $data)
                    {
                        $this->data = $data;
                    }
                    public function array(): array
                    {
                        $errorData = [['Row', 'Data', 'Errors']];
                        foreach ($this->data as $error) {
                            $errorData[] = [
                                $error['row'],
                                json_encode($error['data']),
                                implode('; ', $error['errors'])
                            ];
                        }
                        return $errorData;
                    }
                }, $physicalPath, self::FILE_DISK_PRIVATE);
            }
        )['data']['file_id'] ?? null;
    }
    /**
     * Recreate a temporary file.
     *
     * @param string $system System identifier
     * @param array<string, mixed> $fileLog File log data
     * @return array<string, mixed> File data
     */
    protected function recreateFile(string $system, array $fileLog): array
    {
        $filters = json_decode($fileLog['filters'], true) ?? [];
        if (empty($filters)) {
            throw new InvalidArgumentException('No filters available to recreate file for log ID: ' . $fileLog['id']);
        }
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
        $file_path = $folderPath . '\\' . $filename;
        $disk = $is_public ? self::FILE_DISK_PUBLIC : self::FILE_DISK_PRIVATE;
        if (in_array($operation, ['export', 'report'])) {
            $table = $filters['table'] ?? null;
            if (!$table) {
                throw new InvalidArgumentException('Table not specified in filters for operation: ' . $operation);
            }
            $result = DataService::query('central', $table, $filters);
            if (!$result['status']) {
                throw new Exception('Failed to fetch data for recreation: ' . $result['message']);
            }
            $rows = $result['data'];
            $headings = array_map(fn($col) => str_replace('.', '_', $col), $filters['select'] ?? []);
            $exportData = [$headings];
            foreach ($rows as $row) {
                $rowData = [];
                foreach ($headings as $col) {
                    $rowData[] = $row[str_replace('_', '.', $col)] ?? null;
                }
                $exportData[] = $rowData;
            }
            Excel::store(new class($exportData) implements \Maatwebsite\Excel\Concerns\FromArray {
                private array $data;
                public function __construct(array $data)
                {
                    $this->data = $data;
                }
                public function array(): array
                {
                    return $this->data;
                }
            }, $physicalPath, $disk);
        } elseif ($operation === 'zip') {
            $file_ids = $filters['file_ids'] ?? [];
            if (empty($file_ids)) {
                throw new InvalidArgumentException('No file IDs provided for zip recreation.');
            }
            $files = DataService::fetch('central', 'files', [
                'select' => ['file_id', 'name', 'path', 'is_public'],
                'file_id' => ['operator' => 'IN', 'value' => $file_ids],
                'deleted_at' => ['operator' => 'IS NULL']
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
                'checksum' => hash_file('sha256', Storage::disk($disk)->path($physicalPath)),
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
    /**
     * Archive files into a zip.
     *
     * @param array<array<string, mixed>> $files Files to archive
     * @param string $zipPhysicalPath Path for the zip file
     * @param string $disk Storage disk
     * @return bool Success status
     */
    protected function archiveFiles(array $files, string $zipPhysicalPath, string $disk): bool
    {
        $zip = new ZipArchive();
        $tempPath = Storage::disk($disk)->path($zipPhysicalPath);
        $this->ensureDirectoryExists(dirname($tempPath));
        if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Failed to create zip archive.');
        }
        foreach ($files as $file) {
            $filePath = ltrim($file['path'], '\\');
            $sourceDisk = $file['is_public'] ? self::FILE_DISK_PUBLIC : self::FILE_DISK_PRIVATE;
            if (Storage::disk($sourceDisk)->exists($filePath)) {
                $zip->addFromString($file['name'], Storage::disk($sourceDisk)->get($filePath));
            }
        }
        return $zip->close();
    }
    /**
     * Track a file download.
     *
     * @param string $system System identifier
     * @param int $file_log_id File log ID
     * @param string|null $user_id User ID
     * @param string|null $ip_address IP address
     * @return void
     */
    protected function trackDownload(string $system, int $file_log_id, ?string $user_id, ?string $ip_address): void
    {
        $result = DataService::insert($system, 'download_logs', [
            'file_log_id' => $file_log_id,
            'downloaded_by' => $user_id,
            'ip_address' => $ip_address,
            'downloaded_at' => now(),
        ]);
        if (!$result['status']) {
            throw new Exception('Failed to log download: ' . $result['message']);
        }
        Developer::info('Download tracked', [
            'system' => $system,
            'file_log_id' => $file_log_id,
            'user_id' => $user_id,
            'ip_address' => $ip_address,
        ]);
    }
    /**
     * Find a matching file log for export, report, or zip operations.
     *
     * @param string $system System identifier
     * @param string $operation Operation type
     * @param string|null $table Target table
     * @param array<string, mixed> $filters Filters to match
     * @return array<string, mixed>|null Matching file log
     */
    protected function findMatchingFileLog(string $system, string $operation, ?string $table, array $filters): ?array
    {
        $where = [
            'operation' => ['operator' => '=', 'value' => $operation],
            'status' => ['operator' => '=', 'value' => 'active'],
            'deleted_at' => ['operator' => 'IS NULL'],
        ];
        if ($table) {
            $where['filters->table'] = ['operator' => '=', 'value' => $table];
        }
        $logs = DataService::fetch($system, 'file_logs', [
            'select' => ['id', 'file_id', 'filters', 'temp_file_path', 'is_public', 'expiration_at'],
            ...$where
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
    /**
     * Get MIME type for a file extension.
     *
     * @param string $extension File extension
     * @return string MIME type
     */
    protected function getMimeType(string $extension): string
    {
        return config('filesystems.mime_types.' . strtolower($extension), 'application/octet-stream');
    }
    /**
     * Clear file-related cache.
     *
     * @param string $system System identifier
     * @param string $file_id File ID
     * @return void
     */
    protected function clearFileCache(string $system, string $file_id): void
    {
        Cache::forget("file_{$system}_{$file_id}");
        Cache::forget("file_logs_{$system}_{$file_id}");
        Cache::forget("folder_paths_{$system}");
        Developer::info('File cache cleared', ['system' => $system, 'file_id' => $file_id]);
    }
    /**
     * Format response in a consistent structure.
     *
     * @param bool $status Success status
     * @param array<string, mixed> $data Response data
     * @param string $message Response message
     * @return array<string, mixed> Formatted response
     */
    protected function formatResponse(bool $status, array $data, string $message): array
    {
        return [
            'status' => $status,
            'data' => $data,
            'message' => $message,
        ];
    }
    /**
     * Ensure a directory exists in the storage disk.
     *
     * @param string $path Directory path
     * @return void
     */
    protected function ensureDirectoryExists(string $path): void
    {
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
            Developer::info('Directory created', ['path' => $path]);
        }
    }
    /**
     * Handle file operations (export, report) with common logic.
     *
     * @param string $method Method name for logging
     * @param array<string, mixed> $logContext Logging context
     * @param string $operation Operation type
     * @param string $table Target table
     * @param array<string, mixed> $params Query parameters
     * @param string $business_id Business ID
     * @param string $format File format
     * @param bool $is_public Public or private file
     * @param callable $pathGenerator Generates physical path
     * @param string $successMessage Success message
     * @return array<string, mixed> Response
     */
    protected function handleFileOperation(
        string $method,
        array $logContext,
        string $operation,
        string $table,
        array $params,
        string $business_id,
        string $format,
        bool $is_public,
        callable $pathGenerator,
        string $successMessage
    ): array {
        return $this->handleOperation(
            $method,
            $logContext,
            function () use ($operation, $table, $params, $business_id, $format, $is_public, $pathGenerator, $successMessage) {
                if (!in_array($format, ['csv', 'xlsx', 'ods'])) {
                    throw new InvalidArgumentException('Invalid format. Must be "csv", "xlsx", or "ods".');
                }
                $this->validateRequired(null, $business_id, 'Business ID is required.');
                $existingLog = $this->findMatchingFileLog('central', $operation, $table, $params);
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
                $result = DataService::query('central', $table, $params);
                if (!$result['status']) {
                    throw new Exception('Failed to fetch data: ' . $result['message']);
                }
                $rows = $result['data'];
                $headings = array_map(fn($col) => str_replace('.', '_', $col), $params['select'] ?? []);
                $exportData = [$headings];
                foreach ($rows as $row) {
                    $rowData = [];
                    foreach ($params['select'] ?? [] as $col) {
                        $rowData[] = $row[$col] ?? null;
                    }
                    $exportData[] = $rowData;
                }
                Excel::store(new class($exportData) implements \Maatwebsite\Excel\Concerns\FromArray {
                    private array $data;
                    public function __construct(array $data)
                    {
                        $this->data = $data;
                    }
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
                        'checksum' => hash_file('sha256', Storage::disk($disk)->path($physicalPath)),
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
            }
        );
    }

    /**
 * Create a file record and log in the database.
 *
 * @param array<string, mixed> $fileData File data
 * @param string $operation Operation type
 * @param array<string, mixed> $filters Filters for the operation
 * @param bool $is_public Public or private file
 * @param string|null $temp_file_path Temporary file path
 * @param callable|null $storeCallback Callback to store the file
 * @param string $successMessage Success message
 * @return array<string, mixed> Response with status, data, and message
 */
protected function createFile(
    array $fileData,
    string $operation,
    array $filters,
    bool $is_public,
    ?string $temp_file_path = null,
    ?callable $storeCallback = null,
    string $successMessage = 'File created successfully'
): array {
    return $this->handleOperation(
        'createFile',
        ['operation' => $operation, 'file_id' => $fileData['file_id']],
        function () use ($fileData, $operation, $filters, $is_public, $temp_file_path, $storeCallback, $successMessage) {
            $disk = $is_public ? self::FILE_DISK_PUBLIC : self::FILE_DISK_PRIVATE;
            if ($storeCallback) {
                $storeCallback();
                $fileData['size_bytes'] = Storage::disk($disk)->size(ltrim($fileData['path'], '\\'));
                $fileData['checksum'] = hash_file('sha256', Storage::disk($disk)->path(ltrim($fileData['path'], '\\')));
            }
            $fileInsert = DataService::insert('central', 'files', [
                'file_id' => $fileData['file_id'],
                'folder_id' => $fileData['folder_id'],
                'business_id' => $fileData['business_id'],
                'name' => $fileData['name'],
                'path' => $fileData['path'],
                'original_name' => $fileData['original_name'],
                'extension' => $fileData['extension'],
                'mime_type' => $fileData['mime_type'],
                'size_bytes' => $fileData['size_bytes'],
                'checksum' => $fileData['checksum'],
                'metadata' => $fileData['metadata'] ?? null,
                'uploaded_by' => $fileData['uploaded_by'],
                'is_public' => $is_public,
            ]);
            if (!$fileInsert['status']) {
                throw new Exception('Failed to insert file record: ' . $fileInsert['message']);
            }
            $fileLogInsert = DataService::insert('central', 'file_logs', [
                'file_id' => $fileData['file_id'],
                'operation' => $operation,
                'filters' => json_encode($filters),
                'temp_file_path' => $temp_file_path ?? $fileData['path'],
                'status' => 'active',
                'is_public' => $is_public,
                'download_limit' => self::DEFAULT_DOWNLOAD_LIMIT,
                'expiration_at' => in_array($operation, ['export', 'report', 'zip', 'errors']) ? now()->addSeconds(self::TEMP_FILE_TTL) : null,
                'created_by' => $fileData['uploaded_by'],
            ]);
            if (!$fileLogInsert['status']) {
                DataService::delete('central', 'files', [['column' => 'file_id', 'operator' => '=', 'value' => $fileData['file_id']]]);
                throw new Exception('Failed to insert file log: ' . $fileLogInsert['message']);
            }
            $this->clearFileCache('central', $fileData['file_id']);
            TemporaryFileCreated::dispatch([
                'file_id' => $fileData['file_id'],
                'file_log_id' => $fileLogInsert['data']['id'],
                'file_path' => $temp_file_path ?? $fileData['path'],
                'is_public' => $is_public,
                'expiration_at' => in_array($operation, ['export', 'report', 'zip', 'errors']) ? now()->addSeconds(self::TEMP_FILE_TTL) : null,
            ]);
            return $this->formatResponse(true, [
                'file_id' => $fileData['file_id'],
                'file_log_id' => $fileLogInsert['data']['id'],
                'file_path' => $temp_file_path ?? $fileData['path'],
                'public_url' => $is_public ? asset('storage/' . ($temp_file_path ?? $fileData['path'])) : null,
            ], $successMessage);
        }
    );
}

    /**
     * Handle operations with unified error handling.
     *
     * @param string $method Method name for logging
     * @param array<string, mixed> $context Logging context
     * @param callable $callback Operation logic
     * @param bool $returnRaw Return raw result instead of formatted response
     * @return mixed Response or raw result
     */
    protected function handleOperation(string $method, array $context, callable $callback, bool $returnRaw = false)
    {
        try {
            $result = $callback();
            return $returnRaw ? $result : $result;
        } catch (InvalidArgumentException $e) {
            Developer::warning("Invalid argument in $method", array_merge($context, ['error' => $e->getMessage()]));
            return $returnRaw ? throw $e : $this->formatResponse(false, [], $e->getMessage());
        } catch (Exception $e) {
            Developer::error("Unexpected error in $method", array_merge($context, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]));
            return $returnRaw ? throw $e : $this->formatResponse(false, [], "Unexpected error in $method");
        }
    }
    /**
     * Validate required fields.
     *
     * @param string|null $key Folder key
     * @param string|null $business_id Business ID
     * @param string $message Error message
     * @return void
     */
    protected function validateRequired(?string $key, ?string $business_id, string $message): void
    {
        if ((empty($key) && $key !== null) || empty($business_id)) {
            throw new InvalidArgumentException($message);
        }
    }
    /**
     * Validate file extension.
     *
     * @param string $extension File extension
     * @return void
     */
    protected function validateExtension(string $extension): void
    {
        $allowedExtensions = config('filesystems.allowed_extensions', []);
        if (!in_array(strtolower($extension), $allowedExtensions)) {
            throw new InvalidArgumentException('Unsupported file extension: ' . $extension);
        }
    }
    /**
     * Get folder ID by key.
     *
     * @param string $key Folder key
     * @return string Folder ID
     */
    protected function getFolderId(string $key): string
    {
        $folder = DataService::fetch('central', 'skeleton_folders', [
            'select' => ['folder_id'],
            'key' => ['operator' => '=', 'value' => $key],
            'deleted_at' => ['operator' => 'IS NULL'],
            'is_approved' => ['operator' => '=', 'value' => 1]
        ]);
        if (!$folder['status'] || empty($folder['data'])) {
            throw new InvalidArgumentException('Invalid or non-existent folder key: ' . $key);
        }
        return $folder['data'][0]['folder_id'];
    }
    /**
     * Check for duplicate file by checksum.
     *
     * @param string $checksum File checksum
     * @param string $folder_id Folder ID
     * @param string $key Folder key
     * @return void
     */
    protected function checkDuplicateFile(string $checksum, string $folder_id, string $key): void
    {
        $existingFile = DataService::fetch('central', 'files', [
            'select' => ['file_id'],
            'checksum' => ['operator' => '=', 'value' => $checksum],
            'folder_id' => ['operator' => '=', 'value' => $folder_id],
            'deleted_at' => ['operator' => 'IS NULL']
        ]);
        if ($existingFile['status'] && !empty($existingFile['data'])) {
            throw new InvalidArgumentException('Duplicate file detected in folder: ' . $key);
        }
    }
    /**
     * Generate a unique filename.
     *
     * @param string $base_filename Base filename
     * @param string $extension File extension
     * @param string $key Folder key
     * @param bool $is_public Public or private file
     * @return string Unique filename
     */
    protected function generateUniqueFilename(string $base_filename, string $extension, string $key, bool $is_public): string
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
    /**
     * Store a file in the storage disk.
     *
     * @param UploadedFile $file The file to store
     * @param string $storedName Stored filename
     * @param string $key Folder key
     * @param bool $is_public Public or private file
     * @return string Stored file path
     */
    protected function storeFile(UploadedFile $file, string $storedName, string $key, bool $is_public): string
    {
        $folderPath = $this->getFolderPath($key);
        $disk = $is_public ? self::FILE_DISK_PUBLIC : self::FILE_DISK_PRIVATE;
        if ($is_public) {
            $this->ensureDirectoryExists(Storage::disk(self::FILE_DISK_PUBLIC_COPY)->path(ltrim($folderPath, '\\')));
            $this->ensureDirectoryExists(Storage::disk(self::FILE_DISK_PUBLIC)->path(ltrim($folderPath, '\\')));
        }
        return Storage::disk($disk)->putFileAs(ltrim($folderPath, '\\'), $file, $storedName);
    }
    /**
     * Get file log by ID.
     *
     * @param int $file_log_id File log ID
     * @return array<string, mixed> File log data
     */
    protected function getFileLog(int $file_log_id): array
    {
        $logResult = DataService::fetch('central', 'file_logs', [
            'select' => ['id', 'file_id', 'operation', 'filters', 'temp_file_path', 'status', 'is_public', 'download_count', 'download_limit', 'created_by'],
            'id' => ['operator' => '=', 'value' => $file_log_id],
            'deleted_at' => ['operator' => 'IS NULL']
        ]);
        if (!$logResult['status'] || empty($logResult['data'])) {
            throw new InvalidArgumentException('File log not found for ID: ' . $file_log_id);
        }
        return $logResult['data'][0];
    }
    /**
     * Validate file access permissions.
     *
     * @param array<string, mixed> $fileLog File log data
     * @return void
     */
    protected function validateFileAccess(array $fileLog): void
    {
        $user = Skeleton::authUser();
        if (!$fileLog['is_public'] && (!$user || ($user->user_id !== $fileLog['created_by']))) {
            throw new InvalidArgumentException('Unauthorized access to private file.');
        }
    }
    /**
     * Get or recreate file data.
     *
     * @param array<string, mixed> $fileLog File log data
     * @return array<string, mixed> File data
     */
    protected function getOrRecreateFile(array $fileLog): array
    {
        $fileResult = DataService::fetch('central', 'files', [
            'select' => ['file_id', 'folder_id', 'business_id', 'name', 'path', 'extension', 'mime_type', 'size_bytes', 'is_public', 'uploaded_by'],
            'file_id' => ['operator' => '=', 'value' => $fileLog['file_id']],
            'deleted_at' => ['operator' => 'IS NULL']
        ]);
        if (!$fileResult['status'] || empty($fileResult['data'])) {
            if (in_array($fileLog['operation'], ['export', 'report', 'zip'])) {
                return $this->recreateFile('central', $fileLog);
            }
            throw new InvalidArgumentException('File not found for ID: ' . $fileLog['file_id']);
        }
        return $fileResult['data'][0];
    }
    /**
     * Increment download count for a file log.
     *
     * @param int $file_log_id File log ID
     * @param int $currentCount Current download count
     * @return void
     */
    protected function incrementDownloadCount(int $file_log_id, int $currentCount): void
    {
        $updateResult = DataService::update('central', 'file_logs', ['download_count' => $currentCount + 1], [['column' => 'id', 'operator' => '=', 'value' => $file_log_id]]);
        if (!$updateResult['status']) {
            throw new Exception('Failed to update download count.');
        }
    }
    /**
     * Check if an existing file log is valid.
     *
     * @param array<string, mixed> $fileLog File log data
     * @return bool True if valid
     */
    protected function isValidExistingFile(array $fileLog): bool
    {
        $fileResult = DataService::fetch('central', 'files', [
            'select' => ['file_id', 'path', 'is_public'],
            'file_id' => ['operator' => '=', 'value' => $fileLog['file_id']],
            'deleted_at' => ['operator' => 'IS NULL']
        ]);
        return $fileResult['status'] && !empty($fileResult['data']) && Storage::disk($fileLog['is_public'] ? self::FILE_DISK_PUBLIC : self::FILE_DISK_PRIVATE)->exists(ltrim($fileLog['temp_file_path'], '\\'));
    }
}