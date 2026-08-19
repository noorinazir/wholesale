<?php

namespace App\Enums;

enum EmailStatus: string
{
    case NotSent = 'not_sent';
    case Draft = 'draft';
    case Ready = 'ready';
    case Scheduled = 'scheduled';
    case Sending = 'sending';
    case Sent = 'sent';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case OptedOut = 'opted_out';

    public function label(): string
    {
        return match ($this) {
            self::NotSent => 'Not Sent',
            self::Draft => 'Draft',
            self::Ready => 'Ready',
            self::Scheduled => 'Scheduled',
            self::Sending => 'Sending',
            self::Sent => 'Sent',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
            self::OptedOut => 'Opted Out',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NotSent, self::Draft, self::Cancelled => 'gray',
            self::Ready => 'blue',
            self::Scheduled => 'indigo',
            self::Sending => 'yellow',
            self::Sent => 'green',
            self::Failed, self::OptedOut => 'red',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn($case) => [$case->value => $case->label()])->toArray();
    }

    public static function values(): array
    {
        return collect(self::cases())->map(fn($case) => $case->value)->toArray();
    }

    public static function colors(): array
    {
        return collect(self::cases())->mapWithKeys(fn($case) => [$case->value => $case->color()])->toArray();
    }
}
