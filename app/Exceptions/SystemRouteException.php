<?php
declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Custom exception for SystemRouteController errors.
 */
class SystemRouteException extends \Exception
{
    /**
     * Additional context for the error.
     *
     * @var array<string, mixed>
     */
    private readonly array $context;

    /**
     * Constructor with message, context, and HTTP status code.
     *
     * @param string $message Error message
     * @param array<string, mixed> $context Additional context
     * @param int $code HTTP status code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message,
        array $context = [],
        int $code = Response::HTTP_INTERNAL_SERVER_ERROR,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Gets the error context.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Renders the exception as an HTTP response.
     *
     * @param Request $request The incoming request
     * @return JsonResponse
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $this->getMessage(),
            'data' => [],
            'context' => $this->context,
        ], $this->getCode());
    }
}