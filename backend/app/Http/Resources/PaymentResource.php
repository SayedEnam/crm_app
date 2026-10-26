<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'client_id' => $this->client_id, 'client' => $this->whenLoaded('client', fn () => ['id' => $this->client->id, 'name' => $this->client->name]), 'application_id' => $this->application_id, 'amount' => $this->amount, 'currency' => $this->currency, 'status' => $this->status, 'method' => $this->method, 'reference' => $this->reference, 'paid_at' => $this->paid_at, 'notes' => $this->notes, 'created_at' => $this->created_at];
    }
}
