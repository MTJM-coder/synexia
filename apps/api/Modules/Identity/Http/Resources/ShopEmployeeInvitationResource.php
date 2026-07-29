<?php

namespace Modules\Identity\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopEmployeeInvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'role' => $this->whenLoaded('role', fn () => [
                'name' => $this->role->name,
                'slug' => $this->role->slug,
            ]),
            'invited_by' => $this->whenLoaded('inviter', fn () => [
                'first_name' => $this->inviter->first_name,
                'last_name' => $this->inviter->last_name,
            ]),
            'status' => $this->status,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            // 'token' n'apparaît jamais ici — $hidden sur le modèle de toute façon.
        ];
    }
}
