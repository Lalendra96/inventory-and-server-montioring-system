<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_nodes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name', 160);
            $table->string('host', 255);
            $table->unsignedSmallInteger('ssh_port')->default(22);
            $table->string('ssh_user', 80);
            $table->string('ssh_key_path', 500);
            $table->string('environment', 40)->default('production')->index();
            $table->string('os_hint', 120)->nullable();
            $table->json('allowed_services')->nullable();
            $table->json('protected_process_patterns')->nullable();
            $table->boolean('allow_process_control')->default(false);
            $table->boolean('allow_service_control')->default(false);
            $table->unsignedSmallInteger('poll_interval_seconds')->default(10);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('disabled_at')->nullable();
            $table->foreignId('disabled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('disable_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('server_metric_snapshots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('server_node_id')->constrained('server_nodes')->cascadeOnUpdate();
            $table->timestamp('captured_at')->index();
            $table->decimal('cpu_percent', 6, 2)->nullable();
            $table->unsignedBigInteger('memory_total_bytes')->nullable();
            $table->unsignedBigInteger('memory_used_bytes')->nullable();
            $table->decimal('memory_percent', 6, 2)->nullable();
            $table->unsignedBigInteger('disk_total_bytes')->nullable();
            $table->unsignedBigInteger('disk_used_bytes')->nullable();
            $table->decimal('disk_percent', 6, 2)->nullable();
            $table->decimal('load_1', 8, 2)->nullable();
            $table->decimal('load_5', 8, 2)->nullable();
            $table->decimal('load_15', 8, 2)->nullable();
            $table->unsignedInteger('process_count')->nullable();
            $table->unsignedBigInteger('uptime_seconds')->nullable();
            $table->boolean('reachable')->default(true)->index();
            $table->string('error_code', 80)->nullable();
            $table->text('error_message')->nullable();
            $table->index(['server_node_id', 'captured_at']);
        });

        Schema::create('server_action_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('server_node_id')->constrained('server_nodes')->cascadeOnUpdate();
            $table->foreignId('actor_id')->constrained('users');
            $table->string('action', 80)->index();
            $table->string('target_type', 40);
            $table->string('target', 180);
            $table->string('signal_or_operation', 40)->nullable();
            $table->text('reason');
            $table->boolean('successful')->default(false)->index();
            $table->integer('exit_code')->nullable();
            $table->text('result_summary')->nullable();
            $table->timestamp('executed_at')->useCurrent()->index();
        });

        DB::table('feature_options')->updateOrInsert(
            ['key' => 'server_monitoring'],
            [
                'label' => 'Server Monitoring',
                'description' => 'Live Ubuntu server metrics, processes and governed service/process control.',
                'enabled' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('server_action_logs');
        Schema::dropIfExists('server_metric_snapshots');
        Schema::dropIfExists('server_nodes');
    }
};
