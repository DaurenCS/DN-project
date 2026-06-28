<?php

namespace App\Services;

use App\Interfaces\DocumentGeneratorInterface;
use PhpOffice\PhpWord\TemplateProcessor;

class WordDocumentGenerator implements DocumentGeneratorInterface
{
    public function generate(string $templatePath, string $outputPath, array $variables): void
    {
        $processor = new TemplateProcessor($templatePath);

        foreach ($variables as $key => $value) {
            $processor->setValue($key, $value);
        }

        $processor->saveAs($outputPath);
    }
}
