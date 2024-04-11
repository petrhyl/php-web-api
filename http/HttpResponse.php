<?php

namespace WebApiCore\Http;

use ErrorException;

class HttpResponse
{
    public function __construct(public ?array $data = null, public ?array $errors = null)
    {
        if (empty($data) && empty($errors)) {
            throw new ErrorException("If there is no error the data must be provided.");
        }

        $this->is_error = !empty($errors);
    }

    public bool $is_error;
}
