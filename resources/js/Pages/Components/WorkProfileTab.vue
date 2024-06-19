<template>
    <div class="headline3 mb-2">
        {{ $t('Work profile') }}
    </div>
    <div class="mb-2">
        <div class="headline6Light">
            {{ $t("Edit the user's work profile here.")}}
        </div>
        <hr class="mb-2">
        <div class="w-2/3 mb-2">
            <label for="jobTitle" class="text-sm subpixel-antialiased text-secondary">
                {{ $t('Job title') }}
            </label>
            <input id="jobTitle"
                   v-model="workProfileForm.workName"
                   :placeholder="$t('No designation specified yet')"
                   type="text"
                   class="w-full text-base font-normal mt-1 inputMain focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 placeholder:text-gray-400"
                   @focusout="this.updateWorkProfile()"
            />
        </div>
        <div class="w-2/3 mb-2">
            <label for="jobDescription" class="text-sm subpixel-antialiased text-secondary">
                {{ $t('Job description') }}
            </label>
            <textarea
                id="jobDescription"
                v-model="workProfileForm.workDescription" @focusout="updateWorkProfile"
                :placeholder="$t('No description given yet')"
                rows="4"
                class="w-full text-base font-normal mt-1 inputMain resize-none xsDark focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 placeholder:text-gray-400"/>
        </div>
    </div>
    <div class="headline3 mb-2">
        {{ $t('Crafts') }}
    </div>
    <div class="mb-2">
        <div class="headline6Light">
            {{ $t('Craft settings') }}
        </div>
        <hr class="mb-2">
        <SwitchGroup as="div" class="flex items-center">
            <Switch v-model="craftSettingsForm.canBeAssignedToShifts"
                    :class="[craftSettingsForm.canBeAssignedToShifts ? 'bg-artwork-buttons-create' : 'bg-secondary', 'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2']">
                <span aria-hidden="true"
                      :class="[craftSettingsForm.canBeAssignedToShifts ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"/>
            </Switch>
            <SwitchLabel as="span" class="ml-2 text-sm">
                <span class="text-secondary">{{ $t('May be assigned to shifts') }}</span>
            </SwitchLabel>
        </SwitchGroup>
        <div class="mt-2 headline6Light">
            {{ $t('Shift qualifications')}}
        </div>
        <hr class="mb-2">
        <SwitchGroup v-for="shiftQualification in computedShiftQualifications" as="div" class="flex items-center">
            <Switch v-model="shiftQualification.toggled"
                    @update:modelValue="this.updateUserShiftQualification(shiftQualification)"
                    :class="[shiftQualification.toggled ? 'bg-artwork-buttons-create' : 'bg-secondary', 'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2']">
                <span aria-hidden="true"
                      :class="[shiftQualification.toggled ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"/>
            </Switch>
            <SwitchLabel as="span" class="ml-2 text-sm">
                <span class="text-secondary">{{ $t('Can be used as {shiftQualificationName}', { shiftQualificationName: shiftQualification.name }) }}</span>
            </SwitchLabel>
        </SwitchGroup>
    </div>
    <div v-if="userType === 'user'" class="mb-2">
        <div class="headline6Light">
            {{ $t('Shift planner for')}}
        </div>
        <hr class="mb-2">
        <div class="flex flex-row">
            <TagComponent v-if="this.user.accessibleCrafts?.length > 0"
                          v-for="craft in this.user.accessibleCrafts"
                          :tag="craft"
                          :key="craft.id"
                          :displayed-text="craft.name" hide-x="true"/>
            <span v-else class="text-xs text-gray-500">
                {{ $t('Not assigned as shift planner.') }}
            </span>
        </div>
    </div>
    <div v-if="this.craftSettingsForm.canBeAssignedToShifts" class="mb-2">
        <div class="headline6Light">
            {{  $t('Can be used in the following crafts') }}
        </div>
        <hr class="mb-2">
        <label for="selectedCraftToAdd" class="text-sm subpixel-antialiased text-secondary">
            {{  $t('Assign new crafts') }}
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
                        {{  $t('Select craft') }}
                    </div>
                    <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                </ListboxButton>
                <ListboxOptions class="bg-artwork-navigation-background w-full max-h-32 overflow-y-auto text-sm absolute">
                    <ListboxOption v-if="this.user.assignableCrafts.length === 0" :key="0" :value="null"
                                   class="hover:bg-artwork-buttons-create text-secondary cursor-pointer p-2 flex justify-between ">
                        <div class="h-5 text-gray-300">
                            {{ $t('There are no other crafts that can be assigned.') }}
                        </div>
                    </ListboxOption>
                    <ListboxOption v-else v-for="assignableCraft in this.user.assignableCrafts"
                                   class="hover:bg-artwork-buttons-create text-secondary cursor-pointer p-2 flex justify-between "
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
                :text="$t('Assign craft')"
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
import Permissions from "@/Mixins/Permissions.vue";
import Input from "@/Jetstream/Input.vue";
import {useForm} from "@inertiajs/vue3";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions, Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";
import {CheckIcon} from "@heroicons/vue/solid";
import {ChevronDownIcon} from "@heroicons/vue/outline";
import {router} from "@inertiajs/vue3";
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
                router.patch(
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
                if (this.workProfileForm.isDirty) {
                    this.workProfileForm.patch(
                        route(desiredRoute, routeParameter),
                        {
                            preserveScroll:true,
                            preserveState:true
                        }
                    );
                }
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
                router.patch(
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
                router.delete(
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
