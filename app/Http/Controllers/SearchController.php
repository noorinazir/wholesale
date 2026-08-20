<?php

namespace App\Http\Controllers;

use App\Services\Search\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(private GlobalSearchService $globalSearchService)
    {
    }

    public function global(Request $request): JsonResponse
    {
        $q = $request->input('q', '');
        $user = auth()->user();

        if (!$user) {
            return response()->json([]);
        }

        $results = $this->globalSearchService->search($user, $q);

        return response()->json($results);
    }
}
