<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\Services\MustacheTemplateService;
use App\Models\Credential;
use App\Models\Pipe;
use App\Models\Request;
use App\Models\Sequence;
use App\Models\Source;
use App\Models\Step;
use App\Models\Synchronization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Spatie\Tags\HasTags;

class ExecuteSequence
{
    public MustacheTemplateService $mustache;

    public function __construct(
        public Sequence $sequence
    ) {

    }

    public function handle(MustacheTemplateService $mustache)
    {
        $this->mustache = $mustache;
        $stepClosures = $this->sequence->steps()
            ->orderBy('order', 'asc')
            ->get()
            ->map(function (Step $step) {
                match ($step->actionable_type) {
                    Request::class => $this->handleRequestStep($step),
                    Pipe::class => $this->handleActionStep($step),
                    Synchronization::class => $this->handleSynchronizationStep($step),
                    default => dd('Unhandled match case', $step),
                };
            });

        return (new Pipeline(app()))
            ->send($this->sequence)
            ->through($stepClosures->toArray())
            ->then(function ($val) {
                info('Done executing.', compact('val'));
            });
    }

    protected function handleRequestStep(Step $step)
    {
        return function (mixed $data, \Closure $closure) use ($step) {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $this->buildRequest($step->actionable, $step->credential, [
                'step' => $step,
                'request' => $data,
            ]);

            if ($step->actionable?->headers['debug'] ?? false) {
                dd([
                    'headers' => $response->headers(),
                    'body' => $response->body(),
                    'status' => $response->status(),
                ]);
            }

            return $closure($response->body());
        };
    }

    protected function handleActionStep(Step $step)
    {
        return function (mixed $data, \Closure $next) use ($step) {
            /** @var \App\Models\Pipe $action */
            $action = $step->actionable;

            return $next($this->buildPipeFunction($action, $data));
        };
    }

    protected function buildPipeFunction(Pipe $pipe, $data)
    {
        $buildPhpClassFromPipe = function (Pipe $pipe): string {
            $namespace = new PhpNamespace('App\\Models\\Pipes');

            $class = new ClassType(Str::studly($pipe->name));
            $handleMethod = $class->addMethod('handle');

            $handleMethod->addParameter('content');
            $handleMethod->addParameter('next', null);

            $handleMethod->addBody($pipe->script);
            $namespace->add($class);

            return '<?php'."\n".(string) $namespace;
        };
        try {
            if ($pipe->language !== 'php') {
                dd('Only php has support for now');
            }
            $hash = md5($pipe->script);
            $path = storage_path('app/action-'.$pipe->id.'-'.$hash);

            if (! file_exists($path)) {
                (new Filesystem)->makeDirectory($basePath = app_path('Models/Pipes'), 0755, true, true);
                file_put_contents($basePath.'/'.Str::studly($pipe->name).'.php', $content = $buildPhpClassFromPipe($pipe));
            }

            $script = 'App\\Models\\Pipes\\'.Str::studly($pipe->name);

            if (! empty($script) && class_exists($script)) {
                info('The script is not set for some reason.', [
                    'script' => $script,
                    'pipe' => $pipe,
                ]);

                return call_user_func([app($script), 'handle'], $data);
            } elseif (empty($script)) {
                info('The script is not set for some reason.', [
                    'script' => $script,
                    'pipe' => $pipe,
                ]);
            }
            // Not sure what else the script could be at the moment
        } finally {
            //                isset($path) && unlink($path);
        }
    }

    protected function render(string|array|null $renderable, array $context): string|array
    {
        if (empty($renderable)) {
            return '';
        }

        if (is_string($renderable)) {
            return $this->mustache->render($renderable, $context);
        }

        foreach ($renderable as $key => $template) {
            $renderable[$key] = $this->mustache->render($template, $context);
        }

        return $renderable;
    }

