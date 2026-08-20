<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

class CompanyDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'type', 'original_name', 'file_path', 'mime_type', 'file_size',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getUrlAttribute(): string
    {
        return URL::temporarySignedRoute(
            'settings.company.download-document',
            now()->addMinutes(15),
            ['id' => $this->id]
        );
    }
}
