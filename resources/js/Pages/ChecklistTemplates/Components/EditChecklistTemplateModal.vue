<template>
    <ArtworkBaseModal
        @close="$emit('closed', true)"
        :title="$t('Edit checklist template')"
        :description="$t('Change the name of this checklist template.')"
    >
        <form @submit.prevent="submit">
            <div class="mt-6 grid grid-cols-1 gap-4">
                <BaseInput
                    id="checklistTemplateName"
                    v-model="form.name"
                    :label="$t('Name of the checklist template') + '*'"
                    required
                />
                <div class="w-full flex items-center justify-end text-center">
                    <BaseUIButton
                        type="submit"
                        class="mt-4"
                        :disabled="form.name === '' || form.processing"
                        :label="$t('Save')"
                        is-add-button
                    />
                </div>
            </div>
        </form>
    </ArtworkBaseModal>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

const props = defineProps({
    checklistTemplate: {
        type: Object,
        required: true
    }
})

const emits = defineEmits(['closed'])

const form = useForm({
    name: props.checklistTemplate.name
})

const submit = () => {
    form.patch(route('checklist_templates.update', { checklist_template: props.checklistTemplate.id }), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => emits('closed')
    })
}
</script>