    protected function handleSynchronizationStep(Step $step)
    {
        return function (mixed $data, \Closure $next) use ($step) {
            /** @var \App\Models\Synchronization $synchronization */
            $synchronization = $step->actionable;

            $synchronization->load('credential', 'request', 'formatter');

            $response = $this->buildRequest($synchronization->request, $synchronization->credential, [
                'step' => $step,
                'request' => $data,
            ]);

            // If all goes right, this should be an array of data dynamically assembled from a request.
            /** @var array $standardizedData */
            $standardizedData = $this->buildPipeFunction($formatterPipe = $synchronization->formatter, $response->json());

            // Yea so this current is going only support 1 primary key. Other primary
            // key types could be supported, but are out of scope for the moment.

            $primaryKeys = $standardizedData->mapToGroups(fn ($datum) => [
                $formatterPipe->context['primary_key'] => $datum[$formatterPipe->context['primary_key']],
            ]);

            $query = $this->buildQueryBuilderFromModel($synchronization);
            foreach ($primaryKeys as $key => $ids) {
                $query->whereIn($key, $ids);
            }
            $rebuildQuery = $query->clone();
            $localModels = $query->get();
            $localModelIds = $localModels->pluck($formatterPipe->context['primary_key']);

            $standardizedDataGroupedByPK = $standardizedData->groupBy($formatterPipe->context['primary_key']);
            $localModelsGroupedByPK = $localModels->groupBy($formatterPipe->context['primary_key']);

            $modelIdsToCreate = $standardizedData->pluck($formatterPipe->context['primary_key'])->diff($localModelIds);

            // Grab the local models that cross paths with our primary key
            $modelIdsToUpdate = $localModelIds->intersect($standardizedData->pluck($formatterPipe->context['primary_key']));
            $modelIdsToDelete = $localModelIds->diff($standardizedData->pluck($formatterPipe->context['primary_key']));

            $instances = [];
            foreach ($modelIdsToCreate as $modelId) {
                foreach ($standardizedDataGroupedByPK->get($modelId) as $formattedData) {
                    $modelOrTable = $synchronization->model;
                    $instance = class_exists($modelOrTable) ? new $modelOrTable : new \stdClass();

                    if (method_exists($instance, 'owner')) {
                        $instance->ownable_type = Credential::class;
                        $instance->ownable_id = $synchronization->credential_id;
                    }

                    foreach ($formattedData as $key => $value) {
                        $this->updateProperty($instance, $key, $value);
                    }

                    $this->makeSave($instance, $synchronization);
                    $instances[] = $instance;
                }
            }

            foreach ($modelIdsToUpdate as $modelId) {
                $tags = [];
                /** @var Model $modelToUpdateBecauseItIsRelatedToThePrimaryKey */
                foreach ($localModelsGroupedByPK[$modelId] as $modelToUpdateBecauseItIsRelatedToThePrimaryKey) {
                    foreach ($standardizedDataGroupedByPK[$modelId] as $standardDataForOurModel) {
                        $tags = $standardDataForOurModel['tags'];
                        unset($standardDataForOurModel['tags']);

                        foreach ($standardDataForOurModel as $key => $value) {
                            if ($synchronization->on_update === 'not_empty' && ! empty($standardDataForOurModel[$key])) {
                                $this->updateProperty($modelToUpdateBecauseItIsRelatedToThePrimaryKey, $key, $standardDataForOurModel[$key], $synchronization);
                            } elseif (
                                $synchronization->on_update === 'changed'
                                && $modelToUpdateBecauseItIsRelatedToThePrimaryKey->$key !== $standardDataForOurModel[$key]
                            ) {
                                $this->updateProperty($modelToUpdateBecauseItIsRelatedToThePrimaryKey, $key, $standardDataForOurModel[$key], $synchronization);
                            } elseif (
                                $synchronization->on_update === 'changed'
                                && $modelToUpdateBecauseItIsRelatedToThePrimaryKey->$key === $standardDataForOurModel[$key]
                            ) {
                                continue;
                            } else {
                                info('instance to create', [
                                    'on_update' => $synchronization->on_update,
                                    $key => empty($modelToUpdateBecauseItIsRelatedToThePrimaryKey->$key),
                                    'equals' => [$modelToUpdateBecauseItIsRelatedToThePrimaryKey->$key, $standardDataForOurModel[$key]],
                                ]);
                            }
                        }
                    }

                    $this->makeSave($modelToUpdateBecauseItIsRelatedToThePrimaryKey, $synchronization, $tags);
                    $instances[] = $modelToUpdateBecauseItIsRelatedToThePrimaryKey;
                }
            }
            info('Done', [
                'update' => $modelIdsToUpdate->count(),
                'delete' => $modelIdsToDelete->count(),
                'create' => $modelIdsToCreate->count(),
                'instances' => count($instances),
            ]);

            return $next($rebuildQuery->get());
        };
    }

