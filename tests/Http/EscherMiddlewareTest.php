<?php

namespace Test\SessionValidator\Http;

use Escher\Escher;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SessionValidator\Http\EscherMiddleware;

class EscherMiddlewareTest extends TestCase
{
    private MockHandler $mockHandler;
    private array $history;
    private Escher|MockObject $escherMock;
    private Client $client;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $this->history = [];
        $this->escherMock = $this->createMock(Escher::class);

        $handler = HandlerStack::create($this->mockHandler);
        $handler->push(function (callable $handler): EscherMiddleware {
            return new EscherMiddleware($handler, $this->escherMock, 'key', 'secret');
        });
        $handler->push(Middleware::history($this->history));

        $this->client = new Client([
            'base_uri' => 'http://example.org',
            'handler' => $handler,
        ]);
    }

    #[Test]
    public function itShouldSignTheGetRequest()
    {
        $this->mockHandler->append(new Response(200, [], 'OK'));

        $this->escherMock
            ->expects($this->once())
            ->method('signRequest')
            ->with('key', 'secret', 'GET', 'http://example.org/foo', '', [
                'User-Agent' => 'GuzzleHttp/7',
                'Host' => 'example.org',
            ])
            ->willreturn([
                EscherMiddleware::ESCHER_DATE_HEADER => 'foo',
                EscherMiddleware::ESCHER_AUTH_HEADER => 'bar',
            ]);

        $this->client->get('/foo');

        $this->assertEquals(1, count($this->history));
        $this->assertEquals('foo', $this->history[0]['request']->getHeader(EscherMiddleware::ESCHER_DATE_HEADER)[0]);
        $this->assertEquals('bar', $this->history[0]['request']->getHeader(EscherMiddleware::ESCHER_AUTH_HEADER)[0]);
    }

    #[Test]
    public function itShouldSignThePostRequest()
    {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json'], '[]'));

        $body = json_encode(['msids' => ['foo', 'bar']]);

        $this->escherMock
            ->expects($this->once())
            ->method('signRequest')
            ->with('key', 'secret', 'POST', 'http://example.org/bar', $body, [
                'User-Agent' => 'GuzzleHttp/7',
                'Host' => 'example.org',
                'Content-Length' => strlen($body),
                'Content-Type' => 'application/json',
            ])
            ->willreturn([
                EscherMiddleware::ESCHER_DATE_HEADER => 'foo',
                EscherMiddleware::ESCHER_AUTH_HEADER => 'bar',
            ]);

        $this->client->post('/bar', [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => $body,
        ]);

        $this->assertEquals(1, count($this->history));
        $this->assertEquals('foo', $this->history[0]['request']->getHeader(EscherMiddleware::ESCHER_DATE_HEADER)[0]);
        $this->assertEquals('bar', $this->history[0]['request']->getHeader(EscherMiddleware::ESCHER_AUTH_HEADER)[0]);
    }
}
