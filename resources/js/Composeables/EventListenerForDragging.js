import { ref } from "vue";

export function EventListenerForDragging(event) {
    const isDragging = ref(false);

    function dispatchEventStart(content = null) {
        window.dispatchEvent(new CustomEvent("dragging-started", {
            detail: { message: "Drag operation started", content: content },
        }));

        isDragging.value = true; // Wert aktualisieren
        console.log("dispatchEventStart: isDragging", isDragging.value, content);
    }

    function dispatchEventEnd(event) {
        window.dispatchEvent(new Event("dragging-ended"));
        isDragging.value = false; // Wert zurücksetzen
        console.log("dispatchEventEnd: isDragging", isDragging.value);
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
