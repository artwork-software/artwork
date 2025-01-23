import { router } from '@inertiajs/vue3';

export function useShiftRelevantEventListener(newShiftRelevantEvents, projectId) {
    function init() {
        Echo.private('project.' + projectId)
            .listen('.shift.relevant.event.created', (data) => {
                console.log(data);
            })
    }

    return { init };
}