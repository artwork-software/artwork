<template>
    <div :class="highlight">
        <div class="flex w-full flex-wrap md:flex-nowrap align-baseline">
            <div class="flex w-full flex-grow">
                <input @change="updateTaskStatus(task)"
                       v-model="task.done"
                       type="checkbox"
                       class="cursor-pointer h-6 w-6 text-success border-2 my-2 border-gray-300"/>
                <div class="ml-4 my-auto mDark"
                     :class="task.done ? 'text-secondary line-through' : 'text-primary'">
                    {{ task.name }}
                </div>
                <div v-if="!task.done && task.deadline"
                     class="ml-2 my-auto pt-1 xsLight "
                     :class="task.isDeadlineInFuture ? '' : 'text-error'">
                    {{ $t('until')}} {{ task.humanDeadline }}
                </div>
            </div>

            <div class="my-auto flex mr-3"
                 v-for="department in task.departments">
                <TeamIconCollection
                    :iconName="department.svg_name"
                    :alt="department.name"
                    class="ring-white ring-2 rounded-full h-9 w-9 object-cover"/>
            </div>
            <div v-show="!task.isPrivate"
                 class="my-auto">
                <img class="h-9 w-9 rounded-full object-cover"
                     :src="$page.props.user.profile_photo_url"
                     alt=""/>
            </div>
        </div>
        <Link v-if="task.projectId"
              :href="route('projects.tab', {project: task.projectId, projectTab: this.first_project_tasks_tab_id})"
              class="my-1 flex ml-10 xsDark">
            {{ task.projectName }}
            <ChevronRightIcon class="h-5 w-5 my-auto mx-3" aria-hidden="true"/>
            {{ task.checklistName }}
        </Link>

        <div class="ml-10 my-3 xsLight">
            {{ task.description }}
        </div>
    </div>


</template>

<script>
import {ChevronRightIcon} from "@heroicons/vue/solid";
import {Link, useForm} from "@inertiajs/vue3";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";

export default {
    name: "SingleTask",
    components: {TeamIconCollection, Link, ChevronRightIcon},
    props: [
        'task',
        'first_project_tasks_tab_id'
    ],
    methods: {
        updateTaskStatus(task) {
            this.doneTaskForm.done = task.done;
            this.doneTaskForm.patch(route('tasks.update', {task: task.id}));
        },
    },
    data() {
        return {
            doneTaskForm: useForm({
                done: false
            }),
            highlight: null
        }
    },
    mounted() {
        if(parseInt(this.$page.props.urlParameters.taskId) === this.task.id){
            this.highlight = 'border-2 border-orange-300 rounded-md p-1';

            setTimeout(() => {
                this.highlight = null;
            }, 5000);
        }
    },
}
</script>
