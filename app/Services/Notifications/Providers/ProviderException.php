<?php

namespace App\Services\Notifications\Providers;

use RuntimeException;

class ProviderException extends RuntimeException
{

    private readonly bool $temporary;

    public function __construct(string $message, $temporary = false)
    {
        parent::__construct($message);
        $this->temporary = $temporary;
    }

    public function isTemporary(): bool
    {
        return $this->temporary;
    }
}
