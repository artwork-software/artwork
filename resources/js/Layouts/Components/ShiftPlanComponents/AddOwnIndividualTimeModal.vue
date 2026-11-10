<template>
    <ArtworkBaseModal
        :title="$t('Enter individual time')"
        :description="$t('The time applies immediately and is visible to planners in the duty roster.')"
        @close="$emit('closed')"
    >
        <div class="p-4 space-y-4">
            <!-- Datum (nur Anzeige, kommt aus der angeklickten Tagesspalte) -->
            <div>
                <label class="block text-sm font-medium text-text-muted mb-1">
                    {{ $t('Date') }}
                </label>
                <div class="text-sm text-text bg-surface-sunken rounded-lg px-3 py-2">
                    {{ formatDate(date) }}
                </div>
            </div>

            <BaseInput
                id="own_individual_time_title"
                v-model="form.title"
                :label="$t('Title')"
            />

            <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-text-muted">
                <input
                    v-model="form.full_day"
                    type="checkbox"
                    class="h-4 w-4 rounded border-border text-accent-600 focus:ring-accent-600"
                />
                <span>{{ $t('Full day') }}</span>
            </label>

            <template v-if="!form.full_day">
                <div class="flex items-center gap-1">
                    <BaseInput
                        type="time"
                        id="own_individual_time_start"
                        classes="rounded-r-none"
                        v-model="form.start_time"
                        :label="$t('Start time')"
                    />
                    <BaseInput
                        type="time"
                        id="own_individual_time_end"
                        v-model="form.end_time"
                        classes="border-l-0 rounded-l-none"
                        :label="$t('End time')"
                    />
                </div>

                <div>
                    <BaseInput
                        type="number"
                        id="own_individual_time_break"
                        v-model.number="form.break_minutes"
                        :label="$t('Break (minutes)')"
                        :min="0"
                        :step="1"
                        @input="autoBreak.markManual()"
                    />
                    <LegalBreakHint
                        :break-minutes="form.break_minutes"
                        :legal-minutes="autoBreak.legalMinutes.value"
                        :has-times="autoBreak.hasTimes.value"
                        @reset="autoBreak.resetToLegal()"
                    />
                </div>
            </template>

            <!-- Wiederkehrend: öffnet die Zeitreihe, fest auf die eigene Person -->
            <button
                type="button"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-accent-600 hover:text-accent-700"
                @click="$emit('open-series')"
            >
                <PropertyIcon name="IconRepeat" class="h-4 w-4" />
                {{ $t('Enter as recurring series instead') }}
            </button>

            <p v-if="errorMessage" class="text-sm text-danger">{{ errorMessage }}</p>
        </div>

        <div class="flex justify-end gap-3 p-4 border-t border-border-subtle">
            <BaseUIButton :label="$t('Cancel')" is-cancel-button type="button" @click="$emit('closed')" />
            <BaseUIButton
                type="button"
                is-add-button
                :disabled="saving || !canSave"
                :label="saving ? $t('Saving...') : $t('Save')"
                @click="save"
            />
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, reactive, ref, toRef } from 'vue'
import axios from 'axios'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import LegalBreakHint from '@/Components/Inputs/LegalBreakHint.vue'
import { useAutoBreak } from '@/Composeables/useAutoBreak'
import { extractSaveErrorMessage } from '@/Composeables/BiSaveFeedback.js'

const props = defineProps({
    date: { type: String, required: true }, // Y-m-d
    userId: { type: Number, required: true },
})

const emit = defineEmits(['closed', 'saved', 'open-series'])

const saving = ref(false)
const errorMessage = ref('')

const form = reactive({
    title: '',
    full_day: false,
    start_time: '',
    end_time: '',
    break_minutes: 0,
})

const autoBreak = useAutoBreak(
    toRef(form, 'start_time'),
    toRef(form, 'end_time'),
    toRef(form, 'break_minutes')
)

const canSave = computed(() => form.full_day || (!!form.start_time && !!form.end_time))

function formatDate(dateStr) {
    const [yyyy, mm, dd] = String(dateStr).split('-')
    return `${dd}.${mm}.${yyyy}`
}

async function save() {
    if (saving.value || !canSave.value) {
        return
    }
    saving.value = true
    errorMessage.value = ''

    try {
        await axios.post(route('add.update.individualTimesAndShiftPlanComment'), {
            modelId: props.userId,
            modelType: 0,
            individualTimes: [{
                title: form.title || null,
                start_date: props.date,
                // ganztägig = ohne Uhrzeiten (IndividualTimeService::createForModel)
                start_time: form.full_day ? null : form.start_time,
                end_time: form.full_day ? null : form.end_time,
                break_minutes: form.full_day ? null : form.break_minutes,
            }],
        }, {
            skipErrorToast: true, // Fehler steht im Modal
        })
        emit('saved')
        emit('closed')
    } catch (error) {
        errorMessage.value = extractSaveErrorMessage(error)
    } finally {
        saving.value = false
    }
}
</script>
