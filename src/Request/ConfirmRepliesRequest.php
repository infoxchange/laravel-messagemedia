<?php

namespace IxaDevStuff\MessageMedia\Request;

class ConfirmRepliesRequest
{
    /** @var array */
    public $replyIds = [];

    /**
     * @param array $replyIds
     */
    public function __construct(array $replyIds = [])
    {
        $this->replyIds = $replyIds;
    }
}
