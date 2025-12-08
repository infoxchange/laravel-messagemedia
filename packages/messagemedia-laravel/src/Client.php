<?php

namespace Infoxchange\MessageMedia;

use Infoxchange\MessageMedia\Http\HttpClient;
use Infoxchange\MessageMedia\Request\SendMessagesRequest;
use Infoxchange\MessageMedia\Request\CheckRepliesRequest;
use Infoxchange\MessageMedia\Request\CheckDeliveryReportsRequest;
use Infoxchange\MessageMedia\Request\ConfirmRepliesRequest;
use Infoxchange\MessageMedia\Request\ConfirmDeliveryReportsRequest;
use Infoxchange\MessageMedia\Response\SendMessagesResponse;
use Infoxchange\MessageMedia\Response\CheckRepliesResponse;
use Infoxchange\MessageMedia\Exceptions\ValidationException;

class Client
{
    /** @var HttpClient */
    private $httpClient;

    /** @var string */
    private $apiKey;

    /** @var string */
    private $apiSecret;

    /** @var string */
    private $baseUrl;

    /** @var bool */
    private $useHmac;

    /**
     * @param string $apiKey
     * @param string $apiSecret
     * @param string $baseUrl
     * @param bool $useHmac
     */
    public function __construct(
        $apiKey,
        $apiSecret,
        $baseUrl = 'https://api.messagemedia.com/v1',
        $useHmac = false
    ) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->useHmac = $useHmac;

        $this->httpClient = new HttpClient(
            $apiKey,
            $apiSecret,
            $this->baseUrl,
            $useHmac
        );
    }

    /**
     * Send messages via MessageMedia API
     *
     * @param SendMessagesRequest $request
     * @return SendMessagesResponse
     * @throws ValidationException
     * @throws \Infoxchange\MessageMedia\Exceptions\AuthenticationException
     * @throws \Infoxchange\MessageMedia\Exceptions\ApiException
     */
    public function sendMessages(SendMessagesRequest $request)
    {
        $this->validateSendMessagesRequest($request);

        $messages = array_map(function ($msg) {
            return $this->messageToArray($msg);
        }, $request->messages);

        $payload = json_encode(['messages' => $messages]);

        $response = $this->httpClient->post('/messages/send', $payload);

        return SendMessagesResponse::fromArray($response);
    }

    /**
     * Check for incoming replies
     *
     * @param CheckRepliesRequest $request
     * @return CheckRepliesResponse
     */
    public function checkReplies(CheckRepliesRequest $request)
    {
        $query = [];

        if ($request->limit !== null) {
            $query['limit'] = $request->limit;
        }
        if ($request->offset !== null) {
            $query['offset'] = $request->offset;
        }

        $response = $this->httpClient->get('/replies', $query);

        return CheckRepliesResponse::fromArray($response);
    }

    /**
     * Confirm replies have been processed
     *
     * @param ConfirmRepliesRequest $request
     * @return void
     */
    public function confirmReplies(ConfirmRepliesRequest $request)
    {
        $payload = json_encode(['reply_ids' => $request->replyIds]);
        $this->httpClient->put('/replies', $payload);
    }

    /**
     * Check delivery reports for sent messages
     *
     * @param CheckDeliveryReportsRequest $request
     * @return \Infoxchange\MessageMedia\Response\CheckDeliveryReportsResponse
     */
    public function checkDeliveryReports(CheckDeliveryReportsRequest $request)
    {
        $query = [];

        if ($request->limit !== null) {
            $query['limit'] = $request->limit;
        }
        if ($request->offset !== null) {
            $query['offset'] = $request->offset;
        }

        $response = $this->httpClient->get('/delivery-reports', $query);

        return \Infoxchange\MessageMedia\Response\CheckDeliveryReportsResponse::fromArray($response);
    }

    /**
     * Confirm delivery reports have been processed
     *
     * @param ConfirmDeliveryReportsRequest $request
     * @return void
     */
    public function confirmDeliveryReports(ConfirmDeliveryReportsRequest $request)
    {
        $payload = json_encode(['delivery_report_ids' => $request->deliveryReportIds]);
        $this->httpClient->put('/delivery-reports', $payload);
    }

    /**
     * Validate SendMessagesRequest
     *
     * @param SendMessagesRequest $request
     * @throws ValidationException
     */
    private function validateSendMessagesRequest(SendMessagesRequest $request)
    {
        if (empty($request->messages)) {
            throw new ValidationException([
                ['field' => 'messages', 'message' => 'At least one message is required'],
            ]);
        }

        $errors = [];

        foreach ($request->messages as $index => $message) {
            if (empty($message->content)) {
                $errors[] = [
                    'field' => "messages.{$index}.content",
                    'message' => 'Message content is required',
                ];
            }

            if (empty($message->destinationNumber)) {
                $errors[] = [
                    'field' => "messages.{$index}.destinationNumber",
                    'message' => 'Destination number is required',
                ];
            }

            $destNumber = $message->destinationNumber ?? '';
            if (!preg_match('/^\+?[0-9]{10,}$/', $destNumber)) {
                $errors[] = [
                    'field' => "messages.{$index}.destinationNumber",
                    'message' => 'Invalid phone number format',
                ];
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * Convert Message object to array
     *
     * @param Message $message
     * @return array
     */
    private function messageToArray(Message $message)
    {
        $data = [
            'content' => $message->content,
            'destination_number' => $message->destinationNumber,
        ];

        if (!empty($message->sourceNumber)) {
            $data['source_number'] = $message->sourceNumber;
        }

        if (!empty($message->callbackUrl)) {
            $data['callback_url'] = $message->callbackUrl;
        }

        if (!empty($message->scheduledDatetime)) {
            $data['scheduled_datetime'] = $message->scheduledDatetime;
        }

        if (!empty($message->metadata)) {
            $data['metadata'] = $message->metadata;
        }

        if (!empty($message->deliveryReportUrl)) {
            $data['delivery_report_url'] = $message->deliveryReportUrl;
        }

        if (!empty($message->messageExpiryTimestamp)) {
            $data['message_expiry_timestamp'] = $message->messageExpiryTimestamp;
        }

        return $data;
    }
}
