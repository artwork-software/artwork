<template>
    <app-layout :title="$t('My tasks')">
        <div class="ml-14 mr-10">
            <div class="">
                <ChecklistFunctionBar
                    title="My tasks"
                    :filters="filters"
                    is-in-own-task-management
                />
            </div>

            <div class="" v-if="$page.props.user.checklist_style === 'list'">
                <ChecklistListView
                    :checklists="allChecklists"
                    is-in-own-task-management
                />
            </div>

            <div v-else>
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
                    <div class="divide-y-2 divide-dashed" v-if="money_source_task.length > 0">
                        <div v-for="task in money_source_task" :key="task.id" :id="task.id" class="pt-3" >
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

            <pre>
                {{ allChecklists }}
            </pre>
        </div>

    </app-layout>
</template>

<script>

import Permissions from "@/Mixins/Permissions.vue";


import AppLayout from '@/Layouts/AppLayout.vue'
import {CheckIcon, ChevronDownIcon, ChevronRightIcon} from "@heroicons/vue/solid";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import {Link, useForm} from "@inertiajs/vue3";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";
import SingleMoneySourceTask from "@/Pages/Tasks/Components/SingleMoneySourceTask.vue";
import SingleTask from "@/Pages/Tasks/Components/SingleTask.vue";
import ChecklistListView from "@/Components/Checklist/ChecklistListView.vue";
import ChecklistFunctionBar from "@/Components/Checklist/ChecklistFunctionBar.vue";
import ChecklistKanbanView from "@/Components/Checklist/ChecklistKanbanView.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";

export default {
    mixins: [Permissions],
    name: "OwnTasksManagement",
    props: [
        'tasks',
        'private_checklists',
        'money_source_task',
        'first_project_tasks_tab_id',
        'checklists'
    ],
    components: {
        AlertComponent,
        ChecklistKanbanView,
        ChecklistFunctionBar,
        ChecklistListView,
        SingleTask,
        SingleMoneySourceTask,
        AppLayout,
        ChevronRightIcon,
        TeamIconCollection,
        Link,
        CheckIcon,
        Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        ChevronDownIcon
    },
    methods: {

    },
    computed: {
        allChecklists() {
            return this.checklists.concat(this.private_checklists);
        }
    },
    data() {
        return {
            filters : [
                {
                    name: this.$t('According to checklists'),
                    type: 1
                },
                {
                    name: this.$t('By deadline'),
                    type: 2
                },
                {
                    name: this.$t('Completed tasks'),
                    type: 3
                }
            ]
        }
    },
}
</script>

<style scoped>

</style>
