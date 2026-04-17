<?php

namespace CrucialDigital\Metamorph;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Metamorph Cache Service
 *
 * Provides tenant-aware, per-entity result caching for search/list endpoints.
 *
 * ## Multi-tenant design
 *
 * In apps where a user can belong to multiple tenants (e.g., a teacher working
 * in several schools), the tenant context must come from the **current request**,
 * not from a fixed attribute on the user model.
 *
 * Resolution priority (mode = 'auto'):
 *   1. Request header defined in `cache.tenant_header` (recommended)
 *   2. Authenticated user attribute defined in `cache.tenant_field`
 *   3. Falls back to 'global' (shared cache, no tenant isolation)
 *
 * ## Cache key format
 *
 *   metamorph:{tenant_id}:{entity}:{md5(normalized_request_params)}
 */
class MetamorphCacheService
{
    // -------------------------------------------------------------------------
    //  Public API
    // -------------------------------------------------------------------------

    /**
     * Returns true only when:
     *  - The global cache switch is on (`cache.enabled = true`)
     *  - The entity is explicitly listed in `cache.entities` with a truthy value
     */
    public function isEnabled(string $entity): bool
    {
        if (!config('metamorph.cache.enabled', false)) {
            return false;
        }

        $entityConfig = config("metamorph.cache.entities.{$entity}", null);

        // Entity not listed → disabled
        if ($entityConfig === null) {
            return false;
        }

        // Entity explicitly disabled
        return $entityConfig !== false;
    }

    /**
     * Returns the TTL (seconds) for the given entity.
     * Falls back to the global `cache.ttl`.
     */
    public function ttl(string $entity): int
    {
        $entityConfig = config("metamorph.cache.entities.{$entity}");

        if (is_int($entityConfig) && $entityConfig > 0) {
            return $entityConfig;
        }

        return (int) config('metamorph.cache.ttl', 3600);
    }

    /**
     * Returns the configured Laravel cache store name (null = default store).
     */
    public function store(): ?string
    {
        return config('metamorph.cache.store') ?: null;
    }

    /**
     * Generate a deterministic cache key for an entity + request params combo.
     *
     * Format: metamorph:{tenant_id}:{entity}:{md5(normalized_params)}
     */
    public function generateKey(string $entity, array $params = []): string
    {
        $tenantId = $this->resolveTenantId();
        $hash     = md5(json_encode($this->normalizeParams($params)));

        return "metamorph:{$tenantId}:{$entity}:{$hash}";
    }

    /**
     * Cache-or-execute pattern.
     * Returns the cached result if available; otherwise runs $callback,
     * stores the result, and returns it.
     */
    public function remember(string $entity, array $params, callable $callback): mixed
    {
        $key   = $this->generateKey($entity, $params);
        $store = Cache::store($this->store());

        return $store->remember($key, $this->ttl($entity), $callback);
    }

    /**
     * Invalidate all cached search results for one entity in the current tenant.
     *
     * Called automatically after store / update / destroy on an entity
     * (when `cache.invalidate_on_write = true`).
     */
    public function invalidate(string $entity): void
    {
        $tenantId = $this->resolveTenantId();
        $this->clearByPattern("metamorph:{$tenantId}:{$entity}:*");
    }

    /**
     * Invalidate all Metamorph cache entries for the current tenant (all entities).
     */
    public function invalidateTenant(): void
    {
        $tenantId = $this->resolveTenantId();
        $this->clearByPattern("metamorph:{$tenantId}:*");
    }

    /**
     * Resolve the tenant ID to use for the current request.
     *
     * Supports three modes (configured via `cache.tenant_mode`):
     *  - 'auto'     → header first, then user attribute, then 'global'
     *  - 'header'   → strictly from the configured request header
     *  - 'callback' → custom callable defined in `cache.tenant_resolver`
     */
    public function resolveTenantId(): string
    {
        $mode = config('metamorph.cache.tenant_mode', 'auto');

        try {
            return match ($mode) {
                'header'   => $this->resolveFromHeader(),
                'callback' => $this->resolveFromCallback(),
                default    => $this->resolveAuto(),
            };
        } catch (\Throwable $e) {
            Log::warning('[Metamorph] Cache tenant resolution failed, using "global".', [
                'error' => $e->getMessage(),
            ]);
            return 'global';
        }
    }

