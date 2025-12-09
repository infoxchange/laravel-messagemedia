<?php

namespace Infoxchange\MessageMedia\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Infoxchange\MessageMedia\Client;
use Infoxchange\MessageMedia\Message;
use Infoxchange\MessageMedia\Request\SendMessagesRequest;
use Infoxchange\MessageMedia\Exceptions\ValidationException;

class ClientTest extends TestCase
{
    /** @var Client */
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->client = new Client(
            'test_key',
            'test_secret'
        );
    }

    /**
     * @test
     */
    public function it_validates_empty_messages()
    {
        $this->expectException(ValidationException::class);

        $request = new SendMessagesRequest();
        $request->messages = [];

        $this->client->sendMessages($request);
    }

    /**
     * @test
     */
    public function it_validates_missing_content()
    {
        $this->expectException(ValidationException::class);

        $request = new SendMessagesRequest();
        $message = new Message();
        $message->destinationNumber = '+61491570156';
        // Missing content
        $request->messages = [$message];

        $this->client->sendMessages($request);
    }

    /**
     * @test
     */
    public function it_validates_missing_destination()
    {
        $this->expectException(ValidationException::class);

        $request = new SendMessagesRequest();
        $message = new Message();
        $message->content = 'Test message';
        // Missing destination
        $request->messages = [$message];

        $this->client->sendMessages($request);
    }

    /**
     * @test
     */
    public function it_validates_invalid_phone_number()
    {
        $this->expectException(ValidationException::class);

        $request = new SendMessagesRequest();
        $message = new Message();
        $message->content = 'Test message';
        $message->destinationNumber = 'invalid';
        $request->messages = [$message];

        $this->client->sendMessages($request);
    }

    /**
     * @test
     */
    public function it_creates_message_from_array()
    {
        $data = [
            'content' => 'Test',
            'destination_number' => '+61491570156',
            'message_id' => 'test-id-123',
        ];

        $message = Message::fromArray($data);

        $this->assertEquals('Test', $message->content);
        $this->assertEquals('+61491570156', $message->destinationNumber);
        $this->assertEquals('test-id-123', $message->messageId);
    }

    /**
     * @test
     */
    public function it_converts_message_to_array()
    {
        $message = new Message();
        $message->content = 'Test';
        $message->destinationNumber = '+61491570156';
        $message->messageId = 'test-id-123';

        $array = $message->toArray();

        $this->assertEquals('Test', $array['content']);
        $this->assertEquals('+61491570156', $array['destination_number']);
        $this->assertEquals('test-id-123', $array['message_id']);
    }

    /**
     * @test
     */
    public function it_handles_status_field_in_message()
    {
        $data = [
            'content' => 'Test',
            'destination_number' => '+61491570156',
            'message_id' => 'test-id-123',
            'status' => 'delivered',
        ];

        $message = Message::fromArray($data);

        $this->assertEquals('delivered', $message->status);

        $array = $message->toArray();
        $this->assertEquals('delivered', $array['status']);
    }

    /**
     * @test
     */
    public function it_handles_delivery_report_field_in_message()
    {
        $data = [
            'content' => 'Test',
            'destination_number' => '+61491570156',
            'message_id' => 'test-id-123',
            'delivery_report' => true,
        ];

        $message = Message::fromArray($data);

        $this->assertTrue($message->deliveryReport);

        $array = $message->toArray();
        $this->assertTrue($array['delivery_report']);
    }

    /**
     * @test
     */
    public function it_maintains_backward_compatibility_with_missing_new_fields()
    {
        $data = [
            'content' => 'Test',
            'destination_number' => '+61491570156',
            'message_id' => 'test-id-123',
        ];

        $message = Message::fromArray($data);

        $this->assertNull($message->status);
        $this->assertNull($message->deliveryReport);

        $array = $message->toArray();
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('delivery_report', $array);
        $this->assertNull($array['status']);
        $this->assertNull($array['delivery_report']);
    }

    /**
     * @test
     */
    public function it_validates_empty_message_id_for_get_status()
    {
        $this->expectException(\Infoxchange\MessageMedia\Exceptions\ValidationException::class);

        $this->client->getMessageStatus('');
    }

    /**
     * @test
     */
    public function it_validates_empty_message_id_for_cancel()
    {
        $this->expectException(\Infoxchange\MessageMedia\Exceptions\ValidationException::class);

        $this->client->cancelMessage('');
    }

    /**
     * @test
     */
    public function it_creates_credits_response_from_array()
    {
        $data = [
            'credits' => 1000,
            'expiry_date' => '2024-12-31T23:59:59Z',
        ];

        $response = \Infoxchange\MessageMedia\Response\CreditsResponse::fromArray($data);

        $this->assertEquals(1000, $response->credits);
        $this->assertEquals('2024-12-31T23:59:59Z', $response->expiryDate);
    }

    /**
     * @test
     */
    public function it_converts_credits_response_to_array()
    {
        $response = new \Infoxchange\MessageMedia\Response\CreditsResponse();
        $response->credits = 1000;
        $response->expiryDate = '2024-12-31T23:59:59Z';

        $array = $response->toArray();

        $this->assertEquals(1000, $array['credits']);
        $this->assertEquals('2024-12-31T23:59:59Z', $array['expiry_date']);
    }

    /**
     * @test
     */
    public function it_handles_missing_fields_in_credits_response()
    {
        $data = [];

        $response = \Infoxchange\MessageMedia\Response\CreditsResponse::fromArray($data);

        $this->assertNull($response->credits);
        $this->assertNull($response->expiryDate);
    }
}
