<template>
    <ToolSettingsHeader :title="$t('Regional formats')">
        <SettingsGuideBanner
            class="mt-6"
            storage-key="settings-guide.tool.formats"
            title="How does this area work?"
            :paragraphs="[
                'Number and currency formats apply in the interface and in many PDFs and exports. The date format is currently only used in individual places of the interface (e.g. budget comments, sources of funding, document requests and BI snapshots); PDFs and exports keep their own date format.',
                'Changes are saved immediately.',
            ]"
        />
        <div class="grid grid-cols-1 gap-6 mt-10 max-w-lg">
            <ArtworkBaseListbox
                v-model="selectedNumberLocale"
                :items="numberLocaleOptions"
                option-key="id"
                by="id"
                label="Number format"
            />
            <ArtworkBaseListbox
                v-model="selectedCurrency"
                :items="currencyOptions"
                option-key="id"
                by="id"
                label="Currency"
            />
            <ArtworkBaseListbox
                v-model="selectedDateFormat"
                :items="dateFormatOptions"
                option-key="id"
                by="id"
                label="Date format"
            />

            <div class="rounded-lg border border-border-subtle p-4 text-sm">
                <p class="font-semibold mb-2">{{ $t('Preview') }}</p>
                <dl class="grid grid-cols-2 gap-y-1">
                    <dt class="text-text-subtle">{{ $t('Number') }}</dt>
                    <dd>{{ preview.formatNumber(1234567.891) }}</dd>
                    <dt class="text-text-subtle">{{ $t('Amount') }}</dt>
                    <dd>{{ preview.formatCurrency(1234.5) }}</dd>
                    <dt class="text-text-subtle">{{ $t('Date') }}</dt>
                    <dd>{{ preview.formatDate('2026-12-31') }}</dd>
                </dl>
            </div>
        </div>
    </ToolSettingsHeader>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import ToolSettingsHeader from "@/Pages/ToolSettings/ToolSettingsHeader.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import { createInstanceFormatter } from "@/Helper/instanceFormat.js";

const props = defineProps({
    formatSettings: { type: Object, required: true },
    numberLocales: { type: Array, required: true },
    currencies: { type: Array, required: true },
    dateFormats: { type: Array, required: true },
});

const { t } = useI18n();

const REGION_NAMES = {
    'de-DE': 'Germany',
    'de-AT': 'Austria',
    'de-CH': 'Switzerland (German)',
    'fr-CH': 'Switzerland (French)',
    'en-GB': 'United Kingdom',
    'en-US': 'United States',
};

const numberLocaleOptions = props.numberLocales.map((locale) => ({
    id: locale,
    name: `${t(REGION_NAMES[locale] ?? locale)} · ${createInstanceFormatter({ numberLocale: locale }).formatNumber(1234.56)}`,
}));
const currencyOptions = props.currencies.map((currency) => ({ id: currency, name: currency }));
const dateFormatOptions = props.dateFormats.map((format) => ({
    id: format,
    name: createInstanceFormatter({ dateFormat: format }).formatDate('2026-12-31'),
}));

const findOption = (options, id) => options.find((option) => option.id === id) ?? options[0];

const selectedNumberLocale = ref(findOption(numberLocaleOptions, props.formatSettings.number_locale));
const selectedCurrency = ref(findOption(currencyOptions, props.formatSettings.currency));
const selectedDateFormat = ref(findOption(dateFormatOptions, props.formatSettings.date_format));

const preview = computed(() => createInstanceFormatter({
    numberLocale: selectedNumberLocale.value?.id,
    currency: selectedCurrency.value?.id,
    dateFormat: selectedDateFormat.value?.id,
}));

const save = () => {
    router.patch(route('tool.formats.update'), {
        number_locale: selectedNumberLocale.value?.id,
        currency: selectedCurrency.value?.id,
        date_format: selectedDateFormat.value?.id,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
};

watch([selectedNumberLocale, selectedCurrency, selectedDateFormat], save);
</script>
