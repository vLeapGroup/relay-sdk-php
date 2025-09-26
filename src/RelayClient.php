<?php

namespace Vleap\Relay;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use MultiversX\Transaction;
use MultiversX\Http\Entities\Account;
use MultiversX\Http\ClientFactory;
use MultiversX\Http\NetworkProvider;
use MultiversX\Address;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Vleap\Relay\Types\Env;
use Vleap\Relay\Types\RelayChain;
use Vleap\Relay\Types\ErrorType;
use Vleap\Relay\Core\Config;
use Vleap\Relay\Core\Handlers;
use Vleap\Relay\Core\RelayerConfig;
use Vleap\Relay\Core\Logger;
use Vleap\Relay\Result;
use Vleap\Relay\ErrorResponse;
use Vleap\Relay\Core\TransactionHelper;

class RelayClient
{
    private RelayerConfig $config;
    private Client $httpClient;
    private ?ClientInterface $multiversxClient = null;

    public function __construct(array $config)
    {
        $this->config = new RelayerConfig(
            project: $config['project'] ?? throw new Exception('Project is required'),
            chain: $config['chain'] ?? Config::DEFAULT_CHAIN,
            env: $config['env'] ?? Config::DEFAULT_ENV,
            api: $config['api'] ?? Config::getApiUrl($config['env'] ?? Config::DEFAULT_ENV),
            timeout: $config['timeout'] ?? 5000,
            force: $config['force'] ?? false
        );

        $this->httpClient = new Client([
            'timeout' => $this->config->timeout / 1000,
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ]);
    }

    public function relay(Transaction $tx, ?Handlers $handlers = null): Transaction
    {
        $networkApiUrl = Config::getNetworkApiUrl($this->config->env ?? Config::DEFAULT_ENV);
        $client = $this->multiversxClient ?? ClientFactory::create($networkApiUrl);
        $account = NetworkProvider::api($networkApiUrl, $client)->accounts()->getByAddress($tx->sender->bech32());
        $tx->nonce = $account->nonce;

        if ($this->hasEnoughBalance($account) && !$this->config->force) {
            return $tx;
        }

        $result = $this->makeRequest('relay/transaction', [
            'chain' => $this->config->chain?->value ?? Config::DEFAULT_CHAIN->value,
            'project' => $this->config->project,
            'tx' => $tx->toArray(),
        ]);

        if ($result->error) {
            if ($handlers && $handlers->onError) {
                ($handlers->onError)($result->error, $result->message ?? '');
            }
            Logger::error('Relay failed', [
                'error' => $result->error->value,
                'message' => $result->message
            ]);
            return $tx;
        }

        return TransactionHelper::fromArray($result->res['tx']);
    }

    public function relayBatch(array $txs, ?Handlers $handlers = null): array
    {
        $networkApiUrl = Config::getNetworkApiUrl($this->config->env ?? Config::DEFAULT_ENV);
        $client = $this->multiversxClient ?? ClientFactory::create($networkApiUrl);
        $accounts = [];

        foreach ($txs as $tx) {
            $accounts[] = NetworkProvider::api($networkApiUrl, $client)->accounts()->getByAddress($tx->sender->bech32());
        }

        foreach ($txs as $index => $tx) {
            $tx->nonce = $accounts[$index]->nonce;
        }

        if (array_reduce($accounts, fn($carry, $account) => $carry && $this->hasEnoughBalance($account), true) && !$this->config->force) {
            return $txs;
        }

        $result = $this->makeRequest('relay/batch', [
            'chain' => $this->config->chain?->value ?? Config::DEFAULT_CHAIN->value,
            'project' => $this->config->project,
            'batch' => array_map(fn($tx) => $tx->toArray(), $txs),
        ]);

        if ($result->error) {
            if ($handlers && $handlers->onError) {
                ($handlers->onError)($result->error, $result->message ?? '');
            }
            Logger::error('Relay batch failed', [
                'error' => $result->error->value,
                'message' => $result->message
            ]);
            return $txs;
        }

        return array_map(
            fn($tx) => TransactionHelper::fromArray($tx),
            $result->res['batch']
        );
    }

    public function relayOrFail(Transaction $tx, ?Handlers $handlers = null): Transaction
    {
        return $this->relay($tx, $handlers);
    }

    public function relayBatchOrFail(array $txs, ?Handlers $handlers = null): array
    {
        return $this->relayBatch($txs, $handlers);
    }

    private function makeRequest(string $path, array $data): Result
    {
        if (!$this->config->api) {
            throw new \Exception('Endpoint is not set');
        }

        $sanitizedPath = ltrim($path, '/');
        $url = "{$this->config->api}/{$sanitizedPath}";

        try {
            $response = $this->httpClient->post($url, [
                'json' => $data
            ]);

            $json = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() >= 400) {
                if ($json && isset($json['error'])) {
                    $errorResponse = new ErrorResponse(
                        ErrorType::from($json['error']),
                        $json['message'] ?? ''
                    );
                    Logger::error('API returned error', [
                        'error' => $errorResponse->error->value,
                        'message' => $errorResponse->message,
                        'status' => $response->getStatusCode(),
                        'statusText' => $response->getReasonPhrase(),
                        'url' => $url,
                    ]);
                    return new Result(null, $errorResponse->error, $errorResponse->message);
                } else {
                    Logger::error('HTTP request failed with no error details', [
                        'status' => $response->getStatusCode(),
                        'statusText' => $response->getReasonPhrase(),
                        'url' => $url,
                    ]);
                    return new Result(null, ErrorType::UNKNOWN, "HTTP error! status: {$response->getStatusCode()}");
                }
            }

            return new Result($json, null, null);

        } catch (RequestException $e) {
            $response = $e->getResponse();
            if ($response) {
                $json = json_decode($response->getBody()->getContents(), true);
                if ($json && isset($json['error'])) {
                    $errorResponse = new ErrorResponse(
                        ErrorType::from($json['error']),
                        $json['message'] ?? ''
                    );
                    Logger::error('API returned error', [
                        'error' => $errorResponse->error->value,
                        'message' => $errorResponse->message,
                        'status' => $response->getStatusCode(),
                        'statusText' => $response->getReasonPhrase(),
                        'url' => $url,
                    ]);
                    return new Result(null, $errorResponse->error, $errorResponse->message);
                } else {
                    Logger::error('HTTP request failed with no error details', [
                        'status' => $response->getStatusCode(),
                        'statusText' => $response->getReasonPhrase(),
                        'url' => $url,
                    ]);
                    return new Result(null, ErrorType::UNKNOWN, "HTTP error! status: {$response->getStatusCode()}");
                }
            }

            Logger::error('Network or request failed', [
                'error' => $e->getMessage(),
                'url' => $url,
            ]);
            return new Result(null, ErrorType::UNKNOWN, 'Network or server error occurred');
        } catch (ConnectException $e) {
            Logger::error('Network or request failed', [
                'error' => $e->getMessage(),
                'url' => $url,
            ]);
            return new Result(null, ErrorType::UNKNOWN, 'Network or server error occurred');
        } catch (\Exception $e) {
            Logger::error('Unexpected error', [
                'error' => $e->getMessage(),
                'url' => $url,
            ]);
            return new Result(null, ErrorType::UNKNOWN, 'Unexpected error occurred');
        }
    }


    private function hasEnoughBalance(Account $account): bool
    {
        $balanceThreshold = BigInteger::of('25000000000000000'); // 0.025 EGLD in wei (0.025 * 10^18)
        return BigInteger::of((string)$account->balance)->isGreaterThanOrEqualTo($balanceThreshold);
    }

}
