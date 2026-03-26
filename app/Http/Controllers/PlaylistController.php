<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaylistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $category = $request->input('category');

        $playlists = Playlist::byCategory($category)
            ->latest()
            ->paginate(8);

        $categories = Playlist::select('category')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        $totalCount = Playlist::count();

        return response()->json([
            'playlists' => $playlists,
            'categories' => $categories,
            'totalCount' => $totalCount,
        ]);
    }
}
