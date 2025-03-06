<?php

namespace Test\SessionValidator;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SessionValidator\Client;

class ClientTest extends TestCase
{
    private MockHandler $mockHandler;
    private array $history;

    private Client $client;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $this->history = [];

        $handler = HandlerStack::create($this->mockHandler);
        $handler->push(Middleware::history($this->history));

        $this->client = new Client(new \GuzzleHttp\Client([
            'http_errors' => false,
            'base_uri' => 'http://example.org',
            'handler' => $handler,
        ]));
    }

    #[Test]
    public function isValidCallsTheProperApiEndpoint()
    {
        $this->mockHandler->append(new Response(200));

        $this->client->isValid('msid');

        $this->assertHttpRequest('GET', '/sessions/msid', '');
    }

    public static function serviceResponses(): array
    {
        return [
            [new Response(200), true],
            [new Response(500), true],
            [new TransferException(), true],
            [new Response(404), false],
            [new Response(401), false],
        ];
    }

    #[Test]
    #[DataProvider('serviceResponses')]
    public function isValidByMsidResultsAccordingToReturnedResponseStatuses(
        Response|GuzzleException $response,
        bool $expectedResult,
    ) {
        $this->mockHandler->append($response);

        $this->assertEquals($expectedResult, $this->client->isValid('msid'));
    }

    #[Test]
    public function filterInvalidCallsTheProperApiEndpoint()
    {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json'], '[]'));

        $msids = ['msid1', 'msid2'];
        $body = json_encode(['msids' => $msids]);

        $this->client->filterInvalid($msids);

        $this->assertHttpRequest('POST', '/sessions/filter', $body);
    }

    #[Test]
    public function filterInvalidReturnsInvalidMsidsOnSuccess()
    {
        $invalidMsids = ['msid1'];
        $responseBody = json_encode($invalidMsids);

        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json'], $responseBody));

        $this->assertEquals($invalidMsids, $this->client->filterInvalid(['msid1', 'msid2']));
    }

    #[Test]
    public function filterInvalidReturnsEmptyArrayOnHttpClientException()
    {
        $this->mockHandler->append(new TransferException());

        $this->assertEquals([], $this->client->filterInvalid(['msid1', 'msid2']));
    }

    #[Test]
    public function filterInvalidReturnsEmptyArrayOnClientError()
    {
        $this->mockHandler->append(new Response(400));

        $this->assertEquals([], $this->client->filterInvalid(['msid1', 'msid2']));
    }

    #[Test]
    public function filterInvalidReturnsEmptyArrayOnServiceError()
    {
        $this->mockHandler->append(new Response(500));

        $this->assertEquals([], $this->client->filterInvalid(['msid1', 'msid2']));
    }

    private function assertHttpRequest($method, $url, $body)
    {
        $this->assertEquals(1, count($this->history));
        $this->assertEquals($method, $this->history[0]['request']->getMethod());
        $this->assertEquals($url, $this->history[0]['request']->getUri()->getPath());
        $this->assertEquals($body, $this->history[0]['request']->getBody()->getContents());
    }
}
