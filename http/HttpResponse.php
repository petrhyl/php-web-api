<?php

namespace WebApiCore\Http;

class HttpResponse
{
    public function __construct(
        public mixed $data = null,
        public int $statusCode = 200,
        public ?array $errors = null,
        public array $headers = []
    ) {
        $this->isError = !empty($errors);
    }

    public bool $isError;

    public function send(): void
    {
        header("Content-type: application/json; charset=UTF-8");

        foreach ($this->headers as $header) {
            header($header);
        }

        http_response_code($this->statusCode);

        if (empty($this->errors) && empty($this->data) && $this->statusCode === 204) {
            return;
        }

        echo json_encode(
            ['data' => $this->data, 'errors' => $this->errors, 'isError' => $this->isError],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}
