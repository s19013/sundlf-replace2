<?php

namespace App\Exceptions;

use Exception;

class NotFoundException extends Exception
{
    public function __construct(?string $message = null)
    {
        parent::__construct(
            is_null($message) ? __('response.not_found') : $message,
            404
        );
    }
}
