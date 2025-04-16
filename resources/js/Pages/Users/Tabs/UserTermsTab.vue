<template>
    <div>
        <div>
            <div class="headline3 py-4">
                {{ $t('Hours & remuneration')}}
            </div>
           <div class="grid grid-cols-1 gap-4 max-w-md">
               <div v-if="user_type !== 'service_provider' && user_type !== 'freelancer'" class="flex">
                   <BaseInput type="number" v-model="userForm.weekly_working_hours" :label="$t('h/week as per contract')" @focusout="updateUserTerms" id="weekly_working_hours"/>
               </div>
               <div class="flex col-span-full items-center">
                   <BaseInput type="number" v-model="userForm.salary_per_hour" label="€" @focusout="updateUserTerms" id="salary_per_hour"/>
                   <div class="ml-4 h-10 flex items-center">
                       €/h
                   </div>
               </div>
               <div class="mb-3">
                   <BaseTextarea
                       :label="$t('Further information (variable remuneration, bonuses, etc.)')"
                       id="salary_description"
                       v-model="userForm.salary_description"
                       @focusout="updateUserTerms"
                       rows="4"
                   />
               </div>
           </div>
        </div>
    </div>
</template>

<script>
import {CheckIcon, DotsVerticalIcon, PencilAltIcon, TrashIcon, XIcon} from "@heroicons/vue/outline";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import {useForm} from "@inertiajs/vue3";
import {Menu, MenuButton, MenuItem, MenuItems, Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import NumberInputComponent from "@/Components/Inputs/NumberInputComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";

export default {
    components: {
        BaseTextarea,
        BaseInput,
        NumberInputComponent,
        TextareaComponent,
        CheckIcon,
        XIcon,
        PencilAltIcon,
        JetInputError,
        DotsVerticalIcon,
        TeamIconCollection,
        TrashIcon,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        Switch,
        SwitchGroup,
        SwitchLabel,
        JetDialogModal
    },
    props: [
        'user_to_edit',
        'user_type'
    ],
    data() {
        return {
            showChangeTeamsModal: false,
            userForm: useForm({
                weekly_working_hours: this.user_to_edit.weekly_working_hours,
                salary_per_hour: this.user_to_edit.salary_per_hour,
                salary_description: this.user_to_edit.salary_description,
            })
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
                if (this.userForm.isDirty) {
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
            }
        },
    }
}
</script>
