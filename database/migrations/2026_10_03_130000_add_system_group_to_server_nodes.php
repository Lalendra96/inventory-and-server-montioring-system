<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('server_nodes', function (Blueprint $table): void {
            $table->string('system_group', 40)
                ->default('other')
                ->after('environment')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('server_nodes', function (Blueprint $table): void {
            $table->dropIndex(['system_group']);
            $table->dropColumn('system_group');
        });
    }
};
