<template>
    <div v-if="fields.length > 0">
        <div class="space-y-3">
            <div v-for="field in fields" :key="field.id">
                <!-- TextField -->
                <div v-if="field.type === 'TextField'">
                    <BaseInput
                        :id="'bi_custom_' + field.id"
                        :model-value="getTextValue(field.id)"
                        @update:model-value="val => saveTextField(field.id, val)"
                        @blur="saveTextField(field.id, getTextValue(field.id))"
                        :label="field.name"
                        :disabled="!canEdit"
                    />
                </div>

                <!-- TextArea -->
                <div v-else-if="field.type === 'TextArea'">
                    <BaseTextarea
                        :id="'bi_custom_' + field.id"
                        :model-value="getTextValue(field.id)"
                        @update:model-value="val => localValues[field.id] = { text: val }"
                        @blur="saveTextField(field.id, getTextValue(field.id))"
                        :label="field.name"
                        :rows="3"
                        :disabled="!canEdit"
                    />
                </div>

                <!-- Checkbox -->
                <div v-else-if="field.type === 'Checkbox'" class="flex items-center gap-x-3">
                    <input
                        type="checkbox"
                        :id="'bi_custom_' + field.id"
                        :checked="getCheckedValue(field.id)"
                        @change="saveCheckbox(field.id, $event.target.checked)"
                        class="input-checklist"
                        :disabled="!canEdit"
                    />
                    <label :for="'bi_custom_' + field.id" class="text-sm text-gray-700">{{ field.name }}</label>
                </div>

                <!-- DropDown -->
                <div v-else-if="field.type === 'DropDown'">
                    <ArtworkBaseListbox
                        :model-value="getSelectedValue(field.id)"
                        @update:model-value="val => saveDropdown(field.id, val)"
                        :items="getDropdownOptions(field)"
                        by="value"
                        option-label="value"
                        :label="field.name"
                        :disabled="!canEdit"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive } from 'vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue';
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue';

const props = defineProps({
    fields: { type: Array, default: () => [] },
    fieldValues: { type: Object, default: () => ({}) },
    canEdit: { type: Boolean, default: false },
    projectId: { type: [Number, String], required: true },
});

const emit = defineEmits(['updated']);

// Local reactive copy of values keyed by component_id
const localValues = reactive({});

// Initialize local values from props
for (const [componentId, record] of Object.entries(props.fieldValues)) {
    localValues[componentId] = record.data || {};
}

function getTextValue(fieldId) {
    return localValues[fieldId]?.text ?? '';
}

function getCheckedValue(fieldId) {
    return localValues[fieldId]?.checked ?? false;
}

function getSelectedValue(fieldId) {
    const val = localValues[fieldId]?.selected;
    if (!val) return null;
    return { value: val };
}

function getDropdownOptions(field) {
    return field.data?.options || [];
}

async function saveTextField(fieldId, value) {
    localValues[fieldId] = { text: value };
    await saveValue(fieldId, { text: value });
}

async function saveCheckbox(fieldId, checked) {
    localValues[fieldId] = { checked };
    await saveValue(fieldId, { checked });
}

async function saveDropdown(fieldId, item) {
    const value = item?.value ?? item;
    localValues[fieldId] = { selected: value };
    await saveValue(fieldId, { selected: value });
}

async function saveValue(componentId, data) {
    try {
        await axios.patch(
            route('project.tab.component.update', {
                project: props.projectId,
                component: componentId,
            }),
            { data }
        );
        emit('updated');
    } catch (error) {
        console.error('Error saving BI custom field:', error);
    }
}
</script>
