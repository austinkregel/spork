<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table): void {
            $table->foreignId('server_id')->nullable()->after('credential_id')->constrained()->nullOnDelete();
            $table->foreignId('dns_zone_id')->nullable()->after('server_id')->constrained()->nullOnDelete();
        });

        Schema::table('domain_records', function (Blueprint $table): void {
            $table->foreignId('dns_zone_id')->nullable()->after('domain_id')->constrained('dns_zones')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('domain_records', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('dns_zone_id');
        });

        Schema::table('domains', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('dns_zone_id');
            $table->dropConstrainedForeignId('server_id');
        });
    }
};









