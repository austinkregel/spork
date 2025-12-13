<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('reply_to_message_id')->nullable()->after('event_id');
            $table->string('reply_to_event_id')->nullable()->after('reply_to_message_id');

            $table->foreign('reply_to_message_id')->references('id')->on('messages')->nullOnDelete();
            $table->index('reply_to_event_id');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['reply_to_message_id']);
            $table->dropColumn(['reply_to_message_id', 'reply_to_event_id']);
        });
    }
};







