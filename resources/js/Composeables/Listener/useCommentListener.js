import { stopListeningOnPrivateChannel } from './echoChannel.js';

const ADD_EVENT = '.comment.add';
const DELETE_EVENT = '.comment.delete';

/**
 * Kommentar-Broadcasts auf project.{id} tragen nur Kennungen ({ id, project_id, tab_id }) — der Kanal
 * prüft nur das Projekt-Sichtrecht, nicht die Tab-Sichtbarkeit. Die Komponente lädt ihre Liste über
 * ihren geprüften Endpunkt neu (onChange). Mehrere Ereignisse kurz hintereinander werden gebündelt
 * (entprellt), damit nicht jedes einzelne einen Request auslöst.
 *
 * @param {number} projectId
 * @param {(changes: Array<{type: 'add'|'delete', comment: {id: number, project_id: number|null, tab_id: number|null}}>) => void} onChange
 * @param {{ debounceMs?: number }} options
 */
export function useCommentListener(projectId, onChange, options = {}) {
    const channelName = 'project.' + projectId;
    const debounceMs = options.debounceMs ?? 300;

    let pending = [];
    let timer = null;
    let active = false;

    function flush() {
        timer = null;
        const changes = pending;
        pending = [];
        if (active && changes.length > 0) {
            onChange(changes);
        }
    }

    function schedule(type, data) {
        pending.push({ type, comment: data?.comment ?? {} });
        if (timer !== null) {
            clearTimeout(timer);
        }
        timer = setTimeout(flush, debounceMs);
    }

    const handleAdd = (data) => schedule('add', data);
    const handleDelete = (data) => schedule('delete', data);

    function init() {
        if (active) {
            return;
        }
        active = true;
        Echo.private(channelName)
            .listen(ADD_EVENT, handleAdd)
            .listen(DELETE_EVENT, handleDelete);
    }

    function stop() {
        if (!active) {
            return;
        }
        active = false;
        if (timer !== null) {
            clearTimeout(timer);
            timer = null;
        }
        pending = [];
        stopListeningOnPrivateChannel(channelName, ADD_EVENT, handleAdd);
        stopListeningOnPrivateChannel(channelName, DELETE_EVENT, handleDelete);
    }

    return { init, stop };
}
