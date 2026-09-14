<?php

namespace App\Exceptions;

class UnprocessableException extends OriginalException
{
    public function __construct(string $message)
    {
        parent::__construct(
            $message,
            422
        );
    }
}
