<?php
declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Custom exception for DataService operations.
 *
 * Provides detailed error context for database and service-related failures.
 * @see \App\Services\DataService
 */
class DataServiceException extends Exception
{
    /**
     * Additional context for the exception (e.g., system, table, data).
     *
     * @var array<string, mixed>
     */
    private readonly array $context;

    /**
     * DataServiceException constructor.
     *
     * @param string $message Exception message
     * @param int $code Exception code (default: 0)
     * @param \Throwable|null $previous Previous exception for chaining
     * @param array<string, mixed> $context Additional context data
     */
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;

        // Log the exception with context for debugging
        $this->logException();
    }

    /**
     * Get the exception context.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Log the exception details using the Laravel logger.
     *
     * Uses Redis-backed logging (assuming LOG_CHANNEL=stack with Redis driver).
     */
    private function logException(): void
    {
        Log::error('DataServiceException occurred', [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->context,
            'trace' => $this->getTraceAsString(),
        ]);
    }

    /**
     * Render the exception into a standardized response format.
     *
     * @param string $operation The operation that caused the exception (e.g., 'create', 'update')
     * @return array{status: bool, message: string, data: array<string, mixed>}
     */
    public function render(string $operation): array
    {
        $message = $this->getMessage() ?: "Unexpected error in {$operation} operation";
        return [
            'status' => false,
            'message' => $message,
            'data' => $this->context,
        ];
    }

    /**
     * Render the exception for filter operations.
     *
     * @param int $draw The draw counter for DataTables
     * @return array{status: bool, message: string, draw: int, data: array<string, mixed>, recordsTotal: int, recordsFiltered: int}
     */
    public function renderForFilter(int $draw): array
    {
        $message = $this->getMessage() ?: 'Unexpected error filtering records';
        return [
            'status' => false,
            'message' => $message,
            'draw' => $draw,
            'data' => [],
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
        ];
    }
}