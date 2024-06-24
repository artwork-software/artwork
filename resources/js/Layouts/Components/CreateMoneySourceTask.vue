<template>
    <BaseModal @closed="closeModal" v-if="true" modal-image="/Svgs/Overlays/illu_money_source_create.svg">
            <div class="mx-4">
                <!--   Heading   -->
                <div class="my-1">
                    <div class="flex-grow headline1 mb-6">
                        {{ $t('New task')}}
                    </div>
                    <p class="xsLight">
                        {{ $t('Create a new task. You can also add a deadline and a comment.')}}
                    </p>
                    <div class="pb-2 pt-2">
                        <div class="mb-2">
                            <input type="text"
                                   v-model="this.task.name"
                                   id="sourceName"
                                   :placeholder="$t('Title*')"
                                   class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                        </div>
                        <div class="flex mb-2 space-x-2">
                            <div class="w-1/2">
                                <input type="text" onfocus="(this.type='date')"
                                       v-model="this.task.end_date"
                                       id="sourceStartDate"
                                       :placeholder="$t('To be completed by?*')"
                                       class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                            </div>
                            <div class="w-1/2">
                                <input type="text" onfocus="(this.type='time')"
                                       v-model="this.task.end_time"
                                       id="sourceEndDate"
                                       placeholder="hh:mm*"
                                       class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                            </div>
                        </div>
                        <div class="flex">
                                    <textarea :placeholder="$t('Comment')"
                                              id="description"
                                              v-model="this.task.description"
                                              rows="4"
                                              class="border-2 placeholder:xsLight placeholder:subpixel-antialiased focus:xsDark resize-none text-sm focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                        </div>
                        <div class="flex justify-center mt-2">
                            <FormButton @click="createTask()" :text="$t('Save')"/>
                        </div>
                    </div>
                </div>
            </div>
    </BaseModal>

</template>

<script>

import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {ChevronDownIcon, DotsVerticalIcon, PencilAltIcon, XCircleIcon, XIcon} from '@heroicons/vue/outline';
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
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import Input from "@/Jetstream/Input.vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import {useForm} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    name: 'EventComponent',
    mixins: [Permissions, IconLib],
    components: {
        BaseModal,
        FormButton,
        Input,
        JetDialogModal,
        XIcon,
        XCircleIcon,
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
