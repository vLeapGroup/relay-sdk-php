<?php

namespace Vleap\Relay\Types;

enum ErrorType: string
{
    case RATE_LIMITED = 'rate-limited';
    case BALANCE_REQUIRED = 'balance-required';
    case WHITELIST_REQUIRED = 'whitelist-required';
    case UNKNOWN = 'unknown';
}
