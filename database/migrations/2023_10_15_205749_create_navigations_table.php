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
        Schema::create('navigations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Domain::class)->nullable();
            $table->foreignId('parent_id')->nullable();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('href', 2048);
            $table->integer('order')->default(0);
            $table->boolean('authentication_required');
            $table->json('settings')->nullable();
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->string('pretty_url', 2048)->nullable();
            $table->string('ugly_url', 2048)->nullable();
            $table->timestamps();
        });

        \App\Models\Navigation::create([
            'name' => 'Dashboard',
            'icon' => 'HomeIcon',
            'href' => '/-',
            'order' => 0,
            'authentication_required' => true,
        ]);
        \App\Models\Navigation::create([
            'name' => 'Projects',
            'icon' => 'ClipboardIcon',
            'href' => '/-/projects',
            'order' => 5,
            'authentication_required' => true,
        ]);

        \App\Models\Navigation::create([
            'name' => 'Banking',
            'icon' => 'WalletIcon',
            'href' => '/-/',
            'order' => 6,
            'authentication_required' => true,
        ]);
        \App\Models\Navigation::create([
            'name' => 'Tags',
            'icon' => 'TagIcon',
            'href' => '/-/',
            'order' => 8,
            'authentication_required' => true,
        ]);
        /** @var \App\Models\Navigation $crudNav */
        $crudNav = \App\Models\Navigation::create([
            'name' => 'CRUD',
            'icon' => 'CircleStackIcon',
            'href' => '/-/manage',
            'order' => 6,
            'authentication_required' => true,
        ]);

        $models = \App\Services\Code::instancesOf(\App\Models\Crud::class);

        $crudClasses = array_values(array_filter(
            $models->getClasses(),
            fn ($class) => in_array(\App\Models\Crud::class, class_implements($class))
        ));

        foreach ($crudClasses as $index => $class) {
            \App\Models\Navigation::create([
                'name' => \Illuminate\Support\Str::headline((new $class)->getTable()),
                'href' => '/-/manage/'.\Illuminate\Support\Str::slug(
                    \Illuminate\Support\Str::headline((new $class)->getTable())
                ),
                'icon' => \Illuminate\Support\Str::studly(\Illuminate\Support\Str::headline(class_basename($class))).'Icon',
                'order' => $index,
                'authentication_required' => true,
                'parent_id' => $crudNav->id,
                'settings' => [
                    'title' => \Illuminate\Support\Str::headline(class_basename($class)),
                    'singular' => \Illuminate\Support\Str::studly(class_basename($class)),
                    'api_url' => route(((new $class)->getTable()).'.store'),
                ],
            ]);
        }

        \App\Models\Navigation::create([
            'name' => 'Email',
            'icon' => 'EnvelopeOpenIcon',
            'href' => '/-/inbox',
            'order' => 9,
            'authentication_required' => true,
        ]);
        \App\Models\Navigation::create([
            'name' => 'Settings',
            'icon' => 'Cog6ToothIcon',
            'href' => '/-/settings',
            'order' => 10,
            'authentication_required' => true,
        ]);

        $logic = \App\Models\Navigation::create([
            'name' => 'Logic',
            'icon' => 'VariableIcon',
            'href' => '/-/logic',
            'order' => 7,
            'authentication_required' => true,
        ]);

        $logic->conditions()->create([
            'parameter' => 'config:app.env',
            'comparator' => 'IN',
            'value' => 'dev,local',
        ]);

        \App\Models\Navigation::create([
            'name' => 'Banking',
            'icon' => 'WalletIcon',
            'href' => '/-/banking',
            'order' => 6,
            'authentication_required' => true,
        ]);

        \App\Models\Navigation::create([
            'name' => 'Login',
            'href' => '/login',
            'order' => 0,
            'authentication_required' => false,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('navigations');
    }
};
