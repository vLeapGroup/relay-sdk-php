<?php

namespace Vleap\Relay;

use Vleap\Relay\Types\RelayChain;

class RelayableBatchResponse
{
    public function __construct(
        public RelayChain $chain,
        public int $project,
        public array $batch
    ) {}
}
