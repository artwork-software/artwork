<template>
    <ArtworkBaseModal
        title="Plan change"
        description="Define from which date a different contract and/or different working hours apply."
        modal-size="sm:max-w-3xl"
        @close="$emit('close')">

        <form @submit.prevent="submit" class="space-y-5">
            <!-- Gültigkeit -->
            <div class="rounded-lg border border-border-subtle bg-surface-sunken/60 p-3">
                <p class="text-xs font-medium text-text-muted mb-2">{{ $t('Validity period') }}</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <BaseInput
                            v-model="validFrom"
                            :label="$t('Valid from')"
                            without-translation
                            type="date"
                            required
                            id="plan_valid_from" />
                        <p v-if="errors.valid_from" class="text-danger mt-0.5 text-xs">{{ errors.valid_from }}</p>
                    </div>
                    <div>
                        <BaseInput
                            v-model="validUntil"
                            :label="$t('Valid until')"
                            without-translation
                            type="date"
                            id="plan_valid_until" />
                        <p class="text-[11px] text-text-subtle mt-0.5">{{ $t('Empty = open-ended') }}</p>
                        <p v-if="errors.valid_until" class="text-danger mt-0.5 text-xs">{{ errors.valid_until }}</p>
                    </div>
                </div>
                <p class="text-[11px] text-text-subtle mt-2">
                    {{ $t('The previous open contract period is closed automatically the day before the new period starts.') }}
                </p>
                <div v-if="isRetroactive" class="mt-2 rounded-md border border-warning-border bg-warning-surface px-3 py-2 flex items-start gap-2">
                    <component :is="IconAlertTriangle" class="size-4 shrink-0 text-warning mt-0.5" stroke-width="1.5" />
                    <p class="text-xs text-text">
                        {{ $t('The period starts in the past. Committed shifts in this period will be re-checked against the shift rules.') }}
                    </p>
                </div>
            </div>

            <!-- Vertrag -->
            <section class="rounded-lg border border-border-subtle p-3">
                <h4 class="text-sm font-semibold text-text font-lexend flex items-center gap-2">
                    <component :is="IconContract" class="size-4 text-text-subtle" stroke-width="1.5" />
                    {{ $t('Employment contract') }}
                </h4>
                <p class="text-[11px] text-text-subtle mt-0.5">
                    {{ $t('Currently') }}:
                    <span class="font-medium text-text">{{ currentContract ? (currentContract.contract_name ?? $t('Individual (no template)')) : $t('No contract') }}</span>
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button v-for="option in contractOptions" :key="option.value" type="button"
                            class="rounded-md border px-3 py-1.5 text-xs font-medium transition"
                            :class="contractMode === option.value
                                ? 'border-accent-500 bg-accent-50 text-accent-700'
                                : 'border-border-subtle bg-surface text-text-muted hover:border-accent-200'"
                            @click="contractMode = option.value">
                        {{ $t(option.label) }}
                    </button>
                </div>

                <div v-if="contractMode === 'template'" class="mt-3">
                    <BaseCombobox
                        v-model="templateId"
                        :items="templateItems"
                        option-label="name"
                        option-key="id"
                        :label="$t('Contract template')"
                        :placeholder="$t('Select contract template')"
                        :empty-text="$t('No contracts found.')"
                        coerce="number"
                    />
                    <div v-if="selectedTemplate" class="mt-2 flex flex-wrap gap-1.5">
                        <span v-for="badge in templateBadges" :key="badge"
                              class="inline-flex items-center rounded-full bg-surface-sunken px-2 py-0.5 text-[11px] text-text-muted border border-border-subtle">
                            {{ badge }}
                        </span>
                    </div>
                    <p v-if="errors.user_contract_id" class="text-danger mt-0.5 text-xs">{{ errors.user_contract_id }}</p>
                </div>

                <div v-else-if="contractMode === 'individual'" class="mt-3">
                    <ContractFieldsForm
                        :form="contractForm"
                        :errors="errors"
                        :user-contracts="userContracts"
                        id-prefix="plan_contract"
                    />
                </div>

                <p v-else-if="contractMode === 'end'" class="mt-3 text-xs text-text-muted">
                    {{ $t('The current contract period ends the day before the selected date. No contract applies from then on.') }}
                </p>
            </section>

            <!-- Arbeitszeit -->
            <section class="rounded-lg border border-border-subtle p-3">
                <h4 class="text-sm font-semibold text-text font-lexend flex items-center gap-2">
                    <component :is="IconClockHour10" class="size-4 text-text-subtle" stroke-width="1.5" />
                    {{ $t('Work Time Pattern') }}
                </h4>
                <p class="text-[11px] text-text-subtle mt-0.5">
                    {{ $t('Currently') }}:
                    <span class="font-medium text-text">{{ currentWorkTime ? (currentWorkTime.pattern_name ?? $t('Custom weekly hours (no pattern)')) : $t('No working hours') }}</span>
                    <span v-if="currentWorkTime"> ({{ formatMinutesAsHours(weeklyMinutes(currentWorkTime)) }} / {{ $t('week') }})</span>
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button v-for="option in workTimeOptions" :key="option.value" type="button"
                            class="rounded-md border px-3 py-1.5 text-xs font-medium transition"
                            :class="workTimeMode === option.value
                                ? 'border-accent-500 bg-accent-50 text-accent-700'
                                : 'border-border-subtle bg-surface text-text-muted hover:border-accent-200'"
                            @click="workTimeMode = option.value">
                        {{ $t(option.label) }}
                    </button>
                </div>

                <div v-if="workTimeMode !== 'keep'" class="mt-3">
                    <WorkTimeFieldsForm
                        :form="workTimeForm"
                        :errors="errors"
                        :work-time-patterns="workTimePatterns"
                        :show-pattern-select="workTimeMode === 'pattern'"
                        id-prefix="plan_worktime"
                    />
                    <p v-if="errors.work_time_pattern_id" class="text-danger mt-0.5 text-xs">{{ errors.work_time_pattern_id }}</p>
                </div>
            </section>

            <p v-if="nothingSelected" class="text-xs text-text-subtle">
                {{ $t('Select a contract or working hours change – otherwise there is nothing to save.') }}
            </p>

            <div class="flex justify-end gap-2">
                <BaseUIButton type="button" :label="$t('Cancel')" is-cancel-button @click.stop="$emit('close')" />
                <BaseUIButton
                    type="submit"
                    :label="!processing ? $t('Save change') : $t('Saving...')"
                    is-add-button
                    :disabled="processing || nothingSelected || !validFrom" />
            </div>
        </form>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, reactive, ref} from "vue";
