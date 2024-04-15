<template>
    <div>
        <div>
            <div class="headline3 py-4">
                {{ $t('Hours & remuneration')}}
            </div>
            <div v-if="user_type !== 'service_provider' && user_type !== 'freelancer'" class="flex">
                <input type="number"
                       v-model="userForm.weekly_working_hours"
                       placeholder="h"
                       class="w-28 shadow-sm placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 border-2 block"
                       pattern="[0-9]"
                       @focusout="this.updateUserTerms('weekly_working_hours')"
                />
                <div class="ml-4 h-10 flex items-center">
                    {{ $t('h/week as per contract') }}
                </div>
            </div>
            <div class="flex">
                <input type="number"
                       v-model="userForm.salary_per_hour"
                       placeholder="€"
                       class="w-28 shadow-sm placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 border-2 block"
                       pattern="[0-9]"
                       @focusout="this.updateUserTerms('salary_per_hour')"
                />
                <div class="ml-4 h-10 flex items-center">
                    €/h
                </div>
            </div>
            <div class="py-1">
                <textarea :placeholder="$t('Further information (variable remuneration, bonuses, etc.)')"
                          v-model="userForm.salary_description"
                          rows="4"
                          class="border-gray-300 border-2 resize-none w-full text-sm focus:outline-none focus:ring-0 focus:border-secondary focus:border-1"
                          @focusout="this.updateUserTerms('salary_description')"
                />
            </div>
        </div>
    </div>
</template>

<script>
import {CheckIcon, DotsVerticalIcon, PencilAltIcon, TrashIcon, XIcon} from "@heroicons/vue/outline";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
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
                this.userForm.patch(
                    route(desiredRoute, routeParameter),
                    {
                        preserveScroll: true,
                    }
                );
            }
        },
    }
}
</script>
