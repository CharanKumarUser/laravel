<?php
namespace App\Jobs\Settings;
use App\Facades\{CentralDB, Developer};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\{Artisan, Config};
use Exception;
class UpdateEnvFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**
     * The category of settings to update in the .env file.
     *
     * @var string
     */
    protected $category;
    /**
     * Mapping of central_settings category.key to .env keys.
     *
     * @var array
     */
    protected $envKeyMap = [
        'application.app_name' => 'APP_NAME',
        'application.app_env' => 'APP_ENV',
        'application.app_key' => 'APP_KEY',
        'application.app_debug' => 'APP_DEBUG',
        'application.app_timezone' => 'APP_TIMEZONE',
        'application.app_url' => 'APP_URL',
        'localization.app_locale' => 'APP_LOCALE',
        'localization.app_fallback_locale' => 'APP_FALLBACK_LOCALE',
        'localization.app_faker_locale' => 'APP_FAKER_LOCALE',
        'maintenance.app_maintenance_driver' => 'APP_MAINTENANCE_DRIVER',
        'security.bcrypt_rounds' => 'BCRYPT_ROUNDS',
        'logging.log_channel' => 'LOG_CHANNEL',
        'logging.log_stack' => 'LOG_STACK',
        'logging.log_level' => 'LOG_LEVEL',
        'database.db_connection' => 'DB_CONNECTION',
        'database.db_host' => 'DB_HOST',
        'database.db_port' => 'DB_PORT',
        'database.db_database' => 'DB_DATABASE',
        'database.db_username' => 'DB_USERNAME',
        'database.db_password' => 'DB_PASSWORD',
        'session.session_driver' => 'SESSION_DRIVER',
        'session.session_lifetime' => 'SESSION_LIFETIME',
        'session.session_encrypt' => 'SESSION_ENCRYPT',
        'session.session_path' => 'SESSION_PATH',
        'mail.mail_mailer' => 'MAIL_MAILER',
        'mail.mail_scheme' => 'MAIL_SCHEME',
        'mail.mail_host' => 'MAIL_HOST',
        'mail.mail_port' => 'MAIL_PORT',
        'mail.mail_username' => 'MAIL_USERNAME',
        'mail.mail_password' => 'MAIL_PASSWORD',
        'mail.mail_from_address' => 'MAIL_FROM_ADDRESS',
        'mail.mail_from_name' => 'MAIL_FROM_NAME',
        'aws.aws_access_key_id' => 'AWS_ACCESS_KEY_ID',
        'aws.aws_secret_access_key' => 'AWS_SECRET_ACCESS_KEY',
        'aws.aws_default_region' => 'AWS_DEFAULT_REGION',
        'aws.aws_bucket' => 'AWS_BUCKET',
        'aws.aws_use_path_style_endpoint' => 'AWS_USE_PATH_STYLE_ENDPOINT',
        'reverb.reverb_app_id' => 'REVERB_APP_ID',
        'reverb.reverb_app_key' => 'REVERB_APP_KEY',
        'reverb.reverb_app_secret' => 'REVERB_APP_SECRET',
        'reverb.reverb_host' => 'REVERB_HOST',
        'reverb.reverb_port' => 'REVERB_PORT',
        'reverb.reverb_scheme' => 'REVERB_SCHEME',
        'skeleton.skeleton_developer_mode' => 'SKELETON_DEVELOPER_MODE',
        'skeleton.skeleton_developer_logs' => 'SKELETON_DEVELOPER_LOGS',
        'skeleton.skeleton_allowed_systems' => 'SKELETON_ALLOWED_SYSTEMS',
        'skeleton.skeleton_cache_ttl' => 'SKELETON_CACHE_TTL',
        'skeleton.skeleton_token_length' => 'SKELETON_TOKEN_LENGTH',
        'skeleton.skeleton_token_reload' => 'SKELETON_TOKEN_RELOAD',
        'skeleton.skeleton_max_token_attempts' => 'SKELETON_MAX_TOKEN_ATTEMPTS',
        'skeleton.skeleton_session_db_key' => 'SKELETON_SESSION_DB_KEY',
        'skeleton.skeleton_encryption_cipher' => 'SKELETON_ENCRYPTION_CIPHER',
        'skeleton.skeleton_encryption_queue' => 'SKELETON_ENCRYPTION_QUEUE',
        'skeleton.skeleton_password_expiry_days' => 'SKELETON_PASSWORD_EXPIRY_DAYS',
        'skeleton.skeleton_max_logins' => 'SKELETON_MAX_LOGINS',
        'adms.adms_batch_size' => 'ADMS_BATCH_SIZE',
        'adms.adms_cache_driver' => 'ADMS_CACHE_DRIVER',
        'adms.adms_cache_prefix' => 'ADMS_CACHE_PREFIX',
        'adms.adms_device_ttl' => 'ADMS_DEVICE_TTL',
        'adms.adms_settings_ttl' => 'ADMS_SETTINGS_TTL',
        'adms.adms_commands_ttl' => 'ADMS_COMMANDS_TTL',
        'adms.adms_request_ttl' => 'ADMS_REQUEST_TTL',
        'adms.adms_queue_driver' => 'ADMS_QUEUE_DRIVER',
        'adms.adms_queue_connection' => 'ADMS_QUEUE_CONNECTION',
        'adms.adms_queue_prefix' => 'ADMS_QUEUE_PREFIX',
        'adms.adms_queue_retry_after' => 'ADMS_QUEUE_RETRY_AFTER',
        'adms.adms_max_retries' => 'ADMS_MAX_RETRIES',
        'adms.adms_retry_delay_ms' => 'ADMS_RETRY_DELAY_MS',
        'adms.adms_backoff_factor' => 'ADMS_BACKOFF_FACTOR',
        'adms.adms_central_db_connection' => 'ADMS_CENTRAL_DB_CONNECTION',
        'adms.adms_log_level' => 'ADMS_LOG_LEVEL',
        'adms.adms_log_channel' => 'ADMS_LOG_CHANNEL',
        'social.google_client_id' => 'GOOGLE_CLIENT_ID',
        'social.google_client_secret' => 'GOOGLE_CLIENT_SECRET',
        'social.google_redirect_uri' => 'GOOGLE_REDIRECT_URI',
        'social.facebook_client_id' => 'FACEBOOK_CLIENT_ID',
        'social.facebook_client_secret' => 'FACEBOOK_CLIENT_SECRET',
        'social.facebook_redirect_uri' => 'FACEBOOK_REDIRECT_URI',
        'social.x_client_id' => 'X_CLIENT_ID',
        'social.x_client_secret' => 'X_CLIENT_SECRET',
        'social.x_redirect_uri' => 'X_REDIRECT_URI',
        'social.github_client_id' => 'GITHUB_CLIENT_ID',
        'social.github_client_secret' => 'GITHUB_CLIENT_SECRET',
        'social.github_redirect_uri' => 'GITHUB_REDIRECT_URI',
    ];
    /**
     * Create a new job instance.
     *
     * @param string $category
     * @return void
     */
    public function __construct(string $category)
    {
        $this->category = $category;
    }
    /**
     * Execute the job to update the .env file with settings from the specified category.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Fetch all active settings for the specified category using CentralDB facade
            $settings = CentralDB::table('central_settings')
                ->select('category', 'key', 'value')
                ->where('category', $this->category)
                ->where('status', 'active')
                ->get();
            if ($settings->isEmpty()) {
                Developer::warning("No active settings found for category: {$this->category}");
                return;
            }
            $envPath = base_path('.env');
            if (!file_exists($envPath)) {
                throw new Exception('.env file not found at: ' . $envPath);
            }
            // Read the current .env file
            $envContent = file_get_contents($envPath);
            $envLines = explode("\n", $envContent);
            $newEnvLines = [];
            // Track which .env keys are updated to avoid duplicates
            $updatedKeys = [];
            // Process existing .env lines, updating mapped keys
            foreach ($envLines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '=') === false) {
                    $newEnvLines[] = $line;
                    continue;
                }
                list($key, $value) = array_pad(explode('=', $line, 2), 2, '');
                $key = trim($key);
                // Find the setting that corresponds to this .env key
                $matchingSetting = null;
                foreach ($settings as $setting) {
                    $envKey = $this->envKeyMap[strtolower($setting->category . '.' . $setting->key)] ?? null;
                    if ($envKey === $key) {
                        $matchingSetting = $setting;
                        break;
                    }
                }
                if ($matchingSetting) {
                    // Update the .env key with the database value
                    $value = $matchingSetting->value ?? '';
                    if (preg_match('/[\s"\'=]/', $value)) {
                        $value = '"' . str_replace('"', '\\"', $value) . '"';
                    }
                    $newEnvLines[] = "{$key}={$value}";
                    $updatedKeys[] = $key;
                } else {
                    // Keep non-mapped or unchanged lines
                    $newEnvLines[] = $line;
                }
            }
            // Append any new settings not already in .env
            foreach ($settings as $setting) {
                $envKey = $this->envKeyMap[strtolower($setting->category . '.' . $setting->key)] ?? null;
                if ($envKey && !in_array($envKey, $updatedKeys)) {
                    $value = $setting->value ?? '';
                    if (preg_match('/[\s"\'=]/', $value)) {
                        $value = '"' . str_replace('"', '\\"', $value) . '"';
                    }
                    $newEnvLines[] = "{$envKey}={$value}";
                    $updatedKeys[] = $envKey;
                }
            }
            // Write updated content back to .env
            $newEnvContent = implode("\n", $newEnvLines);
            file_put_contents($envPath, $newEnvContent);
            // Clear configuration cache to apply changes
            Artisan::call('config:clear');
            Developer::notice("Updated .env file for category: {$this->category}", [
                'updated_keys' => $updatedKeys,
            ]);
        } catch (Exception $e) {
            // Log error with detailed message in developer mode
            $errorMessage = Config::get('skeleton.developer_mode')
                ? "Failed to update .env file for category {$this->category}: {$e->getMessage()}"
                : "Failed to update .env file for category {$this->category}";
            Developer::error($errorMessage);
        }
    }
}
