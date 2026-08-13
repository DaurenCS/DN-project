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
            'id'           => $this->id,
            'download_url' => route('certificates.download', ['id' => $this->id]),
            'expires_at'   => $this->expires_at?->format('Y-m-d H:i:s'),
            'created_at'   => $this->created_at?->format('Y-m-d H:i:s'),
            'course'       => $course ? CourseResource::make($course) : null,
        ];
    }
}
