<?php

namespace SessionValidator\Http;

use Escher\Escher;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\RequestInterface;

class EscherMiddleware
{
    const ESCHER_CREDENTIAL_SCOPE = 'eu/session-validator/ems_request';
    const ESCHER_PREFIX = 'EMS';
    const ESCHER_DATE_HEADER = 'X-Ems-Date';
    const ESCHER_AUTH_HEADER = 'X-Ems-Auth';

    public static function create(string $escherKey, string $escherSecret)
    {
        $escher = Escher::create(self::ESCHER_CREDENTIAL_SCOPE)
            ->setAlgoPrefix(self::ESCHER_PREFIX)
            ->setVendorKey(self::ESCHER_PREFIX)
            ->setDateHeaderKey(self::ESCHER_DATE_HEADER)
            ->setAuthHeaderKey(self::ESCHER_AUTH_HEADER);

        return static function (callable $handler) use ($escher, $escherKey, $escherSecret): self {
            return new self($handler, $escher, $escherKey, $escherSecret);
        };
    }

    public function __construct(
        private $nextHandler,
        private readonly Escher $escher,
        private readonly string $escherKey,
        private readonly string $escherSecret,
    ) {
    }

    public function __invoke(RequestInterface $request, array $options): PromiseInterface
    {
        $headers = $request->getHeaders();
        foreach ($headers as $k => $v) {
            $headers[$k] = implode(',', $v);
        }

        $modify = [
            'set_headers' => $this->escher->signRequest(
                $this->escherKey,
                $this->escherSecret,
                $request->getMethod(),
                (string) $request->getUri(),
                $request->getBody()->getContents(),
                $headers,
            ),
        ];

        $fn = $this->nextHandler;
        return $fn(Utils::modifyRequest($request, $modify), $options);
    }
}
