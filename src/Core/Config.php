<?php

namespace Vleap\Relay\Core;

use Vleap\Relay\Types\Env;
use Vleap\Relay\Types\RelayChain;

class Config
{
    public const DEFAULT_CHAIN = RelayChain::MULTIVERSX;
    public const DEFAULT_ENV = Env::MAINNET;

    public const ACCOUNT_MAX_BALANCE = 0.025;

    public const EGLD_DECIMALS = 18;

    public static function getApiUrl(Env $env): string
    {
        return match ($env) {
            Env::DEVNET => 'https://devnet-relay.vleap.ai',
            Env::TESTNET => throw new \Exception('Testnet is not supported yet'),
            Env::MAINNET => 'https://relay.vleap.ai',
        };
    }

    public static function getNetworkApiUrl(Env $env): string
    {
        return match ($env) {
            Env::DEVNET => 'https://devnet-api.multiversx.com',
            Env::TESTNET => 'https://testnet-api.multiversx.com',
            Env::MAINNET => 'https://api.multiversx.com',
        };
    }
}
