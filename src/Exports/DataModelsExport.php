<?php

namespace CrucialDigital\Metamorph\Exports;

use CrucialDigital\Metamorph\Models\CoreForm;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Jenssegers\Mongodb\Eloquent\Model;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DataModelsExport implements FromCollection, ShouldQueue, WithHeadings
{

    use Exportable;

    private Collection $collection;
    private Model|CoreForm $form;

    public function __construct(Collection $collection, Model|CoreForm $form)
    {
        $this->form = $form;
        $this->collection = $this->form->inputs->map(function ($input) use ($collection) {
            return $collection[$input['field']];
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
