<template>
    <div>
        <h3 class="font-lexend text-[13px] font-semibold text-text mb-3">{{ $t('Legal details') }}</h3>
        <div class="grid gap-5 sm:grid-cols-2">
            <BaseInput id="tickets-billing-legal-name" v-model="billing.legal_name" :error="errorOf('legal_name')" :label="$t('Legal name')" required />
            <div>
                <label class="mb-1 block font-lexend text-xs font-medium text-[#3F424A]">{{ $t('Legal form') }}<span class="text-danger">*</span></label>
                <div :class="errorOf('legal_form') && 'rounded-md ring-1 ring-danger-border'">
                    <SearchableSelect v-model="billing.legal_form" :options="legalFormOptions" placeholder="Please select" />
                </div>
                <p v-if="errorOf('legal_form')" class="mt-1 text-[11.5px] text-danger">{{ errorOf('legal_form') }}</p>
            </div>
            <div class="sm:col-span-2">
                <BaseInput id="tickets-billing-street" v-model="billing.street" :error="errorOf('street')" :label="$t('Street and number')" required />
            </div>
            <BaseInput id="tickets-billing-postal-code" v-model="billing.postal_code" :error="errorOf('postal_code')" :label="$t('Postal code')" required />
            <BaseInput id="tickets-billing-city" v-model="billing.city" :error="errorOf('city')" :label="$t('City')" required />
            <div>
                <label class="mb-1 block font-lexend text-xs font-medium text-[#3F424A]">{{ $t('Country') }}<span class="text-danger">*</span></label>
                <SearchableSelect v-model="billing.country" :options="countryOptions" placeholder="Please select" />
            </div>
            <div></div>
            <BaseInput id="tickets-billing-register-number" v-model="billing.register_number" :label="$t('Register number (optional)')" />
            <BaseInput id="tickets-billing-register-court" v-model="billing.register_court" :label="$t('Register court (optional)')" />
            <div>
                <BaseInput id="tickets-billing-vat-id" v-model="billing.vat_id" :error="errorOf('vat_id')" :label="$t('VAT ID')" />
                <p class="mt-1.5 text-xs text-text-subtle">{{ $t('VAT ID or tax number, one of the two is enough.') }}</p>
            </div>
            <BaseInput id="tickets-billing-tax-number" v-model="billing.tax_number" :error="errorOf('vat_id')" :label="$t('Tax number')" />
            <BaseInput id="tickets-billing-contact-name" v-model="billing.contact_name" :error="errorOf('contact_name')" :label="$t('Responsible person')" required />
            <BaseInput id="tickets-billing-contact-phone" v-model="billing.contact_phone" :error="errorOf('contact_phone')" :label="$t('Phone')" required />
            <div class="sm:col-span-2">
                <BaseInput id="tickets-billing-website" v-model="billing.website" :label="$t('Website (optional)')" placeholder="https://" />
            </div>
        </div>

        <h3 class="font-lexend text-[13px] font-semibold text-text mt-8 mb-1">{{ $t('Legal pages of the shop') }}</h3>
        <p class="text-[13px] leading-5 text-text-subtle mb-3">{{ $t('The house\'s own terms, privacy policy and imprint. Buyers see them in the shop and accept the terms at checkout.') }}</p>
        <div class="grid gap-5">
            <div v-for="page in shopLegalPages" :key="page.document">
                <label :for="`tickets-billing-${page.document}`" class="mb-1 block font-lexend text-xs font-medium text-[#3F424A]">{{ page.label }}<span class="text-danger">*</span></label>
                <div class="flex items-start gap-2">
                    <BaseInput :id="`tickets-billing-${page.document}`" v-model="billing[`${page.document}_url`]" :label="page.label" :show-label="false" placeholder="https://" class="flex-1" :error="errorOf(`${page.document}_url`)"
                               @update:model-value="billing[`${page.document}_file`] = null" />
                    <label class="ui-button h-10 shrink-0 cursor-pointer">
                        <IconUpload class="size-3.5" />{{ $t('Upload PDF') }}
                        <input type="file" accept="application/pdf" class="sr-only" @change="pickPdf(page.document, $event)" />
                    </label>
                </div>
                <p v-if="pdfOf(page.document)" class="mt-1.5 flex items-center gap-1.5 text-xs text-text-subtle">
                    <IconFileTypePdf class="size-3.5 shrink-0" />
                    <a v-if="pdfOf(page.document).url" :href="pdfOf(page.document).url" target="_blank" rel="noopener" class="truncate font-medium text-text hover:underline">{{ pdfOf(page.document).name }}</a>
                    <span v-else class="truncate font-medium text-text">{{ pdfOf(page.document).name }}</span>
                    <span class="shrink-0">· {{ $t('A link entered above replaces it.') }}</span>
                    <button type="button" class="ml-1 shrink-0 hover:text-danger" @click="removePdf(page.document)">{{ $t('Remove') }}</button>
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { IconFileTypePdf, IconUpload } from '@tabler/icons-vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import SearchableSelect from '@/Artwork/Listbox/SearchableSelect.vue'
import { legalFormNames } from '@/Pages/Settings/Tickets/drafts.js'

/* The legal details and the shop's legal pages, edited in place: the wizard step and the
   billing tab hand in their own draft and decide themselves when it is good enough. */

const props = defineProps({
    billing: { type: Object, required: true },
    countries: { type: Array, required: true },
    legalForms: { type: Array, required: true },
    /** PDFs stored in tickets, per page: `{ file_name, url }` or null. */
    files: { type: Object, default: () => ({}) },
    /** Fields to mark, from `missingDetails` — empty until the form wants to show them. */
    missing: { type: Array, default: () => [] },
})

const errorOf = (field) => (props.missing.includes(field) ? t('Required') : '')

/** Removing a PDF stored in tickets is the parent's request; one picked here is just dropped. */
const emit = defineEmits(['remove-file'])

const { t } = useI18n()

/* The same names the room editor uses, so a house and its rooms read alike. */
const countryNames = { DE: t('Germany'), AT: t('Austria'), CH: t('Switzerland'), LI: t('Liechtenstein'), LU: t('Luxembourg'), NL: t('Netherlands'), BE: t('Belgium'), FR: t('France'), DK: t('Denmark'), PL: t('Poland'), CZ: t('Czechia'), IT: t('Italy') }
const countryOptions = computed(() => props.countries.map((code) => ({ id: code, name: countryNames[code] ?? code })))

/* Each page is a link or a PDF (`{page}_url`, `{page}_file`) — one source each, as in tickets. */
const shopLegalPages = [
    { document: 'terms', label: t('Terms and conditions') },
    { document: 'privacy', label: t('Privacy policy') },
    { document: 'imprint', label: t('Imprint') },
]

/** The PDF a page has: one picked here and not sent yet, or the one stored in tickets. */
function pdfOf(document) {
    const picked = props.billing[`${document}_file`]
    if (picked) return { name: picked.name, url: null }
    const stored = props.files[document]
    return stored ? { name: stored.file_name, url: stored.url } : null
}

function pickPdf(document, event) {
    props.billing[`${document}_file`] = event.target.files[0] ?? null
    props.billing[`${document}_url`] = ''
    event.target.value = ''
}

function removePdf(document) {
    if (props.billing[`${document}_file`]) {
        props.billing[`${document}_file`] = null
    } else {
        emit('remove-file', document)
    }
}

const legalFormOptions = computed(() => props.legalForms.map((form) => ({ id: form, name: legalFormNames(t)[form] ?? form })))
</script>
