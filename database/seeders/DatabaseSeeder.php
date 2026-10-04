<?php

namespace Database\Seeders;

use App\Models\AssetCategory;
use App\Models\FeatureOption;
use App\Models\Location;
use App\Models\MonitoredSystem;
use App\Models\Role;
use App\Models\SlaPolicy;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'system_admin',
                'System Administrator',
                'Full technical administration and feature configuration.',
            ],
            [
                'himu_admin',
                'HIMU Administrator',
                'HIMU operational administration.',
            ],
            [
                'ict_manager',
                'ICT Manager / Consultant',
                'Operational oversight, approvals and analytics.',
            ],
            [
                'senior_ict_officer',
                'Senior ICT Officer',
                'Ticket assignment and escalation.',
            ],
            [
                'ict_officer',
                'ICT Officer',
                'Support tickets, assets and maintenance.',
            ],
            [
                'technician',
                'Technician',
                'Hardware and field support.',
            ],
            [
                'software_developer',
                'Software Developer',
                'Software request development and deployment workflow.',
            ],
            [
                'system_db_officer',
                'System / Database Officer',
                'System and database monitoring.',
            ],
            [
                'night_shift_officer',
                'Night Shift Officer',
                'Night shift incident capture and handover.',
            ],
            [
                'auditor',
                'Auditor / Verifier',
                'Read-only governance audit and verification.',
            ],
            [
                'department_user',
                'Department / Ward User',
                'Raise and follow support requests.',
            ],
            [
                'reporting_user',
                'Reporting User',
                'Operational reports.',
            ],
        ];

        foreach ($roles as [$name, $label, $description]) {
            Role::updateOrCreate(
                ['name' => $name],
                [
                    'label' => $label,
                    'description' => $description,
                    'is_active' => true,
                ]
            );
        }

        $features = [
            [
                'tickets',
                'Support Requests',
                'Ticket assignment, comments, attachments and SLA tracking.',
            ],
            [
                'assets',
                'Asset / Inventory Management',
                'Hardware inventory, lifecycle and replacement context.',
            ],
            [
                'inspections',
                'Preventive Inspections',
                'Recurring preventive inspection schedules and worklists.',
            ],
            [
                'downtime',
                'System Downtime',
                'System outage, impact, root cause and verification tracking.',
            ],
            [
                'server_monitoring',
                'Server Monitoring',
                'Live Ubuntu server metrics, processes and governed service/process control.',
            ],
            [
                'software_requests',
                'Software Requests',
                'Request, review, development, UAT and deployment workflow.',
            ],
            [
                'procurement',
                'Procurement / Replacement',
                'Governed replacement and procurement workflow.',
            ],
            [
                'night_shift',
                'Night Shift Incidents',
                'Night shift incidents and handover.',
            ],
            [
                'reports',
                'Reports',
                'Operational and governance reporting.',
            ],
            [
                'audit',
                'Audit Viewer',
                'HMAC protected audit viewing and verification.',
            ],
        ];

        foreach ($features as [$key, $label, $description]) {
            FeatureOption::updateOrCreate(
                ['key' => $key],
                [
                    'label' => $label,
                    'description' => $description,
                    'enabled' => true,
                ]
            );
        }

        $slaPolicies = [
            ['critical', 10, 60, 15],
            ['high', 30, 240, 60],
            ['normal', 120, 1440, 240],
            ['low', 240, 4320, 1440],
        ];

        foreach ($slaPolicies as [$priority, $response, $resolution, $escalation]) {
            SlaPolicy::updateOrCreate(
                ['priority' => $priority],
                [
                    'response_minutes' => $response,
                    'resolution_minutes' => $resolution,
                    'escalation_minutes' => $escalation,
                    'is_active' => true,
                ]
            );
        }

        foreach ([
            'Computer / Laptop',
            'Printer',
            'Network / Connectivity',
            'Barcode Device',
            'HIMS / LIMS / RIS-PACS',
            'Other',
        ] as $name) {
            TicketCategory::updateOrCreate(
                ['name' => $name],
                ['is_active' => true]
            );
        }

        foreach ([
            'Computer / Laptop',
            'Printer',
            'Barcode Printer',
            'Network Switch',
            'UPS',
            'Access Point',
            'Server',
            'Other',
        ] as $name) {
            AssetCategory::updateOrCreate(
                ['name' => $name],
                ['is_active' => true]
            );
        }

        $locations = [
            ['CPD', 'CPD'],
            ['WARD23', 'Ward 23'],
            ['RENAL', 'Renal Unit'],
            ['LAB', 'Laboratory'],
            ['ICU', 'ICU'],
            ['A&E', 'A&E'],
            ['MED1', 'Medical Ward 1'],
            ['SURG1', 'Surgical Ward 1'],
            ['ENDO', 'Endocrine Clinic'],
            ['PHARM', 'Pharmacy'],
        ];

        foreach ($locations as [$code, $name]) {
            Location::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => 'unit',
                    'is_active' => true,
                ]
            );
        }

        $systems = [
            ['HIMS', 'HIMS'],
            ['LIMS', 'LIMS'],
            ['RIS-PACS', 'RIS / PACS'],
            ['OTHER', 'Other Modules'],
        ];

        foreach ($systems as [$code, $name]) {
            MonitoredSystem::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'system_type' => 'application',
                    'environment' => 'production',
                    'is_active' => true,
                ]
            );
        }

        $admin = User::updateOrCreate(
            ['username' => env('SEED_ADMIN_USERNAME', 'admin')],
            [
                'name' => env('SEED_ADMIN_NAME', 'System Administrator'),
                'email' => env('SEED_ADMIN_EMAIL', 'admin@thp.local'),
                'password' => Hash::make(
                    env('SEED_ADMIN_PASSWORD', 'CHANGE_THIS_BEFORE_SEEDING')
                ),
                'unit_code' => 'HIMU',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        $systemAdminRoleId = Role::query()
            ->where('name', 'system_admin')
            ->value('id');

        if ($systemAdminRoleId) {
            $admin->roles()->syncWithoutDetaching([$systemAdminRoleId]);
        }
    }
}
