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
use SessionValidator\SessionDataException;
use SessionValidator\SessionDataNotFoundException;

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
    public function isValidWithMsidCallsTheProperApiEndpoint()
    {
        $this->mockHandler->append(new Response(200));

        $this->client->isValid('example_abcdef12345678.12345678');

        $this->assertHttpRequest('GET', '/sessions/example_abcdef12345678.12345678', '');
    }

    public static function serviceResponsesByMSid(): array
    {
        return [
            [new Response(200), true],
            [new Response(500), true],
            [new Response(401), false],
            [new Response(404), false],
            [new TransferException(), true],
        ];
    }

    #[Test]
    #[DataProvider('serviceResponsesByMSid')]
    public function isValidByMsidResultsAccordingToReturnedResponseStatuses(
        Response|GuzzleException $response,
        bool $expectedResult,
    ) {
        $this->mockHandler->append($response);

        $this->assertEquals($expectedResult, $this->client->isValid('example_abcdef12345678.12345678'));
    }

    #[Test]
    public function isValidWithSessionDataTokenCallsTheProperApiEndpoint()
    {
        $this->mockHandler->append(new Response(200));

        $this->client->isValid('session-data-token');

        $this->assertHttpRequestWithAuthorizationBearerHeader('HEAD', '/sessions', 'session-data-token');
    }

    public static function serviceResponsesBySessionDataToken(): array
    {
        return [
            [new Response(200), true],
            [new Response(401), false],
            [new Response(404), false],
        ];
    }

    #[Test]
    #[DataProvider('serviceResponsesBySessionDataToken')]
    public function isValidBySessionDataTokenResultsAccordingToReturnedResponseStatuses(
        Response|GuzzleException $response,
        bool $expectedResult,
    ) {
        $this->mockHandler->append($response);

        $this->assertEquals($expectedResult, $this->client->isValid('session-data-token'));
    }

    public static function raisedExceptions(): array
    {
        return [
            [
                new Response(500),
                SessionDataException::class,
                'Service unreachable'
            ],
            [
                new TransferException(),
                SessionDataException::class,
                'Service unreachable'
            ]
        ];
    }

    #[Test]
    #[DataProvider('raisedExceptions')]
    public function isValidBySessionDataTokenResultsExceptionsAccordingToReturnedResponseStatuses(
        Response|GuzzleException $response,
        string $expectedException,
        string $expectedMessage,
    ) {
        $this->mockHandler->append($response);

        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMessage);

        $this->client->isValid('session-data-token');
    }

    #[Test]
    public function getSessionDataReturnsSessionData()
    {
        $this->mockHandler->append(new Response(200, body: '{ "some": "data" }'));

        $response = $this->client->getSessionData('session-data-token');

        $this->assertHttpRequestWithAuthorizationBearerHeader('GET', '/sessions', 'session-data-token');
        $this->assertEquals(['some' => 'data'], $response);
    }

    #[Test]
    public function getSessionDataRaisesSessionDataNotFoundExceptionIfSessionDataIsNotFound()
    {
        $this->mockHandler->append(new Response(401));

        $this->expectException(SessionDataNotFoundException::class);

        $this->client->getSessionData('session-data-token');
    }

    #[Test]
    public function getSessionDataRaisesSessionDataExceptionIf500Returned()
    {
        $this->mockHandler->append(new Response(500));

        $this->expectException(SessionDataException::class);
        $this->expectExceptionMessage('Service unreachable');

        $this->client->getSessionData('session-data-token');
    }

    #[Test]
    public function getSessionDataRaisesSessionDataExceptionIfSomethingGoesWrong()
    {
        $this->mockHandler->append(new TransferException());

        $this->expectException(SessionDataException::class);
        $this->expectExceptionMessage('Service unreachable');

        $this->client->isValid('session-data-token');
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

    private function assertHttpRequestWithAuthorizationBearerHeader($method, $url, $token)
    {
        $this->assertEquals(1, count($this->history));
        $this->assertEquals($method, $this->history[0]['request']->getMethod());
        $this->assertEquals($url, $this->history[0]['request']->getUri()->getPath());
        $this->assertEquals('Bearer ' . $token, $this->history[0]['request']->getHeader('Authorization')[0]);
    }
}