import {router} from "@inertiajs/vue3";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseCombobox from "@/Artwork/Inputs/BaseCombobox.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ContractFieldsForm from "@/Pages/Users/ContractWorkTime/ContractFieldsForm.vue";
import WorkTimeFieldsForm from "@/Pages/Users/ContractWorkTime/WorkTimeFieldsForm.vue";
import {IconAlertTriangle, IconClockHour10, IconContract} from "@tabler/icons-vue";
import {useTranslation} from "@/Composeables/Translation.js";
import {
    addDays,
    contractValuesFrom,
    formatMinutesAsHours,
    seasonInfoParams,
    weeklyMinutes,
    workTimeValuesFrom
} from "@/Pages/Users/ContractWorkTime/contractWorkTimeFields.js";

const $t = useTranslation();

const props = defineProps({
    userId: { type: Number, required: true },
    userContracts: { type: Array, default: () => [] },
    workTimePatterns: { type: Array, default: () => [] },
    today: { type: String, required: true },
    /** Am Startdatum (presetFrom) gültiger Vertrag / Arbeitszeit-Satz – nur Anzeige "aktuell" */
    currentContract: { type: [Object, null], default: null },
    currentWorkTime: { type: [Object, null], default: null },
    presetFrom: { type: [String, null], default: null },
});

const emit = defineEmits(['close', 'saved']);

const validFrom = ref(props.presetFrom || props.today);
const validUntil = ref('');

const contractMode = ref('keep');
const workTimeMode = ref('keep');
const templateId = ref(null);
const processing = ref(false);
const errors = ref({});

const contractForm = reactive(contractValuesFrom(props.currentContract, null));
const workTimeForm = reactive(workTimeValuesFrom(props.currentWorkTime, null));

