<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image' => $this->image,
            'gender' => $this->gender,
            'itemCount' => (int) ($this->products_count ?? $this->products()->count()),
            'parentId' => $this->parent_id ? (string) $this->parent_id : null,
            'children' => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}

