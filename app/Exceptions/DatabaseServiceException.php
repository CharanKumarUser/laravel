<?php
declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Custom exception for DatabaseService-related errors.
 */
class DatabaseServiceException extends Exception
{
    /**
     * Additional context for the exception.
     *
     * @var array<string, mixed>
     */
    protected array $context;

    /**
     * Constructor for DatabaseServiceException.
     *
     * @param string $message Exception message
     * @param array<string, mixed> $context Additional context for debugging
     * @param int $code Exception code (default: 0)
     * @param \Throwable|null $previous Previous exception for chaining
     */
    public function __construct(string $message, array $context = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
        $this->log();
    }

    /**
     * Gets the exception context.
     *
     * @return array<string, mixed> Context data
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Logs the exception using Laravel's logging system.
     *
     * Assumes Log facade is configured with a Redis driver for non-blocking logging.
     *
     * @return void
     */
    protected function log(): void
    {
        try {
            Log::error($this->getMessage(), [
                'exception' => static::class,
                'code' => $this->getCode(),
                'context' => $this->context,
                'trace' => $this->getTraceAsString(),
            ]);
        } catch (\Throwable $e) {
            error_log("Failed to log DatabaseServiceException: {$e->getMessage()}");
        }
    }

    /**
     * Renders the exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request The incoming request
     * @return \Illuminate\Http\JsonResponse HTTP response
     */
    public function render($request): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $this->getMessage(),
            'data' => [],
            'context' => $this->context,
        ], 500);
    }
}