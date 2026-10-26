<?php

namespace App\Http\Controllers;

use App\Models\ApplicationRecord;
use App\Models\Client;
use App\Models\Document;
use App\Models\FollowUp;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('dashboard.view'), 403);
        $filters = $request->validate(['from' => ['sometimes', 'date'], 'to' => ['sometimes', 'date']]);
        $from = Carbon::parse($filters['from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = Carbon::parse($filters['to'] ?? now()->endOfMonth()->toDateString())->endOfDay();
        abort_if($from->gt($to) || $to->diffInDays($from) > 366, 422, 'Choose a date range of at most one year.');
        $clients = Client::visibleTo($user);
        $applications = ApplicationRecord::visibleTo($user);
        $followups = FollowUp::visibleTo($user);
        $docs = Document::whereHas('application', fn ($q) => $q->visibleTo($user));
        $payments = Payment::query()->whereHas('client', fn ($q) => $q->visibleTo($user));
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $monthExpression = match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM')",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };
        $monthly = (clone $clients)->whereBetween('created_at', [$from, $to])->selectRaw($monthExpression.' as month, COUNT(*) as total')->groupBy('month')->orderBy('month')->get();
        $statuses = (clone $applications)->selectRaw('status, COUNT(*) as total')->groupBy('status')->orderBy('status')->get();
        $services = (clone $applications)->join('services', 'applications.service_id', '=', 'services.id')
            ->selectRaw('services.name as service, COUNT(*) as total')->groupBy('services.name')->orderByDesc('total')->limit(8)->get();
        $canPayments = $user->hasPermission('payments.view');

        return response()->json([
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'stats' => [
                'total_clients' => (clone $clients)->count(),
                'new_clients_this_month' => (clone $clients)->whereBetween('created_at', [$monthStart, $monthEnd])->count(),
                'total_leads' => (clone $clients)->where('status', 'lead')->count(),
                'active_applications' => (clone $applications)->whereIn('status', ['active', 'in_progress'])->count(),
                'pending_documents' => (clone $docs)->where('status', 'pending')->count(),
                'follow_ups_due_today' => (clone $followups)->whereDate('due_at', today())->where('status', 'pending')->count(),
                'overdue_follow_ups' => (clone $followups)->where('due_at', '<', now())->where('status', 'pending')->count(),
                'outstanding_payments' => $canPayments ? (clone $payments)->where('status', 'pending')->sum('amount') : null,
                'total_collections' => $canPayments ? (clone $payments)->where('status', 'paid')->whereBetween('paid_at', [$from, $to])->sum('amount') : null,
                'completed_applications' => (clone $applications)->where('status', 'completed')->count(),
            ],
            'recent_clients' => (clone $clients)->latest()->limit(6)->get(['id', 'name', 'email', 'status', 'created_at']),
            'recent_applications' => (clone $applications)->with(['client:id,name', 'service:id,name'])->latest()->limit(6)->get(['id', 'client_id', 'service_id', 'reference', 'status', 'created_at']),
            'upcoming_follow_ups' => (clone $followups)->with('client:id,name')->where('status', 'pending')->whereBetween('due_at', [now(), now()->addDays(7)])->orderBy('due_at')->limit(8)->get(['id', 'client_id', 'due_at', 'notes']),
            'charts' => ['registrations' => $monthly, 'application_statuses' => $statuses, 'services' => $services],
        ]);
    }
}
