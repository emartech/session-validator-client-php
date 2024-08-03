<?php

namespace SessionValidator;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SessionValidator\Http\EscherMiddleware;

class Client implements ClientInterface
{
    const SERVICE_TIMEOUT = 0.25;

    private \GuzzleHttp\ClientInterface $httpClient;
    private LoggerInterface $logger;

    public static function create(string $serviceUrl, ?string $escherKey = null, ?string $escherSecret = null)
    {
        $config = [
            'http_errors' => false,
            'timeout' => self::SERVICE_TIMEOUT,
            'base_uri' => $serviceUrl,
        ];
        if ($escherKey && $escherSecret) {
            $handler = HandlerStack::create();
            $handler->push(EscherMiddleware::create($escherKey, $escherSecret), 'escher_signer');

            $config['handler'] = $handler;
        }
        $httpClient = new \GuzzleHttp\Client($config);

        return new self($httpClient);
    }

    public function __construct(\GuzzleHttp\ClientInterface $client)
    {
        $this->httpClient = $client;

        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function isValid(string $msid): bool
    {
        $response = $this->sendRequest('GET', "/sessions/$msid");

        if ($response) {
            return $response->getStatusCode() === 200 || $response->getStatusCode() >= 500;
        } else {
            return true;
        }
    }

    public function filterInvalid(array $msids): array
    {
        $body = json_encode(['msids' => $msids]);

        $response = $this->sendRequest('POST', '/sessions/filter', $body);

        if ($response && $response->getStatusCode() === 200) {
            $responseData = json_decode($response->getBody()->getContents(), true);
            return $responseData;
        } else {
            return [];
        }
    }

    private function sendRequest(string $method, string $url, string $body = ''): ?Response
    {
        try {
            $response = $this->httpClient->request($method, $url, [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => $body,
            ]);
            $this->logResult($response);

            return $response;
        } catch (GuzzleException $e) {
            $this->logException($e);
            return null;
        }
    }

    private function logResult(ResponseInterface $response): void
    {
        switch ($response->getStatusCode()) {
            case 200:
                $this->logger->info('MSID exists');
                break;
            case 404:
                $this->logger->info('MSID does not exist');
                break;
            default:
                $this->logger->info("Invalid response: {$response->getStatusCode()}");
                break;
        }
    }

    private function logException(GuzzleException $exception): void
    {
        $this->logger->info($exception->getMessage());
    }
}
