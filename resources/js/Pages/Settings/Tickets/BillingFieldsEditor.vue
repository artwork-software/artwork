<template>
    <div>
        <div class="mb-6">
            <h3 class="font-lexend text-[13px] font-semibold text-text mb-3">{{ $t('Legal details') }}</h3>
            <div class="grid gap-5 sm:grid-cols-2">
                <BaseInput id="tickets-billing-legal-name" v-model="billing.legal_name" :label="$t('Legal name')" required />
                <div>
                    <label class="mb-1 block font-lexend text-xs font-medium text-[#3F424A]">{{ $t('Legal form') }}<span class="text-danger">*</span></label>
                    <SearchableSelect v-model="billing.legal_form" :options="legalFormOptions" :placeholder="$t('Legal form')" />
                </div>
                <div class="sm:col-span-2">
                    <BaseInput id="tickets-billing-street" v-model="billing.street" :label="$t('Street and number')" required />
                </div>
                <BaseInput id="tickets-billing-postal-code" v-model="billing.postal_code" :label="$t('Postal code')" required />
                <BaseInput id="tickets-billing-city" v-model="billing.city" :label="$t('City')" required />
                <div>
                    <label class="mb-1 block font-lexend text-xs font-medium text-[#3F424A]">{{ $t('Country') }}<span class="text-danger">*</span></label>
                    <SearchableSelect v-model="billing.country" :options="countryOptions" :placeholder="$t('Country')" />
                </div>
                <div></div>
                <BaseInput id="tickets-billing-register-number" v-model="billing.register_number" :label="$t('Register number (optional)')" />
                <BaseInput id="tickets-billing-register-court" v-model="billing.register_court" :label="$t('Register court (optional)')" />
                <div>
                    <BaseInput id="tickets-billing-vat-id" v-model="billing.vat_id" :label="$t('VAT ID')" />
                    <p class="mt-1.5 text-xs text-text-subtle">{{ $t('VAT ID or tax number, one of the two is enough.') }}</p>
                </div>
                <BaseInput id="tickets-billing-tax-number" v-model="billing.tax_number" :label="$t('Tax number')" />
                <BaseInput id="tickets-billing-contact-name" v-model="billing.contact_name" :label="$t('Responsible person')" required />
                <BaseInput id="tickets-billing-contact-phone" v-model="billing.contact_phone" :label="$t('Phone')" required />
                <div class="sm:col-span-2">
                    <BaseInput id="tickets-billing-website" v-model="billing.website" :label="$t('Website (optional)')" placeholder="https://" />
                </div>
            </div>
        </div>
        <div>
            <h3 class="font-lexend text-[13px] font-semibold text-text mb-1">{{ $t('Bank account') }}</h3>
            <p class="text-[13px] leading-5 text-text-subtle mb-3">{{ $t('Where artwork tickets pays out. The account holder has to match the legal name; the IBAN is stored encrypted.') }}</p>
            <div class="grid gap-5 sm:grid-cols-2">
                <BaseInput id="tickets-billing-account-holder" v-model="billing.account_holder" :label="$t('Account holder')" required />
                <div>
                    <BaseInput id="tickets-billing-iban" v-model="billing.iban" :label="$t('IBAN')" :required="ibanLast4 === null"
                               :placeholder="ibanLast4 ? `•••• •••• •••• •••• •••• ${ibanLast4}` : 'DE00 0000 0000 0000 0000 00'"
                               @update:model-value="billing.iban = formatIban(billing.iban)" />
                    <p v-if="billing.iban && !isValidIban(billing.iban)" class="mt-1.5 text-xs text-danger">{{ $t('This IBAN is not valid.') }}</p>
                    <p v-else-if="ibanLast4" class="mt-1.5 text-xs text-text-subtle">{{ $t('An IBAN ending in {last4} is stored. Leave the field empty to keep it.', { last4: ibanLast4 }) }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import SearchableSelect from '@/Artwork/Listbox/SearchableSelect.vue'
import { formatIban, isValidIban, legalFormNames } from '@/Pages/Settings/Tickets/drafts.js'

/* The fields of the legal and bank blocks, edited in place: the wizard step and the
   billing tab hand in their own draft and decide themselves when it is good enough. */

const props = defineProps({
    billing: { type: Object, required: true },
    countries: { type: Array, required: true },
    legalForms: { type: Array, required: true },
    /** Set once tickets holds an IBAN; the field may then stay empty. */
    ibanLast4: { type: String, default: null },
})

const { t } = useI18n()

/* The same names the room editor uses, so a house and its rooms read alike. */
const countryNames = { DE: t('Germany'), AT: t('Austria'), CH: t('Switzerland'), LI: t('Liechtenstein'), LU: t('Luxembourg'), NL: t('Netherlands'), BE: t('Belgium'), FR: t('France'), DK: t('Denmark'), PL: t('Poland'), CZ: t('Czechia'), IT: t('Italy') }
const countryOptions = computed(() => props.countries.map((code) => ({ id: code, name: countryNames[code] ?? code })))

const legalFormOptions = computed(() => props.legalForms.map((form) => ({ id: form, name: legalFormNames(t)[form] ?? form })))
</script>
