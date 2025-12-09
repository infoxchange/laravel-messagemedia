<?php

namespace IxaDevStuff\MessageMedia\Response;

class CheckRepliesResponse
{
    /** @var array */
    public $replies = [];

    /** @var int|null */
    public $pageSize;

    /** @var int|null */
    public $pageNumber;

    /**
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $response = new self();

        if (isset($data['replies'])) {
            foreach ($data['replies'] as $replyData) {
                $response->replies[] = Reply::fromArray($replyData);
            }
        }

        $response->pageSize = $data['page_size'] ?? null;
        $response->pageNumber = $data['page_number'] ?? null;

        return $response;
    }
}
