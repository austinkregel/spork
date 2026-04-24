<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('events') || Schema::hasColumn('events', 'identifiers')) {
            return;
        }

        Schema::table('events', function (Blueprint $table): void {
            $table->json('identifiers')->nullable()->after('color');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('events') || ! Schema::hasColumn('events', 'identifiers')) {
            return;
        }

        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn('identifiers');
        });
    }
};
