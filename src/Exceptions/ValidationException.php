<?php

namespace Infoxchange\MessageMedia\Exceptions;

class ValidationException extends MessageMediaException
{
    /** @var array */
    public $errors = [];

    /**
     * @param array $errors
     * @param string $message
     * @param int $code
     */
    public function __construct(array $errors = [], $message = '', $code = 422)
    {
        $this->errors = $errors;

        if (empty($message)) {
            $message = 'Validation failed: ' . json_encode($errors);
        }

        parent::__construct($message, $code);
    }
}
