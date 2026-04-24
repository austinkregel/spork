<?php

declare(strict_types=1);

namespace App\Http\Resources\Calendar;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'],
            'title' => $this->resource['title'],
            'description' => $this->resource['description'],
            'start' => $this->resource['start']->toIso8601String(),
            'end' => $this->resource['end']->toIso8601String(),
            'type' => $this->resource['type'],
            'color' => $this->resource['color'],
            'rrule' => $this->resource['rrule'],
            'recurring' => $this->resource['recurring'],
            'display' => $this->resource['display'] ?? 'auto',
            'allDay' => $this->resource['all_day'] ?? false,
            'model_id' => $this->resource['model_id'] ?? null,
            'model_type' => $this->resource['model_type'] ?? null,
        ];
    }
}
