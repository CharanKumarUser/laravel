<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Services\SkeletonService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share $authUser variable with all Blade views using a View Composer
        View::composer('*', function ($view) {
            $authUser = null;

            try {
                // Resolve the SkeletonService from the service container
                $skeletonService = app(SkeletonService::class);

                // Get the authenticated user (null if not logged in)
                $authUser = $skeletonService->authUser();
            } catch (\Throwable $e) {
                // Optionally log the error, or silently fail to avoid breaking views
                // logger()->error('Error retrieving auth user', ['error' => $e->getMessage()]);
            }

            // Pass $authUser to all views
            $view->with('authUser', $authUser);
        });
    }
}
