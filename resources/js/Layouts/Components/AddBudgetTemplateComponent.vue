<template>
    <ArtworkBaseModal
        title="Save as template"
        description="Save your calculation and make it available to all users as a template."
        modal-size="sm:max-w-lg"
        @close="closeModal(false)"
    >
        <form id="addBudgetTemplateForm" @submit.prevent="createBudgetTemplate">
            <BaseInput
                id="budgetTemplateName"
                v-model="templateName"
                label="Template name"
                required
                :error="errorMessage"
                autofocus
            />
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="closeModal(false)"
            />
            <BaseUIButton
                type="submit"
                form="addBudgetTemplateForm"
                label="Save as template"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="processing"
                :disabled="!canSubmit"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, ref} from 'vue'
import {router} from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

defineOptions({name: 'AddBudgetTemplateComponent'})

const props = defineProps({
    tableId: {type: Number, required: true},
})

const emit = defineEmits(['closed'])

const templateName = ref('')
const processing = ref(false)
const errorMessage = ref('')

const canSubmit = computed(() => templateName.value.trim() !== '' && !processing.value)

function closeModal(saved) {
    emit('closed', saved)
}

function createBudgetTemplate() {
    if (!canSubmit.value) {
        return
    }
    processing.value = true
    errorMessage.value = ''
    router.post(
        route('project.budget.template.create', props.tableId),
        {template_name: templateName.value.trim()},
        {
            preserveScroll: true,
            onSuccess: () => closeModal(true),
            onError: (errors) => {
                errorMessage.value = errors.template_name ?? Object.values(errors)[0] ?? ''
            },
            onFinish: () => {
                processing.value = false
            },
        }
    )
}
</script>
