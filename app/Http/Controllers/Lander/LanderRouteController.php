<?php
namespace App\Http\Controllers\Lander;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;
use Exception;

/**
 * Dispatches action routes for non-authenticated users.
 */
class LanderRouteController extends Controller
{
    private const BASE_NAMESPACE = 'App\\Http\\Controllers\\Lander\\Actions\\';
    private const RATE_LIMIT_ATTEMPTS = 50;
    private const RATE_LIMIT_DECAY_SECONDS = 60;

    /**
     * Dispatches the request to the appropriate action controller.
     *
     * @param Request $request
     * @return Response|\Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function dispatch(Request $request)
    {
        try {
            $segments = $request->segments();
            $token = $segments[0] === 'lander-action' && isset($segments[1]) ? $segments[1] : null;

            if (!$token || !is_string($token)) {
                return $this->handleError($request, 'Invalid action token.', Response::HTTP_NOT_FOUND);
            }

            // Rate limiting for unauthenticated users
            $rateLimitKey = "lander-action:{$token}:{$request->ip()}";
            if (RateLimiter::tooManyAttempts($rateLimitKey, self::RATE_LIMIT_ATTEMPTS, self::RATE_LIMIT_DECAY_SECONDS)) {
                return $this->handleError($request, 'Too many requests.', Response::HTTP_TOO_MANY_REQUESTS);
            }
            RateLimiter::hit($rateLimitKey, self::RATE_LIMIT_DECAY_SECONDS);

            // Resolve controller and method
            $controllerInfo = $this->resolveController($request, $token);

            if (!$controllerInfo) {
                return $this->handleError($request, 'Action not found or invalid.', Response::HTTP_NOT_FOUND);
            }

            [$controllerClass, $method] = $controllerInfo;
            if (!is_string($method) || !method_exists($controllerClass, $method)) {
                return $this->handleError($request, 'Invalid action method.', Response::HTTP_NOT_FOUND);
            }

            // Execute the controller method
            return app($controllerClass)->{$method}($request, ['token' => $token]);
        } catch (Exception $e) {
            return $this->handleError(
                $request,
                config('app.debug') ? $e->getMessage() : 'Internal server error.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Resolves the controller class and method for the action token.
     *
     * @param Request $request
     * @param string $token
     * @return array|null
     */
    private function resolveController(Request $request, string $token): ?array
    {
        $actionInfo = $this->buildActionControllerName($token);
        if (empty($actionInfo) || !isset($actionInfo[1], $actionInfo[2])) {
            return null;
        }

        $controllerName = $actionInfo[1];
        $methodName = $actionInfo[2];

        $controllerClass = self::BASE_NAMESPACE . $controllerName;

        if (!class_exists($controllerClass) || !method_exists($controllerClass, $methodName)) {
            return null;
        }

        return [$controllerClass, $methodName];
    }

    /**
     * Builds controller name and method for action tokens.
     *
     * @param string $token
     * @return array
     */
    private function buildActionControllerName(string $token): array
    {
        $parts = explode('_', trim($token));
        if (count($parts) < 5) {
            return [];
        }

        $actionMap = [
            'a'  => ['a', 'ShowAddController', 'index'],
            'as' => ['as', 'SaveAddController', 'index'],
            'f'  => ['f', 'FormController', 'index'],
            'v'  => ['v', 'ViewController', 'index'],
            's'  => ['s', 'SelectController', 'index'],
            'u'  => ['u', 'UniqueController', 'index'],
        ];

        return $actionMap[trim($parts[4])] ?? [];
    }

    /**
     * Handles errors with appropriate response.
     *
     * @param Request $request
     * @param string $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    private function handleError(Request $request, string $message, int $statusCode)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'status' => false,
                'data' => [],
                'message' => $message,
            ], $statusCode);
        }

        $errorView = "errors.{$statusCode}";
        if (View::exists($errorView)) {
            return response()->view($errorView, ['error' => $message], $statusCode);
        }

        return response()->view('errors.generic', ['error' => $message, 'status' => $statusCode], $statusCode);
    }
}