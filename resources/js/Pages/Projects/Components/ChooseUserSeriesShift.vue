<template>
    <ArtworkBaseModal
        title="Shift staffing period"
        description="You have filled a repeat shift. Determine the period for which you want to fill the employee."
        modal-size="sm:max-w-lg"
        @close="closed"
    >
        <div class="space-y-4">
            <SwitchDualLabel
                v-model="bufferForReturn.onlyThisDay"
                :left-label="$t('On this and other days')"
                :right-label="$t('For this day only')"
                :icon="IconRepeat"
            />

            <div v-if="!bufferForReturn.onlyThisDay" class="space-y-4">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <BaseInput
                        id="seriesShiftStart"
                        v-model="bufferForReturn.start"
                        type="date"
                        label="Start"
                        required
                    />
                    <BaseInput
                        id="seriesShiftEnd"
                        v-model="bufferForReturn.end"
                        type="date"
                        label="End"
                        required
                        :min="bufferForReturn.start || null"
                        :error="hasInvalidPeriod ? $t('The end date must be after the start date.') : ''"
                    />
                </div>
                <ArtworkBaseListbox
                    v-model="selectedDay"
                    :items="weekdays"
                    by="id"
                    option-label="name"
                    label="Repetition"
                    :enable-search="false"
                />
            </div>
        </div>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                @click="closed"
            />
            <BaseUIButton
                label="Save"
                variant="primary"
                icon="IconDeviceFloppy"
                :disabled="!canSubmit"
                @click="returnBuffer"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, reactive, ref} from 'vue'
import {useI18n} from 'vue-i18n'
import {IconRepeat} from '@tabler/icons-vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue'
import SwitchDualLabel from '@/Artwork/Toggles/SwitchDualLabel.vue'

defineOptions({name: 'ChooseUserSeriesShift'})

const props = defineProps({
    event: {type: Object, default: null},
    buffer: {type: Object, default: null},
})

const emit = defineEmits(['close-modal', 'returnBuffer'])

const {t} = useI18n()

const bufferForReturn = reactive({
    onlyThisDay: props.buffer?.onlyThisDay ?? false,
    start: props.buffer?.start ?? null,
    end: props.buffer?.end ?? null,
    dayOfWeek: null,
})

const weekdays = [
    {name: t('Every day'), id: 'all'},
    {name: t('Every Monday'), id: 1},
    {name: t('Every Tuesday'), id: 2},
    {name: t('Every Wednesday'), id: 3},
    {name: t('Every Thursday'), id: 4},
    {name: t('Every Friday'), id: 5},
    {name: t('Every Saturday'), id: 6},
    {name: t('Every Sunday'), id: 0},
]

const selectedDay = ref(weekdays[0])

// ISO-Datumsstrings lassen sich direkt vergleichen
const hasInvalidPeriod = computed(() =>
    !!bufferForReturn.start && !!bufferForReturn.end && bufferForReturn.end < bufferForReturn.start
)

// Für eine Serie braucht das Backend Start und Ende
const canSubmit = computed(() =>
    bufferForReturn.onlyThisDay
    || (!!bufferForReturn.start && !!bufferForReturn.end && !hasInvalidPeriod.value)
)

function closed() {
    emit('close-modal')
}

function returnBuffer() {
    if (!canSubmit.value) {
        return
    }
    bufferForReturn.dayOfWeek = selectedDay.value?.id ?? 'all'
    emit('returnBuffer', {...bufferForReturn})
}
</script>
