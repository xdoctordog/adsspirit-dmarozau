<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OrderCollection extends ResourceCollection
{
    // Tell Laravel to use OrderListResource for each item in the collection
    public $collects = OrderListResource::class;

    /**
     * Customize the pagination information for the resource.
     * This removes the redundant 'links' array from the 'meta' object.
     */
    public function paginationInformation(Request $request, $paginator, array $default): array
    {
        // Remove the 'links' array from the 'meta' object
        unset($default['meta']['links']);

        return $default;
    }
}
