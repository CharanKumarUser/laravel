<?php

namespace App\Http\Controllers\System\Business\DeviceManagement;

use App\Facades\{Data, Developer, Random, Scope, Skeleton, Select};
use App\Http\Controllers\Controller;
use App\Http\Helpers\{PopupHelper, ResponseHelper};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Config;

/**
 * Controller for rendering the add form for DeviceManagement entities.
 */
class ShowAddCtrl extends Controller
{
    /**
     * Renders a popup form for adding new DeviceManagement entities.
     *
     * @param Request $request HTTP request object
     * @param array $params Route parameters with token
     * @return JsonResponse Form configuration or error message
     */
    public function index(Request $request, array $params): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $params['token'] ?? $request->input('skeleton_token');
            if (!$token) {
                return ResponseHelper::moduleError('Token Missing', 'No token was provided.', 400);
            }
            // Resolve token to configuration
            $reqSet = Skeleton::resolveToken($token);
            if (!isset($reqSet['key'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid.', 400);
            }
            // Initialize popup configuration and system options
            $popup = [];
            $holdPopup = false;
            $system = ['central' => 'Central', 'business' => 'Business'];
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'business_devices':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'select', 'name' => 'company_id', 'label' => 'Business', 'required' => true, 'options' => Select::options('companies', 'array', ['company_id' => 'name'], ['where' => ['is_active' => 1]]), 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'text', 'name' => 'name', 'label' => 'Device Name', 'required' => true, 'col' => '6', 'attr' => ['maxlength' => '100']],
                            ['type' => 'text', 'name' => 'serial_number', 'label' => 'Serial Number', 'required' => true, 'col' => '6', 'attr' => ['maxlength' => '100']],
                            ['type' => 'text', 'name' => 'location', 'label' => 'Location', 'required' => false, 'col' => '6', 'attr' => ['maxlength' => '100']],
                            ['type' => 'text', 'name' => 'ip', 'label' => 'IP Address', 'required' => false, 'col' => '6', 'attr' => ['maxlength' => '45', 'inputmode' => 'numeric', 'placeholder' => 'e.g. 192.168.0.10', 'pattern' => '^((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(\.|$)){4}$']],
                            ['type' => 'number', 'name' => 'port', 'label' => 'Port', 'required' => false, 'col' => '6', 'attr' => ['min' => '1', 'max' => '65535']],
                            ['type' => 'text', 'name' => 'mac_address', 'label' => 'MAC Address', 'required' => false, 'col' => '6', 'attr' => ['maxlength' => '17', 'data-validate' => 'mac']],
                            ['type' => 'select', 'name' => 'is_active', 'label' => 'Status', 'options' => ['1' => 'Active', '0' => 'Inactive'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-microchip me-1"></i> Add Device',
                        'short_label' => 'Register new device with network configuration',
                        'button' => 'Save Device',
                        'script' => "window.general.select();window.general.unique();"
                    ];
                break;
                case 'business_device_users':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'select', 'name' => 'device_id', 'label' => 'Device', 'options' => Select::options('devices', 'array', ['device_id' => 'name']), 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'number', 'name' => 'user_id', 'label' => 'User ID', 'required' => true, 'col' => '6', 'attr' => ['maxlength' => '100']],
                            ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'required' => true, 'col' => '6', 'attr' => ['maxlength' => '100']],
                            ['type' => 'password', 'name' => 'password', 'label' => 'PIN', 'required' => true, 'col' => '6', 'attr' => ['maxlength' => '50', 'autocomplete' => 'new-password', 'placeholder' => 'Enter a secure password']],
                            ['type' => 'number', 'name' => 'privilege', 'label' => 'Privilege', 'required' => false, 'col' => '4', 'attr' => ['min' => '0', 'placeholder' => '0']],
                            ['type' => 'text', 'name' => 'card_number', 'label' => 'Card Number', 'required' => false, 'col' => '4', 'attr' => ['maxlength' => '50']],
                            ['type' => 'number', 'name' => 'group_id', 'label' => 'Group ID', 'required' => false, 'col' => '4', 'attr' => ['min' => '1', 'value' => '1']],
                            ['type' => 'text', 'name' => 'time_zone', 'label' => 'Time Zone', 'required' => false, 'col' => '6', 'attr' => ['maxlength' => '16']],
                            ['type' => 'select', 'name' => 'expires', 'label' => 'Expires', 'options' =>['1'=>'Yes', '0'=>'No'], 'required' => false, 'col' => '6', 'attr' => ['data-select'=>'dropdown', 'maxlength' => '11', 'value' => '0']],
                            ['type' => 'datetime-local', 'name' => 'start_datetime', 'label' => 'Start Date & Time', 'required' => false, 'col' => '6'],
                            ['type' => 'datetime-local', 'name' => 'end_datetime', 'label' => 'End Date & Time', 'required' => false, 'col' => '6'],
                            ['type' => 'number', 'name' => 'valid_count', 'label' => 'Valid Count', 'required' => false, 'col' => '12', 'attr' => ['min' => '0', 'value' => '0']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-user me-1"></i> Add Device User',
                        'short_label' => 'Register new device user with access privileges',
                        'button' => 'Save Device User',
                        'script' => 'window.general.select();'
                    ];
                    break;
                case 'business_load_device_users':
                        $popup = [
                    'form' => 'custom',
                    'labelType' => 'floating',
                    'content' => '<input type="hidden" name="save_token" value="'.$token.'">
                        <input type="hidden" name="device_id" value="'. $request->id.'">
                        <div>
                            <h4 class="text-center">Are You sure u want to load users from device?</h4>
                        </div>
                        ',
                    'type' => 'modal',
                    'size' => 'modal-md',
                    'position' => 'end',
                    'label' => '<i class="fa-regular fa-user me-1"></i> Load Users from Device',
                    'short_label' => 'Load users from Device',
                    'button' => 'Load Users',
                    'script' => 'window.general.select();window.presence.smartFace();'
                ];
                break;
                default:
                    return ResponseHelper::emptyPopup();
            }
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (END) <<<                                    *
             *                                                                                                  *
             ****************************************************************************************************/
            // Generate content based on form type
            $content = $popup['form'] === 'builder' ? PopupHelper::generateBuildForm($token, $popup['fields'], $popup['labelType']) : $popup['content'];
            // Generate response
            return response()->json(['token' => $token, 'type' => $popup['type'], 'size' => $popup['size'], 'position' => $popup['position'], 'label' => $popup['label'], 'short_label' => $popup['short_label'], 'content' => $content, 'script' => $popup['script'], 'button_class' => $popup['button_class'] ?? '', 'button' => $popup['button'] ?? '', 'footer' => $popup['footer'] ?? '', 'header' => $popup['header'] ?? '', 'validate' => $reqSet['validate'] ?? '0', 'hold_popup' => $holdPopup, 'status' => true]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while processing the request.', 500);
        }
    }
}
