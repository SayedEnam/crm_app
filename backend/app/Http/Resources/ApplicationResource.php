<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'client_id' => $this->client_id, 'client' => $this->whenLoaded('client', fn () => ['id' => $this->client->id, 'name' => $this->client->name]), 'service_id' => $this->service_id, 'service' => $this->whenLoaded('service', fn () => ['id' => $this->service->id, 'name' => $this->service->name, 'slug' => $this->service->slug]), 'reference' => $this->reference, 'status' => $this->status, 'started_at' => $this->started_at, 'completed_at' => $this->completed_at, 'notes' => $this->notes, 'assigned_user_id' => $this->assigned_user_id, 'created_at' => $this->created_at];
    }
}
