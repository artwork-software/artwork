<template>
    <ArtworkBaseModal
        title="Edit holiday"
        description="Edit the holiday by filling in the fields. You can rename the holiday, change the date and adjust the federal states and color."
        modal-size="sm:max-w-2xl"
        @close="closeModal(false)"
    >
        <form id="editHolidayForm" class="space-y-5" @submit.prevent="storeCustomHoliday">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <BaseInput
                        id="editHolidayName"
                        v-model="customHolidayForm.name"
                        label="Name"
                        required
                        :error="customHolidayForm.errors.name"
                    />
                </div>
                <BaseInput
                    id="editHolidayStart"
                    v-model="customHolidayForm.date"
                    type="date"
                    label="Start date"
                    required
                    :error="dateError"
                />
                <BaseInput
                    id="editHolidayEnd"
                    v-model="customHolidayForm.end_date"
                    type="date"
                    label="End date"
                    :error="customHolidayForm.errors.end_date"
                />
                <!-- Typ: gesetzlich / Schulferien / eigener Eintrag; Typwechsel setzt den Sondertag-Default -->
                <div class="md:col-span-2">
                    <ArtworkBaseListbox
                        v-model="holidayType"
                        :items="holidayTypeOptions"
                        by="id"
                        option-key="id"
                        option-label="name"
                        use-translations
                        label="Holiday type"
                        placeholder="Please select"
                        :enable-search="false"
                    />
                    <p class="mt-1 text-xs text-text-muted">
                        {{ $t('School holidays are not special days by default; statutory public holidays are.') }}
                    </p>
                </div>
            </div>

            <div class="space-y-3 border-t border-border-subtle pt-5">
                <div>
                    <h3 class="font-lexend text-sm font-semibold text-text">{{ $t('Select states & color') }}</h3>
                    <p class="mt-1 text-sm text-text-muted">
                        {{ $t('Select the federal states that should apply to this public holiday. You can select as many federal states as you like. You can then specify a color in which the public holiday should be displayed in your calendar. You do not have to select a federal state if the public holiday applies throughout Germany.') }}
                    </p>
                </div>
                <div class="flex items-end gap-4">
                    <div class="min-w-0 flex-1">
                        <ArtworkBaseListbox
                            v-model="customHolidayForm.selectedSubdivisions"
                            :items="subdivisionOptions"
                            by="id"
                            option-label="name"
                            label="Federal states"
                            placeholder="Select federal states"
                            multiple
                        />
                    </div>
                    <div class="shrink-0">
                        <ColorPickerComponent :color="customHolidayForm.color" @update-color="updateColorCustomHoliday" />
                    </div>
                </div>
                <div v-if="customHolidayForm.selectedSubdivisions.length > 0" class="flex flex-wrap items-center gap-2">
                    <BaseChip
                        v-for="selectedSubdivision in customHolidayForm.selectedSubdivisions"
                        :key="selectedSubdivision.id"
                    >
                        {{ selectedSubdivision.name }}
                        <button
                            type="button"
                            class="-mr-1 inline-flex size-4 cursor-pointer items-center justify-center rounded-full text-text-subtle transition-colors duration-150 hover:text-danger motion-reduce:transition-none"
                            :aria-label="$t('Remove')"
                            @click="removeSubDivisionFormCustomHoliday(selectedSubdivision.id)"
                        >
                            <PropertyIcon name="IconX" class="size-3" stroke-width="2" aria-hidden="true" />
                        </button>
                    </BaseChip>
                </div>
            </div>

            <div class="space-y-4 border-t border-border-subtle pt-5">
                <SwitchDualLabel
                    v-model="isOneOff"
                    :left-label="$t('Repeat the holiday annually')"
                    :right-label="$t('One-off public holiday')"
                    :icon="IconRepeat"
                />
                <BaseCheckbox
                    id="treatAsSpecialDay"
                    v-model="customHolidayForm.treatAsSpecialDay"
                    :label="$t('Treat as special day')"
                />
            </div>
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="customHolidayForm.processing"
                @click="closeModal(false)"
            />
            <BaseUIButton
                type="submit"
                form="editHolidayForm"
                label="Save"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="customHolidayForm.processing"
                :disabled="customHolidayForm.processing"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, ref, watch} from 'vue'
import {useForm} from '@inertiajs/vue3'
import {IconRepeat} from '@tabler/icons-vue'
import {useTranslation} from '@/Composeables/Translation.js'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseChip from '@/Artwork/Chips/BaseChip.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import SwitchDualLabel from '@/Artwork/Toggles/SwitchDualLabel.vue'
import ColorPickerComponent from '@/Components/Globale/ColorPickerComponent.vue'

defineOptions({name: 'EditHolidayModal'})

const props = defineProps({
    holidayToEdit: {
        type: Object,
        required: true,
    },
    subDivisions: {
        type: [Array, Object],
        required: true,
    },
    // [{id: 'public'|'school'|'custom', name: Übersetzungsschlüssel}]
    holidayTypeOptions: {
        type: Array,
        required: false,
        default: () => [
            {id: 'public', name: 'Statutory public holiday'},
            {id: 'school', name: 'School vacations'},
            {id: 'custom', name: 'Custom entry'},
        ],
    },
})

// close(true) nach erfolgreichem Speichern
const emit = defineEmits(['close'])

const t = useTranslation()
const showErrorMessageDate = ref(false)

const customHolidayForm = useForm({
    name: props.holidayToEdit.name,
    date: props.holidayToEdit.date,
    end_date: props.holidayToEdit.end_date,
    color: props.holidayToEdit.color,
    yearly: Boolean(props.holidayToEdit.yearly),
    treatAsSpecialDay: props.holidayToEdit.treatAsSpecialDay || false,
    selectedSubdivisions: [...(props.holidayToEdit.subdivisions ?? [])],
    type: props.holidayToEdit.type || 'custom',
})

const subdivisionOptions = computed(() => Object.values(props.subDivisions ?? {}))

// Schalter rechts = einmalig, links = jährlich
const isOneOff = computed({
    get: () => !customHolidayForm.yearly,
    set: (value) => {
        customHolidayForm.yearly = !value
    },
})

const dateError = computed(() => {
    if (showErrorMessageDate.value) {
        return t('Please select a date')
    }
    return customHolidayForm.errors.date ?? ''
})

const holidayType = ref(
    props.holidayTypeOptions.find((option) => option.id === (props.holidayToEdit.type || 'custom')) ?? props.holidayTypeOptions[0] ?? null
)
watch(holidayType, (option, previous) => {
    const type = option?.id ?? 'custom'
    if (type === (previous?.id ?? customHolidayForm.type)) {
        return
    }
    customHolidayForm.type = type
    // Typwechsel: Sondertag-Default = gesetzlicher Feiertag (kann danach manuell überschrieben werden)
    customHolidayForm.treatAsSpecialDay = type === 'public'
})

function closeModal(saved) {
    if (saved) {
        emit('close', true)
        return
    }
    emit('close')
}

function storeCustomHoliday() {
    showErrorMessageDate.value = !customHolidayForm.date
    if (showErrorMessageDate.value || customHolidayForm.processing) {
        return
    }
    customHolidayForm.patch(route('holidays.update', props.holidayToEdit.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(true),
    })
}

function updateColorCustomHoliday(color) {
    customHolidayForm.color = color
}

function removeSubDivisionFormCustomHoliday(id) {
    customHolidayForm.selectedSubdivisions = customHolidayForm.selectedSubdivisions.filter((subdivision) => subdivision.id !== id)
}
</script>
