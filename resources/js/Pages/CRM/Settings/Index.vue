<template>
    <AppLayout :title="$t('CRM Settings')">
        <div class="mt-5 mx-auto container pb-20">
            <div class="mb-4">
                <Link :href="route('crm.index')" class="inline-flex items-center text-sm font-medium text-artwork-buttons-hover hover:text-artwork-buttons-hover/80">
                    <component :is="IconArrowLeft" class="h-4 w-4 mr-2" />
                    {{ $t('Back to CRM') }}
                </Link>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ $t('CRM Settings') }}</h1>

            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 -translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-1"
            >
                <div v-if="successMessage" class="mb-4 rounded-md bg-green-50 p-3">
                    <p class="text-sm font-medium text-green-800">{{ successMessage }}</p>
                </div>
            </Transition>

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

                <div class="bg-white border border-gray-200 rounded-lg divide-y">
                    <div v-for="type in contactTypes" :key="type.id" class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span
                                v-if="type.icon"
                                class="inline-flex size-7 items-center justify-center rounded"
                                :style="type.color ? { backgroundColor: type.color + '20', color: type.color } : {}"
                            >
                                <PropertyIcon :name="type.icon" class="h-5 w-5" />
                            </span>
                            <span class="font-medium">{{ $t(type.name) }}</span>
                            <span v-if="type.is_system" class="text-xs text-gray-400">({{ $t('System') }})</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <ToolTipComponent
                                direction="left"
                                :tooltip-text="$t('Edit property assignment')"
                                icon="IconSettings2"
                                icon-size="h-4 w-4"
                                @click="openPropertyAssignmentModal(type)"
                            />
                            <button @click="editingType = type; showTypeModal = true" class="text-gray-400 hover:text-gray-600">
                                <component :is="IconEdit" class="h-4 w-4" />
                            </button>
                            <button v-if="!type.is_system" @click="openDeleteTypeModal(type)" class="text-gray-400 hover:text-red-600">
                                <component :is="IconTrash" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Property Groups -->
            <section class="mb-10">
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

                <div class="space-y-4">
                    <div v-for="group in localPropertyGroups" :key="group.id" class="bg-white border border-gray-200 rounded-lg">
                        <div class="px-6 py-4 flex items-center justify-between bg-gray-50 rounded-t-lg">
                            <div class="flex items-center gap-3">
                                <span class="font-medium">{{ $t(group.name) }}</span>
                                <span v-if="group.is_confidential" class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">
                                    {{ $t('Confidential') }}
                                </span>
                                <span v-if="group.is_system" class="text-xs text-gray-400">({{ $t('System') }})</span>
                            </div>
                            <div class="flex items-center gap-2">
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
                                <component :is="IconPlus" class="h-4 w-4" />
                                {{ $t('Add property') }}
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Type Modal -->
        <AddEditTypeModal
            v-if="showTypeModal"
            :contact-type="editingType"
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

        <!-- Property Assignment Modal -->
        <PropertyAssignmentModal
            v-if="showPropertyAssignmentModal"
            :contact-type="assignmentType"
            :property-groups="localPropertyGroups"
            @close="showPropertyAssignmentModal = false; assignmentType = null"
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
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'
import AddEditTypeModal from '@/Pages/CRM/Settings/Components/AddEditTypeModal.vue'
import AddEditGroupModal from '@/Pages/CRM/Settings/Components/AddEditGroupModal.vue'
import AddEditPropertyModal from '@/Pages/CRM/Settings/Components/AddEditPropertyModal.vue'
import PropertyAssignmentModal from '@/Pages/CRM/Settings/Components/PropertyAssignmentModal.vue'
import { IconArrowLeft, IconCirclePlus, IconEdit, IconTrash, IconPlus, IconGripVertical } from '@tabler/icons-vue'
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

// Reactive local copy so vuedraggable can mutate the lists
const localPropertyGroups = reactive(
    props.propertyGroups.map(g => ({
        ...g,
        properties: [...(g.properties || [])],
    }))
)

// Sync localPropertyGroups when Inertia props change (e.g. after store/update/delete or broadcast reload)
watch(() => props.propertyGroups, (newGroups) => {
    localPropertyGroups.splice(0, localPropertyGroups.length, ...newGroups.map(g => ({
        ...g,
        properties: [...(g.properties || [])],
    })))
}, { deep: true })

const dragging = ref(false)

const onPropertyDragChange = (group) => {
    const properties = group.properties.map((p, index) => ({
        id: p.id,
        sort_order: index + 1,
    }))
    router.patch(route('crm.properties.reorder'), { properties }, { preserveScroll: true })
}

// Real-time updates via Reverb
const { init: initCrmListener, destroy: destroyCrmListener } = useCrmSettingsListener()

onMounted(() => {
    initCrmListener()
})

onBeforeUnmount(() => {
    destroyCrmListener()
})

// -- Property Assignment --
const showPropertyAssignmentModal = ref(false)
const assignmentType = ref(null)

const openPropertyAssignmentModal = (type) => {
    assignmentType.value = type
    showPropertyAssignmentModal.value = true
}

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
