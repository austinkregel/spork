<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table): void {
            $table->string('machine_id')->nullable()->after('server_id');
            $table->string('connection_type')->nullable()->after('machine_id');

            $table->foreignId('provider_credential_id')
                ->nullable()
                ->after('credential_id')
                ->constrained('credentials')
                ->nullOnDelete();

            $table->string('provider_server_id')->nullable()->after('provider_credential_id');

            $table->unique('machine_id');
            $table->index('provider_server_id');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table): void {
            $table->dropUnique(['machine_id']);
            $table->dropIndex(['provider_server_id']);

            $table->dropConstrainedForeignId('provider_credential_id');
            $table->dropColumn(['machine_id', 'connection_type', 'provider_server_id']);
        });
    }
};


