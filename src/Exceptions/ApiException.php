<?php

namespace IxaDevStuff\MessageMedia\Exceptions;

class ApiException extends MessageMediaException
{
    /**
     * @param string $message
     * @param int $code
     */
    public function __construct($message = 'API error', $code = 500)
    {
        parent::__construct($message, $code);
    }
}
