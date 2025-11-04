<?php
namespace App\Http\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Helper class for generating standardized JSON responses.
 */
class ResponseHelper
{
    /**
     * Generates a standardized error response.
     *
     * @param string $title The title of the error
     * @param string $message The detailed error message
     * @param int $statusCode The HTTP status code (default: 400)
     * @return JsonResponse The formatted error response
     */
    public static function moduleError(string $title, string $message, int $statusCode = 400): JsonResponse
    {
        // Validate HTTP status code
        if ($statusCode < 100 || $statusCode > 599) {
            $statusCode = 400;
        }

        return response()->json([
            'status' => false,
            'title' => $title,
            'message' => $message
        ], $statusCode);
    }

    /**
     * Generates a popup response encouraging the user to explore more.
     *
     * @return JsonResponse
     */
    public static function emptyPopup(): JsonResponse
    {
        return response()->json([
            'token' => uniqid(),
            'type' => 'modal',
            'size' => 'modal-md',
            'position' => 'center',
            'label' => 'Explore More',
            'short_label' => '',
            'content' => '
                <div class="text-center">
                    <img src="' . asset('errors/empty-popup.svg') . '" alt="Explore More" class="img-fluid mb-3" style="max-width:200px;">
                    <h2 class="h4 fw-bold">Discover More Features</h2>
                    <p class="text-muted sf-12">
                        This area is just the beginning! Dive into other sections of the application to unlock more functionality and content tailored for you.
                    </p>
                    <div class="mt-3">
                        <a href="#" class="btn btn-outline-secondary rounded-pill me-2 px-3" data-bs-dismiss="modal">Close</a>
                        <a href="' . url('/dashboard') . '" class="btn btn-primary rounded-pill px-3">Explore Dashboard</a>
                    </div>
                </div>
            ',
            'script' => '',
            'button_class' => '',
            'button' => '',
            'footer' => 'hide',
            'header' => '',
            'validate' => '0',
            'status' => true,
            'title' => 'Keep Exploring',
            'message' => 'Discover more features and sections.'
        ]);
    }
}