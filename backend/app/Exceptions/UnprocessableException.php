<?php

namespace App\Exceptions;

class UnprocessableException extends OriginalException
{
    /** Create an exception for a semantically invalid request. */
    public function __construct(string $message)
    {
        parent::__construct(
            $message,
            422
        );
    }
}
