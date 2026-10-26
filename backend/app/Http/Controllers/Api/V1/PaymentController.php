<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\ApplicationRecord;
use App\Models\Client;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Payment::class);
        $q = Payment::whereHas('client', fn ($x) => $x->visibleTo($request->user()))->with('client:id,name');
        if ($s = $request->query('status')) {
            $q->where('status', $s);
        }

return PaymentResource::collection($q->latest()->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function store(PaymentRequest $request)
    {
        Gate::authorize('create', Payment::class);
        $data = $request->validated();
        Client::visibleTo($request->user())->findOrFail($data['client_id']);
        if (! empty($data['application_id'])) {
            $app = ApplicationRecord::visibleTo($request->user())->findOrFail($data['application_id']);
            abort_unless((int) $app->client_id === (int) $data['client_id'], 422, 'Application must belong to the selected client.');
        } if (($data['status'] ?? 'pending') === 'paid' && ! isset($data['paid_at'])) {
            $data['paid_at'] = today();
        } $row = Payment::create($data);

        return (new PaymentResource($row->load('client')))->response()->setStatusCode(201);
    }

    public function show(Request $request, Payment $payment)
    {
        Gate::authorize('view', $payment);

        return new PaymentResource($payment->load('client'));
    }

    public function update(PaymentRequest $request, Payment $payment)
    {
        Gate::authorize('update', $payment);
        $data = $request->validated();
        Client::visibleTo($request->user())->findOrFail($data['client_id']);
        if (! empty($data['application_id'])) {
            $app = ApplicationRecord::visibleTo($request->user())->findOrFail($data['application_id']);
            abort_unless((int) $app->client_id === (int) $data['client_id'], 422);
        } if (($data['status'] ?? $payment->status) === 'paid' && ! isset($data['paid_at'])) {
            $data['paid_at'] = $payment->paid_at ?? today();
        } $payment->update($data);

        return new PaymentResource($payment->refresh()->load('client'));
    }

    public function destroy(Request $request, Payment $payment)
    {
        Gate::authorize('delete', $payment);
        $payment->delete();

        return response()->noContent();
    }
}
