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
        $userCertificate = $this->certificateGenerator->requestCertificateForCourse($request->user(), $slug);

        return CertificateResource::make($userCertificate);
    }

    public function getUserCertificates(Request $request)
    {
        $certificates = $this->certificateGenerator->getUserCertificates($request->user());

        return CertificateResource::collection($certificates);
    }

    public function downloadCertificate(Request $request, int $userCertificateId)
    {
        return $this->certificateGenerator->downloadCertificateForCourse($request->user(), $userCertificateId);
    }
}
