<?php

namespace App\Http\Controllers\Api;

use App\Enum\CertificateStatus;
use App\Http\Controllers\Controller;
use App\Models\UserCertificate;
use App\Services\CertificateDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CertificateApprovalController extends Controller
{
    public function __construct(private CertificateDocumentService $documentService) {}

    public function process(Request $request)
    {
        if (! $request->hasValidSignature()) {
            abort(401, 'Ссылка недействительна или просрочена.');
        }

        $certificateId = $request->query('certificate_id');
        $commissionUserId = $request->query('commission_user_id');
        $action = $request->query('action');

        $certificate = UserCertificate::with(['user', 'courseCertificate'])->findOrFail($certificateId);

        if ($certificate->status !== CertificateStatus::PENDING->value) {
            return response('Заявка уже обработана.');
        }

        DB::transaction(function () use ($certificate, $action, $commissionUserId) {
            DB::table('certificate_approvals')->insert([
                'user_certificate_id' => $certificate->id,
                'commission_user_id'  => $commissionUserId,
                'action'              => $action,
                'created_at'          => now(),
            ]);

            if ($action === 'reject') {
                $certificate->update(['status' => CertificateStatus::REJECTED->value]);
                return;
            }

            $user = $certificate->user;
            $variables = [
                'date_kz'       => now()->format('d.m.Y'),
                'date_ru'       => now()->format('d.m.Y'),
                'user_name'     => trim($user->name . ' ' . $user->second_name),
                'user_position' => $user->position ?? 'Сотрудник',
            ];

            $filePath = $this->documentService->generateAndUpload($certificate->courseCertificate->template_path, $variables);

            $certificate->update([
                'status'    => CertificateStatus::APPROVED->value,
                'file_path' => $filePath,
            ]);
        });

        return response("Вы успешно " . ($action === 'approve' ? 'одобрили' : 'отклонили') . " заявку.");
    }
}
