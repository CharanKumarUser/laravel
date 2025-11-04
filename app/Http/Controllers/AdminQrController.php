<?php
// app/Http/Controllers/AdminQrController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Jobs\SmartPresence\GenerateQrJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

class AdminQrController extends Controller
{
    public function start(Request $request)
    {
        $businessId = 'BIZ000001'; // Can be dynamic if needed
        $companyIds = json_decode($request->input('company_ids', '[]'), true);

        if (!is_array($companyIds) || empty($companyIds)) {
            return response()->json(['status' => 'error', 'message' => 'No company IDs provided'], 400);
        }
 
        $response = [];
        foreach ($companyIds as $companyId) {
            // Clear any existing flags and jobs
            Cache::forget("qr_active_{$businessId}_{$companyId}");
            Cache::forget("qr_job_running_{$businessId}_{$companyId}");
            Cache::forget("last_scan_{$businessId}_{$companyId}");

            // Flush any pending unique jobs for this key
            Queue::clear("default", "generate_qr_job_{$businessId}_{$companyId}"); // Adjust queue name if not 'default'

            // Set new flags
            Cache::put("qr_active_{$businessId}_{$companyId}", true, now()->addMinutes(10));
            Cache::put("last_scan_{$businessId}_{$companyId}", now(), now()->addMinutes(10));

            // Dispatch (unique job will handle duplicates)
            if (!Cache::get("qr_job_running_{$businessId}_{$companyId}")) {
                Cache::put("qr_job_running_{$businessId}_{$companyId}", true, now()->addMinutes(10));
                GenerateQrJob::dispatch($businessId, $companyId)->onQueue('default');
                $response[$companyId] = 'started';
            } else {
                $response[$companyId] = 'already_running';
            }
        }

        return response()->json(['status' => 'started', 'details' => $response]);
    }

    public function stop(Request $request)
    {
        $businessId = 'BIZ000001';
        $companyIds = json_decode($request->input('company_ids', '[]'), true);

        if (!is_array($companyIds) || empty($companyIds)) {
            return response()->json(['status' => 'error', 'message' => 'No company IDs provided'], 400);
        }

        $response = [];
        foreach ($companyIds as $companyId) {
            // Forget cache flags
            Cache::forget("qr_active_{$businessId}_{$companyId}");
            Cache::forget("qr_job_running_{$businessId}_{$companyId}");
            Cache::forget("last_scan_{$businessId}_{$companyId}");

            // Clear any pending jobs in queue using unique ID
            Queue::clear("default", "generate_qr_job_{$businessId}_{$companyId}"); // Adjust queue name

            $response[$companyId] = 'stopped';
        }

        return response()->json(['status' => 'stopped', 'details' => $response]);
    }
}