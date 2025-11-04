<?php
namespace App\Http\Controllers\Lander;
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Facades\{CentralDB, Random};
use App\Http\Helpers\{ExceptionHelper, RandomHelper, SupremeHelper, SwitchingHelper};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Hash, Route, Session, Validator, View};
use Illuminate\Support\Str;
use Exception;
class NavigationController extends Controller
{
    /**
     * Renders dashboard-related views based on route parameters.
     *
     * @param Request $request HTTP request object
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $plans = CentralDB::table('business_plans')
                ->where('type', 'fixed')
                ->where('landing_visibility', 1)
                ->where('is_approved', 1)
                ->orderBy('display_order', 'asc')
                ->get();
            $data = [
                'status' => true,
                'plans' => $plans,
            ];
            if (View::exists('welcome')) {
                return view('welcome', compact('data'));
            }
            return response()->view('errors.404', [
                'status' => false,
                'title' => 'Page Not Found',
                'message' => 'The requested page does not exist.'
            ], 404);
        } catch (Exception $e) {
            return response()->view('errors.404', [
                'status' => false,
                'title' => 'Page Not Found',
                'message' => 'An error occurred: ' . (config('skeleton.developer_mode') ? $e->getMessage() : 'Please try again later.')
            ], 404);
        }
    }
    /**
     * Renders dynamic documentation page based on route parameter.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function dyn_doc_page()
    {
        try {
            $docId = explode('.', Route::currentRouteName())[1] ?? null;
            if (!$docId) {
                return $this->errorView('Invalid Route', 'Documentation ID is missing.');
            }
            $documentationData = SupremeHelper::fetch('PDC', ['where' => ['product_id' => env('SUPREME_PRODUCT_ID')]]);
            $docs = $documentationData instanceof \Illuminate\Http\JsonResponse ? $documentationData->getData(true)['data'] : $documentationData['data'];
            $document = collect($docs)->firstWhere('doc_id', $docId);
            if (!$document) {
                return $this->errorView('Documentation Not Found', 'The requested documentation does not exist.');
            }
            return view('landing.help.documentation', compact('document'));
        } catch (Exception $e) {
            return ExceptionHelper::handle($e);
        }
    }
    /**
     * Renders the help page.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function help(Request $request)
    {
        return view('landing.help.home');
    }
    /**
     * Renders dynamic legal page based on route parameter.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function dyn_legal_page()
    {
        try {
            $pageId = explode('.', Route::currentRouteName())[1] ?? null;
            if (!$pageId) {
                return $this->errorView('Invalid Route', 'Legal page ID is missing.');
            }
            $legalData = SupremeHelper::fetch('PPG', [
                'join' => ['product_page_contents' => ['product_pages.page_id', '=', 'product_page_contents.page_id']],
                'where' => ['product_pages.page_id' => $pageId],
                'select' => [
                    'product_pages.title as page_title',
                    'product_pages.description as page_description',
                    'product_page_contents.heading',
                    'product_page_contents.tagline',
                    'product_page_contents.content',
                    'product_page_contents.updated_at',
                ]
            ]);
            if (empty($legalData)) {
                return $this->errorView('Legal Page Not Found', 'The requested legal page does not exist.');
            }
            $title = $legalData[0]->page_title ?? 'Legal Information';
            return view('landing.legal', compact('legalData', 'title'));
        } catch (Exception $e) {
            return ExceptionHelper::handle($e);
        }
    }
    /**
     * Renders the unsubscribe page.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function unsubscribe(Request $request)
    {
        return view('landing.unsubscribe');
    }
    /**
     * Renders the plan selection view.
     *
     * @param Request $request
     * @param string|null $token
     * @param string|null $name
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function plan_view(Request $request, ?string $token = null, ?string $name = null)
    {
        try {
            if ($token && $name) {
                $plan = CentralDB::table('business_plans')
                    ->where('plan_id', $token)
                    ->where('type', 'fixed')
                    ->where('landing_visibility', 1)
                    ->where('is_approved', 1)
                    ->firstOrFail();
                $faqs = CentralDB::table('faqs')
                    ->where('status', 'published')
                    ->where('category', 'plan-query')
                    ->where('sub_category', $plan->plan_id)
                    ->orderBy('updated_at', 'asc')
                    ->get();
                $data = ['status' => true, 'plan' => $plan, 'faqs' => $faqs];
                return view('landing.plans.plan-view', compact('data'));
            } else {
                $plans = CentralDB::table('business_plans')
                    ->where('type', 'fixed')
                    ->where('landing_visibility', 1)
                    ->where('is_approved', 1)
                    ->orderBy('display_order', 'asc')
                    ->get();
                $data = ['status' => true, 'plans' => $plans];
                $onboarding = $this->getOnboardingData($request);
                return view('landing.plans.all-plans', compact('data', 'onboarding'));
            }
        } catch (\Exception $e) {
            return response()->view('errors.404', [
                'status' => false,
                'title' => 'Page Not Found',
                'message' => 'An error occurred: ' . (config('skeleton.developer_mode') ? $e->getMessage() : 'Please try again later.')
            ], 404);
        }
    }
    /**
     * Renders onboarding views based on type.
     *
     * @param Request $request
     * @param string|null $type
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function onboarding(Request $request, $type = null)
    {
        $onboarding = $this->getOnboardingData($request);
        $view = match ($type) {
            'user' => 'landing.onboarding.user',
            'business' => 'landing.onboarding.business',
            'device' => 'landing.onboarding.device',
            'payment' => 'landing.onboarding.payment',
            default => 'landing.onboarding.plan-view',
        };
        if ($type && !view()->exists($view)) {
            return response()->view('errors.404', [
                'status' => false,
                'title' => 'Page Not Found',
                'message' => 'The requested page does not exist.'
            ], 404);
        }
        // Enforce sequential navigation
        if ($type === 'business' && (!$onboarding || !isset($onboarding->admin_email))) {
            return redirect()->route('onboarding.type', ['type' => 'user'])->with('error', 'Please complete user details first.');
        }
        if ($type === 'device' && (!$onboarding || !isset($onboarding->name))) {
            return redirect()->route('onboarding.type', ['type' => 'business'])->with('error', 'Please complete business details first.');
        }
        if ($type === 'payment' && (!$onboarding || !isset($onboarding->name))) {
            return redirect()->route('onboarding.type', ['type' => 'business'])->with('error', 'Please complete business details first.');
        }
        $payLink = '';
        if ($type === 'payment') {
            // Prepare final response array
            $payToken = Random::token(60);
            $payExpiredAt = now()->addMinutes(30)->toDateTimeString();
            $payData = [
                'pay_init_id' => 'PYI' . Random::token(7),
                'product_id' => env('SUPREME_PRODUCT_ID'),
                'company_id' => env('SUPREME_COMPANY_ID'),
                'company_name' => $onboarding->name ?? 'N/A',
                'gst_no' => $onboarding->tax_id ?? 'N/A',
                'phone' => $onboarding->admin_phone ?? 'N/A',
                'email' => $onboarding->admin_email ?? 'N/A',
                'address' => $onboarding->address_line1 . ($onboarding->address_line2 ? ', ' . $onboarding->address_line2 : '') . ', ' . $onboarding->city . ', ' . $onboarding->pincode,
                'display' => $onboarding->name ?? 'N/A',
                'plan_id' => $onboarding->plan_id ?? 'N/A',
                'raw_id' => $onboarding->onboarding_id,
                'raw_data' => json_encode($onboarding),
                'return_to' => 'away',
                'return_url' => env('APP_URL') . '/onboarding/finalize',
                'token' => $payToken,
                'expires_at' => $payExpiredAt,
                'status' => 'initiated',
            ];
            // Send data using SupremeHelper
            $payDataResult = SupremeHelper::send('create', 'PYI', $payData);
            $switchingData = [
                'switch' => 'away',
                'route_name' => 'payment.initiating',
                'token' => $payToken,
                'expires_at' => $payExpiredAt,
                'return_type' => 'link'
            ];
            $payLink = SwitchingHelper::encodeAndSend($switchingData);
        }
        $deviceCode = $type === 'device' ? ($onboarding->device_code ?? 'BIZ' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT)) : null;
        $deviceUrl = $type === 'device' ? ($onboarding->device_url ?? config('app.url') . '/g/onboarding/device') : null;
        return view($view, compact('onboarding', 'deviceCode', 'deviceUrl', 'payLink'));
    }
    /**
     * Handle landing page form submissions (e.g., FAQ and contact forms).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function landing_forms(Request $request)
    {
        try {
            $saveType = $request->input('save_type');
            if (!$saveType) {
                return ResponseHelper::moduleError('Error', 'No save type provided.', 422, ['alert' => true]);
            }
            $validated = [];
            $title = 'Success';
            $message = 'Form submitted successfully.';
            $table = null;
            switch ($saveType) {
                case 'faqs':
                    $validator = Validator::make($request->all(), [
                        'name' => 'required|string|regex:/^[A-Za-z\s]{3,50}$/',
                        'email' => 'required|email|regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/',
                        'message' => 'required|string|min:10|max:500',
                        'category' => 'required|string',
                        'sub_category' => 'required|string|max:150',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first(), 422, ['alert' => true]);
                    }
                    $validated = $validator->validated();
                    $validated['question'] = $validated['message'];
                    $validated['status'] = 'draft';
                    unset($validated['message']);
                    $title = 'Question Submitted';
                    $message = 'Thank you for your question. Our team will review it and respond soon.';
                    $table = 'faqs';
                    break;
                case 'contact':
                    $validator = Validator::make($request->all(), [
                        'name' => 'required|string|regex:/^[A-Za-z\s]{3,50}$/',
                        'phone' => 'required|string|regex:/^[6-9]\d{9}$/',
                        'email' => 'required|email|regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/',
                        'subject' => 'required|string|regex:/^.{3,100}$/',
                        'message' => 'required|string|min:10|max:500',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Error', $validator->errors()->first(), 422, ['alert' => true]);
                    }
                    $validated = $validator->validated();
                    $validated['submission_id'] = 'FSM' . Str::random(17);
                    $validated['submission_type'] = 'contact';
                    $validated['page_url'] = $request->fullUrl();
                    $validated['user_agent'] = $request->userAgent();
                    $title = 'Contact Request Submitted';
                    $message = 'Thank you for reaching out. Our team will get back to you shortly.';
                    $table = 'form_submissions';
                    break;
                default:
                    return ResponseHelper::moduleError('Invalid Save Type', 'The provided save type is not supported.', 422, ['alert' => true]);
            }
            $validated['created_by'] = 'System';
            $validated['updated_by'] = 'System';
            $validated['created_at'] = now();
            $validated['updated_at'] = now();
            $result = CentralDB::table($table)->insert($validated);
            return response()->json([
                'status' => $result,
                'reload_table' => true,
                'token' => $saveType,
                'affected' => $result ? $saveType : '-',
                'title' => $result ? $title : 'Failed',
                'message' => $result ? $message : 'Failed to save form data.',
                'alert' => true,
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::moduleError(
                'Error',
                config('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while processing the form.',
                500,
                ['alert' => true]
            );
        }
    }
    /**
     * Handle onboarding form submissions.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function onboarding_forms(Request $request)
    {
        $saveType = $request->input('save_type');
        $onboardingId = $request->input('onboarding_id', Str::random(30));
        $onboarding = CentralDB::table('business_onboarding')->where('onboarding_id', $onboardingId)->first();
        switch ($saveType) {
            case 'plan':
                return $this->savePlan($request, $onboarding, $onboardingId);
            case 'new':
            case 'existing':
                return $this->saveUser($request, $onboarding, $onboardingId);
            case 'business':
                return $this->saveBusiness($request, $onboarding, $onboardingId);
            case 'device':
                return $this->saveDevice($request, $onboarding, $onboardingId);
            case 'payment':
                return $this->savePayment($request, $onboarding, $onboardingId);
            default:
                return redirect()->back()->with('error', 'Invalid form submission.');
        }
    }
    /**
     * Get or initialize onboarding data from session or database.
     *
     * @param Request $request
     * @return object
     */
    protected function getOnboardingData(Request $request)
    {
        $onboardingId = Session::get('onboarding_id') ?? $request->input('onboarding_id');
        if ($onboardingId) {
            $onboarding = CentralDB::table('business_onboarding')
                ->where('onboarding_id', $onboardingId)
                ->whereIn('status', ['pending', 'approved'])
                ->first();
            if ($onboarding) {
                Session::put('onboarding_id', $onboarding->onboarding_id);
                // Ensure biometricDevices is properly formatted
                if (isset($onboarding->device_info)) {
                    $onboarding->biometricDevices = collect(json_decode($onboarding->device_info, true) ?? [])->map(function ($device) {
                        return (object) $device;
                    });
                } else {
                    $onboarding->biometricDevices = collect([]);
                }
                // Ensure plan is properly formatted
                if (isset($onboarding->plan_id)) {
                    $onboarding->plan = CentralDB::table('business_plans')
                        ->where('plan_id', $onboarding->plan_id)
                        ->first();
                }
                return $onboarding;
            }
        }
        if ($request->query('email') || $request->query('phone')) {
            $onboarding = CentralDB::table('business_onboarding')
                ->where(function ($query) use ($request) {
                    if ($request->query('email')) {
                        $query->where('admin_email', $request->query('email'));
                    }
                    if ($request->query('phone')) {
                        $query->orWhere('admin_phone', $request->query('phone'));
                    }
                })
                ->whereIn('status', ['pending', 'approved'])
                ->orderBy('created_at', 'desc')
                ->first();
            if ($onboarding) {
                Session::put('onboarding_id', $onboarding->onboarding_id);
                // Ensure biometricDevices is properly formatted
                if (isset($onboarding->device_info)) {
                    $onboarding->biometricDevices = collect(json_decode($onboarding->device_info, true) ?? [])->map(function ($device) {
                        return (object) $device;
                    });
                } else {
                    $onboarding->biometricDevices = collect([]);
                }
                // Ensure plan is properly formatted
                if (isset($onboarding->plan_id)) {
                    $onboarding->plan = CentralDB::table('business_plans')
                        ->where('plan_id', $onboarding->plan_id)
                        ->first();
                }
                return $onboarding;
            }
        }
        return (object) [
            'biometricDevices' => collect([]),
            'plan' => null,
        ];
    }
    /**
     * Save plan selection.
     *
     * @param Request $request
     * @param mixed $onboarding
     * @param string $onboardingId
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function savePlan(Request $request, $onboarding, $onboardingId)
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:business_plans,plan_id',
        ]);
        if ($validator->fails()) {
            return redirect()->route('onboarding.type', ['type' => 'plan-view'])->withErrors($validator)->withInput();
        }
        $data = [
            'onboarding_id' => $onboardingId,
            'plan_id' => $request->input('plan_id'),
            'onboarding_stage' => 'plan-selection',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => 'System',
            'updated_by' => 'System',
        ];
        CentralDB::table('business_onboarding')->updateOrInsert(
            ['onboarding_id' => $onboardingId],
            $data
        );
        Session::put('onboarding_id', $onboardingId);
        return redirect()->route('onboarding.type', ['type' => 'user']);
    }
    /**
     * Save user details (new or existing).
     *
     * @param Request $request
     * @param mixed $onboarding
     * @param string $onboardingId
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function saveUser(Request $request, $onboarding, $onboardingId)
    {
        if ($request->input('save_type') === 'new') {
            $validator = Validator::make($request->all(), [
                'admin_first_name' => 'required|string|regex:/^[A-Za-z\s]{2,100}$/',
                'admin_last_name' => 'required|string|regex:/^[A-Za-z\s]{2,100}$/',
                'admin_email' => 'required|email|max:150|unique:business_onboarding,admin_email',
                'admin_phone' => 'required|string|regex:/^\+?[1-9]\d{1,14}$/|max:20|unique:business_onboarding,admin_phone',
                'admin_password' => 'required|string|min:8|confirmed',
            ]);
            if ($validator->fails()) {
                return redirect()->route('onboarding.type', ['type' => 'user'])->withErrors($validator)->withInput();
            }
            $data = [
                'onboarding_id' => $onboardingId,
                'admin_first_name' => $request->input('admin_first_name'),
                'admin_last_name' => $request->input('admin_last_name'),
                'admin_email' => $request->input('admin_email'),
                'admin_phone' => $request->input('admin_phone'),
                'admin_password_hash' => Hash::make($request->input('admin_password')),
                'onboarding_stage' => 'account-creation',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 'System',
                'updated_by' => 'System',
            ];
        } else {
            $validator = Validator::make($request->all(), [
                'admin_email' => 'nullable|email|max:150',
                'admin_phone' => 'nullable|string|regex:/^\+?[1-9]\d{1,14}$/|max:20',
                'admin_password' => 'required|string|min:8',
            ]);
            if (!$request->input('admin_email') && !$request->input('admin_phone')) {
                $validator->errors()->add('admin_email', 'Please provide either an email or phone number.');
                return redirect()->route('onboarding.type', ['type' => 'user'])->withErrors($validator)->withInput();
            }
            if ($validator->fails()) {
                return redirect()->route('onboarding.type', ['type' => 'user'])->withErrors($validator)->withInput();
            }
            $existing = CentralDB::table('business_onboarding')
                ->where(function ($query) use ($request) {
                    if ($request->input('admin_email')) {
                        $query->where('admin_email', $request->input('admin_email'));
                    }
                    if ($request->input('admin_phone')) {
                        $query->orWhere('admin_phone', $request->input('admin_phone'));
                    }
                })
                ->whereIn('status', ['pending', 'approved'])
                ->first();
            if ($existing && !Hash::check($request->input('admin_password'), $existing->admin_password_hash)) {
                return redirect()->route('onboarding.type', ['type' => 'user'])->with('error', 'Invalid credentials.')->withInput();
            }
            if ($existing) {
                $onboarding = $existing;
                $onboardingId = $existing->onboarding_id;
            }
            $data = [
                'onboarding_id' => $onboardingId,
                'admin_email' => $request->input('admin_email'),
                'admin_phone' => $request->input('admin_phone'),
                'admin_password_hash' => Hash::make($request->input('admin_password')),
                'onboarding_stage' => 'account-creation',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 'System',
                'updated_by' => 'System',
            ];
        }
        CentralDB::table('business_onboarding')->updateOrInsert(
            ['onboarding_id' => $onboardingId],
            $data
        );
        Session::put('onboarding_id', $onboardingId);
        return redirect()->route('onboarding.type', ['type' => 'business']);
    }
    /**
     * Save business details.
     *
     * @param Request $request
     * @param mixed $onboarding
     * @param string $onboardingId
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function saveBusiness(Request $request, $onboarding, $onboardingId)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|regex:/^[A-Za-z0-9\s]{3,100}$/',
            'legal_name' => 'nullable|string|regex:/^[A-Za-z0-9\s]{3,100}$/',
            'industry' => 'required|string|regex:/^[A-Za-z\s]{3,50}$/',
            'business_size' => 'required|in:micro,small,medium,large',
            'no_of_employees' => 'required|integer|min:0',
            'registration_no' => 'nullable|string|regex:/^[A-Za-z0-9\-]{3,50}$/',
            'tax_id' => 'nullable|string|regex:/^[A-Za-z0-9\-]{3,50}$/',
            'website' => 'nullable|url|regex:/^https?:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,}$/',
            'address_line1' => 'required|string|regex:/^[A-Za-z0-9\s,.\-]{5,255}$/',
            'address_line2' => 'nullable|string|regex:/^[A-Za-z0-9\s,.\-]{0,255}$/',
            'city' => 'required|string|regex:/^[A-Za-z\s]{2,100}$/',
            'pincode' => 'required|string|regex:/^\d{5,10}$/',
            'state' => 'nullable|string|regex:/^[A-Za-z\s]{2,100}$/',
            'country' => 'required|string|regex:/^[A-Za-z\s]{2,100}$/',
            'email' => 'required|email|regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/',
            'phone' => 'nullable|string|regex:/^\+?[1-9]\d{1,14}$/',
            'hr_contact_email' => 'nullable|email|regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/',
            'hr_contact_phone' => 'nullable|string|regex:/^\+?[1-9]\d{1,14}$/',
        ]);
        if ($validator->fails()) {
            return redirect()->route('onboarding.type', ['type' => 'business'])->withErrors($validator)->withInput();
        }
        $data = array_merge([
            'onboarding_id' => $onboardingId,
            'onboarding_stage' => 'business-info',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => 'System',
            'updated_by' => 'System',
        ], $request->only([
            'name',
            'legal_name',
            'industry',
            'business_size',
            'no_of_employees',
            'registration_no',
            'tax_id',
            'website',
            'address_line1',
            'address_line2',
            'city',
            'pincode',
            'state',
            'country',
            'email',
            'phone',
            'hr_contact_email',
            'hr_contact_phone'
        ]));
        CentralDB::table('business_onboarding')->updateOrInsert(
            ['onboarding_id' => $onboardingId],
            $data
        );
        Session::put('onboarding_id', $onboardingId);
        return redirect()->route('onboarding.type', ['type' => 'device']);
    }
    /**
     * Save device setup details.
     *
     * @param Request $request
     * @param mixed $onboarding
     * @param string $onboardingId
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function saveDevice(Request $request, $onboarding, $onboardingId)
    {
        $validator = Validator::make($request->all(), [
            'device_count' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return redirect()->route('onboarding.type', ['type' => 'device'])->withErrors($validator)->withInput();
        }
        $deviceCode = 'BIZ' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $data = [
            'onboarding_id' => $onboardingId,
            'device_code' => $deviceCode,
            'device_count' => $request->input('device_count') ?? 1,
            'onboarding_stage' => 'device-info',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => 'System',
            'updated_by' => 'System',
        ];
        CentralDB::table('business_onboarding')->updateOrInsert(
            ['onboarding_id' => $onboardingId],
            $data
        );
        Session::put('onboarding_id', $onboardingId);
        return redirect()->route('onboarding.type', ['type' => 'device']);
    }
    public function onboarded_devices(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'onboarding_id' => 'required|string',
            ]);
            $onboarding_id = $request->input('onboarding_id');
            // Find the onboarding record
            $onboarding = CentralDB::table('business_onboarding')->where('onboarding_id', $onboarding_id)->first();
            if (!$onboarding) {
                return response()->json(['error' => 'Onboarding ID not found'], 404);
            }
            // Fetch devices from device_info JSON column
            $devices = json_decode($onboarding->device_info ?? '{}', true);
            // Transform devices into an array if it's an object keyed by SerialNumber
            if (is_array($devices) && !array_is_list($devices)) {
                $devices = array_values($devices); // Convert object to array of devices
            } else if (!is_array($devices)) {
                $devices = []; // Ensure devices is an array if JSON is invalid
            }
            // Ensure each device has required fields
            $devices = array_map(function ($device) {
                return [
                    'ID' => $device['ID'] ?? 'N/A',
                    'DeviceName' => $device['DeviceName'] ?? 'N/A',
                    'SerialNumber' => $device['SerialNumber'] ?? $device['slno'] ?? 'N/A',
                    'slno' => $device['SerialNumber'] ?? $device['slno'] ?? 'N/A',
                    'FWVersion' => $device['FWVersion'] ?? 'N/A',
                    'IPAddress' => $device['IPAddress'] ?? 'N/A',
                    'MAC' => $device['MAC'] ?? 'N/A',
                    'timestamp' => $device['timestamp'] ?? 'N/A',
                    'Return' => $device['Return'] ?? '0',
                    'CMD' => $device['CMD'] ?? 'INFO',
                    // Include other fields as needed
                ];
            }, $devices);
            $device_count = $onboarding->device_count ?? 0;
            return response()->json([
                'devices' => $devices,
                'device_count' => $device_count
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch devices'], 500);
        }
    }
    /**
     * Save summary and initiate payment.
     *
     * @param Request $request
     * @param mixed $onboarding
     * @param string $onboardingId
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function savePayment(Request $request, $onboarding, $onboardingId)
    {
        if (!$onboarding || !isset($onboarding->name)) {
            return redirect()->route('onboarding.type', ['type' => 'business'])->with('error', 'Please complete business details first.');
        }
        $data = [
            'onboarding_id' => $onboardingId,
            'onboarding_stage' => 'payment-initiated',
            'payment_status' => 'initiated',
            'status' => 'pending',
            'updated_at' => now(),
            'updated_by' => 'System',
        ];
        CentralDB::table('business_onboarding')->updateOrInsert(
            ['onboarding_id' => $onboardingId],
            $data
        );
        Session::put('onboarding_id', $onboardingId);
        // Placeholder for payment gateway integration
        return redirect()->route('onboarding.type', ['type' => 'payment'])->with('success', 'Payment initiated. Please complete the payment process.');
    }
}
