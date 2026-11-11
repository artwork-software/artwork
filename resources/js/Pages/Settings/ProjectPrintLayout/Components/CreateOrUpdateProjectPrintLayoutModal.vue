<template>
    <ArtworkBaseModal
        :title="projectPrintLayout ? 'Edit Print Layout' : 'Create Print Layout'"
        :description="projectPrintLayout ? 'Edit the print layout' : 'Create a new print layout'"
        modal-size="sm:max-w-2xl"
        @close="emits('close')"
    >
        <form id="createOrUpdateProjectPrintLayoutForm" class="space-y-4" @submit.prevent="createOrUpdate">
            <BaseInput
                id="print_layout_name"
                v-model="createOrUpdateForm.name"
                label="Name"
                required
                :error="createOrUpdateForm.errors.name"
            />
            <BaseInput
                id="print_layout_description"
                v-model="createOrUpdateForm.description"
                label="Description"
                :error="createOrUpdateForm.errors.description"
            />
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div v-for="column in columnSettings" :key="column.key">
                    <label
                        :for="`print_layout_${column.key}`"
                        class="mb-2 flex items-center justify-between font-lexend text-xs font-medium text-text"
                    >
                        <span>{{ $t(column.label) }}</span>
                        <span class="inline-flex min-w-6 items-center justify-center rounded-md border border-border-subtle bg-surface-sunken px-1.5 py-0.5 text-xs font-medium tabular-nums text-text-muted">
                            {{ createOrUpdateForm[column.key] }}
                        </span>
                    </label>
                    <input
                        :id="`print_layout_${column.key}`"
                        v-model="createOrUpdateForm[column.key]"
                        type="range"
                        min="1"
                        :max="column.max"
                        step="1"
                        :disabled="isEditing"
                        class="h-2 w-full cursor-pointer appearance-none rounded-lg bg-border-subtle accent-accent-600 disabled:cursor-not-allowed"
                    />
                </div>
            </div>
            <BaseAlertComponent
                v-if="parseInt(createOrUpdateForm.columns_body) > 1"
                type="warning"
                message="The columns of the main part are set to more than 1, which makes the print layout less legible and it is no longer possible to add the components marked as “special components”."
                use-translation
                class="!mb-0"
            />
            <div class="border-t border-border-subtle pt-4">
                <ArtworkBaseToggle
                    id="print_layout_is_active"
                    v-model="createOrUpdateForm.is_active"
                    label="Should this print layout be available?"
                    use-translation
                />
            </div>
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="createOrUpdateForm.processing"
                @click="emits('close')"
            />
            <BaseUIButton
                type="submit"
                form="createOrUpdateProjectPrintLayoutForm"
                :label="isEditing ? 'Update' : 'Create'"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="createOrUpdateForm.processing"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed} from "vue";
import {useForm} from "@inertiajs/vue3";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ArtworkBaseToggle from "@/Artwork/Toggles/ArtworkBaseToggle.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";

defineOptions({name: 'CreateOrUpdateProjectPrintLayoutModal'})

const props = defineProps({
    projectPrintLayout: {
        type: Object,
        required: false,
        default: null,
    },
})

const emits = defineEmits([
    'close',
])

// Spaltenzahlen sind nach dem Anlegen fest (bestehende Komponenten hängen daran)
const isEditing = computed(() => Boolean(props.projectPrintLayout))

const columnSettings = [
    {key: 'columns_header', label: 'Header columns', max: 5},
    {key: 'columns_footer', label: 'Footer columns', max: 5},
    {key: 'columns_body', label: 'Body columns', max: 3},
]

/**
 * Form for creating or updating a project print layout
 * @type {InertiaForm<{name: (*|string), description: (*|string), columns_header: (number), columns_footer: (number), columns_body: (number), is_active: (boolean)}>}
 */
const createOrUpdateForm = useForm({
    id: props.projectPrintLayout ? props.projectPrintLayout.id : null,
    name: props.projectPrintLayout ? props.projectPrintLayout.name : '',
    description: props.projectPrintLayout ? props.projectPrintLayout.description : '',
    columns_header: props.projectPrintLayout ? props.projectPrintLayout.columns_header : 1,
    columns_footer: props.projectPrintLayout ? props.projectPrintLayout.columns_footer : 1,
    columns_body: props.projectPrintLayout ? props.projectPrintLayout.columns_body : 1,
    is_active: props.projectPrintLayout ? Boolean(props.projectPrintLayout.is_active) : true,
})

const createOrUpdate = () => {
    if (createOrUpdateForm.processing) {
        return
    }
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            emits('close')
        }
    }
    if (props.projectPrintLayout) {
        createOrUpdateForm.patch(route('project-print-layout.update', props.projectPrintLayout.id), options)
    } else {
        createOrUpdateForm.post(route('project-print-layout.store'), options)
    }
}
</script>
