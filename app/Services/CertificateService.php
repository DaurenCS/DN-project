<?php

namespace App\Services;

use App\Interfaces\CertificateGeneratorInterface;
use App\Interfaces\DocumentGeneratorInterface;
use App\Models\Course;
use App\Models\User;
use App\Models\UserCertificate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateService implements CertificateGeneratorInterface
{
    public function __construct(protected DocumentGeneratorInterface $docGenerator) {}

    public function issueCertificateForCourse(User $user, string $courseSlug): UserCertificate
    {
        $course = Course::query()
            ->with('certificates')
            ->where('slug', $courseSlug)
            ->firstOr(fn () => abort(404, 'Курс не найден.'));

        $userCourse = $user->getUserCourse($course->id);

        if (!$userCourse) {
            abort(403, 'Вы не записаны на этот курс.');
        }

        if ($userCourse->status !== 'completed') {
            abort(403, 'Курс еще не завершен.');
        }

        $template = $course->certificates->first();

        if (!$template) {
            abort(404, 'Шаблон сертификата для данного курса не найден.');
        }

        $courseCertificateId = $template->pivot->id;

        $existingCertificate = UserCertificate::query()
            ->where('user_id', $user->id)
            ->where('course_certificate_id', $courseCertificateId)
            ->first();

        if ($existingCertificate) {
            return $existingCertificate;
        }

        if (!Storage::disk('local')->exists($template->template_path)) {
            abort(404, 'Файл шаблона не найден на сервере.');
        }

        Storage::makeDirectory('issued_certificates');

        $fileName = 'issued_certificates/' . Str::uuid() . '.docx';

        $templateFullPath = Storage::disk('local')->path($template->template_path);
        $outputFullPath = Storage::path($fileName);

        $templateFullPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $templateFullPath);
        $outputFullPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $outputFullPath);

        $variables = [
            'student_name' => $user->name,
            'course_title' => $course->title,
            'date'         => now()->format('d.m.Y'),
        ];

        $this->docGenerator->generate($templateFullPath, $outputFullPath, $variables);

        return UserCertificate::query()->create([
            'user_id'               => $user->id,
            'course_certificate_id' => $courseCertificateId,
            'file_path'             => $fileName,
            'expires_at'            => now()->addMonths($template->validity_months),
        ]);
    }

    public function getUserCertificates(User $user)
    {
        return UserCertificate::query()
            ->with(['courseCertificate.course'])
            ->where('user_id', $user->id)
            ->get();
    }

    public function downloadCertificateForCourse(User $user, int $certificateId): array
    {
        $userCertificate = UserCertificate::query()
            ->where('id', $certificateId)
            ->where('user_id', $user->id)
            ->firstOr(fn () => abort(404, 'Сертификат не найден.'));

        if (!Storage::exists($userCertificate->file_path)) {
            abort(404, 'Файл сертификата не найден на сервере.');
        }

        return [
            'full_path' => Storage::path($userCertificate->file_path),
            'file_name' => "Certificate_{$userCertificate->id}.docx",
        ];
    }
}