    // -------------------------------------------------------------------------
    //  Private helpers
    // -------------------------------------------------------------------------

    /**
     * Auto mode:
     *  1. Prefer the request header (best for multi-tenant where the user
     *     switches between tenants — the header carries the active tenant).
     *  2. Fall back to the user model attribute (single fixed tenant per user).
     *  3. Fall back to 'global'.
     */
    private function resolveAuto(): string
    {
        // 1. Request header takes precedence
        $header   = config('metamorph.cache.tenant_header', 'X-Tenant-Id');
        $fromHeader = request()->header($header);
        if ($fromHeader) {
            return (string) $fromHeader;
        }

        // 2. Authenticated user attribute
        $field = config('metamorph.cache.tenant_field', 'ecole_id');
        if ($user = auth()->user()) {
            $tenantId = data_get($user, $field);
            if ($tenantId) {
                return (string) $tenantId;
            }
        }

        return 'global';
    }

    private function resolveFromHeader(): string
    {
        $header = config('metamorph.cache.tenant_header', 'X-Tenant-Id');
        return (string) (request()->header($header) ?? 'global');
    }

    private function resolveFromCallback(): string
    {
        $resolver = config('metamorph.cache.tenant_resolver');

        if (is_callable($resolver)) {
            return (string) ($resolver() ?? 'global');
        }

        return 'global';
    }

    /**
     * Normalize the request params that should influence the cache key.
     * Only query-semantic params are included; authentication tokens,
     * CSRF fields, etc. are excluded intentionally.
     */
    private function normalizeParams(array $params): array
    {
        $relevant = [
            'columns'         => $params['columns']         ?? ['*'],
            'filters'         => $params['filters']         ?? [],
            'limit'           => $params['limit']           ?? null,
            'only_trash'      => $params['only_trash']      ?? false,
            'order_by'        => $params['order_by']        ?? 'created_at',
            'order_direction' => $params['order_direction'] ?? 'ASC',
            'page'            => $params['page']            ?? 1,
            'paginate'        => $params['paginate']        ?? true,
            'per_page'        => $params['per_page']        ?? 15,
            'randomize'       => $params['randomize']       ?? false,
            'relations'       => $params['relations']       ?? [],
            'search'          => $params['search']          ?? [],
            'term'            => $params['term']            ?? null,
            'with_trash'      => $params['with_trash']      ?? false,
        ];

        // Sort keys so that parameter order does not affect the hash
        ksort($relevant);

        return $relevant;
    }

    /**
     * Clear all cache keys matching a Redis key pattern.
     * Falls back gracefully for non-Redis stores.
     */
    private function clearByPattern(string $pattern): void
    {
        try {
            $store  = Cache::store($this->store());
            $driver = $store->getStore();

            if ($driver instanceof \Illuminate\Cache\RedisStore) {
                $redis  = $driver->connection();
                $prefix = config('cache.prefix', '');
                // The prefix is prepended by the Redis store, so we must include it in the search
                $keys = $redis->keys("{$prefix}{$pattern}");

                if (!empty($keys)) {
                    $redis->del($keys);
                }
            } elseif (method_exists($store, 'tags')) {
                // Memcached / Redis with tag support
                $store->tags([$pattern])->flush();
            } else {
                Log::info(
                    '[Metamorph] Cache pattern invalidation is not supported by the current driver. '
                    . 'Use Redis for automatic invalidation.'
                );
            }
        } catch (\Throwable $e) {
            Log::warning('[Metamorph] Cache invalidation failed.', [
                'pattern' => $pattern,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
