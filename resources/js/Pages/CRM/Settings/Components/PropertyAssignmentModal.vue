<template>
    <ArtworkBaseModal
        :title="$t('Edit property assignment')"
        :description="$t('Manage which properties are assigned to this contact type.')"
        @close="$emit('close')"
    >
        <div class="mt-4 space-y-4">
            <div v-for="group in displayedGroups" :key="group.id" class="border border-gray-200 rounded-lg">
                <div class="px-4 py-3 bg-gray-50 rounded-t-lg flex items-center justify-between">
                    <span class="font-medium text-sm">{{ $t(group.name) }}</span>
                    <ToolTipComponent
                        v-if="contactType.is_system && group.is_system"
                        direction="left"
                        :tooltip-text="$t('This group is relevant for artwork functions and cannot be removed')"
                        icon="IconInfoCircle"
                        icon-size="h-4 w-4"
                    />
                    <button
                        v-else
                        @click="removeGroup(group)"
                        class="text-gray-400 hover:text-red-600"
                    >
                        <component :is="IconX" class="h-4 w-4" />
                    </button>
                </div>
                <div class="divide-y">
                    <div v-for="property in group.properties" :key="property.id" class="px-4 py-2">
                        <div class="flex items-center gap-3">
                            <template v-if="contactType.is_system && property.is_system && checkedPropertyIds.has(property.id)">
                                <ToolTipComponent
                                    direction="right"
                                    :tooltip-text="$t('This property is relevant for artwork functions and cannot be deselected')"
                                    icon="IconInfoCircle"
                                    icon-size="h-4 w-4"
                                />
                            </template>
                            <template v-else>
                                <input
                                    type="checkbox"
                                    :checked="checkedPropertyIds.has(property.id)"
                                    @change="toggleProperty(property.id)"
                                    class="rounded border-gray-300 text-indigo-600"
                                />
                            </template>
                            <span class="text-sm">{{ property.name }}</span>
                        </div>
                        <!-- Pivot options, only visible when property is checked -->
                        <div v-if="checkedPropertyIds.has(property.id)" class="ml-7 mt-1 flex items-center gap-4">
                            <label class="flex items-center gap-1 text-xs cursor-pointer">
                                <input type="checkbox" v-model="pivotData[property.id].is_required" class="rounded border-gray-300 text-indigo-600 h-3.5 w-3.5" />
                                {{ $t('Mandatory field') }}
                            </label>
                            <label class="flex items-center gap-1 text-xs cursor-pointer">
                                <input type="checkbox" v-model="pivotData[property.id].show_in_list" class="rounded border-gray-300 text-indigo-600 h-3.5 w-3.5" />
                                {{ $t('Show in list view') }}
                            </label>
                            <label v-if="property.type !== 'upload'" class="flex items-center gap-1 text-xs cursor-pointer">
                                <input type="checkbox" v-model="pivotData[property.id].is_filterable" class="rounded border-gray-300 text-indigo-600 h-3.5 w-3.5" />
                                {{ $t('Filterable') }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add group dropdown -->
            <div v-if="unassignedGroups.length" class="flex items-center gap-2">
                <ArtworkBaseListbox
                    v-model="selectedGroupToAdd"
                    :items="unassignedGroups"
                    by="id"
                    option-label="name"
                    option-key="id"
                    :placeholder="$t('Add property group')"
                    :use-translations="true"
                    class="w-full"
                />
                <button
                    @click="addGroup"
                    :disabled="!selectedGroupToAdd"
                    class="ui-button-add shrink-0"
                >
                    <component :is="IconPlus" class="h-4 w-4" />
                </button>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button class="ui-button-cancel" @click="$emit('close')">{{ $t('Cancel') }}</button>
                <button class="ui-button-add" @click="save" :disabled="saving">
                    {{ $t('Save') }}
                </button>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'
import { IconX, IconPlus } from '@tabler/icons-vue'

const props = defineProps({
    contactType: { type: Object, required: true },
    propertyGroups: { type: Array, required: true },
})

const emit = defineEmits(['close'])

const saving = ref(false)

// Build initial pivot data from existing assignments
const initialPivotData = {}
const assignedPropertyIds = []
for (const p of (props.contactType.properties ?? [])) {
    assignedPropertyIds.push(p.id)
    initialPivotData[p.id] = {
        is_required: p.pivot?.is_required == true,
        show_in_list: p.pivot?.show_in_list == true,
        is_filterable: p.pivot?.is_filterable == true,
    }
}

// Also pre-populate defaults for all properties so toggling works smoothly
for (const group of props.propertyGroups) {
    for (const p of (group.properties ?? [])) {
        if (!initialPivotData[p.id]) {
            initialPivotData[p.id] = {
                is_required: false,
                show_in_list: false,
                is_filterable: false,
            }
        }
    }
}

const checkedPropertyIds = ref(new Set(assignedPropertyIds))
const pivotData = reactive(initialPivotData)

const removedGroupIds = ref(new Set())

const displayedGroups = computed(() => {
    return props.propertyGroups.filter(group => {
        if (removedGroupIds.value.has(group.id)) {
            return false
        }
        const hasAssignedProperty = (group.properties ?? []).some(p => checkedPropertyIds.value.has(p.id))
        return hasAssignedProperty || (props.contactType.is_system && group.is_system)
    })
})

const unassignedGroups = computed(() => {
    const displayedIds = new Set(displayedGroups.value.map(g => g.id))
    return props.propertyGroups.filter(g => !displayedIds.has(g.id))
})

const selectedGroupToAdd = ref(null)

const toggleProperty = (propertyId) => {
    if (checkedPropertyIds.value.has(propertyId)) {
        checkedPropertyIds.value.delete(propertyId)
    } else {
        checkedPropertyIds.value.add(propertyId)
    }
}

const removeGroup = (group) => {
    (group.properties ?? []).forEach(p => {
        checkedPropertyIds.value.delete(p.id)
    })
    removedGroupIds.value.add(group.id)
}

const addGroup = () => {
    if (!selectedGroupToAdd.value) return
    const group = selectedGroupToAdd.value
    ;(group.properties ?? []).forEach(p => {
        checkedPropertyIds.value.add(p.id)
    })
    removedGroupIds.value.delete(group.id)
    selectedGroupToAdd.value = null
}

const allProperties = computed(() =>
    props.propertyGroups.flatMap(g => g.properties ?? [])
)

const save = () => {
    saving.value = true

    const properties = [...checkedPropertyIds.value].map(id => {
        const prop = allProperties.value.find(p => p.id === id)
        return {
            id,
            is_required: pivotData[id]?.is_required ?? false,
            show_in_list: pivotData[id]?.show_in_list ?? false,
            is_filterable: prop?.type === 'upload' ? false : (pivotData[id]?.is_filterable ?? false),
        }
    })

    router.patch(route('crm.types.sync-properties', props.contactType.id), {
        properties,
    }, {
        preserveScroll: true,
        onSuccess: () => emit('close'),
        onFinish: () => saving.value = false,
    })
}
</script>
