<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'first_name'        => $this->name,
            'second_name'        => $this->second_name,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'position'    => $this->position,

            'birthday'    => $this->birthday,
            'department'  => $this->department,
            'is_active'   => $this->is_active,
            'roles'       => $this->roles->map(function ($role) {
                return [
                    'name'         => __("roles.{$role->name}"),
                ];
            }),
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at'    => $this->created_at->toISOString(),
        ];
    }
}
