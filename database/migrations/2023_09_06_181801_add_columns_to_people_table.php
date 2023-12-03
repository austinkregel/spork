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
        Schema::table('people', function (Blueprint $table) {
            $table->json('names')->nullable()->after('emails');
            // ssn, sos id, ein, usernames, etc
            $table->json('identifiers')->nullable()->after('emails');
            // precinct_name, BOE, senate districts, rep districts, ward, etc
            $table->json('locality')->nullable()->after('emails');

            $table->json('jobs')->nullable()->after('emails');
            $table->json('education')->nullable()->after('emails');
            $table->string('estimated_income')->nullable()->after('emails');
            $table->string('estimated_home_value')->nullable()->after('emails');
            $table->string('photo_url', 2048)->nullable()->after('estimated_home_value');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            //
        });
    }
};
