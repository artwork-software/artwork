// Gemeinsame Spalten-Maße der Bulk-Ansicht. Header (BulkHeader) und Zeilen
// (BulkSingleEvent) sind GETRENNTE Flex-Container — beide müssen mit identischen
// flex-/min-width-Werten rechnen, sonst laufen die Spalten auseinander.
import { usePage } from "@inertiajs/vue3";

// Konfigurierte Breite = Mindestbreite; Restplatz wächst proportional dazu mit
// (flex-grow = px), damit die vom User gewählten Proportionen erhalten bleiben.
export const getBulkColumnSize = (column) => {
    const px = parseInt(usePage().props.auth.user.bulk_column_size[column]);
    return {
        flex: `${px} 0 ${px}px`,
        minWidth: px + 'px'
    };
};
