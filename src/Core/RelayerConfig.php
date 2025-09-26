<?php

namespace Vleap\Relay\Core;

use Vleap\Relay\Types\Env;
use Vleap\Relay\Types\RelayChain;

class RelayerConfig
{
    public function __construct(
        public int $project,
        public ?RelayChain $chain = null,
        public ?Env $env = null,
        public ?string $api = null,
        public int $timeout = 5000,
        public bool $force = false
    ) {}
}
