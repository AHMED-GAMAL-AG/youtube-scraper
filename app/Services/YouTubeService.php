<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeService
{
    private string $apiKey;
    private string $baseUrl = 'https://www.googleapis.com/youtube/v3';

    public function __construct()
    {
        $this->apiKey = config('services.youtube.api_key');
    }

    public function searchPlaylists(string $query): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/search", [
                'part' => 'snippet',
                'q' => $query,
                'type' => 'playlist',
                'maxResults' => 2,
                'relevanceLanguage' => 'ar',
                'key' => $this->apiKey,
            ]);

            if (!$response->successful()) {
                Log::warning("YouTube search failed for: {$query}", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $items = $response->json('items', []);
            $playlists = [];

            foreach ($items as $item) {
                $playlistId = $item['id']['playlistId'] ?? null;
                if (!$playlistId) continue;

                $playlists[] = [
                    'playlist_id' => $playlistId,
                    'title' => $item['snippet']['title'] ?? '',
                    'description' => $item['snippet']['description'] ?? '',
                    'thumbnail' => $item['snippet']['thumbnails']['high']['url']
                        ?? $item['snippet']['thumbnails']['medium']['url']
                        ?? $item['snippet']['thumbnails']['default']['url']
                        ?? '',
                    'channel_name' => $item['snippet']['channelTitle'] ?? '',
                ];
            }

            if (!empty($playlists)) {
                $details = $this->getPlaylistDetails(array_column($playlists, 'playlist_id'));
                foreach ($playlists as &$playlist) {
                    if (isset($details[$playlist['playlist_id']])) {
                        $playlist = array_merge($playlist, $details[$playlist['playlist_id']]);
                    }
                }
            }

            return $playlists;
        } catch (\Exception $e) {
            Log::error("YouTube API error for query: {$query}", ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function getPlaylistDetails(array $playlistIds): array
    {
        if (empty($playlistIds)) return [];

        try {
            $response = Http::get("{$this->baseUrl}/playlists", [
                'part' => 'contentDetails,statistics',
                'id' => implode(',', $playlistIds),
                'key' => $this->apiKey,
            ]);

            if (!$response->successful()) return [];

            $details = [];
            foreach ($response->json('items', []) as $item) {
                $id = $item['id'];
                $videoCount = $item['contentDetails']['itemCount'] ?? 0;
                $viewCount = $item['statistics']['viewCount'] ?? 0;

                $details[$id] = [
                    'video_count' => (int) $videoCount,
                    'view_count' => (int) $viewCount,
                    'total_duration' => $this->formatDuration($videoCount),
                ];
            }

            return $details;
        } catch (\Exception $e) {
            Log::error("YouTube playlist details error", ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function formatDuration(int $videoCount): string
    {
        $estimatedMinutes = $videoCount * 15;
        $hours = intdiv($estimatedMinutes, 60);
        $minutes = $estimatedMinutes % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours} ساعات {$minutes} دقيقة";
        } elseif ($hours > 0) {
            return "{$hours} ساعات";
        }

        return "{$minutes} دقيقة";
    }
}
