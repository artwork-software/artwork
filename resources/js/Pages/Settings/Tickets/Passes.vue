<template>
    <TicketsSettingsHeader :description="$t('Multi-visit passes and subscriptions the house sells online.')">
        <template v-if="connection.connected" #actions>
            <button type="button" class="ui-button" @click="openNew('subscription')">
                <IconRepeat class="size-4" />{{ $t('New subscription') }}
            </button>
            <button type="button" class="ui-button-add" @click="openNew('card')">
                <IconPlus class="size-4" />{{ $t('New pass') }}
            </button>
        </template>

        <PassTypeModal v-if="editing" :pass-type="editing.passType" :kind="editing.kind" :productions="productions" :groups="groups" @close="editing = null" />

        <ArtworkBaseDeleteModal v-if="deleting" :title="$t('Delete {name}?', { name: deleting.name })"
                                :description="$t('Nothing of it has been sold yet.')" @close="deleting = null" @delete="destroy" />

        <NotConnected v-if="!connection.connected" :message="$t('Passes and subscriptions can be managed once this installation is connected to Artwork-Tickets.')" />

        <div v-else class="rounded-lg border border-border-subtle/70 bg-surface shadow-raised px-3 py-4 sm:px-5 sm:py-5">
            <div v-if="ticketsError" class="mb-4 flex items-start gap-2.5 rounded-md border border-warning-border bg-warning-surface px-3.5 py-3 text-[13px] leading-5 text-text">
                <IconAlertTriangle class="size-4 shrink-0 mt-0.5 text-warning" />
                <span>{{ $t('Passes and subscriptions could not be loaded from Artwork-Tickets: {message}', { message: ticketsError }) }}</span>
            </div>

            <div v-if="passTypes.length === 0 && !ticketsError" class="py-10 text-center">
                <p class="font-lexend text-sm font-medium text-text">{{ $t('No passes or subscriptions yet') }}</p>
                <p class="mx-auto mt-1 max-w-md text-[13px] leading-5 text-text-muted">
                    {{ $t('Create a multi-visit pass, for example ten visits at full admission, or a subscription that tops itself up every month.') }}
                </p>
            </div>

            <table v-else-if="passTypes.length > 0" class="w-full border-collapse text-[13px]">
                <thead>
                    <tr class="text-left text-xs text-text-subtle">
                        <th class="py-2.5 font-medium">{{ $t('Name') }}</th>
                        <th class="py-2.5 font-medium">{{ $t('Price') }}</th>
                        <th class="py-2.5 font-medium">{{ $t('Uses') }}</th>
                        <th class="py-2.5 font-medium">{{ $t('Valid for') }}</th>
                        <th class="py-2.5 font-medium text-right">{{ $t('Issued') }}</th>
                        <th class="py-2.5 pl-4 font-medium">{{ $t('Status') }}</th>
                        <th class="py-2.5"><span class="sr-only">{{ $t('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="passType in passTypes" :key="passType.id" class="border-t border-border-hairline hover:bg-surface-sunken/60"
                        :class="{ 'text-text-muted': passType.status === 'archived' }">
                        <td class="py-2.5 pr-3">
                            <button type="button" class="text-left" @click="openEdit(passType)">
                                <span class="block font-medium hover:underline" :class="passType.status === 'archived' ? 'text-text-muted' : 'text-text'">{{ passType.name }}</span>
                                <span class="block text-xs text-text-subtle">{{ passType.kind === 'subscription' ? $t('Subscription') : $t('Multi-visit pass') }}</span>
                            </button>
                        </td>
                        <td class="py-2.5 pr-3 tabular-nums">
                            <span v-for="line in priceLines(passType)" :key="line" class="block">{{ line }}</span>
                        </td>
                        <td class="py-2.5 pr-3 tabular-nums">
                            <span class="block">{{ usesLabel(passType) }}</span>
                            <span v-if="passType.maxUsesPerPeriod !== null" class="block text-xs text-text-subtle">{{ paceLabel(passType) }}</span>
                        </td>
                        <td class="py-2.5 pr-3 max-w-[220px] truncate text-text-muted" :title="validFor(passType)">{{ validFor(passType) }}</td>
                        <td class="py-2.5 text-right tabular-nums">{{ passType.issuedCount }}</td>
                        <td class="py-2.5 pl-4">
                            <BaseChip :variant="statusVariant[passType.status]">{{ statusLabel[passType.status] }}</BaseChip>
                        </td>
                        <td class="py-2.5 pl-3">
                            <div class="flex items-center justify-end gap-1.5">
                                <button v-if="passType.status !== 'published'" type="button" class="ui-button-small" :disabled="busy" @click="changeStatus(passType, 'publish')">
                                    {{ passType.status === 'archived' ? $t('Put back on sale') : $t('Put on sale') }}
                                </button>
                                <button v-else type="button" class="ui-button-small" :disabled="busy" @click="changeStatus(passType, 'archive')">
                                    {{ $t('Archive pass') }}
                                </button>
                                <button type="button" class="flex size-7 items-center justify-center rounded-md text-text-subtle hover:text-text hover:bg-surface-sunken" :aria-label="$t('Edit')" @click="openEdit(passType)">
                                    <IconPencil class="size-[15px]" />
                                </button>
                                <button v-if="passType.issuedCount === 0" type="button" class="flex size-7 items-center justify-center rounded-md text-text-subtle hover:text-danger hover:bg-danger-surface"
                                        :aria-label="$t('Delete')" @click="deleting = passType">
                                    <IconTrash class="size-[15px]" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p v-if="passTypes.some((passType) => passType.status === 'archived')" class="mt-3.5 text-xs leading-[18px] text-text-subtle">
                {{ $t('Archived: no longer on sale; issued passes and running subscriptions stay valid.') }}
            </p>
        </div>
    </TicketsSettingsHeader>
</template>

<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { IconAlertTriangle, IconPencil, IconPlus, IconRepeat, IconTrash } from '@tabler/icons-vue'
import TicketsSettingsHeader from '@/Pages/Settings/Tickets/TicketsSettingsHeader.vue'
import NotConnected from '@/Pages/Settings/Tickets/NotConnected.vue'
import PassTypeModal from '@/Pages/Settings/Tickets/PassTypeModal.vue'
import ArtworkBaseDeleteModal from '@/Artwork/Modals/ArtworkBaseDeleteModal.vue'
import BaseChip from '@/Artwork/Chips/BaseChip.vue'
import { formatDate, formatEuro } from '@/Helper/ticketingFormat.js'

const props = defineProps({
    connection: { type: Object, required: true },
    passTypes: { type: Array, default: () => [] },
    productions: { type: Array, default: () => [] },
    groups: { type: Array, default: () => [] },
    ticketsError: { type: String, default: null },
})

const { t } = useI18n()

/* The same words as the passes page of Artwork-Tickets. */
const statusLabel = { draft: t('Draft'), published: t('On sale'), archived: t('Archived') }
const statusVariant = { draft: 'neutral', published: 'success', archived: 'warning' }

const productionTitles = computed(() => new Map(props.productions.map((production) => [production.id, production.title])))
const groupNames = computed(() => new Map(props.groups.map((group) => [group.id, group.name])))

/** { passType, kind } while the dialog is open. */
const editing = ref(null)
const deleting = ref(null)
const busy = ref(false)

function openNew(kind) {
    editing.value = { passType: null, kind }
}

function openEdit(passType) {
    editing.value = { passType, kind: passType.kind }
}

function priceLines(passType) {
    if (passType.kind === 'card') {
        return [formatEuro(passType.price)]
    }

    const lines = []
    if (passType.monthlyPrice !== null) {
        lines.push(t('{price} / month', { price: formatEuro(passType.monthlyPrice) }))
    }
    if (passType.upfrontPrice !== null) {
        lines.push(passType.termMode === 'season'
            ? t('{price} until {date}', { price: formatEuro(passType.upfrontPrice), date: formatDate(passType.seasonEnds) })
            : t('{price} for {months} months', { price: formatEuro(passType.upfrontPrice), months: passType.termMonths }))
    }
    return lines
}

function usesLabel(passType) {
    if (passType.kind === 'card') {
        return passType.uses
    }
    return passType.usesWindow === 'term'
        ? t('{count} / term', { count: passType.uses })
        : t('{count} / month', { count: passType.uses })
}

function paceLabel(passType) {
    const count = passType.maxUsesPerPeriod
    return {
        day: t('at most {count} per day', { count }),
        week: t('at most {count} per week', { count }),
        month: t('at most {count} per month', { count }),
    }[passType.limitPeriod]
}

function titlesOf(eventIds) {
    return eventIds.map((id) => productionTitles.value.get(id)).filter(Boolean)
}

/** Groups first, then the productions picked one by one. */
function validFor(passType) {
    if (passType.allEvents) {
        return t('All productions')
    }
    const names = [...passType.groupIds.map((id) => groupNames.value.get(id)).filter(Boolean), ...titlesOf(passType.eventIds)]
    return names.length > 0 ? names.join(', ') : t('No production yet')
}

const visit = { preserveScroll: true, onStart: () => (busy.value = true), onFinish: () => (busy.value = false) }

function changeStatus(passType, action) {
    router.post(route(`settings.tickets.passes.${action}`, passType.id), {}, visit)
}

function destroy() {
    router.delete(route('settings.tickets.passes.destroy', deleting.value.id), { ...visit, onSuccess: () => (deleting.value = null) })
}
</script>
