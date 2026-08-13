<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CertificateResource;
use App\Interfaces\CertificateGeneratorInterface;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function __construct(private CertificateGeneratorInterface $certificateGenerator) {}

    public function generateCertificate($slug)
    {
        $userCertificate = $this->certificateGenerator->issueCertificateForCourse($slug);

        return response()->json('success', 200);

//        return response()->download($userCertificate->file_path, "Certificate.docx")
//            ->deleteFileAfterSend(true);
    }

    public function getUserCertificates(Request $request)
    {
        $certificates = $this->certificateGenerator->getUserCertificates($request->user());

        return CertificateResource::collection($certificates);
    }

}
