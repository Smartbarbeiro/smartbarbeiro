<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ClientHaircutPhoto extends Model
{
    protected $fillable = [
        'user_id',
        'barbershop_user_id',
        'photo_path',
    ];

    protected $appends = [
        'photo_url',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(User::class, 'barbershop_user_id');
    }

    /**
     * @return Attribute<string, never>
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn (): string => '/uploads/'.ltrim($this->photo_path, '/'));
    }

    public function deleteStoredPhoto(): void
    {
        if ($this->photo_path) {
            Storage::disk('public')->delete($this->photo_path);
        }
    }
}
