<?php

namespace Test\SessionValidator\Http;

use Escher\Escher;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\TestCase;
use SessionValidator\Http\EscherClient;

class EscherClientTest extends TestCase
{
    /** @var ClientInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $clientMock;
    /** @var Escher|\PHPUnit_Framework_MockObject_MockObject */
    private $escherMock;

    /** @var string */
    private $escherKey;
    /** @var string */
    private $escherSecret;
    /** @var array */
    private $requestOptions;

    /** @var EscherClient */
    private $client;

    protected function setUp()
    {
        $this->clientMock = $this->createMock(ClientInterface::class);
        $this->escherMock = $this->createMock(Escher::class);

        $this->escherKey = 'escher_key';
        $this->escherSecret = 'escher_secret';
        $this->requestOptions = [
            'headers' => ['headers'],
            'body' => 'body',
        ];

        $this->client = new EscherClient($this->clientMock, $this->escherMock, $this->escherKey, $this->escherSecret);
    }

    /**
     * @test
     */
    public function requestShouldSignDefaultValues()
    {
        $method = 'GET';
        $uri = 'https://service-url/';

        $this->expectSigning($method, $uri, '', []);

        $this->client->request($method, $uri);
    }

    /**
     * @test
     */
    public function requestShouldSignGivenValues()
    {
        $method = 'GET';
        $uri = 'https://service-url/';

        $this->expectSigning($method, $uri, 'body', ['headers']);

        $this->client->request($method, $uri, $this->requestOptions);
    }

    /**
     * @test
     */
    public function requestShouldSendSignedRequest()
    {
        $signedHeaders = ['signed headers'];

        $method = 'GET';
        $uri = 'https://service-url/';
        $options = [
            'headers' => $signedHeaders,
            'body' => 'body',
        ];

        $this->mockSigningResult($signedHeaders);
        $this->expectHttpRequest($method, $uri, $options);

        $this->client->request($method, $uri, $this->requestOptions);
    }

    private function mockSigningResult($signedHeaders)
    {
        $this->escherMock
            ->expects($this->any())
            ->method('signRequest')
            ->willReturn($signedHeaders);
    }

    private function expectSigning($method, $uri, $body, $headers)
    {
        $this->escherMock
            ->expects($this->once())
            ->method('signRequest')
            ->with($this->escherKey, $this->escherSecret, $method, $uri, $body, $headers);
    }

    private function expectHttpRequest($method, $uri, $options)
    {
        $this->clientMock
            ->expects($this->once())
            ->method('request')
            ->with($method, $uri, $options);
    }
}
