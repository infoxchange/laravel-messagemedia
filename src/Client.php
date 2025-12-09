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

    /** @var string|null */
    private $proxyUrl;

    /**
     * @param string $apiKey
     * @param string $apiSecret
     * @param string $baseUrl
     * @param bool $useHmac
     * @param string|null $proxyUrl
     */
    public function __construct(
        $apiKey,
        $apiSecret,
        $baseUrl = 'https://api.messagemedia.com/v1',
        $useHmac = false,
        $proxyUrl = null
    ) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->useHmac = $useHmac;
        $this->proxyUrl = $proxyUrl;

        $this->httpClient = new HttpClient(
            $apiKey,
            $apiSecret,
            $this->baseUrl,
            $useHmac,
            30, // timeout
            true, // verifySsl
            $proxyUrl
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

        $response = $this->httpClient->post('/messages', $payload);

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
        $this->httpClient->post('/replies/confirmed', $payload);
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

        $response = $this->httpClient->get('/delivery_reports', $query);

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
        $this->httpClient->post('/delivery_reports/confirmed', $payload);
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

    /**
     * Get the status of a specific message
     *
     * @param string $messageId The message ID to check
     * @return Message
     * @throws ValidationException If messageId is empty
     * @throws \Infoxchange\MessageMedia\Exceptions\NotFoundException If message not found
     * @throws \Infoxchange\MessageMedia\Exceptions\AuthenticationException
     * @throws \Infoxchange\MessageMedia\Exceptions\ApiException
     */
    public function getMessageStatus($messageId)
    {
        if (empty($messageId)) {
            throw new ValidationException([
                ['field' => 'messageId', 'message' => 'Message ID is required'],
            ]);
        }

        $response = $this->httpClient->get("/messages/{$messageId}");

        return Message::fromArray($response);
    }

    /**
     * Cancel a scheduled message before it is sent
     *
     * @param string $messageId The message ID to cancel
     * @return bool True if canceled successfully
     * @throws ValidationException If messageId is empty
     * @throws \Infoxchange\MessageMedia\Exceptions\NotFoundException If message not found or already sent
     * @throws \Infoxchange\MessageMedia\Exceptions\AuthenticationException
     * @throws \Infoxchange\MessageMedia\Exceptions\ApiException
     */
    public function cancelMessage($messageId)
    {
        if (empty($messageId)) {
            throw new ValidationException([
                ['field' => 'messageId', 'message' => 'Message ID is required'],
            ]);
        }

        $this->httpClient->post("/messages/{$messageId}/cancel");

        return true;
    }

    /**
     * Get remaining account credits for prepaid accounts
     *
     * @return \Infoxchange\MessageMedia\Response\CreditsResponse
     * @throws \Infoxchange\MessageMedia\Exceptions\AuthenticationException
     * @throws \Infoxchange\MessageMedia\Exceptions\ApiException
     */
    public function getCredits()
    {
        $response = $this->httpClient->get('/credits');

        return \Infoxchange\MessageMedia\Response\CreditsResponse::fromArray($response);
    }

    /**
     * Set proxy URL for HTTP requests
     *
     * @param string|null $proxyUrl Proxy URL (e.g., 'http://proxy.example.com:8080' or 'http://user:pass@proxy.example.com:8080')
     * @return void
     */
    public function setProxy($proxyUrl)
    {
        $this->proxyUrl = $proxyUrl;
        $this->httpClient->setProxy($proxyUrl);
    }

    /**
     * Get current proxy URL
     *
     * @return string|null
     */
    public function getProxy()
    {
        return $this->proxyUrl;
    }
}
