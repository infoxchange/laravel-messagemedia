<?php

namespace Infoxchange\MessageMedia\Response;

use Infoxchange\MessageMedia\Message;

class SendMessagesResponse
{
    /** @var array */
    public $messages = [];

    /**
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $response = new self();

        if (isset($data['messages'])) {
            foreach ($data['messages'] as $msgData) {
                $response->messages[] = Message::fromArray($msgData);
            }
        }

        return $response;
    }
}
