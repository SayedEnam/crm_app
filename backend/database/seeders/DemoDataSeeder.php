<?php

namespace Database\Seeders;

use App\Models\ApplicationRecord;
use App\Models\Client;
use App\Models\ClientPortalAccount;
use App\Models\FollowUp;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed synthetic CRM records and a client portal account for demonstrations.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $roleNames = [
            'super-admin' => 'Super Admin',
            'admin' => 'Admin',
            'manager' => 'Manager',
            'staff' => 'Staff',
            'accountant' => 'Accountant',
            'viewer' => 'Viewer',
        ];
        $demoUsers = [];
        foreach ($roleNames as $slug => $label) {
            $baseRole = Role::where('slug', $slug)->firstOrFail();
            $demoRole = Role::updateOrCreate(
                ['slug' => 'demo-'.$slug],
                ['name' => 'Demo '.$label.' (Read-only)'],
            );
            $demoRole->permissions()->sync($baseRole->permissions()->where('slug', 'like', '%.view')->pluck('permissions.id'));

            $user = User::updateOrCreate(
                ['email' => 'demo.'.$slug.'@example.com'],
                [
                    'name' => 'Demo '.$label,
                    'password' => Hash::make('DemoRole@2026!'),
                    'is_active' => true,
                    'assigned_manager_id' => null,
                ],
            );
            $user->roles()->sync([$demoRole->id]);
            $demoUsers[$slug] = $user;
        }

        $client = Client::updateOrCreate(
            ['email' => 'demo.client@example.com'],
            [
                'name' => 'Demo Client — Amina Rahman',
                'phone' => '+880 1700 000000',
                'company' => 'Demo Account',
                'country' => 'Bangladesh',
                'address' => 'Demo address — no real client information',
                'notes' => 'Synthetic demonstration record. Do not use for real client work.',
                'status' => 'active',
                'assigned_user_id' => $demoUsers['staff']->id,
            ],
        );

        ClientPortalAccount::updateOrCreate(
            ['email' => 'demo.client@example.com'],
            [
                'client_id' => $client->id,
                'name' => $client->name,
                'password' => Hash::make('DemoClient@2026!'),
                'is_active' => true,
            ],
        );

        $services = Service::query()->where('is_active', true)->get()->keyBy('slug');
        $applicationSpecs = [
            ['slug' => 'visa-processing', 'reference' => 'DEMO-VISA-001', 'status' => 'in_progress', 'notes' => 'Demo case: visa application review.'],
            ['slug' => 'student-admissions', 'reference' => 'DEMO-STU-001', 'status' => 'pending_documents', 'notes' => 'Demo case: student admission documents requested.'],
            ['slug' => 'document-attestation', 'reference' => 'DEMO-ATT-001', 'status' => 'completed', 'notes' => 'Demo case: document attestation completed.'],
        ];

        foreach ($applicationSpecs as $spec) {
            $service = $services->get($spec['slug']);
            if (! $service) {
                continue;
            }

            $application = ApplicationRecord::updateOrCreate(
                ['reference' => $spec['reference']],
                [
                    'client_id' => $client->id,
                    'service_id' => $service->id,
                    'assigned_user_id' => $demoUsers['staff']->id,
                    'status' => $spec['status'],
                    'started_at' => now()->subDays(10)->toDateString(),
                    'completed_at' => $spec['status'] === 'completed' ? now()->subDays(2)->toDateString() : null,
                    'notes' => $spec['notes'],
                ],
            );

            FollowUp::updateOrCreate(
                ['client_id' => $client->id, 'application_id' => $application->id],
                [
                    'assigned_user_id' => $demoUsers['staff']->id,
                    'due_at' => now()->addDays(2),
                    'status' => 'pending',
                    'notes' => 'Demo follow-up: contact the client with a status update.',
                ],
            );
        }

        $visaApplication = ApplicationRecord::query()->where('reference', 'DEMO-VISA-001')->first();
        Payment::updateOrCreate(
            ['reference' => 'DEMO-PAY-001'],
            [
                'client_id' => $client->id,
                'application_id' => $visaApplication?->id,
                'amount' => 15000,
                'currency' => 'BDT',
                'status' => 'paid',
                'method' => 'bank_transfer',
                'paid_at' => now()->subDays(3)->toDateString(),
                'notes' => 'Synthetic demo payment; no real funds were collected.',
            ],
        );

        $roleSamples = [
            'super-admin' => ['visa-processing', 'in_progress'],
            'admin' => ['student-admissions', 'pending_documents'],
            'manager' => ['certificate-equivalency', 'active'],
            'staff' => ['document-attestation', 'completed'],
            'accountant' => ['air-ticket-booking', 'active'],
            'viewer' => ['hr-consultancy', 'pending'],
        ];

        foreach ($roleSamples as $slug => [$serviceSlug, $status]) {
            $service = $services->get($serviceSlug);
            if (! $service) {
                continue;
            }

            $sampleClient = Client::updateOrCreate(
                ['email' => 'demo.'.$slug.'.client@example.com'],
                [
                    'name' => 'Demo '.$roleNames[$slug].' Client',
                    'phone' => '+880 1700 000'.str_pad((string) (array_search($slug, array_keys($roleNames), true) + 1), 3, '0', STR_PAD_LEFT),
                    'company' => 'Demo Account',
                    'country' => 'Bangladesh',
                    'address' => 'Synthetic demo record',
                    'notes' => 'Fictional read-only dashboard sample.',
                    'status' => $slug === 'viewer' ? 'lead' : 'active',
                    'assigned_user_id' => $demoUsers[$slug]->id,
                ],
            );

            $application = ApplicationRecord::updateOrCreate(
                ['reference' => 'DEMO-ROLE-'.strtoupper($slug)],
                [
                    'client_id' => $sampleClient->id,
                    'service_id' => $service->id,
                    'assigned_user_id' => $demoUsers[$slug]->id,
                    'status' => $status,
                    'started_at' => now()->subDays(7)->toDateString(),
                    'completed_at' => $status === 'completed' ? now()->subDay()->toDateString() : null,
                    'notes' => 'Fictional sample for the read-only '.$roleNames[$slug].' demo dashboard.',
                ],
            );

            FollowUp::updateOrCreate(
                ['client_id' => $sampleClient->id, 'application_id' => $application->id],
                [
                    'assigned_user_id' => $demoUsers[$slug]->id,
                    'due_at' => now()->addDays(2),
                    'status' => 'pending',
                    'notes' => 'Demo reminder only.',
                ],
            );

            if ($demoUsers[$slug]->hasPermission('payments.view')) {
                Payment::updateOrCreate(
                    ['reference' => 'DEMO-ROLE-PAY-'.strtoupper($slug)],
                    [
                        'client_id' => $sampleClient->id,
                        'application_id' => $application->id,
                        'amount' => 2500,
                        'currency' => 'BDT',
                        'status' => 'pending',
                        'method' => null,
                        'paid_at' => null,
                        'notes' => 'Synthetic demo balance; no real funds are due.',
                    ],
                );
            }
        }

        Payment::updateOrCreate(
            ['reference' => 'DEMO-PAY-002'],
            [
                'client_id' => $client->id,
                'application_id' => $visaApplication?->id,
                'amount' => 5000,
                'currency' => 'BDT',
                'status' => 'pending',
                'method' => null,
                'paid_at' => null,
                'notes' => 'Synthetic demo outstanding balance.',
            ],
        );
    }
}
