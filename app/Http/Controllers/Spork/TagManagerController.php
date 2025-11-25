<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Models\Tag;

class TagManagerController
{
    public function __invoke()
    {
        return redirect('/-/automation/tags');
    }

    public function show(Tag $tag)
    {
        return redirect('/-/automation/tags/'.$tag->id);
    }
}
