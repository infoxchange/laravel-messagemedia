<?php

namespace IxaDevStuff\MessageMedia\Response;

class Reply
{
    /** @var string|null */
    public $replyId;

    /** @var string|null */
    public $messageId;

    /** @var string|null */
    public $content;

    /** @var string|null */
    public $sourceNumber;

    /** @var string|null */
    public $destinationNumber;

    /** @var string|null */
    public $dateReceived;

    /** @var array|null */
    public $metadata;

    /**
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $reply = new self();

        $reply->replyId = $data['reply_id'] ?? null;
        $reply->messageId = $data['message_id'] ?? null;
        $reply->content = $data['content'] ?? null;
        $reply->sourceNumber = $data['source_number'] ?? null;
        $reply->destinationNumber = $data['destination_number'] ?? null;
        $reply->dateReceived = $data['date_received'] ?? null;
        $reply->metadata = $data['metadata'] ?? null;

        return $reply;
    }
}
