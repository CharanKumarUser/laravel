<?php
namespace App\Http\Controllers\System\Business\CompanyManagement;
use App\Facades\{Data, Developer, Random, Skeleton, FileManager};
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, Validator};
/**
 * Controller for saving new CompanyManagement entities.
 */
class FormCtrl extends Controller
{
    /**
     * Saves new CompanyManagement entity data based on validated input.
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
            $byMeta = $timestampMeta = $store = true;
            $reloadTable = $reloadCard = $reloadPage = $holdPopup = false;
            $validated = [];
            $title = 'Success';
            $message = 'CompanyManagement data saved successfully.';
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different configuration keys
             switch ($reqSet['key']) {
                case 'CompanyManagement_entities':
                    $validator = Validator::make($request->all(), [
                        'name' => 'required|string|max:255',
                        'type' => 'required|in:data,unique,select,other',
                        'status' => 'required|in:active,inactive',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Failed', $validator->errors()->first(), 422);
                    }
                    $validated = $validator->validated();
                    $validated['entity_id'] = Random::unique(6, 'ENT');
                    $title = 'Entity Added';
                    $message = 'CompanyManagement entity configuration added successfully.';
                    break;
                case 'business_company_news':
    // Handle delete request (admin or owner only)
    if ($request->boolean('delete')) {
        $newsId = $request->input('news_id');
        if (!$newsId) {
            return ResponseHelper::moduleError('Invalid', 'news_id is required', 422);
        }

        $system = Skeleton::getUserSystem();
        $viewerId = Skeleton::authUser()->user_id;

        // ✅ Fixed fetch (removed nested 'where', replaced 'columns' with 'select')
        $postResp = Data::fetch($system, 'news', [
            'select' => ['news_id', 'user_id', 'deleted_at'],
            'news_id' => $newsId,
            'limit' => 1
        ]);

        if (empty($postResp['data'])) {
            return ResponseHelper::moduleError('Not Found', 'Post not found', 404);
        }

        $post = $postResp['data'][0];

        // Permission: owner or admin
        $canDelete = ($post['user_id'] ?? null) === $viewerId;
        if (!$canDelete) {
            try {
                $role = Skeleton::authUser('role');
                $roleName = strtoupper($role['name'] ?? '');
                $roleId = strtoupper($role['role_id'] ?? '');
                $canDelete = in_array($roleName, ['ADMIN', 'SUPREME'], true) || in_array($roleId, ['ADMIN', 'SUPREME'], true);
            } catch (Exception $e) {}
        }

        if (!$canDelete) {
            return ResponseHelper::moduleError('Forbidden', 'You are not allowed to delete this post', 403);
        }

        // Soft delete
        $result = Data::update($system, 'news', [
            'deleted_at' => now(),
            'updated_at' => now(),
            'updated_by' => $viewerId,
        ], ['news_id' => $newsId]);

        if (!$result['status']) {
            return ResponseHelper::moduleError('Delete Failed', $result['message'] ?? 'Failed to delete post', 400);
        }

        $store = false;
        $title = 'Post Deleted';
        $message = 'Post deleted successfully.';
        break;
    }

    // Validation
    $validator = Validator::make($request->all(), [
        'content' => 'required|string|max:5000',
        'title' => 'nullable|string|max:255',
        'link_url' => 'nullable|url|max:500',
        'file_id' => 'nullable|string|max:30',
        'image' => 'nullable|file|image|max:5120',
        'is_public' => 'nullable|in:0,1',
        'scope_id' => 'nullable|string|max:30',
        'high_priority' => 'nullable|in:yes,no',
    ]);

    if ($validator->fails()) {
        return ResponseHelper::moduleError('Validation Failed', $validator->errors()->first(), 422);
    }

    $validated = $validator->validated();
    $validated['user_id'] = Skeleton::authUser()->user_id;

    // Idempotency check: avoid duplicate consecutive posts
    $system = $reqSet['system'];

    // ✅ Fixed fetch
    $recent = Data::fetch($system, 'news', [
        'select' => ['news_id', 'user_id', 'content', 'created_at'],
        'user_id' => $validated['user_id'],
        'content' => $validated['content'],
        'order_by' => [['column' => 'created_at', 'direction' => 'desc']],
        'limit' => 1
    ]);

    if (!empty($recent['data'])) {
        try {
            $lastAt = \Carbon\Carbon::parse($recent['data'][0]['created_at']);
            if ($lastAt->diffInSeconds(now()) < 5) {
                return response()->json(['status' => true, 'reload_page' => true, 'message' => 'Duplicate ignored']);
            }
        } catch (Exception $e) {}
    }

    $validated['news_id'] = Random::unique(12, 'NWS');

    // Handle optional image upload
    try {
        if ($request->hasFile('image')) {
            $user = Skeleton::authUser();
            $businessId = $user->business_id ?? '';
            if (!empty($businessId)) {
                $folderKey = 'business_news_images';
                $fileResult = FileManager::saveFile(
                    $request,
                    $folderKey,
                    'image',
                    'News',
                    $businessId,
                    true
                );
                if (!empty($fileResult['status']) && $fileResult['status'] === true) {
                    $validated['file_id'] = $fileResult['data']['file_id'] ?? ($validated['file_id'] ?? null);
                }
            }
        }
    } catch (Exception $e) {
        if (Config::get('skeleton.developer_mode')) {
            Developer::warning('News image upload failed', ['error' => $e->getMessage()]);
        }
    }

    unset($validated['image']);

    // ✅ Fixed fetch: default scope lookup
    if (empty($validated['scope_id'])) {
        $userId = $validated['user_id'];
        $scopeResult = Data::fetch($system, 'scope_mapping', [
            'select' => ['scope_id'],
            'user_id' => $userId,
            'limit' => 1
        ]);
        if ($scopeResult['status'] && !empty($scopeResult['data'])) {
            $validated['scope_id'] = $scopeResult['data'][0]['scope_id'];
        }
    }

    // ✅ Fixed fetch: fallback to default active scope
    if (empty($validated['scope_id'])) {
        $defaultScopeResult = Data::fetch($system, 'scopes', [
            'select' => ['scope_id'],
            'is_active' => 1,
            'limit' => 1,
            'order_by' => [['column' => 'created_at', 'direction' => 'asc']]
        ]);

        $validated['scope_id'] = $defaultScopeResult['status'] && !empty($defaultScopeResult['data'])
            ? $defaultScopeResult['data'][0]['scope_id']
            : 'DEFAULT_SCOPE';
    }

    $validated['is_public'] = (int)($validated['is_public'] ?? 1);
    $validated['status'] = 'active';
    $validated['high_priority'] = (($validated['high_priority'] ?? 'no') === 'yes') ? 'yes' : 'no';

    if (Config::get('skeleton.developer_mode')) {
        Developer::info('FormCtrl: News post prepared for insertion', [
            'validated_fields' => array_keys($validated),
            'validated_data' => $validated,
            'has_image' => $request->hasFile('image'),
            'file_id_set' => !empty($validated['file_id'])
        ]);
    }

    $title = 'Post Shared';
    $message = 'Announcement posted successfully.';
    $reloadPage = true;
    break;

                case 'business_company_news_like':
                    $newsId = $request->input('news_id');
                    if (!$newsId) {
                        return ResponseHelper::moduleError('Invalid', 'news_id is required', 422);
                    }
                    $system = Skeleton::getUserSystem();
                    // If 'list' flag is present, return likers list instead of toggling like
                    if ($request->boolean('list')) {
                        $likesResp = Data::fetch($system, 'news_likes', [
                            'columns' => [
                                'news_likes.user_id AS user_id',
                                'news_likes.created_at AS created_at',
                            ],
                            'where' => [
                                'news_id' => $newsId,
                                'deleted_at' => null,
                            ],
                            'limit' => 100000,
                        ]);
                        if (!$likesResp['status']) {
                            return ResponseHelper::moduleError('Fetch Failed', $likesResp['message'] ?? 'Failed to fetch likes', 400);
                        }
                        $userIds = [];
                        foreach (($likesResp['data'] ?? []) as $row) {
                            $uid = $row['user_id'] ?? null;
                            if ($uid) { $userIds[$uid] = true; }
                        }
                        $userIds = array_keys($userIds);
                        if (empty($userIds)) {
                            return response()->json(['status' => true, 'likers' => []]);
                        }
                        $usersResp = Data::fetch($system, 'users', [
                            'columns' => [
                                'users.user_id AS user_id',
                                'users.first_name AS first_name',
                                'users.last_name AS last_name',
                            ],
                            'where' => [
                                'users.user_id' => ['operator' => 'IN', 'value' => $userIds],
                            ],
                            'limit' => 100000,
                        ]);
                        if (!$usersResp['status']) {
                            return ResponseHelper::moduleError('Fetch Failed', $usersResp['message'] ?? 'Failed to fetch users', 400);
                        }
                        // Determine admin users
                        $adminUserIds = [];
                        $roleLinksResp = Data::fetch($system, 'user_roles', [
                            'columns' => [
                                'user_roles.user_id AS user_id',
                                'user_roles.role_id AS role_id',
                            ],
                            'where' => [
                                'deleted_at' => null,
                                'is_active' => 1,
                                'user_id' => ['operator' => 'IN', 'value' => $userIds],
                            ],
                        ]);
                        $roleIds = [];
                        $userIdToRoleIds = [];
                        if ($roleLinksResp['status'] && !empty($roleLinksResp['data'])) {
                            foreach ($roleLinksResp['data'] as $rl) {
                                $rid = $rl['role_id'] ?? null;
                                $uid = $rl['user_id'] ?? null;
                                if ($rid && $uid) {
                                    $roleIds[$rid] = true;
                                    $userIdToRoleIds[$uid][] = $rid;
                                }
                            }
                        }
                        if (!empty($roleIds)) {
                            $roleDefsResp = Data::fetch($system, 'roles', [
                                'columns' => [
                                    'roles.role_id AS role_id',
                                    'roles.name AS name',
                                ],
                                'where' => [
                                    'roles.deleted_at' => null,
                                    'roles.is_active' => 1,
                                    'roles.role_id' => ['operator' => 'IN', 'value' => array_keys($roleIds)],
                                ],
                            ]);
                            $roleIdToIsAdmin = [];
                            if ($roleDefsResp['status'] && !empty($roleDefsResp['data'])) {
                                foreach ($roleDefsResp['data'] as $rd) {
                                    $name = strtoupper($rd['name'] ?? '');
                                    $roleIdToIsAdmin[$rd['role_id']] = in_array($name, ['ADMIN', 'SUPREME'], true);
                                }
                            }
                            foreach ($userIdToRoleIds as $uid => $rids) {
                                foreach ($rids as $rid) {
                                    if (!empty($roleIdToIsAdmin[$rid])) {
                                        $adminUserIds[$uid] = true;
                                        break;
                                    }
                                }
                            }
                        }
                        $userMap = [];
                        foreach ($usersResp['data'] as $u) {
                            $uid = $u['user_id'] ?? null;
                            if (!$uid) { continue; }
                            $name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
                            $userMap[$uid] = [
                                'user_id' => $uid,
                                'name' => $name !== '' ? $name : $uid,
                                'is_admin' => !empty($adminUserIds[$uid]),
                            ];
                        }
                        $likers = [];
                        foreach (($likesResp['data'] ?? []) as $l) {
                            $uid = $l['user_id'] ?? null;
                            $user = $uid && isset($userMap[$uid]) ? $userMap[$uid] : null;
                            if ($user) {
                                $likers[] = [
                                    'user_id' => $uid,
                                    'name' => $user['name'],
                                    'is_admin' => $user['is_admin'],
                                    'liked_at' => $l['created_at'] ?? '',
                                ];
                            }
                        }
                        // Sort by most recent likes first
                        usort($likers, function($a, $b) { 
                            return strtotime($b['liked_at']) - strtotime($a['liked_at']); 
                        });
                        return response()->json(['status' => true, 'likers' => $likers]);
                    }
                    $userId = Skeleton::authUser()->user_id;
                    // Check for ACTIVE like only (deleted_at = null)
                    $existing = Data::fetch($system, 'news_likes', [
                        'columns' => ['id'],
                        'where' => [
                            'news_id' => $newsId,
                            'user_id' => $userId,
                            'deleted_at' => null,
                        ],
                        'limit' => 1,
                    ]);
                    $likedNow = false;
                    if (!empty($existing['data'])) {
                        // Currently liked -> unlike (hard delete to ensure clean re-like)
                        $result = Data::delete($system, 'news_likes', [
                            'news_id' => $newsId,
                            'user_id' => $userId,
                        ]);
                        $title = 'Post Unliked';
                        $message = 'Post unliked successfully.';
                        $likedNow = false;
                    } else {
                        // No ACTIVE record -> create a fresh like
                        $result = Data::insert($system, 'news_likes', [
                            'news_id' => $newsId,
                            'user_id' => $userId,
                        ]);
                        $title = 'Post Liked';
                        $message = 'Post liked successfully.';
                        $likedNow = true;
                    }
                    // Recount likes excluding soft-deleted
                    $countResp = Data::fetch($system, 'news_likes', [
                        'columns' => ['id'],
                        'where' => [
                            'news_id' => $newsId,
                            'deleted_at' => null,
                        ],
                        'limit' => 100000,
                    ]);
                    $likesCount = $countResp['status'] && !empty($countResp['data']) ? count($countResp['data']) : 0;
                    // Build custom response
                    $store = false; // handled above
                    return response()->json([
                        'status' => $result['status'] ?? false,
                        'title' => ($result['status'] ?? false) ? $title : 'Failed',
                        'message' => ($result['status'] ?? false) ? $message : ($result['message'] ?? 'Failed'),
                        'liked' => $likedNow,
                        'likes_count' => $likesCount,
                    ]);
                case 'business_company_news_comment':
                    // If list=1, return comments list instead of creating comment
                    if ($request->boolean('list')) {
                        $newsId = $request->input('news_id');
                        if (empty($newsId)) {
                            return ResponseHelper::moduleError('Validation Failed', 'news_id is required', 422);
                        }
                        $system = Skeleton::getUserSystem();
                        $commentsResp = Data::fetch($system, 'news_comments', [
                            'columns' => [
                                'news_comments.comment_id AS comment_id',
                                'news_comments.user_id AS user_id',
                                'news_comments.content AS content',
                                'news_comments.created_at AS created_at',
                            ],
                            'where' => [
                                'news_id' => $newsId,
                            ],
                            'sort' => [
                                ['column' => 'created_at', 'direction' => 'asc'],
                            ],
                            'limit' => 100000,
                        ]);
                        if (!$commentsResp['status']) {
                            return ResponseHelper::moduleError('Fetch Failed', $commentsResp['message'] ?? 'Failed to fetch comments', 400);
                        }
                        $userIds = [];
                        foreach (($commentsResp['data'] ?? []) as $row) {
                            $uid = $row['user_id'] ?? null;
                            if ($uid) { $userIds[$uid] = true; }
                        }
                        $userIds = array_keys($userIds);
                        if (empty($userIds)) {
                            return response()->json(['status' => true, 'comments' => []]);
                        }
                        $usersResp = Data::fetch($system, 'users', [
                            'columns' => [
                                'users.user_id AS user_id',
                                'users.first_name AS first_name',
                                'users.last_name AS last_name',
                            ],
                            'where' => [
                                'users.user_id' => ['operator' => 'IN', 'value' => $userIds],
                            ],
                            'limit' => 100000,
                        ]);
                        if (!$usersResp['status']) {
                            return ResponseHelper::moduleError('Fetch Failed', $usersResp['message'] ?? 'Failed to fetch users', 400);
                        }
                        // Determine admin users
                        $adminUserIds = [];
                        $roleLinksResp = Data::fetch($system, 'user_roles', [
                            'columns' => [
                                'user_roles.user_id AS user_id',
                                'user_roles.role_id AS role_id',
                            ],
                            'where' => [
                                'deleted_at' => null,
                                'is_active' => 1,
                                'user_id' => ['operator' => 'IN', 'value' => $userIds],
                            ],
                        ]);
                        $roleIds = [];
                        $userIdToRoleIds = [];
                        if ($roleLinksResp['status'] && !empty($roleLinksResp['data'])) {
                            foreach ($roleLinksResp['data'] as $rl) {
                                $rid = $rl['role_id'] ?? null;
                                $uid = $rl['user_id'] ?? null;
                                if ($rid && $uid) {
                                    $roleIds[$rid] = true;
                                    $userIdToRoleIds[$uid][] = $rid;
                                }
                            }
                        }
                        if (!empty($roleIds)) {
                            $roleDefsResp = Data::fetch($system, 'roles', [
                                'columns' => [
                                    'roles.role_id AS role_id',
                                    'roles.name AS name',
                                ],
                                'where' => [
                                    'roles.deleted_at' => null,
                                    'roles.is_active' => 1,
                                    'roles.role_id' => ['operator' => 'IN', 'value' => array_keys($roleIds)],
                                ],
                            ]);
                            $roleIdToIsAdmin = [];
                            if ($roleDefsResp['status'] && !empty($roleDefsResp['data'])) {
                                foreach ($roleDefsResp['data'] as $rd) {
                                    $name = strtoupper($rd['name'] ?? '');
                                    $roleIdToIsAdmin[$rd['role_id']] = in_array($name, ['ADMIN', 'SUPREME'], true);
                                }
                            }
                            foreach ($userIdToRoleIds as $uid => $rids) {
                                foreach ($rids as $rid) {
                                    if (!empty($roleIdToIsAdmin[$rid])) {
                                        $adminUserIds[$uid] = true;
                                        break;
                                    }
                                }
                            }
                        }
                        $userMap = [];
                        foreach ($usersResp['data'] as $u) {
                            $uid = $u['user_id'] ?? null;
                            if (!$uid) { continue; }
                            $name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
                            $userMap[$uid] = [
                                'user_id' => $uid,
                                'name' => $name !== '' ? $name : $uid,
                                'is_admin' => !empty($adminUserIds[$uid]),
                            ];
                        }
                        $comments = [];
                        foreach (($commentsResp['data'] ?? []) as $c) {
                            $uid = $c['user_id'] ?? null;
                            $user = $uid && isset($userMap[$uid]) ? $userMap[$uid] : null;
                            $comments[] = [
                                'comment_id' => $c['comment_id'] ?? '',
                                'content' => $c['content'] ?? '',
                                'created_at' => $c['created_at'] ?? '',
                                'user_name' => $user ? $user['name'] : 'Unknown',
                                'is_admin' => $user ? $user['is_admin'] : false,
                            ];
                        }
                        return response()->json(['status' => true, 'comments' => $comments]);
                    }
                    $validator = Validator::make($request->all(), [
                        'news_id' => 'required|string|max:30',
                        'content' => 'required|string|max:2000',
                        'parent_comment_id' => 'nullable|string|max:30',
                    ]);
                    if ($validator->fails()) {
                        return ResponseHelper::moduleError('Validation Failed', $validator->errors()->first(), 422);
                    }
                    $validated = $validator->validated();
                    $validated['comment_id'] = Random::unique(12, 'CMT');
                    $validated['user_id'] = Skeleton::authUser()->user_id;
                    $validated['is_edited'] = 0;
                    $title = 'Comment Added';
                    $message = 'Comment added successfully.';
                    break;
                case 'business_company_news_view':
                    // If list=1, return views list instead of creating view
                    if ($request->boolean('list')) {
                        $newsId = $request->input('news_id');
                        if (empty($newsId)) {
                            return ResponseHelper::moduleError('Validation Failed', 'news_id is required', 422);
                        }
                        $system = Skeleton::getUserSystem();
                        $viewsResp = Data::fetch($system, 'news_views', [
                            'columns' => [
                                'news_views.user_id AS user_id',
                                'news_views.created_at AS created_at',
                            ],
                            'where' => [
                                'news_id' => $newsId,
                            ],
                            'sort' => [
                                ['column' => 'created_at', 'direction' => 'desc'],
                            ],
                            'limit' => 100000,
                        ]);
                        if (!$viewsResp['status']) {
                            return ResponseHelper::moduleError('Fetch Failed', $viewsResp['message'] ?? 'Failed to fetch views', 400);
                        }
                        $userIds = [];
                        foreach (($viewsResp['data'] ?? []) as $row) {
                            $uid = $row['user_id'] ?? null;
                            if ($uid) { $userIds[$uid] = true; }
                        }
                        $userIds = array_keys($userIds);
                        if (empty($userIds)) {
                            return response()->json(['status' => true, 'viewers' => []]);
                        }
                        $usersResp = Data::fetch($system, 'users', [
                            'columns' => [
                                'users.user_id AS user_id',
                                'users.first_name AS first_name',
                                'users.last_name AS last_name',
                            ],
                            'where' => [
                                'users.user_id' => ['operator' => 'IN', 'value' => $userIds],
                            ],
                            'limit' => 100000,
                        ]);
                        if (!$usersResp['status']) {
                            return ResponseHelper::moduleError('Fetch Failed', $usersResp['message'] ?? 'Failed to fetch users', 400);
                        }
                        // Determine admin users
                        $adminUserIds = [];
                        $roleLinksResp = Data::fetch($system, 'user_roles', [
                            'columns' => [
                                'user_roles.user_id AS user_id',
                                'user_roles.role_id AS role_id',
                            ],
                            'where' => [
                                'deleted_at' => null,
                                'is_active' => 1,
                                'user_id' => ['operator' => 'IN', 'value' => $userIds],
                            ],
                        ]);
                        $roleIds = [];
                        $userIdToRoleIds = [];
                        if ($roleLinksResp['status'] && !empty($roleLinksResp['data'])) {
                            foreach ($roleLinksResp['data'] as $rl) {
                                $rid = $rl['role_id'] ?? null;
                                $uid = $rl['user_id'] ?? null;
                                if ($rid && $uid) {
                                    $roleIds[$rid] = true;
                                    $userIdToRoleIds[$uid][] = $rid;
                                }
                            }
                        }
                        if (!empty($roleIds)) {
                            $roleDefsResp = Data::fetch($system, 'roles', [
                                'columns' => [
                                    'roles.role_id AS role_id',
                                    'roles.name AS name',
                                ],
                                'where' => [
                                    'roles.deleted_at' => null,
                                    'roles.is_active' => 1,
                                    'roles.role_id' => ['operator' => 'IN', 'value' => array_keys($roleIds)],
                                ],
                            ]);
                            $roleIdToIsAdmin = [];
                            if ($roleDefsResp['status'] && !empty($roleDefsResp['data'])) {
                                foreach ($roleDefsResp['data'] as $rd) {
                                    $name = strtoupper($rd['name'] ?? '');
                                    $roleIdToIsAdmin[$rd['role_id']] = in_array($name, ['ADMIN', 'SUPREME'], true);
                                }
                            }
                            foreach ($userIdToRoleIds as $uid => $rids) {
                                foreach ($rids as $rid) {
                                    if (!empty($roleIdToIsAdmin[$rid])) {
                                        $adminUserIds[$uid] = true;
                                        break;
                                    }
                                }
                            }
                        }
                        $userMap = [];
                        foreach ($usersResp['data'] as $u) {
                            $uid = $u['user_id'] ?? null;
                            if (!$uid) { continue; }
                            $name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
                            $userMap[$uid] = [
                                'user_id' => $uid,
                                'name' => $name !== '' ? $name : $uid,
                                'is_admin' => !empty($adminUserIds[$uid]),
                            ];
                        }
                        $viewers = [];
                        foreach (($viewsResp['data'] ?? []) as $v) {
                            $uid = $v['user_id'] ?? null;
                            $user = $uid && isset($userMap[$uid]) ? $userMap[$uid] : null;
                            $viewers[] = [
                                'user_id' => $uid,
                                'user_name' => $user ? $user['name'] : 'Unknown',
                                'is_admin' => $user ? $user['is_admin'] : false,
                                'viewed_at' => $v['created_at'] ?? '',
                            ];
                        }
                        return response()->json(['status' => true, 'viewers' => $viewers]);
                    }
                    $newsId = $request->input('news_id');
                    $system = Skeleton::getUserSystem();
                    $userId = Skeleton::authUser()->user_id ?? null;
                    if (!$newsId) {
                        return ResponseHelper::moduleError('Invalid', 'news_id is required', 422);
                    }
                    // Only count views for authenticated users (one per user per post)
                    if (empty($userId)) {
                        return response()->json(['status' => true]);
                    }
                    // If already viewed by this user, do nothing
                    $existing = Data::fetch($system, 'news_views', [
                        'columns' => ['id'],
                        'where'   => [
                            'news_id' => $newsId,
                            'user_id' => $userId,
                        ],
                        'limit'   => 1,
                    ]);
                    if (!empty($existing['data'])) {
                        return response()->json(['status' => true]);
                    }
                    // Create the view record
                    $validated = [
                        'news_id'    => $newsId,
                        'user_id'    => $userId,
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->header('User-Agent'),
                    ];
                    $result = Data::insert($system, 'news_views', $validated, $reqSet['token'] ?? null);
                    if (!$result['status']) {
                        return ResponseHelper::moduleError('View Track Failed', $result['message'] ?? 'Failed to track view', 400);
                    }
                    $title = 'View Tracked';
                    $message = 'View tracked successfully.';
                    $store = false;
                    break;
                default:
                    return ResponseHelper::moduleError('Invalid Configuration', 'The configuration key is not supported.', 400);
            }
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (END) <<<                                    *
             *                                                                                                  *
             ****************************************************************************************************/
            if ($store) {
                if ($byMeta) {
                    $validated['created_by'] = Skeleton::authUser()->user_id;
                }
                if ($timestampMeta) {
                    $validated['created_at'] = $validated['updated_at'] = now();
                }
            // Insert data
            $result = Data::insert($reqSet['system'], $reqSet['table'], $validated);
            }
            // Generate response
            return response()->json(['status' => $result['status'], 'reload_table' => $reloadTable, 'reload_card' => $reloadCard, 'reload_page' => $reloadPage, 'hold_popup' => $holdPopup, 'token' => $reqSet['token'], 'affected' => $result['status'] ? $result['data']['id'] ?? '' : '-', 'title' => $result['status'] ? $title : 'Failed', 'message' => $result['status'] ? $message : $result['message']]);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while saving the data.', 500);
        }
    }
}