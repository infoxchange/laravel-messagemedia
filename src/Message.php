<?php

namespace Infoxchange\MessageMedia;

class Message
{
    /** @var string|null */
    public $content;

    /** @var string|null */
    public $destinationNumber;

    /** @var string|null */
    public $sourceNumber;

    /** @var string|null */
    public $callbackUrl;

    /** @var string|null */
    public $scheduledDatetime;

    /** @var array|null */
    public $metadata;

    /** @var string|null */
    public $deliveryReportUrl;

    /** @var int|null */
    public $messageExpiryTimestamp;

    /** @var string|null */
    public $messageId;

    /**
     * @param array|null $attributes
     */
    public function __construct($attributes = null)
    {
        if ($attributes) {
            foreach ($attributes as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
    }

    /**
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $message = new self();

        $message->content = $data['content'] ?? null;
        $message->destinationNumber = $data['destination_number'] ?? null;
        $message->sourceNumber = $data['source_number'] ?? null;
        $message->callbackUrl = $data['callback_url'] ?? null;
        $message->scheduledDatetime = $data['scheduled_datetime'] ?? null;
        $message->metadata = $data['metadata'] ?? null;
        $message->deliveryReportUrl = $data['delivery_report_url'] ?? null;
        $message->messageExpiryTimestamp = $data['message_expiry_timestamp'] ?? null;
        $message->messageId = $data['message_id'] ?? null;

        return $message;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'content' => $this->content,
            'destination_number' => $this->destinationNumber,
            'source_number' => $this->sourceNumber,
            'callback_url' => $this->callbackUrl,
            'scheduled_datetime' => $this->scheduledDatetime,
            'metadata' => $this->metadata,
            'delivery_report_url' => $this->deliveryReportUrl,
            'message_expiry_timestamp' => $this->messageExpiryTimestamp,
            'message_id' => $this->messageId,
        ];
    }
}
