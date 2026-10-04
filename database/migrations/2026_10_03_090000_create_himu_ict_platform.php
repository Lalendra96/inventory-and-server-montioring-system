<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 80)->nullable()->unique()->after('id');
            $table->string('staff_no', 80)->nullable()->unique()->after('username');
            $table->string('phone', 30)->nullable();
            $table->string('unit_code', 80)->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('must_change_password')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->foreignId('disabled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('disable_reason')->nullable();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->string('label', 120);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnUpdate();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate();
            $table->primary(['role_id', 'user_id']);
        });

        Schema::create('feature_options', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();
            $table->string('label', 160);
            $table->text('description')->nullable();
            $table->boolean('enabled')->default(true)->index();
            $table->json('settings')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name', 160);
            $table->string('type', 80)->default('unit');
            $table->string('parent_code', 60)->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('disabled_at')->nullable();
            $table->foreignId('disabled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('disable_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('ticket_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('color', 20)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('sla_policies', function (Blueprint $table) {
            $table->id();
            $table->string('priority', 30)->unique();
            $table->unsignedInteger('response_minutes');
            $table->unsignedInteger('resolution_minutes');
            $table->unsignedInteger('escalation_minutes');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_no', 40)->unique();
            $table->foreignId('category_id')->nullable()->constrained('ticket_categories')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject', 180);
            $table->text('description');
            $table->string('priority', 30)->default('normal')->index();
            $table->string('status', 40)->default('open')->index();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('response_due_at')->nullable()->index();
            $table->timestamp('resolution_due_at')->nullable()->index();
            $table->timestamp('escalated_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('disabled_at')->nullable();
            $table->foreignId('disabled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('disable_reason')->nullable();
            $table->timestamps();
            $table->index(['status', 'priority']);
        });

        Schema::create('ticket_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnUpdate();
            $table->foreignId('user_id')->constrained('users');
            $table->text('comment');
            $table->boolean('internal_only')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamp('disabled_at')->nullable();
            $table->foreignId('disabled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('disable_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnUpdate();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->string('original_name', 255);
            $table->string('stored_path', 500);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('sha256', 64);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ticket_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnUpdate();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 80);
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->index(['ticket_id', 'occurred_at']);
        });

        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_tag', 80)->unique();
            $table->foreignId('category_id')->nullable()->constrained('asset_categories')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('name', 180);
            $table->string('manufacturer', 120)->nullable();
            $table->string('model', 120)->nullable();
            $table->string('serial_number', 160)->nullable()->index();
            $table->string('status', 40)->default('active')->index();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_until')->nullable();
            $table->string('supplier', 180)->nullable();
            $table->decimal('purchase_cost', 14, 2)->nullable();
            $table->string('ip_address', 80)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('disabled_at')->nullable();
            $table->foreignId('disabled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('disable_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnUpdate();
            $table->foreignId('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('moved_by')->constrained('users');
            $table->string('reason', 220)->nullable();
            $table->timestamp('moved_at')->useCurrent();
        });

        Schema::create('asset_faults', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnUpdate();
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->string('fault_type', 120)->nullable();
            $table->text('description');
            $table->timestamp('reported_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('inspection_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 180);
            $table->foreignId('asset_category_id')->nullable()->constrained('asset_categories')->nullOnDelete();
            $table->json('checklist');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('inspection_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('inspection_templates');
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('frequency', 40)->default('monthly');
            $table->unsignedInteger('interval_days')->nullable();
            $table->date('next_due_on')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->nullable()->constrained('inspection_schedules')->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_on')->index();
            $table->timestamp('performed_at')->nullable();
            $table->string('status', 40)->default('scheduled')->index();
            $table->json('results')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('monitored_systems', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name', 160);
            $table->string('system_type', 80)->default('application');
            $table->string('environment', 40)->default('production');
            $table->string('health_url', 500)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('system_downtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_id')->nullable()->constrained('monitored_systems')->nullOnDelete();
            $table->foreignId('reported_by')->constrained('users');
            $table->string('classification', 100);
            $table->timestamp('started_at')->index();
            $table->timestamp('ended_at')->nullable();
            $table->text('impact')->nullable();
            $table->text('root_cause')->nullable();
            $table->text('resolution')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('software_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no', 40)->unique();
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('title', 180);
            $table->text('description');
            $table->string('priority', 30)->default('normal');
            $table->string('status', 50)->default('requested')->index();
            $table->text('uat_notes')->nullable();
            $table->timestamp('deployed_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('procurement_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no', 40)->unique();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users');
            $table->string('request_type', 50)->default('replacement');
            $table->string('status', 50)->default('draft')->index();
            $table->string('priority', 30)->default('normal');
            $table->text('justification');
            $table->decimal('estimated_cost', 14, 2)->nullable();
            $table->string('committee_reference', 120)->nullable();
            $table->text('decision_notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('night_shift_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('incident_no', 40)->unique();
            $table->foreignId('reported_by')->constrained('users');
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->string('severity', 30)->default('normal')->index();
            $table->string('title', 180);
            $table->text('details');
            $table->timestamp('occurred_at')->index();
            $table->string('status', 40)->default('open')->index();
            $table->text('handover_notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('event_uuid')->unique();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 120)->index();
            $table->string('auditable_type', 180)->nullable()->index();
            $table->string('auditable_id', 120)->nullable()->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->uuid('request_id')->nullable()->index();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->string('previous_hash', 64)->nullable();
            $table->string('integrity_hash', 64);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        foreach ([
            'audit_logs', 'notifications', 'night_shift_incidents', 'procurement_requests',
            'software_requests', 'system_downtimes', 'monitored_systems', 'inspections',
            'inspection_schedules', 'inspection_templates', 'asset_faults', 'asset_movements',
            'assets', 'asset_categories', 'ticket_events', 'ticket_attachments', 'ticket_comments',
            'tickets', 'sla_policies', 'ticket_categories', 'locations', 'feature_options',
            'role_user', 'roles',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('disabled_by');
            $table->dropColumn([
                'username', 'staff_no', 'phone', 'unit_code', 'is_active', 'must_change_password',
                'last_login_at', 'disabled_at', 'disable_reason',
            ]);
        });
    }
};
