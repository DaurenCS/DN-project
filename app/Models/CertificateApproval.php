<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateApproval extends Model
{
    protected $fillable = [
        'user_certificate_id',
        'commission_user_id',
        'action'
    ];
}
