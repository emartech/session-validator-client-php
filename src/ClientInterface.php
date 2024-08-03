<?php

namespace SessionValidator;

interface ClientInterface
{
    public function isValid(string $msid): bool;

    public function filterInvalid(array $msids): array;
}
