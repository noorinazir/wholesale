<?php

namespace App\Enums;

enum VendorStatus: string
{
    case New = 'new';
    case Researching = 'researching';
    case ReadyToContact = 'ready_to_contact';
    case Contacted = 'contacted';
    case Replied = 'replied';
    case Interested = 'interested';
    case NotInterested = 'not_interested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case FollowUpRequired = 'follow_up_required';
    case OptedOut = 'opted_out';
    case InvalidEmail = 'invalid_email';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Researching => 'Researching',
            self::ReadyToContact => 'Ready to Contact',
            self::Contacted => 'Contacted',
            self::Replied => 'Replied',
            self::Interested => 'Interested',
            self::NotInterested => 'Not Interested',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::FollowUpRequired => 'Follow Up Required',
            self::OptedOut => 'Opted Out',
            self::InvalidEmail => 'Invalid Email',
            self::Archived => 'Archived',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New, self::Researching => 'blue',
            self::ReadyToContact, self::Contacted => 'indigo',
            self::Replied => 'purple',
            self::Interested, self::Approved => 'green',
            self::NotInterested, self::Rejected, self::OptedOut => 'red',
            self::FollowUpRequired => 'yellow',
            self::InvalidEmail => 'orange',
            self::Archived => 'gray',
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
