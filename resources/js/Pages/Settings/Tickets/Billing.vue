<template>
    <TicketsSettingsHeader :description="$t('Who stands behind the house, what its buyers read and where it is paid out.')">
        <template #actions>
            <button v-if="connection.connected && billing" type="button" class="ui-button-add relative" :disabled="!canSave" @click="submit">
                <IconDeviceFloppy class="size-4" />{{ form.processing ? $t('Saving…') : $t('Save in Artwork-Tickets') }}
                <span v-if="form.isDirty" class="absolute -top-1 -right-1 size-2.5 rounded-full bg-warning ring-2 ring-surface"></span>
            </button>
        </template>

        <PendingChangesBar :visible="connection.connected && form.isDirty" :message="$t('Changes not yet in Artwork-Tickets')" :submit-label="$t('Save in Artwork-Tickets')"
                           :icon="IconDeviceFloppy" :processing-label="$t('Saving…')" :can-submit="canSave" :processing="form.processing" @discard="form.reset()" @submit="submit" />

        <NotConnected v-if="!connection.connected" :message="$t('The details can be filled in once this installation is connected to Artwork-Tickets.')" />

        <div v-else class="rounded-lg border border-border-subtle/70 bg-surface shadow-raised px-3 py-4 sm:px-5 sm:py-5">
            <p class="mb-4 max-w-[760px] text-[13px] leading-5 text-text-subtle">
                {{ $t('Artwork-Tickets needs this for the credit notes on its fee and for payouts. Everything is stored there, not here; the same fields are in the settings of Artwork-Tickets.') }}
            </p>

            <div v-if="ticketsError" class="mb-4 flex items-start gap-2.5 rounded-md border border-warning-border bg-warning-surface px-3.5 py-3 text-[13px] leading-5 text-text">
                <IconAlertTriangle class="size-4 shrink-0 mt-0.5 text-warning" />
                <span>{{ $t('The details could not be loaded from Artwork-Tickets: {message}', { message: ticketsError }) }}</span>
            </div>

            <template v-if="billing">
                <div v-if="!billing.platform_terms.accepted" class="mb-5 max-w-[900px] rounded-md border border-warning-border bg-warning-surface px-3.5 py-3">
                    <span class="font-lexend mb-2 flex items-center gap-2 text-[13px] font-medium text-text">
                        <IconAlertTriangle class="size-4 shrink-0 text-warning" />{{ $t('The house has not accepted the current terms of Artwork-Tickets yet') }}
                    </span>
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <PlatformTermsConsent v-model="termsChecked" :terms-url="billing.platform_terms.terms_url" :dpa-url="billing.platform_terms.dpa_url" />
                        <button type="button" class="ui-button-add" :disabled="!termsChecked || termsForm.processing" @click="acceptTerms">{{ $t('Accept') }}</button>
                    </div>
                </div>

                <div class="mb-5 flex flex-wrap items-center gap-2">
                    <BaseChip :variant="billing.legal_complete ? 'success' : 'warning'">{{ $t('Legal details') }} · {{ billing.legal_complete ? $t('complete') : $t('incomplete') }}</BaseChip>
                    <BaseChip :variant="billing.shop_legal_complete ? 'success' : 'warning'">{{ $t('Legal pages') }} · {{ billing.shop_legal_complete ? $t('complete') : $t('incomplete') }}</BaseChip>
                    <BaseChip :variant="billing.payout_account === 'verified' ? 'success' : 'warning'">{{ $t('Payout account') }} · {{ payoutLabel }}</BaseChip>
                </div>

                <div v-if="!billing.legal_complete || !billing.shop_legal_complete || billing.payout_account !== 'verified'" class="mb-5 flex items-start gap-2.5 rounded-md border border-warning-border bg-warning-surface px-3.5 py-3 text-[13px] leading-5 text-text max-w-[900px]">
                    <IconAlertTriangle class="size-4 shrink-0 mt-0.5 text-warning" />
                    <span>{{ $t('Until everything here is complete and the payment provider has verified the house, no date can be released for sale.') }}</span>
                </div>

                <div class="max-w-[900px]">
                    <BillingFieldsEditor :billing="form" :countries="countries" :legal-forms="legalForms" :files="billing.shop_legal_files"
                                         :missing="missingDetails(form, billing.shop_legal_files)" @remove-file="removeFile" />
                </div>

                <ul v-if="Object.keys(form.errors).length" class="mt-4 rounded-md border border-danger-border bg-danger-surface p-3 text-[13px] text-danger">
                    <li v-for="(message, key) in form.errors" :key="key">{{ message }}</li>
                </ul>

                <p class="mt-5 text-xs text-text-subtle">{{ $t('Fields can stay empty for now.') }}</p>

                <div class="mt-8 max-w-[900px] border-t border-border-hairline pt-6">
                    <h3 class="font-lexend text-[13px] font-semibold text-text mb-1">{{ $t('Payout account') }}</h3>
                    <p class="text-[13px] leading-5 text-text-subtle mb-4">{{ $t('So that the house can sell tickets, the payment provider confirms who stands behind it and takes the bank account. No account of its own with the payment provider is needed.') }}</p>
                    <PayoutAccount :state="billing.payout_account" :stripe-key="billing.stripe_key" />
                </div>
            </template>
        </div>
    </TicketsSettingsHeader>
</template>

<script setup>
import { computed, ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { IconAlertTriangle, IconDeviceFloppy } from '@tabler/icons-vue'
import TicketsSettingsHeader from '@/Pages/Settings/Tickets/TicketsSettingsHeader.vue'
import NotConnected from '@/Pages/Settings/Tickets/NotConnected.vue'
import BillingFieldsEditor from '@/Pages/Settings/Tickets/BillingFieldsEditor.vue'
import BaseChip from '@/Artwork/Chips/BaseChip.vue'
import PendingChangesBar from '@/Pages/Settings/Tickets/PendingChangesBar.vue'
import PayoutAccount from '@/Pages/Settings/Tickets/PayoutAccount.vue'
import PlatformTermsConsent from '@/Pages/Settings/Tickets/PlatformTermsConsent.vue'
import { missingDetails, noLegalFiles } from '@/Pages/Settings/Tickets/drafts.js'

const props = defineProps({
    connection: { type: Object, required: true },
    /** null while tickets did not answer — the form then has nothing to edit. */
    billing: { type: Object, default: null },
    countries: { type: Array, required: true },
    legalForms: { type: Array, required: true },
    ticketsError: { type: String, default: null },
})

const { t } = useI18n()

const form = useForm({ ...(props.billing?.profile ?? {}), ...noLegalFiles() })

/* Half-filled is fine, as in the settings of Artwork-Tickets. */
const canSave = computed(() => !form.processing && form.isDirty)

const payoutLabel = computed(() => ({ open: t('incomplete'), review: t('in review'), verified: t('verified') }[props.billing.payout_account]))

const termsChecked = ref(false)
const termsForm = useForm({})

function acceptTerms() {
    termsForm.post(route('settings.tickets.billing.platform-terms'), { preserveScroll: true })
}

/* Picked PDFs travel with the save; once tickets has them they are its stored files. */
function submit() {
    form.post(route('settings.tickets.billing.save'), {
        preserveScroll: true,
        onSuccess: () => {
            Object.assign(form, noLegalFiles())
            form.defaults(form.data())
        },
    })
}

function removeFile(document) {
    router.delete(route('settings.tickets.billing.documents.remove', document), { preserveScroll: true })
}
</script>
