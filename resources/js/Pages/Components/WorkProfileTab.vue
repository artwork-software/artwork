<template>
    <div class="headline3 mb-2">
        Arbeitsprofil
    </div>
    <div class="mb-2">
        <div class="headline6Light">
            Bearbeite das Arbeitsprofil der Nutzer*in hier.
        </div>
        <hr class="mb-2">
        <div v-if="this.$page.props.flash.success?.workProfile"
             class="w-2/3 font-bold text-sm border-1 border-green-600 rounded bg-green-600 p-2 text-white mb-1">
            {{ this.$page.props.flash.success?.workProfile }}
        </div>
        <div class="w-2/3 mb-2">
            <label for="jobTitle" class="text-sm subpixel-antialiased text-secondary">
                Arbeitsbezeichnung
            </label>
            <input id="jobTitle"
                   v-model="workProfileForm.workName"
                   placeholder="Noch keine Bezeichnung angegeben"
                   type="text"
                   class="w-full text-base font-normal mt-1 inputMain focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 placeholder:text-gray-400"/>
        </div>
        <div class="w-2/3 mb-2">
            <label for="jobDescription" class="text-sm subpixel-antialiased text-secondary">
                Arbeitsbeschreibung
            </label>
            <textarea
                id="jobDescription"
                v-model="workProfileForm.workDescription"
                placeholder="Noch keine Beschreibung angegeben"
                rows="4"
                class="w-full text-base font-normal mt-1 inputMain resize-none xsDark focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 placeholder:text-gray-400"/>
        </div>
        <div class="w-2/3 flex flex-row justify-center">
            <FormButton
                text="Speichern"
                @click="updateWorkProfile"
                />
        </div>
    </div>
    <div class="headline3 mb-2">
        Gewerke
    </div>
    <div class="mb-2">
        <div class="headline6Light">
            Gewerkseinstellungen
        </div>
        <hr class="mb-2">
        <SwitchGroup as="div" class="flex items-center">
            <Switch v-model="craftSettingsForm.canBeAssignedToShifts"
                    :class="[craftSettingsForm.canBeAssignedToShifts ? 'bg-indigo-600' : 'bg-secondary', 'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2']">
                <span aria-hidden="true"
                      :class="[craftSettingsForm.canBeAssignedToShifts ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"/>
            </Switch>
            <SwitchLabel as="span" class="ml-2 text-sm">
                <span class="text-secondary">Darf zu Schichten eingeteilt werden</span>
            </SwitchLabel>
        </SwitchGroup>
        <div class="mt-2 headline6Light">
            Schicht-Qualifikationen
        </div>
        <hr class="mb-2">
        <SwitchGroup v-for="shiftQualification in computedShiftQualifications" as="div" class="flex items-center">
            <Switch v-model="shiftQualification.toggled"
                    @update:modelValue="this.updateUserShiftQualification(shiftQualification)"
                    :class="[shiftQualification.toggled ? 'bg-indigo-600' : 'bg-secondary', 'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2']">
                <span aria-hidden="true"
                      :class="[shiftQualification.toggled ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"/>
            </Switch>
            <SwitchLabel as="span" class="ml-2 text-sm">
                <span class="text-secondary">Als {{shiftQualification.name}} einsetzbar</span>
            </SwitchLabel>
        </SwitchGroup>
    </div>
    <div v-if="userType === 'user'" class="mb-2">
        <div class="headline6Light">
            Schichtplaner für
        </div>
        <hr class="mb-2">
        <div class="flex flex-row">
            <TagComponent v-if="this.user.accessibleCrafts?.length > 0"
                          v-for="craft in this.user.accessibleCrafts"
                          :tag="craft"
                          :key="craft.id"
                          :displayed-text="craft.name" hide-x="true"/>
            <span v-else class="text-xs text-gray-500">
                Nicht als Schichtplaner zugeteilt.
            </span>
        </div>
    </div>
    <div v-if="this.craftSettingsForm.canBeAssignedToShifts" class="mb-2">
        <div class="headline6Light">
            In folgenden Gewerken einsetzbar
        </div>
        <hr class="mb-2">
        <div v-if="this.$page.props.flash.success?.craft"
             class="w-2/3 font-bold text-sm border-1 border-green-600 rounded bg-green-600 p-2 text-white mb-1">
            {{ this.$page.props.flash.success?.craft }}
        </div>
        <label for="selectedCraftToAdd" class="text-sm subpixel-antialiased text-secondary">
            Neue Gewerke zuordnen
        </label>
        <div class="w-full mb-2 flex items-center">
            <Listbox as="div" id="selectedCraftToAdd" class="w-2/3 relative" v-model="this.selectedCraftToAssign">
                <ListboxButton class="inputMain w-full h-10 cursor-pointer truncate flex p-2">
                    <div v-if="this.selectedCraftToAssign" class="flex-grow text-left">
                        {{
                            this.user.assignableCrafts.find(
                                assignableCraft => assignableCraft.id === this.selectedCraftToAssign
                            )?.name
                        }}
                    </div>
                    <div v-else class="flex-grow xsLight text-left subpixel-antialiased">
                        Gewerk wählen
                    </div>
                    <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                </ListboxButton>
                <ListboxOptions class="bg-primary w-full max-h-32 overflow-y-auto text-sm absolute">
                    <ListboxOption v-if="this.user.assignableCrafts.length === 0" :key="0" :value="null"
                                   class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between ">
                        <div class="h-5 text-gray-300">
                            Es gibt keine weiteren Gewerke die zugeordnet werden können.
                        </div>
                    </ListboxOption>
                    <ListboxOption v-else v-for="assignableCraft in this.user.assignableCrafts"
                                   class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between "
                                   :key="assignableCraft.id"
                                   :value="assignableCraft.id"
                                   v-slot="{ active, selected }">
                        <div :class="[selected ? 'text-white' : '']">
                            {{ assignableCraft.name }}
                        </div>
                        <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                    </ListboxOption>
                </ListboxOptions>
            </Listbox>
            <AddButtonSmall
                :disabled="this.selectedCraftToAssign === null"
                text="Gewerk zuordnen"
                @click="assignCraft()"
                class="ml-4"
                />
        </div>
        <div class="w-2/3 flex flex-row flex-wrap">
            <TagComponent v-for="craft in user.assignedCrafts" :tag="craft" :key="craft.id" :displayed-text="craft.name" :method="removeCraft" :property="craft.id"/>
        </div>
    </div>
