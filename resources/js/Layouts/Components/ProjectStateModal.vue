<template>
    <ArtworkBaseModal
        v-if="show"
        :title="title || 'Edit Status'"
        modal-size="sm:max-w-lg"
        @close="closeModal"
    >
        <form id="projectStateForm" class="space-y-4" @submit.prevent="submit">
            <div class="flex items-end gap-x-3">
                <div class="shrink-0">
                    <span class="mb-1 block font-lexend text-xs font-medium text-text-muted">{{ $t('Color') }}</span>
                    <ColorPickerComponent :color="form.color" @update-color="addColor"/>
                </div>
                <BaseInput
                    id="projectStateName"
                    v-model="form.name"
                    label="Name"
                    required
                    class="flex-1"
                />
            </div>
            <BaseCheckbox
                id="projectStateIsPlanning"
                v-model="form.is_planning"
                :label="$t(PLANNING_LABEL_KEY)"
                :description="$t(PLANNING_DESCRIPTION_KEY)"
            />
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                @click="closeModal"
            />
            <BaseUIButton
                type="submit"
                form="projectStateForm"
                label="Save"
                variant="primary"
                icon="IconDeviceFloppy"
                :disabled="!form.name?.trim()"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {ref, watch} from "vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseCheckbox from "@/Artwork/Inputs/BaseCheckbox.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ColorPickerComponent from "@/Components/Globale/ColorPickerComponent.vue";

defineOptions({name: 'ProjectStateModal'})

const DEFAULT_COLOR = '#FCFCFB';
// Schlüssel enthalten Anführungszeichen, daher nicht direkt im Template-Attribut
const PLANNING_LABEL_KEY = 'Project counts as "in planning"';
const PLANNING_DESCRIPTION_KEY = 'If a project has a status where “Project counts as in planning” is enabled, the switch for planned events is preselected when events are entered in the project. Events are only created as planned while that switch is on.';

const props = defineProps({
    // Projekteinstellungen steuern die Sichtbarkeit über show, der Status-Tag per v-if
    show: {
        type: Boolean,
        default: true
    },
    title: {
        type: String,
        default: ''
    },
    state: {
        type: Object,
        default: () => ({
            name: '',
            color: DEFAULT_COLOR,
            is_planning: false
        })
    }
});

const emit = defineEmits(['close', 'submit']);

function emptyForm() {
    return {
        id: null,
        name: '',
        color: DEFAULT_COLOR,
        is_planning: false
    };
}

const form = ref(emptyForm());

/**
 * is_planning kommt je nach Quelle als Boolean oder 0/1. Vorher setzte onMounted nur bei === 1 auf true und
 * überschrieb damit einen Boolean-Wert true wieder mit false.
 */
function fillForm(state) {
    if (!state) {
        return;
    }
    form.value.id = state.id || null;
    form.value.name = state.name || '';
    form.value.color = state.color || DEFAULT_COLOR;
    form.value.is_planning = Boolean(Number(state.is_planning));
}

watch(() => props.state, fillForm, {deep: true, immediate: true});

function closeModal() {
    emit('close');
    form.value = emptyForm();
}

function submit() {
    if (!form.value.name?.trim()) {
        return;
    }
    emit('submit', {...form.value});
    closeModal();
}

function addColor(color) {
    form.value.color = color;
}
</script>
