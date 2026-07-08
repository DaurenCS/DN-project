<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $course = $this->courseCertificate->course;
        return [
            'path' => $this->file_path,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'slug' => $course->slug,
            ]
        ];
    }
}
