# Relay SDK

**Enable gasless transactions on MultiversX with just one line of code change.**

```bash
composer require vleap/relay-sdk
```

**Before:**

```php
$result = $wallet->sendTransaction($transaction);
```

**After:**

```php
$relayableTx = (new RelayClient(['project' => 'your-project-id']))->relay($transaction);
$result = $wallet->sendTransaction($relayableTx);
```

That's it! Your users now pay zero gas fees when they have a fresh wallet or low balance.

## Examples

```php
use VLeap\RelaySdk\RelayClient;

// Single transaction
$relayedTx = (new RelayClient(['project' => 'your-project-id']))->relay($transaction);

// Batch transactions
$relayedTxs = (new RelayClient(['project' => 'your-project-id']))->relayBatch($transactions);
```

## Support

- **GitHub**: [vLeap Group](https://github.com/vLeapGroup)
- **Telegram**: [https://telegram.vleap.ai](https://telegram.vleap.ai)
