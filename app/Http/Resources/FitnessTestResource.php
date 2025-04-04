<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FitnessTestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Chuyển đổi dữ liệu fitness test để thỏa mãn cấu trúc JSON cần thiết
     */
    public function toArray(Request $request): array
    {
        $thresholds = $this->thresholds ? [
            'excellent_threshold' => $this->thresholds->excellent_threshold,
            'good_threshold' => $this->thresholds->good_threshold,
            'pass_threshold' => $this->thresholds->pass_threshold,
        ] : [
            'excellent_threshold' => null,
            'good_threshold' => null,
            'pass_threshold' => null,
        ];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'unit' => $this->unit,
            'higher_is_better' => $this->higher_is_better,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'thresholds' => $thresholds,
        ];
    }
}
