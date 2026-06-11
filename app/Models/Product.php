<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'image_path',
        'is_active',
    ];

    protected $casts = [
        'price' => 'float',
        'stock' => 'integer',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $searchTerm): Builder
    {
        return $query->when($searchTerm, function (Builder $q) use ($searchTerm) {
            $q->where(function ($innerQuery) use ($searchTerm) {
                $innerQuery->where('name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('description', 'like', '%'.$searchTerm.'%');
            });
        });
    }

    public function scopeByCategory(Builder $query, ?string $categorySlug): Builder
    {
        return $query->when($categorySlug, function (Builder $q) use ($categorySlug) {
            $q->whereHas('category', function ($innerQuery) use ($categorySlug) {
                $innerQuery->where('slug', $categorySlug);
            });
        });
    }

    public function scopeSort(Builder $query, ?string $sortBy, ?string $sortOrder): Builder
    {
        $sortBy = $sortBy ?: 'created_at';
        $sortOrder = $sortOrder === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['price', 'created_at', 'name'])) {
            return $query->orderBy($sortBy, $sortOrder);
        }

        return $query->orderBy('created_at', 'desc');
    }
}
