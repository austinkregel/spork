<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_rss_feeds', function (Blueprint $table) {
            $table->morphs('owner');
            $table->dateTime('last_modified')->nullable();
            $table->string('etag')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('external_rss_feeds', function (Blueprint $table) {
            $table->dropMorphs('owner');
            $table->dropColumn('last_modified');
            $table->dropColumn('etag');
        });
    }
};
