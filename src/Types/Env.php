<?php

namespace Vleap\Relay\Types;

enum Env: string
{
    case MAINNET = 'mainnet';
    case TESTNET = 'testnet';
    case DEVNET = 'devnet';
}
