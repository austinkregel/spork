<?php

declare(strict_types=1);

namespace App\Services\News\Feeds;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use SimpleXMLElement;

abstract class AbstractFeed
{
    protected ?SimpleXMLElement $element;

    public $data;

    public array $headers;

    public string $url;

    public function __construct(?SimpleXMLElement $element, array $headers)
    {
        $this->element = $element;
        $this->headers = $headers;
    }

    public function toArray(): array
    {
        return [
            'last_modified' => $this->getLastModified(),
            'etag' => $this->getEtag(),
            'url' => $this->url,
            'name' => $this->getName(),
            'profile_photo_path' => $this->getPhoto(),
            'data' => $this->getData(),
        ];
    }

    public function getEtag(): ?string
    {
        return Arr::get($this->headers, 'etag', [null])[0];
    }

    abstract public function getLastModified(): ?Carbon;

    abstract public function getPhoto(): ?string;

    abstract public function getName(): string;

    abstract public function getData(): array;
}
