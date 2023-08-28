<template>
    <div>
        <div>
            <div class="headline3 py-4">
                Stunden & Vergütung
            </div>

            <div v-if="user_type !== 'service_provider' && user_type !== 'freelancer'" class="flex">
                <input type="number" v-model="userForm.weekly_working_hours" placeholder="h"
                       class="w-28 shadow-sm placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 border-2 block"/>
                <div class="ml-4 h-10 flex items-center">
                    h/Woche lt. Vertrag
                </div>
            </div>
            <div class="flex">
                <input type="number" v-model="userForm.salary_per_hour" placeholder="€"
                       class="w-28 shadow-sm placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 border-2 block"/>
                <div class="ml-4 h-10 flex items-center">
                    €/h
                </div>
            </div>
            <div class="py-1">
                            <textarea placeholder="Weitere Informationen (variable Vergütung, Zuschläge, etc.)"
                                      id="salary_description"
                                      v-model="userForm.salary_description"
                                      rows="4"
                                      class="border-gray-300 border-2 resize-none w-full text-sm focus:outline-none focus:ring-0 focus:border-secondary focus:border-1"/>
            </div>
        </div>


        <AddButton @click="updateUserTerms"
                   class=" inline-flex items-center px-12 py-3 border focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                   text="Änderungen speichern" mode="modal"/>
    </div>

    <!-- Success Modal -->
    <jet-dialog-modal :show="showSuccessModal" @close="closeSuccessModal">
        <template #content>
            <div class="mx-4">
                <div class="headline1 my-2">
                    Nutzer*in erfolgreich bearbeitet
                </div>
                <XIcon @click="closeSuccessModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="successText">
                    Die Änderungen wurden erfolgreich gespeichert.
                </div>
                <div class="mt-6">
                    <button class="bg-success focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                            @click="closeSuccessModal">
                        <CheckIcon class="h-6 w-6 text-secondaryHover"/>
                    </button>
                </div>
            </div>

        </template>
    </jet-dialog-modal>

</template>

<script>


import ProjectShowHeaderComponent from "@/Pages/Projects/Components/ProjectShowHeaderComponent.vue";
import InfoTab from "@/Pages/Projects/Components/TabComponents/InfoTab.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import BaseSidenav from "@/Layouts/Components/BaseSidenav.vue";
import ProjectSecondSidenav from "@/Layouts/Components/ProjectSecondSidenav.vue";
import ProjectShiftSidenav from "@/Layouts/Components/ProjectShiftSidenav.vue";
import ProjectSidenav from "@/Layouts/Components/ProjectSidenav.vue";
import UserEditHeader from "@/Pages/Users/Components/UserEditHeader.vue";
import {CheckIcon, DotsVerticalIcon, PencilAltIcon, TrashIcon, XIcon} from "@heroicons/vue/outline";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import AddButton from "@/Layouts/Components/AddButton.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import {useForm} from "@inertiajs/inertia-vue3";
import {Menu, MenuButton, MenuItem, MenuItems, Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";

export default {
    components: {
        CheckIcon,
        XIcon,
        PencilAltIcon,
        JetInputError,
        AddButton,
        DotsVerticalIcon,
        TeamIconCollection,
        TrashIcon,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        Switch, SwitchGroup, SwitchLabel,
        JetDialogModal
    },
    props: [
        'user_to_edit',
        'user_type'
    ],
    data() {
        return {
            showChangeTeamsModal: false,
            showSuccessModal: false,
            userForm: useForm({
                can_master: this.user_to_edit.can_master,
                weekly_working_hours: this.user_to_edit.weekly_working_hours,
                salary_per_hour: this.user_to_edit.salary_per_hour,
                salary_description: this.user_to_edit.salary_description,
            }),
            resetPasswordForm: this.$inertia.form({
                email: this.user_to_edit.email
            }),
        }
    },
    methods: {
        updateUserTerms() {
            if(this.user_type === 'service_provider'){
                this.userForm.patch(route('service_provider.update', this.user_to_edit.id), {
                    can_master: this.user_to_edit.can_master,
                    weekly_working_hours: this.user_to_edit.weekly_working_hours,
                    salary_per_hour: this.user_to_edit.salary_per_hour,
                    salary_description: this.user_to_edit.salary_description,
                    preserveScroll: true,
                    onFinish: () => {
                        this.openSuccessModal();
                    },
                });
            }else if(this.user_type === 'freelancer'){
                this.userForm.patch(route('freelancer.update', this.user_to_edit.id), {
                    can_master: this.user_to_edit.can_master,
                    weekly_working_hours: this.user_to_edit.weekly_working_hours,
                    salary_per_hour: this.user_to_edit.salary_per_hour,
                    salary_description: this.user_to_edit.salary_description,
                    preserveScroll: true,
                    onFinish: () => {
                        this.openSuccessModal();
                    },
                });
            }else{
                this.userForm.patch(route('user.update.terms', this.user_to_edit.id), {
                    can_master: this.user_to_edit.can_master,
                    weekly_working_hours: this.user_to_edit.weekly_working_hours,
                    salary_per_hour: this.user_to_edit.salary_per_hour,
                    salary_description: this.user_to_edit.salary_description,
                    preserveScroll: true,
                    onFinish: () => {
                        this.openSuccessModal();
                    },
                });
            }

        },
        openSuccessModal() {
            this.showSuccessModal = true;
            setTimeout(() => this.closeSuccessModal(), 2000)
        },
        closeSuccessModal() {
            this.showSuccessModal = false;
        },

    }
}
</script>


<style scoped>

</style>
