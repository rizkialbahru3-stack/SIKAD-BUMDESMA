<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    protected $fillable = [
        'type',
        'slug',
        'label',
        'title',
        'excerpt',
        'body',
        'image_url',
        'published_at',
        'is_published',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_published' => 'boolean',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(function (Builder $query) {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }
}
