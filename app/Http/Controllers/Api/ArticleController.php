<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    /**
     * Get paginated list of articles with filters
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Article::query();

        // Search
        if ($search = $request->input('search')) {
            $query->search($search);
        }

        // Single filters
        $query->filterByDate($request->input('date'))
              ->filterByCategory($request->input('category'))
              ->filterBySource($request->input('source'))
              ->filterByAuthor($request->input('author'));

        // Multiple filters (comma-separated)
        if ($sources = $request->input('sources')) {
            if (is_string($sources)) {
                $sourcesArray = array_filter(array_map('trim', explode(',', $sources)));
            } else {
                $sourcesArray = array_filter((array) $sources);
            }
            
            if (!empty($sourcesArray)) {
                $query->whereIn('source', $sourcesArray);
            }
        }

        if ($categories = $request->input('categories')) {
            $categoriesArray = is_array($categories) ? $categories : explode(',', $categories);
            $query->filterByCategories($categoriesArray);
        }

        if ($authors = $request->input('authors')) {
            $authorsArray = is_array($authors) ? $authors : explode(',', $authors);
            $query->filterByAuthors($authorsArray);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'published_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        $allowedSortFields = ['published_at', 'title', 'source', 'created_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('published_at', 'desc');
        }

        // Pagination
        $perPage = min($request->input('per_page', 20), 100); // Max 100 per page

        $articles = $query->paginate($perPage)->withQueryString();

        return ArticleResource::collection($articles);
    }

    /**
     * Get a single article
     * 
     * @param Article $article
     * @return ArticleResource
     */
    public function show(Article $article): ArticleResource
    {
        return new ArticleResource($article);
    }

    /**
     * Get personalized articles based on user preferences
     * 
     * @param Request $request
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function personalizedFeed(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        $preference = $user->preference;

        // If no preferences, return all articles
        if (!$preference || (
            empty($preference->sources) && 
            empty($preference->categories) && 
            empty($preference->authors)
        )) {
            return response()->json([
                'message' => 'No preferences set. Please set your preferences first.',
                'data' => [],
                'links' => [],
                'meta' => []
            ], 200);
        }

        $query = Article::query();

        // Apply preferences with OR logic
        $query->where(function ($q) use ($preference) {
            $hasConditions = false;

            if (!empty($preference->sources)) {
                $q->orWhereIn('source', $preference->sources);
                $hasConditions = true;
            }

            if (!empty($preference->categories)) {
                $q->orWhereIn('category', $preference->categories);
                $hasConditions = true;
            }

            if (!empty($preference->authors)) {
                $q->orWhereIn('author', $preference->authors);
                $hasConditions = true;
            }

            // If no conditions, return nothing
            if (!$hasConditions) {
                $q->whereRaw('1 = 0');
            }
        });

        // Apply additional filters from request
        if ($search = $request->input('search')) {
            $query->search($search);
        }

        $query->filterByDate($request->input('date'))
              ->filterByCategory($request->input('category'))
              ->filterBySource($request->input('source'));

        // Sorting
        $sortBy = $request->input('sort_by', 'published_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        $allowedSortFields = ['published_at', 'title', 'source', 'created_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('published_at', 'desc');
        }

        // Pagination
        $perPage = min($request->input('per_page', 20), 100);
        $articles = $query->paginate($perPage)->withQueryString();

        return ArticleResource::collection($articles);
    }

    /**
     * Get available filter options
     * 
     * @return JsonResponse
     */
    public function filters(): JsonResponse
    {
        return response()->json([
            'data' => [
                'sources' => Article::distinct()
                    ->orderBy('source')
                    ->pluck('source')
                    ->filter()
                    ->values(),
                    
                'categories' => Article::distinct()
                    ->whereNotNull('category')
                    ->where('category', '!=', '')
                    ->orderBy('category')
                    ->pluck('category')
                    ->filter()
                    ->values(),
                    
                'authors' => Article::distinct()
                    ->whereNotNull('author')
                    ->where('author', '!=', '')
                    ->where('author', '!=', 'Unknown')
                    ->orderBy('author')
                    ->limit(100)
                    ->pluck('author')
                    ->filter()
                    ->values(),
            ]
        ]);
    }
}
