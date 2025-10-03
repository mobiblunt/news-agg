<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ArticleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Article::query();

        // Search
        if ($search = $request->input('search')) {
            $query->search($search);
        }

        // Filters
        $query->filterByDate($request->input('date'))
              ->filterByCategory($request->input('category'))
              ->filterBySource($request->input('source'))
              ->filterByAuthor($request->input('author'));

        // User preferences
        if ($request->user() && $request->boolean('use_preferences')) {
            $preferences = $request->user()->preference;
            
            if ($preferences) {
                if ($preferences->sources) {
                    $query->whereIn('source', $preferences->sources);
                }
                if ($preferences->categories) {
                    $query->whereIn('category', $preferences->categories);
                }
                if ($preferences->authors) {
                    $query->whereIn('author', $preferences->authors);
                }
            }
        }

        $articles = $query->orderBy('published_at', 'desc')
                          ->paginate($request->input('per_page', 20));

        return ArticleResource::collection($articles);
    }
}