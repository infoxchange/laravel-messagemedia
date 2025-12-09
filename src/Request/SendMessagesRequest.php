<?php

namespace IxaDevStuff\MessageMedia\Request;

class SendMessagesRequest
{
    /** @var array */
    public $messages = [];

    /**
     * @param array|null $messages
     */
    public function __construct($messages = null)
    {
        if ($messages) {
            $this->messages = $messages;
        }
    }
}
