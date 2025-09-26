<?php

namespace Vleap\Relay;

use Vleap\Relay\Types\RelayChain;

class RelayableTxResponse
{
    public function __construct(
        public RelayChain $chain,
        public int $project,
        public object $tx
    ) {}
}
