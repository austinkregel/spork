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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            // What are projects?
            $table->string('name');
            $table->json('settings')->nullable();
            $table->foreignIdFor(App\Models\Team::class);
            $table->timestamps();
        });

        Schema::create('project_resources', function (Blueprint $table) {
            $table->id();
            // person, user
            $table->morphs('resource');
            $table->foreignIdFor(App\Models\Project::class);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
        Schema::dropIfExists('project_resources');
    }
};
