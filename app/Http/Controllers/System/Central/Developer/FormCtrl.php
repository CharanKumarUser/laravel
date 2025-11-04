<?php
namespace App\Http\Controllers\System\Central\Developer;

use App\Facades\{Data, Random, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Validator};

/**
 * Controller for saving new developer entities.
 */
class FormCtrl extends Controller
{
    /**
     * Saves new developer entity data based on validated input.
     *
     * @param Request $request HTTP request with form data and token
     * @return JsonResponse Success or error message
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $request->input('save_token');
            if (!$token) {
                return ResponseHelper::moduleError('Token Missing', 'No token was provided.', 400);
            }

            // Resolve token to configuration
            $reqSet = Skeleton::resolveToken($token);
            if (!isset($reqSet['key'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid.', 400);
            }

            // Initialize variables
            $byMeta = $timestampMeta = $reloadTable = true;
            $validated = [];
            $title = 'Success';
            $message = 'Data saved successfully.';

            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
             switch ($reqSet['key']) {
                case 'central_general_settings':
                    switch ($request->input('form_type')) {
                        case 'application':
                            $validator = Validator::make($request->all(), [
                                'form_type' => 'required|in:application,database,social,aws,redis,mail,reverb,queue,cache',
                                'app_name' => 'required|string|max:255',
                                'app_env' => 'required|in:local,testing,production',
                                'app_key' => 'required|string|max:255',
                                'app_debug' => 'required|in:true,false',
                                'app_timezone' => 'required|string|timezone:all',
                                'app_url' => 'required|url|max:255',
                                'app_locale' => 'required|string|max:10',
                                'app_fallback_locale' => 'required|string|max:10',
                                'app_faker_locale' => 'required|string|max:10',
                                'app_maintenance_driver' => 'required|in:file,redis',
                                'bcrypt_rounds' => 'required|integer|min:4|max:31',
                                'log_channel' => 'required|string|max:50',
                                'log_stack' => 'required|string|max:50',
                                'log_level' => 'required|in:debug,info,notice,warning,error,critical,alert,emergency',
                            ]);
                            if ($validator->fails()) {
                                return ResponseHelper::moduleError('Validation Failed', $validator->errors()->first(), 422);
                            }
                            $validated = $validator->validated();
                            $title = 'Application settings updated';
                            $message = 'Application settings updated successfully.';
                            break;
                        case 'database':
                            $validator = Validator::make($request->all(), [
                                'form_type' => 'required|in:application,database,social,aws,redis,mail,reverb,queue,cache',
                                'db_connection' => 'required|in:mysql,pgsql,sqlite,sqlsrv',
                                'db_host' => 'required|string|max:255',
                                'db_port' => 'required|integer|min:1|max:65535',
                                'db_database' => 'required|string|max:255',
                                'db_username' => 'required|string|max:255',
                                'db_password' => 'nullable|string|max gioco: 255',
                            ]);
                            if ($validator->fails()) {
                                return ResponseHelper::moduleError('Validation Failed', $validator->errors()->first(), 422);
                            }
                            $validated = $validator->validated();
                            $title = 'Database settings updated';
                            $message = 'Database settings updated successfully.';
                            break;
                        case 'social':
                            $validator = Validator::make($request->all(), [
                                'form_type' => 'required|in:application,database,social,aws,redis,mail,reverb,queue,cache',
                                'google_client_id' => 'nullable|string|max:255',
                                'google_client_secret' => 'nullable|string|max:255',
                                'google_redirect_uri' => 'nullable|url|max:255',
                                'facebook_client_id' => 'nullable|string|max:255',
                                'facebook_client_secret' => 'nullable|string|max:255',
                                'facebook_redirect_uri' => 'nullable|url|max:255',
                                'x_client_id' => 'nullable|string|max:255',
                                'x_client_secret' => 'nullable|string|max:255',
                                'x_redirect_uri' => 'nullable|url|max:255',
                                'github_client_id' => 'nullable|string|max:255',
                                'github_client_secret' => 'nullable|string|max:255',
                                'github_redirect_uri' => 'nullable|url|max:255',
                            ]);
                            if ($validator->fails()) {
                                return ResponseHelper::moduleError('Validation Failed', $validator->errors()->first(), 422);
                            }
                            $validated = $validator->validated();
                            $title = 'Social login settings updated';
                            $message = 'Social login settings updated successfully.';
                            break;
                        case 'aws':
                            $validator = Validator::make($request->all(), [
                                'form_type' => 'required|in:application,database,social,aws,redis,mail,reverb,queue,cache',
                                'aws_access_key_id' => 'nullable|string|max:255',
                                'aws_secret_access_key' => 'nullable|string|max:255',
                                'aws_default_region' => 'required|string|max:50',
                                'aws_bucket' => 'nullable|string|max:255',
                                'aws_use_path_style_endpoint' => 'required|in:true,false',
                            ]);
                            if ($validator->fails()) {
                                return ResponseHelper::moduleError('Validation Failed', $validator->errors()->first(), 422);
                            }
                            $validated = $validator->validated();
                            $title = 'AWS settings updated';
                            $message = 'AWS settings updated successfully.';
                            break;
                        case 'redis':
                            $validator = Validator::make($request->all(), [
                                'form_type' => 'required|in:application,database,social,aws,redis,mail,reverb,queue,cache',
                                'redis_client' => 'required|in:phpredis,predis',
                                'redis_host' => 'required|string|max:255',
                                'redis_password' => 'nullable|string|max:255',
                                'redis_port' => 'required|integer|min:1|max:65535',
                            ]);
                            if ($validator->fails()) {
                                return ResponseHelper::moduleError('Validation Failed', $validator->errors()->first(), 422);
                            }
                            $validated = $validator->validated();
                            $title = 'Redis settings updated';
                            $message = 'Redis settings updated successfully.';
                            break;
                        case 'mail':
                            $validator = Validator::make($request->all(), [
                                'form_type' => 'required|in:application,database,social,aws,redis,mail,reverb,queue,cache',
                                'mail_mailer' => 'required|in:smtp,sendmail,mailgun,ses,postmark,log',
                                'mail_scheme' => 'nullable|in:tls,ssl',
                                'mail_host' => 'nullable|string|max:255',
                                'mail_port' => 'nullable|integer|min:1|max:65535',
                                'mail_username' => 'nullable|string|max:255',
                                'mail_password' => 'nullable|string|max:255',
                                'mail_from_address' => 'required|email|max:255',
                                'mail_from_name' => 'required|string|max:255',
                            ]);
                            if ($validator->fails()) {
                                return ResponseHelper::moduleError('Validation Failed', $validator->errors()->first(), 422);
                            }
                            $validated = $validator->validated();
                            $title = 'Mail settings updated';
                            $message = 'Mail settings updated successfully.';
                            break;
                        case 'reverb':
                            $validator = Validator::make($request->all(), [
                                'form_type' => 'required|in:application,database,social,aws,redis,mail,reverb,queue,cache',
                                'reverb_app_id' => 'required|string|max:50',
                                'reverb_app_key' => 'required|string|max:255',
                                'reverb_app_secret' => 'required|string|max:255',
                                'reverb_host' => 'required|string|max:255',
                                'reverb_port' => 'required|integer|min:1|max:65535',
                                'reverb_scheme' => 'required|in:http,https',
                            ]);
                            if ($validator->fails()) {
                                return ResponseHelper::moduleError('Validation Failed', $validator->errors()->first(), 422);
                            }
                            $validated = $validator->validated();
                            $title = 'Reverb settings updated';
                            $message = 'Reverb settings updated successfully.';
                            break;
                        case 'queue':
                            $validator = Validator::make($request->all(), [
                                'form_type' => 'required|in:application,database,social,aws,redis,mail,reverb,queue,cache',
                                'queue_connection' => 'required|in:sync,database,redis,rabbitmq',
                            ]);
                            if ($validator->fails()) {
                                return ResponseHelper::moduleError('Validation Failed', $validator->errors()->first(), 422);
                            }
                            $validated = $validator->validated();
                            $title = 'Queue settings updated';
                            $message = 'Queue settings updated successfully.';
                            break;
                        case 'cache':
                            $validator = Validator::make($request->all(), [
                                'form_type' => 'required|in:application,database,social,aws,redis,mail,reverb,queue,cache',
                                'cache_store' => 'required|in:array,database,redis,file,memcached',
                            ]);
                            if ($validator->fails()) {
                                return ResponseHelper::moduleError('Validation Failed', $validator->errors()->first(), 422);
                            }
                            $validated = $validator->validated();
                            $title = 'Cache settings updated';
                            $message = 'Cache settings updated successfully.';
                            break;
                        default:
                            return ResponseHelper::moduleError('Invalid Form Type', 'The form type is not supported.', 400);
                    }
                    $store = false;
                    unset($validated['form_type']);
                    $category = $request->input('form_type');
                    $table = $reqSet['table'];
                    $userId = Skeleton::authUser()->user_id ?? null;
                    if (!$userId) {
                        return ResponseHelper::moduleError('Authentication Error', 'No authenticated user found.', 401);
                    }
                    $now = now();
                    // Step 1 — Set all existing records in this category to `inactive`
                    $updateResult = CentralDB::table($table)->where('category', $category)->update(['status' => 'inactive']);
                    if ($updateResult === false) {
                        return ResponseHelper::moduleError(
                            'Failed to update existing records',
                            'Database update failed',
                            500
                        );
                    }
                    // Step 2 — Prepare bulk insert data
                    $insertData = [];
                    foreach ($validated as $key => $value) {
                        $insertData[] = [
                            'category' => $category,
                            'key' => strtoupper($key),
                            'value' => $value ?? '',
                            'status' => 'active',
                            'created_by' => $userId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    // Step 3 — Bulk insert new records
                    if (!empty($insertData)) {
                        $insertSuccess = CentralDB::table($table)->insert($insertData);
                        if ($insertSuccess) {
                            // Dispatch job to update .env file after 10 seconds
                            UpdateEnvFileJob::dispatch($category)->delay(now()->addSeconds(10));
                            $result = [
                                'status' => true,
                                'data' => ['id' => '-'],
                                'message' => $message
                            ];
                        } else {
                            return ResponseHelper::moduleError(
                                'Error',
                                'Failed to insert new settings',
                                500
                            );
                        }
                    } else {
                        $result = [
                            'status' => true,
                            'data' => ['id' => '-'],
                            'message' => $message
                        ];
                    }
                    break;
                default:
                   return ResponseHelper::moduleError('Invalid Configuration', 'The configuration key is not supported.', 400);
            }
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (END) <<<                                    *
             *                                                                                                  *
             ****************************************************************************************************/

            // Add metadata
            if ($byMeta || $timestampMeta) {
                if ($byMeta) {
                    $validated['created_by'] = Skeleton::authUser()->user_id;
                }
                if ($timestampMeta) {
                    $validated['created_at'] = $validated['updated_at'] = now();
                }
            }

            // Insert data
            $result = Data::insert('central', $reqSet['table'], $validated);

            // Generate response
            return response()->json([
                'status' => $result['status'],
                'reload_table' => $reloadTable,
                'token' => $reqSet['token'],
                'affected' => $result['status'] ? $result['id'] : '-',
                'title' => $result['status'] ? $title : 'Failed',
                'message' => $result['status'] ? $message : $result['message']
            ]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.', 500);
        }
    }
}