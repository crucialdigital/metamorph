<?php

namespace CrucialDigital\Metamorph;

use MongoDB\Laravel\Eloquent\Model;

class Config
{
    /**
     * @param $model
     * @return mixed
     */
    public static function policies($model): mixed
    {
        return (isset($model)) ? config("metamorph.policies.$model", []) : config("metamorph.policies", []);
    }

    /**
     * @return mixed
     */
    public static function globalMiddleware(): mixed
    {
        return config("metamorph.middlewares", []);
    }


    /**
     * @param $model
     * @return mixed
     */
    public static function modelMiddleware($model): mixed
    {
        return (isset($model)) ? config("metamorph.model_middlewares.$model", []) : config("metamorph.model_middlewares", []);
    }

    /**
     * @param $model
     * @return mixed
     */
    public static function repositories($model): mixed
    {
        return (isset($model)) ? config("metamorph.repositories.$model") : config("metamorph.repositories", []);
    }


    /**
     * @param $model
     * @return mixed|string|Model
     */
    public static function models($model): mixed
    {
        return (isset($model)) ? config("metamorph.models.$model") : config("metamorph.models", []);
    }


    /**
     * @return mixed
     */
    public static function resources(): mixed
    {
        return config('metamorph.resources', []);
    }


    /**
     * @return mixed
     */
    public static function dataModelBaseDir(): mixed
    {
        return config("metamorph.data_model_base_dir");
    }


    /**
     * @return mixed
     */
    public static function modelDir(): mixed
    {
        return config("metamorph.model_dir");
    }

    /**
     * @return mixed
     */
    public static function repositoryDir(): mixed
    {
        return config("metamorph.repository_dir");
    }


    /**
     * @return mixed
     */
    public static function routePrefix(): mixed
    {
        return config("metamorph.route_prefix");
    }


    /**
     * @return mixed
     */
    public static function uploadPath(): mixed
    {
        return config("metamorph.upload_path");
    }



}
