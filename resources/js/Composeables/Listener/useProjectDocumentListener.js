import { router } from '@inertiajs/vue3';

export function useProjectDocumentListener(documentList, projectId) {
    function init() {
        Echo.private('project.' + projectId)
            .listen('.document.add', (data) => {
                const newDocument = data.document;

                // Überprüfen, ob das Dokument bereits existiert
                const existingDocumentIndex = documentList.findIndex(
                    (doc) => doc.id === newDocument.id
                );

                if (existingDocumentIndex !== -1) {
                    // Entfernen des bestehenden Dokuments aus der Liste
                    documentList.splice(existingDocumentIndex, 1);
                }

                // Neues Dokument an den Anfang der Liste einfügen
                documentList.unshift(newDocument);
            })
            .listen('.document.delete', (data) => {
                const deletedDocumentId = data.document.id;

                // Dokument aus der Liste entfernen
                const documentIndex = documentList.findIndex(
                    (doc) => doc.id === deletedDocumentId
                );

                if (documentIndex !== -1) {
                    documentList.splice(documentIndex, 1);
                    console.log('Document deleted: ', deletedDocumentId);
                } else {
                    console.warn('Document to delete not found: ', deletedDocumentId);
                }
            })
    }

    return { init };
}