<?php

namespace Vleap\Relay;

use Vleap\Relay\Types\ErrorType;

class Result
{
    public function __construct(
        public mixed $res = null,
        public ?ErrorType $error = null,
        public ?string $message = null
    ) {}
}
