<?php

// config for CrucialDigital/DataModel
return [

    /*
     * Directory in which data models are stored
     */

    'data_model_base_dir' => database_path('models'),


    /*
     * Directory in which models are stored
     */

    'model_dir' => app_path('Models'),

    /*
     * Directory in which repositories are stored
     */

    'repository_dir' => app_path('Repositories'),

    /*
     * Middleware for all routes
     */

    'middlewares' => [],

    /*
     * Middleware for model routes
     */

    'model_middlewares' => [],


    /*
     * Middleware for model routes
     */

    'route_prefix' => 'metamorph',

    /*
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

    /*
     * Models are an array of the corresponding entity of the laravel Models of your application
     * i.e
     * [
     *  'user' => App\Models\User::class,
     *  'customer' => App\Models\Customer::class,
     * ]
     */

    'models' => [
        // Enter models here !
    ],


    /*
     * The repositories' is the custom builder form fetching model's data
     * i.e: 'user' =
     */
    'repositories' => [

    ]

];
