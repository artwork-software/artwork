import { router } from '@inertiajs/vue3'

export function useCrmSettingsListener() {
    function init() {
        Echo.private('crm.settings')
            .listen('.crm.settings.changed', () => {
                router.reload({ only: ['propertyGroups', 'contactTypes'] })
            })
    }

    function destroy() {
        Echo.leave('crm.settings')
    }

    return { init, destroy }
}
