<?php
namespace App\Events;

use App\Models\UserCertificate;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CertificateRequested
{
    use Dispatchable, SerializesModels;
    public function __construct(public UserCertificate $certificate) {}
}
