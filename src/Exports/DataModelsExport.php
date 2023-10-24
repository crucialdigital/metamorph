<?php

namespace CrucialDigital\Metamorph\Exports;

use CrucialDigital\Metamorph\Models\MetamorphForm;
use Illuminate\Contracts\Queue\ShouldQueue;
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
        $this->collection = $collection->map(function ($data) use ($form){
            $prepared = [];
            $form->inputs->each(function ($input) use (&$prepared, $data){
                $prepared[$input['field']] = $data[$input['field']];
            });
            return $prepared;
        });

    }

    public function collection(): Collection
    {
        return $this->collection;
    }

    public function headings(): array
    {
        return $this->form->inputs->map(function ($input) {
            return $input['name'];
        })->toArray();
    }
}
