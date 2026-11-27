<template>
    <TicketsSettingsHeader :description="$t('Discounts the shop applies by itself: an early-bird price, a discount from several dates, a package.')">
        <template v-if="connection.connected" #actions>
            <button type="button" class="ui-button-add" @click="editing = { discount: null }">
                <IconPlus class="size-4" />{{ $t('New discount') }}
            </button>
        </template>

        <DiscountModal v-if="editing" :discount="editing.discount" :package-for="editing.packageFor" :discounts="discounts" :productions="productions"
                       :groups="groups" @close="editing = null" />

        <ArtworkBaseDeleteModal v-if="deleting" :title="$t('Delete {name}?', { name: deleting.name })"
                                :description="deleteDescription" @close="deleting = null" @delete="destroy" />

        <NotConnected v-if="!connection.connected" :message="$t('Discounts can be managed once this installation is connected to Artwork-Tickets.')" />

        <div v-else class="rounded-lg border border-border-subtle/70 bg-surface shadow-raised px-3 py-4 sm:px-5 sm:py-5">
            <div v-if="ticketsError" class="mb-4 flex items-start gap-2.5 rounded-md border border-warning-border bg-warning-surface px-3.5 py-3 text-[13px] leading-5 text-text">
                <IconAlertTriangle class="size-4 shrink-0 mt-0.5 text-warning" />
                <span>{{ $t('Discounts could not be loaded from Artwork-Tickets: {message}', { message: ticketsError }) }}</span>
            </div>

            <div v-if="discounts.length === 0 && !ticketsError" class="py-10 text-center">
                <p class="font-lexend text-sm font-medium text-text">{{ $t('No discounts yet') }}</p>
                <p class="mx-auto mt-1 max-w-md text-[13px] leading-5 text-text-muted">
                    {{ $t('An early-bird price, for example: 15 % off every workshop until 1 April. The shop shows the lowered price with the regular one struck through, and switches back by itself.') }}
                </p>
            </div>

            <table v-else-if="discounts.length > 0" class="w-full border-collapse text-[13px]">
                <thead>
                    <tr class="text-left text-xs text-text-subtle">
                        <th class="py-2.5 font-medium">{{ $t('Name') }}</th>
                        <th class="py-2.5 font-medium">{{ $t('Discount') }}</th>
                        <th class="py-2.5 font-medium">{{ $t('Valid for') }}</th>
                        <th class="py-2.5 font-medium">{{ $t('Period') }}</th>
                        <th class="py-2.5"><span class="sr-only">{{ $t('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="discount in discounts" :key="discount.id" class="border-t border-border-hairline hover:bg-surface-sunken/60">
                        <td class="py-2.5 pr-3">
                            <button type="button" class="text-left" @click="editing = { discount }">
                                <span class="block font-medium text-text hover:underline">{{ discount.name }}</span>
                                <span v-if="discount.status === 'paused'" class="block text-xs text-text-subtle">{{ $t('Paused') }}</span>
                                <span v-else-if="discount.period === 'ended'" class="block text-xs text-text-subtle">{{ $t('Expired') }}</span>
                            </button>
                        </td>
                        <td class="py-2.5 pr-3 tabular-nums">{{ discountWorth(discount, t) }}</td>
                        <td class="py-2.5 pr-3 max-w-[320px] truncate text-text-muted" :title="scope(discount)">{{ scope(discount) }}</td>
                        <td class="py-2.5 pr-3 tabular-nums text-text-muted">{{ discountPeriod(discount, t) }}</td>
                        <td class="py-2.5 pl-3">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button" class="flex size-7 items-center justify-center rounded-md text-text-subtle hover:text-text hover:bg-surface-sunken" :aria-label="$t('Edit')" @click="editing = { discount }">
                                    <IconPencil class="size-[15px]" />
                                </button>
                                <button type="button" class="flex size-7 items-center justify-center rounded-md text-text-subtle hover:text-danger hover:bg-danger-surface"
                                        :aria-label="$t('Delete')" @click="deleting = discount">
                                    <IconTrash class="size-[15px]" />
                                </button>
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
import { IconAlertTriangle, IconPencil, IconPlus, IconTrash } from '@tabler/icons-vue'
import TicketsSettingsHeader from '@/Pages/Settings/Tickets/TicketsSettingsHeader.vue'
import NotConnected from '@/Pages/Settings/Tickets/NotConnected.vue'
import DiscountModal from '@/Pages/Settings/Tickets/DiscountModal.vue'
import ArtworkBaseDeleteModal from '@/Artwork/Modals/ArtworkBaseDeleteModal.vue'
import { discountPeriod, discountWorth, validFor } from '@/Pages/Settings/Tickets/discounts.js'
import { packageOnlyWarning, strandedProductions } from '@/Pages/Settings/Tickets/packageReach.js'

/* Automatic discounts: Rabattcodes without a code that tickets applies by itself inside their
   period — per ticket (Frühbucher), from a number of dates in one booking (Staffel), or as the price
   of that many dates together (package). */
const props = defineProps({
    connection: { type: Object, required: true },
    discounts: { type: Array, default: () => [] },
    productions: { type: Array, default: () => [] },
    groups: { type: Array, default: () => [] },
    ticketsError: { type: String, default: null },
    /** Opens a new package for this production, from its project's ticketing tab. */
    packageFor: { type: String, default: null },
    /** Opens this discount, picked in a project's ticketing tab. */
    editDiscount: { type: String, default: null },
})

const { t } = useI18n()

/** { discount, packageFor } while the dialog is open; discount null for a new one. */
const editing = ref(openingDialog())
const deleting = ref(null)

function openingDialog() {
    if (props.packageFor) return { discount: null, packageFor: props.packageFor }
    const discount = props.discounts.find((candidate) => candidate.id === props.editDiscount)
    return discount ? { discount } : null
}

const scope = (discount) => validFor(discount, props.productions, props.groups, t)

/* Deleting the last package of a production sold only in packages takes its dates out of online sale. */
const deleteDescription = computed(() => {
    const stranded = strandedProductions(props.discounts, props.productions, props.groups, deleting.value, null)
    const kept = t('Tickets already sold keep their price.')
    return stranded.length === 0 ? kept : `${packageOnlyWarning(stranded, t)} ${kept}`
})

function destroy() {
    router.delete(route('settings.tickets.discounts.destroy', deleting.value.id), { preserveScroll: true, onSuccess: () => (deleting.value = null) })
}
</script>
