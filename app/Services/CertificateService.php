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

//         if (!$userCourse) {
//             abort(403, 'Пользователь не записан на этот курс.');
//         }
//
//         if ($userCourse->status != 'completed') {
//             abort(403, 'Курс не завершен.');
//         }

        $template = $course->certificates->first();

        if (!$template) {
            abort(404, 'Шаблон сертификата для данного курса не найден.');
        }

        $courseCertificateId = $template->pivot->id;

        $userCertificate = UserCertificate::where('user_id', $user->id)
            ->where('course_certificate_id', $courseCertificateId)
            ->first();

        if ($userCertificate) {
            return $userCertificate;
        }

        Storage::makeDirectory('issued_certificates');

        $fileName = 'issued_certificates/' . Str::uuid() . '.docx';

        $templateFullPath = Storage::path($template->template_path);
        $outputFullPath = Storage::path($fileName);

        $templateFullPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $templateFullPath);
        $outputFullPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $outputFullPath);
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

    public function getUserCertificates(User $user)
    {
        $userCertificates = UserCertificate::query()
            ->with(['courseCertificate.course'])
            ->where('user_id', $user->id)
            ->get();

        return $userCertificates;
    }
}
