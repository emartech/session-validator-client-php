<?php

namespace SessionValidator;

interface ClientInterface
{
    /**
     * @param string $msid
     * @return bool
     */
    public function isValid($msid);

    /**
     * @param array $msids
     * @return array
     */
    public function filterInvalid(array $msids);
}
