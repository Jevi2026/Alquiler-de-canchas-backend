<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cancha extends Model
{
    protected $fillable = ['name', 'surface', 'price_per_hour', 'opens_at', 'closes_at', 'is_active', 'photo_path'];

    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path
            ? request()->getSchemeAndHttpHost().'/api/canchas/'.$this->id.'/photo'
            : null;
    }

    protected function casts(): array
    {
        return ['price_per_hour' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
