<?php

namespace WebApiCore\Http;

class HttpResponse
{
    /**
     * @param mixed $data
     * @param int $statusCode
     * @param array|null $error an error object as an associative array
     * @param array $headers an array of headers defined as strings
     */
    public function __construct(
        public mixed $data = null,
        public int $statusCode = 200,
        public ?array $error = null,
        public array $headers = []
    ) {
        $this->isError = !empty($error);
    }

    public bool $isError;

    public function send(): void
    {
        header("Content-type: application/json; charset=UTF-8");

        foreach ($this->headers as $header) {
            header($header);
        }

        http_response_code($this->statusCode);

        if (empty($this->error) && $this->data === null) {
            return;
        }

        echo json_encode(
            ['data' => $this->data, 'error' => $this->error, 'isError' => $this->isError],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}
