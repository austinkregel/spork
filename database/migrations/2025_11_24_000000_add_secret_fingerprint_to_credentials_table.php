<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('credentials', 'secret_fingerprint')) {
            return;
        }

        Schema::table('credentials', function (Blueprint $table): void {
            $table->string('secret_fingerprint', 255)
                ->nullable()
                ->after('settings')
                ->index('credentials_secret_fingerprint_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('credentials', 'secret_fingerprint')) {
            return;
        }

        Schema::table('credentials', function (Blueprint $table): void {
            $table->dropIndex('credentials_secret_fingerprint_index');
            $table->dropColumn('secret_fingerprint');
        });
    }
};
