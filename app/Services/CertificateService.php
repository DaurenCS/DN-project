<?php
namespace App\Services;

use App\Enum\CertificateStatus;
use App\Events\CertificateRequested;
use App\Interfaces\CertificateGeneratorInterface;
use App\Models\Course;
use App\Models\User;
use App\Models\UserCertificate;
use Illuminate\Support\Facades\Storage;

class CertificateService implements CertificateGeneratorInterface
{
    public function requestCertificateForCourse(User $user, string $courseSlug): UserCertificate
    {
        $course = Course::query()
            ->with('certificates')
            ->where('slug', $courseSlug)
            ->firstOr(fn () => abort(404, 'Курс не найден.'));

        $userCourse = $user->getUserCourse($course->id);

        if (!$userCourse || $userCourse->status !== 'completed') {
            abort(403, 'Курс не завершен или вы не записаны.');
        }

        $template = $course->certificates->first();
        if (!$template) abort(404, 'Шаблон не найден.');


        $certificate = UserCertificate::create([
            'user_id'               => $user->id,
            'course_certificate_id' => $template->pivot->id,
            'status'                => CertificateStatus::PENDING->value,
            'file_path'             => null,
            'expires_at'            => now()->addMonths($template->validity_months),
        ]);

        event(new CertificateRequested($certificate));

        return $certificate;
    }

    public function getUserCertificates(User $user)
    {
        return UserCertificate::query()
            ->with(['courseCertificate.course'])
            ->where('user_id', $user->id)
            ->where('status', CertificateStatus::APPROVED->value)
            ->get();
    }

    public function downloadCertificateForCourse(User $user, int $userCertificateId)
    {
        $userCertificate = UserCertificate::query()
            ->where('user_id', $user->id)
            ->where('id', $userCertificateId)
            ->where('status', CertificateStatus::APPROVED->value)
            ->firstOr(fn () => abort(404, 'Готовый сертификат не найден.'));

        if (!Storage::exists($userCertificate->file_path)) {
            abort(404, 'Файл сертификата не найден в хранилище.');
        }

        return Storage::download($userCertificate->file_path, "Certificate_{$userCertificate->id}.docx");
    }
}
