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
        Schema::table('messages', function (Blueprint $table) {
            $table->string('subject', 255)->nullable();
            $table->boolean('seen')->default(false);
            $table->boolean('spam')->default(false);
            $table->boolean('answered')->default(false);
            $table->longText('message')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \App\Models\Message::query()->truncate();
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('subject');
            $table->dropColumn('seen');
            $table->dropColumn('spam');
            $table->dropColumn('answered');
            $table->text('message')->change();
        });
    }
};
