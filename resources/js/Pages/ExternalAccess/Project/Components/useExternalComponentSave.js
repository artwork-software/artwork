// Shared save helper for writable external component renderers.
// PATCHes the project-specific component value via the scoped write route.
// Uses the shared (CSRF-configured) axios singleton from bootstrap-external.
import { ref } from 'vue'
import axios from 'axios'

export function useExternalComponentSave(projectId, tabId, componentId) {
    const status = ref(null) // null | 'saving' | 'saved' | 'error'
    let resetTimer = null

    async function save(data) {
        status.value = 'saving'
        try {
            await axios.patch(
                route('external.project.tab.component.update', {
                    project: projectId,
                    tab: tabId,
                    component: componentId,
                }),
                { data },
            )
            status.value = 'saved'
            if (resetTimer) clearTimeout(resetTimer)
            resetTimer = setTimeout(() => { status.value = null }, 2000)
            return true
        } catch (e) {
            status.value = 'error'
            return false
        }
    }

    return { status, save }
}
