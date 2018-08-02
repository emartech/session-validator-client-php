<?php

namespace Test\SessionValidator;

use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use SessionValidator\Client;
use SessionValidator\Http\EscherClient;

class ClientTest extends TestCase
{
    /** @var EscherClient|\PHPUnit_Framework_MockObject_MockObject */
    private $escherClientMock;

    /** @var string */
    private $serviceUrl;

    /** @var Client */
    private $client;

    protected function setUp()
    {
        $this->escherClientMock = $this->createMock(EscherClient::class);

        $this->serviceUrl = 'https://service-url';

        $this->client = new Client(
            $this->escherClientMock,
            $this->serviceUrl
        );
    }

    /**
     * @test
     */
    public function isValidCallsTheProperApiEndpoint()
    {
        $this->expectHttpRequest('GET', "{$this->serviceUrl}/sessions/msid", '');

        $this->client->isValid('msid');
    }

    /**
     * @test
     */
    public function isValidReturnsTrueOnSuccessfulResponse()
    {
        $this->mockHttpResponse(new Response());

        $this->assertTrue($this->client->isValid('msid'));
    }

    /**
     * @test
     */
    public function isValidReturnsTrueOnServiceError()
    {
        $this->mockHttpResponse(new Response(500));

        $this->assertTrue($this->client->isValid('msid'));
    }

    /**
     * @test
     */
    public function isValidReturnsTrueOnHttpClientException()
    {
        $this->mockHttpClientException();

        $this->assertTrue($this->client->isValid('msid'));
    }

    /**
     * @test
     */
    public function isValidReturnsFalseOnNotFound()
    {
        $this->mockHttpResponse(new Response(404));

        $this->assertFalse($this->client->isValid('msid'));
    }

    /**
     * @test
     */
    public function filterInvalidCallsTheProperApiEndpoint()
    {
        $msids = ['msid1', 'msid2'];
        $body = json_encode(['msids' => $msids]);

        $this->expectHttpRequest('POST', "{$this->serviceUrl}/sessions/filter", $body);

        $this->client->filterInvalid($msids);
    }

    /**
     * @test
     */
    public function filterInvalidReturnsInvalidMsidsOnSuccess()
    {
        $invalidMsids = ['msid1'];
        $responseBody = json_encode(['msids' => $invalidMsids]);

        $this->mockHttpResponse(new Response(200, [], $responseBody));

        $this->assertEquals($invalidMsids, $this->client->filterInvalid(['msid1', 'msid2']));
    }

    /**
     * @test
     */
    public function filterInvalidReturnsEmptyArrayOnHttpClientException()
    {
        $this->mockHttpClientException();

        $this->assertEquals([], $this->client->filterInvalid(['msid1', 'msid2']));
    }

    /**
     * @test
     */
    public function filterInvalidReturnsEmptyArrayOnClientError()
    {
        $this->mockHttpResponse(new Response(400));

        $this->assertEquals([], $this->client->filterInvalid(['msid1', 'msid2']));
    }

    /**
     * @test
     */
    public function filterInvalidReturnsEmptyArrayOnServiceError()
    {
        $this->mockHttpResponse(new Response(500));

        $this->assertEquals([], $this->client->filterInvalid(['msid1', 'msid2']));
    }

    private function mockHttpResponse($response)
    {
        $this->escherClientMock
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);
    }

    private function mockHttpClientException()
    {
        $this->escherClientMock
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new TransferException());
    }

    private function expectHttpRequest($method, $url, $body)
    {
        $this->escherClientMock
            ->expects($this->once())
            ->method('request')
            ->with($method, $url, [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => $body
            ])
            ->willReturn(new Response());
    }
}
