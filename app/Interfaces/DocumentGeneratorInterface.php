<?php

namespace App\Interfaces;

interface DocumentGeneratorInterface
{
    public function generate(string $templatePath, string $outputPath, array $variables): void;
}
