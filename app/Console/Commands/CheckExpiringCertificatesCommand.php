<?php
namespace App\Console\Commands;

use App\Enum\CertificateStatus;
use App\Models\UserCertificate;
use App\Notifications\CertificateExpiringNotification;
use Illuminate\Console\Command;

class CheckExpiringCertificatesCommand extends Command
{
    protected $signature = 'certificates:check-expiring';
    protected $description = 'Проверка сертификатов, у которых истекает срок действия (за 1 месяц и за 10 дней)';

    public function handle(): int
    {
        $checkIntervals = [
            30 => now()->addDays(30)->toDateString(),
            10 => now()->addDays(10)->toDateString(),
        ];

        foreach ($checkIntervals as $days => $targetDate) {
            UserCertificate::query()
                ->where('status', CertificateStatus::APPROVED)
                ->whereDate('expires_at', $targetDate)
                ->with(['user', 'courseCertificate.course'])
                ->chunkById(100, function ($certificates) use ($days) {
                    foreach ($certificates as $certificate) {
                        if ($certificate->user) {
                            $certificate->user->notify(
                                new CertificateExpiringNotification($certificate, $days)
                            );
                        }
                    }
                });
        }

        $this->info('Проверка истекающих сертификатов успешно завершена.');

        return Command::SUCCESS;
    }
}
