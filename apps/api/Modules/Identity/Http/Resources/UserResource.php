<?php

namespace Modules\Identity\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid, // jamais l'id interne
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar_path ? asset('storage/'.$this->avatar_path) : null,
            'locale' => $this->locale,
            'status' => $this->status,
            'is_super_admin' => $this->is_super_admin,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
