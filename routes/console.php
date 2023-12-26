<?php

declare(strict_types=1);

use App\Services\Programming\LaravelProgrammingStyle;
use Nette\PhpGenerator\Literal;

Artisan::command('test', function () {
    // Goals?
    //  - Generate created, deleted, updated, restored, etc, events for all models.
    //  - Add the above events to the $events property on the model

    $allModels = LaravelProgrammingStyle::instancesOf(\Illuminate\Database\Eloquent\Model::class)
        ->getClasses();

    collect($allModels)
        ->filter(fn ($model) => str_starts_with($model, 'App'))
        ->filter(fn ($model) => ! str_contains($model, 'Team'))
        ->map(function ($model) {
            $laravelModelEvents = [
                'created',
                'creating',
                'deleting',
                'deleted',
                'updating',
                'updated',
            ];

            $modelCodeInstance = LaravelProgrammingStyle::for($model);
            try {
                $existingPropertyValue = $modelCodeInstance->getProperty('dispatchesEvents');

                $allEvents = (new ReflectionClass($model))->getProperty('dispatchesEvents')->getValue(new $model);

                $eventServiceProvider = LaravelProgrammingStyle::for(\App\Providers\EventServiceProvider::class);
                foreach ($allEvents as $event) {
                    $eventServiceProvider->addListenerToEvent($event, \App\Listeners\DebugEventListener::class);
                    $eventServiceProvider->removeListenerFromEvent($event, \App\Listeners\DebugEventListener::class);
                }

                $eventServiceProvider->toFile(LaravelProgrammingStyle::REPLACE_FILE);
                $this->info('Nothing to create for '.$model);
            } catch (\Nette\InvalidArgumentException $e) {
                $className = class_basename($model);

                $eventNamespace = 'App\\Events\\Models\\'.ucfirst($className);
                $values = collect(array_combine($laravelModelEvents, $laravelModelEvents))
                    ->map(function ($eventName) use ($model, $eventNamespace, $className) {
                        $eventClassName = $className.ucfirst($eventName);

                        $newFile = lcfirst(str_replace('\\', '/', $eventNamespace.'/'.$eventClassName.'.php'));

                        if (! file_exists($newFile)) {
                            LaravelProgrammingStyle::for(\App\Events\ExampleEvent::class)
                                ->renameClass($eventClassName, $eventNamespace)
                                ->import([\App\Events\AbstractLogicalEvent::class, $model])
                                ->modifyMethod('__construct', '', parameters: [
                                    (new \Nette\PhpGenerator\PromotedParameter('model'))->setType($model),
                                ])
                                ->saveAs($newFile);
                        }

                        return new Literal($eventClassName.'::class');
                    });
                foreach ($values as $valueToImport) {
                    $modelCodeInstance->import($eventNamespace.'\\'.str_replace('::class', '', $valueToImport));
                }

                $modelCodeInstance->addProperty('dispatchesEvents', $values->toArray());
                $modelCodeInstance->toFile(LaravelProgrammingStyle::REPLACE_FILE);
                $this->info($model.' '.$e->getMessage()."\n");
            } catch (\Throwable $e) {
                dd($e);
            }
        });
});
