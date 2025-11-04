<?php
namespace App\Http\Controllers\Lander\Actions;
use App\Facades\{Data, Developer, Random, Skeleton, Helper, FileManager};
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Validator};
/**
 * Controller for saving new landing entities.
 */
class SaveAddController extends Controller
{
    /**
     * Saves landing entity data based on validated input.
     *
     * @param Request $request HTTP request containing form data and token
     * @return JsonResponse JSON response with status, title, and message
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $request->input('save_token');
            if (!$token) {
                return ResponseHelper::moduleError('Token Missing', 'No token was provided.');
            }
            // Resolve token to configuration
            $reqSet = Skeleton::resolveToken($token);
            if (!isset($reqSet['key'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid.');
            }
            // Initialize flags and variables
            $byMeta = $timestampMeta = true;
            $reloadTable = $reloadCard = false;
            $validated = [];
            $title = 'Success';
            $message = 'Record added successfully.';
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
                        case 'demo-request':
                            $validator = Validator::make($request->all(), [
                                'name' => 'required|string|max:100',
                                'email' => 'nullable|string|max:100',
                                'phone' => 'nullable|string|max:100',
                                'company' => 'nullable|string',
                                'employee_count' => 'nullable|string',
                                'message' => 'nullable|string',
                            ]);
                            if ($validator->fails()) {
                                return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                            }
                            $validated = $validator->validated();
                            $validated['data'] = json_encode([
                                'company' => $validated['company'] ?? null,
                                'employee_count' => $validated['employee_count'] ?? null,
                            ]);
                            unset($validated['company'], $validated['employee_count']);
                            $validated['request_id'] = Random::unique(4, 'REQ');
                            $validated['page_url'] = $request->fullUrl();
                            $validated['user_agent'] = $request->userAgent();
                            $validated['request_type'] = $reqSet['id'] ?? '';
                            $reloadTable = true;
                            $title = 'Demo Request Submitted';
                            $message = 'Thank you for reaching out. Our team will review your request and connect with you to schedule the demo.';
                            break;
                        case 'quotation':
                            $validator = Validator::make($request->all(), [
                                'name' => 'required|string|max:100',
                                'email' => 'nullable|string|max:100',
                                'phone' => 'nullable|string|max:100',
                                'company' => 'nullable|string',
                                'employee_count' => 'nullable|string',
                                'message' => 'nullable|string',
                                'plan' => 'nullable|string',
                            ]);
                            if ($validator->fails()) {
                                return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                            }
                            $validated = $validator->validated();
                            $validated['data'] = json_encode([
                                'company' => $validated['company'] ?? null,
                                'employee_count' => $validated['employee_count'] ?? null,
                                'plan' => $validated['plan'] ?? null,
                            ]);
                            unset($validated['company'], $validated['employee_count'], $validated['plan']);
                            $validated['request_id'] = Random::unique(4, 'REQ');
                            $validated['page_url'] = $request->fullUrl();
                            $validated['user_agent'] = $request->userAgent();
                            $validated['request_type'] = $reqSet['id'] ?? '';
                            $reloadTable = true;
                            $title = 'Quotation Request Submitted';
                            $message = 'Thank you for your interest. Our team will review your quotation request and get back to you shortly with the details.';
                            break;

                        case 'reseller':

                            Developer::info($request->all());
                        $validator = Validator::make($request->all(), [
                            'first_name' => 'required|string|max:100',
                            'last_name' => 'nullable|string|max:100',
                            'email' => 'nullable|string|max:100',
                            'phone' => 'nullable|string|max:100',
                            'company_name' => 'nullable|string',

                            'incorporation_date' => 'nullable|string',
                            'entity_type' => 'nullable|string',
                            'business_nature' => 'nullable|string',
                            'vendor_type' => 'nullable|string',
                            'cin' => 'nullable|string',
                            'roc' => 'nullable|string',
                            'msme' => 'nullable|string',
                            'gst' => 'nullable|string',

                            // Address
                            'address_line1' => 'nullable|string',
                            'address_line2' => 'nullable|string',
                            'city' => 'nullable|string',
                            'district' => 'nullable|string',
                            'state' => 'nullable|string',
                            'pincode' => 'nullable|string',
                            'profile_photo' => 'nullable|file|image'

                        ]);

                        if ($validator->fails()) {
                            return ResponseHelper::moduleError('Validation Error', $validator->errors()->first());
                        }

                        $validated = $validator->validated();

                        Developer::info($validated);



                        $fileId = null;
                        if ($request->hasFile('profile_photo')) {

                            Developer::info("helooooooo");
                            $folderKey = 'central_reseller';
                            $fileResult = FileManager::saveFile($request, $folderKey, 'profile_photo', 'Profile', 'CENTRAL', false);

                            Developer::info($fileResult);
                            if ($fileResult['status']) {
                                $fileId = $fileResult['data']['file_id'];
                            }
                        }


                        // Structure everything inside 'data'
                        $validated['data'] = json_encode([
                            'company' => $validated['company_name'] ?? null,
                            'entity_type' => $validated['entity_type'] ?? null,
                            'business_nature' => $validated['business_nature'] ?? null,
                            'vendor_type' => $validated['vendor_type'] ?? null,

                            'address' => [
                                'line1' => $validated['address_line1'] ?? null,
                                'line2' => $validated['address_line2'] ?? null,
                                'city' => $validated['city'] ?? null,
                                'district' => $validated['district'] ?? null,
                                'state' => $validated['state'] ?? null,
                                'pincode' => $validated['pincode'] ?? null,
                            ],

                            'profile' => $fileId,
                        ]);


                       

                        $validated['request_id'] = Random::unique(4, 'REQ');
                        $validated['page_url'] = $request->fullUrl();
                        $validated['user_agent'] = $request->userAgent();
                        $validated['request_type'] = $reqSet['id'] ?? '';
                        $validated['name'] = ($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? '');

                         unset(
                            
                            $validated['first_name'], $validated['last_name'], $validated['company_name'], $validated['incorporation_date'], $validated['entity_type'], $validated['business_nature'], $validated['vendor_type'],
                            $validated['cin'], $validated['roc'], $validated['msme'], $validated['gst'],$validated['profile_photo'],
                            $validated['address_line1'], $validated['address_line2'], $validated['city'], $validated['district'], $validated['state'], $validated['pincode']
                           
                        );

                        $reloadTable = true;
                        $title = 'Reseller Request Submitted';
                        $message = 'Thank you for your interest in becoming a reseller. Our team will review your details and contact you shortly.';
                        break;

                        default:
                            return ResponseHelper::moduleError('Invalid Configuration', 'The configuration key is not supported.');
                    }
                    break;
                // Handle invalid configuration keys
                default:
                    return ResponseHelper::moduleError('Invalid Configuration', 'The configuration key is not supported.');
            }
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (END) <<<                                    *
             *                                                                                                  *
             ****************************************************************************************************/
            // Add metadata if required
            if ($byMeta || $timestampMeta) {
                if ($byMeta) {
                    $validated['created_by'] = 'System';
                }
                if ($timestampMeta) {
                    $validated['created_at'] = $validated['updated_at'] = now();
                }
            }
            // Insert data into the database
            $result = Data::insert('central', $reqSet['table'], $validated, $reqSet['key']);
            // Return response based on creation success
            return response()->json([
                'status' => $result['status'],
                'reload_table' => $reloadTable,
                'reload_card' => $reloadCard,
                'token' => $reqSet['token'],
                'affected' => $result['status'] ? $result['id'] : '-',
                'title' => $result['status'] ? $title : 'Failed',
                'message' => $result['status'] ? $message : $result['message']
            ]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.');
        }
    }
}
