<template>
    <div class="headline3 mb-2">
        {{ $t('Work profile') }}
    </div>
    <div class="mb-2">
        <div class="headline6Light">
            {{ $t("Edit the user's work profile here.")}}
        </div>
        <hr class="mb-2">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-xl">
            <div class="mb-2 col-span-full">
                <BaseInput id="workName" v-model="workProfileForm.workName" :label="$t('Job title')"  @focusout="this.updateWorkProfile()"/>
            </div>
            <div class="col-span-full mb-2">
                <BaseTextarea
                    id="jobDescription"
                    v-model="workProfileForm.workDescription" @focusout="updateWorkProfile"
                    :label="$t('Job description')"
                    :rows="4"
                />
            </div>
        </div>
    </div>

    <div class="my-3">
        <div class="headline6Light">
            {{ $t("Freelancer Settings")}}
        </div>
        <hr class="mb-2">
        <SwitchGroup as="div" class="flex items-center" v-if="userType === 'user'">
            <Switch v-model="workProfileForm.is_freelancer" @update:modelValue="updateWorkProfile" :disabled="workProfileForm.processing"
                    :class="[workProfileForm.is_freelancer ? 'bg-artwork-buttons-create' : 'bg-secondary', workProfileForm.processing ? 'cursor-not-allowed' : '', 'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2']">
                <span aria-hidden="true" :class="[workProfileForm.is_freelancer ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"/>
            </Switch>
            <SwitchLabel as="span" class="ml-2 text-sm">
                <span class="text-secondary">{{ $t('Show as freelancer in the tool') }}</span>
            </SwitchLabel>
        </SwitchGroup>
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
        <div class="headline6Light mb-3">
            {{ $t('Shift planner for')}}
        </div>
        <div class="inline-flex gap-2">
            <div v-for="(craft, index) in user.accessibleCrafts" class="group block shrink-0 bg-gray-50 w-fit pr-3 rounded-full border border-gray-300">
                <div class="flex items-center">
                    <div>
                        <div class="block size-8  rounded-full object-cover border-2" :style="{backgroundColor: backgroundColorWithOpacityOld(craft.color, 75), borderColor: craft.color}" />
                    </div>
                    <div class="mx-2">
                        <p class="xsDark group-hover:text-gray-900">{{ craft.name}}</p>
                    </div>
                </div>
            </div>
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
        <div class="flex items-center">
            <Listbox as="div" id="selectedCraftToAdd" class="relative" v-model="this.selectedCraftToAssign">
                <ListboxButton class="menu-button w-96">
                    <div v-if="this.selectedCraftToAssign" class="flex-grow text-left">
                        {{ this.user.assignableCrafts.find(assignableCraft => assignableCraft.id === this.selectedCraftToAssign)?.name }}
                    </div>
                    <div v-else class="flex-grow xsLight text-left subpixel-antialiased">
                        {{  $t('Select craft') }}
                    </div>
                    <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                </ListboxButton>
                <ListboxOptions class="bg-artwork-navigation-background w-full max-h-32 min-h-16 overflow-y-auto text-sm absolute rounded-lg">
                    <ListboxOption v-if="this.user.assignableCrafts.length === 0" :key="0" :value="null" class="hover:bg-artwork-buttons-create text-secondary cursor-pointer p-2 flex justify-between ">
                        <div class="h-5 text-gray-300">
                            {{ $t('There are no other crafts that can be assigned.') }}
                        </div>
                    </ListboxOption>
                    <ListboxOption v-else v-for="assignableCraft in this.user.assignableCrafts"
                                   class="hover:bg-artwork-buttons-create text-white cursor-pointer p-2 flex justify-between "
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
        <div class="inline-flex gap-2 mt-4">
            <div v-for="(craft, index) in user.assignedCrafts" class="group block shrink-0 bg-gray-50 w-fit pr-3 rounded-full border border-gray-300">
                <div class="flex items-center">
                    <div>
                        <div class="block size-8  rounded-full object-cover border-2" :style="{backgroundColor: backgroundColorWithOpacityOld(craft.color, 75), borderColor: craft.color}" />
                    </div>
                    <div class="mx-2">
                        <p class="xsDark group-hover:text-gray-900">{{ craft.name}}</p>
                    </div>
                    <div class="flex items-center">
                        <button type="button" @click="removeCraft(craft.id)">
                            <XIcon class="h-4 w-4 text-gray-400 hover:text-error" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Permissions from "@/Mixins/Permissions.vue";
import Input from "@/Jetstream/Input.vue";
import {router, useForm} from "@inertiajs/vue3";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions, Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";
import {CheckIcon} from "@heroicons/vue/solid";
import {ChevronDownIcon, XIcon} from "@heroicons/vue/outline";
import {nextTick, reactive} from "vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import Button from "@/Jetstream/Button.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";

export default {
    components: {
        BaseTextarea,
        BaseInput,
        Button, XIcon,
        TextInputComponent,
        TextareaComponent,
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
                workDescription: this.user.work_description,
                is_freelancer: this.user.is_freelancer,
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
                nextTick( () => {
                    if (this.workProfileForm.isDirty) {
                        this.workProfileForm.patch(
                            route(desiredRoute, routeParameter),
                            {
                                preserveScroll:true,
                                preserveState:true
                            }
                        );
                    }
                })
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
        },
        backgroundColorWithOpacityOld(color, percent = 15) {
            if (!color) return `rgba(255, 255, 255, ${percent / 100})`;
            return `rgba(${parseInt(color.slice(-6, -4), 16)}, ${parseInt(color.slice(-4, -2), 16)}, ${parseInt(color.slice(-2), 16)}, ${percent / 100})`;
        }
    }
}
</script>
