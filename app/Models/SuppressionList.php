<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuppressionList extends Model
{
    use HasFactory;

    protected $table = 'suppression_list';

    protected $fillable = [
        'vendor_id', 'email', 'reason', 'notes', 'suppressed_at',
    ];

    protected function casts(): array
    {
        return [
            'suppressed_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public static function isSuppressed(string $email): bool
    {
        return static::where('email', $email)->exists();
    }
}
