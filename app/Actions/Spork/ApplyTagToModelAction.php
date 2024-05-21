<?php

declare(strict_types=1);

namespace App\Actions\Spork;

use App\Contracts\ActionInterface;
use App\Models\Credential;
use App\Models\Domain;
use App\Models\ExternalRssFeed;
use App\Models\Finance\Account;
use App\Models\Finance\Transaction;
use App\Models\Page;
use App\Models\Person;
use App\Models\Research;
use App\Models\Tag;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\Request;

class ApplyTagToModelAction extends CustomAction implements ActionInterface
{
    public function __construct(
        $name = 'Apply Tag',
        $slug = 'apply-tag'
    ) {
        parent::__construct($name, $slug, models: [
            Domain::class,
            Credential::class,
            ExternalRssFeed::class,
            Transaction::class,
            Research::class,
            Person::class,
            Account::class,
            Page::class,
        ]);
    }

    public function __invoke(Dispatcher $dispatcher, Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'tag' => 'required|string',
        ]);
    }

    public function fields(): array
    {
        return [
            'tag' => [
                'type' => 'select',
                'label' => 'Tag',
                'required' => true,
                'options' => Tag::all(),
            ],
        ];
    }
}
