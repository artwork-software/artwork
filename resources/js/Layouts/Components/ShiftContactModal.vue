<template>
    <BaseModal @closed="$emit('closeModal')" v-if="show" modal-image="/Svgs/Overlays/illu_appointment_edit.svg">
            <div class="headline1">
                {{ $t('Contact persons') }}
            </div>
            <div class="xsLight my-4">
                {{ $t('Define contact persons for this shift planning') }}
            </div>
            <div class="w-full grid grid-cols-2">
                <div class="flex flex-wrap mt-4 mr-4 col-span-1" v-for="user in this.projectManagers">
                    <div class="flex">
                        <div class="mr-4">
                            <img :data-tooltip-target="user?.id"
                                 :src="user?.profile_photo_url"
                                 :alt="user?.name"
                                 class="ring-white ring-2 rounded-full h-11 w-11 object-cover"
                            />
                        </div>
                        <div>
                            <div>
                                <div class="xsDark">
                                    {{ user.first_name }} {{ user.last_name }}
                                </div>
                                <div class="xxsLight tracking-wider uppercase">
                                    {{ $t('Project management') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="w-full relative">
                <div class="my-auto w-full mr-12">
                    <input :placeholder="$t('Employee')"
                           id="userSearch"
                           v-model="user_query"
                           autocomplete="off"
                           class="mt-4 p-4 inputMain resize-none xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
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
                                    <p @click="addUserToContactArray(user)"
                                       class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                        {{ user.first_name }} {{ user.last_name }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>
            <div class="mt-4 w-full">
                <span v-for="(user,index) in this.shift_contacts"
                      class="flex mt-4 mr-1 rounded-full items-center font-bold text-primary">
                    <div class="flex items-center">
                        <img class="flex h-11 w-11 rounded-full object-cover"
                             :src="user.profile_photo_url"
                             alt=""/>
                        <span class="flex ml-4 sDark">
                            {{ user.first_name }} {{ user.last_name }}
                        </span>
                    </div>
                    <button type="button" @click="deleteUserFromContactArray(user)">
                        <span class="sr-only">{{ $t('Remove user as contact person') }}</span>
                        <XCircleIcon class="ml-2 mt-1 h-5 w-5 hover:text-error "/>
                    </button>
                </span>
            </div>
            <div class="flex justify-center mt-2">
                <FormButton :text="$t('Save')" @click="changeShiftContacts"/>
            </div>
    </BaseModal>
</template>

<script>
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import {XIcon, DownloadIcon} from "@heroicons/vue/outline";
import Permissions from "@/Mixins/Permissions.vue";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import {XCircleIcon} from "@heroicons/vue/solid";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    mixins: [Permissions],
    name: "ShiftContactModal",
    props: [
        'show',
        'assignedShiftContacts',
        'projectId',
        'projectManagers'
    ],
    components: {
        BaseModal,
        FormButton,
        XCircleIcon,
        UserTooltip,
        JetDialogModal,
        JetInputError,
        XIcon,
        DownloadIcon
    },
    emits: ['closeModal'],
    data() {
        return {
            user_search_results: [],
            user_query: '',
            //remove reference to original object by destructuring to new array
            shift_contacts: [...this.assignedShiftContacts]
        }
    },
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
        changeShiftContacts() {
            let contactIds = [];
            this.shift_contacts.forEach((contact) => {
                contactIds.push(contact.id);
            })
            this.$inertia.patch(route('projects.update.shift_contacts', {project: this.projectId}), {
                contactIds: contactIds,
            });
            this.$emit('closeModal')
        },
        addUserToContactArray(user) {
            let contactIds = [];
            this.shift_contacts.forEach((contact) => {
                contactIds.push(contact.id)
            })
            if (!contactIds.includes(user.id)) {
                this.shift_contacts.push(user);
            }
            this.user_query = '';
        },
        deleteUserFromContactArray(user) {
            if (this.shift_contacts.includes(user)) {
                this.shift_contacts.splice(this.shift_contacts.indexOf(user), 1);
            }
        },
    }
}
</script>
