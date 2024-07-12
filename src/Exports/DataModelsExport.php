<?php

namespace CrucialDigital\Metamorph\Exports;

use CrucialDigital\Metamorph\Config;
use CrucialDigital\Metamorph\Models\MetamorphForm;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use MongoDB\Laravel\Query\Builder;

class DataModelsExport implements FromCollection, WithHeadings, WithChunkReading
{
    use Exportable;
    private Collection $collection;



    public function __construct(Collection $collection, protected MetamorphForm $form)
    {
        $this->collection = collect();
        $ressources = $this->resources($collection);
        foreach ($collection as $data) {
            $prepared = [];
            foreach ($this->form->inputs as $input) {
                if (isset($input['type']) && in_array($input['type'], ['resource', 'multiresource', 'selectresource'])) {
                    $model = ($ressources[$input['entity']] ?? collect())->where('_id', '=', $data[$input['field']])->first();
                    if ($model == null) {
                        $prepared[$input['field']] = '';
                    } else {
                        $label = (Config::models($input['entity']))::label() ?? 'name';
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
            }

            $class = Config::models($form->entity);
            if ($class && $class::exportsFields()) {
                foreach ($class::exportsFields() as $key => $column) {
                    $prepared[$key] = $this->getValue($key, $data);
                }
            }
            $this->collection->push($prepared);
        }
    }

    private function resources(Collection $collection): array
    {
        $data = [];
        foreach ($this->form->inputs as $input) {
            if (isset($input['type']) && in_array($input['type'], ['resource', 'multiresource', 'selectresource'])) {
                $data[$input['entity']] = $this->_makeBuilder($input['entity'])->whereIn('_id', $collection->pluck($input['field'])->flatten()->unique()->values()->toArray())->get();
            }
        }

        return $data;
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
        $class = Config::models($this->form->entity);
        if ($class && $class::exportsFields()) {
            foreach ($class::exportsFields() as $column) {
                $headers[] = $column;
            }
        }
        return $headers;
    }

    private function _makeBuilder($entity): ? Builder
    {
        return DB::collection(app(Config::models($entity))->getTable());
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

    public function chunkSize(): int
    {
        return 50;
    }
}
