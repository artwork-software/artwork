<template>
    <app-layout :title="$t('My tasks')">
        <div class="ml-14">
            <div>
                <ChecklistFunctionBar
                    title="My tasks"
                    :filters="filters"
                    is-in-own-task-management
                >
                    <template #search>
                        <div class="" v-if="!showSearch" @click="showSearch = true">
                            <IconSearch class="h-6 w-6" />
                        </div>
                        <div v-if="showSearch">
                            <div class="relative -mt-4">
                                <TextInputComponent
                                    id="userSearch"
                                    v-model="search"
                                    label="Search for tasks"
                                    class="w-full"
                                    @focus="search = ''"
                                    is-small
                                />
                                <div class="absolute right-2 top-2 cursor-pointer" @click="removeSearch">
                                    <IconX class="h-6 w-6 text-gray-400" />
                                </div>
                            </div>
                        </div>
                    </template>
                    <template #filter>
                        <div>
                            <IconFilter class="w-6 h-6" />
                        </div>
                    </template>
                </ChecklistFunctionBar>
            </div>

            <div v-if="$page.props.user.checklist_style === 'list'">
                <ChecklistListView
                    :checklists="allChecklists"
                    is-in-own-task-management
                />
            </div>

            <div v-else class="-mx-10 bg-artwork-project-background px-10 py-10">
                <ChecklistKanbanView
                    :checklists="allChecklists"
                    is-in-own-task-management
                />
            </div>

            <div class="my-20">
                <div class="headline2 mb-5">
                    {{ $t('Sources of funding')}} {{ $t('Tasks')}}
                </div>
                <div>
                    <div class="divide-y-2 divide-dashed" v-if="moneySourceTasks.length > 0">
                        <div v-for="task in moneySourceTasks" :key="task.id" :id="task.id" class="pt-3">
                            <SingleMoneySourceTask :task="task" />
                        </div>
                    </div>
                    <div v-else>
                        <AlertComponent
                            type="info"
                            :text="$t('There are no tasks related to the sources of funding.')"
                            show-icon
                            icon-size="h-6 w-6"
                            text-size="text-base"
                        />
                    </div>
                </div>
            </div>
        </div>
    </app-layout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SingleMoneySourceTask from "@/Pages/Tasks/Components/SingleMoneySourceTask.vue";
import ChecklistListView from "@/Components/Checklist/ChecklistListView.vue";
import ChecklistFunctionBar from "@/Components/Checklist/ChecklistFunctionBar.vue";
import ChecklistKanbanView from "@/Components/Checklist/ChecklistKanbanView.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import {useTranslation} from "@/Composeables/Translation.js";
import { IconSearch, IconX, IconFilter } from "@tabler/icons-vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
const $t = useTranslation();

const props = defineProps({
    tasks: Array,
    private_checklists: Array,
    money_source_task: Array,
    first_project_tasks_tab_id: Number,
    checklists: Array
});

const search = ref('');
const showSearch = ref(false);
const filters = ref([
    { name: $t('According to checklists'), type: 1 },
    { name: $t('By deadline'), type: 2 },
    { name: $t('Completed tasks'), type: 3 }
]);

const allChecklists = computed(() => {
    const all = props.checklists.concat(props.private_checklists);

    // add a search filter for checklist name and task name.
    if (search.value) {
        return all.filter(checklist => {
            return checklist.name.toLowerCase().includes(search.value.toLowerCase()) || checklist.tasks.some(task => task.name.toLowerCase().includes(search.value.toLowerCase()));
        });
    } else {
        return all;
    }
});
const moneySourceTasks = ref(props.money_source_task)

const removeSearch = () => {
    search.value = '';
    showSearch.value = false;
}

</script>

<style scoped>
</style>
