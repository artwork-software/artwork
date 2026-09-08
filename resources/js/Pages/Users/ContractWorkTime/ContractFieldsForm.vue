<template>
    <div class="space-y-4">
        <!-- Vorlage: Auswahl kopiert die Vorlagenwerte in die Felder (danach individuell anpassbar) -->
        <div v-if="showTemplateSelect" class="rounded-md border border-border-subtle bg-surface-sunken/60 p-3">
            <BaseCombobox
                v-model="templateId"
                :items="templateItems"
                option-label="name"
                option-key="id"
                :label="$t('Contract template')"
                :placeholder="$t('Individual (no template)')"
                :empty-text="$t('No contracts found.')"
                coerce="number"
                @update:model-value="applyTemplate"
            />
            <p class="text-[11px] text-text-subtle mt-2">
                {{ $t('Selecting a template copies its values into the fields below. You can adjust them individually afterwards; deviations from the template are shown as chips.') }}
            </p>
        </div>

        <div class="rounded-md border border-border-subtle p-3">
            <h4 class="text-xs font-semibold text-text font-lexend flex items-center gap-2">
                <component :is="IconCalendarOff" class="size-4 text-text-subtle" stroke-width="1.5" />
                {{ $t('Free days & compensation') }}
            </h4>
            <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <BaseInput
                        v-model="form.free_full_days_per_week"
                        label="Free Full Days Per Week"
                        type="number"
                        :min="0"
                        :id="`${idPrefix}_free_full_days_per_week`" />
                    <p v-if="errors.free_full_days_per_week" class="text-danger mt-0.5 text-xs">{{ errors.free_full_days_per_week }}</p>
                </div>
                <div>
                    <BaseInput
                        v-model="form.free_half_days_per_week"
                        label="Free Half Days Per Week"
                        type="number"
                        :min="0"
                        :id="`${idPrefix}_free_half_days_per_week`" />
                    <p v-if="errors.free_half_days_per_week" class="text-danger mt-0.5 text-xs">{{ errors.free_half_days_per_week }}</p>
                </div>
                <div>
                    <BaseInput
                        v-model="form.compensation_period"
                        label="Compensation Period (in days)"
                        type="number"
                        :min="0"
                        :id="`${idPrefix}_compensation_period`" />
                    <p v-if="errors.compensation_period" class="text-danger mt-0.5 text-xs">{{ errors.compensation_period }}</p>
                </div>
            </div>
            <div class="mt-3">
                <BaseCheckbox
                    v-model="form.special_day_rule_active"
                    :id="`${idPrefix}_special_day_rule_active`"
                    :label="$t('Special Day Rule Active')"
                    :description="$t('Active: special days reduce the daily target of this person. Inactive: special days do not count, every day has the normal daily target (e.g. contracts where public holidays do not matter).')"
                />
            </div>
        </div>

        <div class="rounded-md border border-border-subtle p-3">
            <h4 class="text-xs font-semibold text-text font-lexend flex items-center gap-2">
                <component :is="IconInfoSquareRounded" class="size-4 text-text-subtle" stroke-width="1.5" />
                {{ $t('Season-related info data') }}
            </h4>
            <p class="text-[11px] text-text-subtle mt-1">
                {{ $t('Activate the parameters relevant for this contract and define the target value (X). The season is configured in the tool settings under "Communication & Legal".') }}
            </p>
            <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2">
                <div v-for="param in seasonInfoParams" :key="param.key"
                     class="rounded-md border border-border-subtle p-2.5">
                    <BaseCheckbox
                        v-model="form[param.activeKey]"
                        :id="`${idPrefix}_param_${param.key}`"
                        :label="$t(param.label)"
                    />
                    <div v-if="form[param.activeKey]" class="mt-2 pl-7">
                        <BaseInput
                            v-model="form[param.key]"
                            :label="$t('Minimum value (X)')"
                            type="number"
                            :min="0"
                            :step="param.step || '1'"
                            :id="`${idPrefix}_${param.key}`" />
                    </div>
                </div>
                <div class="rounded-md border border-border-subtle p-2.5">
                    <BaseInput
                        v-model="form.annual_vacation_days"
                        :label="$t('Annual vacation days (per calendar year)')"
                        type="number"
                        :min="0"
                        :id="`${idPrefix}_annual_vacation_days`" />
                </div>
            </div>
        </div>

        <div class="rounded-md border border-border-subtle p-3">
            <h4 class="text-xs font-semibold text-text font-lexend flex items-center gap-2">
                <component :is="IconClockPlus" class="size-4 text-text-subtle" stroke-width="1.5" />
                {{ $t('Overtime') }}
            </h4>
            <div class="mt-3">
                <BaseCheckbox
                    v-model="form.overtime_rule_active"
                    :id="`${idPrefix}_overtime_rule_active`"
                    :label="$t('Overtime rule active')"
                    :description="$t('If activated, overtime must be compensated within the given number of days; otherwise it is shown in the \'Overtime\' tab as \'overtime to be paid out\' and must be booked out manually.')"
                />
                <div v-if="form.overtime_rule_active" class="mt-3 pl-7 max-w-sm">
                    <BaseInput
                        v-model="form.overtime_compensation_period"
                        :label="$t('Period within which overtime must be reduced (days)')"
                        type="number"
                        :min="1"
                        :id="`${idPrefix}_overtime_compensation_period`" />
                    <p v-if="errors.overtime_compensation_period" class="text-danger mt-0.5 text-xs">{{ errors.overtime_compensation_period }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {computed, ref, watch} from "vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseCheckbox from "@/Artwork/Inputs/BaseCheckbox.vue";
import BaseCombobox from "@/Artwork/Inputs/BaseCombobox.vue";
import {IconCalendarOff, IconClockPlus, IconInfoSquareRounded} from "@tabler/icons-vue";
import {contractValuesFrom, seasonInfoParams} from "@/Pages/Users/ContractWorkTime/contractWorkTimeFields.js";

const props = defineProps({
    /** Reaktives Formularobjekt mit den Vertragsfeldern (useForm oder reactive) – wird direkt beschrieben */
    form: { type: Object, required: true },
    errors: { type: Object, default: () => ({}) },
    userContracts: { type: Array, default: () => [] },
    showTemplateSelect: { type: Boolean, default: true },
    idPrefix: { type: String, default: 'contract' },
});

const templateId = ref(props.form.user_contract_id ?? null);

watch(() => props.form.user_contract_id, (value) => {
    templateId.value = value ?? null;
});

const templateItems = computed(() => props.userContracts.map(contract => ({ id: contract.id, name: contract.name })));

/** Vorlage gewählt → Werte kopieren; abgewählt → Vorlagenbezug lösen, Werte bleiben (individuell) */
const applyTemplate = (value) => {
    const id = value === '' || value === undefined ? null : value;
    const template = props.userContracts.find(contract => contract.id === id);
    const values = template ? contractValuesFrom(template, template.id) : { user_contract_id: null };
    Object.entries(values).forEach(([key, fieldValue]) => {
        props.form[key] = fieldValue;
    });
};
</script>
