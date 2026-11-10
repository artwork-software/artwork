<template>
    <ArtworkBaseModal
        title="Import template"
        description="To make your work easier, use a template."
        modal-size="sm:max-w-lg"
        @close="closeModal(false)"
    >
        <ArtworkBaseListbox
            v-if="templateOptions.length > 0"
            v-model="selectedTemplate"
            :items="templateOptions"
            by="id"
            option-label="name"
            label="Template"
        />
        <EmptyState
            v-else
            icon="IconTemplate"
            :title="$t('No templates available')"
            :description="$t('Templates can be created in the budget settings.')"
        />
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="closeModal(false)"
            />
            <BaseUIButton
                label="Import template"
                variant="primary"
                icon="IconFileImport"
                :processing="processing"
                :disabled="!selectedTemplate"
                @click="useTemplate"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, ref} from 'vue'
import {router} from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import EmptyState from '@/Artwork/Feedback/EmptyState.vue'

defineOptions({name: 'UseTemplateComponent'})

const props = defineProps({
    projectId: {type: Number, default: null},
    templates: {type: [Array, Object], default: () => []},
})

const emit = defineEmits(['closed'])

const selectedTemplate = ref(null)
const processing = ref(false)

const templateOptions = computed(() => Object.values(props.templates ?? {}))

function closeModal(imported) {
    emit('closed', imported)
}

function useTemplate() {
    if (!selectedTemplate.value || processing.value) {
        return
    }
    processing.value = true
    router.post(
        route('project.budget.template.use', selectedTemplate.value.id),
        {project_id: props.projectId},
        {
            preserveScroll: true,
            onSuccess: () => closeModal(true),
            onFinish: () => {
                processing.value = false
            },
        }
    )
}
</script>
