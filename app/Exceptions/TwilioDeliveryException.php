<?php

namespace App\Exceptions;

use RuntimeException;

class TwilioDeliveryException extends RuntimeException
{
    public function __construct(
        string $message,
        private ?string $providerCode = null
    ) {
        parent::__construct($message);
    }

    public function providerCode(): ?string
    {
        return $this->providerCode;
    }
}
