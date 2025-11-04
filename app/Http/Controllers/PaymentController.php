<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
/* Exceptions */
use Exception;
/* Classes */
use App\Http\Classes\{
    DeviceTableHelper,
    SwitchingHelper,
    RandomHelper,
    SupremeHelper
};
use App\Models\Device\Device;
/* Models */
use App\Models\User;
use App\Models\Organization\{Organization, MyPayment};
use App\Models\Helper\LicenceKey;
use App\Models\Notify\WpCredit;
class PaymentController extends Controller
{

    public function status(Request $request)
    {
        try {
            // Retrieve data from cache
            $data = SwitchingHelper::getCache($request);
             
            if (!$data) {
                return redirect()->route('home');
            }
            if ($data["status"] == "success") {
                $orgData = Organization::create([
                    "org_id" => RandomHelper::uniqueId('ORG', 5),
                    "name" => $data['company_name'],
                    "org_type" => $data['company_type'],
                    "license_key" => $data['company_license_key'],
                    "email" => $data['company_email'],
                    "phone" => $data['company_phone'],
                    "address_json" => $data['company_address_json'],
                    "org_size" => $data['company_org_size'],
                    "tax_id" => $data['company_gst_no'],
                    "status" => 'active',
                    "plan_start_date" => $data['company_plan_start_date'],
                    "plan_end_date" => $data['company_plan_end_date'],
                    "is_trial" => 'N',
                ]);
            
                $userData = User::create([
                    "gotit_id" => RandomHelper::uniqueId('GT', 7),
                    "org_id" => $orgData->org_id,
                    "first_name" => $data['first_name'],
                    "last_name" => $data['last_name'],
                    "phone" => $data['phone'],
                    "email" => $data['email'],
                    "username" => $data['email'],
                    "password" => $data['password'],
                    "role" => 'admin',
                    "nxt_role" => 'admin',
                    "joined_date" => date('Y-m-d'),
                    "account_status" => 'active',
                    "last_update" => now(),
                ]);
            
                $licData = LicenceKey::create([
                    "lic_id" => RandomHelper::uniqueId('LIC', 12),
                    "org_id" => $orgData->org_id,
                    "status" => 'active',
                ]);
                $orgData->update(['license_key' => $licData->lic_id]);
            
                // Decode device_data JSON and create device entries
                $devices = json_decode($data['device_data'], true);
                if (is_array($devices)) {
                    foreach ($devices as $deviceName => $deviceDetails) {
                        Device::create([
                            "device_id" => RandomHelper::uniqueId('DEV', 7),
                            "org_id" => $orgData->org_id,
                            "name" => $deviceName, // Store device name as key
                            "ip" => $deviceDetails['ip'] ?? null,
                            "port" => $deviceDetails['port'] ?? null,
                            "status" => 'active',
                        ]);
                    }
                }
            
                $mpData = MyPayment::create([
                    "payment_id" => $data['payment_id'],
                    "client_id" => $data['client_id'],
                    "gotit_id" => $userData->gotit_id,
                    "product_id" => $data['product_id'],
                    "org_id" => $orgData->org_id,
                    "payment_date" => $data['transaction_date'],
                    "amount" => $data['amount'],
                    "status" => $data['status'],
                    "plan_id" => $data['plan_id'],
                    "plan_start_date" => $data['plan_start_date'],
                    "valid_upto" => $data['valid_upto'],
                    "auto_renewal" => $data['auto_renewal'],
                ]);
                
                 $mpData = WpCredit::create([
                    "org_id" => $orgData->org_id,
                    "total_credits" => '5000',
                    "used_credits" => '0',
                    "status" => 'active',
                ]);
            
                DeviceTableHelper::createTable('users', $orgData->org_id);
                DeviceTableHelper::createTable('attendance', $orgData->org_id);
                DeviceTableHelper::createTable('commands', $orgData->org_id);
                DeviceTableHelper::createTable('captures', $orgData->org_id);

                
                $recipient = $data['email'];
                

                $values =  [
                    'name' => $data['first_name']. ' ' .$data['last_name'],
                    'plan_name' => $data['plan_name'],
                    'product_name' => $data['product_name'],
                    "plan_name" => $data['plan_name'],
                    'payment_date' => $data['transaction_date'],
                    'invoice_id' => $data['invoice_id'],
                    'transaction_id' => $data['transaction_id'],
                    'amount' => $data['amount'],
                    'username' => $data['email'],
                    'license_key' => $licData->lic_id,
                ];
                $options = [
                    'cc' => '',
                    'bcc' => '',
                    'mail_category' => env('SUPREME_PRODUCT_ID'),
                    'mail_type' => 'Admin Creds',
                    'ref_id' => env('SUPREME_PRODUCT_ID'),
                ];
                $templateId = 'WE-VMTKIZVM';
                $mail1data = [
                    "we_id" => $templateId,
                    "to" => $recipient,
                    "values" => $values,
                    "options" => $options
                ];
                SupremeHelper::send('mail', 'MAL', $mail1data);
                
            }
            

            $data = [
                "status" => $data['status'],
                "amount" => $data['amount'],
                "plan" => $data['plan_name'],
                "reference_id" => $data['reference_id'],
                "transaction_id" => $data['transaction_id'],
                "transaction_date" => $data['transaction_date'],
                 "return_url" => $data['return_url'].'/login',
            ];


           

            return view('landing.payment.status', compact('data'));
            
            
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'title'   => 'Server Error',
                'message' => 'Something went wrong. Please try again later.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

}
