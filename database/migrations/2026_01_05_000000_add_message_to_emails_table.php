<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emails', function (Blueprint $table): void {
            if (! Schema::hasColumn('emails', 'message_text')) {
                // Only store a sanitized, plain-text version of the email body.
                // Use TEXT (not LONGTEXT) to keep a hard upper bound (~64KB) per row.
                $table->text('message_text')->nullable()->after('subject');
            }
        });
    }

    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table): void {
            if (Schema::hasColumn('emails', 'message_text')) {
                $table->dropColumn('message_text');
            }
        });
    }
};


