<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientApplicationRequest;
use App\Http\Requests\PublicIntakeRequest;
use App\Models\ApplicationRecord;
use App\Models\Client;
use App\Models\ClientPortalAccount;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class PublicIntakeController extends Controller
{
    public function services(): JsonResponse
    {
        return response()->json([
            'data' => Service::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(PublicIntakeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $passport = $data['passport'];
        $supportingDocuments = $data['supporting_documents'] ?? [];
        $storedPaths = [];

        try {
            $passportPath = $passport->store('client-submissions/'.now()->format('Y/m'), 'local');
            if (! $passportPath) {
                throw new RuntimeException('The passport file could not be stored.');
            }
            $storedPaths[] = $passportPath;

            $storedDocuments = [];
            foreach ($supportingDocuments as $document) {
                $path = $document['file']->store('client-submissions/'.now()->format('Y/m'), 'local');
                if (! $path) {
                    throw new RuntimeException('A supporting document could not be stored.');
                }
                $storedPaths[] = $path;
                $storedDocuments[] = ['category' => $document['category'], 'file' => $document['file'], 'path' => $path];
            }

            $reference = 'PUB-'.Str::upper(Str::random(10));

            DB::transaction(function () use ($data, $passport, $passportPath, $storedDocuments, $reference): void {
                $client = Client::create([
                    'name' => $data['name'],
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'country' => $data['country'] ?? null,
                    'status' => 'lead',
                    'notes' => $data['notes'] ?? null,
                ]);

                $application = ApplicationRecord::create([
                    'client_id' => $client->id,
                    'service_id' => $data['service_id'],
                    'reference' => $reference,
                    'status' => 'pending',
                    'notes' => 'Submitted through the public client intake form.',
                ]);

                $application->documents()->create([
                    'name' => $this->documentName('Passport', $passport),
                    'file_path' => $passportPath,
                    'mime_type' => $passport->getMimeType(),
                    'status' => 'pending',
                    'notes' => 'Passport uploaded through the public client intake form.',
                ]);

                foreach ($storedDocuments as $document) {
                    $application->documents()->create([
                        'name' => $this->documentName(ucfirst($document['category']), $document['file']),
                        'file_path' => $document['path'],
                        'mime_type' => $document['file']->getMimeType(),
                        'status' => 'pending',
                        'notes' => 'Category: '.$document['category'].'. Uploaded through the public client intake form.',
                    ]);
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        return response()->json([
            'message' => 'Your information and documents were submitted successfully.',
            'reference' => $reference,
        ], 201);
    }

    public function storeForClient(ClientApplicationRequest $request): JsonResponse
    {
        /** @var ClientPortalAccount $account */
        $account = $request->user();
        $data = $request->validated();
        $passport = $data['passport'];
        $supportingDocuments = $data['supporting_documents'] ?? [];
        $storedPaths = [];

        try {
            $passportPath = $passport->store('client-submissions/'.now()->format('Y/m'), 'local');
            if (! $passportPath) {
                throw new RuntimeException('The passport file could not be stored.');
            }
            $storedPaths[] = $passportPath;
            $storedDocuments = [];
            foreach ($supportingDocuments as $document) {
                $path = $document['file']->store('client-submissions/'.now()->format('Y/m'), 'local');
                if (! $path) {
                    throw new RuntimeException('A supporting document could not be stored.');
                }
                $storedPaths[] = $path;
                $storedDocuments[] = ['category' => $document['category'], 'file' => $document['file'], 'path' => $path];
            }

            $reference = 'CLI-'.Str::upper(Str::random(10));
            DB::transaction(function () use ($account, $data, $passport, $passportPath, $storedDocuments, $reference): void {
                $account->client()->update(['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'], 'address' => $data['address'] ?? null]);
                $account->update(['name' => $data['name'], 'email' => $data['email']]);
                $application = ApplicationRecord::create(['client_id' => $account->client_id, 'service_id' => $data['service_id'], 'reference' => $reference, 'status' => 'pending', 'notes' => $data['notes'] ?? null]);
                $application->documents()->create(['name' => $this->documentName('Passport', $passport), 'file_path' => $passportPath, 'mime_type' => $passport->getMimeType(), 'status' => 'pending', 'notes' => 'Passport submitted through the authenticated client portal.']);
                foreach ($storedDocuments as $document) {
                    $application->documents()->create(['name' => $this->documentName(ucfirst($document['category']), $document['file']), 'file_path' => $document['path'], 'mime_type' => $document['file']->getMimeType(), 'status' => 'pending', 'notes' => 'Category: '.$document['category'].'. Submitted through the authenticated client portal.']);
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        return response()->json(['message' => 'Your service request and documents were submitted successfully.', 'reference' => $reference], 201);
    }

    private function documentName(string $category, UploadedFile $file): string
    {
        $originalName = basename(str_replace('\\', '/', $file->getClientOriginalName()));

        return $category.' - '.Str::limit($originalName, 160, '');
    }
}
