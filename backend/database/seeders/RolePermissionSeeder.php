<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Service;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'dashboard.view' => 'View dashboard', 'clients.view' => 'View clients', 'clients.manage' => 'Manage clients',
            'reports.view' => 'View business reports',
            'applications.view' => 'View applications', 'applications.manage' => 'Manage applications',
            'documents.view' => 'View documents', 'documents.manage' => 'Manage documents', 'followups.view' => 'View follow-ups', 'followups.manage' => 'Manage follow-ups',
            'payments.view' => 'View payments', 'payments.manage' => 'Manage payments',
            'services.view' => 'View services', 'services.manage' => 'Manage services', 'users.manage' => 'Manage users', 'roles.manage' => 'Manage roles',
        ];
        foreach ($permissions as $slug => $name) {
            Permission::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }
        $map = [
            'super-admin' => array_keys($permissions),
            'admin' => array_values(array_diff(array_keys($permissions), ['roles.manage'])),
            'manager' => ['dashboard.view', 'reports.view', 'clients.view', 'clients.manage', 'applications.view', 'applications.manage', 'documents.view', 'documents.manage', 'followups.view', 'followups.manage', 'payments.view', 'services.view'],
            'staff' => ['dashboard.view', 'reports.view', 'clients.view', 'clients.manage', 'applications.view', 'applications.manage', 'documents.view', 'documents.manage', 'followups.view', 'followups.manage', 'services.view'],
            'accountant' => ['dashboard.view', 'reports.view', 'clients.view', 'applications.view', 'payments.view', 'payments.manage', 'services.view'],
            'viewer' => ['dashboard.view', 'reports.view', 'clients.view', 'applications.view', 'documents.view', 'followups.view', 'payments.view', 'services.view'],
        ];
        $labels = ['super-admin' => 'Super Admin', 'admin' => 'Admin', 'manager' => 'Manager', 'staff' => 'Staff', 'accountant' => 'Accountant', 'viewer' => 'Viewer'];
        foreach ($map as $slug => $slugs) {
            $role = Role::firstOrCreate(['slug' => $slug], ['name' => $labels[$slug]]);
            $role->permissions()->sync(Permission::whereIn('slug', $slugs)->pluck('id'));
        }
        foreach ([
            ['name' => 'Visa Processing', 'slug' => 'visa-processing'],
            ['name' => 'Student Admissions', 'slug' => 'student-admissions'],
            ['name' => 'Certificate Equivalency', 'slug' => 'certificate-equivalency'],
            ['name' => 'Document Attestation', 'slug' => 'document-attestation'],
            ['name' => 'Air Ticket Booking', 'slug' => 'air-ticket-booking'],
            ['name' => 'HR Consultancy', 'slug' => 'hr-consultancy'],
        ] as $service) {
            Service::firstOrCreate(['slug' => $service['slug']], $service);
        }
    }
}
