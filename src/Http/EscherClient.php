<?php

namespace SessionValidator\Http;

use Escher\Escher;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class EscherClient
{
    const ESCHER_CREDENTIAL_SCOPE = 'eu/session-validator/ems_request';
    const ESCHER_PREFIX = 'EMS';
    const ESCHER_DATE_HEADER = 'X-Ems-Date';
    const ESCHER_AUTH_HEADER = 'X-Ems-Auth';

    /** @var Escher */
    private $escher;
    /** @var ClientInterface */
    private $client;

    /** @var string */
    private $escherKey;
    /** @var string */
    private $escherSecret;

    public static function create($escherKey, $escherSecret, array $config = [])
    {
        $client = new Client($config);

        $escher = Escher::create(self::ESCHER_CREDENTIAL_SCOPE)
            ->setAlgoPrefix(self::ESCHER_PREFIX)
            ->setVendorKey(self::ESCHER_PREFIX)
            ->setDateHeaderKey(self::ESCHER_DATE_HEADER)
            ->setAuthHeaderKey(self::ESCHER_AUTH_HEADER);

        return new self($client, $escher, $escherKey, $escherSecret);
    }

    public function __construct(ClientInterface $client, Escher $escher, $escherKey, $escherSecret)
    {
        $this->client = $client;
        $this->escher = $escher;

        $this->escherKey = $escherKey;
        $this->escherSecret = $escherSecret;
    }

    /**
     * @throws GuzzleException
     */
    public function request($method, $uri, array $options = [])
    {
        $body = array_key_exists('body', $options) ? $options['body'] : '';
        $headers = array_key_exists('headers', $options) ? $options['headers'] : [];

        $options['headers'] = $this->escher->signRequest(
            $this->escherKey,
            $this->escherSecret,
            $method,
            $uri,
            $body,
            $headers
        );

        return $this->client->request($method, $uri, $options);
    }
}
