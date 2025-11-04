<?php
namespace App\Http\Controllers\System\Business\SmartPresence;

use App\Events\Attendance\SmartAttendanceEvent;
use App\Facades\Developer;
use App\Http\Controllers\Controller;
use App\Facades\Skeleton;
use App\Http\Helpers\ResponseHelper;
use App\Facades\BusinessDB;
use App\Facades\Random;
use App\Facades\Data;
use App\Jobs\AttendanceProcess;
use App\Services\AttendanceService;
use App\Services\Data\DataService;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, View};
use Illuminate\Support\Str;

/**
 * Token controller for handling specific SmartPresence module operations.
 */
class TokenCtrl extends Controller
{  
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    } 

    /**
     * Handles custom operations for the SmartPresence module.
     *
     * @param Request $request HTTP request object
     * @param array $params Route parameters
     * @return JsonResponse Response with operation result
     */
    public function index(Request $request, array $params)
    {
        try {
            Developer::alert('helloo qr request',['request data'=>$request->all()]);
            // Extract and validate action
            $action = $params['redirect'][2] ?? null;
            Developer::alert('helloo qr', ['params' => $params['redirect']]);
            $paramStr = $params['redirect'][3] ?? '';

            if (!$action) {
                return response()->view('errors.404', ['status' => false, 'title' => 'Action is Missing', 'message' => 'No action was provided.'], 404);
            }

            $baseView = 'system.business.smart-presence';
            $viewPath = 'errors.404';
            $data = [
                'status' => true,
                'token' => null,
                'message' => 'Token processed successfully.',
            ];

            switch ($action) {
                        case 'qr':
                            parse_str($paramStr, $queryParams);
                            $token = $queryParams['token'] ?? null;
                            $time = $queryParams['time'] ?? null;
                            $save = isset($queryParams['save']) && $queryParams['save'] === '1';
                            if (!$token || !$time) {
                                return ResponseHelper::moduleError('Invalid Parameters', 'Token or time is missing.', 400);
                            }
                            // Convert time to datetime (assuming time is in milliseconds)
                            $timeSeconds = floor($time / 1000);
                            $createdAt = date('Y-m-d H:i:s', $timeSeconds);
                            // Get authenticated company_id and user_id
                            $businessId = Skeleton::authUser()->business_id ?? '123';
                            $userId = Skeleton::authUser()->user_id ?? null;

                            Developer::alert('company id ', ['company_id' => $businessId]);
                            Developer::alert('userId id ', ['userId' => $userId]);

                            // Fetch user from the users table
                            $user = BusinessDB::table('users')
                                ->where('business_id', $businessId)
                                ->where('user_id', $userId)
                                ->whereNull('deleted_at')
                                ->first();

                            Developer::alert('user', ['user' => $user]);

                            // Check if user exists
                            if (!$user) {
                                Developer::alert('error', ['error' => 'User not found']);
                                return ResponseHelper::moduleError('Invalid User', 'User not found for the given business ID and user ID.', 400);
                            }

                            // Log company_id from user
                            Developer::alert('user company_id', ['company_id' => $user->company_id]);

                            // Validate company exists
                            $company = BusinessDB::table('companies')
                                ->where('company_id', $user->company_id)
                                ->whereNull('deleted_at')
                                ->first();

                            if (!$company) {
                                Developer::alert('error', ['error' => 'Company not found']);
                                return ResponseHelper::moduleError('Invalid Company', 'Company ID not found.', 400);
                            }

                            $companyId = $company->company_id;
                            Developer::alert('hiii bosss');
                            Developer::alert('company id ', ['company_id' => $companyId]);

                            if ($save) {
                                // Check if a token record exists for the company
                                $existingToken = BusinessDB::table('smart_qr_tokens')
                                    ->where('company_id', $companyId)
                                    ->where('is_active', 1)
                                    ->first();

                                if (!$existingToken) {
                                    // No record: Insert new
                                    $qrTokenId = Random::uniqueId('TKN', 5, true);
                                    BusinessDB::table('smart_qr_tokens')->insert([
                                        'qr_token_id' => $qrTokenId,
                                        'company_id' => $companyId,
                                        'token' => $token,
                                        'refresh_interval' => 5,
                                        'is_active' => 1,
                                        'scan_time' => $time,
                                        'created_at' => $createdAt,
                                        'updated_at' => $createdAt,
                                    ]);
                                    $data['token'] = $token;
                                    Developer::alert('QR token inserted', [
                                        'qr_token_id' => $qrTokenId,
                                        'token' => $token,
                                        'company_id' => $companyId,
                                        'scan_time' => $createdAt
                                    ]);
                                    return new JsonResponse(['status' => true, 'message' => 'Token saved successfully', 'data' => ['token' => $token]], 200);
                                } else {
                                    // Record exists: Update it
                                    BusinessDB::table('smart_qr_tokens')
                                        ->where('company_id', $companyId)
                                        ->where('is_active', 1)
                                        ->update([
                                            'token' => $token,
                                            'scan_time' => $time,
                                            'updated_at' => $createdAt,
                                        ]);
                                    $data['token'] = $token;
                                    Developer::alert('QR token updated', [
                                        'token' => $token,
                                        'company_id' => $companyId,
                                        'scan_time' => $createdAt
                                    ]);
                                    return new JsonResponse(['status' => true, 'message' => 'Token updated successfully', 'data' => ['token' => $token]], 200);
                                }
                            }

                            // If save=1 is not present, fetch the active token for the company
                            $existingToken = BusinessDB::table('smart_qr_tokens')
                                ->where('company_id', $companyId)
                                ->where('is_active', 1)
                                ->first();

                            if ($existingToken && $existingToken->token === $token) {
                                // Token matched: Insert into smart_attendance without shift_id
                                $attendanceData = [
                                    'attendance_id' => Random::uniqueId('ATT', 6, true),
                                    'user_id' => $userId,
                                    'method' => 'geo-qr',
                                    'punch' => null, // Default to 'in' or adjust based on requirements
                                    'coordinates' => null,
                                    'address' => null,
                                    'distance' => null,
                                    'emotion' => null,
                                    'selfi_path' => null,
                                    'device' => null,
                                    'is_active' => 1,
                                    'created_by' => $userId,
                                    'updated_by' => $userId,
                                    'created_at' => $createdAt,
                                    'updated_at' => $createdAt,
                                ];
                                // 4.1 Basic insert
                                $result = DataService::insert('business','smart_attendance', $attendanceData);
                                // Dispatch AttendanceProcess job with attendance_id as latest_record_id
                                $latest_record_id = $attendanceData['attendance_id'];
                                AttendanceProcess::dispatch($latest_record_id, $businessId,$request->userAgent());
                                Developer::alert('Attendance record inserted', [
                                    'attendance_id' => $attendanceData['attendance_id'],
                                    'user_id' => $userId,
                                    'punch' => $attendanceData['punch'],
                                    'scan_time' => $createdAt,
                                    'result' => $result
                                ]);

                                $data['token'] = $token;
                                Developer::alert('QR token matched, attendance processed', [
                                    'token' => $token,
                                    'company_id' => $companyId
                                ]);
                                return new JsonResponse(['status' => true, 'message' => 'Token matches, attendance processed', 'data' => ['token' => $token]], 200);
                            }

                            // Token not matched or no token exists: Return error
                            Developer::alert('QR token not matched or no token exists', [
                                'token' => $token,
                                'company_id' => $companyId
                            ]);
                            return ResponseHelper::moduleError('Token Not Matched', 'The provided token does not match the existing token or no token exists.', 400);
                        default:
                                return ResponseHelper::moduleError('Invalid Configuration', 'The configuration key is not supported.', 400);
                        }
                        // Render view if it exists
                        if (View::exists($viewPath)) {
                            return view($viewPath, compact('data'));
                        }
                        // Return 404 view if view does not exist
                        return response()->view('errors.404', ['status' => false, 'title' => 'Page Not Found', 'message' => 'The requested page does not exist.'], 404);
                    } catch (Exception $e) {
                        Developer::alert('QR Token Error', ['error' => $e->getMessage()]);
                        return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while processing the request.', 500);
                    }
                }
            }