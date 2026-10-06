import assert from 'node:assert/strict';
import { readdirSync, readFileSync } from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

// Vue rendert unbekannte Komponenten-Tags stillschweigend als leeres Custom Element; seit das
// IconLib-Mixin (registrierte früher alle Tabler-Icons) weg ist, fehlten so z. B. alle
// Qualifikations-Icons. Diese Prüfung findet Tags ohne Import/Registrierung.

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../resources/js');

const vueFiles = (dir) => readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) {
        return vueFiles(full);
    }
    return entry.name.endsWith('.vue') ? [full] : [];
});

const BUILTINS = new Set(['Transition', 'TransitionGroup', 'KeepAlive', 'Teleport', 'Suspense', 'Component']);

// Bewusst ausgeschaltet: per v-if auf eine undefinierte Variable nie gerendert (Import auskommentiert)
const KNOWN = new Set(['Layouts/Components/ProjectShiftSidenav.vue:RelevantDatesForShiftPlanningComponent']);

const stripComments = (code) => code.replace(/\/\*[\s\S]*?\*\//g, '').replace(/(^|[^:])\/\/.*$/gm, '$1');

test('every PascalCase tag in a template is imported or registered', () => {
    const missing = [];
    for (const file of vueFiles(root)) {
        const source = readFileSync(file, 'utf8');
        const scripts = stripComments([...source.matchAll(/<script[^>]*>([\s\S]*?)<\/script>/g)].map((m) => m[1]).join('\n'));
        const template = source
            .replace(/<script[^>]*>[\s\S]*?<\/script>/g, '')
            .replace(/<style[^>]*>[\s\S]*?<\/style>/g, '')
            .replace(/<!--[\s\S]*?-->/g, '');
        const self = path.basename(file, '.vue');
        const relative = path.relative(root, file);
        for (const [, tag] of template.matchAll(/<([A-Z][A-Za-z0-9]*)\b/g)) {
            if (BUILTINS.has(tag) || tag === self || KNOWN.has(`${relative}:${tag}`)) {
                continue;
            }
            if (!new RegExp(`\\b${tag}\\b`).test(scripts)) {
                missing.push(`${relative}: <${tag}>`);
            }
        }
    }
    assert.deepEqual([...new Set(missing)], []);
});

test('$t used inside <script setup> is declared there', () => {
    const undeclared = [];
    for (const file of vueFiles(root)) {
        const match = /<script\b[^>]*\bsetup\b[^>]*>([\s\S]*?)<\/script>/.exec(readFileSync(file, 'utf8'));
        if (!match) {
            continue;
        }
        const code = stripComments(match[1]);
        if (!/(^|[^.\w$])\$t\s*\(|typeof \$t/.test(code)) {
            continue;
        }
        if (/[\s,{]\$t\s*=[^=]|\bt:\s*\$t\b|function \$t\b|import[^;]*\$t/.test(code)) {
            continue;
        }
        undeclared.push(path.relative(root, file));
    }
    assert.deepEqual(undeclared, []);
});
