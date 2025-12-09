<?php

namespace IxaDevStuff\MessageMedia\Response;

class CheckDeliveryReportsResponse
{
    /** @var array */
    public $deliveryReports = [];

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

        if (isset($data['delivery_reports'])) {
            foreach ($data['delivery_reports'] as $reportData) {
                $response->deliveryReports[] = DeliveryReport::fromArray($reportData);
            }
        }

        $response->pageSize = $data['page_size'] ?? null;
        $response->pageNumber = $data['page_number'] ?? null;

        return $response;
    }
}
