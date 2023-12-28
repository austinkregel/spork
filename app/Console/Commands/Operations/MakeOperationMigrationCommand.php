<?php

declare(strict_types=1);

namespace App\Console\Commands\Operations;

use Illuminate\Database\Console\Migrations\BaseCommand;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;

final class MakeOperationMigrationCommand extends BaseCommand
{
    protected $signature = 'make:operation-migration {name : The name of the operation}';

    protected $description = 'Create a new operation migration';

    protected $creator;

    protected $composer;

    public function __construct(MigrationCreator $creator, Composer $composer)
    {
        parent::__construct();

        $this->creator = $creator;
        $this->composer = $composer;
    }

    public function handle()
    {
        $name = Str::snake(Str::plural($this->input->getArgument('name')));
        $table = Str::snake(Str::pluralStudly(class_basename($this->input->getArgument('name'))));

        $file = $this->creator->create('create_'.$name.'_table', $this->getMigrationPath(), $table, true);

        $this->line("<info>Created Migration:</info> {$file}");
    }
}
