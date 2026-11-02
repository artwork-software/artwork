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
                :items="availablePropertyTypeOptions"
                by="id"
                option-label="name"
                option-key="id"
                :label="$t('Type')"
                :use-translations="true"
                :placeholder="$t('Please select')"
            />
            <p v-if="form.errors.type" class="mt-1 text-xs text-danger">{{ form.errors.type }}</p>
            <BaseInput id="prop_tooltip" v-model="form.tooltip_text" :label="$t('Tooltip text')" />

            <!-- Mehrfachauswahl: Darstellung -->
            <div v-if="form.type === 'multiselect'" class="space-y-1">
                <ArtworkBaseListbox
                    v-model="selectedMultiselectDisplay"
                    :items="multiselectDisplayOptions"
                    by="id"
                    option-label="name"
                    option-key="id"
                    :label="$t('Display')"
                    :use-translations="true"
                />
                <p v-if="isTypeLockedToMultiselect" class="text-xs text-text-subtle">
                    {{ $t('A multiple choice property cannot be changed back to another type.') }}
                </p>
            </div>

            <!-- Select values input -->
            <div v-if="form.type === 'select' || form.type === 'multiselect'" class="space-y-2">
                <label class="componentLabel">{{ $t('Selection values') }}</label>
                <BaseSortableList v-model="form.select_values" labeled>
                    <template #default="{ index }">
                        <BaseInput
                            :id="'select_value_' + index"
                            v-model="form.select_values[index]"
                            :label="$t('Selection value {index}', { index: index + 1 })"
                        />
                    </template>
                    <template #actions="{ remove }">
                        <button type="button" class="rounded p-1 text-text-subtle hover:text-danger" :title="$t('Remove')" :aria-label="$t('Remove')" @click="remove">
                            <IconX class="h-4 w-4" />
                        </button>
                    </template>
                </BaseSortableList>
                <button type="button" @click="form.select_values.push('')" class="text-sm text-accent-600 hover:text-accent-600 flex items-center gap-1">
                    <IconCirclePlus class="h-4 w-4" />
                    {{ $t('Add option') }}
                </button>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <BaseUIButton variant="secondary" hide-icon @click="$emit('close')">{{ $t('Cancel') }}</BaseUIButton>
                <BaseUIButton variant="primary" hide-icon @click="submit" :disabled="form.processing">
                    {{ property ? $t('Save') : $t('Create') }}
                </BaseUIButton>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseSortableList from '@/Artwork/Inputs/BaseSortableList.vue'
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue'
import { IconX, IconCirclePlus } from '@tabler/icons-vue'

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
    { id: 'multiselect', name: 'Multiple choice' },
    { id: 'link', name: 'Link' },
    { id: 'upload', name: 'Upload' },
]

const form = useForm({
    crm_property_group_id: props.property?.crm_property_group_id ?? props.groupId,
    name: props.property?.name ?? '',
    type: props.property?.type ?? 'text',
    tooltip_text: props.property?.tooltip_text ?? '',
    select_values: props.property?.select_values ?? [],
    multiselect_display: props.property?.multiselect_display ?? 'checkboxes',
})

const multiselectDisplayOptions = [
    { id: 'checkboxes', name: 'Checkboxes' },
    { id: 'dropdown', name: 'Dropdown with multiple selection' },
]

const selectedMultiselectDisplay = computed({
    get: () => multiselectDisplayOptions.find(o => o.id === form.multiselect_display) ?? multiselectDisplayOptions[0],
    set: (val) => { form.multiselect_display = val?.id ?? 'checkboxes' },
})

// Mehrfachauswahl nur neu oder aus einer Einfachauswahl; eine bestehende Mehrfachauswahl bleibt es
// (Gegenstück zu CrmPropertyService::assertTypeChangeKeepsValues)
const isTypeLockedToMultiselect = computed(() => props.property?.type === 'multiselect')
const availablePropertyTypeOptions = computed(() => {
    if (isTypeLockedToMultiselect.value) {
        return propertyTypeOptions.filter((option) => option.id === 'multiselect')
    }
    if (props.property && props.property.type !== 'select') {
        return propertyTypeOptions.filter((option) => option.id !== 'multiselect')
    }
    return propertyTypeOptions
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
