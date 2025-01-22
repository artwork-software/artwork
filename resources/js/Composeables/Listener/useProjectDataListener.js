import { router } from '@inertiajs/vue3';

export function useProjectDataListener(componentData, projectId) {
    function init() {
        Echo.private('project.' + projectId)
            .listen('.data.updated', (data) => {
                const updatedData = data.data;
                const projectData = componentData.project_value;
                if (projectData !== null) {
                    if (
                        projectData.id === updatedData.id &&
                        projectData.project_id === updatedData.project_id &&
                        projectData.component_id === updatedData.component_id
                    ) {
                        Object.assign(componentData.project_value, updatedData);
                    }
                } else {
                    componentData.project_value = updatedData
                }
            })
    }

    return { init };
}