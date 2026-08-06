<template>
    <div class="bg-white rounded-lg border border-border-subtle overflow-hidden">
        <!-- Group Header -->
        <button
            @click="expanded = !expanded"
            class="w-full flex items-center justify-between px-6 py-4 bg-surface-sunken hover:bg-surface-sunken transition-colors"
        >
            <div class="flex items-center gap-3">
                <PropertyIcon v-if="group.icon" :name="group.icon" class="h-5 w-5 text-text-subtle" />
                <h3 class="text-sm font-semibold text-text">{{ $t(group.name) }}</h3>
                <span v-if="group.is_confidential" class="inline-flex items-center rounded-full bg-warning-surface px-2 py-0.5 text-xs font-medium text-warning">
                    {{ $t('Confidential') }}
                </span>
            </div>
            <component :is="expanded ? IconChevronUp : IconChevronDown" class="h-5 w-5 text-text-subtle" />
        </button>

        <!-- Group Content -->
        <div v-if="expanded" class="px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="property in group.properties" :key="property.id">
                    <template v-if="editing">
                        <CrmPropertyValueInput
                            :property="property"
                            :value="getPropertyValue(property.id)"
                            :contact-id="contact.id"
                            :required="!!property.pivot?.is_required"
                            :error="errors[property.id] || ''"
                            @update:value="onUpdateValue(property.id, $event)"
                        />
                    </template>
                    <template v-else>
                        <div>
                            <dt class="flex items-center text-sm font-medium text-text-subtle">
                                <span>{{ $t(property.name) }}<span v-if="property.pivot?.is_required" class="text-danger ml-0.5">*</span></span>
                                <!-- tooltip_text ist Nutzerdaten-Text aus den CRM-Settings — bewusst ohne $t -->
                                <ToolTipComponent
                                    v-if="property.tooltip_text"
                                    class="ml-1.5"
                                    direction="right"
                                    :tooltip-text="property.tooltip_text"
                                    icon="IconInfoCircle"
                                    icon-size="h-4 w-4"
                                />
                            </dt>
                            <dd class="mt-1 text-sm text-text">
                                <template v-if="property.type === 'checkbox'">
                                    <component :is="getPropertyValue(property.id) === '1' ? IconCheck : IconX" class="h-5 w-5" :class="getPropertyValue(property.id) === '1' ? 'text-success' : 'text-text-subtle'" />
                                </template>
                                <template v-else-if="property.type === 'link' && getPropertyValue(property.id)">
                                    <a :href="getPropertyValue(property.id)" target="_blank" class="text-accent-600 hover:text-accent-600">
                                        {{ getPropertyValue(property.id) }}
                                    </a>
                                </template>
                                <template v-else-if="property.type === 'upload' && getPropertyValue(property.id)">
                                    <a :href="'/storage/' + getPropertyValue(property.id)" target="_blank" download class="inline-flex items-center gap-1.5 text-accent-700 hover:underline">
                                        <component :is="IconFile" class="h-4 w-4" />
                                        {{ getPropertyValue(property.id).split('/').pop() }}
                                    </a>
                                </template>
                                <template v-else-if="property.type === 'date' && getPropertyValue(property.id)">
                                    {{ formatDate(getPropertyValue(property.id)) }}
                                </template>
                                <template v-else>
                                    {{ getPropertyValue(property.id) || '-' }}
                                </template>
                            </dd>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'
import CrmPropertyValueInput from '@/Pages/CRM/Components/CrmPropertyValueInput.vue'
import { IconChevronUp, IconChevronDown, IconCheck, IconX, IconFile } from '@tabler/icons-vue'

const props = defineProps({
    group: { type: Object, required: true },
    contact: { type: Object, required: true },
    editing: { type: Boolean, default: false },
    errors: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['update-value', 'clear-error'])

const expanded = ref(true)

const formatDate = (value) => {
    if (!value) return '-'
    const date = new Date(value)
    if (isNaN(date.getTime())) return value
    const day = String(date.getDate()).padStart(2, '0')
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const year = date.getFullYear()
    return `${day}.${month}.${year}`
}

const getPropertyValue = (propertyId) => {
    const pv = props.contact.property_values?.find(v => v.crm_property_id === propertyId)
    return pv?.value ?? ''
}

const onUpdateValue = (propertyId, value) => {
    emit('update-value', { propertyId, value })
    emit('clear-error', propertyId)
}
</script>
