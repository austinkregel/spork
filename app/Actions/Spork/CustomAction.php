<?php

namespace App\Actions\Spork;

class CustomAction
{
    public function __construct(
        public $name = 'Set Namecheap DNS',
        public $url = '/api/basement/namecheap',
    ) {
    }
}
