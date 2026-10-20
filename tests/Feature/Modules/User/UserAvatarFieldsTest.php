<?php

namespace Tests\Feature\Modules\User;

use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * User::AVATAR_FIELDS ist die gemeinsame Feldliste für Personen, die nur als Avatar mit
 * Namens-Tooltip erscheinen (Projektübersicht, Checklisten, Räume, Interne Vorgänge, Schichteinstellungen …).
 */
final class UserAvatarFieldsTest extends FeatureTestCase
{
    #[Test]
    public function avatar_fields_serialize_exactly_name_and_photo(): void
    {
        $user = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);

        $serialized = $user->setVisible(User::AVATAR_FIELDS)->toArray();

        $this->assertEqualsCanonicalizing(User::AVATAR_FIELDS, array_keys($serialized));
        $this->assertSame($user->id, $serialized['id']);
        $this->assertSame('Ada Lovelace', $serialized['full_name']);
        $this->assertNotEmpty($serialized['profile_photo_url']);
        $this->assertArrayNotHasKey('email', $serialized);
    }
}
