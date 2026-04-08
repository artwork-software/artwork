<template>
    <ArtworkBaseModal
        :title="property ? $t('Edit property') : $t('New property')"
        :description="$t('Define a property for CRM contacts.')"
        @close="$emit('close')"
    >
        <div class="space-y-4 mt-4">
            <BaseInput id="prop_name" v-model="form.name" :label="$t('Name')" :error="form.errors.name" />
            <ArtworkBaseListbox
                v-model="selectedPropertyType"
                :items="propertyTypeOptions"
                by="id"
                option-label="name"
                option-key="id"
                :label="$t('Type')"
                :use-translations="true"
                :placeholder="$t('Please select')"
            />
            <p v-if="form.errors.type" class="mt-1 text-xs text-artwork-messages-error">{{ form.errors.type }}</p>
            <BaseInput id="prop_tooltip" v-model="form.tooltip_text" :label="$t('Tooltip text')" />

            <!-- Select values input -->
            <div v-if="form.type === 'select'" class="space-y-2">
                <label class="componentLabel">{{ $t('Selection values') }}</label>
                <div v-for="(val, index) in form.select_values" :key="index" class="flex items-center gap-3">
                    <BaseInput
                        :id="'select_value_' + index"
                        v-model="form.select_values[index]"
                        :label="$t('Selection value {index}', { index: index + 1 })"
                    />
                    <button type="button" @click="form.select_values.splice(index, 1)" class="text-gray-400 hover:text-red-600">
                        <IconX class="h-4 w-4" />
                    </button>
                </div>
                <button type="button" @click="form.select_values.push('')" class="text-sm text-indigo-600 hover:text-indigo-500 flex items-center gap-1">
                    <IconPlus class="h-4 w-4" />
                    {{ $t('Add option') }}
                </button>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button class="ui-button-cancel" @click="$emit('close')">{{ $t('Cancel') }}</button>
                <button class="ui-button-add" @click="submit" :disabled="form.processing">
                    {{ property ? $t('Save') : $t('Create') }}
                </button>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue'
import { IconX, IconPlus } from '@tabler/icons-vue'

const props = defineProps({
    property: { type: Object, default: null },
    groupId: { type: Number, required: true },
})

const emit = defineEmits(['close'])

const propertyTypeOptions = [
    { id: 'text', name: 'Text' },
    { id: 'textarea', name: 'Textarea' },
    { id: 'number', name: 'Number' },
    { id: 'date', name: 'Date' },
    { id: 'checkbox', name: 'Checkbox' },
    { id: 'select', name: 'Selection' },
    { id: 'link', name: 'Link' },
    { id: 'upload', name: 'Upload' },
]

const form = useForm({
    crm_property_group_id: props.property?.crm_property_group_id ?? props.groupId,
    name: props.property?.name ?? '',
    type: props.property?.type ?? 'text',
    tooltip_text: props.property?.tooltip_text ?? '',
    select_values: props.property?.select_values ?? [],
})

const selectedPropertyType = computed({
    get: () => propertyTypeOptions.find(o => o.id === form.type) ?? null,
    set: (val) => { form.type = val?.id ?? 'text' },
})

const submit = () => {
    if (props.property) {
        form.patch(route('crm.properties.update', props.property.id), {
            onSuccess: () => emit('close'),
        })
    } else {
        form.post(route('crm.properties.store'), {
            onSuccess: () => emit('close'),
        })
    }
}
</script>
