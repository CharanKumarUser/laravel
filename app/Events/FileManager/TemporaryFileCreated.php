<?php

namespace App\Events\FileManager;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TemporaryFileCreated
{
    use Dispatchable, SerializesModels;

    public $system;
    public $fileLogId;
    public $fileId;
    public $filePath;
    public $isPublic;
    public $expirationAt;

    public function __construct(string $system, int $fileLogId, string $fileId, string $filePath, bool $isPublic, $expirationAt)
    {
        $this->system = $system;
        $this->fileLogId = $fileLogId;
        $this->fileId = $fileId;
        $this->filePath = $filePath;
        $this->isPublic = $isPublic;
        $this->expirationAt = $expirationAt;
    }
}