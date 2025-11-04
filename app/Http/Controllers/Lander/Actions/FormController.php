<?php
namespace App\Http\Controllers\Lander\Actions;
use App\Facades\{Data, Developer, Random, Skeleton};
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Validator};
/**
 * Controller for saving new Landing entities.
 */
class FormController extends Controller
{
    /**
     * Saves new Landing entity data based on validated input.
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
            Developer::info($reqSet);
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'lander_landing_requests':
                    // Define form fields for adding a new token
                    switch ($reqSet['id']) {
                        case 'contact':
                            $validator = Validator::make($request->all(), [
                                'name' => 'required|string|max:100',
                                'email' => 'nullable|string|max:100',
                                'phone' => 'nullable|string|max:100',
                                'subject' => 'nullable|string',
                                'message' => 'nullable|string',
                            ]);
                            if ($validator->fails()) {
                                return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                            }
                            $validated = $validator->validated();
                            $validated['request_id'] = Random::unique(4, 'REQ');
                            $validated['page_url'] = $request->fullUrl();
                            $validated['user_agent'] = $request->userAgent();
                            $validated['request_type'] = $reqSet['id'] ?? '';
                            $reloadTable = true;
                            $title = 'We’ve Got Your Message!';
                            $message = 'Thanks for contacting us. Someone from our team will review your inquiry and respond as soon as possible.';
                            break;
                        default:
                            return ResponseHelper::moduleError('Invalid Configuration', 'The configuration key is not supported.');
                    }
                    break;
                default:
                    return ResponseHelper::moduleError('Invalid Configuration', 'The configuration key is not supported.');
            }
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (END) <<<                                    *
             *                                                                                                  *
             ****************************************************************************************************/
            // Add metadata
            if ($byMeta || $timestampMeta) {
                if ($byMeta) {
                    $validated['created_by'] = 'System';
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
                'affected' => $result['status'] ? $result['data']['id'] : '-',
                'title' => $result['status'] ? $title : 'Failed',
                'message' => $result['status'] ? $message : $result['message']
            ]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.', 500);
        }
    }
}
