<template>
    <div>
        <div>
            <div class="font-lexend font-semibold text-[clamp(16px,2vw,18px)]/[21px] text-text py-4">
                {{ $t('Hours & remuneration')}}
            </div>
           <div class="grid grid-cols-1 gap-4 max-w-md">
               <!-- Wochenstunden: reine Anzeige aus dem heute gültigen Arbeitszeitmuster (einzige Soll-Quelle),
                    kein Eingabefeld mehr; Externe haben kein Soll -->
               <div v-if="user_type === 'user'" class="rounded-lg border border-border-subtle bg-surface-sunken px-3 py-2 text-sm">
                   <template v-if="weeklyHoursFromPattern !== null">
                       <span class="text-text-muted">{{ $t('Weekly hours according to work time pattern') }}:</span>
                       <span class="ml-1 font-semibold text-text">{{ formattedWeeklyHours }} h</span>
                   </template>
                   <template v-else>
                       <span class="text-text-muted">{{ $t('No work time pattern stored') }}</span>
                       <Link :href="workTimePatternRoute" class="ml-2 text-accent-600 underline underline-offset-2 hover:text-accent-700">
                           {{ $t('Contract & work time') }}
                       </Link>
                   </template>
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
import {IconCheck, IconDotsVertical, IconEdit, IconTrash, IconX} from "@tabler/icons-vue";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import {Link, useForm} from "@inertiajs/vue3";
import {Menu, MenuButton, MenuItem, MenuItems, Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import NumberInputComponent from "@/Components/Inputs/NumberInputComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";

export default {
    components: {
        Link,
        BaseTextarea,
        BaseInput,
        NumberInputComponent,
        TextareaComponent,
        IconCheck,
        IconX,
        IconEdit,
        JetInputError,
        IconDotsVertical,
        TeamIconCollection,
        IconTrash,
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
                salary_per_hour: this.user_to_edit.salary_per_hour,
                salary_description: this.user_to_edit.salary_description,
            })
        }
    },
    computed: {
        // UserShowResource liefert weekly_working_hours berechnet aus dem heute gültigen Muster (null ohne Muster)
        weeklyHoursFromPattern() {
            const value = this.user_to_edit?.weekly_working_hours
            if (value === null || value === undefined || value === '') return null
            const number = Number(value)
            return Number.isFinite(number) ? number : null
        },
        formattedWeeklyHours() {
            return this.weeklyHoursFromPattern === null
                ? ''
                : this.weeklyHoursFromPattern.toLocaleString('de-DE', {minimumFractionDigits: 0, maximumFractionDigits: 2})
        },
        // Tab „Vertrag & Arbeitszeit" (legt ein anderes Paket an); Fallback auf den bisherigen Muster-Tab
        workTimePatternRoute() {
            const params = {user: this.user_to_edit.id}
            try {
                if (route().has('user.edit.contract-and-work-time')) {
                    return route('user.edit.contract-and-work-time', params)
                }
            } catch (e) {
                // Routenliste (noch) nicht verfügbar -> Fallback
            }
            return route('user.edit.work-time-pattern', params)
        },
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
