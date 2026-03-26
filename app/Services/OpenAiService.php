<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class OpenAiService
{
    public function generateCourseTitles(string $category): array
    {
        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant that generates educational YouTube course search queries. Always respond with a valid JSON array of strings only, no other text.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Generate 15 educational YouTube playlist/course search queries in Arabic for the category: \"{$category}\". The queries should be diverse and cover different aspects of the category. Return ONLY a JSON array of strings, example: [\"query1\", \"query2\"]",
                    ],
                ],
                'temperature' => 0.8,
                'max_tokens' => 1000,
            ]);

            $content = $response->choices[0]->message->content;
            $titles = json_decode($content, true);

            if (!is_array($titles) || empty($titles)) {
                Log::warning("OpenAI returned invalid format for category: {$category}", ['content' => $content]);
                return $this->fallbackTitles($category);
            }

            return array_slice($titles, 0, 20);
        } catch (\Exception $e) {
            Log::error("OpenAI API error for category: {$category}", ['error' => $e->getMessage()]);
            return $this->fallbackTitles($category);
        }
    }

    private function fallbackTitles(string $category): array
    {
        return [
            "دورة {$category} للمبتدئين",
            "تعلم {$category} من الصفر",
            "دورة {$category} الشاملة",
            "أساسيات {$category}",
            "{$category} للمحترفين",
            "دورة {$category} كاملة بالعربي",
            "تعلم {$category} خطوة بخطوة",
            "شرح {$category} بالعربي",
            "كورس {$category} مجاني",
            "احتراف {$category}",
        ];
    }
}
