<?php
namespace App\Http\Controllers\System\Actions;
use App\Http\Controllers\Controller;
use App\Facades\{CentralDB, Data, Developer, Skeleton};
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Log, Schema};
/**
 * Controller for handling single and multiple record deletions with custom Bootstrap 5 accordion UI.
 */
class Delete extends Controller
{
    /**
     * Columns to exclude from the details table globally.
     *
     * @var array
     */
    protected $excludedColumns = [
        'id',
        'unique_id',
        'content',
        'password',
        'created_by',
        'updated_by',
        'deleted_at',
        'deleted_on',
    ];
    /**
     * Renders a popup to confirm single record deletion.
     *
     * @param Request $request HTTP request object.
     * @param array $params Route parameters with token.
     * @return JsonResponse Custom UI configuration or error message.
     */
    public function single(Request $request, array $params = []): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $params['token'] ?? $request->input('skeleton_token', '');
            if (empty($token)) {
                return response()->json(['status' => false, 'title' => 'Token Missing', 'message' => 'No token was provided.']);
            }
            // Resolve token to configuration
            $reqSet = Skeleton::resolveToken($token);
            if (!isset($reqSet['system']) || !isset($reqSet['table']) || !isset($reqSet['id'])) {
                return response()->json(['status' => false, 'title' => 'Invalid Token', 'message' => 'The provided token is invalid or missing required data.']);
            }
            // Fetch record details
            $result = Data::fetch($reqSet['system'], $reqSet['table'], ['where' => [$reqSet['act'] ?? 'id' => $reqSet['id']]]);
            if (!$result['status'] || empty($result['data'])) {
                return response()->json(['status' => false, 'title' => 'Record Not Found', 'message' => 'The requested record was not found.']);
            }
            $record = $result['data'][0] ?? null;
            // Generate details table
            $detailsHtml = '<table class="table table-sm table-bordered mb-0">';
            if ($record) {
                $filteredRecord = array_diff_key((array)$record, array_flip($this->excludedColumns));
                if (empty($filteredRecord)) {
                    $detailsHtml .= '<tr><td colspan="2">No displayable details available</td></tr>';
                } else {
                    foreach ($filteredRecord as $key => $value) {
                        $detailsHtml .= sprintf('<tr><td>%s</td><td class="text-wrap"><b>%s</b></td></tr>', htmlspecialchars(ucwords(str_replace('_', ' ', $key))), htmlspecialchars($value));
                    }
                }
            } else {
                $detailsHtml .= '<tr><td colspan="2">No details available</td></tr>';
            }
            $detailsHtml .= '</table>';
            // Check table schema for deletion columns
            $hasDeletedOn = Schema::connection($reqSet['system'])->hasColumn($reqSet['table'], 'deleted_on');
            // Build checkbox HTML conditionally
            $checkboxHtml = '';
            if ($hasDeletedOn) {
                $checkboxHtml .= '<div class="mb-3 d-flex justify-content-center align-items-center">' .
                    '<div class="form-check">' .
                    '<input class="form-check-input" type="checkbox" name="delete_type" value="1" id="perm-delete-' . $token . '">' .
                    '<label class="form-check-label ms-2" for="perm-delete-' . $token . '">Permanent Delete</label>' .
                    '</div>' .
                    '</div>';
            }
            // Define custom content with Bootstrap 5 accordion
            $content = '<div class="alert alert-transparent mb-0" role="alert">' .
                '<div class="accordion" id="deleteAccordion-' . $token . '">' .
                '<div class="accordion-item border-0">' .
                '<h2 class="accordion-header py-2 my-0">' .
                '<button class="accordion-button bg-transparent collapsed py-2 text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-' . $token . '" aria-expanded="false" aria-controls="collapse-' . $token . '">' .
                '<h4 class="m-0">Are you sure you want to delete this record?</h4>' .
                '</button>' .
                '</h2>' .
                '<div id="collapse-' . $token . '" class="accordion-collapse collapse" data-bs-parent="#deleteAccordion-' . $token . '">' .
                '<div class="accordion-body p-2">' .
                '<input type="hidden" name="delete_token" value="' . $token . '">' .
                $checkboxHtml .
                $detailsHtml .
                '<div class="mt-2"><i class="sf-10"><span class="text-danger">Warning: </span>Permanent deletion schedules the data for removal after 30 days. Temporary deleted data can be retrieved before then.</i></div>' .
                '</div>' .
                '</div>' .
                '</div>' .
                '</div>' .
                '</div>';
            // Generate response
            return response()->json([
                'token' => $token,
                'type' => 'modal',
                'size' => 'modal-md',
                'position' => 'end',
                'label' => '<i class="fa-regular fa-trash-can me-1"></i> Delete Record',
                'content' => $content,
                'script' => '',
                'button_class' => 'btn-danger',
                'button' => 'Confirm Delete',
                'validate' => $reqSet['validate'] ?? '0',
                'status' => true
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'title' => 'Error', 'message' => Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while processing the request.']);
        }
    }
    /**
     * Processes the confirmed single record deletion.
     *
     * @param Request $request HTTP request object.
     * @return JsonResponse Success or error message.
     */
    public function delete_single(Request $request): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $request->input('delete_token', '');
            if (empty($token)) {
                return response()->json(['status' => false, 'title' => 'Token Missing', 'message' => 'No token was provided.']);
            }
            // Resolve token to configuration
            $reqSet = Skeleton::resolveToken($token);
            if (!isset($reqSet['table']) || !isset($reqSet['id'])) {
                return response()->json(['status' => false, 'title' => 'Invalid Token', 'message' => 'The provided token is invalid or missing required data.']);
            }
            // Determine deletion type
            $deleteType = $request->input('delete_type', 0);
            // Check table schema for deletion columns
            $hasDeletedOn = Schema::connection($reqSet['system'])->hasColumn($reqSet['table'], 'deleted_on');
            // Perform deletion using Data facade
            $updateData = [
                $deleteType ? 'deleted_on' : 'deleted_at' => $deleteType ? now()->addDays(30) : now(),
                'updated_by' => Skeleton::authUser()->user_id
            ];
            $result = Data::update($reqSet['system'], $reqSet['table'], $updateData, [$reqSet['act'] ?? 'id' => $reqSet['id']]);
            $affected = $result['status'] ? ($result['data']['affected_rows'] ?? 0) : 0;
            return response()->json([
                'status' => $affected > 0,
                'token' => $reqSet['token'],
                'reload_table' => true,
                'reload_card' => true,
                'title' => $affected > 0 ? 'Success' : 'Failed',
                'message' => $affected > 0 ? ($deleteType ? 'Record permanently deleted.' : 'Record temporarily deleted.') : 'No changes were made.',
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'title' => 'Error', 'message' => Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while deleting the record.']);
        }
    }
    /**
     * Renders a popup to confirm multiple record deletion.
     *
     * @param Request $request HTTP request object.
     * @param array $params Route parameters with token.
     * @return JsonResponse Custom UI configuration or error message.
     */
    public function bulk(Request $request, array $params = []): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $params['token'] ?? $request->input('skeleton_token', '');
            if (empty($token)) {
                return response()->json(['status' => false, 'title' => 'Token Missing', 'message' => 'No token was provided.']);
            }
            // Resolve token to configuration
            $reqSet = Skeleton::resolveToken($token);
            if (!isset($reqSet['system']) || !isset($reqSet['table'])) {
                return response()->json(['status' => false, 'title' => 'Invalid Token', 'message' => 'The provided token is invalid or missing required data.']);
            }
            // Parse IDs
            $ids = array_filter(explode('@', $request->input('id')));
            if (empty($ids)) {
                return response()->json(['status' => false, 'title' => 'Invalid Data', 'message' => 'No records specified for deletion.']);
            }
            // Fetch records details
            $result = Data::fetch($reqSet['system'], $reqSet['table'], ['where' => [
                $reqSet['act'] => ['operator' => 'IN', 'value' => $ids],
            ]]);
            if (!$result['status'] || empty($result['data'])) {
                return response()->json(['status' => false, 'title' => 'Records Not Found', 'message' => $result['message'] ?: 'The requested records were not found.']);
            }
            $records = $result['data'];
            // Determine display mode based on record count
            $recordCount = count($records);
            $maxDisplayRecords = 10; // Limit for detailed display
            $excludedColumns = $this->excludedColumns ?? [];
            // Generate summary or detailed table
            $detailsHtml = '';
            if ($recordCount > $maxDisplayRecords) {
                // Summary for large datasets
                $detailsHtml .= '<div class="text-muted mb-2">Selected ' . $recordCount . ' records for deletion.</div>';
                $detailsHtml .= '<button class="btn btn-link p-0 text-decoration-underline" type="button" data-bs-toggle="collapse" data-bs-target="#details-' . $token . '" aria-expanded="false" aria-controls="details-' . $token . '">Show Details</button>';
                $detailsHtml .= '<div class="collapse mt-2" id="details-' . $token . '">';
                $detailsHtml .= '<div class="table-responsive" style="max-height: 200px;">';
                $detailsHtml .= '<table class="table table-sm table-bordered mb-0">';
            } else {
                $detailsHtml .= '<table class="table table-sm table-bordered mb-0">';
            }
            // Generate table content
            if (!empty($records)) {
                $displayRecords = $recordCount > $maxDisplayRecords ? array_slice($records, 0, 5) : $records; // Show first 5 for large sets
                foreach ($displayRecords as $index => $record) {
                    $recordArray = (array)$record;
                    $filteredRecord = array_diff_key($recordArray, array_flip($excludedColumns));
                    $detailsHtml .= '<tr><td colspan="2" class="bg-light"><b>Record ' . ($index + 1) . '</b></td></tr>';
                    if (empty($filteredRecord)) {
                        $detailsHtml .= '<tr><td colspan="2">No displayable details available</td></tr>';
                    } else {
                        foreach ($filteredRecord as $key => $value) {
                            $detailsHtml .= sprintf(
                                '<tr><td>%s</td><td class="text-wrap"><b>%s</b></td></tr>',
                                htmlspecialchars(ucwords(str_replace('_', ' ', $key))),
                                htmlspecialchars($value ?? '')
                            );
                        }
                    }
                }
                if ($recordCount > $maxDisplayRecords) {
                    $detailsHtml .= '<tr><td colspan="2" class="text-muted">... and ' . ($recordCount - 5) . ' more records</td></tr>';
                }
            } else {
                $detailsHtml .= '<tr><td colspan="2">No details available</td></tr>';
            }
            $detailsHtml .= '</table>';
            if ($recordCount > $maxDisplayRecords) {
                $detailsHtml .= '</div></div>'; // Close table-responsive and collapse
            }
            // Check table schema for deletion columns
            $hasDeletedAt = Schema::connection($reqSet['system'])->hasColumn($reqSet['table'], 'deleted_at');
            $hasDeletedOn = Schema::connection($reqSet['system'])->hasColumn($reqSet['table'], 'deleted_on');
            // Build checkbox HTML
            $checkboxHtml = '';
            if ($hasDeletedAt || $hasDeletedOn) {
                $checkboxHtml .= '<div class="mb-3 d-flex justify-content-center align-items-center">';
                if ($hasDeletedAt) {
                    $checkboxHtml .= '<div class="form-check me-3">' .
                        '<input class="form-check-input" type="checkbox" name="delete_type[]" value="temporary" id="temp-delete-' . $token . '" checked>' .
                        '<label class="form-check-label ms-2" for="temp-delete-' . $token . '">Temporary Delete</label>' .
                        '</div>';
                }
                if ($hasDeletedOn) {
                    $checkboxHtml .= '<div class="form-check">' .
                        '<input class="form-check-input" type="checkbox" name="delete_type[]" value="permanent" id="perm-delete-' . $token . '">' .
                        '<label class="form-check-label ms-2" for="perm-delete-' . $token . '">Permanent Delete</label>' .
                        '</div>';
                }
                $checkboxHtml .= '</div>';
            } else {
                $checkboxHtml .= '<div class="alert alert-info mb-3">No deletion options available for this table.</div>';
            }
            // Define custom content with Bootstrap 5 accordion
            $content = '<div class="alert alert-warning bg-transparent mb-0" role="alert">' .
                '<div class="accordion" id="deleteAccordion-' . $token . '">' .
                '<div class="accordion-item border-0">' .
                '<h2 class="accordion-header py-2 my-0">' .
                '<button class="accordion-button bg-transparent collapsed py-2 text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-' . $token . '" aria-expanded="false" aria-controls="collapse-' . $token . '">' .
                '<h4 class="m-0">Confirm Bulk Deletion of ' . $recordCount . ' Record(s)</h4>' .
                '</button>' .
                '</h2>' .
                '<div id="collapse-' . $token . '" class="accordion-collapse collapse" data-bs-parent="#deleteAccordion-' . $token . '">' .
                '<div class="accordion-body p-1 rounded-2">' .
                '<form id="delete-form-' . $token . '">' .
                '<input type="hidden" name="delete_token" value="' . $token . '">' .
                '<input type="hidden" name="delete_ids" value="' . implode('@', $ids) . '">' .
                $checkboxHtml .
                $detailsHtml .
                '<div class="mt-2"><i class="sf-10"><span class="text-danger">Warning: </span>This action will affect <b>' . $recordCount . '</b> records. Permanent deletion schedules data for removal after 30 days. Temporary deleted data can be retrieved before then.</i></div>' .
                '</form>' .
                '</div>' .
                '</div>' .
                '</div>' .
                '</div>';
            // Generate response
            return response()->json([
                'token' => $token,
                'type' => 'modal',
                'size' => 'modal-lg',
                'position' => 'end',
                'label' => '<i class="fa-solid fa-trash-can-arrow-up me-1"></i> Bulk Delete Records',
                'content' => $content,
                'script' => '',
                'button_class' => 'btn-outline-danger',
                'button' => 'Confirm Bulk Delete',
                'validate' => $reqSet['validate'] ?? '0',
                'status' => true
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'title' => 'Error',
                'message' => Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while processing the request.'
            ]);
        }
    }
    /**
     * Processes the confirmed bulk record deletion.
     *
     * @param Request $request HTTP request object.
     * @return JsonResponse Success or error message.
     */
    public function delete_bulk(Request $request): JsonResponse
    {
        try {
            // Extract and validate token
            $token = $request->input('delete_token', '');
            if (empty($token)) {
                return response()->json(['status' => false, 'title' => 'Token Missing', 'message' => 'No token was provided.']);
            }
            // Resolve token to configuration
            $reqSet = Skeleton::resolveToken($token);
            if (!isset($reqSet['system']) || !isset($reqSet['table']) || !isset($reqSet['act'])) {
                return response()->json(['status' => false, 'title' => 'Invalid Token', 'message' => 'The provided token is invalid or missing required data.']);
            }
            // Split delete_ids into individual IDs
            $ids = explode('@', $request->input('delete_ids', ''));
            if (empty($ids)) {
                return response()->json(['status' => false, 'title' => 'Invalid Data', 'message' => 'No valid IDs provided for deletion.']);
            }
            // Check table schema for deletion columns
            $hasDeletedAt = Schema::connection($reqSet['system'])->hasColumn($reqSet['table'], 'deleted_at');
            $hasDeletedOn = Schema::connection($reqSet['system'])->hasColumn($reqSet['table'], 'deleted_on');
            // Get deletion types from form
            $deleteTypes = $request->input('delete_type', []);
            if (!is_array($deleteTypes)) {
                $deleteTypes = [$deleteTypes];
            }
            // Validate deletion types
            $availableDeleteTypes = [];
            if ($hasDeletedAt) {
                $availableDeleteTypes[] = 'temporary';
            }
            if ($hasDeletedOn) {
                $availableDeleteTypes[] = 'permanent';
            }
            if (!empty($deleteTypes) && !empty($availableDeleteTypes) && array_diff($deleteTypes, $availableDeleteTypes)) {
                return response()->json(['status' => false, 'title' => 'Invalid Deletion Type', 'message' => 'Selected deletion type(s) are not supported by this table.']);
            }
            // Prepare update data
            $updateData = [
                'updated_by' => Skeleton::authUser()->user_id,
                'updated_at' => now(),
            ];
            $messageParts = [];
            if (in_array('temporary', $deleteTypes) && $hasDeletedAt) {
                $updateData['deleted_at'] = now();
                $messageParts[] = 'temporarily deleted';
            }
            if (in_array('permanent', $deleteTypes) && $hasDeletedOn) {
                $updateData['deleted_on'] = now()->addDays(30);
                $messageParts[] = 'permanently deleted';
            }
            // Perform deletion
            $affected = 0;
            if (!empty($updateData) && !empty($deleteTypes)) {
                // Soft delete using a single update query
                $result = Data::update($reqSet['system'], $reqSet['table'], $updateData, [$reqSet['act'] => ['operator' => 'IN', 'value' => $ids]], 'all'); 
                $affected = $result['data']['affected_rows'] ?? 0;
            } else {
                return response()->json(['status' => false, 'title' => 'Invalid Request', 'message' => 'No valid deletion type selected or soft deletion not supported.']);
            }
            // Construct response message
            $message = $affected > 0
                ? sprintf('Records %s (%d).', implode(' and ', $messageParts), $affected)
                : 'No changes were made.';
            return response()->json([
                'status' => $affected > 0,
                'token' => $reqSet['token'],
                'reload_table' => true,
                'title' => $affected > 0 ? 'Success' : 'Failed',
                'message' => $message,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'title' => 'Error',
                'message' => Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while deleting the records.'
            ]);
        }
    }
}
