<?php

namespace App\Services;

use App\Interfaces\CertificateGeneratorInterface;
use App\Interfaces\DocumentGeneratorInterface;
use App\Models\Course;
use App\Models\User;
use App\Models\UserCertificate;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateService implements CertificateGeneratorInterface
{
    protected $docGenerator;

    public function __construct(DocumentGeneratorInterface $docGenerator)
    {
        $this->docGenerator = $docGenerator;
    }

    public function issueCertificateForCourse(User $user, string $courseSlug): UserCertificate
    {
        $course = Course::query()
            ->with('certificates')
            ->where('slug', $courseSlug)
            ->firstOrFail();

        $userCourse = $user->getUserCourse($course->id);

        if (!$userCourse) {
            abort(403, 'Пользователь не записан на этот курс.');
        }

        if ($userCourse->status != 'completed') {
            abort(403, 'Курс не завершен.');

        }

        $template = $course->certificates->first();

        $courseCertificateId = $template->pivot->id;

        $userCertificate = UserCertificate::where('user_id', $user->id)
            ->where('course_certificate_id', $courseCertificateId)
            ->first();

        if ($userCertificate) {
            return $userCertificate;
        }

        $templateFullPath = Storage::path($template->template_path);
        $fileName = 'issued_certificates/' . Str::uuid() . '.docx';
        $outputFullPath = Storage::path('private/' . $fileName);

        $variables = [
            'student_name' => $user->name,
            'course_title' => $course->title,
            'date'         => now()->format('d.m.Y'),
        ];

        $this->docGenerator->generate($templateFullPath, $outputFullPath, $variables);

        return UserCertificate::query()
            ->create([
                'user_id'               => $user->id,
                'course_certificate_id' => $courseCertificateId,
                'file_path'             => $fileName,
                'expires_at'            => now()->addMonths($template->validity_months),
            ]);
    }
}
