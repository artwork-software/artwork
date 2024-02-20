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
    <SuccessModal
        :show="showSuccessModal"
        @closed="closeSuccessModal"
        title="Nutzer*in erfolgreich bearbeitet"
        description="Die Änderungen wurden erfolgreich gespeichert."
        button="Schließen"
    />

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
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";

export default {
    components: {
        SuccessModal,
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
            let desiredRoute = null,
                routeParameter = null;

            switch (this.user_type) {
                case 'service_provider':
                    desiredRoute = 'service_provider.update.terms';
                    routeParameter = {serviceProvider: this.user_to_edit.id};
                    break;
                case 'freelancer':
                    desiredRoute = 'freelancer.update.terms';
                    routeParameter = {freelancer: this.user_to_edit.id};
                    break;
                case 'user':
                    desiredRoute = 'user.update.terms';
                    routeParameter = {user: this.user_to_edit.id};
                    break;
            }

            if (desiredRoute) {
                this.userForm.patch(
                    route(desiredRoute, routeParameter),
                    {
                        preserveScroll: true,
                        onSuccess: () => {
                            this.openSuccessModal();
                        },
                    }
                );
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
