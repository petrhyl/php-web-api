<?php

namespace WebApiCore\Http;

use WebApiCore\Http\HttpUser;

class HttpRequest
{
    public ?array $body = null;
    public array $queryParams = [];
    public string $urlPath;
    public string $host;
    public string $userAgent;
    public string $method;
    public array $headers = [];
    public ?HttpUser $user = null;
}
