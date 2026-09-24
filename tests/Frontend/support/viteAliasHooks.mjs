// Resolve-Hooks für node:test: löst den Vite-Alias "@/" auf resources/js auf und ergänzt die
// Endung bei Deep-Imports ohne exports-Map (z.B. dayjs/plugin/weekOfYear), wie es Vite tut.
import { fileURLToPath, pathToFileURL } from 'node:url';
import path from 'node:path';

const resourcesJs = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../../resources/js');

export async function resolve(specifier, context, nextResolve) {
    if (specifier.startsWith('@/')) {
        return nextResolve(pathToFileURL(path.join(resourcesJs, specifier.slice(2))).href, context);
    }
    try {
        return await nextResolve(specifier, context);
    } catch (error) {
        if (error?.code === 'ERR_MODULE_NOT_FOUND' && !specifier.endsWith('.js')) {
            return nextResolve(`${specifier}.js`, context);
        }
        throw error;
    }
}
