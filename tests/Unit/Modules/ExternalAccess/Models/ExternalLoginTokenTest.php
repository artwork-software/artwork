<?php

namespace Tests\Unit\Modules\ExternalAccess\Models;

use Artwork\Modules\ExternalAccess\Models\ExternalLoginToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExternalLoginTokenTest extends TestCase
{
    #[Test]
    public function is_valid_returns_true_for_fresh_unused_token(): void
    {
        $token = ExternalLoginToken::factory()->valid()->create();

        $this->assertTrue($token->isValid());
    }

    #[Test]
    public function is_valid_returns_false_for_expired_token(): void
    {
        $token = ExternalLoginToken::factory()->expired()->create();

        $this->assertFalse($token->isValid());
    }

    #[Test]
    public function is_valid_returns_false_for_used_token(): void
    {
        $token = ExternalLoginToken::factory()->valid()->used()->create();

        $this->assertFalse($token->isValid());
    }

    #[Test]
    public function token_hash_is_hidden_from_array_output(): void
    {
        $token = ExternalLoginToken::factory()->valid()->create();

        $this->assertArrayNotHasKey('token_hash', $token->toArray());
    }
}
