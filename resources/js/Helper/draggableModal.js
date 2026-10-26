/**
 * Modal am Kopf verschiebbar machen. mousemove/mouseup hängen nur während des Ziehens am document –
 * vorher kamen bei jedem Öffnen zwei Listener dazu, die nie entfernt wurden (Speicher wuchs, jede
 * Mausbewegung lief durch alle toten Handler).
 *
 * @returns {() => void} entfernt alle Listener
 */
export function attachModalDrag(container, dragHandle, doc = globalThis.document) {
    if (!container || !dragHandle || !doc) {
        return () => {};
    }

    const raf = globalThis.requestAnimationFrame?.bind(globalThis) ?? ((callback) => setTimeout(callback, 0));
    const cancelRaf = globalThis.cancelAnimationFrame?.bind(globalThis) ?? clearTimeout;
    let offsetX = 0;
    let offsetY = 0;
    let animationFrameId = null;

    const onMouseMove = (event) => {
        if (animationFrameId !== null) {
            cancelRaf(animationFrameId);
        }
        animationFrameId = raf(() => {
            animationFrameId = null;
            container.style.position = 'absolute';
            container.style.left = `${event.clientX - offsetX}px`;
            container.style.top = `${event.clientY - offsetY}px`;
            // Prevent text selection while dragging
            doc.body?.classList.add('select-none');
        });
    };

    const stopDragging = () => {
        doc.removeEventListener('mousemove', onMouseMove);
        doc.removeEventListener('mouseup', stopDragging);
        if (animationFrameId !== null) {
            cancelRaf(animationFrameId);
            animationFrameId = null;
        }
        doc.body?.classList.remove('select-none');
    };

    const onMouseDown = (event) => {
        offsetX = event.clientX - container.offsetLeft;
        offsetY = event.clientY - container.offsetTop;
        doc.addEventListener('mousemove', onMouseMove);
        doc.addEventListener('mouseup', stopDragging);
    };

    dragHandle.addEventListener('mousedown', onMouseDown);

    return () => {
        dragHandle.removeEventListener('mousedown', onMouseDown);
        stopDragging();
    };
}
