import { ref } from "vue";

// Modul-globaler State: Payload der aktuell gezogenen Komponente,
// damit Drop-Zonen schon während des Draggings prüfen können, ob das Ziel gültig ist.
const draggedComponent = ref(null);

export function EventListenerForDragging(event) {
    const isDragging = ref(false);

    function dispatchEventStart(content = null) {
        window.dispatchEvent(new CustomEvent("dragging-started", {
            detail: { message: "Drag operation started", content: content },
        }));

        draggedComponent.value = content;
        isDragging.value = true; // Wert aktualisieren
    }

    function dispatchEventEnd(event) {
        window.dispatchEvent(new Event("dragging-ended"));
        draggedComponent.value = null;
        isDragging.value = false; // Wert zurücksetzen
    }

    function addEventListenerForDraggingStart() {
        const onDragStart = () => {
            isDragging.value = true;
        };
        const onDragEnd = () => {
            isDragging.value = false;
        };

        window.addEventListener("dragging-started", onDragStart);
        window.addEventListener("dragging-ended", onDragEnd);

        return { onDragStart, onDragEnd };
    }

    function removeEventListenerForDraggingStart(listeners) {
        if (listeners) {
            window.removeEventListener("dragging-started", listeners.onDragStart);
            window.removeEventListener("dragging-ended", listeners.onDragEnd);
        }
    }

    return {
        isDragging,
        draggedComponent,
        dispatchEventStart,
        dispatchEventEnd,
        addEventListenerForDraggingStart,
        removeEventListenerForDraggingStart,
    };
}
