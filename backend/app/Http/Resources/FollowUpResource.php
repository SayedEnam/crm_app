<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FollowUpResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'client_id' => $this->client_id, 'client' => $this->whenLoaded('client', fn () => ['id' => $this->client->id, 'name' => $this->client->name]), 'application_id' => $this->application_id, 'due_at' => $this->due_at, 'status' => $this->status, 'notes' => $this->notes, 'assigned_user_id' => $this->assigned_user_id];
    }
}
