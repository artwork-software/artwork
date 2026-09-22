<template>
    <TicketsSettingsHeader :description="$t('Who stands behind the ticket house and where artwork tickets pays out.')">
        <template #actions>
            <button v-if="connection.connected && billing" type="button" class="ui-button-add relative" :disabled="!canSave" @click="submit">
                <IconDeviceFloppy class="size-4" />{{ form.processing ? $t('Saving…') : $t('Save in artwork tickets') }}
                <span v-if="form.isDirty" class="absolute -top-1 -right-1 size-2.5 rounded-full bg-warning ring-2 ring-surface"></span>
            </button>
        </template>

        <PendingChangesBar :visible="connection.connected && form.isDirty" :message="$t('Changes not yet in artwork tickets')" :submit-label="$t('Save in artwork tickets')"
                           :icon="IconDeviceFloppy" :processing-label="$t('Saving…')" :can-submit="canSave" :processing="form.processing" @discard="form.reset()" @submit="submit" />

        <NotConnected v-if="!connection.connected" :message="$t('The details can be filled in once this installation is connected to artwork tickets.')" />

        <div v-else class="rounded-lg border border-border-subtle/70 bg-surface shadow-raised px-3 py-4 sm:px-5 sm:py-5">
            <p class="mb-4 max-w-[760px] text-[13px] leading-5 text-text-subtle">
                {{ $t('artwork tickets needs this for the credit notes on its fee and for payouts. Everything is stored there, not here; the same fields are in the settings of artwork tickets.') }}
            </p>

            <div v-if="ticketsError" class="mb-4 flex items-start gap-2.5 rounded-md border border-warning-border bg-warning-surface px-3.5 py-3 text-[13px] leading-5 text-text">
                <IconAlertTriangle class="size-4 shrink-0 mt-0.5 text-warning" />
                <span>{{ $t('The details could not be loaded from artwork tickets: {message}', { message: ticketsError }) }}</span>
            </div>

            <template v-if="billing">
                <div class="mb-5 flex flex-wrap items-center gap-2">
                    <BaseChip :variant="billing.legal_complete ? 'success' : 'warning'">{{ $t('Legal details') }} · {{ billing.legal_complete ? $t('complete') : $t('incomplete') }}</BaseChip>
                    <BaseChip :variant="billing.bank_complete ? 'success' : 'warning'">{{ $t('Bank account') }} · {{ billing.bank_complete ? $t('complete') : $t('incomplete') }}</BaseChip>
                </div>

                <div v-if="!billing.legal_complete || !billing.bank_complete" class="mb-5 flex items-start gap-2.5 rounded-md border border-warning-border bg-warning-surface px-3.5 py-3 text-[13px] leading-5 text-text max-w-[900px]">
                    <IconAlertTriangle class="size-4 shrink-0 mt-0.5 text-warning" />
                    <span>{{ $t('Until both blocks are complete, no date can be released for sale and nothing is paid out.') }}</span>
                </div>

                <div class="max-w-[900px]">
                    <BillingFieldsEditor :billing="form" :countries="countries" :legal-forms="legalForms" :iban-last4="billing.iban_last4" />
                </div>

                <ul v-if="Object.keys(form.errors).length" class="mt-4 rounded-md border border-danger-border bg-danger-surface p-3 text-[13px] text-danger">
                    <li v-for="(message, key) in form.errors" :key="key">{{ message }}</li>
                </ul>

                <p class="mt-5 text-xs text-text-subtle">{{ $t('Fields can stay empty for now; a saved IBAN is never shown again.') }}</p>
            </template>
        </div>
    </TicketsSettingsHeader>
</template>

<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { IconAlertTriangle, IconDeviceFloppy } from '@tabler/icons-vue'
import TicketsSettingsHeader from '@/Pages/Settings/Tickets/TicketsSettingsHeader.vue'
import NotConnected from '@/Pages/Settings/Tickets/NotConnected.vue'
import BillingFieldsEditor from '@/Pages/Settings/Tickets/BillingFieldsEditor.vue'
import BaseChip from '@/Artwork/Chips/BaseChip.vue'
import PendingChangesBar from '@/Pages/Settings/Tickets/PendingChangesBar.vue'
import { isValidIban } from '@/Pages/Settings/Tickets/drafts.js'

const props = defineProps({
    connection: { type: Object, required: true },
    /** null while tickets did not answer — the form then has nothing to edit. */
    billing: { type: Object, default: null },
    countries: { type: Array, required: true },
    legalForms: { type: Array, required: true },
    ticketsError: { type: String, default: null },
})

const form = useForm({ ...(props.billing?.profile ?? {}) })

/* Half-filled is fine; only a typed IBAN has to be a real one. */
const canSave = computed(() => !form.processing && form.isDirty && (form.iban === '' || isValidIban(form.iban)))

function submit() {
    form.put(route('settings.tickets.billing.save'), { preserveScroll: true, onSuccess: () => { form.iban = ''; form.defaults(form.data()) } })
}
</script>
