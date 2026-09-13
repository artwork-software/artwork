<template>
    <div class="px-4 py-3">
        <div class="flex items-start gap-3">
            <component :is="IconContract" class="size-5 shrink-0 text-text-subtle mt-0.5" stroke-width="1.5" />

            <!-- Kein Vertrag in diesem Zeitraum -->
            <div v-if="!assign" class="min-w-0 flex-1 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <p class="text-xs text-text-subtle">{{ $t('Employment contract') }}</p>
                    <p class="text-sm font-medium text-text">{{ $t('No contract in this period') }}</p>
                    <p class="text-[11px] text-text-subtle mt-0.5">
                        {{ $t('Without a contract no rule check applies and there are no season figures.') }}
                    </p>
                </div>
                <BaseUIButton
                    :label="$t('Set contract from here')"
                    :icon="IconFileSearch"
                    is-small
                    @click.stop="$emit('planFrom')"
                />
            </div>

            <div v-else class="min-w-0 flex-1">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-xs text-text-subtle">{{ $t('Employment contract') }}</p>
                        <p class="text-sm font-semibold text-text font-lexend flex items-center gap-2 flex-wrap">
                            {{ assign.contract_name ?? $t('Individual (no template)') }}
                            <span v-if="assign.user_contract_id && assign.deviations.length > 0"
                                  class="inline-flex items-center rounded-full bg-warning-surface border border-warning-border px-2 py-0.5 text-[11px] font-medium text-text">
                                {{ $t('{0} deviation(s) from template', [assign.deviations.length]) }}
                            </span>
                        </p>
                        <p class="text-[11px] text-text-subtle mt-0.5">
                            {{ $t('Valid') }}: {{ formatPeriod(assign.valid_from, assign.valid_until, $t) }}
                        </p>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <BaseUIButton
                            :label="expanded ? $t('Close') : $t('Edit')"
                            :icon="expanded ? IconChevronUp : IconPencil"
                            is-small
                            @click.stop="toggle"
                        />
                        <BaseUIButton
                            :label="$t('Delete period')"
                            :icon="IconTrash"
                            is-small
                            variant="ghost"
                            @click.stop="showConfirmDelete = true"
                        />
                    </div>
                </div>

                <!-- Kompakte Werte -->
                <dl class="mt-2 flex flex-wrap gap-x-5 gap-y-1">
                    <div v-for="item in summaryItems" :key="item.label" class="flex items-baseline gap-1">
                        <dt class="text-[11px] text-text-subtle">{{ $t(item.label) }}:</dt>
                        <dd class="text-xs font-medium text-text">
                            {{ item.value }}
                            <!-- 0 auf dem Zeitraum = nicht gesetzt → Vorlagenwert gilt (ContractSettingsResolver) -->
                            <span v-if="item.inherited"
                                  class="font-normal text-text-subtle"
                                  :title="$t('Not set on this period – the template value applies.')">
                                {{ $t('(from template)') }}
                            </span>
                        </dd>
                    </div>
                </dl>

                <!-- Abweichungen von der Vorlage -->
                <div v-if="assign.user_contract_id && assign.deviations.length > 0" class="mt-2 flex flex-wrap gap-1.5">
                    <span v-for="deviation in assign.deviations" :key="deviation.key"
                          class="inline-flex items-center gap-1 rounded-full bg-surface-sunken border border-border-subtle px-2 py-0.5 text-[11px] text-text-muted"
                          :title="$t('Template: {0}', [formatValue(deviation.key, deviation.template_value)])">
                        {{ $t(contractFieldLabels[deviation.key] ?? deviation.key) }}: {{ formatValue(deviation.key, deviation.value) }}
                        <span class="text-text-subtle">({{ $t('template') }} {{ formatValue(deviation.key, deviation.template_value) }})</span>
                    </span>
                </div>

                <!-- Bearbeiten -->
                <form v-if="expanded" @submit.prevent="submit" class="mt-4 space-y-4 border-t border-border-subtle pt-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-w-xl">
                        <div>
                            <BaseInput
                                v-model="form.valid_from"
                                :label="$t('Valid from')"
                                without-translation
                                type="date"
                                :id="`assign_${assign.id}_valid_from`" />
                            <p class="text-[11px] text-text-subtle mt-0.5">{{ $t('Empty = from the beginning') }}</p>
                            <p v-if="form.errors.valid_from" class="text-danger mt-0.5 text-xs">{{ form.errors.valid_from }}</p>
                        </div>
                        <div>
                            <BaseInput
                                v-model="form.valid_until"
                                :label="$t('Valid until')"
                                without-translation
                                type="date"
                                :id="`assign_${assign.id}_valid_until`" />
                            <p class="text-[11px] text-text-subtle mt-0.5">{{ $t('Empty = open-ended') }}</p>
                            <p v-if="form.errors.valid_until" class="text-danger mt-0.5 text-xs">{{ form.errors.valid_until }}</p>
                        </div>
                    </div>

                    <div v-if="isRetroactive" class="rounded-md border border-warning-border bg-warning-surface px-3 py-2 flex items-start gap-2">
                        <component :is="IconAlertTriangle" class="size-4 shrink-0 text-warning mt-0.5" stroke-width="1.5" />
                        <p class="text-xs text-text">
                            {{ $t('The period starts in the past. Committed shifts in this period will be re-checked against the shift rules.') }}
                        </p>
                    </div>

                    <ContractFieldsForm
                        :form="form"
                        :errors="form.errors"
                        :user-contracts="userContracts"
                        :id-prefix="`assign_${assign.id}`"
                    />

                    <div class="flex justify-end gap-2">
                        <BaseUIButton
                            type="button"
                            :label="$t('Cancel')"
                            is-cancel-button
                            @click.stop="toggle"
                        />
                        <BaseUIButton
                            type="submit"
                            :label="!form.processing ? $t('Save') : $t('Saving...')"
                            is-add-button
                            :disabled="form.processing"/>
                    </div>
                </form>
            </div>
        </div>

        <ConfirmDeleteModal
            v-if="showConfirmDelete"
            :title="$t('Delete contract period')"
            :description="$t('Do you really want to delete this contract period? Shift rules will be re-checked for the affected period.')"
            @delete="destroy"
            @closed="showConfirmDelete = false"
        />
    </div>
