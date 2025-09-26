<?php

namespace Vleap\Relay;

use Vleap\Relay\Types\ErrorType;

class ErrorResponse
{
    public function __construct(
        public ErrorType $error,
        public string $message
    ) {}
}
