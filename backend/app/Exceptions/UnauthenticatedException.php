<?php

namespace App\Exceptions;

class UnauthenticatedException extends OriginalException
{
    public function __construct()
    {
        parent::__construct(
            __('auth.unAuthenticated'),
            401
        );
    }
}
