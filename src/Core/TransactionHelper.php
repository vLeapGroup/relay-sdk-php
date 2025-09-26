<?php

namespace Vleap\Relay\Core;

use MultiversX\Transaction;
use MultiversX\Address;
use MultiversX\TransactionPayload;
use Brick\Math\BigInteger;

class TransactionHelper
{
    public static function fromArray(array $data): Transaction
    {
        $sender = Address::newFromBech32($data['sender']);
        $receiver = Address::newFromBech32($data['receiver']);
        $value = BigInteger::of($data['value'] ?? '0');

        $payload = null;
        if (!empty($data['data'])) {
            $payload = new TransactionPayload(base64_decode($data['data']));
        }

        return new Transaction(
            nonce: $data['nonce'] ?? 0,
            value: $value,
            sender: $sender,
            receiver: $receiver,
            gasLimit: $data['gasLimit'] ?? 50000,
            gasPrice: $data['gasPrice'] ?? Transaction::MIN_GAS_PRICE,
            data: $payload,
            chainID: $data['chainID'] ?? '1',
            version: $data['version'] ?? Transaction::VERSION_DEFAULT,
            options: $data['options'] ?? Transaction::OPTIONS_DEFAULT
        );
    }
}