</template>

<script setup>
import {computed, ref} from "vue";
import {router, useForm} from "@inertiajs/vue3";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import ContractFieldsForm from "@/Pages/Users/ContractWorkTime/ContractFieldsForm.vue";
import {
    IconAlertTriangle,
    IconChevronUp,
    IconContract,
    IconFileSearch,
    IconPencil,
    IconTrash
} from "@tabler/icons-vue";
import {useTranslation} from "@/Composeables/Translation.js";
import {
    contractFieldLabels,
    contractValuesFrom,
    formatPeriod,
    seasonInfoParams
} from "@/Pages/Users/ContractWorkTime/contractWorkTimeFields.js";

const $t = useTranslation();

const props = defineProps({
    assign: { type: [Object, null], default: null },
    userId: { type: Number, required: true },
    userContracts: { type: Array, default: () => [] },
    today: { type: String, required: true },
});

const emit = defineEmits(['planFrom', 'saved']);

const expanded = ref(false);
const showConfirmDelete = ref(false);

const form = useForm({
    assign_id: props.assign?.id ?? null,
    valid_from: props.assign?.valid_from ?? '',
    valid_until: props.assign?.valid_until ?? '',
    ...contractValuesFrom(props.assign, props.assign?.user_contract_id ?? null),
});

const toggle = () => {
    if (!expanded.value && props.assign) {
        form.clearErrors();
        Object.assign(form, {
            assign_id: props.assign.id,
            valid_from: props.assign.valid_from ?? '',
            valid_until: props.assign.valid_until ?? '',
            ...contractValuesFrom(props.assign, props.assign.user_contract_id ?? null),
        });
    }
    expanded.value = !expanded.value;
};

const isRetroactive = computed(() => !!form.valid_from && form.valid_from < props.today);

const yesNo = (value) => (value ? $t('Yes') : $t('No'));

const formatValue = (key, value) => {
    if (typeof value === 'boolean' || key.endsWith('_active')) {
        return yesNo(!!value);
    }
    if (value === null || value === undefined || value === '') {
        return '–';
    }
    return value;
};

// Wirksamer Wert je Feld: Server liefert effective_values (Zuweisung, bei 0 die Vorlage + inherited);
// ohne diese Angabe (z. B. ohne Vorlage) der rohe Wert der Zuweisung
const effective = (key) => {
    const entry = props.assign?.effective_values?.[key];
    if (entry && typeof entry === 'object') {
        return { value: entry.value ?? 0, inherited: !!entry.inherited };
    }
    return { value: props.assign?.[key] ?? 0, inherited: false };
};

const summaryItems = computed(() => {
    if (!props.assign) {
        return [];
    }
    const items = [
        { label: 'Free Full Days Per Week', ...effective('free_full_days_per_week') },
        { label: 'Free Half Days Per Week', ...effective('free_half_days_per_week') },
        { label: 'Compensation Period (in days)', ...effective('compensation_period') },
        { label: 'Special Day Rule Active', value: yesNo(props.assign.special_day_rule_active) },
        {
            label: 'Overtime rule active',
            value: props.assign.overtime_rule_active
                ? `${$t('Yes')} (${props.assign.overtime_compensation_period ?? '-'} ${$t('days')})`
                : $t('No'),
        },
    ];
    seasonInfoParams
        .filter(param => props.assign[param.activeKey])
        .forEach(param => {
            const { value, inherited } = effective(param.key);
            items.push({
                label: param.label,
                value: param.decimals ? Number(value ?? 0).toFixed(param.decimals) : (value ?? 0),
                inherited,
            });
        });
    const vacation = effective('annual_vacation_days');
    if (Number(vacation.value) > 0) {
        items.push({ label: 'Annual vacation days (per calendar year)', ...vacation });
    }
    return items;
});

const submit = () => {
    form.transform(data => ({
        ...data,
        valid_from: data.valid_from || null,
        valid_until: data.valid_until || null,
    })).patch(route('user-contract-settings.update-user', props.userId), {
        preserveScroll: true,
        onSuccess: () => {
            expanded.value = false;
            emit('saved');
        },
    });
};

const destroy = () => {
    router.delete(route('user-contract-settings.assign.destroy', { user: props.userId, assign: props.assign.id }), {
        preserveScroll: true,
        onFinish: () => {
            showConfirmDelete.value = false;
        },
    });
};
</script>
