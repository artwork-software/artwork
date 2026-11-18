import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

// Validierungsfehler beim Anlegen/Speichern einer Unterkunft landeten nur in der Konsole;
// Speichern ohne Namen tat sichtbar nichts.
test('accommodation form shows server validation errors at the fields', () => {
    const source = readFileSync(
        new URL('../../resources/js/Pages/Accommodation/Components/UpdateOrCreateAccommodation.vue', import.meta.url),
        'utf8',
    );

    for (const field of ['name', 'email', 'phone_number', 'street', 'zip_code', 'location', 'daily_allowance', 'breakfast_deduction_per_day']) {
        assert.match(source, new RegExp(`v-model="accommodationForm\\.${field}" :error="accommodationForm\\.errors\\.${field}"`), field);
    }
    assert.doesNotMatch(source, /console\.log\('Error (creating|updating|auto-saving) accommodation'\)/);
});
