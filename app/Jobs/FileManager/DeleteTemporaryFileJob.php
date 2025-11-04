<?php
namespace App\Jobs\FileManager;

use App\Facades\{Data, Developer};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\{Cache, Storage};

class DeleteTemporaryFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $system;
    protected $fileLogId;
    protected $fileId;
    protected $filePath;
    protected $isPublic;

    public function __construct(string $system, int $fileLogId, string $fileId, string $filePath, bool $isPublic)
    {
        $this->system = $system;
        $this->fileLogId = $fileLogId;
        $this->fileId = $fileId;
        $this->filePath = $filePath;
        $this->isPublic = $isPublic;
    }

    public function handle()
    {
        try {
            $disk = $this->isPublic ? 'files_public' : 'files_private';
            $physicalPath = ltrim($this->filePath, '/');

            // Delete file from storage
            if (Storage::disk($disk)->exists($physicalPath)) {
                Storage::disk($disk)->delete($physicalPath);
            }

            // Update file_logs status
            $updateResult = Data::update($this->system, 'file_logs', ['id' => $this->fileLogId], ['status' => 'deleted']);
            if (!$updateResult['status']) {
                throw new \Exception('Failed to update file log status: ' . $updateResult['message']);
            }

            // Delete file record
            $deleteResult = Data::delete($this->system, 'files', ['file_id' => $this->fileId]);
            if (!$deleteResult['status']) {
                throw new \Exception('Failed to delete file record: ' . $deleteResult['message']);
            }

            // Clear cache
            Cache::forget("file_{$this->system}_{$this->fileId}");
            Cache::forget("folder_paths_{$this->system}");

            Developer::info('Temporary file deleted successfully', [
                'system' => $this->system,
                'file_log_id' => $this->fileLogId,
                'file_id' => $this->fileId,
            ]);
        } catch (\Exception $e) {
            Developer::error('Failed to delete temporary file', [
                'system' => $this->system,
                'file_log_id' => $this->fileLogId,
                'file_id' => $this->fileId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}