<?php

namespace App\Exceptions;

class NotFoundException extends OriginalException
{
    public function __construct(?string $message = null)
    {
        parent::__construct(
            is_null($message) ? __('response.not_found') : $message,
            404
        );
    }
}
