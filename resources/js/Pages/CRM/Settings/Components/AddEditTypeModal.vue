<template>
    <ArtworkBaseModal
        :title="contactType ? $t('Edit contact type') : $t('New contact type')"
        :description="$t('Define the contact type and select which property groups apply to it.')"
        modal-size="sm:max-w-3xl"
        @close="$emit('close')"
    >
        <div class="mt-4 space-y-6">
            <!-- Basics -->
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

            <!-- Property group assignment -->
            <div>
                <div class="flex items-baseline justify-between">
                    <h3 class="text-sm font-semibold text-text">{{ $t('Property groups') }}</h3>
                    <span v-if="propertyGroups.length" class="text-xs text-text-subtle">
                        {{ $t('{count} of {total} groups assigned', { count: assignedGroupCount, total: propertyGroups.length }) }}
                    </span>
                </div>
                <p class="text-xs text-text-subtle mt-0.5 mb-3">
                    {{ $t('Assigned groups define which fields contacts of this type have. Expand a group to fine-tune individual properties.') }}
                    {{ $t('Drag groups to define their order on the contact page of this type.') }}
                </p>

                <!-- Explanation of the three per-property pivot flags -->
                <SettingsGuideBanner
                    variant="static"
                    title="What the options per property mean"
                    :paragraphs="flagInfoParagraphs"
                    class="mb-3"
                />

                <div v-if="!propertyGroups.length" class="rounded-lg border border-dashed border-border bg-surface-sunken p-4 text-sm text-text-subtle">
                    {{ $t('No property groups exist yet. Create a property group first — afterwards you can assign it to this contact type.') }}
                </div>

                <draggable
                    v-else
                    :list="localGroups"
                    item-key="id"
                    ghost-class="opacity-50"
                    handle=".group-order-handle"
                    class="space-y-2 max-h-[45vh] overflow-y-auto pr-1"
                >
                    <template #item="{ element: group }">
                    <div
                        class="rounded-lg border transition-colors"
                        :class="isGroupAssigned(group) ? 'border-accent-200 bg-white' : 'border-border-subtle bg-surface-sunken/70'"
                    >
                        <!-- Group header -->
                        <div
                            class="px-4 py-3 flex items-center gap-3 cursor-pointer select-none"
                            @click="toggleExpanded(group.id)"
                        >
                            <component
                                :is="IconGripVertical"
                                class="group-order-handle h-4 w-4 text-text-subtle hover:text-text-subtle cursor-grab shrink-0"
                                @click.stop
                            />
                            <ToolTipComponent
                                v-if="isGroupLocked(group)"
                                direction="right"
                                :tooltip-text="$t('This group is relevant for artwork functions and cannot be removed')"
                                icon="IconLock"
                                icon-size="h-4 w-4"
                                @click.stop
                            />
                            <input
                                v-else
                                type="checkbox"
                                class="rounded border-border text-accent-600 shrink-0"
                                :checked="isGroupFullyChecked(group)"
                                :indeterminate="isGroupPartiallyChecked(group)"
                                :disabled="!(group.properties?.length)"
                                @click.stop
                                @change="toggleGroup(group)"
                            />
                            <span class="text-sm font-medium truncate" :class="isGroupAssigned(group) ? 'text-text' : 'text-text-subtle'">
                                {{ $t(group.name) }}
                            </span>
                            <span v-if="group.is_confidential" class="inline-flex items-center rounded-full bg-warning-surface px-2 py-0.5 text-xs font-medium text-warning shrink-0">
                                {{ $t('Confidential') }}
                            </span>
                            <span class="ml-auto text-xs tabular-nums shrink-0" :class="isGroupAssigned(group) ? 'text-accent-600 font-medium' : 'text-text-subtle'">
                                {{ checkedCount(group) }}/{{ group.properties?.length ?? 0 }} {{ $t('Properties') }}
                            </span>
                            <component
                                :is="IconChevronDown"
                                class="h-4 w-4 text-text-subtle transition-transform shrink-0"
                                :class="expandedGroupIds.has(group.id) ? 'rotate-180' : ''"
                            />
                        </div>

                        <!-- Group properties -->
                        <div v-if="expandedGroupIds.has(group.id)" class="border-t border-border-subtle divide-y divide-border-subtle">
                            <div v-for="property in group.properties" :key="property.id" class="px-4 py-2 pl-11">
                                <div class="flex items-center gap-3">
                                    <ToolTipComponent
                                        v-if="isPropertyLocked(property)"
                                        direction="right"
                                        :tooltip-text="$t('This property is relevant for artwork functions and cannot be deselected')"
                                        icon="IconLock"
                                        icon-size="h-4 w-4"
                                    />
                                    <input
                                        v-else
                                        type="checkbox"
                                        class="rounded border-border text-accent-600"
                                        :checked="checkedPropertyIds.has(property.id)"
                                        @change="toggleProperty(property)"
                                    />
                                    <span class="text-sm">{{ property.name }}</span>
                                    <span class="text-xs text-text-subtle bg-surface-sunken rounded px-2 py-0.5">
                                        {{ $t(propertyTypeLabels[property.type] ?? property.type) }}
                                    </span>
                                </div>
                                <div v-if="checkedPropertyIds.has(property.id)" class="ml-7 mt-1 flex items-center gap-4">
                                    <label class="flex items-center gap-1 text-xs cursor-pointer text-text-muted">
                                        <input type="checkbox" v-model="pivotData[property.id].is_required" class="rounded border-border text-accent-600 h-3.5 w-3.5" />
                                        {{ $t('Mandatory field') }}
                                    </label>
                                    <label class="flex items-center gap-1 text-xs cursor-pointer text-text-muted">
                                        <input type="checkbox" v-model="pivotData[property.id].show_in_list" class="rounded border-border text-accent-600 h-3.5 w-3.5" />
                                        {{ $t('Show in list view') }}
                                    </label>
                                    <label v-if="property.type !== 'upload'" class="flex items-center gap-1 text-xs cursor-pointer text-text-muted">
                                        <input type="checkbox" v-model="pivotData[property.id].is_filterable" class="rounded border-border text-accent-600 h-3.5 w-3.5" />
                                        {{ $t('Filterable') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    </template>
                </draggable>
            </div>

            <div class="flex justify-end gap-3">
                <BaseUIButton variant="secondary" hide-icon @click="$emit('close')">{{ $t('Cancel') }}</BaseUIButton>
                <BaseUIButton variant="primary" hide-icon @click="submit" :disabled="form.processing">
                    {{ contactType ? $t('Save') : $t('Create') }}
                </BaseUIButton>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import draggable from 'vuedraggable'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import SettingsGuideBanner from '@/Artwork/Guide/SettingsGuideBanner.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import IconSelector from '@/Components/Icon/IconSelector.vue'
import ColorPickerComponent from '@/Components/Globale/ColorPickerComponent.vue'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'
import { IconChevronDown, IconGripVertical } from '@tabler/icons-vue'
import { useTranslation } from '@/Composeables/Translation.js'

const $t = useTranslation()

const props = defineProps({
    contactType: { type: Object, default: null },
    propertyGroups: { type: Array, required: true },
})

const emit = defineEmits(['close'])

const propertyTypeLabels = {
    text: 'Text',
    textarea: 'Textarea',
    number: 'Number',
    date: 'Date',
    checkbox: 'Checkbox',
    select: 'Selection',
    link: 'Link',
    upload: 'Upload',
}

const flagInfoParagraphs = [
    'Mandatory field: the property must be filled when creating or editing a contact and is marked with a red asterisk.',
    'Show in list view: the property gets its own sortable column in the contact list.',
    'Filterable: the property appears in the filter of the contact list.',
]

const form = useForm({
    name: props.contactType?.name ?? '',
    icon: props.contactType?.icon ?? '',
    color: props.contactType?.color ?? '#0d6be3',
    properties: [],
})

// -- Assignment state, derived per property like the previous PropertyAssignmentModal --
const initialPivotData = {}
const assignedPropertyIds = []
for (const p of (props.contactType?.properties ?? [])) {
    assignedPropertyIds.push(p.id)
    initialPivotData[p.id] = {
        is_required: p.pivot?.is_required == true,
        show_in_list: p.pivot?.show_in_list == true,
        is_filterable: p.pivot?.is_filterable == true,
    }
}
for (const group of props.propertyGroups) {
    for (const p of (group.properties ?? [])) {
        if (!initialPivotData[p.id]) {
            initialPivotData[p.id] = { is_required: false, show_in_list: false, is_filterable: false }
        }
    }
}

const checkedPropertyIds = ref(new Set(assignedPropertyIds))
const pivotData = reactive(initialPivotData)
const expandedGroupIds = ref(new Set())

// Lokale, sortierbare Kopie der Gruppen: zugewiesene zuerst in der Typ-Reihenfolge
// (abgeleitet aus der Pivot-Sortierung von contactType.properties), Rest global.
const typeGroupOrder = new Map()
;(props.contactType?.properties ?? []).forEach((p, index) => {
    if (!typeGroupOrder.has(p.crm_property_group_id)) {
        typeGroupOrder.set(p.crm_property_group_id, index)
    }
})

const localGroups = ref(
    [...props.propertyGroups].sort((a, b) => {
        const ai = typeGroupOrder.has(a.id) ? typeGroupOrder.get(a.id) : Number.MAX_SAFE_INTEGER
        const bi = typeGroupOrder.has(b.id) ? typeGroupOrder.get(b.id) : Number.MAX_SAFE_INTEGER
        return ai - bi
    })
)

const isGroupLocked = (group) =>
    !!props.contactType?.is_system && !!group.is_system

const isPropertyLocked = (property) =>
    !!props.contactType?.is_system && !!property.is_system && checkedPropertyIds.value.has(property.id)

const checkedCount = (group) =>
    (group.properties ?? []).filter(p => checkedPropertyIds.value.has(p.id)).length

const isGroupAssigned = (group) => checkedCount(group) > 0

const isGroupFullyChecked = (group) =>
    (group.properties?.length ?? 0) > 0 && checkedCount(group) === group.properties.length

const isGroupPartiallyChecked = (group) => {
    const count = checkedCount(group)
    return count > 0 && count < (group.properties?.length ?? 0)
}

const assignedGroupCount = computed(() =>
    props.propertyGroups.filter(g => isGroupAssigned(g)).length
)

const toggleExpanded = (groupId) => {
    if (expandedGroupIds.value.has(groupId)) {
        expandedGroupIds.value.delete(groupId)
    } else {
        expandedGroupIds.value.add(groupId)
    }
}

const toggleGroup = (group) => {
    if (isGroupFullyChecked(group)) {
        // Locked (system) properties stay assigned
        for (const p of (group.properties ?? [])) {
            if (!isPropertyLocked(p)) {
                checkedPropertyIds.value.delete(p.id)
            }
        }
    } else {
        for (const p of (group.properties ?? [])) {
            checkedPropertyIds.value.add(p.id)
        }
        expandedGroupIds.value.add(group.id)
    }
}

const toggleProperty = (property) => {
    if (isPropertyLocked(property)) {
        return
    }
    if (checkedPropertyIds.value.has(property.id)) {
        checkedPropertyIds.value.delete(property.id)
    } else {
        checkedPropertyIds.value.add(property.id)
    }
}

const submit = () => {
    // Reihenfolge der (gedraggten) Gruppen in fortlaufende Pivot-sort_order übersetzen
    let sortCounter = 0
    form.properties = []
    for (const group of localGroups.value) {
        for (const prop of (group.properties ?? [])) {
            if (!checkedPropertyIds.value.has(prop.id)) continue
            form.properties.push({
                id: prop.id,
                is_required: pivotData[prop.id]?.is_required ?? false,
                show_in_list: pivotData[prop.id]?.show_in_list ?? false,
                is_filterable: prop.type === 'upload' ? false : (pivotData[prop.id]?.is_filterable ?? false),
                sort_order: ++sortCounter,
            })
        }
    }

    if (props.contactType) {
        form.patch(route('crm.types.update', props.contactType.id), {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        })
    } else {
        form.post(route('crm.types.store'), {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        })
    }
}
</script>
