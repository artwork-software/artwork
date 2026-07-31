<template>
    <AppLayout :title="$t('CRM Settings')">
        <div class="mt-5 mx-auto container pb-20">
            <ToolbarHeader
                :icon="IconSettings"
                :title="$t('CRM Settings')"
                icon-bg-class="bg-indigo-600/10 text-indigo-700"
                :search-enabled="false"
            >
                <template #actions>
                    <Link :href="route('crm.import')" class="ui-button">
                        {{ $t('Import contacts') }}
                    </Link>
                    <Link :href="route('crm.index')" class="ui-button">
                        {{ $t('Back to CRM') }}
                    </Link>
                </template>
            </ToolbarHeader>

            <!-- Workflow explanation -->
            <div class="mt-6 rounded-2xl border border-indigo-100 bg-indigo-50/50">
                <button
                    type="button"
                    class="w-full px-5 py-4 flex items-center gap-3 text-left"
                    @click="toggleWorkflowHelp"
                >
                    <component :is="IconInfoCircle" class="h-5 w-5 text-indigo-600 shrink-0" />
                    <span class="text-sm font-semibold text-gray-900">{{ $t('How the CRM setup works') }}</span>
                    <component
                        :is="IconChevronDown"
                        class="h-4 w-4 text-gray-400 ml-auto transition-transform shrink-0"
                        :class="workflowHelpCollapsed ? '' : 'rotate-180'"
                    />
                </button>
                <div v-if="!workflowHelpCollapsed" class="px-5 pb-5">
                    <div class="grid gap-3 md:grid-cols-4">
                        <div v-for="(step, index) in workflowSteps" :key="index" class="rounded-xl bg-white border border-gray-100 p-4 flex flex-col">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="flex items-center justify-center size-6 rounded-full bg-indigo-600 text-white text-xs font-bold shrink-0">{{ index + 1 }}</span>
                                <span class="text-sm font-medium text-gray-900">{{ $t(step.title) }}</span>
                            </div>
                            <p class="text-xs text-gray-500 grow">{{ $t(step.text) }}</p>
                            <button
                                v-if="step.action"
                                type="button"
                                class="mt-3 text-xs font-medium text-indigo-600 hover:text-indigo-500 text-left flex items-center gap-1"
                                @click="step.action()"
                            >
                                <component :is="IconCirclePlus" class="h-3.5 w-3.5" />
                                {{ $t(step.actionLabel) }}
                            </button>
                            <Link
                                v-else-if="step.href"
                                :href="step.href"
                                class="mt-3 text-xs font-medium text-indigo-600 hover:text-indigo-500 flex items-center gap-1"
                            >
                                <component :is="IconArrowRight" class="h-3.5 w-3.5" />
                                {{ $t(step.actionLabel) }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 -translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-1"
            >
                <div v-if="successMessage" class="mt-4 rounded-md bg-green-50 p-3">
                    <p class="text-sm font-medium text-green-800">{{ successMessage }}</p>
                </div>
            </Transition>

            <!-- Property Groups -->
            <section class="mt-8 mb-10">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold">{{ $t('Property groups') }}</h2>
                        <p class="text-sm text-gray-500">{{ $t('Organize properties into groups. Confidential groups require permissions to view.') }}</p>
                    </div>
                    <button class="ui-button-add" @click="showGroupModal = true">
                        <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                        {{ $t('New group') }}
                    </button>
                </div>

                <div v-if="!localPropertyGroups.length" class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-10 text-center">
                    <component :is="IconLayoutGrid" class="mx-auto h-10 w-10 text-gray-300" />
                    <p class="mt-3 text-sm font-medium text-gray-900">{{ $t('No property groups yet') }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ $t('Start here: property groups bundle the properties your contacts share, e.g. address or conditions.') }}</p>
                    <button class="ui-button-add mx-auto mt-4" @click="showGroupModal = true">
                        <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                        {{ $t('New group') }}
                    </button>
                </div>

                <draggable
                    v-else
                    :list="localPropertyGroups"
                    item-key="id"
                    ghost-class="opacity-50"
                    handle=".group-drag-handle"
                    class="space-y-4"
                    @start="dragging = true"
                    @end="dragging = false"
                    @change="onGroupDragChange"
                >
                    <template #item="{ element: group }">
                        <div class="bg-white border border-gray-200 rounded-lg">
                            <div class="px-4 py-4 flex items-center justify-between bg-gray-50 rounded-t-lg">
                                <div class="flex items-center gap-3 min-w-0">
                                    <component :is="IconGripVertical" class="group-drag-handle h-4 w-4 text-gray-300 hover:text-gray-500 shrink-0" :class="dragging ? 'cursor-grabbing' : 'cursor-grab'" />
                                    <span class="font-medium">{{ $t(group.name) }}</span>
                                    <span v-if="group.is_confidential" class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">
                                        {{ $t('Confidential') }}
                                    </span>
                                    <span v-if="group.is_system" class="text-xs text-gray-400">({{ $t('System') }})</span>
                                    <!-- Which contact types use this group -->
                                    <div class="hidden lg:flex items-center gap-1.5 ml-3 min-w-0">
                                        <template v-if="typesUsingGroup(group).length">
                                            <span class="text-xs text-gray-400 shrink-0">{{ $t('Used by') }}:</span>
                                            <span
                                                v-for="type in typesUsingGroup(group)"
                                                :key="type.id"
                                                class="inline-flex items-center gap-1 rounded-full bg-white border border-gray-200 px-2 py-0.5 text-xs text-gray-600 shrink-0"
                                            >
                                                <PropertyIcon v-if="type.icon" :name="type.icon" class="h-3 w-3" :style="type.color ? { color: type.color } : {}" />
                                                {{ $t(type.name) }}
                                            </span>
                                        </template>
                                        <span v-else class="text-xs text-gray-400 italic">{{ $t('Not assigned to any contact type yet') }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button @click="editingGroup = group; showGroupModal = true" class="text-gray-400 hover:text-gray-600">
                                        <component :is="IconEdit" class="h-4 w-4" />
                                    </button>
                                    <button v-if="!group.is_system" @click="openDeleteGroupModal(group)" class="text-gray-400 hover:text-red-600">
                                        <component :is="IconTrash" class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <!-- Properties in group (draggable) -->
                            <draggable
                                v-if="group.properties?.length"
                                :list="group.properties"
                                item-key="id"
                                ghost-class="opacity-50"
                                handle=".drag-handle"
                                class="divide-y"
                                @start="dragging = true"
                                @end="dragging = false"
                                @change="onPropertyDragChange(group)"
                            >
                                <template #item="{ element: property }">
                                    <div class="px-6 py-3 flex items-center justify-between group">
                                        <div class="flex items-center gap-3">
                                            <component :is="IconGripVertical" class="drag-handle h-4 w-4 text-gray-300 hover:text-gray-500" :class="dragging ? 'cursor-grabbing' : 'cursor-grab'" />
                                            <span class="text-sm">{{ property.name }}</span>
                                            <span class="text-xs text-gray-400 bg-gray-100 rounded px-2 py-0.5">{{ $t(propertyTypeLabels[property.type] ?? property.type) }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button v-if="!property.is_system" @click="editingProperty = property; activeGroupForProperty = group; showPropertyModal = true" class="text-gray-400 hover:text-gray-600">
                                                <component :is="IconEdit" class="h-4 w-4" />
                                            </button>
                                            <button v-if="!property.is_system" @click="openDeletePropertyModal(property)" class="text-gray-400 hover:text-red-600">
                                                <component :is="IconTrash" class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </draggable>

                            <!-- Add property button -->
                            <div class="px-6 py-3 border-t">
                                <button @click="activeGroupForProperty = group; showPropertyModal = true" class="text-sm text-indigo-600 hover:text-indigo-500 flex items-center gap-1">
                                    <component :is="IconCirclePlus" class="h-4 w-4" />
                                    {{ $t('Add property') }}
                                </button>
                            </div>
                        </div>
                    </template>
                </draggable>
            </section>

            <!-- Contact Types -->
            <section class="mb-10">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold">{{ $t('Contact types') }}</h2>
                        <p class="text-sm text-gray-500">{{ $t('Manage the types of contacts in your CRM.') }}</p>
                    </div>
                    <button class="ui-button-add" @click="showTypeModal = true">
                        <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                        {{ $t('New type') }}
                    </button>
                </div>

                <div v-if="!localContactTypes.length" class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-10 text-center">
                    <component :is="IconAddressBook" class="mx-auto h-10 w-10 text-gray-300" />
                    <p class="mt-3 text-sm font-medium text-gray-900">{{ $t('No contact types yet') }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ $t('Create your first contact type to categorize CRM contacts. You can assign property groups directly while creating it.') }}</p>
                    <button class="ui-button-add mx-auto mt-4" @click="showTypeModal = true">
                        <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                        {{ $t('New type') }}
                    </button>
                </div>

                <div v-else class="bg-white border border-gray-200 rounded-lg">
                    <draggable
                        :list="localContactTypes"
                        item-key="id"
                        ghost-class="opacity-50"
                        handle=".type-drag-handle"
                        class="divide-y"
                        @start="dragging = true"
                        @end="dragging = false"
                        @change="onTypeDragChange"
                    >
                        <template #item="{ element: type }">
                            <div class="px-4 py-4 flex items-center justify-between">
                                <div class="flex items-center gap-3 min-w-0">
                                    <component :is="IconGripVertical" class="type-drag-handle h-4 w-4 text-gray-300 hover:text-gray-500 shrink-0" :class="dragging ? 'cursor-grabbing' : 'cursor-grab'" />
                                    <span
                                        v-if="type.icon"
                                        class="inline-flex size-7 items-center justify-center rounded shrink-0"
                                        :style="type.color ? { backgroundColor: type.color + '20', color: type.color } : {}"
                                    >
                                        <PropertyIcon :name="type.icon" class="h-5 w-5" />
                                    </span>
                                    <span class="font-medium">{{ $t(type.name) }}</span>
                                    <span v-if="type.is_system" class="text-xs text-gray-400">({{ $t('System') }})</span>
                                    <span class="text-xs text-gray-400 ml-2">
                                        {{ $t('{groups} groups · {properties} properties', { groups: assignedGroupCount(type), properties: (type.properties?.length ?? 0) }) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <ToolTipComponent
                                        direction="left"
                                        :tooltip-text="$t('Edit contact type and property assignment')"
                                        icon="IconEdit"
                                        icon-size="h-4 w-4"
                                        @click="editingType = type; showTypeModal = true"
                                    />
                                    <button v-if="!type.is_system" @click="openDeleteTypeModal(type)" class="text-gray-400 hover:text-red-600">
                                        <component :is="IconTrash" class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </template>
                    </draggable>
                </div>
            </section>
        </div>

        <!-- Type Modal (basics + property group assignment) -->
        <AddEditTypeModal
            v-if="showTypeModal"
            :contact-type="editingType"
            :property-groups="localPropertyGroups"
            @close="showTypeModal = false; editingType = null"
        />

        <!-- Group Modal -->
        <AddEditGroupModal
            v-if="showGroupModal"
            :property-group="editingGroup"
            @close="showGroupModal = false; editingGroup = null"
        />

        <!-- Property Modal -->
        <AddEditPropertyModal
            v-if="showPropertyModal"
            :property="editingProperty"
            :group-id="activeGroupForProperty?.id"
            @close="showPropertyModal = false; editingProperty = null; activeGroupForProperty = null"
        />

        <!-- Delete Confirmation Modals -->
        <ConfirmDeleteModal
            v-if="showDeleteTypeModal"
            @close="showDeleteTypeModal = false"
            :title="$t('Delete contact type')"
            :description="$t('Are you sure you want to delete this contact type?')"
            @delete="deleteType"
        />

        <ConfirmDeleteModal
            v-if="showDeleteGroupModal"
            @close="showDeleteGroupModal = false"
            :title="$t('Delete property group')"
            :description="$t('Are you sure you want to delete this property group?')"
            @delete="deleteGroup"
        />

        <ConfirmDeleteModal
            v-if="showDeletePropertyModal"
            @close="showDeletePropertyModal = false"
            :title="$t('Delete property')"
            :description="$t('Are you sure you want to delete this property?')"
            @delete="deleteProperty"
        />
    </AppLayout>
</template>

<script setup>
import { ref, reactive, watch, onMounted, onBeforeUnmount } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import draggable from 'vuedraggable'
import AppLayout from '@/Layouts/AppLayout.vue'
import ToolbarHeader from '@/Artwork/Toolbar/ToolbarHeader.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'
import AddEditTypeModal from '@/Pages/CRM/Settings/Components/AddEditTypeModal.vue'
import AddEditGroupModal from '@/Pages/CRM/Settings/Components/AddEditGroupModal.vue'
import AddEditPropertyModal from '@/Pages/CRM/Settings/Components/AddEditPropertyModal.vue'
import {
    IconCirclePlus, IconEdit, IconTrash, IconGripVertical, IconSettings,
    IconInfoCircle, IconChevronDown, IconArrowRight, IconLayoutGrid, IconAddressBook,
} from '@tabler/icons-vue'
import { useCrmSettingsListener } from '@/Composeables/Listener/useCrmSettingsListener.js'
import { useTranslation } from '@/Composeables/Translation.js'

const $t = useTranslation()

const props = defineProps({
    contactTypes: { type: Array, required: true },
    propertyGroups: { type: Array, required: true },
})

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

// -- Workflow help (collapsible, remembered per browser) --
const WORKFLOW_HELP_KEY = 'crm-settings-workflow-help-collapsed'
const workflowHelpCollapsed = ref(localStorage.getItem(WORKFLOW_HELP_KEY) === '1')

const toggleWorkflowHelp = () => {
    workflowHelpCollapsed.value = !workflowHelpCollapsed.value
    localStorage.setItem(WORKFLOW_HELP_KEY, workflowHelpCollapsed.value ? '1' : '0')
}

const workflowSteps = [
    {
        title: 'Create property groups',
        text: 'Property groups bundle related properties, e.g. address or conditions. Properties are created inside a group.',
        actionLabel: 'New group',
        action: () => { showGroupModal.value = true },
    },
    {
        title: 'Create contact types',
        text: 'Contact types categorize your contacts, e.g. artists or agencies. Each type has its own icon and color.',
        actionLabel: 'New type',
        action: () => { showTypeModal.value = true },
    },
    {
        title: 'Assign property groups',
        text: 'Assign property groups to a contact type to define which fields its contacts have. This happens directly when creating or editing a type.',
        actionLabel: null,
        action: null,
    },
    {
        title: 'Import contacts',
        text: 'Optionally import existing contacts from an Excel or CSV file and map its columns to the assigned properties.',
        actionLabel: 'Go to import',
        href: route('crm.import'),
    },
]

// Reactive local copies so vuedraggable can mutate the lists
const localPropertyGroups = reactive(
    props.propertyGroups.map(g => ({
        ...g,
        properties: [...(g.properties || [])],
    }))
)

const localContactTypes = reactive([...props.contactTypes])

// Sync local copies when Inertia props change (e.g. after store/update/delete or broadcast reload)
watch(() => props.propertyGroups, (newGroups) => {
    localPropertyGroups.splice(0, localPropertyGroups.length, ...newGroups.map(g => ({
        ...g,
        properties: [...(g.properties || [])],
    })))
}, { deep: true })

watch(() => props.contactTypes, (newTypes) => {
    localContactTypes.splice(0, localContactTypes.length, ...newTypes)
}, { deep: true })

const dragging = ref(false)

const onPropertyDragChange = (group) => {
    const properties = group.properties.map((p, index) => ({
        id: p.id,
        sort_order: index + 1,
    }))
    router.patch(route('crm.properties.reorder'), { properties }, { preserveScroll: true })
}

const onGroupDragChange = () => {
    const groups = localPropertyGroups.map((g, index) => ({
        id: g.id,
        sort_order: index + 1,
    }))
    router.patch(route('crm.groups.reorder'), { groups }, { preserveScroll: true })
}

const onTypeDragChange = () => {
    const types = localContactTypes.map((t, index) => ({
        id: t.id,
        sort_order: index + 1,
    }))
    router.patch(route('crm.types.reorder'), { types }, { preserveScroll: true })
}

// -- Derived display helpers --
const assignedGroupCount = (type) => {
    const groupIds = new Set((type.properties ?? []).map(p => p.crm_property_group_id))
    return groupIds.size
}

const typesUsingGroup = (group) => {
    const seen = new Map()
    for (const property of (group.properties ?? [])) {
        for (const type of (property.contact_types ?? [])) {
            if (!seen.has(type.id)) {
                seen.set(type.id, type)
            }
        }
    }
    return [...seen.values()]
}

// Real-time updates via Reverb
const { init: initCrmListener, destroy: destroyCrmListener } = useCrmSettingsListener()

onMounted(() => {
    initCrmListener()
})

onBeforeUnmount(() => {
    destroyCrmListener()
})

// -- Success feedback --
const successMessage = ref('')
const showSuccess = (msg) => {
    successMessage.value = msg
    setTimeout(() => { successMessage.value = '' }, 3000)
}

// -- Type Management --
const showTypeModal = ref(false)
const editingType = ref(null)
const showDeleteTypeModal = ref(false)
const typeToDelete = ref(null)

const openDeleteTypeModal = (type) => {
    typeToDelete.value = type
    showDeleteTypeModal.value = true
}

const deleteType = () => {
    router.delete(route('crm.types.destroy', typeToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteTypeModal.value = false
            typeToDelete.value = null
            showSuccess($t('Deleted successfully'))
        },
    })
}

// -- Group Management --
const showGroupModal = ref(false)
const editingGroup = ref(null)
const showDeleteGroupModal = ref(false)
const groupToDelete = ref(null)

const openDeleteGroupModal = (group) => {
    groupToDelete.value = group
    showDeleteGroupModal.value = true
}

const deleteGroup = () => {
    router.delete(route('crm.groups.destroy', groupToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteGroupModal.value = false
            groupToDelete.value = null
            showSuccess($t('Deleted successfully'))
        },
    })
}

// -- Property Management --
const showPropertyModal = ref(false)
const editingProperty = ref(null)
const activeGroupForProperty = ref(null)
const showDeletePropertyModal = ref(false)
const propertyToDelete = ref(null)

const openDeletePropertyModal = (property) => {
    propertyToDelete.value = property
    showDeletePropertyModal.value = true
}

const deleteProperty = () => {
    router.delete(route('crm.properties.destroy', propertyToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeletePropertyModal.value = false
            propertyToDelete.value = null
            showSuccess($t('Deleted successfully'))
        },
    })
}
</script>
