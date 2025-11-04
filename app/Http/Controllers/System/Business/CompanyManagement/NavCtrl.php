<?php
namespace App\Http\Controllers\System\Business\CompanyManagement;
use App\Http\Controllers\Controller;
use App\Facades\{Skeleton, Data, Helper, Developer, BusinessDB, Scope};
use App\Http\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Config, View, Cache};
use Carbon\{Carbon, CarbonPeriod};
/**
 * Controller for rendering navigation views for the CompanyManagement module.
 */
class NavCtrl extends Controller
{
    /**
     * Renders dashboard-related views based on route parameters.
     *
     * @param Request $request HTTP request object
     * @param array $params Route parameters (module, section, item, token)
     * @return \Illuminate\View\View|JsonResponse
     */
    public function index(Request $request, array $params)
    {
        try {
            // Extract route parameters
            $baseView = 'system.business.company-management';
            $module = $params['module'] ?? 'CompanyManagement';
            $section = $params['section'] ?? null;
            $item = $params['item'] ?? null;
            $token = $params['token'] ?? null;
            // Build view path
            $viewPath = $baseView;
            if ($section) {
                $viewPath .= "." . $section;
                if ($item) {
                    $viewPath .= "." . $item;
                }
            } else {
                $viewPath .= '.index';
            }
            // Extract view name and normalize path
            $viewName = strtolower(str_replace(' ', '-', str_replace("{$baseView}.", '', $viewPath)));
            $viewPath = strtolower(str_replace(' ', '-', $viewPath));
            // Initialize base data
            $data = [
                'status' => true,
            ];
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (START) <<<                                  *
             *                                                                                                  *
             ****************************************************************************************************/
            // Handle different view names
           switch ($viewName) {
				case 'index':
					$data['dashboard_list'] = [];
					break;
				case 'holidays':
					 $userRole=Skeleton::authUser('roles');
                    $role_id= array_key_first($userRole);
                    if($role_id=='ADMIN'){
						$companies = Data::fetch('business', 'companies', [
							'where' => ['business_id' => Skeleton::authUser()->business_id ?? '' ]
						]);
					}	
					else{
						$companies = Data::fetch('business', 'companies', [
							'where' => ['business_id' => Skeleton::authUser()->business_id ?? '', 'company_id' => Skeleton::authUser()->company_id]
						]);
					}
					if (!$companies['status']) {
						return ResponseHelper::moduleError('User Fetch Failed', $companies['message'], 400);
					}
					$companyEvents = Helper::holidays();
					$data = [
						'companies' => array_map(function ($company) use ($companyEvents) {
							$companyObj = (object) $company;
							$companyId  = (string) $company['company_id'];

							$holidays = BusinessDB::table('company_holidays')->where('company_id', $companyId)->where('is_active', 1)->whereNull('deleted_at')
								->get(['start_date','end_date','recurring_type','recurring_day']);

							$yearStart  = now()->startOfYear();
							$yearEnd    = now()->endOfYear();
							$monthStart = now()->startOfMonth();
							$monthEnd   = now()->endOfMonth();

							$allDays = collect();

							foreach ($holidays as $h) {
								$start = Carbon::parse($h->start_date);
								$end   = Carbon::parse($h->end_date);

								// clamp to current year only
								if ($start < $yearStart) $start = $yearStart->copy();
								if ($end > $yearEnd) $end = $yearEnd->copy();
								if ($start > $end) continue;

								if ($h->recurring_type === 'weekly' && $h->recurring_day) {
									$dayNum = array_search(strtolower($h->recurring_day), 
										['sunday','monday','tuesday','wednesday','thursday','friday','saturday']);

									foreach (CarbonPeriod::create($start, $end) as $day) {
										if ($day->dayOfWeek === $dayNum) {
											$allDays->push($day->format('Y-m-d'));
										}
									}
								} else {
									foreach (CarbonPeriod::create($start, $end) as $day) {
										$allDays->push($day->format('Y-m-d'));
									}
								}
							}
							$uniqueDays = $allDays->unique()->values();

							// total holidays in current year
							$total = $uniqueDays->count();
							$thisMonth = $uniqueDays->filter(fn($d) => Carbon::parse($d)->between($monthStart, $monthEnd))->count();
							$workingDays = max(0, now()->daysInMonth - $thisMonth);
							$companyObj->events       = $companyEvents[$companyId] ?? [];
							$companyObj->total        = $total;        // unique holiday days in current year
							$companyObj->this_month   = $thisMonth;   // unique holiday days in current month
							$companyObj->working_days = $workingDays; // working days in current month

							return $companyObj;
						}, $companies['data'] ?? [])
					];


					break;
				case 'company':
					
                    $companies = Data::fetch('business', 'companies', ['business_id' => Skeleton::authUser()->business_id ?? '']);

					if (!$companies['status'] && !empty($companies['data'])) {
						return ResponseHelper::moduleError('User Fetch Failed', $companies['message'], 400);
					}

					$companyList = [];
					foreach ($companies['data'] ?? [] as $company) {
						$scopes = Scope::getScopePaths('all', $company['company_id'], true);
						$userCount = BusinessDB::table('users')
							->where('company_id', $company['company_id'])
							->whereNull('deleted_at')
							->count();
						$roleCount = BusinessDB::table('roles')
							->whereNull('deleted_at')
							->count();
						$polociesCount = BusinessDB::table('company_policies')
							->where('company_id', $company['company_id'])
							->whereNull('deleted_at')
							->count();
						$company['users_count'] = $userCount;
						$company['roles_count'] = $roleCount;
						$company['scopes']      = $scopes;
						$company['policy_count']= $polociesCount;
						$company['cover']= Skeleton::authUser()->cover;
						
						$companyList[] = $company;
					}

					$data = [
						'companies' => $companyList,
					];
				break;


				case 'news':
					$system = Skeleton::getUserSystem();
					$currentUserId = Skeleton::authUser()->user_id ?? null;
					// Determine if the current viewer is an admin (role-based)
					$viewerIsAdmin = false;
					try {
						$skeletonService = app(\App\Services\SkeletonService::class);
						$role = $skeletonService->authUser('role');
						$roleName = strtoupper($role['name'] ?? '');
						$roleId = strtoupper($role['role_id'] ?? '');
						$viewerIsAdmin = in_array($roleName, ['ADMIN', 'SUPREME'], true) || in_array($roleId, ['ADMIN', 'SUPREME'], true);
					} catch (Exception $e) {
					}
					$dev = Config::get('skeleton.developer_mode');
					if ($dev) Developer::info('Announcements: start', ['system' => $system, 'user_id' => $currentUserId]);
					// Get user's scope_id
					$userScopeId = null;
					if ($currentUserId) {
						$scopeResult = Data::fetch($system, 'scope_mapping', [
							'columns' => ['scope_id'],
							'where' => ['user_id' => $currentUserId],
							'limit' => 1
						]);
						if ($dev) Developer::info('Announcements: scope lookup', ['result' => $scopeResult]);
						if ($scopeResult['status'] && !empty($scopeResult['data'])) {
							$userScopeId = $scopeResult['data'][0]['scope_id'] ?? null;
						}
					}
					// Fetch latest news posts - filter by user's scope (or show all if no scope assigned)
					$newsWhere = ['deleted_at' => null];
					if ($userScopeId) {
						$newsWhere['scope_id'] = $userScopeId;
					}
					$newsResponse = Data::fetch($system, 'news', [
						'columns' => [
							'news.news_id AS news_id',
							'news.user_id AS user_id',
							'news.content AS content',
							'news.file_id AS file_id',
							'news.created_at AS created_at',
						],
						'where' => $newsWhere,
						'sort' => [
							['column' => 'created_at', 'direction' => 'desc'],
						],
						'pagination' => [
							'limit' => 10,
							'page' => 1,
						],
					]);
					if ($dev) Developer::info('Announcements: news fetch', ['where' => $newsWhere, 'status' => $newsResponse['status'] ?? null, 'count' => isset($newsResponse['data']) ? count($newsResponse['data']) : 0]);
					$posts = $newsResponse['status'] ? ($newsResponse['data'] ?? []) : [];
					$newsIds = array_values(array_filter(array_column($posts, 'news_id')));
					// Fetch high priority posts separately
					$highPriorityPosts = [];
					if (!empty($userScopeId)) {
						$highPriorityResponse = Data::fetch($system, 'news', [
							'columns' => [
								'news.news_id AS news_id',
								'news.user_id AS user_id',
								'news.content AS content',
								'news.file_id AS file_id',
								'news.created_at AS created_at',
							],
							'where' => [
								'deleted_at' => null,
								'scope_id' => $userScopeId,
								'high_priority' => 'yes',
								'status' => 'active',
							],
							'sort' => [
								['column' => 'created_at', 'direction' => 'desc'],
							],
							'pagination' => [
								'limit' => 5,
								'page' => 1,
							],
						]);
						if ($dev) Developer::info('Announcements: high priority fetch', ['status' => $highPriorityResponse['status'] ?? null, 'count' => isset($highPriorityResponse['data']) ? count($highPriorityResponse['data']) : 0]);
						$highPriorityPosts = $highPriorityResponse['status'] ? ($highPriorityResponse['data'] ?? []) : [];
					}
					// Fetch highest liked posts separately
					$highestLikedPosts = [];
					if (!empty($userScopeId)) {
						// First get the news IDs with highest like counts
						$topLikedNewsIds = [];
						$likesCountResp = Data::fetch($system, 'news_likes', [
							'columns' => [
								'news_likes.news_id AS news_id',
								'COUNT(*) AS like_count',
							],
							'where' => [
								'deleted_at' => null,
							],
							'group_by' => ['news_id'],
							'sort' => [
								['column' => 'like_count', 'direction' => 'desc'],
							],
							'pagination' => [
								'limit' => 5,
								'page' => 1,
							],
						]);
						if ($likesCountResp['status'] && !empty($likesCountResp['data'])) {
							$topLikedNewsIds = array_column($likesCountResp['data'], 'news_id');
							if (!empty($topLikedNewsIds)) {
								$highestLikedResponse = Data::fetch($system, 'news', [
									'columns' => [
										'news.news_id AS news_id',
										'news.user_id AS user_id',
										'news.content AS content',
										'news.file_id AS file_id',
										'news.created_at AS created_at',
									],
									'where' => [
										'deleted_at' => null,
										'scope_id' => $userScopeId,
										'status' => 'active',
										'news_id' => ['operator' => 'IN', 'value' => $topLikedNewsIds],
									],
									'sort' => [
										['column' => 'created_at', 'direction' => 'desc'],
									],
								]);
								if ($dev) Developer::info('Announcements: highest liked fetch', ['status' => $highestLikedResponse['status'] ?? null, 'count' => isset($highestLikedResponse['data']) ? count($highestLikedResponse['data']) : 0]);
								$highestLikedPosts = $highestLikedResponse['status'] ? ($highestLikedResponse['data'] ?? []) : [];
							}
						}
					}
					$likesMap = [];
					$likedByUser = [];
					$commentsMap = [];
					$viewsMap = [];
					$usersMap = [];
					$fileIds = [];
					$adminUserIds = [];
					if (!empty($posts)) {
						// Collect IDs
						$userIds = array_values(array_unique(array_filter(array_column($posts, 'user_id'))));
						$fileIds = array_values(array_unique(array_filter(array_column($posts, 'file_id'))));
						if ($dev) Developer::info('Announcements: collected IDs', ['user_ids' => $userIds, 'file_ids' => $fileIds]);
						// Determine which users are admins
						if (!empty($userIds)) {
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
							if ($dev) Developer::info('Announcements: role links', ['status' => $roleLinksResp['status'] ?? null, 'count' => isset($roleLinksResp['data']) ? count($roleLinksResp['data']) : 0]);
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
								if ($dev) Developer::info('Announcements: role defs', ['status' => $roleDefsResp['status'] ?? null, 'count' => isset($roleDefsResp['data']) ? count($roleDefsResp['data']) : 0]);
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
						}
						// Likes, Comments, Views fetch logs minimal
						if ($dev) Developer::info('Announcements: fetching counters', ['news_ids' => $newsIds]);
						// Likes for these posts
						$likesResp = Data::fetch($system, 'news_likes', [
							'columns' => [
								'news_likes.news_id AS news_id',
								'news_likes.user_id AS user_id',
							],
							'where' => [
								'news_id' => ['operator' => 'IN', 'value' => $newsIds],
								'deleted_at' => null,
							],
							'limit' => 100000,
						]);
						if ($likesResp['status']) {
							foreach ($likesResp['data'] as $like) {
								$nid = $like['news_id'] ?? null;
								$uid = $like['user_id'] ?? null;
								if (!$nid) {
									continue;
								}
								$likesMap[$nid] = ($likesMap[$nid] ?? 0) + 1;
								if ($currentUserId && $uid === $currentUserId) {
									$likedByUser[$nid] = true;
								}
							}
						}
						// Comments for these posts
						$commentsResp = Data::fetch($system, 'news_comments', [
							'columns' => [
								'news_comments.news_id AS news_id',
								'news_comments.comment_id AS comment_id',
							],
							'where' => [
								'news_id' => ['operator' => 'IN', 'value' => $newsIds],
							],
							'limit' => 100000,
						]);
						if ($commentsResp['status']) {
							foreach ($commentsResp['data'] as $comment) {
								$nid = $comment['news_id'] ?? null;
								if (!$nid) {
									continue;
								}
								$commentsMap[$nid] = ($commentsMap[$nid] ?? 0) + 1;
							}
						}
						// Views for these posts
						$viewsResp = Data::fetch($system, 'news_views', [
							'columns' => [
								'news_views.news_id AS news_id',
								'news_views.id AS id',
							],
							'where' => [
								'news_id' => ['operator' => 'IN', 'value' => $newsIds],
							],
							'limit' => 100000,
						]);
						if ($viewsResp['status']) {
							foreach ($viewsResp['data'] as $view) {
								$nid = $view['news_id'] ?? null;
								if (!$nid) {
									continue;
								}
								$viewsMap[$nid] = ($viewsMap[$nid] ?? 0) + 1;
							}
						}
						// Users map (basic name & avatar/profile)
						if (!empty($userIds)) {
							$usersResp = Data::fetch($system, 'users', [
								'columns' => [
									'users.user_id AS user_id',
									'users.first_name AS first_name',
									'users.last_name AS last_name',
									'users.profile AS profile',
								],
								'where' => [
									'users.user_id' => ['operator' => 'IN', 'value' => $userIds],
								],
							]);
							$profileFileIds = [];
							if ($usersResp['status']) {
								foreach ($usersResp['data'] as $u) {
									$pid = $u['profile'] ?? null;
									if (!empty($pid)) {
										$profileFileIds[$pid] = true;
									}
								}
							}
							$profileUrlMap = [];
							// Try cache first per file_id
							$toFetch = [];
							foreach (array_keys($profileFileIds) as $fid) {
								$cached = Cache::get('avatar_url:' . $fid);
								if ($cached) {
									$profileUrlMap[$fid] = $cached;
								} else {
									$toFetch[] = $fid;
								}
							}
							if (!empty($toFetch)) {
								$filesResp2 = Data::fetch('central', 'files', [
									'columns' => [
										'files.file_id AS file_id',
										'files.path AS file_path',
										'files.is_public AS is_public',
									],
									'where' => [
										'files.file_id' => ['operator' => 'IN', 'value' => $toFetch],
									],
								]);
								if ($filesResp2['status']) {
									foreach ($filesResp2['data'] as $f) {
										$path = $f['file_path'] ?? null;
										$isPublic = (bool)($f['is_public'] ?? false);
										$url = ($isPublic && $path) ? asset('storage/' . ltrim($path, '/\\')) : null;
										$profileUrlMap[$f['file_id']] = $url;
										Cache::put('avatar_url:' . $f['file_id'], $url, now()->addMinutes(10));
									}
								}
							}
							if ($usersResp['status']) {
								$defaultAvatar = asset('treasury/img/common/default/profile/default-1.svg');
								foreach ($usersResp['data'] as $u) {
									$pid = $u['profile'] ?? null;
									$profileUrl = $pid && isset($profileUrlMap[$pid]) ? $profileUrlMap[$pid] : $defaultAvatar;
									$usersMap[$u['user_id']] = [
										'first_name' => $u['first_name'] ?? '',
										'last_name' => $u['last_name'] ?? '',
										'profile_url' => $profileUrl,
									];
								}
							}
						}
						// Optional: resolve file URLs for public files
						$fileUrlMap = [];
						if (!empty($fileIds)) {
							$filesResp = Data::fetch('central', 'files', [
								'columns' => [
									'files.file_id AS file_id',
									'files.path AS file_path',
									'files.is_public AS is_public',
								],
								'where' => [
									'files.file_id' => ['operator' => 'IN', 'value' => $fileIds],
								],
							]);
							if ($dev) Developer::info('Announcements: files fetched', ['status' => $filesResp['status'] ?? null, 'count' => isset($filesResp['data']) ? count($filesResp['data']) : 0]);
							if ($filesResp['status']) {
								foreach ($filesResp['data'] as $f) {
									$path = $f['file_path'] ?? null;
									$isPublic = (bool)($f['is_public'] ?? false);
									$fileUrlMap[$f['file_id']] = ($isPublic && $path) ? asset('storage/' . ltrim($path, '/\\')) : null;
								}
							}
						}
						// Enrich posts
						foreach ($posts as &$p) {
							$uid = $p['user_id'] ?? null;
							$nid = $p['news_id'] ?? null;
							$fileId = $p['file_id'] ?? null;
							$user = $uid && isset($usersMap[$uid]) ? $usersMap[$uid] : null;
							$p['author_name'] = $user ? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) : 'Unknown';
							$p['author_avatar'] = $user && !empty($user['profile_url']) ? $user['profile_url'] : null;
							$p['likes_count'] = (int)($likesMap[$nid] ?? 0);
							$p['comments_count'] = (int)($commentsMap[$nid] ?? 0);
							$p['views_count'] = (int)($viewsMap[$nid] ?? 0);
							$p['is_liked'] = (bool)($likedByUser[$nid] ?? false);
							$p['file_url'] = $fileId && isset($fileUrlMap[$fileId]) ? $fileUrlMap[$fileId] : null;
							$p['is_mine'] = $currentUserId && $uid === $currentUserId;
							// is_admin = whether the AUTHOR is admin (for badge)
							$p['is_admin'] = !empty($adminUserIds[$uid]);
							// can_delete = viewer is admin OR viewer is the author
							$p['can_delete'] = $viewerIsAdmin || ($currentUserId && $uid === $currentUserId);
						}
						unset($p);
						// Enrich high priority posts with the same data
						if (!empty($highPriorityPosts)) {
							foreach ($highPriorityPosts as &$hp) {
								$uid = $hp['user_id'] ?? null;
								$fileId = $hp['file_id'] ?? null;
								$user = $uid && isset($usersMap[$uid]) ? $usersMap[$uid] : null;
								$hp['author_name'] = $user ? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) : 'Unknown';
								$hp['author_avatar'] = $user && !empty($user['profile_url']) ? $user['profile_url'] : null;
								$hp['file_url'] = $fileId && isset($fileUrlMap[$fileId]) ? $fileUrlMap[$fileId] : null;
								$hp['is_admin'] = !empty($adminUserIds[$uid]);
							}
							unset($hp);
						}
						// Enrich highest liked posts with the same data
						if (!empty($highestLikedPosts)) {
							foreach ($highestLikedPosts as &$hl) {
								$uid = $hl['user_id'] ?? null;
								$fileId = $hl['file_id'] ?? null;
								$user = $uid && isset($usersMap[$uid]) ? $usersMap[$uid] : null;
								$hl['author_name'] = $user ? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) : 'Unknown';
								$hl['author_avatar'] = $user && !empty($user['profile_url']) ? $user['profile_url'] : null;
								$hl['file_url'] = $fileId && isset($fileUrlMap[$fileId]) ? $fileUrlMap[$fileId] : null;
								$hl['is_admin'] = !empty($adminUserIds[$uid]);
							}
							unset($hl);
						}
					}
					if ($dev) Developer::info('Announcements: end', ['post_count' => count($posts), 'high_priority_count' => count($highPriorityPosts), 'highest_liked_count' => count($highestLikedPosts)]);
					$data['posts'] = $posts;
					$data['high_priority_posts'] = $highPriorityPosts;
					$data['highest_liked_posts'] = $highestLikedPosts;
					break;
				default:
					break;
			}
            /****************************************************************************************************
             *                                                                                                  *
             *                             >>> MODIFY THIS SECTION (END) <<<                                    *
             *                                                                                                  *
             ****************************************************************************************************/
            // Render view if it exists
            if (View::exists($viewPath)) {
                return view($viewPath, compact('data'));
            }
            // Return 404 view if view does not exist
            return response()->view('errors.404', ['status' => false, 'title' => 'Page Not Found', 'message' => 'The requested page does not exist.'], 404);
        } catch (Exception $e) {
            return ResponseHelper::moduleError('Error', Config::get('skeleton.developer_mode') ? $e->getMessage() : 'An error occurred while loading the page.', 500);
        }
    }
}