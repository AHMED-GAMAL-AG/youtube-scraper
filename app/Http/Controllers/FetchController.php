<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCategoriesJob;
use App\Models\FetchJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FetchController extends Controller
{
    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'categories' => 'required|string|min:2',
        ]);

        $categories = array_filter(
            array_map('trim', explode("\n", $request->input('categories')))
        );

        if (empty($categories)) {
            return response()->json(['error' => 'يرجى إدخال تصنيف واحد على الأقل'], 422);
        }

        $fetchJob = FetchJob::create([
            'categories' => array_values($categories),
            'status' => 'pending',
        ]);

        ProcessCategoriesJob::dispatch($fetchJob);

        return response()->json([
            'id' => $fetchJob->id,
            'message' => 'تم بدء عملية الجمع',
        ]);
    }

    public function status(FetchJob $fetchJob): JsonResponse
    {
        return response()->json([
            'id' => $fetchJob->id,
            'status' => $fetchJob->status,
            'progress' => $fetchJob->progress,
            'current_step' => $fetchJob->current_step,
            'total_found' => $fetchJob->total_found,
            'stopped' => $fetchJob->stopped,
        ]);
    }

    public function stop(FetchJob $fetchJob): JsonResponse
    {
        $fetchJob->update(['stopped' => true]);

        return response()->json(['message' => 'تم طلب الإيقاف']);
    }
}
