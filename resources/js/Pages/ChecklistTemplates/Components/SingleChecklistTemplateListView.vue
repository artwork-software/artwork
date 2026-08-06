<template>
    <div class="border border-border-subtle rounded-xl mb-4 bg-white">
        <div class="p-5">
            <!-- Header -->
            <div class="flex items-center justify-between bg-accent-50 rounded-lg px-4 py-3">
                <div class="flex items-center gap-x-3 min-w-0">
                    <div class="truncate text-xs font-semibold flex items-center gap-x-2 min-w-0">
                        <span class="truncate">{{ checklist_template.name }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-x-2 print:hidden">
                    <div class="hidden sm:flex items-center gap-x-2 text-text-subtle">
                        <UserPopoverTooltip v-if="checklist_template.user" :user="checklist_template.user" height="6" width="6" />
                        <span class="text-[11px]">{{ checklist_template.created_at }}</span>
                    </div>
                    <span class="bg-accent-50 border border-accent-200 text-accent-600 text-xs px-2 py-0.5 rounded">
                        {{ tasks.length }}
                    </span>
                    <IconCirclePlus
                        class="h-6 w-6 cursor-pointer hover:text-accent-700 transition-all duration-150 ease-in-out"
                        @click.stop="openAddTaskModal = true"
                    />
                    <BaseMenu has-no-offset white-menu-background>
                        <BaseMenuItem icon="IconEdit" title="Edit" white-menu-background @click="showEditModal = true" />
                        <BaseMenuItem icon="IconCopy" title="Duplicate" white-menu-background @click="duplicateTemplate" />
                        <BaseMenuItem icon="IconTrash" title="Delete" white-menu-background @click="showDeleteModal = true" />
                    </BaseMenu>
                    <div class="h-6 w-6 cursor-pointer" @click.stop="expanded = !expanded">
                        <IconChevronDown class="h-6 w-6 transition-transform" :class="expanded ? 'rotate-180' : ''" />
                    </div>
                </div>
            </div>

            <!-- Tasks -->
            <div v-if="expanded" class="mt-4 border-b border-dashed border-border-subtle pb-3">
                <div v-if="tasks.length === 0" class="text-center text-sm text-text-subtle py-4">
                    {{ $t('No tasks added yet') }}
                </div>
                <draggable
                    v-else
                    v-model="tasks"
                    item-key="id"
                    handle=".drag-handle"
                    ghost-class="opacity-50"
                    @end="persistOrder"
                >
                    <template #item="{ element }">
                        <SingleTaskTemplateInListView
                            :task="element"
                            :checklist-template-id="checklist_template.id"
                        />
                    </template>
                </draggable>
            </div>

            <!-- Add task footer -->
            <div
                v-if="expanded"
                class="px-5 py-2.5 mt-2 cursor-pointer flex items-center text-center justify-center gap-x-4"
                @click="openAddTaskModal = true"
            >
                <AlertComponent :text="$t('Click here to create a task')" type="info" />
            </div>
        </div>
    </div>

    <!-- Edit name modal -->
    <EditChecklistTemplateModal
        v-if="showEditModal"
        :checklist-template="checklist_template"
        @closed="showEditModal = false"
    />

    <!-- Delete confirm modal -->
    <ConfirmDeleteModal
        v-if="showDeleteModal"
        :title="$t('Delete checklist template')"
        :description="$t('Are you sure you want to delete the checklist template {0}?', [checklist_template.name])"
        @closed="showDeleteModal = false"
        @delete="deleteTemplate"
    />

    <!-- Add task modal -->
    <AddEditTaskTemplateModal
        v-if="openAddTaskModal"
        :checklist-template-id="checklist_template.id"
        @closed="openAddTaskModal = false"
    />
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import draggable from 'vuedraggable'
import { IconChevronDown, IconCirclePlus } from '@tabler/icons-vue'
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import BaseMenuItem from '@/Components/Menu/BaseMenuItem.vue'
import UserPopoverTooltip from '@/Layouts/Components/UserPopoverTooltip.vue'
import AlertComponent from '@/Components/Alerts/AlertComponent.vue'
import SingleTaskTemplateInListView from '@/Pages/ChecklistTemplates/Components/SingleTaskTemplateInListView.vue'
import EditChecklistTemplateModal from '@/Pages/ChecklistTemplates/Components/EditChecklistTemplateModal.vue'
import AddEditTaskTemplateModal from '@/Pages/ChecklistTemplates/Components/AddEditTaskTemplateModal.vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'

const props = defineProps({
    checklist_template: {
        type: Object,
        required: true
    }
})

const expanded = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const openAddTaskModal = ref(false)

const tasks = ref([...(props.checklist_template.task_templates ?? [])])

watch(
    () => props.checklist_template.task_templates,
    (newTasks) => {
        tasks.value = [...(newTasks ?? [])]
    },
    { deep: true }
)

const persistOrder = () => {
    const taskTemplates = tasks.value.map((task, index) => ({
        id: task.id,
        order: index
    }))

    router.put(route('task_templates.order'), { taskTemplates }, {
        preserveState: true,
        preserveScroll: true
    })
}

const duplicateTemplate = () => {
    router.post(route('checklist_templates.duplicate', { checklistTemplate: props.checklist_template.id }), {}, {
        preserveScroll: true
    })
}

const deleteTemplate = () => {
    router.delete(route('checklist_templates.destroy', { checklist_template: props.checklist_template.id }), {
        preserveScroll: true,
        onSuccess: () => { showDeleteModal.value = false }
    })
}
</script>
