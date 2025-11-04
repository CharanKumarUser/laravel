<?php
namespace App\Http\Controllers\System\Business\DeviceManagement;
use App\Facades\{Data, Developer, Random, Scope, Skeleton, Select};
use App\Http\Controllers\Controller;
use App\Http\Helpers\PopupHelper;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
/**
 * Controller for rendering the edit form for DeviceManagement entities.
 */
class ShowEditCtrl extends Controller
{
    /**
     * Renders a popup form for editing DeviceManagement entities.
     *
     * @param  Request  $request  HTTP request object
     * @param  array  $params  Route parameters with token
     * @return JsonResponse Form configuration or error message
     */
    public function index(Request $request, array $params): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $params['token'] ?? $request->input('skeleton_token');
            if (! $token) {
                return ResponseHelper::moduleError('Token Missing', 'No token was provided.', 400);
            }
            // Resolve token to configuration
            $reqSet = Skeleton::resolveToken($token);
            if (! isset($reqSet['key']) || ! isset($reqSet['act']) || ! isset($reqSet['id'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid.', 400);
            }
            // Fetch existing data
            $result = Data::fetch($reqSet['system'], $reqSet['table'], [$reqSet['act'] => $reqSet['id']]);
            $dataItem = $result['data'][0] ?? null;
            $data = is_array($dataItem) ? (object) $dataItem : $dataItem;
            if (! $data) {
                return ResponseHelper::moduleError('Record Not Found', 'The requested record was not found.', 404);
            }
            // Initialize popup configuration
            $popup = [];
            $holdPopup = false;
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
                            ['type' => 'select', 'name' => 'company_id', 'label' => 'Business', 'required' => true, 'options' => Select::options('companies', 'array', ['company_id' => 'name'], ['where' => ['is_active' => 1]]), 'col' => '6', 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->company_id]],
                            ['type' => 'text', 'name' => 'name', 'label' => 'Device Name', 'required' => true, 'value' => $data->name, 'col' => '6', 'attr' => ['maxlength' => '100']],
                            ['type' => 'text', 'name' => 'serial_number', 'label' => 'Serial Number', 'required' => true, 'value' => $data->serial_number, 'col' => '6', 'attr' => ['maxlength' => '100']],
                            ['type' => 'text', 'name' => 'location', 'label' => 'Location', 'required' => false, 'value' => $data->location, 'col' => '6', 'attr' => ['maxlength' => '100']],
                            ['type' => 'text', 'name' => 'ip', 'label' => 'IP Address', 'required' => false, 'value' => $data->ip, 'col' => '6', 'attr' => ['maxlength' => '45', 'inputmode' => 'numeric', 'placeholder' => 'e.g. 192.168.0.10', 'pattern' => '^((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(\.|$)){4}$']],
                            ['type' => 'number', 'name' => 'port', 'label' => 'Port', 'required' => false, 'value' => $data->port, 'col' => '6', 'attr' => ['min' => '1', 'max' => '65535']],
                            ['type' => 'text', 'name' => 'mac_address', 'label' => 'MAC Address', 'required' => false, 'value' => $data->mac_address, 'col' => '6', 'attr' => ['maxlength' => '17', 'data-validate' => 'mac']],
                            ['type' => 'select', 'name' => 'is_active', 'label' => 'Status', 'options' => ['1' => 'Active', '0' => 'Inactive'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown', 'data-value' => $data->is_active]],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-md',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-microchip me-1"></i> Edit Device',
                        'short_label' => 'Update device network configuration',
                        'button' => 'Update Device',
                        'script' => "window.general.select();window.general.unique();",
                    ];
                    break;
                case 'business_device_settings':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'text', 'name' => 'device_id', 'label' => 'Device ID', 'value' => $data->device_id, 'required' => true, 'col' => '6', 'attr' => ['maxlength' => '30', 'readonly' => 'readonly']],
                            ['type' => 'text', 'name' => 'trans_stamp', 'label' => 'Transaction Stamp', 'value' => $data->trans_stamp, 'col' => '6', 'attr' => ['maxlength' => '50']],
                            ['type' => 'text', 'name' => 'attlog_stamp', 'label' => 'Attendance Log Stamp', 'value' => $data->attlog_stamp, 'col' => '6', 'attr' => ['maxlength' => '50']],
                            ['type' => 'text', 'name' => 'op_stamp', 'label' => 'Operation Stamp', 'value' => $data->op_stamp, 'col' => '6', 'attr' => ['maxlength' => '50']],
                            ['type' => 'text', 'name' => 'operlog_stamp', 'label' => 'Operator Log Stamp', 'value' => $data->operlog_stamp, 'col' => '6', 'attr' => ['maxlength' => '50']],
                            ['type' => 'text', 'name' => 'photo_stamp', 'label' => 'Photo Stamp', 'value' => $data->photo_stamp, 'col' => '6', 'attr' => ['maxlength' => '50']],
                            ['type' => 'text', 'name' => 'attphoto_stamp', 'label' => 'Attendance Photo Stamp', 'value' => $data->attphoto_stamp, 'col' => '6', 'attr' => ['maxlength' => '50']],
                            ['type' => 'number', 'name' => 'error_delay', 'label' => 'Error Delay', 'value' => $data->error_delay, 'col' => '4', 'attr' => ['min' => '0']],
                            ['type' => 'number', 'name' => 'delay', 'label' => 'Delay', 'value' => $data->delay, 'col' => '4', 'attr' => ['min' => '0']],
                            ['type' => 'text', 'name' => 'trans_times', 'label' => 'Transmission Times', 'value' => $data->trans_times, 'col' => '4', 'attr' => ['maxlength' => '50']],
                            ['type' => 'number', 'name' => 'trans_interval', 'label' => 'Transmission Interval', 'value' => $data->trans_interval, 'col' => '4', 'attr' => ['min' => '0']],
                            ['type' => 'text', 'name' => 'trans_flag', 'label' => 'Transmission Flag', 'value' => $data->trans_flag, 'col' => '4', 'attr' => ['maxlength' => '20']],
                            ['type' => 'select', 'name' => 'realtime', 'label' => 'Realtime', 'value' => $data->realtime, 'options' => ['1' => 'Yes', '0' => 'No'], 'col' => '4', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'number', 'name' => 'timeout', 'label' => 'Timeout', 'value' => $data->timeout, 'col' => '4', 'attr' => ['min' => '0']],
                            ['type' => 'number', 'name' => 'timezone', 'label' => 'Timezone', 'value' => $data->timezone, 'col' => '4', 'attr' => ['min' => '0']],
                            ['type' => 'select', 'name' => 'encrypt', 'label' => 'Encrypt', 'value' => $data->encrypt, 'options' => ['0' => 'No', '1' => 'Yes'], 'col' => '4', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'memory_alert', 'label' => 'Memory Alert', 'value' => $data->memory_alert, 'options' => ['0' => 'No', '1' => 'Yes'], 'col' => '4', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'number', 'name' => 'memory_threshold', 'label' => 'Memory Threshold', 'value' => $data->memory_threshold, 'col' => '4', 'attr' => ['min' => '0']],
                            ['type' => 'number', 'name' => 'memory_interval', 'label' => 'Memory Interval', 'value' => $data->memory_interval, 'col' => '4', 'attr' => ['min' => '0']],
                            ['type' => 'select', 'name' => 'attlog_alert', 'label' => 'Attendance Log Alert', 'value' => $data->attlog_alert, 'options' => ['0' => 'No', '1' => 'Yes'], 'col' => '4', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'number', 'name' => 'attlog_threshold', 'label' => 'Attendance Log Threshold', 'value' => $data->attlog_threshold, 'col' => '4', 'attr' => ['min' => '0']],
                            ['type' => 'number', 'name' => 'attlog_interval', 'label' => 'Attendance Log Interval', 'value' => $data->attlog_interval, 'col' => '4', 'attr' => ['min' => '0']],
                            ['type' => 'select', 'name' => 'auto_remove_logs', 'label' => 'Auto Remove Logs', 'value' => $data->auto_remove_logs, 'options' => ['0' => 'No', '1' => 'Yes'], 'col' => '4', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'number', 'name' => 'auto_remove_age', 'label' => 'Auto Remove Age', 'value' => $data->auto_remove_age, 'col' => '4', 'attr' => ['min' => '0']],
                            ['type' => 'number', 'name' => 'auto_remove_threshold', 'label' => 'Auto Remove Threshold', 'value' => $data->auto_remove_threshold, 'col' => '4', 'attr' => ['min' => '0']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-xl',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-cogs me-1"></i> Edit Device Settings',
                        'short_label' => 'Configure device synchronization and performance settings',
                        'button' => 'Update Device Settings',
                        'script' => 'window.skeleton.select();',
                    ];
                    break;
                case 'business_device_users':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'text', 'name' => 'device_user_id', 'label' => 'Device User ID', 'value' => $data->device_user_id, 'required' => true, 'col' => '6', 'attr' => ['maxlength' => '50', 'readonly' => 'readonly']],
                            ['type' => 'text', 'name' => 'device_id', 'label' => 'Device ID', 'value' => $data->device_id, 'required' => true, 'col' => '6', 'attr' => ['maxlength' => '30', 'readonly' => 'readonly']],
                            ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'value' => $data->name, 'required' => true, 'col' => '6', 'attr' => ['maxlength' => '100']],
                            ['type' => 'number', 'name' => 'privilege', 'label' => 'Privilege', 'value' => $data->privilege, 'col' => '6', 'attr' => ['min' => '0']],
                            ['type' => 'password', 'name' => 'password', 'label' => 'Password', 'value' => $data->password, 'col' => '6', 'attr' => ['maxlength' => '50']],
                            ['type' => 'text', 'name' => 'card_number', 'label' => 'Card Number', 'value' => $data->card_number, 'col' => '6', 'attr' => ['maxlength' => '50']],
                            ['type' => 'number', 'name' => 'group_id', 'label' => 'Group ID', 'value' => $data->group_id, 'col' => '6', 'attr' => ['min' => '1']],
                            ['type' => 'text', 'name' => 'time_zone', 'label' => 'Time Zone', 'value' => $data->time_zone, 'col' => '6', 'attr' => ['maxlength' => '16']],
                            ['type' => 'text', 'name' => 'expires', 'label' => 'Expires', 'value' => $data->expires, 'col' => '6', 'attr' => ['maxlength' => '11']],
                            ['type' => 'text', 'name' => 'start_datetime', 'label' => 'Start DateTime', 'value' => $data->start_datetime, 'col' => '6', 'attr' => ['maxlength' => '50']],
                            ['type' => 'text', 'name' => 'end_datetime', 'label' => 'End DateTime', 'value' => $data->end_datetime, 'col' => '6', 'attr' => ['maxlength' => '50']],
                            ['type' => 'number', 'name' => 'valid_count', 'label' => 'Valid Count', 'value' => $data->valid_count, 'col' => '6', 'attr' => ['min' => '0']],
                        ],
                        'type' => 'modal',
                        'size' => 'modal-lg',
                        'position' => 'end',
                        'label' => '<i class="fa-solid fa-user me-1"></i> Edit Device User',
                        'short_label' => 'Update device user access privileges and settings',
                        'button' => 'Update Device User',
                        'script' => 'window.skeleton.select();',
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
    /**
     * Renders a popup to confirm bulk update of records.
     *
     * @param  Request  $request  HTTP request object containing input data.
     * @param  array  $params  Route parameters including token.
     * @return JsonResponse Custom UI configuration for the popup or an error message.
     */
    public function bulk(Request $request, array $params = []): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $params['token'] ?? $request->input('skeleton_token', '');
            if (empty($token)) {
                return ResponseHelper::moduleError('Token Missing', 'No token was provided.', 400);
            }
            // Resolve token to configuration
            $reqSet = Skeleton::resolveToken($token);
            if (! isset($reqSet['system']) || ! isset($reqSet['table']) || ! isset($reqSet['act'])) {
                return ResponseHelper::moduleError('Invalid Token', 'The provided token is invalid or missing required data.', 400);
            }
            // Parse IDs
            $ids = array_filter(explode('@', $request->input('id', '')));
            if (empty($ids)) {
                return ResponseHelper::moduleError('Invalid Data', 'No records specified for update.', 400);
            }
            // Fetch records details
            $result = Data::fetch($reqSet['system'], $reqSet['table'], ['where' => [
                $reqSet['act'] => ['operator' => 'IN', 'value' => $ids],
            ]], 'all');
            if (! $result['status'] || empty($result['data'])) {
                return ResponseHelper::moduleError('Records Not Found', $result['message'] ?: 'The requested records were not found.', 404);
            }
            $records = $result['data'];
            // Initialize popup configuration
            $popup = [];
            $holdPopup = false;
            $recordCount = count($records);
            $maxDisplayRecords = 5;
            // Generate accordion for records
            $detailsHtml = sprintf('<div class="alert alert-warning" role="alert"><div class="accordion" id="updateAccordion-%s"><div class="accordion-item border-0"><h2 class="accordion-header p-0 my-0"><button class="accordion-button collapsed p-2 text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-%s" aria-expanded="false" aria-controls="collapse-%s">Confirm Bulk Update of %d Record(s)</button></h2><div id="collapse-%s" class="accordion-collapse collapse" data-bs-parent="#updateAccordion-%s"><div class="accordion-body p-2 bg-light"><div class="accordion" id="updateRecords-%s">', $token, $token, $token, $recordCount, $token, $token, $token);
            if ($recordCount > $maxDisplayRecords) {
                $detailsHtml .= sprintf('<div class="d-flex justify-content-between align-items-center"><div class="text-muted">Updating <b>%d</b> records.</div><button class="btn btn-link btn-sm text-decoration-none text-primary sf-12" type="button" data-bs-toggle="collapse" data-bs-target="#details-%s" aria-expanded="false" aria-controls="details-%s">Details</button></div><div class="collapse mt-2" id="details-%s"><div class="table-responsive" style="max-height: 200px;">', $recordCount, $token, $token, $token);
            }
            $detailsHtml .= '<table class="table table-sm table-bordered mb-0">';
            $displayRecords = $recordCount > $maxDisplayRecords ? array_slice($records, 0, 5) : $records;
            foreach ($displayRecords as $index => $record) {
                $recordArray = (array) $record;
                $recordId = htmlspecialchars($recordArray[$reqSet['act']] ?? 'N/A');
                $detailsHtml .= sprintf('<tr><td colspan="2"><b>Record %d (ID: %s)</b></td></tr>', $index + 1, $recordId);
                if (empty($recordArray)) {
                    $detailsHtml .= '<tr><td colspan="2" class="text-muted">No displayable details available</td></tr>';
                } else {
                    foreach ($recordArray as $key => $value) {
                        $detailsHtml .= sprintf('<tr><td>%s</td><td><b>%s</b></td></tr>', htmlspecialchars(ucwords(str_replace('_', ' ', $key))), htmlspecialchars($value ?? ''));
                    }
                }
            }
            $detailsHtml .= $recordCount > $maxDisplayRecords ? sprintf('<tr><td colspan="2" class="text-muted">... and %d more records</td></tr></table></div></div>', $recordCount - count($displayRecords)) : '</table>';
            $detailsHtml .= sprintf('</div><div class="mt-2"><i class="sf-10"><span class="text-danger">Note: </span>Only non-unique fields can be updated in bulk. Changes will apply to all %d selected records. Ensure values are valid to avoid data conflicts.</i></div></div></div></div></div></div>', $recordCount);
            // Initialize popup configuration
            $popup = [];
            $detailsHtmlPlacement = 'top';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
            switch ($reqSet['key']) {
                case 'DeviceManagement_entities':
                    $popup = [
                        'form' => 'builder',
                        'labelType' => 'floating',
                        'fields' => [
                            ['type' => 'select', 'name' => 'type', 'label' => 'Type', 'options' => ['data' => 'Data', 'unique' => 'Unique', 'select' => 'Select', 'other' => 'Other'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                            ['type' => 'select', 'name' => 'status', 'label' => 'Status', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'required' => true, 'col' => '6', 'attr' => ['data-select' => 'dropdown']],
                        ],
                        'type' => 'offcanvas',
                        'size' => '-',
                        'position' => 'end',
                        'label' => '<i class="fa-regular fa-folder me-1"></i> Bulk Edit DeviceManagement Entities',
                        'short_label' => 'Update multiple device management entities simultaneously',
                        'button' => 'Update Entities',
                        'script' => 'window.skeleton.select();window.skeleton.unique();',
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
            $content = '<input type="hidden" name="update_ids" value="'.$request->input('id', '').'">';
            $content .= $popup['form'] === 'builder' ? PopupHelper::generateBuildForm($token, $popup['fields'], $popup['labelType']) : $popup['content'];
            $content = $detailsHtmlPlacement === 'top' ? $detailsHtml.$content : $content.$detailsHtml;
            // Generate response
            return response()->json(['token' => $token, 'type' => $popup['type'], 'size' => $popup['size'], 'position' => $popup['position'], 'label' => $popup['label'], 'short_label' => $popup['short_label'], 'content' => $content, 'script' => $popup['script'], 'button_class' => $popup['button_class'] ?? '', 'button' => $popup['button'] ?? '', 'footer' => $popup['footer'] ?? '', 'header' => $popup['header'] ?? '', 'validate' => $reqSet['validate'] ?? '0', 'hold_popup' => $holdPopup, 'status' => true]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while processing the request.', 500);
        }
    }
}
