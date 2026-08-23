<?php

namespace App\Models;

use App\Enum\CertificateStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserCertificate extends Model
{
    protected $table = 'user_certificate';

    protected $fillable = [
        'user_id',
        'course_certificate_id',
        'file_path',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'status' => CertificateStatus::class,
    ];

    public function courseCertificate() {
        return $this->belongsTo(CourseCertificate::class, 'course_certificate_id');
    }
    public function getCourseAttribute()
    {
        return $this->courseCertificate->course;
    }
    public function getTemplateAttribute()
    {
        return $this->courseCertificate->certificate;
    }
    public function isValid(): bool {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(CertificateApproval::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
