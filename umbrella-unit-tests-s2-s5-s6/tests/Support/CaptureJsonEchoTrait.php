<?php

declare(strict_types=1);

namespace UmbrellaTests\Support;

trait CaptureJsonEchoTrait
{
    protected function jsonecho($resp = null): void
    {
        $src = $resp ?? $this->resp;
        $payload = json_decode(json_encode($src), false);
        throw new JsonEchoExit($payload);
    }
}
