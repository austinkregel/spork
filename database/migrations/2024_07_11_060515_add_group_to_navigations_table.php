<?php

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
        Schema::table('navigations', function (Blueprint $table) {
            $table->string('group')->nullable()->index()->after('ugly_url');
        });

        \App\Models\Navigation::create([
            'name' => 'Dashboard',
            'icon' => 'HomeIcon',
            'href' => '/-/dashboard',
            'order' => 0,
            'authentication_required' => true,
        ]);
        \App\Models\Navigation::create([
            'name' => 'Projects',
            'icon' => 'ClipboardIcon',
            'href' => '/-/projects',
            'order' => 1,
            'authentication_required' => true,
        ]);

        \App\Models\Navigation::create([
            'name' => 'Email',
            'icon' => 'EnvelopeOpenIcon',
            'href' => '/-/inbox',
            'order' => 9,
            'authentication_required' => true,
            'group' => 'communication',
        ]);

        \App\Models\Navigation::create([
            'name' => 'Messaging',
            'icon' => 'ChatBubbleLeftRightIcon',
            'href' => '/-/postal',
            'order' => 10,
            'authentication_required' => true,
            'group' => 'communication',
        ]);


        /** @var \App\Models\Navigation $crudNav */
        $crudNav = \App\Models\Navigation::create([
            'name' => 'CRUD',
            'icon' => 'CircleStackIcon',
            'href' => '/-/manage',
            'order' => 6,
            'group' => 'core',
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
            'name' => 'Tags',
            'icon' => 'TagIcon',
            'href' => '/-/tag-manager',
            'order' => 8,
            'group' => 'core',
            'authentication_required' => true,
        ]);
        \App\Models\Navigation::create([
            'name' => 'Settings',
            'icon' => 'Cog6ToothIcon',
            'href' => '/-/settings',
            'order' => 10,
            'authentication_required' => true,
            'group' => 'core',
        ]);

        $logic = \App\Models\Navigation::create([
            'name' => 'Logic',
            'icon' => 'VariableIcon',
            'href' => '/-/logic',
            'order' => 7,
            'authentication_required' => true,
            'group' => 'development',
        ]);

        $logic->conditions()->create([
            'parameter' => 'config:app.env',
            'comparator' => 'IN',
            'value' => 'dev,local',
        ]);

        \App\Models\Navigation::create([
            'name' => 'Login',
            'href' => '/login',
            'order' => 0,
            'authentication_required' => false,
        ]);

        \App\Models\Navigation::create([
            'name' => 'Banking',
            'icon' => 'WalletIcon',
            'href' => '/-/banking',
            'order' => 6,
            'authentication_required' => true,
            'group' => 'tools',
        ]);
        \App\Models\Navigation::create([
            'name' => 'Rss Feeds',
            'icon' => 'RssIcon',
            'href' => '/-/',
            'order' => 6,
            'authentication_required' => true,
            'group' => 'tools',
        ]);
        \App\Models\Navigation::create([
            'name' => 'Servers',
            'icon' => 'ServerIcon',
            'href' => '/-/',
            'order' => 6,
            'authentication_required' => true,
            'group' => 'tools',
        ]);
        \App\Models\Navigation::create([
            'name' => 'File Manager',
            'icon' => 'ServerIcon',
            'href' => '/-/',
            'order' => 6,
            'authentication_required' => true,
            'group' => 'tools',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('navigations', function (Blueprint $table) {
            $table->dropColumn('group');
        });
    }
};
