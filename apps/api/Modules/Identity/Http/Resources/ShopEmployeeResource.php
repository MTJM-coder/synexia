<?php

namespace Modules\Identity\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopEmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'shop_id' => $this->shop_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'role' => $this->whenLoaded('role', fn () => [
                'name' => $this->role->name,
                'slug' => $this->role->slug,
            ]),
            'job_title' => $this->job_title,
            'status' => $this->status,
            'hired_at' => $this->hired_at?->toIso8601String(),
        ];
    }
}
