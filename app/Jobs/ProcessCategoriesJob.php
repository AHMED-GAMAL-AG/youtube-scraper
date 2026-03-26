<?php

namespace App\Jobs;

use App\Models\FetchJob;
use App\Models\Playlist;
use App\Services\OpenAiService;
use App\Services\YouTubeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCategoriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 1;

    public function __construct(
        private FetchJob $fetchJob
    ) {}

    public function handle(OpenAiService $openAi, YouTubeService $youtube): void
    {
        $this->fetchJob->update(['status' => 'processing']);

        $categories = $this->fetchJob->categories;
        $totalCategories = count($categories);
        $totalFound = 0;

        try {
            foreach ($categories as $index => $category) {
                if ($this->shouldStop()) break;

                $this->updateProgress(
                    "جاري توليد عناوين لـ: {$category}",
                    (int) (($index / $totalCategories) * 100),
                    $totalFound
                );

                $titles = $openAi->generateCourseTitles($category);

                foreach ($titles as $titleIndex => $title) {
                    if ($this->shouldStop()) break;

                    $this->updateProgress(
                        "جاري البحث عن: {$title}",
                        (int) ((($index + ($titleIndex / count($titles))) / $totalCategories) * 100),
                        $totalFound
                    );

                    $playlists = $youtube->searchPlaylists($title);

                    foreach ($playlists as $playlistData) {
                        $playlistData['category'] = $category;

                        Playlist::firstOrCreate(
                            ['playlist_id' => $playlistData['playlist_id']],
                            $playlistData
                        );

                        $totalFound++;
                    }

                    usleep(200000);
                }
            }

            $status = $this->shouldStop() ? 'completed' : 'completed';
            $this->fetchJob->update([
                'status' => $status,
                'progress' => 100,
                'current_step' => 'تم الانتهاء',
                'total_found' => $totalFound,
            ]);
        } catch (\Exception $e) {
            Log::error('ProcessCategoriesJob failed', ['error' => $e->getMessage()]);
            $this->fetchJob->update([
                'status' => 'failed',
                'current_step' => 'حدث خطأ: ' . $e->getMessage(),
                'total_found' => $totalFound,
            ]);
        }
    }

    private function shouldStop(): bool
    {
        $this->fetchJob->refresh();
        return $this->fetchJob->stopped;
    }

    private function updateProgress(string $step, int $progress, int $totalFound): void
    {
        $this->fetchJob->update([
            'current_step' => $step,
            'progress' => min($progress, 99),
            'total_found' => $totalFound,
        ]);
    }
}
