<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'application_id' => $this->application_id, 'name' => $this->name, 'mime_type' => $this->mime_type, 'status' => $this->status, 'notes' => $this->notes, 'created_at' => $this->created_at, 'download_url' => route('api.v1.documents.download', $this->id)];
    }
}
