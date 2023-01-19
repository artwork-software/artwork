<template>
    <jet-dialog-modal :show="true" @close="closeModal()">
        <template #content>
            <img alt="Neue Aufgabe" src="/Svgs/Overlays/illu_money_source_create.svg"
                 class="-ml-6 -mt-8 mb-4"/>
            <XIcon @click="closeModal()" class="text-secondary h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                   aria-hidden="true"/>
            <div class="mx-4">
                <!--   Heading   -->
                <div class="my-1">
                    <div class="flex-grow headline1 mb-6">
                        Neue Aufgabe
                    </div>
                    <p>
                        Lege eine neue Aufgabe an. Du kannst sie zudem mit einer Deadline und einem Kommentar versehen.
                    </p>
                    <div class="pb-2 pt-2">
                        <div class="mb-2">
                            <input type="text"
                                   v-model="this.task.name"
                                   id="sourceName"
                                   placeholder="Titel*"
                                   class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                        </div>
                        <div class="flex mb-2 space-x-2">
                            <div class="w-1/2">
                                <input type="text" onfocus="(this.type='date')"
                                       v-model="this.task.end_date"
                                       id="sourceStartDate"
                                       placeholder="Zu erledigen bis?"
                                       class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                            </div>
                            <div class="w-1/2">
                                <input type="text" onfocus="(this.type='time')"
                                       v-model="this.task.end_time"
                                       id="sourceEndDate"
                                       placeholder="hh:mm"
                                       class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                            </div>
                        </div>
                        <div class="mb-2">
                            <div class="relative w-full">
                                <div class="w-full">
                                    <input id="userSearch" v-model="user_query" type="text" autocomplete="off"
                                           placeholder="Wer ist zuständig für die Aufgabe?"
                                           class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                </div>
                                <transition leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100"
                                            leave-to-class="opacity-0">
                                    <div v-if="user_search_results.length > 0 && user_query.length > 0"
                                         class="absolute z-10 mt-1 w-full max-h-60 bg-primary shadow-lg
                                                        text-base ring-1 ring-black ring-opacity-5
                                                        overflow-auto focus:outline-none sm:text-sm">
                                        <div class="border-gray-200">
                                            <div v-for="(user, index) in user_search_results" :key="index"
                                                 class="flex items-center cursor-pointer">
                                                <div class="flex-1 text-sm py-4">
                                                    <p @click="addUserToMoneySourceUserArray(user)"
                                                       class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                        {{ user.first_name }} {{ user.last_name }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </transition>
                            </div>
                            <div v-if="usersToAdd.length > 0" class="mt-2 mb-4 flex items-center">
                                        <span v-for="(user,index) in usersToAdd"
                                              class="flex mr-5 rounded-full items-center font-bold text-primary">
                                        <div class="flex items-center">
                                            <img class="flex h-11 w-11 rounded-full object-cover"
                                                 :src="user.profile_photo_url"
                                                 alt=""/>
                                            <span class="flex ml-4 sDark">
                                            {{ user.first_name }} {{ user.last_name }}
                                            </span>
                                            <button type="button" @click="deleteUserFromMoneySourceUserArray(index)">
                                                <span class="sr-only">Nutzer aus der Aufgabe entfernen</span>
                                                <XIcon
                                                    class="ml-2 h-4 w-4 p-0.5 hover:text-error rounded-full bg-buttonBlue text-white border-0 "/>
                                            </button>
                                        </div>

                                        </span>
                            </div>
                        </div>
                        <div class="flex">
                                    <textarea placeholder="Kommentar"
                                              id="description"
                                              v-model="this.task.description"
                                              rows="4"
                                              class="border-2 placeholder:xsLight placeholder:subpixel-antialiased focus:xsDark resize-none w-full text-sm focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                        </div>
                        <div class="flex justify-center mt-2">
                            <AddButton mode="modal" class="bg-primary text-white resize-none"
                                       @click="createTask()" text="Speichern"/>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </jet-dialog-modal>

</template>

<script>

import JetDialogModal from "@/Jetstream/DialogModal";
import {ChevronDownIcon, DotsVerticalIcon, PencilAltIcon, XCircleIcon, XIcon} from '@heroicons/vue/outline';
import EventTypeIconCollection from "@/Layouts/Components/EventTypeIconCollection";
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems
} from "@headlessui/vue";
import {CheckIcon, ChevronUpIcon, TrashIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import Input from "@/Jetstream/Input";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent";
import TagComponent from "@/Layouts/Components/TagComponent";
import InputComponent from "@/Layouts/Components/InputComponent";
import {useForm} from "@inertiajs/inertia-vue3";
import AddButton from "@/Layouts/Components/AddButton";

export default {
    name: 'EventComponent',

    components: {
        Input,
        JetDialogModal,
        XIcon,
        XCircleIcon,
        EventTypeIconCollection,
        Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        ChevronDownIcon,
        ChevronUpIcon,
        SvgCollection,
        CheckIcon,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        PencilAltIcon,
        TrashIcon,
        DotsVerticalIcon,
        ConfirmationComponent,
        TagComponent,
        InputComponent,
        AddButton
    },
    computed: {
    },
    data() {
        return {
            task: useForm({
                name: '',
                description: '',
                end_date: null,
                end_time: null,
                users: [],
                money_source: this.money_source_id,
                deadline: null
            }),
            user_search_results: [],
            user_query: '',
            usersToAdd: [],
        }
    },

    props: ['money_source_id'],

    emits: ['closed'],

    watch: {
        user_query: {
            handler() {
                if (this.user_query.length > 0) {
                    axios.get('/users/search', {
                        params: {query: this.user_query}
                    }).then(response => {
                        this.user_search_results = response.data
                    })
                }
            },
            deep: true
        },
    },

    methods: {
        openModal() {

        },
        closeModal(bool) {
            this.$emit('closed', bool);
        },
        addUserToMoneySourceUserArray(user) {
            if(!this.usersToAdd.find(userToAdd => userToAdd.id === user.id)){
                this.usersToAdd.push(user);
            }
            this.user_query = '';
        },
        deleteUserFromMoneySourceUserArray(index) {
            this.usersToAdd.splice(index, 1);
        },
        createTask(){
            this.usersToAdd.forEach((userToAdd) => {
                this.task.users.push(userToAdd.id);
            })
            this.task.deadline = this.task.end_date + ' ' + this.task.end_time
            this.task.post(route('money_source.task.add'))
            this.name = ''
            this.description= ''
            this.end_date= null
            this.end_time= null
            this.users= []
            this.money_source= this.money_source_id
            this.deadline = null
            this.closeModal(true);
        },
        formatDate(date, time) {
            if (date === null || time === null) return null;
            return (new Date(date + ' ' + time))
        },
    },
}
</script>

<style scoped></style>
