<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class PreferenceController extends Controller
{
    /**
     * Get user preferences
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        $preference = $user->preference;

        // Get available options
        $availableOptions = $this->getAvailableOptions();

        return response()->json([
            'data' => [
                'preferences' => $preference ? [
                    'sources' => $preference->sources ?? [],
                    'categories' => $preference->categories ?? [],
                    'authors' => $preference->authors ?? [],
                ] : [
                    'sources' => [],
                    'categories' => [],
                    'authors' => [],
                ],
                'available_options' => $availableOptions
            ]
        ], 200);
    }

    /**
     * Create or update user preferences
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'sources' => 'nullable|array',
                'sources.*' => 'string',
                'categories' => 'nullable|array',
                'categories.*' => 'string',
                'authors' => 'nullable|array',
                'authors.*' => 'string',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        // Filter out empty values
        $validated['sources'] = array_values(array_filter($validated['sources'] ?? []));
        $validated['categories'] = array_values(array_filter($validated['categories'] ?? []));
        $validated['authors'] = array_values(array_filter($validated['authors'] ?? []));

        $user = $request->user();
        
        $preference = $user->preference()->updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        return response()->json([
            'message' => 'Preferences saved successfully',
            'data' => [
                'sources' => $preference->sources ?? [],
                'categories' => $preference->categories ?? [],
                'authors' => $preference->authors ?? [],
            ]
        ], 200);
    }

    /**
     * Update user preferences (alias for store)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        return $this->store($request);
    }

    /**
     * Delete user preferences
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if ($user->preference) {
            $user->preference->delete();
        }

        return response()->json([
            'message' => 'Preferences deleted successfully'
        ], 200);
    }

    /**
     * Get available filter options
     * 
     * @return array
     */
    private function getAvailableOptions(): array
    {
        return [
            'sources' => Article::distinct()
                ->orderBy('source')
                ->pluck('source')
                ->filter()
                ->values()
                ->toArray(),
                
            'categories' => Article::distinct()
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->orderBy('category')
                ->pluck('category')
                ->filter()
                ->values()
                ->toArray(),
                
            'authors' => Article::distinct()
                ->whereNotNull('author')
                ->where('author', '!=', '')
                ->where('author', '!=', 'Unknown')
                ->orderBy('author')
                ->limit(100)
                ->pluck('author')
                ->filter()
                ->values()
                ->toArray(),
        ];
    }
}