<?php

namespace CrucialDigital\Metamorph\Http\Controllers;

use CrucialDigital\Metamorph\Resources\MasterCrudResourceCollection;
use CrucialDigital\Metamorph\Config;
use CrucialDigital\Metamorph\DataRepositoryBuilder;
use CrucialDigital\Metamorph\Exports\DataModelsExport;
use CrucialDigital\Metamorph\Models\MetamorphForm;
use CrucialDigital\Metamorph\ResourceQueryLoader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SearchController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        $middlewares = [];
        $model = request()->route('entity');
        $middlewares_config = Config::modelMiddleware($model);
        if (isset($middlewares_config)) {
            foreach ($middlewares_config as $middleware => $only) {
                if (is_string($only) && $only == '*') {
                    $middlewares[] = new Middleware($middleware);
                } else {
                    $middlewares[] = new Middleware($middleware, only: $only);
                }
            }
        }
        return $middlewares;
    }

    /**
     */
    public function search(Request $request, $entity)
    {

        $policies = collect(Config::policies($entity))->map(fn($police) => Str::lower($police))->toArray();

        if (in_array('viewany', $policies)) {
            Gate::authorize("viewAny", config("metamorph.models.$entity"));
        }

        $builder = $this->_makeBuilder($entity);

        if ($builder != null) {
            $data = (new ResourceQueryLoader($builder))->load();
            if($request->query('paginate', true)){
                return (new MasterCrudResourceCollection($data, $entity));
            }else{
                return response()->json($data);
            }
        } else {
            return response()->json(null, 404);
        }
    }

    /**
     * @param Request $request
     * @param $entity
     * @param $form
     * @return Response|BinaryFileResponse|JsonResponse
     */

    public function export(Request $request, $entity, $form): Response|BinaryFileResponse|JsonResponse
    {

        set_time_limit(0);
        $policies = collect(Config::policies($entity))->map(fn($police) => Str::lower($police))->toArray();

        if (in_array('viewany', $policies)) {
            Gate::authorize("viewAny", config("metamorph.models.$entity"));
        }

        $form = MetamorphForm::findOrFail($form);
        $builder = $this->_makeBuilder($entity);

        if ($builder != null) {
            $data = (new ResourceQueryLoader($builder))->load();
            $format = $request->input('format', 'CSV');
            $writerType = match (mb_strtoupper($format)) {
                'XLSX' => Excel::XLSX,
                'XLS' => Excel::XLS,
                'PDF' => Excel::DOMPDF,
                'ODS' => Excel::ODS,
                default => Excel::CSV,
            };
            return (new DataModelsExport($data, $form))
                ->download($entity . "." . mb_strtolower($format), $writerType);
        } else {
            return response()->json(null, 404);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */

    public function findAll(Request $request): JsonResponse
    {

        $request->validate([
            'resources' => ['required', 'array'],
        ]);

        $needed = $request->input('resources');
        $resources = [];
        foreach ($needed as $set) {
            if (isset($set['value'])) {
                $value = is_array($set['value']) ? $set['value'] : explode(',', $set['value']);
                $entity = $set['entity'];
                $field = $set['field'];

                $data = $this->_makeBuilder($entity)?->whereIn('id', $value)->get()->toArray();
                $data = collect($data)->map(function ($res) use ($entity) {
                    $model = config('metamorph.models.' . $entity);
                    if (class_exists($model) && method_exists($model, 'label')) {
                        return $res[config('metamorph.models.' . $entity)::label()];
                    } else {
                        return '----';
                    }
                });
                $resources[$field] = implode(', ', $data->toArray());
            }
        }
        return response()->json($resources);
    }

    /**
     * @param $entity
     * @return Builder|null
     */
    private function _makeBuilder($entity): ?Builder
    {
        $model = Config::models($entity);
        $repository = Config::repositories($entity);

        if ($repository && class_exists($repository)) {
            if (!(new $repository instanceof DataRepositoryBuilder)) {
                abort(500, "The data repository must implement CrucialDigital\Metamorph\DataRepositoryBuilder class");
            }
            return app($repository)->builder();
        }

        if (!class_exists($model)) {
            abort(404, "Model not found !");
        }

        return app($model)->where('id', 'exists', true);
    }


}
