<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\ApplicationRecord;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Document::class);
        $q = Document::whereHas('application', fn ($x) => $x->visibleTo($request->user()));
        if ($id = $request->query('application_id')) {
            $q->where('application_id', $id);
        }

return DocumentResource::collection($q->latest()->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function store(DocumentRequest $request)
    {
        Gate::authorize('create', Document::class);
        $app = ApplicationRecord::visibleTo($request->user())->findOrFail($request->integer('application_id'));
        $file = $request->file('file');
        $path = $file->store('documents', 'local');
        $document = Document::create(['application_id' => $app->id, 'uploaded_by' => $request->user()->id, 'name' => $request->input('name') ?: $file->getClientOriginalName(), 'file_path' => $path, 'mime_type' => $file->getMimeType(), 'status' => $request->input('status', 'pending'), 'notes' => $request->input('notes')]);

        return (new DocumentResource($document))->response()->setStatusCode(201);
    }

    public function show(Request $request, Document $document)
    {
        Gate::authorize('view', $document);

        return new DocumentResource($document);
    }

    public function download(Request $request, Document $document)
    {
        Gate::authorize('view', $document);
        abort_unless($document->file_path && Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->name);
    }

    public function update(Request $request, Document $document)
    {
        Gate::authorize('update', $document);
        $data = $request->validate(['name' => ['sometimes', 'required', 'string', 'max:180'], 'status' => ['sometimes', 'required', 'in:pending,received,approved,rejected'], 'notes' => ['nullable', 'string', 'max:5000']]);
        $document->update($data);

        return new DocumentResource($document->refresh());
    }

    public function destroy(Request $request, Document $document)
    {
        Gate::authorize('delete', $document);
        if ($document->file_path) {
            Storage::disk('local')->delete($document->file_path);
        } $document->delete();

        return response()->noContent();
    }
}
