<?php

namespace SessionValidator;

use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SessionValidator\Http\EscherClient;

class Client implements ClientInterface
{
    const SERVICE_TIMEOUT = 0.25;

    /** @var EscherClient */
    private $client;
    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $serviceUrl;

    public static function create($serviceUrl, $escherKey, $escherSecret)
    {
        $httpClient = EscherClient::create($escherKey, $escherSecret, [
            'http_errors' => false,
            'timeout' => self::SERVICE_TIMEOUT,
        ]);

        return new self($httpClient, $serviceUrl);
    }

    public function __construct(EscherClient $client, $serviceUrl)
    {
        $this->client = $client;
        $this->serviceUrl = $serviceUrl;

        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function isValid($msid)
    {
        $response = $this->sendRequest('GET', "{$this->serviceUrl}/sessions/$msid");

        if ($response) {
            return $response->getStatusCode() === 200 || $response->getStatusCode() >= 500;
        } else {
            return true;
        }
    }

    public function filterInvalid(array $msids)
    {
        $body = json_encode(['msids' => $msids]);

        $response = $this->sendRequest('POST', "{$this->serviceUrl}/sessions/filter", $body);

        if ($response && $response->getStatusCode() === 200) {
            $responseData = json_decode($response->getBody(), true);
            return $responseData['msids'];
        } else {
            return [];
        }
    }

    private function sendRequest($method, $url, $body = '')
    {
        try {
            $response = $this->client->request($method, $url, [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => $body,
            ]);
            $this->logResult($response);

            return $response;
        } catch (GuzzleException $e) {
            $this->logException($e);
            return false;
        }
    }

    private function logResult(ResponseInterface $response)
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

    private function logException(GuzzleException $exception)
    {
        $this->logger->info($exception->getMessage());
    }
}
