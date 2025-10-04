<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Article extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'title',
        'content',
        'author',
        'source',
        'source_id',
        'category',
        'published_at',
        'url',
        'image_url',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (!$search) return $query;
        
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%");
        });
    }

    public function scopeFilterByDate(Builder $query, ?string $date): Builder
    {
        if (!$date) return $query;
        
        return $query->whereDate('published_at', '>=', $date);
    }

    public function scopeFilterByCategory(Builder $query, ?string $category): Builder
    {
        if (!$category) return $query;
        
        return $query->where('category', $category);
    }

    public function scopeFilterBySource(Builder $query, ?string $source): Builder
    {
        if (!$source) return $query;
        
        return $query->where('source', $source);
    }

    public function scopeFilterByAuthor(Builder $query, ?string $author): Builder
    {
        if (!$author) return $query;
        
        return $query->where('author', $author);
    }
}
