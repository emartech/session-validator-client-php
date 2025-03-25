<?php

namespace SessionValidator;

interface ClientInterface
{
    public function isValid(string $id): bool;

    public function filterInvalid(array $msids): array;

    public function getSessionData(string $sessionDataToken): array;
}
