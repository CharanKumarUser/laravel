<?php
namespace App\Http\Controllers\System;
use App\Facades\{Developer, Skeleton};
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;
use Exception;
/**
 * Dispatches panel routes across all modules dynamically.
 */
class SystemRouteController extends Controller
{
    private const BASE_NAMESPACE = 'App\\Http\\Controllers\\System\\';
    private const RATE_LIMIT_ATTEMPTS = 100;
    private const RATE_LIMIT_DECAY_SECONDS = 60;
    /**
     * Dispatches the request to the appropriate controller.
     *
     * @param Request $request
     * @return Response|\Illuminate\Http\JsonResponse|\Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function dispatch(Request $request)
    {
        try {
            // Check authentication
            if (!Auth::check()) {
                Developer::warning('Unauthenticated access attempt.', ['path' => $request->path()]);
                return $this->handleError($request, 'Authentication required.', Response::HTTP_UNAUTHORIZED);
            }
            $user = Skeleton::authUser();
            $userId = $user->user_id ?? null;
            if (!$userId) {
                Developer::warning('Invalid user ID.', ['path' => $request->path()]);
                return $this->handleError($request, 'Invalid user.', Response::HTTP_UNAUTHORIZED);
            }
            $system = ucfirst(Skeleton::getUserSystem() ?? 'business');
            $segments = $request->segments();
            $module = isset($segments[0]) ? $this->toCamelCase($segments[0]) : 'Dashboard';
            $section = isset($segments[1]) ? $this->toCamelCase($segments[1]) : null;
            $item = isset($segments[2]) ? $this->toCamelCase($segments[2]) : null;
            $token = ($segments[0] ?? '') === 'skeleton-action' && isset($segments[1]) ? $segments[1] : '';
            $redirectToken = ($segments[0] ?? '') === 't' && isset($segments[1]) ? $segments : [];
            // Rate limiting
            $rateLimitKey = "dispatch:{$userId}";
            if (RateLimiter::tooManyAttempts($rateLimitKey, self::RATE_LIMIT_ATTEMPTS, self::RATE_LIMIT_DECAY_SECONDS)) {
                Developer::warning('Rate limit exceeded.', ['user_id' => $userId, 'path' => $request->path()]);
                return $this->handleError($request, 'Too many requests.', Response::HTTP_TOO_MANY_REQUESTS);
            }
            RateLimiter::hit($rateLimitKey, self::RATE_LIMIT_DECAY_SECONDS);
            if (!$token && !$redirectToken) {
                $modules = collect(Skeleton::getModules())
                    ->pluck('name')
                    ->map(fn($name) => $this->toCamelCase($name ?: 'Dashboard'))
                    ->toArray();
                if (!in_array($module, $modules)) {
                    Developer::warning('Invalid module.', ['module' => $module, 'system' => $system]);
                    return $this->handleError($request, 'Module not found.', Response::HTTP_NOT_FOUND);
                }
                if (!Skeleton::hasPermission("view:{$module}", $user)) {
                    Developer::warning('Permission denied for module.', ['module' => $module, 'user_id' => $userId]);
                    return $this->handleError($request, 'Permission denied.', Response::HTTP_FORBIDDEN);
                }
                $moduleData = collect(Skeleton::getModules())->firstWhere('name', $module);
                $moduleId = $moduleData['module_id'] ?? null;
                if ($section) {
                    if (!$moduleId) {
                        Developer::warning('Invalid module ID.', ['module' => $module]);
                        return $this->handleError($request, 'Module not found.', Response::HTTP_NOT_FOUND);
                    }
                    $sections = collect(Skeleton::getSections())
                        ->where('module_id', $moduleId)
                        ->pluck('name')
                        ->map(fn($name) => $this->toCamelCase($name ?: ''))
                        ->toArray();
                    if (!in_array($section, $sections)) {
                        Developer::warning('Invalid section.', ['section' => $section, 'module' => $module]);
                        return $this->handleError($request, 'Section not found.', Response::HTTP_NOT_FOUND);
                    }
                    if (!Skeleton::hasPermission("view:{$module}::{$section}", $user)) {
                        Developer::warning('Permission denied for section.', ['section' => $section, 'module' => $module, 'user_id' => $userId]);
                        return $this->handleError($request, 'Permission denied.', Response::HTTP_FORBIDDEN);
                    }
                }
                if ($item) {
                    $sectionData = collect(Skeleton::getSections())
                        ->where('module_id', $moduleId)
                        ->where('name', $section)
                        ->first();
                    $sectionId = $sectionData['section_id'] ?? null;
                    if (!$sectionId) {
                        Developer::warning('Invalid section ID.', ['section' => $section, 'module' => $module]);
                        return $this->handleError($request, 'Section not found.', Response::HTTP_NOT_FOUND);
                    }
                    $items = collect(Skeleton::getItems())
                        ->where('section_id', $sectionId)
                        ->pluck('name')
                        ->map(fn($name) => $this->toCamelCase($name ?: ''))
                        ->toArray();
                    if (!in_array($item, $items)) {
                        Developer::warning('Invalid item.', ['item' => $item, 'section' => $section, 'module' => $module]);
                        return $this->handleError($request, 'Item not found.', Response::HTTP_NOT_FOUND);
                    }
                    if (!Skeleton::hasPermission("view:{$module}::{$section}::{$item}", $user)) {
                        Developer::warning('Permission denied for item.', ['item' => $item, 'section' => $section, 'module' => $module, 'user_id' => $userId]);
                        return $this->handleError($request, 'Permission denied.', Response::HTTP_FORBIDDEN);
                    }
                }
            }
            // Resolve controller and method
            $controllerInfo = $this->resolveController($system, $module, $token, $redirectToken);
            if (!$controllerInfo) {
                return $this->handleError($request, 'Route not found.', Response::HTTP_NOT_FOUND);
            }
            [$controllerClass, $method] = $controllerInfo;
            if (!is_string($method) || !method_exists($controllerClass, $method)) {
                Developer::error('Invalid method for controller.', ['controller' => $controllerClass, 'method' => $method, 'user_id' => $userId]);
                return $this->handleError($request, 'Invalid controller method.', Response::HTTP_NOT_FOUND);
            }
            // Execute the controller method
            return app($controllerClass)->{$method}($request, [
                'module' => $module,
                'section' => $section,
                'item' => $item,
                'redirect' => $redirectToken,
                'token' => $token,
            ]);
        } catch (Exception $e) {
            Developer::error('Error in SystemRouteController.', ['error' => $e->getMessage(), 'path' => $request->path()]);
            return $this->handleError(
                $request,
                config('app.debug') ? $e->getMessage() : 'Internal server error.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
    /**
     * Resolves the controller class and method for the request.
     *
     * @param string $system
     * @param string $module
     * @param string $action
     * @param array $redirectToken
     * @return array|null
     */
    private function resolveController(string $system, string $module, string $token, array $redirectToken): ?array
    {
        $controllerName = 'NavCtrl';
        $methodName = 'index';
        $module = trim(str_replace(' ', '', ucwords($module, '-')));
        // Handle token-based action routes (/skeleton-action/{token})
        if ($token) {
            $config = Skeleton::resolveToken($token);
            if (empty($config) || !isset($config['key'])) {
                Developer::warning('Invalid SkeletonToken config.', ['token' => $token]);
                return null;
            }
            $data = collect(Skeleton::getSkeletonData()['tokens'])->firstWhere('key', $config['key']);
            if (!$data) {
                Developer::warning('Invalid SkeletonToken key.', ['key' => $config['key'], 'token' => $token]);
                return null;
            }
            $module = ucfirst($this->toCamelCase($data['module'] ?? 'Dashboard'));
            $module = trim(str_replace(' ', '', ucwords($module, '-')));
            $actionInfo = $this->buildActionControllerName($token);
            if (empty($actionInfo) || !isset($actionInfo[0], $actionInfo[1], $actionInfo[2])) {
                Developer::warning('Invalid action info.', ['token' => $token]);
                return null;
            }
            $actionType = $actionInfo[0];
            $controllerName = $actionInfo[1];
            $methodName = $actionInfo[2];
            if ($actionType == 's') {
                $controllerClass = "App\\Http\\Helpers\\{$controllerName}";
            } else {
                $controllerClass = in_array($actionType, ['d', 'ds', 'db', 'dbs', 'u'])
                ? self::BASE_NAMESPACE . "Actions\\{$controllerName}"
                : self::BASE_NAMESPACE . "{$system}\\{$module}\\{$controllerName}";
            }
            
            
        } elseif (!empty($redirectToken)) {
            // Non-token routes
            $module = Str::studly($redirectToken[1]);
            $controllerClass = self::BASE_NAMESPACE . "{$system}\\{$module}\\TokenCtrl";
        } else {
            // Non-token routes
            $controllerClass = self::BASE_NAMESPACE . "{$system}\\{$module}\\NavCtrl";
        }
        if (!class_exists($controllerClass) || !method_exists($controllerClass, $methodName)) {
            Developer::warning('Controller or method not found.', ['controller' => $controllerClass, 'method' => $methodName]);
            return null;
        }
        return [$controllerClass, $methodName];
    }
    /**
     * Builds controller name for skeleton actions.
     *
     * @param string $token
     * @return array
     */
    private function buildActionControllerName(string $token): array
    {
        $parts = explode('_', trim($token));
        if (count($parts) < 5) {
            Developer::warning('Invalid token format.', ['token' => $token]);
            return [];
        }
        $actionMap = [
            // Add
            'a'   => ['a', 'ShowAddCtrl', 'index'],
            'as'  => ['as', 'SaveAddCtrl', 'index'],
            // Edit
            'e'   => ['e', 'ShowEditCtrl', 'index'],
            'es'  => ['es', 'SaveEditCtrl', 'index'],
            'eb'  => ['eb', 'ShowEditCtrl', 'bulk'],
            'ebs' => ['ebs', 'SaveEditCtrl', 'bulk'],
            // Delete
            'd'   => ['d', 'Delete', 'single'],
            'ds'  => ['ds', 'Delete', 'delete_single'],
            'db'  => ['db', 'Delete', 'bulk'],
            'dbs' => ['dbs', 'Delete', 'delete_bulk'],
            // Form
            'f'   => ['f', 'FormCtrl', 'index'],
            // Display/View
            'c'   => ['c', 'CardCtrl', 'index'],
            't'   => ['t', 'TableCtrl', 'index'],
            'v'   => ['v', 'ViewCtrl', 'index'],
            // Utility
            's'   => ['s', 'SelectHelper', 'index'],
            'u'   => ['u', 'Unique', 'index'],
        ];
        return $actionMap[trim($parts[4])] ?? [];
    }
    /**
     * Converts dashed string to camel case.
     *
     * @param string $input
     * @return string
     */
    private function toCamelCase(string $input): string
    {
        return trim(str_replace('-', ' ', ucwords($input ?: 'Dashboard', '-')));
    }
    /**
     * Handles errors with developer mode support.
     *
     * @param Request $request
     * @param string $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View|\Illuminate\Http\RedirectResponse
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
        if (!Auth::check()) {
            return redirect()->route('login')->withErrors(['error' => $message]);
        }
        $errorView = "errors.{$statusCode}";
        if (View::exists($errorView)) {
            return response()->view($errorView, ['error' => $message], $statusCode);
        }
        return response()->view('errors.generic', ['error' => $message, 'status' => $statusCode], $statusCode);
    }
    /**
     * Reloads the Skeleton configuration.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reload_skeleton(Request $request)
    {
        try {
            Skeleton::reloadSkeleton();
            return response()->json([
                'status' => true,
                'title' => 'Success',
                'message' => 'Skeleton reloaded successfully',
                'timestamp' => now(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'title' => 'Error',
                'message' => 'Failed to reload skeleton.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error.',
                'timestamp' => now(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}