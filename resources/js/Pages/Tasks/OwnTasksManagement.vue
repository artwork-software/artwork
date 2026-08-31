<template>
    <AppLayout :title="$t('My tasks')">
        <div class="artwork-container-fluid">

            <ToolbarHeader
                band
                :icon="IconChecklist"
                :title="$t('My ToDo-Lists')"
                :description="$t('Organize, filter and edit your tasks')"
                :search-enabled="false"
            >
                <template #actions>
                    <nav class="grid grid-cols-2 sm:flex gap-2">
                        <BaseUIButton label="New checklist" use-translation is-add-button on-band @click="showChecklistEditModal = true" />
                    </nav>
                </template>
            </ToolbarHeader>

            <div class="mt-4 mb-6 p-4 bg-accent-50 border border-accent-200 rounded-lg text-sm text-accent-700">
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

            <!-- Money Source Tasks: bewusst dezent als Unterkategorie und default eingeklappt
                 (Abnahme FIN-01 Ref. 3.33); erscheint nur, wenn dem User selbst Aufgaben
                 zugewiesen sind (Backend liefert ausschließlich eigene) -->
            <section class="mt-8" v-if="moneySourceTasks.length > 0">
                <button type="button"
                        class="flex items-center gap-1.5 text-sm font-medium text-text-muted hover:text-text cursor-pointer select-none"
                        @click="showMoneySourceTasks = !showMoneySourceTasks">
                    <component :is="showMoneySourceTasks ? IconChevronDown : IconChevronRight" class="w-4 h-4" />
                    {{ $t('Funding source tasks') }}
                    <span class="inline-flex items-center rounded-full bg-surface-sunken px-2 py-0.5 text-xs tabular-nums text-text-subtle ring-1 ring-inset ring-border-subtle">
                        {{ moneySourceTasks.length }}
                    </span>
                </button>
                <div v-if="showMoneySourceTasks" class="mt-2 rounded-2xl border border-border-subtle bg-white shadow-sm divide-y">
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
import { IconChecklist, IconChevronDown, IconChevronRight } from '@tabler/icons-vue'
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
// Default eingeklappt — der Bereich soll die eigentlichen ToDo-Listen nicht dominieren
const showMoneySourceTasks = ref(false)
const moneySourceTasks = ref(props.money_source_task ?? [])
</script>

<style scoped>
</style>
