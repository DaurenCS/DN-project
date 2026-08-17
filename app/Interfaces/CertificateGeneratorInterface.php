<?php

namespace App\Interfaces;

use App\Models\User;
use App\Models\UserCertificate;

interface CertificateGeneratorInterface
{
    public function issueCertificateForCourse(User $user, string $courseSlug): UserCertificate;

    public function getUserCertificates(User $user);

    public function downloadCertificateForCourse(User $user, int $userCertificateId): array;
}
