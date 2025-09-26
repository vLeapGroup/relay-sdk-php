<?php

namespace Vleap\Relay\Core;

class Handlers
{
    public $onError;

    public function __construct(?callable $onError = null)
    {
        $this->onError = $onError;
    }
}
