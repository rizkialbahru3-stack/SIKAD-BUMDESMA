<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLocation extends Model
{
    protected $fillable = [
        'name', 'address', 'latitude', 'longitude',
        'radius_meters', 'max_accuracy_meters', 'enforce_radius', 'enforce_checkout_radius', 'is_active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'enforce_radius' => 'boolean',
        'enforce_checkout_radius' => 'boolean',
        'is_active' => 'boolean',
    ];

    public static function active(): ?self
    {
        return static::where('is_active', true)->orderBy('id')->first();
    }
}
