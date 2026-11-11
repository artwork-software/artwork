<template>
    <ArtworkBaseModal
        title="Rename table"
        description="Choose a meaningful name for your template. This will make it easy for all users to find."
        modal-size="sm:max-w-lg"
        @close="closeModal(false)"
    >
        <form id="renameBudgetTableForm" @submit.prevent="updateBudgetTemplateName">
            <BaseInput
                id="budgetTableName"
                v-model="tableName"
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
                form="renameBudgetTableForm"
                label="Rename"
                variant="primary"
                icon="IconPencil"
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

defineOptions({name: 'RenameTableComponent'})

const props = defineProps({
    table: {type: Object, required: true},
})

const emit = defineEmits(['closed'])

const tableName = ref(props.table?.name ?? '')
const processing = ref(false)
const errorMessage = ref('')

const canSubmit = computed(() => tableName.value.trim() !== '' && !processing.value)

function closeModal(renamed) {
    emit('closed', renamed)
}

function updateBudgetTemplateName() {
    if (!canSubmit.value) {
        return
    }
    processing.value = true
    errorMessage.value = ''
    router.patch(
        route('project.budget.table.update-name'),
        {
            table_id: props.table.id,
            table_name: tableName.value.trim(),
        },
        {
            preserveScroll: true,
            onSuccess: () => closeModal(true),
            onError: (errors) => {
                errorMessage.value = errors.table_name ?? Object.values(errors)[0] ?? ''
            },
            onFinish: () => {
                processing.value = false
            },
        }
    )
}
</script>
