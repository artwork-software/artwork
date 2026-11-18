import assert from 'node:assert/strict';
import { existsSync, readdirSync, readFileSync } from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

// Echo setzt vor Ereignisnamen ohne führenden Punkt den Namensraum App\Events. Für Broadcasts aus
// artwork/Modules (Klassenname = Artwork\Modules\…) oder mit broadcastAs() kam ein solcher Listener
// nie an (so waren Teamverwaltung und Belegungs-Reload stumm). Ohne Punkt sind daher nur
// Klassen aus app/Events erlaubt.

const projectRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const jsRoot = path.join(projectRoot, 'resources/js');

const sourceFiles = (dir) => readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) {
        return sourceFiles(full);
    }
    return /\.(vue|js|ts)$/.test(entry.name) ? [full] : [];
});

test('echo listeners without a leading dot only use App\\Events class names', () => {
    const wrong = [];
    for (const file of sourceFiles(jsRoot)) {
        const source = readFileSync(file, 'utf8');
        // Kanal-Methoden .listen('Name' …) / .stopListening('Name' …); eigene Helfer listen(kanal, '.x') nicht
        for (const match of source.matchAll(/\.(?:listen|stopListening)\(\s*['"`]([^'"`]+)['"`]/g)) {
            const eventName = match[1];
            if (eventName.startsWith('.')) {
                continue;
            }
            if (!existsSync(path.join(projectRoot, 'app/Events', `${eventName.replaceAll('.', '/')}.php`))) {
                wrong.push(`${path.relative(jsRoot, file)}: '${eventName}'`);
            }
        }
    }

    assert.deepEqual(wrong, [], 'Ereignisname ohne Punkt, aber keine Klasse in app/Events: mit broadcastAs() und \'.name\' hören');
});

test('department updates are heard under their broadcastAs name and cleaned up', () => {
    const page = readFileSync(path.join(jsRoot, 'Pages/Departments/DepartmentManagement.vue'), 'utf8');
    const event = readFileSync(path.join(projectRoot, 'artwork/Modules/Department/Events/DepartmentUpdated.php'), 'utf8');

    assert.match(event, /return 'departments\.updated';/);
    assert.match(page, /\.listen\('\.departments\.updated', reloadDepartments\)/);
    assert.match(page, /stopListeningOnPrivateChannel\('departments', '\.departments\.updated', reloadDepartments\)/);
});
