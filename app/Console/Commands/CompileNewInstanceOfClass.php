<?php

namespace App\Console\Commands;

use App\Events\AbstractLogicalEvent;
use App\Services\Code;
use App\Services\Programming\LaravelProgrammingStyle;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\Type;

class CompileNewInstanceOfClass extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'compile:from {sourceClass} {destination} {--blank-methods=true}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Have Code rename a class from one example to an instance.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sourceClass = $this->argument('sourceClass');
        $destinationClass = $this->argument('destination');

        if (!str_contains($destinationClass, '\\')) {
            $namespace = str_replace(class_basename($sourceClass), '', $sourceClass);

            $destinationClass = $namespace.$destinationClass;
        }

        if (!str_starts_with($sourceClass, 'App\\')) {
            throw new \Exception('Not setup for '. $sourceClass);
        }

        $newEvent = LaravelProgrammingStyle::for($sourceClass)
            ->renameClass($filename = class_basename($destinationClass),  trim(str_replace(class_basename($destinationClass), '', $destinationClass), '\\'))
            ->modifyMethod('__construct', '//', parameters: [
                (new Parameter('model'))
                ->setType(Model::class)
            ])
            ->import(AbstractLogicalEvent::class)
            ->import(Model::class)
            ->toFile(Code::RETURN_CONTENTS);

        $newFileName =  str_replace('\\', '/', lcfirst($destinationClass)).'.php';

        file_put_contents(base_path($newFileName), $newEvent);
    }
}
