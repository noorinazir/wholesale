<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class AuditLogService
{
    public function log(string $action, string $object, ?string $description = null, array $properties = []): void
    {
        $user = Auth::user();

        activity()
            ->causedBy($user)
            ->withProperties(array_merge([
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'object' => $object,
            ], $properties))
            ->log($action . ($description ? ': ' . $description : ''));
    }

    public function getLogs(int $perPage = 25)
    {
        return Activity::latest()->paginate($perPage);
    }
}
