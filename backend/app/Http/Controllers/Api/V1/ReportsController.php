<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApplicationRecord;
use App\Models\Client;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    private function dates(Request $request): array
    {
        $data = $request->validate(['from' => ['required', 'date'], 'to' => ['required', 'date']]);
        $from = Carbon::parse($data['from'])->startOfDay();
        $to = Carbon::parse($data['to'])->endOfDay();
        abort_if($from->gt($to) || $to->diffInDays($from) > 366, 422, 'Choose a date range of at most one year.');

        return [$from, $to];
    }

    private function report(Request $request, Carbon $from, Carbon $to): array
    {
        $user = $request->user();
        abort_unless($user->hasPermission('reports.view'), 403);
        $clients = Client::visibleTo($user)->whereBetween('created_at', [$from, $to]);
        $apps = ApplicationRecord::visibleTo($user)->whereBetween('applications.created_at', [$from, $to]);
        $expression = match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', created_at)",'pgsql' => "to_char(created_at, 'YYYY-MM')",default => "DATE_FORMAT(created_at, '%Y-%m')"
        };
        $registration = (clone $clients)->selectRaw($expression.' as month, COUNT(*) as total')->groupBy('month')->orderBy('month')->get();
        $statuses = (clone $apps)->selectRaw('status, COUNT(*) as total')->groupBy('status')->orderBy('status')->get();
        $services = (clone $apps)->join('services', 'applications.service_id', '=', 'services.id')->selectRaw('services.name as service, COUNT(*) as total')->groupBy('services.name')->orderByDesc('total')->get();
        $result = ['filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString()], 'clients' => ['total' => (clone $clients)->count(), 'new_leads' => (clone $clients)->where('status', 'lead')->count(), 'registrations' => $registration], 'applications' => ['total' => (clone $apps)->count(), 'statuses' => $statuses, 'services' => $services]];
        if ($user->hasPermission('payments.view')) {
            $payments = Payment::whereHas('client', fn ($q) => $q->visibleTo($user));
            $result['payments'] = ['outstanding' => (clone $payments)->where('status', 'pending')->sum('amount'), 'collections' => (clone $payments)->where('status', 'paid')->whereBetween('paid_at', [$from, $to])->sum('amount')];
        }

        return $result;
    }

    public function index(Request $request)
    {
        [$from,$to] = $this->dates($request);

        return response()->json($this->report($request, $from, $to));
    }

    public function export(Request $request): StreamedResponse
    {
        [$from,$to] = $this->dates($request);
        $report = $this->report($request, $from, $to);

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            $safe = static fn ($value) => is_string($value) && preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
            fputcsv($out, ['Group', 'Metric', 'Value']);
            fputcsv($out, ['Clients', 'Total', $report['clients']['total']]);
            fputcsv($out, ['Clients', 'New leads', $report['clients']['new_leads']]);
            foreach ($report['clients']['registrations'] as $row) {
                fputcsv($out, ['Registrations', $safe($row->month), $row->total]);
            }fputcsv($out, ['Applications', 'Total', $report['applications']['total']]);
            foreach ($report['applications']['statuses'] as $row) {
                fputcsv($out, ['Application status', $safe($row->status), $row->total]);
            }foreach ($report['applications']['services'] as $row) {
                fputcsv($out, ['Service', $safe($row->service), $row->total]);
            }if (isset($report['payments'])) {
                fputcsv($out, ['Payments', 'Outstanding', $report['payments']['outstanding']]);
                fputcsv($out, ['Payments', 'Collections', $report['payments']['collections']]);
            }fclose($out);
        }, 'crm-report-'.$from->toDateString().'-'.$to->toDateString().'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
