<?php

namespace IxaDevStuff\MessageMedia\Response;

class DeliveryReport
{
    /** @var string|null */
    public $deliveryReportId;

    /** @var string|null */
    public $messageId;

    /** @var string|null */
    public $status;

    /** @var string|null */
    public $statusCode;

    /** @var string|null */
    public $dateReceived;

    /** @var string|null */
    public $sourceNumber;

    /** @var string|null */
    public $destinationNumber;

    /** @var array|null */
    public $metadata;

    /**
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $report = new self();

        $report->deliveryReportId = $data['delivery_report_id'] ?? null;
        $report->messageId = $data['message_id'] ?? null;
        $report->status = $data['status'] ?? null;
        $report->statusCode = $data['status_code'] ?? null;
        $report->dateReceived = $data['date_received'] ?? null;
        $report->sourceNumber = $data['source_number'] ?? null;
        $report->destinationNumber = $data['destination_number'] ?? null;
        $report->metadata = $data['metadata'] ?? null;

        return $report;
    }
}
