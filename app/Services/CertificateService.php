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

        if (!Storage::exists($template->template_path)) {
            abort(404, 'Файл шаблона не найден на сервере.');
        }

        $tempTemplatePath = tempnam(sys_get_temp_dir(), 'tpl_') . '.docx';
        $tempOutputPath = tempnam(sys_get_temp_dir(), 'out_') . '.docx';

        try {
            file_put_contents($tempTemplatePath, Storage::get($template->template_path));

            $dates = $this->getFormattedDates();

            $variables = [
                'date_kz'       => $dates['date_kz'],
                'date_ru'       => $dates['date_ru'],
                'user_name'     => $user->name . '' . $user->second_name,
                'user_position' => $user->position ?? 'Сотрудник',
            ];

            $this->docGenerator->generate($tempTemplatePath, $tempOutputPath, $variables);

            $fileName = 'issued_certificates/' . Str::uuid() . '.docx';
            Storage::put($fileName, file_get_contents($tempOutputPath));

        } finally {
            @unlink($tempTemplatePath);
            @unlink($tempOutputPath);
        }

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

    public function downloadCertificateForCourse(User $user, int $userCertificateId)
    {
        $userCertificate = UserCertificate::query()
            ->where('user_id', $user->id)
            ->where('id', $userCertificateId)
            ->firstOr(fn () => abort(404, 'Сертификат не найден.'));


        if (!Storage::exists($userCertificate->file_path)) {
            abort(404, 'Файл сертификата не найден в хранилище.');
        }

        $fileName = "Certificate_{$userCertificate->id}.docx";

        return Storage::download($userCertificate->file_path, $fileName);
    }

    private function getFormattedDates(): array
    {
        $now = now();
        $day = $now->format('d');
        $year = $now->format('Y');
        $monthNum = (int) $now->format('n');

        $monthsKz = [
            1 => 'қаңтар', 2 => 'ақпан', 3 => 'наурыз', 4 => 'сәуір',
            5 => 'мамыр', 6 => 'маусым', 7 => 'шілде', 8 => 'тамыз',
            9 => 'қыркүйек', 10 => 'қазан', 11 => 'қараша', 12 => 'желтоқсан'
        ];

        $monthsRu = [
            1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля',
            5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа',
            9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря'
        ];

        return [
            'date_kz' => "«{$day}» " . $monthsKz[$monthNum] . " {$year} г.",
            'date_ru' => "«{$day}» " . $monthsRu[$monthNum] . " {$year} г.",
        ];
    }
}
