<?php

namespace WebApiCore\Http;

use ErrorException;

class HttpResponse
{
    public function __construct(public mixed $data = null, public int $statusCode = 200, public ?array $errors = null)
    {
        $this->isError = !empty($errors);
    }

    public bool $isError;

    public function send(): void
    {
        header("Content-type: application/json; charset=UTF-8");
        http_response_code($this->statusCode);
        echo json_encode(['data' => $this->data, 'errors' => $this->errors, 'isError' => $this->isError]);
        die();
    }
}
