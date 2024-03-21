<?php

namespace CrucialDigital\Metamorph\Exports;

use CrucialDigital\Metamorph\Config;
use CrucialDigital\Metamorph\DataRepositoryBuilder;
use CrucialDigital\Metamorph\Models\MetamorphForm;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use MongoDB\Laravel\Eloquent\Model;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DataModelsExport implements FromCollection, WithHeadings
{

    use Exportable;

    private Collection $collection;
    private MetamorphForm $form;

    public function __construct(Collection $collection, $form)
    {
        $this->form = $form;
        $this->collection = $collection->map(function ($data) use ($form) {
            $prepared = [];
            $form->inputs->each(function ($input) use (&$prepared, $data) {
                if (isset($input['type']) && in_array($input['type'], ['resource', 'multiresource', 'selectresource'])) {
                    $model = $this->_makeBuilder($input['entity'])->firstWhere('_id', '=', $data[$input['field']]);
                    if ($model == null) {
                        $prepared[$input['field']] = '';
                    } else {
                        $label = $model->getModel()::label() ?? 'name';
                        $prepared[$input['field']] = $model[$label];
                    }

                } else {
                    $prepared[$input['field']] = match ($input['type']) {
                        'date' => (new Carbon($data[$input['field']]))->format('d-m-Y'),
                        'datetime' => (new Carbon($data[$input['field']]))->format('d-m-Y H:i'),
                        'boolean' => $data[$input['field']] ? 'Oui' : 'Non',
                        'select' => collect($input['options'])
                                ->firstWhere('value', '=', $data[$input['field']])['label']
                            ?? $data[$input['field']],
                        default => $data[$input['field']],
                    };
                }
            });
            $class = $this->_makeBuilder($form->entity)?->getModel();
            if ($class && $class::exportsFields()) {
                foreach ($class::exportsFields() as $key => $column) {
                    $prepared[$key] = $this->getValue($key, $data);
                }
            }
            return $prepared;
        });

    }

    public function collection(): Collection
    {
        return $this->collection;
    }

    public function headings(): array
    {
        $headers = $this->form->inputs->map(function ($input) {
            return $input['name'];
        })->toArray();
        $class = $this->_makeBuilder($this->form->entity)?->getModel();
        if ($class && $class::exportsFields()) {
            foreach ($class::exportsFields() as $column) {
                $headers[] = $column;
            }
        }
        return $headers;
    }

    private function _makeBuilder($entity): ?Builder
    {
        $model = Config::models($entity);
        $repository = Config::repositories($entity);

        if ($repository && class_exists($repository)) {
            if (!(new $repository instanceof DataRepositoryBuilder)) {
                return null;
            }
            return (new $repository)->builder();
        }

        if (!class_exists($model)) {
            return null;
        }

        return $model::query();
    }

    private function getValue($key, $data)
    {
        $value = $data;
        $keys = explode('.', $key);
        foreach ($keys as $k) {
            $value = (isset($data) && isset($value[$k])) ? $value[$k] : null;
        }
        return $value;
    }
}
