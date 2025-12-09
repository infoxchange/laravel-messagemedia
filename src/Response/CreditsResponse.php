<?php

namespace Infoxchange\MessageMedia\Response;

class CreditsResponse
{
    /** @var int|null */
    public $credits;

    /** @var string|null */
    public $expiryDate;

    /**
     * Create CreditsResponse from API response array
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $response = new self();

        $response->credits = $data['credits'] ?? null;
        $response->expiryDate = $data['expiry_date'] ?? null;

        return $response;
    }

    /**
     * Convert CreditsResponse to array
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'credits' => $this->credits,
            'expiry_date' => $this->expiryDate,
        ];
    }
}
