<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService
    ) {}

    public function index(Request $request)
    {
        $range = $request->input('range', '30');
        $from = $request->input('from');
        $to = $request->input('to');

        if ($from && $to) {
            $fromDate = Carbon::parse($from)->startOfDay();
            $toDate = Carbon::parse($to)->endOfDay();
        } else {
            $fromDate = now()->subDays((int) $range)->startOfDay();
            $toDate = now()->endOfDay();
        }

        $data = $this->reportService->getOverviewReport($fromDate, $toDate);

        return view('reports.index', array_merge($data, [
            'range' => $range,
            'from' => $fromDate->toDateString(),
            'to' => $toDate->toDateString(),
        ]));
    }

    public function campaign(Request $request, $id)
    {
        $campaign = Campaign::findOrFail($id);
        $data = $this->reportService->getCampaignReport($campaign);

        return view('reports.campaign', $data);
    }

    public function export(Request $request): StreamedResponse
    {
        $range = $request->input('range', '30');
        $fromDate = now()->subDays((int) $range)->startOfDay();
        $toDate = now()->endOfDay();

        $data = $this->reportService->getOverviewReport($fromDate, $toDate);
        $csv = $this->reportService->exportCsv($data);

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'campaign-report-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
