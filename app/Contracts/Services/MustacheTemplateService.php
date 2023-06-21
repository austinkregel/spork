<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use Mustache_Cache;
use Mustache_Compiler;
use Mustache_Loader;
use Mustache_Parser;
use Mustache_Tokenizer;

interface MustacheTemplateService
{
    public function render($template, $context = []);

    public function getEscape();

    public function getEntityFlags();

    public function getCharset();

    public function getPragmas();

    public function setLoader(Mustache_Loader $loader);

    public function getLoader();

    public function setPartialsLoader(Mustache_Loader $partialsLoader);

    public function getPartialsLoader();

    public function setPartials(array $partials = []);

    public function setHelpers($helpers);

    public function getHelpers();

    public function addHelper($name, $helper);

    public function getHelper($name);

    public function hasHelper($name);

    public function removeHelper($name);

    public function setLogger($logger = null);

    public function getLogger();

    public function setTokenizer(Mustache_Tokenizer $tokenizer);

    public function getTokenizer();

    public function setParser(Mustache_Parser $parser);

    public function getParser();

    public function setCompiler(Mustache_Compiler $compiler);

    public function getCompiler();

    public function setCache(Mustache_Cache $cache);

    public function getCache();

    public function getTemplateClassName($source);

    public function loadTemplate($name);

    public function loadPartial($name);

    public function loadLambda($source, $delims = null);
}
