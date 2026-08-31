<?php

namespace Tests\Unit\Modules\ExternalUserManagement;

use Artwork\Modules\ExternalUserManagement\Support\LdapIdentifier;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LdapIdentifierTest extends TestCase
{
    // Little-Endian-Binaerform von 6f8dd8c3-1b4c-4d4f-9a2b-8c1d2e3f4a5b, wie sie
    // Active Directory fuer objectGUID ausliefert.
    private const string BINARY_GUID_HEX = 'c3d88d6f4c1b4f4d9a2b8c1d2e3f4a5b';

    private const string STRING_GUID = '6f8dd8c3-1b4c-4d4f-9a2b-8c1d2e3f4a5b';

    private const string BINARY_SID_HEX = '010500000000000515000000dcf4dc3b833d2b46828ba62800020000';

    private const string STRING_SID = 'S-1-5-21-1004336348-1177238915-682003330-512';

    #[Test]
    public function theBinaryActiveDirectoryGuidBecomesItsCanonicalStringForm(): void
    {
        $this->assertSame(
            self::STRING_GUID,
            LdapIdentifier::normalize('objectGUID', hex2bin(self::BINARY_GUID_HEX))
        );
    }

    #[Test]
    public function theConversionIsIdempotentAndCaseInsensitive(): void
    {
        // Der Identifier laeuft auf mehreren Pfaden (Sync, Preview, Login) durch die
        // Normalisierung – eine zweite Konvertierung darf den Wert nicht veraendern.
        $this->assertSame(self::STRING_GUID, LdapIdentifier::normalize('objectGUID', self::STRING_GUID));
        $this->assertSame(self::STRING_GUID, LdapIdentifier::normalize('objectguid', self::STRING_GUID));
        $this->assertSame(self::STRING_SID, LdapIdentifier::normalize('objectsid', self::STRING_SID));
    }

    #[Test]
    public function theBinaryObjectSidBecomesItsCanonicalStringForm(): void
    {
        $this->assertSame(
            self::STRING_SID,
            LdapIdentifier::normalize('objectSid', hex2bin(self::BINARY_SID_HEX))
        );
    }

    #[Test]
    public function textualIdentifierAttributesArePassedThroughUnchanged(): void
    {
        $this->assertSame('jdoe', LdapIdentifier::normalize('sAMAccountName', 'jdoe'));
        $this->assertSame(
            'b8c1f0aa-1234-4321-abcd-000000000001',
            LdapIdentifier::normalize('entryUUID', 'b8c1f0aa-1234-4321-abcd-000000000001')
        );
    }

    #[Test]
    public function emptyValuesBecomeNull(): void
    {
        $this->assertNull(LdapIdentifier::normalize('objectGUID', null));
        $this->assertNull(LdapIdentifier::normalize('objectGUID', ''));
    }

    #[Test]
    public function aValueThatIsNeitherAGuidNorSixteenBytesIsNotMangled(): void
    {
        // Guid::binaryGuidToString() prueft die Laenge nicht und wuerde aus jedem
        // String eine plausibel aussehende GUID basteln – der Shape-Guard verhindert das.
        $this->assertSame('not-a-guid', LdapIdentifier::normalize('objectGUID', 'not-a-guid'));
    }

    #[Test]
    public function anyRemainingBinaryValueIsHexEncoded(): void
    {
        $this->assertSame('fffe00', LdapIdentifier::normalize('someBinaryAttribute', "\xff\xfe\x00"));
        $this->assertSame('fffe00', LdapIdentifier::safeString("\xff\xfe\x00"));
        $this->assertNull(LdapIdentifier::safeString(null));
        $this->assertSame('Müller', LdapIdentifier::safeString('Müller'));
    }

    #[Test]
    public function theNormalizedIdentifierSurvivesJsonEncoding(): void
    {
        // Regressionstest fuer den 500er im Verbindungstest: json_encode() scheiterte
        // am rohen Binaerwert, und zwar ausserhalb jedes try/catch im Controller.
        $payload = ['users' => [['identifier' => LdapIdentifier::normalize(
            'objectGUID',
            hex2bin(self::BINARY_GUID_HEX)
        )]]];

        $json = json_encode($payload, JSON_THROW_ON_ERROR);

        $this->assertStringContainsString(self::STRING_GUID, $json);
    }

    #[Test]
    public function theGuidFilterValueIsEscapedHexForActiveDirectory(): void
    {
        // AD vergleicht objectGUID binaer – im Filter muss \c3\d8\8d\6f… stehen.
        $this->assertSame(
            '\\' . implode('\\', str_split(self::BINARY_GUID_HEX, 2)),
            LdapIdentifier::toFilterValue('objectGUID', self::STRING_GUID)
        );
    }

    #[Test]
    public function textualAttributesGetNoSpecialFilterValue(): void
    {
        // null bedeutet: normales where() mit dem unveraenderten Wert.
        $this->assertNull(LdapIdentifier::toFilterValue('sAMAccountName', 'jdoe'));
        $this->assertNull(LdapIdentifier::toFilterValue('entryUUID', self::STRING_GUID));
        $this->assertNull(LdapIdentifier::toFilterValue('objectGUID', 'not-a-guid'));
    }
}
