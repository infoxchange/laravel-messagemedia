<?php

namespace IxaDevStuff\MessageMedia\Request;

class ConfirmDeliveryReportsRequest
{
    /** @var array */
    public $deliveryReportIds = [];

    /**
     * @param array $deliveryReportIds
     */
    public function __construct(array $deliveryReportIds = [])
    {
        $this->deliveryReportIds = $deliveryReportIds;
    }
}
