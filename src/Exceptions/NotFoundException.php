<?php

namespace IxaDevStuff\MessageMedia\Exceptions;

class NotFoundException extends MessageMediaException
{
    /**
     * @param string $message
     * @param int $code
     */
    public function __construct($message = 'Resource not found', $code = 404)
    {
        parent::__construct($message, $code);
    }
}
