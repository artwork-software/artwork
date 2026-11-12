<template>
    <ArtworkBaseModal
        title="Create timeline"
        description="Define the shift-relevant times. You can create shifts along this timeline."
        modal-size="sm:max-w-xl"
        @close="closeModal(false)"
    >
        <div class="space-y-4">
            <SingleTimeLine
                v-for="time in timeLine"
                :key="time.id"
                :time="time"
                :preset="true"
            />

            <div
                v-if="showAddTimeLineForm"
                class="space-y-3 rounded-lg border border-border-subtle bg-surface-sunken p-4"
            >
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <BaseInput
                        id="presetTimeLineStart"
                        v-model="addTimeLineForm.start"
                        type="time"
                        label="Start time"
                        required
                        :error="helpText || addTimeLineForm.errors.start"
                        @focusout="checkTime"
                    />
                    <BaseInput
                        id="presetTimeLineEnd"
                        v-model="addTimeLineForm.end"
                        type="time"
                        label="End time"
                        required
                        :error="addTimeLineForm.errors.end"
                        @focusout="checkTime"
                    />
                </div>
                <BaseTextarea
                    id="presetTimeLineDescription"
                    v-model="addTimeLineForm.description"
                    label="Description"
                    :rows="3"
                />
                <div class="flex justify-end">
                    <BaseUIButton
                        label="Remove"
                        variant="ghost"
                        size="sm"
                        icon="IconX"
                        @click="hideAddTimeLineForm"
                    />
                </div>
            </div>
            <BaseUIButton
                v-else
                label="Add time"
                variant="secondary"
                icon="IconCirclePlus"
                @click="showAddTimeLineForm = true"
            />

            <BaseAlertComponent
                v-if="hasInvalidTimeLine"
                type="error"
                :message="$t('The start time must be before the end time.')"
            />
        </div>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="closeModal(false)"
            />
            <BaseUIButton
                label="Save"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="processing"
                @click="saveTimeLines"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {ref} from 'vue'
import {router, useForm} from '@inertiajs/vue3'
import {useI18n} from 'vue-i18n'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import BaseAlertComponent from '@/Components/Alerts/BaseAlertComponent.vue'
import SingleTimeLine from '@/Pages/Projects/Components/SingleTimeLine.vue'

defineOptions({name: 'AddEditTimeLineModal'})

const props = defineProps({
    presetId: {type: [Number, String], required: true},
    timeLine: {type: Array, default: () => []},
})

const emit = defineEmits(['closed'])

const {t} = useI18n()

const helpText = ref('')
const processing = ref(false)
const hasInvalidTimeLine = ref(false)
const showAddTimeLineForm = ref(props.timeLine.length === 0)
const addTimeLineForm = useForm({
    start: null,
    end: null,
    description: null,
})

function closeModal(saved) {
    emit('closed', saved)
}

function hideAddTimeLineForm() {
    showAddTimeLineForm.value = false
    helpText.value = ''
    addTimeLineForm.reset()
    addTimeLineForm.clearErrors()
}

function checkTime() {
    if (!addTimeLineForm.start || !addTimeLineForm.end) {
        helpText.value = t('Please fill in both fields.')
        return false
    }
    if (addTimeLineForm.start >= addTimeLineForm.end) {
        helpText.value = t('The start time must be before the end time.')
        return false
    }
    helpText.value = ''
    return true
}

function saveTimeLines() {
    if (processing.value) {
        return
    }
    hasInvalidTimeLine.value = props.timeLine.some((timeline) => timeline.start >= timeline.end)
    if (hasInvalidTimeLine.value) {
        return
    }
    if (showAddTimeLineForm.value) {
        if (!checkTime()) {
            return
        }
        processing.value = true
        addTimeLineForm.post(
            route('preset.add.timeline.row', {shiftPreset: props.presetId}),
            {
                preserveState: true,
                preserveScroll: true,
                // bestehende Zeilen erst nach erfolgreichem Anlegen speichern
                onSuccess: () => updateTimes(),
                onError: () => {
                    processing.value = false
                },
            }
        )
        return
    }
    processing.value = true
    updateTimes()
}

function updateTimes() {
    router.patch(route('preset.timeline.update'), {
        timelines: props.timeLine,
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => closeModal(true),
        onFinish: () => {
            processing.value = false
        },
    })
}
</script>
