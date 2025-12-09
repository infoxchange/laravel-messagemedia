<?php

namespace IxaDevStuff\MessageMedia\Tests\Unit;

use PHPUnit\Framework\TestCase;
use IxaDevStuff\MessageMedia\Client;
use IxaDevStuff\MessageMedia\Message;
use IxaDevStuff\MessageMedia\Request\SendMessagesRequest;
use IxaDevStuff\MessageMedia\Exceptions\ValidationException;

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
}
