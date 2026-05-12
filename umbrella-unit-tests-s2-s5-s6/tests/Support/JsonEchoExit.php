<?php

declare(strict_types=1);

namespace UmbrellaTests\Support;

/**
 * Thrown from test harness jsonecho() to mimic framework exit().
 *
 * Phải extends Error (không phải Exception): nhiều controller bọc logic trong
 * try { ... } catch (Exception $ex) { $this->resp->msg = $ex->getMessage(); } rồi jsonecho();
 * nếu dùng Exception thì JsonEchoExit bị bắt nhầm và msg thành "json_echo_stop".
 */
final class JsonEchoExit extends \Error
{
    /** @var object */
    public $response;

    public function __construct(object $response)
    {
        parent::__construct('json_echo_stop');
        $this->response = $response;
    }
}
