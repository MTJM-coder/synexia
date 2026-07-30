<?php

namespace Modules\Categories\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shop_id' => $this->shop_id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'icon_path' => $this->icon_path,
            'image_path' => $this->image_path,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'is_global' => $this->isGlobal(),
            'children' => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
