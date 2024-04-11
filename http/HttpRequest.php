<?php

namespace Core\Http;

use Core\Http\HttpUser;

class HttpRequest
{
    public ?array $body = null;
    public ?array $queryParams = null;
    public string $urlPath;
    public ?string $host = null;
    public string $method;
    public array $headers = [];
    public ?HttpUser $user = null;
}
