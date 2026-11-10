<template>
    <TicketsSettingsHeader :description="$t('Groups of productions that passes, subscriptions and discount codes count for as a whole.')">
        <template v-if="connection.connected" #actions>
            <button type="button" class="ui-button-add" @click="editing = { group: null }">
                <IconPlus class="size-4" />{{ $t('New group') }}
            </button>
        </template>

        <GroupModal v-if="editing" :group="editing.group" :productions="productions" @close="editing = null" />

        <ArtworkBaseDeleteModal v-if="deleting" :title="$t('Delete {name}?', { name: deleting.name })"
                                :description="usedCount(deleting) > 0
                                    ? $t('{count} passes, subscriptions and discount codes will no longer count for the productions in it.', { count: usedCount(deleting) }, usedCount(deleting))
                                    : $t('The productions in it stay as they are.')"
                                @close="deleting = null" @delete="destroy" />

        <NotConnected v-if="!connection.connected" :message="$t('Production groups can be managed once this installation is connected to Artwork-Tickets.')" />

        <div v-else class="rounded-lg border border-border-subtle/70 bg-surface shadow-raised px-3 py-4 sm:px-5 sm:py-5">
            <div v-if="ticketsError" class="mb-4 flex items-start gap-2.5 rounded-md border border-warning-border bg-warning-surface px-3.5 py-3 text-[13px] leading-5 text-text">
                <IconAlertTriangle class="size-4 shrink-0 mt-0.5 text-warning" />
                <span>{{ $t('Production groups could not be loaded from Artwork-Tickets: {message}', { message: ticketsError }) }}</span>
            </div>

            <div v-if="groups.length === 0 && !ticketsError" class="py-10 text-center">
                <p class="font-lexend text-sm font-medium text-text">{{ $t('No groups yet') }}</p>
                <p class="mx-auto mt-1 max-w-md text-[13px] leading-5 text-text-muted">
                    {{ $t('Create “Workshops”, for example, and add every workshop. New passes and discount codes then only pick the group, and a new workshop is added here once.') }}
                </p>
            </div>

            <table v-else-if="groups.length > 0" class="w-full border-collapse text-[13px]">
                <thead>
                    <tr class="text-left text-xs text-text-subtle">
                        <th class="py-2.5 font-medium">{{ $t('Group') }}</th>
                        <th class="py-2.5 font-medium">{{ $t('Productions') }}</th>
                        <th class="py-2.5 font-medium text-right">{{ $t('Used by') }}</th>
                        <th class="py-2.5"><span class="sr-only">{{ $t('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="group in groups" :key="group.id" class="border-t border-border-hairline hover:bg-surface-sunken/60">
                        <td class="py-2.5 pr-3">
                            <button type="button" class="text-left" @click="editing = { group }">
                                <span class="block font-medium text-text hover:underline">{{ group.name }}</span>
                                <span class="block text-xs text-text-subtle">{{ $t('{count} productions', { count: group.eventIds.length }, group.eventIds.length) }}</span>
                            </button>
                        </td>
                        <td class="py-2.5 pr-3 max-w-[420px] truncate text-text-muted" :title="members(group)">
                            {{ group.eventIds.length > 0 ? members(group) : $t('No production yet') }}
                        </td>
                        <td class="py-2.5 text-right" :class="usedCount(group) === 0 && 'text-text-subtle'">
                            {{ usedCount(group) === 0 ? $t('Not used yet') : usedBy(group) }}
                        </td>
                        <td class="py-2.5 pl-3">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button" class="flex size-7 items-center justify-center rounded-md text-text-subtle hover:text-text hover:bg-surface-sunken" :aria-label="$t('Edit')" @click="editing = { group }">
                                    <IconPencil class="size-[15px]" />
                                </button>
                                <button v-if="group.deletable" type="button" class="flex size-7 items-center justify-center rounded-md text-text-subtle hover:text-danger hover:bg-danger-surface"
                                        :aria-label="$t('Delete')" @click="deleting = group">
                                    <IconTrash class="size-[15px]" />
                                </button>
                                <span v-else class="flex size-7 items-center justify-center text-text-subtle"
                                      :title="$t('A sold pass or a redeemed discount code counts on this group, so it stays.')">
                                    <IconLock class="size-[15px]" />
                                </span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </TicketsSettingsHeader>
</template>

<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { IconAlertTriangle, IconLock, IconPencil, IconPlus, IconTrash } from '@tabler/icons-vue'
import TicketsSettingsHeader from '@/Pages/Settings/Tickets/TicketsSettingsHeader.vue'
import NotConnected from '@/Pages/Settings/Tickets/NotConnected.vue'
import GroupModal from '@/Pages/Settings/Tickets/GroupModal.vue'
import ArtworkBaseDeleteModal from '@/Artwork/Modals/ArtworkBaseDeleteModal.vue'

/* Produktionsgruppen: tickets holds them and reads their members live, so a production added here
   counts at once for every pass, subscription and discount code that names the group. */
const props = defineProps({
    connection: { type: Object, required: true },
    groups: { type: Array, default: () => [] },
    productions: { type: Array, default: () => [] },
    ticketsError: { type: String, default: null },
})

const { t } = useI18n()

/** { group } while the dialog is open; group null for a new one. */
const editing = ref(null)
const deleting = ref(null)

const productionTitles = computed(() => new Map(props.productions.map((production) => [production.id, production.title])))

function members(group) {
    return group.eventIds.map((id) => productionTitles.value.get(id)).filter(Boolean).join(', ')
}

function usedCount(group) {
    return group.passTypeCount + group.couponCount
}

function usedBy(group) {
    return [
        group.passTypeCount > 0 ? t('{count} passes and subscriptions', { count: group.passTypeCount }, group.passTypeCount) : null,
        group.couponCount > 0 ? t('{count} discount codes', { count: group.couponCount }, group.couponCount) : null,
    ].filter(Boolean).join(', ')
}

function destroy() {
    router.delete(route('settings.tickets.groups.destroy', deleting.value.id), { preserveScroll: true, onSuccess: () => (deleting.value = null) })
}
</script>
