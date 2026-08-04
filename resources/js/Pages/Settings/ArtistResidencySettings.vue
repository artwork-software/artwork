<template>
    <ProjectSettingsHeader
        :title="$t('Artist residencies')"
        :description="$t('Define global default values for artist residencies.')"
    >
        <SettingsGuideBanner
            class="mb-6"
            storage-key="settings-guide.project.artist-residencies"
            title="How does this area work?"
            :paragraphs="[
                'These are default values: they are pre-filled when you create a new artist residency and can be overwritten there.',
                'Changing a default does not affect existing residencies.',
                'This tab is only saved when you click the save button.',
            ]"
        />
        <div class="rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5">
            <BasePageTitle
                :title="$t('Artist residencies')"
                :description="$t('Settings for artist residencies such as default values for breakfast deductions.')"
            />

            <transition
                enter-active-class="duration-300 ease-out"
                enter-from-class="transform opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="transform opacity-0"
            >
                <div class="my-3 text-xs bg-success px-3 py-1.5 text-white rounded-lg" v-show="showSaveSuccess">
                    {{ $t('Saved. The changes have been successfully applied.') }}
                </div>
            </transition>

            <div class="mt-4 max-w-xs">
                <BaseInput
                    type="number"
                    id="breakfast_deduction"
                    v-model="form.breakfast_deduction_per_day"
                    :label="$t('Deduction per breakfast') + ' (€)'"
                    :step="0.01"
                />
            </div>

            <div class="mt-6 max-w-xs">
                <BaseInput
                    type="number"
                    id="daily_allowance_default"
                    v-model="form.artist_residency_daily_allowance_default"
                    :label="$t('Default daily allowance') + ' (€)'"
                    :step="0.01"
                />
                <p class="text-xs text-text-subtle mt-1">
                    {{ $t('Pre-filled into the daily allowance field when creating a new artist residency.') }}
                </p>
            </div>

            <div class="mt-6 max-w-md">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        v-model="form.artist_residency_do_not_save_default"
                        class="rounded border-border text-accent-700 focus:ring-accent-700"
                    />
                    <span class="text-sm font-medium text-text-muted">
                        {{ $t('“Do not save artist in database” checked by default') }}
                    </span>
                </label>
            </div>

            <div class="mt-6 max-w-md">
                <label class="block text-sm font-medium text-text-muted">
                    {{ $t('Name columns in the artist residency table') }}
                </label>
                <p class="text-xs text-text-subtle mt-0.5">
                    {{ $t('Enable/disable and reorder which name columns are shown first (left to right).') }}
                </p>
                <ul class="mt-2 divide-y divide-border-subtle rounded-md border border-border-subtle">
                    <li
                        v-for="(column, index) in form.artist_residency_name_columns"
                        :key="column.key"
                        class="flex items-center justify-between px-3 py-2"
                    >
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="column.enabled"
                                class="rounded border-border text-accent-700 focus:ring-accent-700"
                            />
                            <span class="text-sm text-text-muted">{{ $t(nameColumnLabels[column.key] ?? column.key) }}</span>
                        </label>
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                class="p-1 text-text-subtle hover:text-text-muted disabled:text-text-subtle disabled:cursor-not-allowed"
                                :disabled="index === 0"
                                @click="moveNameColumn(index, -1)"
                                :title="$t('Move up')"
                            >
                                <IconChevronUp class="h-4 w-4" />
                            </button>
                            <button
                                type="button"
                                class="p-1 text-text-subtle hover:text-text-muted disabled:text-text-subtle disabled:cursor-not-allowed"
                                :disabled="index === form.artist_residency_name_columns.length - 1"
                                @click="moveNameColumn(index, 1)"
                                :title="$t('Move down')"
                            >
                                <IconChevronDown class="h-4 w-4" />
                            </button>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="mt-6">
                <button
                    type="button"
                    class="ui-button-add"
                    @click="save"
                    :disabled="form.processing"
                >
                    {{ $t('Save') }}
                </button>
            </div>
        </div>
    </ProjectSettingsHeader>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { IconChevronUp, IconChevronDown } from '@tabler/icons-vue'
import ProjectSettingsHeader from '@/Pages/Settings/Components/ProjectSettingsHeader.vue'
import SettingsGuideBanner from '@/Artwork/Guide/SettingsGuideBanner.vue'
import BasePageTitle from '@/Artwork/Titles/BasePageTitle.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'

const props = defineProps({
    breakfastDeductionPerDay: { type: Number, default: 5.60 },
    artistResidencyDoNotSaveDefault: { type: Boolean, default: false },
    artistResidencyDailyAllowanceDefault: { type: Number, default: 0.00 },
    artistResidencyNameColumns: { type: Array, default: () => [] },
})

const nameColumnLabels = {
    name: 'Artist name',
    first_name: 'First name',
    last_name: 'Last name',
}

const showSaveSuccess = ref(false)

const form = useForm({
    breakfast_deduction_per_day: props.breakfastDeductionPerDay ?? 5.60,
    artist_residency_do_not_save_default: props.artistResidencyDoNotSaveDefault ?? false,
    artist_residency_daily_allowance_default: props.artistResidencyDailyAllowanceDefault ?? 0.00,
    artist_residency_name_columns: (props.artistResidencyNameColumns && props.artistResidencyNameColumns.length)
        ? JSON.parse(JSON.stringify(props.artistResidencyNameColumns))
        : [
            { key: 'name', enabled: true },
            { key: 'first_name', enabled: false },
            { key: 'last_name', enabled: false },
        ],
})

const moveNameColumn = (index, direction) => {
    const target = index + direction
    if (target < 0 || target >= form.artist_residency_name_columns.length) {
        return
    }
    const reordered = [...form.artist_residency_name_columns]
    ;[reordered[index], reordered[target]] = [reordered[target], reordered[index]]
    form.artist_residency_name_columns = reordered
}

const save = () => {
    form.transform((data) => ({
        ...data,
        breakfast_deduction_per_day: Number(data.breakfast_deduction_per_day),
        artist_residency_daily_allowance_default: Number(data.artist_residency_daily_allowance_default),
    })).patch(route('project_settings.artist_residency.update'), {
        preserveScroll: true,
        onSuccess: () => {
            showSaveSuccess.value = true
            setTimeout(() => {
                showSaveSuccess.value = false
            }, 2500)
        },
    })
}
</script>
