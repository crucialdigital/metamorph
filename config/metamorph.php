<?php

// config for CrucialDigital/DataModel
use App\Tools\Entity;
use Illuminate\Support\Str;

return [

    /**
     * Directory in which data models are stored
     */

    'data_model_base_dir' => database_path('models'),


    /**
     * Directory in which models are stored
     */

    'model_dir' => app_path('Models'),

    /**
     * Directory in which repositories are stored
     */

    'repository_dir' => app_path('Repositories'),

    /**
     * Middleware for all routes
     */

    'middlewares' => [],

    /**
     * Middleware for model routes
     */

    'model_middlewares' => [],

    /**
     * Registered policies
     */

    'policies' => [

    ],


    /**
     * Prefix for model routes
     */

    'route_prefix' => 'metamorph',


    /**
     * Prefix for form uploaded files
     */

    'upload_path' => Str::slug(Str::lower(env('APP_NAME', 'metamorph'))),


    /**
     * Resources are data models created and that we use for another purpose
     * It is arrays of two entry (label, entity)
     * i.e
     *      [
     *        "label"=> "User of the application",
     *        "entity" => "user"
     *       ]
     *
     */
    'resources' => [
        //Enter resources here !
    ],

    /**
     * models are an array of the corresponding entity of the laravel Models of your application
     * i.e
     * [
     *  'user' => App\Models\User::class,
     *  'customer' => App\Models\Customer::class,
     * ]
     */

    'models' => [
        // Enter models here !
    ],


    /**
     * The repositories' is the custom builder form fetching model's data
     * i.e: 'user' =
     */
    'repositories' => [

    ],

    /**
     * Cache configuration
     * Allows enabling/disabling caching system and configuring tenant settings.
     */
    'cache' => [
        'enabled' => env('METAMORPH_CACHE_ENABLED', true),

        // Default TTL for cached responses in seconds
        'ttl' => env('METAMORPH_CACHE_TTL', 3600),

        // Custom Laravel cache store to use (e.g., 'redis', 'memcached')
        // Null means default cache store
        'store' => null,

        // How to resolve the tenant ID for caching context
        // 'auto' : checks header, then user attribute, then 'global'
        // 'header' : exclusively uses the tenant header
        // 'callback' : executes 'tenant_resolver' closure
        'tenant_mode' => 'auto',

        // Header name containing the tenant identifier (for 'auto' and 'header' modes)
        'tenant_header' => 'X-Tenant-Id',

        // Attribute on the authenticated user model to use as tenant ID (for 'auto' mode)
        'tenant_field' => 'ecole_id',

        // Custom closure to resolve the tenant ID (for 'callback' mode)
        'tenant_resolver' => null,

        // Entities where caching is explicitly enabled.
        // true = enabled (uses global ttl)
        // int = enabled with specific ttl in seconds
        // false = explicitly disabled
        'entities' => [
            // 'post' => true,
            // 'category' => 7200,
            // 'user' => false,
        ],
    ],
];
