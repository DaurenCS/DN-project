<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CertificateResource;
use App\Interfaces\CertificateGeneratorInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CertificateController extends Controller
{
    public function __construct(private CertificateGeneratorInterface $certificateGenerator) {}

    public function generateCertificate(Request $request, string $slug): CertificateResource
    {
        $userCertificate = $this->certificateGenerator->issueCertificateForCourse($request->user(), $slug);

        return CertificateResource::make($userCertificate);
    }

    public function getUserCertificates(Request $request)
    {
        $certificates = $this->certificateGenerator->getUserCertificates($request->user());

        return CertificateResource::collection($certificates);
    }

    public function downloadCertificate(Request $request, int $certificateId): BinaryFileResponse
    {
        $result = $this->certificateGenerator->downloadCertificateForCourse($request->user(), $certificateId);

        return response()->download($result['full_path'], $result['file_name']);
    }
}
