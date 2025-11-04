<?php
namespace App\Services;
use App\Facades\{BusinessDB, CentralDB, Skeleton};
use Illuminate\Support\Facades\{Log, Cache};
use Exception;
use InvalidArgumentException;
class SearchService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const SEARCH_TABLES = [
        'users' => [
            'table' => 'users',
            'title' => 'Users',
            'columns' => ['users.first_name', 'users.last_name', 'users.email'],
            'select' => [
                'users.user_id AS id',
                'CONCAT(users.first_name, " ", users.last_name) AS value',
                '"users" AS type',
                'users.first_name',
                'users.last_name',
                'users.email',
                'companies.name AS company_name'
            ],
            'joins' => [
                ['type' => 'left', 'table' => 'companies', 'on' => ['users.company_id', 'companies.company_id']]
            ],
            'where' => [
                ['column' => 'users.deleted_at', 'operator' => '=', 'value' => null],
                ['column' => 'users.account_status', 'operator' => '=', 'value' => 'active']
            ]
        ],
        'scopes' => [
            'table' => 'scopes',
            'title' => 'Departments',
            'columns' => ['scopes.name'],
            'select' => [
                'scopes.scope_id AS id',
                'scopes.name AS value',
                '"scopes" AS type',
                'NULL AS first_name',
                'NULL AS last_name',
                'NULL AS email',
                'NULL AS company_name'
            ],
            'where' => [
                ['column' => 'scopes.deleted_at', 'operator' => '=', 'value' => null],
                ['column' => 'scopes.is_active', 'operator' => '=', 'value' => 1]
            ]
        ],
        'news' => [
            'table' => 'news',
            'title' => 'News',
            'columns' => ['news.title', 'news.content'],
            'select' => [
                'news.news_id AS id',
                'news.title AS value',
                '"news" AS type',
                'NULL AS first_name',
                'NULL AS last_name',
                'NULL AS email',
                'NULL AS company_name'
            ],
            'where' => [
                ['column' => 'news.deleted_at', 'operator' => '=', 'value' => null],
                ['column' => 'news.status', 'operator' => '=', 'value' => 'published']
            ]
        ],
        'companies' => [
            'table' => 'companies',
            'title' => 'Companies',
            'columns' => ['companies.name'],
            'select' => [
                'companies.company_id AS id',
                'companies.name AS value',
                '"companies" AS type',
                'NULL AS first_name',
                'NULL AS last_name',
                'NULL AS email',
                'NULL AS company_name'
            ],
            'where' => [
                ['column' => 'companies.deleted_at', 'operator' => '=', 'value' => null],
                ['column' => 'companies.is_active', 'operator' => '=', 'value' => 1]
            ]
        ]
    ];
    /**
     * Perform global search across multiple tables.
     *
     * @param string $query
     * @return array
     * @throws InvalidArgumentException|Exception
     */
    public function search(string $query): array
    {
        try {
            if (empty(trim($query))) {
                throw new InvalidArgumentException('Search query is required.');
            }
            $query = trim($query);
            $cacheKey = 'search:all:' . md5($query);
            $unionQueries = [];
            $system = Skeleton::getUserSystem();
            $connection = ($system === 'central') ? CentralDB::class : BusinessDB::class;
            foreach (self::SEARCH_TABLES as $type => $config) {
                $sql = $connection::table($config['table']);
                // Apply joins
                if (!empty($config['joins'])) {
                    foreach ($config['joins'] as $join) {
                        $sql->leftJoin($join['table'], $join['on'][0], '=', $join['on'][1]);
                    }
                }
                // Select fields
                $sql->selectRaw(implode(',', $config['select']));
                // Apply static conditions
                foreach ($config['where'] as $condition) {
                    if (!isset($condition['column'], $condition['operator'])) {
                        continue;
                    }
                    if (strtoupper($condition['operator']) === 'IN' && is_array($condition['value'])) {
                        $sql->whereIn($condition['column'], $condition['value']);
                    } else {
                        $sql->where($condition['column'], $condition['operator'], $condition['value']);
                    }
                }
                // Apply dynamic OR conditions for search columns
                $sql->where(function ($q) use ($config, $query) {
                    foreach ($config['columns'] as $column) {
                        $q->orWhere($column, 'LIKE', "%{$query}%");
                    }
                });
                $unionQueries[] = $sql;
            }
            // Combine all queries using UNION
            $finalQuery = array_shift($unionQueries);
            foreach ($unionQueries as $uq) {
                $finalQuery->union($uq);
            }
            // Execute query with limit
            $results = $finalQuery->limit(20)->get();
            // Organize results by type
            $categorized = ['users' => [], 'scopes' => [], 'news' => [], 'companies' => []];
            $titles = array_map(fn($config) => $config['title'], self::SEARCH_TABLES);
            foreach ($results as $row) {
                $type = $row->type ?? null;
                if (!$type || !isset($categorized[$type])) continue;
                $result = [
                    'id' => $row->id,
                    'value' => $row->value,
                    'type' => $type
                ];
                if ($type === 'users') {
                    $result['profile'] = [
                        'user_id' => $row->id,
                        'first_name' => $row->first_name,
                        'last_name' => $row->last_name,
                        'email' => $row->email,
                        'company_name' => $row->company_name
                    ];
                }
                $categorized[$type][] = $result;
            }
            $response = [
                'success' => true,
                'results' => $categorized,
                'titles' => $titles
            ];
            return $response;
        } catch (Exception $e) {
            Log::error("Search failed: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return [
                'success' => false,
                'results' => ['users' => [], 'scopes' => [], 'news' => [], 'companies' => []],
                'titles' => array_map(fn($config) => $config['title'], self::SEARCH_TABLES),
                'error' => $e->getMessage()
            ];
        }
    }
}
