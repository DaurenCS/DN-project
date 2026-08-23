<?php
namespace App\Services;

use App\Interfaces\DocumentGeneratorInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateDocumentService
{
    public function __construct(private DocumentGeneratorInterface $docGenerator) {}

    public function generateAndUpload(string $templatePath, array $variables): string
    {
        if (!Storage::exists($templatePath)) {
            abort(404, 'Файл шаблона не найден в хранилище.');
        }

        $tempTemplatePath = tempnam(sys_get_temp_dir(), 'tpl_') . '.docx';
        $tempOutputPath = tempnam(sys_get_temp_dir(), 'out_') . '.docx';

        try {
            file_put_contents($tempTemplatePath, Storage::get($templatePath));

            $this->docGenerator->generate($tempTemplatePath, $tempOutputPath, $variables);

            $fileName = 'issued_certificates/' . Str::uuid() . '.docx';
            Storage::put($fileName, file_get_contents($tempOutputPath));

            return $fileName;
        } finally {
            @unlink($tempTemplatePath);
            @unlink($tempOutputPath);
        }
    }
}
