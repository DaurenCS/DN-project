<?php

namespace App\Jobs;

use App\Interfaces\CertificateGeneratorInterface;
use App\Models\UserCourse;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateCourseCertificateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public UserCourse $userCourse
    ) {}

    /**
     * Execute the job.
     */
    public function handle(CertificateGeneratorInterface $certificateGenerator): void
    {
        $this->userCourse->loadMissing(['user', 'course']);
        $certificateGenerator->requestCertificateForCourse(
            $this->userCourse->user,
            $this->userCourse->course->slug
        );
    }
}
