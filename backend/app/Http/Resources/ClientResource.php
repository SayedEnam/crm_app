<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'email' => $this->email, 'phone' => $this->phone, 'company' => $this->company, 'country' => $this->country, 'address' => $this->address, 'status' => $this->status, 'notes' => $this->notes, 'assigned_user_id' => $this->assigned_user_id, 'portal_account_exists' => (bool) $this->portal_account_exists, 'created_at' => $this->created_at, 'updated_at' => $this->updated_at];
    }
}
