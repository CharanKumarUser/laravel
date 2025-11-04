<?php
namespace App\Services;
use App\Facades\{Data, Developer, Helper, Skeleton};
use Illuminate\Support\Facades\Auth;
use Exception;
/**
 * Service to handle scope hierarchy, circular checks, and path generation.
 */
class ScopeService
{
    private const SCOPE_TABLE = 'scopes';
    private const SCOPE_USER_TABLE = 'scope_mapping';
    /**
     * Check if assigning parent creates a circular reference.
     *
     * @param string $parentId The parent scope ID to check
     * @param string|null $currentScopeId The scope ID being validated (null for creation)
     * @return bool True if circular reference detected, false otherwise
     */
    public function isCircularReference(string $parentId, ?string $currentScopeId): bool
    {
        if (!$parentId || $parentId === $currentScopeId) {
            return true;
        }
        $system = Skeleton::getUserSystem();
        $response = Data::fetch($system, self::SCOPE_TABLE, [
            'select' => ['scope_id', 'parent_id'],
            'where' => ['deleted_at' => null, 'is_active' => 1]
        ], 'all');
        if (!$response['status']) {
            throw new Exception($response['message']);
        }
        $scopes = $response['data'];
        $visited = [$parentId];
        $current = $parentId;
        while ($current) {
            $found = false;
            foreach ($scopes as $scope) {
                if ($scope['scope_id'] === $current && $scope['parent_id']) {
                    if ($scope['parent_id'] === $currentScopeId || in_array($scope['parent_id'], $visited)) {
                        return true;
                    }
                    $visited[] = $scope['parent_id'];
                    $current = $scope['parent_id'];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                break;
            }
        }
        return false;
    }
    /**
     * Get scope paths or hierarchy.
     *
     * @param string|null $scope 'all', 'self', or 'open_scopes' (defaults to 'all')
     * @param string|null $parentIds Comma-separated scope IDs to treat as roots
     * @param bool $asJson Return as JSON string or PHP array
     * @return array|string
     */
    public function getScopePaths(?string $scope = 'all', ?string $parentIds = null, bool $asJson = false)
    {
        $scope = $scope ?? 'all';
        $system = Skeleton::getUserSystem();
        // Fetch scopes
        $scopes = $this->fetchScopes($scope, $system);
        // Parse root IDs
        $roots = $parentIds ? array_map('trim', explode(',', $parentIds)) : null;
        // Build hierarchy
        $tree = $this->buildHierarchy($scopes, $roots);
        // Convert to path array if not JSON
        if (!$asJson) {
            $paths = [];
            $this->buildPaths($tree, '', $paths);
            return $paths;
        }
        return json_encode($tree, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    /**
     * Fetch scopes based on scope type.
     */
    private function fetchScopes(string $scope, string $system): array
    {
        $columns = [
            'id',
            'sno',
            'scope_id',
            'code',
            'name',
            'group',
            'description',
            'parent_id',
            'background',
            'color',
            'data',
            'schema',
            'snap',
            'allow_form',
            'is_active'
        ];
        if ($scope === 'self') {
            $userId = Auth::id();
            if (!$userId) {
                throw new Exception('User not authenticated');
            }
            $response = Data::fetch($system, self::SCOPE_USER_TABLE, [
                'select' => ['scope_id'],
                'where' => ['user_id' => $userId, 'deleted_at' => null]
            ], 'first');

            if (!$response['status']) {
                throw new Exception($response['message']);
            }

            $scopeIds = [];
            if (!empty($response['data']) && is_array($response['data'])) {
                $scopeIds = array_column($response['data'], 'scope_id');
            }
            if (empty($scopeIds)) { 
                return [];
            }
            $response = Data::fetch(
                $system,
                self::SCOPE_TABLE,
                [
                    'select'  => $columns,
                    'whereIn' => ['scope_id' => $scopeIds],
                    'where'   => ['deleted_at' => null, 'is_active' => 1],
                    'orderBy' => ['sno' => 'ASC']
                ],
                'all'
            );

        } elseif ($scope === 'open_scopes') {
            $userId = Auth::id();
            $scopeIds = [];
            if ($userId) {
                $response = Data::fetch($system, self::SCOPE_USER_TABLE, [
                    'select' => ['scopes', 'restrict'],
                    'where' => ['user_id' => $userId, 'deleted_at' => null]
                ], 'first');
                if ($response['status'] && $response['data']) {
                    $scopes = json_decode($response['data']['scopes'] ?? '[]', true) ?? [];
                    $restrict = json_decode($response['data']['restrict'] ?? '{}', true) ?? [];
                    $scopeIds = empty($restrict) ? $scopes : array_diff($scopes, array_keys($restrict));
                }
            }
            // If no user or no specific scopes, fetch all active scopes
            if (empty($scopeIds)) {
                $response = Data::fetch($system, self::SCOPE_TABLE, [
                    'select' => $columns,
                    'where' => ['deleted_at' => null, 'is_active' => 1],
                    'orderBy' => ['sno' => 'ASC']
                ], 'all');
            } else {
                $response = Data::fetch($system, self::SCOPE_TABLE, [
                    'select' => $columns,
                    'whereIn' => ['scope_id' => $scopeIds],
                    'where' => ['deleted_at' => null, 'is_active' => 1],
                    'orderBy' => ['sno' => 'ASC']
                ], 'all');
            }
        } else {
            $response = Data::fetch($system, self::SCOPE_TABLE, [
                'select' => $columns,
                'where' => ['deleted_at' => null, 'is_active' => 1],
                'orderBy' => ['sno' => 'ASC']
            ], 'all');
        }
        if (!$response['status']) {
            throw new Exception($response['message']);
        }
        return $response['data'];
    }
    /**
     * Build hierarchy tree from scopes, optionally rooted at specific IDs.
     */
    private function buildHierarchy(array $scopes, ?array $roots = null): array
    {
        // Index all scopes by scope_id
        $map = [];
        foreach ($scopes as $scope) {
            $map[$scope['scope_id']] = $scope;
        }

        // Recursive builder function
        $buildTree = function ($parentId) use (&$buildTree, $map) {
            $children = [];

            foreach ($map as $scope) {
                if ($scope['parent_id'] === $parentId) {
                    $child = [
                        'id'          => $scope['scope_id'], // ✅ use scope_id as id
                        'code'        => $scope['code'],
                        'name'        => $scope['name'],
                        'group'       => $scope['group'],
                        'description' => $scope['description'],
                        'background'  => $scope['background'] ?? '#FFD700',
                        'color'       => $scope['color'] ?? '#000000',
                        'data'        => $scope['data'] ? json_decode($scope['data'], true) : null,
                        'schema'      => $scope['schema'] ? json_decode($scope['schema'], true) : null,
                        'snap'        => $scope['snap'] ? json_decode($scope['snap'], true) : null,
                        'allow_form'  => $scope['allow_form'],
                        'is_active'   => $scope['is_active'],
                        'children'    => $buildTree($scope['scope_id']), // 👈 recursive call
                    ];
                    $children[] = $child;
                }
            }

            // Optional: sort children by sno if available
            usort($children, fn($a, $b) => ($a['sno'] ?? 0) <=> ($b['sno'] ?? 0));

            return $children;
        };

        $tree = [];

        // Build tree for specific roots or all top-level nodes
        if ($roots !== null && count($roots) > 0) {
            foreach ($roots as $rootId) {
                if (isset($map[$rootId])) {
                    $scope = $map[$rootId];
                    $tree[] = [
                        'id'          => $scope['scope_id'],
                        'code'        => $scope['code'],
                        'name'        => $scope['name'],
                        'group'       => $scope['group'],
                        'description' => $scope['description'],
                        'background'  => $scope['background'] ?? '#FFD700',
                        'color'       => $scope['color'] ?? '#000000',
                        'data'        => $scope['data'] ? json_decode($scope['data'], true) : null,
                        'schema'      => $scope['schema'] ? json_decode($scope['schema'], true) : null,
                        'snap'        => $scope['snap'] ? json_decode($scope['snap'], true) : null,
                        'allow_form'  => $scope['allow_form'],
                        'is_active'   => $scope['is_active'],
                        'children'    => $buildTree($rootId),
                    ];
                }
            }
        } else {
            // Build full tree from top-level scopes
            foreach ($map as $scope) {
                if (empty($scope['parent_id'])) {
                    $tree[] = [
                        'id'          => $scope['scope_id'],
                        'code'        => $scope['code'],
                        'name'        => $scope['name'],
                        'group'       => $scope['group'],
                        'description' => $scope['description'],
                        'background'  => $scope['background'] ?? '#FFD700',
                        'color'       => $scope['color'] ?? '#000000',
                        'data'        => $scope['data'] ? json_decode($scope['data'], true) : null,
                        'schema'      => $scope['schema'] ? json_decode($scope['schema'], true) : null,
                        'snap'        => $scope['snap'] ? json_decode($scope['snap'], true) : null,
                        'allow_form'  => $scope['allow_form'],
                        'is_active'   => $scope['is_active'],
                        'children'    => $buildTree($scope['scope_id']),
                    ];
                }
            }
        }

        return $tree;
    }

    /**
     * Build flat paths from hierarchy.
     */
    private function buildPaths(array $nodes, string $prefix, array &$paths): void
    {
        foreach ($nodes as $node) {
            $scopeName = strtolower(str_replace(' ', '-', trim($node['name'])));
            $fullPath = $prefix ? $prefix . '->' . $scopeName : $scopeName;
            $paths[$node['id']] = $fullPath;
            // Check if children is an array before recursing
            if (isset($node['children']) && is_array($node['children']) && !empty($node['children'])) {
                $this->buildPaths($node['children'], $fullPath, $paths);
            }
        }
    }
    /**
     * Clean empty children arrays for JSON compatibility.
     */
    private function cleanEmptyChildren(array &$nodes): void
    {
        foreach ($nodes as &$node) {
            if (isset($node['children']) && is_array($node['children']) && !empty($node['children'])) {
                $this->cleanEmptyChildren($node['children']);
            } else {
                $node['children'] = new \stdClass();
            }
        }
    }
    /**
     * Get path for a single scope by ID.
     *
     * @param string $scopeId The scope ID
     * @return string The scope path
     */
    public function getScopePath(string $scopeId): string
    {
        if (empty($scopeId)) {
            throw new Exception('Scope ID is required.');
        }
        $paths = [];
        $this->buildPaths($this->getScopePaths('all', null, false), '', $paths);
        if (!isset($paths[$scopeId])) {
            throw new Exception("Scope not found: $scopeId");
        }
        return $paths[$scopeId];
    }
    /**
     * Get parent scopes of the given scope ID up to a certain level.
     */
    public function getParents(string $scopeId, ?int $level = null): array
    {
        if (empty($scopeId)) {
            throw new Exception('Scope ID is required.');
        }
        $system = Skeleton::getUserSystem();
        $scopes = $this->fetchScopes('all', $system);
        $map = [];
        foreach ($scopes as $scope) {
            $map[$scope['scope_id']] = $scope;
        }
        if (!isset($map[$scopeId])) {
            throw new Exception("Scope not found: $scopeId");
        }
        $result = [$scopeId => $map[$scopeId]['name']];
        $depth = 0;
        $currentId = $scopeId;
        while (isset($map[$currentId]['parent_id'])) {
            $parentId = $map[$currentId]['parent_id'];
            if (!isset($map[$parentId])) break;
            $result[$parentId] = $map[$parentId]['name'];
            $currentId = $parentId;
            $depth++;
            if ($level !== null && $depth >= $level) {
                break;
            }
        }
        return $result;
    }
    /**
     * Get parent most scope of the given scope ID.
     */
    public function getMostParentScope(string $scopeId): array
    {
        if (empty($scopeId)) {
            throw new Exception('Scope ID is required.');
        }

        $system = Skeleton::getUserSystem();
        $scopes = $this->fetchScopes('all', $system);

        $map = [];
        foreach ($scopes as $scope) {
            $map[$scope['scope_id']] = $scope;
        }

        if (!isset($map[$scopeId])) {
            throw new Exception("Scope not found: $scopeId");
        }

        $currentId = $scopeId;

        // Traverse up until no parent exists
        while (isset($map[$currentId]['parent_id']) && !empty($map[$currentId]['parent_id'])) {
            $parentId = $map[$currentId]['parent_id'];
            if (!isset($map[$parentId])) break;
            $currentId = $parentId;
        }

        // Return the top-most parent scope
        return [
            'scope_id' => $currentId,
            'name' => $map[$currentId]['name']
        ];
    }

    /**
     * Get all accessible scope IDs for the current authenticated user.
     * If the user is ADMIN, return all scopes.
     *
     * @param bool $includeSelf Include current scope in the result (default false)
     * @return array
     */
    public function userChildScopes(?bool $includeSelf = false): array
    {
        $user = Skeleton::getAuthenticatedUser();
        $roles= $user['roles'];
        $roleId = array_key_first($roles);

        if ($roleId === 'ADMIN') {
            $allScopes = $this->getScopePaths('all', null, false);
            return array_keys($allScopes);
        }

        // Non-admin: get only child scopes of user's scope
        $scopeId =Skeleton::authUser()->scope_id;
        $childScopes = self::getChilds($scopeId, null, $includeSelf);

        return array_keys($childScopes);
    }


    /**
     * Get child scopes of the given scope ID up to a certain level.
     */
    public function getChilds(string $scopeId, ?int $level = null, ?bool $includeSelf = false): array
    {
        if (empty($scopeId)) {
            throw new Exception('Scope ID is required.');
        }
        $system = Skeleton::getUserSystem();
        $scopes = $this->fetchScopes('all', $system);
        $childrenMap = [];
        $map = [];
        foreach ($scopes as $scope) {
            $map[$scope['scope_id']] = $scope;
            $pid = $scope['parent_id'];
            if ($pid) {
                $childrenMap[$pid][] = $scope;
            }
        }
        if (!isset($map[$scopeId])) {
            throw new Exception("Scope not found: $scopeId");
        }
         $result = [];
        // If includeSelf is true, add the current scope first
        if ($includeSelf) {
            $result[$scopeId] = $map[$scopeId]['name'];
        }
        $this->collectChildScopes($scopeId, $childrenMap, $result, 0, $level);
        return $result;
    }
    private function collectChildScopes(string $parentId, array $childrenMap, array &$result, int $depth, ?int $maxDepth): void
    {
        if (!isset($childrenMap[$parentId])) {
            return;
        }
        foreach ($childrenMap[$parentId] as $child) {
            $result[$child['scope_id']] = $child['name'];
            if ($maxDepth === null || $depth + 1 < $maxDepth) {
                $this->collectChildScopes($child['scope_id'], $childrenMap, $result, $depth + 1, $maxDepth);
            }
        }
    }
    /**
     * Get siblings of the given scope ID up to a certain level.
     */
    public function getSiblings(string $scopeId, ?int $level = null): array
    {
        if (empty($scopeId)) {
            throw new Exception('Scope ID is required.');
        }
        $system = Skeleton::getUserSystem();
        $scopes = $this->fetchScopes('all', $system);
        $map = [];
        foreach ($scopes as $scope) {
            $map[$scope['scope_id']] = $scope;
        }
        if (!isset($map[$scopeId])) {
            throw new Exception("Scope not found: $scopeId");
        }
        $current = $map[$scopeId];
        $parentId = $current['parent_id'] ?? null;
        $siblings = [$scopeId => $current['name']]; // Include self
        if ($parentId) {
            foreach ($scopes as $scope) {
                if ($scope['parent_id'] === $parentId && $scope['scope_id'] !== $scopeId) {
                    $siblings[$scope['scope_id']] = $scope['name'];
                }
            }
            // Include siblings at higher levels if specified
            if ($level !== null && $level > 1) {
                $parentSiblings = $this->getSiblings($parentId, $level - 1);
                $siblings = array_merge($siblings, $parentSiblings);
            }
        }
        return $siblings;
    }
    /**
     * Render a hierarchical path menu from JSON data with highlighted scope based on URL.
     *
     * @param string $jsonString JSON string of scope hierarchy
     * @param string $highlightId ID of the scope to highlight (e.g., from URL segment)
     * @return string HTML string of the rendered menu or error message
     */
    public function renderPath($jsonString, $highlightId)
    {
        try {
            // Parse JSON string
            $jsonData = json_decode($jsonString, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return '<div class="alert alert-warning">Invalid JSON data</div>';
            }
            // Normalize JSON data to ensure children is an array
            $normalizeChildren = function ($node) use (&$normalizeChildren) {
                if (isset($node['children']) && !is_array($node['children'])) {
                    $node['children'] = empty($node['children']) ? [] : [$node['children']];
                }
                foreach ($node['children'] ?? [] as &$child) {
                    $child = $normalizeChildren($child);
                }
                return $node;
            };
            $data = is_string($jsonData) ? json_decode($jsonData, true) : $jsonData;
            if (!is_array($data) || empty($data)) {
                return '<div class="alert alert-warning">No data available</div>';
            }
            // Normalize the entire data structure
            $normalizedData = array_map(function ($node) use ($normalizeChildren) {
                return $normalizeChildren($node);
            }, $data);
            // Check if highlightId exists in the data
            $idExists = false;
            $checkId = function ($node) use (&$checkId, $highlightId) {
                if (isset($node['id']) && strval($node['id']) === strval($highlightId)) {
                    return true;
                }
                foreach ($node['children'] ?? [] as $child) {
                    if ($checkId($child)) {
                        return true;
                    }
                }
                return false;
            };
            foreach ($normalizedData as $item) {
                if ($checkId($item)) {
                    $idExists = true;
                    break;
                }
            }
            if (!$idExists) {
                // Optional: Log or display a warning if highlightId is not found
                // error_log("Highlight ID '$highlightId' not found in JSON data");
            }
            // Pastel colors for styling
            $pastelColors = ['#FFFFFF', '#FAFAFA', '#FFF2F2', '#F2FFF4', '#F2F2FF', '#FAF2FF', '#F2FFFF', '#FEFFF2'];
            $companyColors = [];
            $colorIndex = 0;
            foreach ($normalizedData as $item) {
                $companyColors[$item['id']] = $item['background'] ?? $pastelColors[$colorIndex++ % count($pastelColors)];
            }
            // SVG line helper
            $createSvgLine = function ($type, $depth) {
                $unitWidth = 10;
                $svgWidth = $depth * $unitWidth + ($type !== 'empty' ? $unitWidth : 0);
                $svg = '<svg width="' . $svgWidth . '" height="24" viewBox="0 0 ' . $svgWidth . ' 24" style="display:inline-block;vertical-align:middle;margin-right:2px;">';
                if ($type !== 'empty') {
                    $path = '';
                    if ($type === 'vertical') {
                        $path = 'M' . ($depth * $unitWidth + $unitWidth / 2) . ' 0 V24';
                    } elseif ($type === 'branch') {
                        $path = 'M' . ($depth * $unitWidth + $unitWidth / 2) . ' 12 H' . ($depth * $unitWidth + $unitWidth) .
                            ' M' . ($depth * $unitWidth + $unitWidth / 2) . ' 0 V24';
                    } elseif ($type === 'last') {
                        $path = 'M' . ($depth * $unitWidth + $unitWidth / 2) . ' 12 H' . ($depth * $unitWidth + $unitWidth) .
                            ' M' . ($depth * $unitWidth + $unitWidth / 2) . ' 0 V12';
                    }
                    $svg .= '<path d="' . $path . '" stroke="#666" stroke-width="1"/>';
                }
                $svg .= '</svg>';
                return $svg;
            };
            // Build dropdown tree
            $menuItems = '';
            $buildTree = function ($node, $label, $depth = 0, $parentsLast = [], $isLast = true, $companyKey = '')
            use (&$buildTree, $createSvgLine, $highlightId, $companyColors) {
                if (!$node || !$label) return '';
                $itemId = $node['id'] ?? '';
                $labelColor = $node['background'] ?? '#333333';
                $isActive = strval($itemId) === strval($highlightId) ? ' path-active' : '';
                $lineContainer = '';
                for ($i = 0; $i < $depth; $i++) {
                    $lineContainer .= $createSvgLine($parentsLast[$i] ? 'empty' : 'vertical', $i);
                }
                if ($depth > 0) {
                    $lineContainer .= $createSvgLine($isLast ? 'last' : 'branch', $depth);
                }
                // Adjust inline style for active item to ensure highlight visibility
                $itemHtml = '<a href="' . url('/') . '/t/scope-management/page/' . $itemId . '" class="path-item' . $isActive . '" data-id="' . $itemId . '" data-label="' . htmlspecialchars($label) . '" style="background:transparent;color:#333333;cursor:pointer;">';
                $itemHtml .= '<span style="display:inline-block;">' . $lineContainer . '</span>';
                $itemHtml .= '<span style="display:inline-block;vertical-align:middle;color:' . $labelColor . '" 
                data-bs-toggle="tooltip"
                title="' . $node['group'] . '">' . htmlspecialchars($label) . '</span>';
                $itemHtml .= '</a>';
                $childrenHtml = '';
                foreach ($node['children'] ?? [] as $idx => $child) {
                    $childrenHtml .= $buildTree(
                        $child,
                        $child['name'] ?? '',
                        $depth + 1,
                        array_merge($parentsLast, [$isLast]),
                        $idx === count($node['children'] ?? []) - 1,
                        $companyKey ?: ($depth === 0 ? $node['id'] : $companyKey)
                    );
                }
                return $itemHtml . $childrenHtml;
            };
            foreach ($normalizedData as $idx => $item) {
                $menuItems .= $buildTree($item, $item['name'] ?? '', 0, [], $idx === count($normalizedData) - 1, $item['id'] ?? '');
            }
            return $menuItems ?: '<div class="alert alert-warning">No items to display</div>';
        } catch (Exception $e) {
            return '<div class="alert alert-warning">Error rendering path: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}