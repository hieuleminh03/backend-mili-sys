<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator; // Import the Paginator class

class FitnessTestCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     * Chuyển đổi collection thành array JSON với format chuẩn
     */
    public function toArray(Request $request): array
    {
        /* --- ORIGINAL CODE --- */
        // Check if the resource is an instance of a Paginator
        if ($this->resource instanceof AbstractPaginator) {
            return [
                'current_page' => $this->resource->currentPage(),
                'data' => $this->collection, // The collection of FitnessTestResource items
                'first_page_url' => $this->resource->url(1),
                'from' => $this->resource->firstItem(),
                'last_page' => $this->resource->lastPage(),
                'last_page_url' => $this->resource->url($this->resource->lastPage()),
                'links' => $this->resource->linkCollection()->toArray(),
                'next_page_url' => $this->resource->nextPageUrl(),
                'path' => $this->resource->path(),
                'per_page' => $this->resource->perPage(),
                'prev_page_url' => $this->resource->previousPageUrl(),
                'to' => $this->resource->lastItem(),
                'total' => $this->resource->total(),
            ];
        }

        // Fallback for non-paginated collections (shouldn't happen for this endpoint)
        return [
            'data' => $this->collection,
            'total' => $this->collection->count(), // Use collection count if not paginated
        ];
    }
}
