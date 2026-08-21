<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = collect();
        $pagination = null;

        try {
            $logs = Activity::latest()->paginate(25);
            $pagination = $logs;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Activity log table not available: ' . $e->getMessage());
        }

        return view('settings.audit', [
            'logs' => $logs,
            'pagination' => $pagination,
        ]);
    }
}
