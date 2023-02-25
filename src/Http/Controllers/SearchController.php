<?php

namespace CrucialDigital\Metamorph\Http\Controllers;

use CrucialDigital\Metamorph\DataRepositoryBuilder;
use CrucialDigital\Metamorph\Exports\DataModelsExport;
use CrucialDigital\Metamorph\Models\MetamorphForm;
use CrucialDigital\Metamorph\ResourceQueryLoader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SearchController extends Controller
{

    public function search($entity): JsonResponse
    {
        $builder = $this->_makeBuilder($entity);

        if ($builder != null) {
            $data = (new ResourceQueryLoader($builder))->load();
            return response()->json($data);
        } else {
            return response()->json(null, 404);
        }
    }

    /**
     * @param $entity
     * @param $form
     * @return Response|BinaryFileResponse|JsonResponse
     */

    public function export($entity, $form): Response|BinaryFileResponse|JsonResponse
    {
        $form = MetamorphForm::findOrFail($form);
        $builder = $this->_makeBuilder($entity);

        if ($builder != null) {
            $data = (new ResourceQueryLoader($builder))->load();
            return (new DataModelsExport($data, $form))
                ->download($entity . '.csv', Excel::CSV);
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

                $data = $this->_makeBuilder($entity)?->whereIn('_id', $value)->get()->toArray();
                $data = collect($data)->map(function ($res) use ($entity) {
                    $model = config('metamorph.models.' . $entity);
                    if(class_exists($model) && method_exists($model, 'label')){
                        return $res[config('metamorph.models.' . $entity)::label()];
                    }else {
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
        $model = config('metamorph.models.' . $entity);
        $repository = config('metamorph.repositories.' . $entity);

        if ($repository && class_exists($repository)) {
            if (!(new $repository instanceof DataRepositoryBuilder)) {
                abort(500, "The data repository must implement CrucialDigital\Metamorph\DataRepositoryBuilder class");
            }
            return (new $repository)->builder();
        }

        if (!class_exists($model)) {
            abort(404, "Model not found !");
        }

        return $model::query();
    }


}
