<?php

namespace App\Events\Authentication;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AuthenticationFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly string $loginFingerprint) {}
}
