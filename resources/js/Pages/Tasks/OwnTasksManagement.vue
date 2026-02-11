<template>
    <AppLayout :title="$t('My tasks')">
        <div class="artwork-container">

            <ToolbarHeader
                :icon="IconChecklist"
                :title="$t('My ToDo-Lists')"
                icon-bg-class="bg-green-600/10 text-green-700"
                :description="$t('Organize, filter and edit your tasks')"
                :search-enabled="false"
            >
                <template #actions>
                    <nav class="grid grid-cols-2 sm:flex gap-2">
                        <BaseUIButton label="New checklist" use-translation is-add-button @click="showChecklistEditModal = true" />
                    </nav>
                </template>
            </ToolbarHeader>

            <div class="mt-4 mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
                {{ $t('You only see checklists that have at least one task assigned to you, or that are assigned to you') }}
            </div>
            <!-- Reuse ChecklistComponent with OwnTasksManagement mode -->
            <ChecklistComponent
                :project="null"
                :is-in-own-task-management="true"
                :external-public-checklists="public_checklists"
                :external-private-checklists="private_checklists"
                :show-project-filter="true"
                :checklist_templates="checklist_templates"
                :can-edit-component="false"
                :tab_id="null"
            />

            <!-- Money Source Tasks -->
            <section class="mt-10" v-if="moneySourceTasks.length > 0">
                <h2 class="text-lg font-semibold mb-2">{{ $t('Money Source Tasks') }}</h2>
                <div v-if="moneySourceTasks.length" class="rounded-2xl border border-gray-100 bg-white shadow-sm divide-y">
                    <div v-for="task in moneySourceTasks" :key="task.id" class="px-5 py-4">
                        <SingleMoneySourceTask :task="task" />
                    </div>
                </div>
            </section>
        </div>

        <AddEditChecklistModal
            :checklist_templates="checklist_templates"
            :project="null"
            :checklist-to-edit="null"
            :tab_id="null"
            v-if="showChecklistEditModal"
            @closed="showChecklistEditModal = false"
        />
    </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import SingleMoneySourceTask from '@/Pages/Tasks/Components/SingleMoneySourceTask.vue'
import { IconChecklist } from '@tabler/icons-vue'
import ToolbarHeader from "@/Artwork/Toolbar/ToolbarHeader.vue";
import AddEditChecklistModal from "@/Components/Checklist/Modals/AddEditChecklistModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ChecklistComponent from "@/Pages/Projects/Components/ChecklistComponent.vue";

const props = defineProps<{
    money_source_task: any[],
    first_project_tasks_tab_id: number,
    checklist_templates: Array<any>,
    public_checklists: Array<any>,
    private_checklists: Array<any>,
}>()

const showChecklistEditModal = ref(false)
const moneySourceTasks = ref(props.money_source_task ?? [])
</script>

<style scoped>
</style>
