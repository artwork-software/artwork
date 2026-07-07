<template>
    <ArtworkBaseModal
        :title="contactType ? $t('Edit contact type') : $t('New contact type')"
        :description="$t('Define a new contact type for your CRM.')"
        @close="$emit('close')"
    >
        <div class="space-y-4 mt-4">
            <div class="flex items-center gap-x-3">
                <IconSelector
                    :current-icon="form.icon"
                    @update:modelValue="(icon) => form.icon = icon"
                />
                <div class="w-full">
                    <BaseInput id="type_name" v-model="form.name" :label="$t('Name')" :error="form.errors.name" />
                </div>
                <ColorPickerComponent
                    :color="form.color"
                    @updateColor="(color) => form.color = color"
                />
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button class="ui-button-cancel" @click="$emit('close')">{{ $t('Cancel') }}</button>
                <button class="ui-button-add" @click="submit" :disabled="form.processing">
                    {{ contactType ? $t('Save') : $t('Create') }}
                </button>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import IconSelector from '@/Components/Icon/IconSelector.vue'
import ColorPickerComponent from '@/Components/Globale/ColorPickerComponent.vue'

const props = defineProps({
    contactType: { type: Object, default: null },
})

const emit = defineEmits(['close'])

const form = useForm({
    name: props.contactType?.name ?? '',
    icon: props.contactType?.icon ?? '',
    color: props.contactType?.color ?? '#0d6be3',
})

const submit = () => {
    if (props.contactType) {
        form.patch(route('crm.types.update', props.contactType.id), {
            onSuccess: () => emit('close'),
        })
    } else {
        form.post(route('crm.types.store'), {
            onSuccess: () => emit('close'),
        })
    }
}
</script>
