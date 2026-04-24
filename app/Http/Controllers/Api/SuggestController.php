<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Taggable;
use App\Operations\Operation;
use App\Services\Code;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SuggestController extends Controller
{
    public function taggableTypes(Request $request)
    {
        $classes = Code::instancesOf(Taggable::class)->getClasses();

        $types = collect($classes)->map(function (string $fqcn) {
            /** @var Model $model */
            $model = new $fqcn;
            $table = $model->getTable();
            $label = Str::title(str_replace('_', ' ', $table));

            return [
                'label' => $label,
                'fqcn' => $fqcn,
                'table' => $table,
            ];
        })->values();

        return response()->json(['data' => $types]);
    }

    public function models(Request $request)
    {
        $request->validate([
            'type' => ['required', 'string'],
            'q' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $type = str_replace('\\\\', '\\', $request->string('type')->toString());

        abort_unless(class_exists($type), 422, 'Unknown model type');

        /** @var Model $model */
        $model = new $type;
        abort_unless($model instanceof Model, 422, 'Not a model');

        /** @var Builder $query */
        $query = $model::query();

        // Scope to current user when applicable
        $table = $model->getTable();
        if (Schema::hasColumn($table, 'user_id')) {
            $query->where("{$table}.user_id", $request->user()->id);
        }
        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull("{$table}.deleted_at");
        }

        $q = $request->string('q')->toString();
        if ($q !== '') {
            // Prefer qsearch scope if available
            if (method_exists($model, 'scopeQsearch')) {
                $query->qsearch($q);
            } elseif (Schema::hasColumn($table, 'name')) {
                $query->where("{$table}.name", 'like', "%{$q}%");
            } elseif (Schema::hasColumn($table, 'title')) {
                $query->where("{$table}.title", 'like', "%{$q}%");
            }
        }

        switch ($table) {
            case 'servers':
                $query->whereHas('credential');
                break;
            default:
                break;
        }

        $items = $query->limit((int) $request->input('limit', 20))
            ->get();

        $data = $items->map(function (Model $m) use ($table) {
            $label = null;
            if (Schema::hasColumn($table, 'name')) {
                $label = $m->getAttribute('name');
                if (is_array($label)) {
                    $label = $label['en'] ?? json_encode($label);
                }
            } elseif (Schema::hasColumn($table, 'title')) {
                $label = $m->getAttribute('title');
            }

            $label ??= class_basename($m).' #'.$m->getKey();

            if ($m->credential) {
                $label = $label.' #'.$m->credential->name;
            }

            return [
                'id' => $m->getKey(),
                'label' => $label,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function operations()
    {
        $operations = collect(config('operations.operations', []))
            ->filter(fn ($class) => class_exists($class) && is_subclass_of($class, Operation::class))
            ->map(fn ($class) => [
                'label' => class_basename($class),
                'value' => $class,
            ])
            ->values();

        return response()->json(['data' => $operations]);
    }
}