const contractOptions = computed(() => {
    const options = [
        { value: 'keep', label: 'Keep contract' },
        { value: 'template', label: 'Select contract template' },
        { value: 'individual', label: 'Individual values' },
    ];
    if (props.currentContract && !props.currentContract.valid_until) {
        options.push({ value: 'end', label: 'End contract' });
    }
    return options;
});

const workTimeOptions = [
    { value: 'keep', label: 'Keep working hours' },
    { value: 'pattern', label: 'Select Work Time Pattern' },
    { value: 'custom', label: 'Custom weekly hours' },
];

const templateItems = computed(() => props.userContracts.map(contract => ({ id: contract.id, name: contract.name })));
const selectedTemplate = computed(() => props.userContracts.find(contract => contract.id === templateId.value) ?? null);

const templateBadges = computed(() => {
    const contract = selectedTemplate.value;
    if (!contract) {
        return [];
    }
    const badges = [
        `${$t('Free Full Days Per Week')}: ${contract.free_full_days_per_week ?? 0}`,
        `${$t('Free Half Days Per Week')}: ${contract.free_half_days_per_week ?? 0}`,
        `${$t('Compensation Period (in days)')}: ${contract.compensation_period ?? 0}`,
        `${$t('Special Day Rule Active')}: ${contract.special_day_rule_active ? $t('Yes') : $t('No')}`,
    ];
    seasonInfoParams
        .filter(param => contract[param.activeKey])
        .forEach(param => badges.push(`${$t(param.label)}: ${contract[param.key] ?? 0}`));
    if (contract.overtime_rule_active) {
        badges.push(`${$t('Overtime rule active')}: ${contract.overtime_compensation_period ?? '-'} ${$t('days')}`);
    }
    return badges;
});

const isRetroactive = computed(() => !!validFrom.value && validFrom.value < props.today);

const nothingSelected = computed(() => contractMode.value === 'keep' && workTimeMode.value === 'keep');

/** Vertrags-Payload je Modus (null = kein Vertragsaufruf) */
const contractPayload = () => {
    const validity = { valid_from: validFrom.value, valid_until: validUntil.value || null };
    if (contractMode.value === 'template') {
        if (!selectedTemplate.value) {
            errors.value = { user_contract_id: $t('Please select a contract template.') };
            return null;
        }
        return { ...validity, ...contractValuesFrom(selectedTemplate.value, selectedTemplate.value.id) };
    }
    if (contractMode.value === 'individual') {
        return { ...validity, ...contractForm };
    }
    if (contractMode.value === 'end' && props.currentContract) {
        // Laufenden Zeitraum am Vortag beenden (Bearbeitung des bestehenden Satzes)
        return {
            assign_id: props.currentContract.id,
            valid_from: props.currentContract.valid_from,
            valid_until: addDays(validFrom.value, -1),
            ...contractValuesFrom(props.currentContract, props.currentContract.user_contract_id ?? null),
        };
    }
    return null;
};

const workTimePayload = () => {
    if (workTimeMode.value === 'keep') {
        return null;
    }
    const values = { ...workTimeForm };
    if (workTimeMode.value === 'custom') {
        values.work_time_pattern_id = null;
    }
    return { valid_from: validFrom.value, valid_until: validUntil.value || null, ...values };
};

const patch = (routeName, payload) => new Promise((resolve, reject) => {
    router.patch(route(routeName, props.userId), payload, {
        preserveScroll: true,
        onSuccess: () => resolve(),
        onError: (responseErrors) => reject(responseErrors),
    });
});

/** Beide Endpunkte nacheinander: erst Vertrag, dann Arbeitszeit */
const submit = async () => {
    if (nothingSelected.value || processing.value) {
        return;
    }
    errors.value = {};
    const contractData = contractMode.value === 'keep' ? null : contractPayload();
    if (contractMode.value !== 'keep' && contractData === null) {
        return;
    }
    const workTimeData = workTimePayload();

    processing.value = true;
    try {
        if (contractData) {
            await patch('user-contract-settings.update-user', contractData);
        }
        if (workTimeData) {
            await patch('shift.work-time-pattern.update-user', workTimeData);
        }
        emit('saved');
        emit('close');
    } catch (responseErrors) {
        errors.value = responseErrors ?? {};
    } finally {
        processing.value = false;
    }
};
</script>
