<?php

declare(strict_types=1);

namespace Modules\AIEngine\Contracts;

interface AIProviderInterface
{
    /**
     * توليد نص من prompt
     */
    public function generate(string $prompt, array $options = []): string;

    /**
     * تحليل بيانات مُهيكلة
     */
    public function analyze(string $prompt, array $data, array $options = []): array;

    /**
     * تصنيف نص (مثلاً: تصنيف بلاغ صيانة)
     */
    public function classify(string $text, array $categories): string;
}
