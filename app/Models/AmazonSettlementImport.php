<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AmazonSettlementImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_name', 'settlement_id', 'settlement_start_date', 'settlement_end_date',
        'total_amount', 'status', 'raw_content', 'parse_summary', 'error_message', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'settlement_start_date' => 'date',
            'settlement_end_date' => 'date',
            'total_amount' => 'decimal:2',
            'parse_summary' => 'array',
            'raw_content' => 'string',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AmazonSettlementTransaction::class, 'import_id');
    }

    public static function statusLabels(): array
    {
        return [
            'pending' => 'Pending',
            'parsed' => 'Parsed (Ready for Review)',
            'imported' => 'Imported',
            'failed' => 'Failed',
        ];
    }
}
