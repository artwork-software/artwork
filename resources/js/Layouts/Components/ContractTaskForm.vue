<template>
    <div id="createTask" v-if="show" class="my-6">
        <input type="text"
               v-model="this.newTaskName"
               id="newTaskName"
               :placeholder="$t('Task')"
               class="w-full h-12 sDark inputMain bg-transparent placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300"/>

        <div class="sm:w-full mt-4" id="deadline">
            <div class="w-full flex">
                <input v-model="deadlineDate"
                       id="startDate"
                       type="text"
                       :placeholder="$t('To be completed by?')"
                       required
                       onfocus="(this.type='date')"
                       class="border-gray-300 h-12 inputMain bg-transparent xsDark placeholder-secondary disabled:border-none flex-grow"/>
                <input v-model="deadlineTime"
                       id="changeStartTime"
                       type="time"
                       required
                       class="border-gray-300 h-12 inputMain xsDark placeholder-secondary bg-transparent disabled:border-none"/>
            </div>
        </div>
        <div class="relative w-full">
            <input id="taskUserSearch" v-model="task_user_query" type="text" autocomplete="off"
                   :placeholder="$t('Who is responsible for this task?')"
                   class="h-12 mt-4 w-full bg-transparent sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
            <transition leave-active-class="transition ease-in duration-100"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0">
                <div v-if="task_user_search_results.length > 0 && task_user_query.length > 0"
                     class="absolute w-full z-10 mt-1 max-h-60 bg-primary shadow-lg
                                                        text-base ring-1 ring-black ring-opacity-5
                                                        overflow-auto focus:outline-none sm:text-sm">
                    <div class="border-gray-200">
                        <div v-for="(user, index) in task_user_search_results" :key="index"
                             class="flex items-center cursor-pointer">
                            <div class="flex-1 text-sm py-4">
                                <p @click="addUserToTaskUserArray(user)"
                                   class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                    {{ user.first_name }} {{ user.last_name }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </div>
        <div class="flex mt-1 -mb-2 justify-center text-sm text-error">
            {{this.errorText}}
        </div>
        <div v-if="taskUsers.length > 0" class="mt-2 mb-4 flex items-center">
                                        <span v-for="(user,index) in taskUsers"
                                              class="flex mr-5 rounded-full items-center font-bold text-primary">
                                        <div class="flex items-center">
                                            <img class="flex h-11 w-11 rounded-full object-cover"
                                                 :src="user.profile_photo_url"
                                                 alt=""/>
                                            <span class="flex ml-4 sDark">
                                            {{ user.first_name }} {{ user.last_name }}
                                            </span>
                                            <button type="button" @click="deleteUserFromTaskUserArray(index)">
                                                <span class="sr-only">{{ $t('Remove user from task')}}</span>
                                                <IconX stroke-width="1.5"
                                                    class="ml-2 h-4 w-4 p-0.5 hover:text-error rounded-full bg-artwork-buttons-create text-white border-0 "/>
                                            </button>
                                        </div>

                                        </span>
        </div>

        <textarea :placeholder="$t('Comment / Note')"
                  id="description"
                  v-model="taskDescription"
                  rows="5"
                  class="mt-4 inputMain bg-transparent resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

    </div>
</template>

<script>
import {XIcon} from "@heroicons/vue/outline";
import Permissions from "@/Mixins/Permissions.vue";
import IconLib from "@/Mixins/IconLib.vue";

export default {
    name: "ContractTaskForm",
    mixins: [Permissions, IconLib],
    components: {
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