    protected function makeSave($modelToUpdateBecauseItIsRelatedToThePrimaryKey, Synchronization $synchronization, array $tags)
    {
        if ($modelToUpdateBecauseItIsRelatedToThePrimaryKey instanceof Model) {
            if ($modelToUpdateBecauseItIsRelatedToThePrimaryKey->isDirty() && ! $modelToUpdateBecauseItIsRelatedToThePrimaryKey->wasRecentlyCreated) {
                info('saving update to model', [
                    'model' => $synchronization->model,
                    'primary_key' => $synchronization->formatter->context['primary_key'],
                ]);
                $modelToUpdateBecauseItIsRelatedToThePrimaryKey->save();
                /** @var HasTags $modelToUpdateBecauseItIsRelatedToThePrimaryKey */
                //                if ($modelToUpdateBecauseItIsRelatedToThePrimaryKey->tags()->)
                $modelToUpdateBecauseItIsRelatedToThePrimaryKey->attachTags($tags);
                dd($tags);
            } else {
                info('updating timestamps', [
                    'model' => $synchronization->model,
                    'primary_key' => $synchronization->formatter->context['primary_key'],
                ]);
                $modelToUpdateBecauseItIsRelatedToThePrimaryKey->touch('updated_at');
            }
            $modelToUpdateBecauseItIsRelatedToThePrimaryKey->syncTags([]);
            $modelToUpdateBecauseItIsRelatedToThePrimaryKey->attachTags($tags);
        } elseif ($modelToUpdateBecauseItIsRelatedToThePrimaryKey instanceof \stdClass) {
            if ($modelToUpdateBecauseItIsRelatedToThePrimaryKey->isDirty ?? false) {
                info('saving update to table', [
                    'model' => $synchronization->model,
                    'primary_key' => $synchronization->formatter->context['primary_key'],
                ]);
                $primaryKey = $synchronization->formatter->context['primary_key'];
                $baseQuery = \DB::table($synchronization->model)
                    ->where($primaryKey, $modelToUpdateBecauseItIsRelatedToThePrimaryKey?->changes[$primaryKey] ?? $modelToUpdateBecauseItIsRelatedToThePrimaryKey->$primaryKey);
                if ($baseQuery->exists()) {
                    $baseQuery->update($modelToUpdateBecauseItIsRelatedToThePrimaryKey->changes);
                } else {
                    $fields = array_map(fn ($class) => $class->Field, \DB::select('describe '.$synchronization->model));

                    if (in_array('ownable_type', $fields)) {
                        $this->updateProperty($modelToUpdateBecauseItIsRelatedToThePrimaryKey, 'ownable_type', Credential::class);
                        $this->updateProperty($modelToUpdateBecauseItIsRelatedToThePrimaryKey, 'ownable_id', $synchronization->credential_id);
                    }

                    $this->updateProperty($modelToUpdateBecauseItIsRelatedToThePrimaryKey, 'created_at', now());
                    $baseQuery->insert($modelToUpdateBecauseItIsRelatedToThePrimaryKey?->changes);
                }
            }
        }
    }

    protected function updateProperty(&$model, $key, $data)
    {
        if ($model instanceof Model) {
            $model->$key = $data;
            info("Changed $key to $data for a model");
        } elseif ($model instanceof \stdClass) {
            if (empty($model->changes)) {
                $model->changes = [];
            }

            if (($model->$key ?? null) == $data) {
                info('---- Not changing shit, the value is already set to that');

                return;
            }

            $model->changes[$key] = $data;
            $model->changes['updated_at'] = now();
            info("Changed $key to $data for a table: ".($model->$key ?? ''));
            $model->isDirty = true;
        }
    }

    protected function buildQueryBuilderFromModel(Synchronization $synchronization)
    {
        if (str_contains($synchronization->model, '\\')) {
            $model = $synchronization->model;

            return $model::query();
        }

        return \DB::table($synchronization->model);
    }

    protected function buildRequest(Request $request, Credential $credential, array $context = [])
    {
        /** @var Source $source */
        $source = $request->source;

        if (! empty($request->body)) {
            $body = $this->render($request->body, array_merge([
                'source' => $source,
                'credential' => $credential,
            ], $context));
        }

        $baseClient = Http::withHeaders(array_merge([
            'Content-type' => $source->response_type,
        ], match ($request->authentication) {
            'none' => [],
            'basic' => [
                'Authorization' => 'Basic '.base64_encode($credential->access_token.':'.$credential->refresh_token),
            ],
            'bearer', 'token' => [
                'Authorization' => 'Bearer '.$credential->access_token,
            ],
            'ovh-style' => [
                'X-Ovh-Signature' => '$1$'.sha1(
                    $credential->settings['application_secret'].'+'.$credential->settings['consumer_key']
                    .'+'.$source->base_url.$request->url.'+'.json_encode($body ?? '').'+'.time()
                ),
                'X-Ovh-Consumer' => $credential->settings['consumer_key'],
                'X-Ovh-Application' => $credential->settings['application_key'],
            ],
        }, ($request->headers ?? [])))->baseUrl($source->base_url);

        if (isset($body)) {
            $baseClient->withBody($body);
        }

        // Generate the http client with the headers from the credential, base url from the source, and the url and action type from the action.
        $httpClient = clone $baseClient;

        /** @var \Illuminate\Http\Client\Response $response */
        return $httpClient->{$request->method}(
            $this->render($request->url, array_merge([
                'source' => $source,
                'credential' => $credential,
            ], $context)),
            $this->render($request->body ?? $request->query_parameters, array_merge([
                'source' => $source,
                'credential' => $credential,
            ], $context)),
        );
    }
}
