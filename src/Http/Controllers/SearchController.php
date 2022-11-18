<?php

namespace CrucialDigital\Metamorph\Http\Controllers;

use CrucialDigital\Metamorph\DataRepositoryBuilder;
use CrucialDigital\Metamorph\Exports\DataModelsExport;
use CrucialDigital\Metamorph\Models\CoreForm;
use CrucialDigital\Metamorph\ResourceQueryLoader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
        $form = CoreForm::findOrFail($form);
        $builder = $this->_makeBuilder($entity);

        if ($builder != null) {
            return (new DataModelsExport((new ResourceQueryLoader($builder))->load(), $form))
                ->download($entity . '.xlsx', Excel::XLSX);
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

                $data = $this->_makeBuilder($entity)?->whereIn('_id', $value)->get();

                $resources[$field] = implode(', ', $data?->map(function ($res) use ($entity) {
                        $res = $res->toArray();
                        return $res[config('metamorph.models.' . $entity)::label()] ?? '----';
                    })?->toArray() ?? []);
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
        $default = config('metamorph.models.' . $entity);
        if (!class_exists($default)) {
            abort(500, "Class $default does not exists !");
        } else {
            $default = $default::query();
        }
        $repository = config('metamorph.repositories.' . $entity);

        if ($repository && !class_exists($repository)) {
            abort(500, "Class $repository does not exists !");
        }

        if ($repository && !(new $repository instanceof DataRepositoryBuilder)) {
            abort(500, "The data repository must implement CrucialDigital\Metamorph\DataRepositoryBuilder class");
        }
        if ($repository) {
            return (new $repository)->builder();
        }
        return $default;
    }


}
