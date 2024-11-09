<?php

namespace CrucialDigital\Metamorph\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MasterCrudResourceCollection extends ResourceCollection
{
    public function __construct($resource, protected  $form = null)
    {
        parent::__construct($resource);
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
        return [ ...$default, ...$default['meta'], 'form' => $this->form ];
    }
}
