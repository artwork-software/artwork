import { ref } from "vue";

export function EventListenerForDragging() {
    const isDragging = ref(false);

    function dispatchEventStart() {
        window.dispatchEvent(new CustomEvent("dragging-started", {
            detail: { message: "Drag operation started" }
        }));
        isDragging.value = true; // Wert aktualisieren
    }

    function dispatchEventEnd() {
        window.dispatchEvent(new Event("dragging-ended"));
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
        dispatchEventStart,
        dispatchEventEnd,
        addEventListenerForDraggingStart,
        removeEventListenerForDraggingStart,
    };
}
