<template>
    <ArtworkBaseModal
        :title="property ? 'Edit article property' : 'Add article property'"
        modal-size="sm:max-w-2xl"
        @close="$emit('close')"
    >
        <form id="articlePropertyForm" @submit.prevent="addEditProperty">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div class="col-span-full">
                    <BaseInput
                        id="property-name"
                        v-model="propertyForm.name"
                        label="Name"
                        required
                        :error="propertyForm.errors.name"
                    />
                </div>

                <div class="col-span-full">
                    <BaseTextarea
                        id="property-tooltip-text"
                        v-model="propertyForm.tooltip_text"
                        label="Tooltip text"
                    />
                </div>

                <div v-if="!isSpecialProperty" class="col-span-full">
                    <ArtworkBaseListbox
                        v-model="selectedType"
                        :items="types"
                        by="type"
                        option-key="type"
                        option-label="name"
                        use-translations
                        label="Select a data type"
                    />
                </div>

                <div v-if="selectedType?.type === 'selection'" class="col-span-full space-y-2">
                    <div
                        v-for="(value, index) in propertyForm.select_values"
                        :key="index"
                        class="flex items-end gap-2"
                    >
                        <BaseInput
                            :id="'property-select-value-' + index"
                            v-model="propertyForm.select_values[index]"
                            :label="$t('Selection value {index}', {index: index + 1})"
                            without-translation
                        />
                        <BaseUIButton
                            variant="ghost"
                            size="sm"
                            icon="IconX"
                            label="Remove"
                            class="shrink-0"
                            @click="propertyForm.select_values.splice(index, 1)"
                        />
                    </div>

                    <div class="flex justify-end">
                        <BaseUIButton
                            variant="ghost"
                            size="sm"
                            icon="IconCirclePlus"
                            label="Selection Add value"
                            @click="propertyForm.select_values.push('')"
                        />
                    </div>
                </div>

                <div
                    v-for="option in checkboxOptions"
                    :key="option.field"
                    class="col-span-2 flex items-center gap-2"
                >
                    <BaseCheckbox
                        :id="'property-' + option.field"
                        v-model="propertyForm[option.field]"
                        :label="$t(option.label)"
                    />
                    <ToolTipComponent
                        icon="IconInfoCircle"
                        icon-size="size-4"
                        :tooltip-text="$t(option.tooltip)"
                        direction="right"
                    />
                </div>
            </div>
        </form>

        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="propertyForm.processing"
                @click="$emit('close')"
            />
            <BaseUIButton
                type="submit"
                form="articlePropertyForm"
                :label="property ? 'Update' : 'Create'"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="propertyForm.processing"
                :disabled="propertyForm.processing || checkIfPropertyHasValues"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, ref} from 'vue'
import {useForm} from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'

defineOptions({name: 'AddEditArticlePropertyModal'})

const props = defineProps({
    property: {
        type: Object,
        required: false,
        default: null,
    },
})

const emit = defineEmits(['close'])

const types = [
    {name: 'Text', type: 'text'},
    {name: 'Number', type: 'number'},
    {name: 'Date', type: 'date'},
    {name: 'Year', type: 'year'},
    {name: 'Time', type: 'time'},
    {name: 'Datetime', type: 'datetime-local'},
    {name: 'Checkbox', type: 'checkbox'},
    {name: 'Selection', type: 'selection'},
    {name: 'File', type: 'file'},
]

const checkboxOptions = [
    {field: 'is_filterable', label: 'Filterable', tooltip: 'Should it be possible to filter by this property?'},
    {field: 'show_in_list', label: 'Show in article overview', tooltip: 'Should this property be shown in the article overview?'},
    {field: 'is_required', label: 'Value required', tooltip: 'Does this value have to be provided when creating an article?'},
    {
        field: 'across_articles',
        label: 'across all articles',
        tooltip: 'Only relevant for single inventory capable articles. A property with this active will always be set for all articles and is made for properties that are the same for all articles.',
    },
    {
        field: 'individual_value',
        label: 'individual value',
        tooltip: 'If active, this property will not be duplicated when duplicating an article and individual values can not be edited with multiedit-functions. This is useful for properties like serial numbers that should be unique for each article.',
    },
]

// Raum und Hersteller sind Sondereigenschaften: ihr Typ ist fest
const isSpecialProperty = computed(() => props.property?.type === 'room' || props.property?.type === 'manufacturer')

const selectedType = ref(props.property
    ? (types.find(type => type.type === props.property.type) || {type: props.property.type, name: props.property.type})
    : types[0])

const propertyForm = useForm({
    id: props.property?.id ?? null,
    name: props.property?.name ?? '',
    tooltip_text: props.property?.tooltip_text ?? '',
    type: props.property?.type ?? types[0].type,
    is_filterable: Boolean(props.property?.is_filterable),
    show_in_list: Boolean(props.property?.show_in_list),
    is_required: Boolean(props.property?.is_required),
    across_articles: Boolean(props.property?.across_articles),
    individual_value: Boolean(props.property?.individual_value),
    select_values: Array.isArray(props.property?.select_values) ? [...props.property.select_values] : [],
})

function addEditProperty() {
    if (checkIfPropertyHasValues.value || propertyForm.processing) {
        return
    }

    propertyForm.type = isSpecialProperty.value ? props.property.type : selectedType.value?.type

    const options = {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    }

    if (props.property) {
        propertyForm.patch(route('inventory-management.settings.properties.update', {inventoryArticleProperty: props.property.id}), options)
    } else {
        propertyForm.post(route('inventory-management.settings.properties.create'), options)
    }
}

const checkIfPropertyHasValues = computed(() => {
    if (String(propertyForm.name ?? '').trim() === '') {
        return true
    }

    // Sondereigenschaften brauchen nur einen Namen
    if (isSpecialProperty.value) {
        return false
    }

    // Auswahlfelder brauchen mindestens einen Wert
    return selectedType.value?.type === 'selection'
        && (propertyForm.select_values.length === 0 || propertyForm.select_values[0] === '')
})
</script>
