<template>
    <div id="createTask" v-if="show" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="col-span-full">
            <BaseInput
                v-model="this.newTaskName"
                id="newTaskName"
                :label="$t('Task')"
            />

        </div>
        <div class="col-span-full" id="deadline">
            <div class="w-full flex">
                <BaseInput
                    type="date"
                    v-model="deadlineDate"
                    id="startDate"
                    :label="$t('To be completed by?')"
                    classes="border-r-0 rounded-l-lg rounded-r-none"
                    required
                />
                <BaseInput
                    type="time"
                    label="hh:mm"
                    v-model="deadlineTime"
                    id="changeStartTime"
                    classes="rounded-l-none rounded-r-lg"
                    required
                />
            </div>
        </div>
        <div class="col-span-full">
            <UserSearch v-model="task_user_query" :label="$t('Who is responsible for this task?')" @userSelected="addUserToTaskUserArray"/>
        </div>
        <div class="flex mt-1 justify-center text-sm text-error col-span-full">
            {{this.errorText}}
        </div>
        <div v-if="taskUsers.length > 0" class="col-span-full">
            <div v-for="(user,index) in taskUsers" class="flex mr-5 rounded-full items-center font-bold text-primary">
                <div class="flex items-center">
                    <img class="flex h-11 w-11 rounded-full object-cover" :src="user.profile_photo_url" alt=""/>
                    <span class="flex ml-4 sDark">
                        {{ user.first_name }} {{ user.last_name }}
                    </span>
                    <button type="button" @click="deleteUserFromTaskUserArray(index)">
                        <span class="sr-only">{{ $t('Remove user from task')}}</span>
                        <IconX stroke-width="1.5" class="ml-2 h-4 w-4 p-0.5 hover:text-error rounded-full text-primary border-0 "/>
                    </button>
                </div>
            </div>
        </div>

        <div class="col-span-full">
            <BaseTextarea
                :label="$t('Comment / Note')"
                id="description"
                v-model="taskDescription"
                rows="5"
            />
        </div>

    </div>
</template>

<script>
import {XIcon} from "@heroicons/vue/outline";
import Permissions from "@/Mixins/Permissions.vue";
import IconLib from "@/Mixins/IconLib.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import DateInputComponent from "@/Components/Inputs/DateInputComponent.vue";
import TimeInputComponent from "@/Components/Inputs/TimeInputComponent.vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";

export default {
    name: "ContractTaskForm",
    mixins: [Permissions, IconLib],
    components: {
        BaseTextarea,
        BaseInput,
        TextareaComponent,
        UserSearch,
        TimeInputComponent,
        DateInputComponent,
        TextInputComponent,
        XIcon
    },
    props: {
        show: Boolean,
        users: Array
    },
    data () {
        return {
            deadlineDate: null,
            deadlineTime: null,
            deadline: null,
            taskDescription: '',
            task_user_search_results: [],
            task_user_query: '',
            newTaskName: '',
            taskUsers: [],
            errorText:''
        }
    },
    watch: {
        task_user_query: {
            handler() {
                if (this.task_user_query.length > 0) {
                    axios.get('/users/search', {
                        params: {query: this.task_user_query}
                    }).then(response => {
                        this.task_user_search_results = response.data;
                    })
                }
            },
            deep: true
        }
    },
    methods: {
        addUserToTaskUserArray(user) {
            if (!this.taskUsers.find(userToAdd => userToAdd.id === user.id)) {
                this.taskUsers.push(user);
                this.errorText = '';
            }
            this.task_user_query = '';
        },
        deleteUserFromTaskUserArray(index) {
            this.taskUsers.splice(index, 1);
        },
        saveTask() {
            if (this.deadlineDate) {
                if (this.deadlineTime === null) {
                    this.deadlineTime = '00:00';
                }
                this.deadline = this.formatDate(this.deadlineDate, this.deadlineTime);
            }
            if(this.taskUsers.length > 0){
                let task = {
                    "name": this.newTaskName,
                    "description": this.taskDescription,
                    "deadline": this.deadline,
                    "assigned_users": this.taskUsers,
                    "done": false,
                    "new": true
                }
                this.$emit('addTask', task)
                this.clearForm();
            }else{
                this.errorText = this.$t('You must assign the task to a person with document access')
                this.$emit('showError');
            }
        },
        formatDate(date, time) {
            if (date === null || time === null) return null;
            return new Date((new Date(date + ' ' + time)).getTime() - ((new Date(date + ' ' + time)).getTimezoneOffset() * 60000)).toISOString();
        },
        clearForm(){
            this.newTaskName = ''
            this.taskDescription = ''
            this.task_user_query = ''
            this.deadlineDate = null
            this.deadlineTime = null
            this.taskUsers = []
        },
    }
}
</script>

<style scoped>

</style>
