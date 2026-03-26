<?php

namespace App\Http\Controllers;

use App\Models\Playlist;

class HomeController extends Controller
{
    public function index()
    {
        $playlists = Playlist::latest()->paginate(8);
        $categories = Playlist::select('category')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        $totalCount = Playlist::count();

        return view('home', compact('playlists', 'categories', 'totalCount'));
    }
}
