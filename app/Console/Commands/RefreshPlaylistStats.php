<?php

namespace App\Console\Commands;

use App\Models\Playlist;
use App\Services\YouTubeService;
use Illuminate\Console\Command;

class RefreshPlaylistStats extends Command
{
    protected $signature = 'playlists:refresh-stats {--limit=50}';
    protected $description = 'Refresh video count and view count for playlists with missing data';

    public function handle(YouTubeService $youtube): int
    {
        $limit = (int) $this->option('limit');

        $playlists = Playlist::where('video_count', 0)
            ->orWhere('view_count', 0)
            ->limit($limit)
            ->get();

        if ($playlists->isEmpty()) {
            $this->info('No playlists need refreshing.');
            return 0;
        }

        $this->info("Refreshing {$playlists->count()} playlists...");
        $bar = $this->output->createProgressBar($playlists->count());

        foreach ($playlists->chunk(10) as $chunk) {
            $ids = $chunk->pluck('playlist_id')->toArray();
            $details = $youtube->getPlaylistDetails($ids);

            foreach ($chunk as $playlist) {
                if (isset($details[$playlist->playlist_id])) {
                    $playlist->update($details[$playlist->playlist_id]);
                }
                $bar->advance();
            }

            usleep(500000);
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done!');

        return 0;
    }
}
