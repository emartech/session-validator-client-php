<?php

namespace Test\SessionValidator;

use PHPUnit\Framework\TestCase;
use SessionValidator\Cache\CacheInterface;
use SessionValidator\CachedClient;
use SessionValidator\ClientInterface;

class CachedClientTest extends TestCase
{
    /** @var ClientInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $clientMock;
    /** @var CacheInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $cacheMock;

    /** @var string */
    private $msid;
    /** @var string */
    private $value;
    /** @var array */
    private $msids;
    /** @var array */
    private $invalidMsids;

    /** @var CachedClient */
    private $client;

    protected function setUp()
    {
        $this->clientMock = $this->createMock(ClientInterface::class);
        $this->cacheMock = $this->createMock(CacheInterface::class);

        $this->client = new CachedClient($this->clientMock, $this->cacheMock);

        $this->msid = 'msid';
        $this->value = 'value';

        $this->msids = ['msid1', 'msid2', 'msid3'];
        $this->invalidMsids = ['msid1', 'msid2'];
    }

    /**
     * @test
     */
    public function isValidShouldReturnTheCachedResultIfExists()
    {
        $this->mockCachedValue();
        $this->expectClientIsValidNotCalled();

        $this->assertEquals($this->value, $this->client->isValid($this->msid));
    }

    /**
     * @test
     */
    public function isValidShouldReturnTheClientsResponseIfThereIsNoCache()
    {
        $this->mockClientValue();

        $this->assertEquals($this->value, $this->client->isValid($this->msid));
    }

    /**
     * @test
     */
    public function isValidShouldCacheTheClientsResponse()
    {
        $this->mockClientValue();
        $this->expectValueCached($this->msid, $this->value);

        $this->client->isValid($this->msid);
    }

    /**
     * @test
     */
    public function filterInvalidShouldReturnTheClientsResponse()
    {
        $this->mockInvalidMsids();

        $this->assertEquals($this->invalidMsids, $this->client->filterInvalid($this->msids));
    }

    /**
     * @test
     */
    public function filterInvalidShouldCacheTheValidMsids()
    {
        $this->mockInvalidMsids();
        $this->expectValueCached('msid3', true);

        $this->assertEquals($this->invalidMsids, $this->client->filterInvalid($this->msids));
    }

    private function mockCachedValue()
    {
        $this->cacheMock
            ->expects($this->once())
            ->method('get')
            ->with($this->msid)
            ->willReturn($this->value);
    }

    private function mockClientValue()
    {
        $this->clientMock
            ->expects($this->once())
            ->method('isValid')
            ->with($this->msid)
            ->willReturn($this->value);
    }

    private function mockInvalidMsids()
    {
        $this->clientMock
            ->expects($this->once())
            ->method('filterInvalid')
            ->with($this->msids)
            ->willReturn($this->invalidMsids);
    }

    private function expectClientIsValidNotCalled()
    {
        $this->clientMock
            ->expects($this->never())
            ->method('isValid');
    }

    private function expectValueCached($msid, $value)
    {
        $this->cacheMock
            ->expects($this->once())
            ->method('set')
            ->with($msid, $value);
    }
}
