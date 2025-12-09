<?php

namespace IxaDevStuff\MessageMedia\Exceptions;

class AuthenticationException extends MessageMediaException
{
    /**
     * @param string $message
     * @param int $code
     */
    public function __construct($message = 'Authentication failed', $code = 401)
    {
        parent::__construct($message, $code);
    }
}