</template>

<script>
import Permissions from "@/mixins/Permissions.vue";
import Input from "@/Jetstream/Input.vue";
import AddButton from "@/Layouts/Components/AddButton.vue";
import {useForm} from "@inertiajs/inertia-vue3";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions, Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";
import {CheckIcon} from "@heroicons/vue/solid";
import {ChevronDownIcon} from "@heroicons/vue/outline";
import {Inertia} from "@inertiajs/inertia";
import {reactive} from "vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";

export default {
    components: {
        AddButtonSmall,
        FormButton,
        ListboxOption,
        ListboxOptions,
        ListboxButton,
        Listbox,
        ChevronDownIcon, CheckIcon,
        Switch,
        SwitchGroup,
        SwitchLabel,
        TagComponent,
        AddButton,
        Input
    },
    mixins: [Permissions],
    props: ['userType', 'user', 'shiftQualifications'],
    data() {
        return {
            workProfileForm: useForm({
                workName: this.user.work_name,
                workDescription: this.user.work_description
            }),
            craftSettingsForm: useForm({
                canBeAssignedToShifts: this.user.can_work_shifts
            }),
            selectedCraftToAssign: null
        }
    },
    computed: {
        computedShiftQualifications() {
            let computedShiftQualifications = [];

            this.shiftQualifications.forEach(
                (shiftQualification) => {
                    let computedShiftQualification = {
                        id: shiftQualification.id,
                        name: shiftQualification.name,
                    };

                    let userShiftQualificationById = this.user.shiftQualifications.find(
                        (userShiftQualification) => userShiftQualification.id === shiftQualification.id
                    );
                    computedShiftQualification.toggled = typeof userShiftQualificationById !== 'undefined';
                    computedShiftQualifications.push(reactive(computedShiftQualification));
                }
            );

            return computedShiftQualifications;
        }
    },
    watch: {
        'craftSettingsForm.isDirty': {
            handler(craftSettingsFormIsDirty) {
                if (craftSettingsFormIsDirty) {
                    let desiredRoute = null,
                        routeParameter = null;

                    switch (this.userType) {
                        case 'user':
                            desiredRoute = 'user.update.craftSettings';
                            routeParameter = {user: this.user.id};
                            break;
                        case 'freelancer':
                            desiredRoute = 'freelancer.update.craftSettings';
                            routeParameter = {freelancer: this.user.id};
                            break;
                        case 'serviceProvider':
                            desiredRoute = 'service_provider.update.craftSettings';
                            routeParameter = {serviceProvider: this.user.id};
                            break;
                    }

                    if (desiredRoute) {
                        this.craftSettingsForm.patch(
                            route(desiredRoute, routeParameter),
                            {
                                preserveScroll:true,
                                preserveState:true
                            }
                        );
                    }
                }
            }
        }
    },
    methods: {
        updateUserShiftQualification(shiftQualification) {
            let desiredRoute = null,
                routeParameter = null;

            switch (this.userType) {
                case 'user':
                    desiredRoute = 'user.update.shift-qualification';
                    routeParameter = {user: this.user.id};
                    break;
                case 'freelancer':
                    desiredRoute = 'freelancer.update.shift-qualification';
                    routeParameter = {freelancer: this.user.id};
                    break;
                case 'serviceProvider':
                    desiredRoute = 'service_provider.update.shift-qualification';
                    routeParameter = {serviceProvider: this.user.id};
                    break;
            }

            if (desiredRoute) {
                Inertia.patch(
                    route(desiredRoute, routeParameter),
                    {
                        shiftQualificationId: shiftQualification.id,
                        create: shiftQualification.toggled
                    },
                    {
                        preserveScroll:true,
                        preserveState:true
                    }
                );
            }
        },
        updateWorkProfile() {
            let desiredRoute = null,
                routeParameter = null;

            switch (this.userType) {
                case 'user':
                    desiredRoute = 'user.update.workProfile';
                    routeParameter = {user: this.user.id};
                    break;
                case 'freelancer':
                    desiredRoute = 'freelancer.update.workProfile';
                    routeParameter = {freelancer: this.user.id};
                    break;
                case 'serviceProvider':
                    desiredRoute = 'service_provider.update.workProfile';
                    routeParameter = {serviceProvider: this.user.id};
                    break;
            }

            if (desiredRoute) {
                this.workProfileForm.patch(
                    route(desiredRoute, routeParameter),
                    {
                        preserveScroll:true,
                        preserveState:true
                    }
                );
            }
        },
        assignCraft() {
            let desiredRoute = null,
                routeParameter = null;

            switch (this.userType) {
                case 'user':
                    desiredRoute = 'user.assign.craft';
                    routeParameter = {user: this.user.id};
                    break;
                case 'freelancer':
                    desiredRoute = 'freelancer.assign.craft';
                    routeParameter = {freelancer: this.user.id};
                    break;
                case 'serviceProvider':
                    desiredRoute = 'service_provider.assign.craft';
                    routeParameter = {serviceProvider: this.user.id};
                    break;
            }

            if (desiredRoute) {
                Inertia.patch(
                    route(desiredRoute, routeParameter),
                    {
                        craftId: this.selectedCraftToAssign
                    },
                    {
                        preserveScroll: true,
                        onSuccess: () => this.selectedCraftToAssign = null
                    }
                );
            }
        },
        removeCraft(craftId) {
            let desiredRoute = null,
                routeParameter = null;

            switch (this.userType) {
                case 'user':
                    desiredRoute = 'user.remove.craft';
                    routeParameter = {user: this.user.id, craft: craftId};
                    break;
                case 'freelancer':
                    desiredRoute = 'freelancer.remove.craft';
                    routeParameter = {freelancer: this.user.id, craft: craftId};
                    break;
                case 'serviceProvider':
                    desiredRoute = 'service_provider.remove.craft';
                    routeParameter = {serviceProvider: this.user.id, craft: craftId};
                    break;
            }

            if (desiredRoute) {
                Inertia.delete(
                    route(desiredRoute, routeParameter),
                    {
                        preserveScroll: true
                    }
                );
            }
        }
    }
}
</script>
