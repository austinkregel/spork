<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App;
use App\Contracts\ModelQuery;
use App\Http\Requests\Dynamic\CreateRequest;
use App\Http\Requests\Dynamic\DeleteRequest;
use App\Http\Requests\Dynamic\ForceDeleteRequest;
use App\Http\Requests\Dynamic\IndexRequest;
use App\Http\Requests\Dynamic\RestoreRequest;
use App\Http\Requests\Dynamic\UpdateRequest;
use App\Http\Requests\Dynamic\ViewRequest;
use App\Services\ActionFilter;
use App\Services\Development\DescribeTableService;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Spatie\QueryBuilder\AllowedFilter as Filter;
use Spatie\QueryBuilder\QueryBuilder;

class LocalAdminController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    public const MODELS = [
        'accounts' => App\Models\Finance\Account::class,
        'articles' => App\Models\Article::class,
        'budgets' => App\Models\Finance\Budget::class,
        'conditions' => App\Models\Condition::class,
        'credentials' => App\Models\Credential::class,
        'domains' => App\Models\Domain::class,
        'external_rss_feeds' => App\Models\ExternalRssFeed::class,
        'messages' => App\Models\Message::class,
        'navigations' => App\Models\Navigation::class,
        'pages' => App\Models\Page::class,
        'people' => App\Models\Person::class,
        'projects' => App\Models\Project::class,
        'research' => App\Models\Research::class,
        'scripts' => App\Models\Spork\Script::class,
        'servers' => App\Models\Server::class,
        'tags' => App\Models\Tag::class,
        'threads' => App\Models\Thread::class,
        'transactions' => App\Models\Finance\Transaction::class,
        'users' => App\Models\User::class,
    ];

    public function fields(IndexRequest $request)
    {
        return response()->json((new DescribeTableService)->describe($this->getModel($request)));
    }

    protected function getModel(Request $request)
    {
        $parts = $request->path();
        $split = array_filter(explode('/', $parts), fn ($part) => ! is_numeric($part));

        $tableFromUrl = end($split);

        return static::MODELS[$tableFromUrl];
    }

    /**
     * @throws Exception
     */
    public function index(IndexRequest $request)
    {
        $class = $this->getModel($request);
        $model = new $class;
        $action = new ActionFilter(request()->get('action', 'paginate:14'));

        $description = (new DescribeTableService)->describe($model);

        $query = QueryBuilder::for($class)
            ->allowedFields($description['fields'])
            ->allowedFilters(array_merge([
                Filter::scope('q'),
            ], $description['filters']))
            ->allowedIncludes($description['includes'])
            ->allowedSorts($description['sorts']);

        return $action->execute($query);
    }

    public function store(CreateRequest $request)
    {
        $model = $this->getModel($request);
        $description = (new DescribeTableService)->describe(new $model);

        $request->validate(array_reduce($description['required'], fn ($all, $field) => array_merge(
            $all,
            [$field => 'required']
        ), []));
        /** @var ModelQuery $resource */
        $resource = new $model;
        $resource->fill($request->all());
        $resource->save();

        return $resource->refresh();
    }

    public function show(ViewRequest $request, $abstractEloquentModel = null)
    {
        $query = QueryBuilder::for($this->getModel($request));

        return $query->find($abstractEloquentModel) ?? response([
            'message' => 'No resource found by that id.',
        ], 414);
    }

    public function update(UpdateRequest $request, $abstractEloquentModel = null)
    {
        $modelClass = $this->getModel($request);
        $abstractEloquentModel = $modelClass::findOrFail($abstractEloquentModel);
        $abstractEloquentModel->update($request->all());

        return $abstractEloquentModel->refresh();
    }

    public function destroy(DeleteRequest $request, $abstractEloquentModel)
    {
        $modelClass = $this->getModel($request);
        $abstractEloquentModel = $modelClass::findOrFail($abstractEloquentModel);
        $abstractEloquentModel->delete();

        return response('', 204);
    }

    public function forceDestroy(ForceDeleteRequest $request, $abstractEloquentModel)
    {
        $modelClass = $this->getModel($request);
        $abstractEloquentModel = $modelClass::findOrFail($abstractEloquentModel);

        $model = new $modelClass;

        if (! $model->usesSoftdeletes()) {
            abort(404, 'You cannot force delete an item of this type.');

            return;
        }

        $abstractEloquentModel->forceDelete();

        return response('', 204);
    }

    public function restore(RestoreRequest $request, $model, $abstractEloquentModel)
    {
        $modelClass = $this->getModel($request);
        $abstractEloquentModel = $modelClass::findOrFail($abstractEloquentModel);

        if (! $abstractEloquentModel->usesSoftdeletes()) {
            abort(404, 'You cannot restore an item of this type.');

            return;
        }

        $abstractEloquentModel->restore();

        return $abstractEloquentModel->refresh();
    }
}
