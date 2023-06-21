<?php

namespace App\Services;

use App\Contracts\Services\MustacheTemplateService;

use Mustache_Engine;

class MustacheService extends Mustache_Engine implements MustacheTemplateService
{
    public function render($template, $context = array())
    {
        return parent::render($template, $context);
    }
}
