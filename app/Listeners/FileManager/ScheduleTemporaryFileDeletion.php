<?php

namespace App\Listeners\FileManager;

use App\Events\FileManager\TemporaryFileCreated;
use App\Jobs\FileManager\DeleteTemporaryFileJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class ScheduleTemporaryFileDeletion implements ShouldQueue
{
    public function handle(TemporaryFileCreated $event)
    {
        DeleteTemporaryFileJob::dispatch(
            $event->system,
            $event->fileLogId,
            $event->fileId,
            $event->filePath,
            $event->isPublic
        )->delay($event->expirationAt);
    }
}
