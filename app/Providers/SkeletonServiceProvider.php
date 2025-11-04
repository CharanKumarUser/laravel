<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use App\Services\Database\DatabaseService;
use App\Services\SkeletonService;
use Illuminate\Support\Facades\{Auth, Blade, DB};

/**
 * Service provider for SkeletonService and related services, optimized for authenticated users.
 */
class SkeletonServiceProvider extends ServiceProvider
{
    protected $listen = [
        \App\Events\FileManager\TemporaryFileCreated::class => [
            \App\Listeners\FileManager\ScheduleTemporaryFileDeletion::class,
        ],
    ];

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        /**
         * Facade bindings for CentralDB and BusinessDB
         */
        $this->app->singleton('central.db', function ($app) {
            $service = $app->make(DatabaseService::class);
            $conn = $service->getConnection('central');

            return new class($conn) {
                public function __construct(private $conn) {}
                public function __call($method, $args) {
                    return $this->conn->$method(...$args);
                }
            };
        });

        $this->app->singleton('business.db', function ($app) {
            $service = $app->make(DatabaseService::class);
            $conn = $service->getConnection('business');

            return new class($conn) {
                public function __construct(private $conn) {}
                public function __call($method, $args) {
                    return $this->conn->$method(...$args);
                }
            };
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Register @skeletonToken directive
        Blade::directive('skeletonToken', function ($expression) {
            return "<?php echo app(\\App\\Services\\SkeletonService::class)->getTokenForKey({$expression})['data']['token'] ?? ''; ?>";
        });

        // Permission-related Blade directives
        Blade::directive('can', function ($expression) {
            return "<?php
                \$user = auth()->user();
                \$canResult = auth()->check() && app(\\App\\Services\\SkeletonService::class)->hasPermission({$expression});
                if (\$canResult): ?>";
        });

        Blade::directive('endcan', function () {
            return '<?php endif; ?>';
        });

        Blade::directive('has', function ($expression) {
            return "<?php
                \$user = auth()->user();
                \$hasResult = auth()->check() && app(\\App\\Services\\SkeletonService::class)->hasAnyPermission({$expression});
                if (\$hasResult): ?>";
        });

        Blade::directive('endhas', function () {
            return '<?php endif; ?>';
        });
    }
}
