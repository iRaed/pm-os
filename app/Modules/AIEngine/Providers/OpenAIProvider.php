<?php

declare(strict_types=1);

namespace Modules\AIEngine\Providers;

use Modules\AIEngine\Contracts\AIProviderInterface;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAIProvider implements AIProviderInterface
{
    public function generate(string $prompt, array $options = []): string
    {
        $response = OpenAI::chat()->create([
            'model' => config('openai.model', 'gpt-4o'),
            'messages' => [
                ['role' => 'system', 'content' => 'أنت مساعد ذكي متخصص في إدارة الأملاك والعقارات في المملكة العربية السعودية. تجيب بالعربية الفصحى بأسلوب مهني.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => $options['max_tokens'] ?? 1000,
            'temperature' => $options['temperature'] ?? 0.3,
        ]);

        return $response->choices[0]->message->content ?? '';
    }

    public function analyze(string $prompt, array $data, array $options = []): array
    {
        $fullPrompt = $prompt . "\n\nالبيانات:\n" . json_encode($data, JSON_UNESCAPED_UNICODE);
        $fullPrompt .= "\n\nأجب بصيغة JSON فقط.";

        $response = $this->generate($fullPrompt, array_merge($options, ['temperature' => 0.1]));

        $cleaned = trim($response);
        $cleaned = preg_replace('/^```json\s*/', '', $cleaned);
        $cleaned = preg_replace('/\s*```$/', '', $cleaned);

        return json_decode($cleaned, true) ?? [];
    }

    public function classify(string $text, array $categories): string
    {
        $categoriesList = implode(', ', $categories);
        $prompt = "صنّف النص التالي إلى واحدة من هذه الفئات: [{$categoriesList}]\n\nالنص: {$text}\n\nأجب بكلمة واحدة فقط (الفئة):";

        $result = $this->generate($prompt, ['max_tokens' => 50, 'temperature' => 0]);
        return trim(strtolower($result));
    }
}
