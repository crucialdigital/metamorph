<?php

namespace CrucialDigital\Metamorph\Resources;

use CrucialDigital\Metamorph\Models\MetamorphForm;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MasterCrudResourceCollection extends ResourceCollection
{
    protected ?MetamorphForm $dataModel;
    public function __construct($resource, protected ?string $form = null)
    {
        parent::__construct($resource);
        $this->dataModel = MetamorphForm::firstWhere('entity', $this->form);
    }

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }

    public function paginationInformation($request, $paginated, $default)
    {
        return [ ...$default, ...$default['meta'], 'form' => $this->dataModel ];
    }
}
