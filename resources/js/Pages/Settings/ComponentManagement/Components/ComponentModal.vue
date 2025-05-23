<template>
    <BaseModal v-if="show" @closed="closeModal">
        <div class="relative z-40 px-4">
            <ModalHeader :title="isCreateMode() ? $t('Create a new component') : $t('Edit component')"/>
            <div class="grid grid-cols-1 gap-4">
                <Listbox v-if="this.isCreateMode()" as="div" v-model="selectedType">
                    <ListboxLabel class="xsLight">{{$t('Component Layout')}}</ListboxLabel>
                    <div class="relative mt-2">
                        <ListboxButton class="menu-button">
                            <div class="block truncate">{{ $t(selectedType?.name) }}</div>
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                <IconChevronDown class="h-5 w-5 text-gray-400" aria-hidden="true" />
                            </span>
                        </ListboxButton>
                        <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                            <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                                <ListboxOption as="template" v-for="componentTyp in tabComponentTypes" :key="componentTyp.name" :value="componentTyp" v-slot="{ active, selected }">
                                    <li :class="[active ? 'bg-indigo-600 text-white' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                        <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ $t(componentTyp.name) }}</span>
                                        <span v-if="selected" :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                            <IconCircleCheck class="h-5 w-5" aria-hidden="true" />
                                        </span>
                                    </li>
                                </ListboxOption>
                            </ListboxOptions>
                        </transition>
                    </div>
                </Listbox>
                <div>
                    <BaseInput
                        :label="$t('Name of the component')"
                        v-model="componentName"
                        id="componentName"
                    />
                    <span v-show="helpTexts.name" class="mt-1 text-xs text-red-500">
                                            {{ helpTexts.name }}
                                        </span>
                </div>
                <div v-if="!this.componentToEdit?.special" class="grid grid-cols-1 gap-4">
                    <div class="headline4">
                        {{ this.isCreateMode() ? $t('Enter basic data') : $t('Edit basic data') }}
                    </div>
                    <div v-for="(text, index) in textData">
                        <div class="" v-if="index === 'title'">
                            <BaseInput
                                :label="$t('Title')"
                                v-model="textData.title"
                                :id="index"
                            />
                        </div>
                        <div class="" v-if="index === 'label'">
                            <BaseInput
                                :label="$t('label')"
                                v-model="textData.label"
                                :id="index"
                            />
                        </div>
                        <div class="" v-if="index === 'text'">
                            <BaseInput
                                :label="$t('Text')"
                                v-model="textData.text"
                                :id="index"
                            />
                        </div>
                        <div class="" v-if="index === 'placeholder'">
                            <BaseInput
                                :label="$t('Placeholder')"
                                v-model="textData.placeholder"
                                :id="index"
                            />
                        </div>
                        <div class="" v-if="index === 'height'">
                            <label :for="index" class="xsLight">{{ $t('Height - ({0} pixels)', [textData.height])}}</label>
                            <div class="mt-1">
                                <input type="range" v-model="textData.height" min="0" max="150" class="h-12 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300" />
                            </div>
                        </div>
                        <div class="" v-if="index === 'title_size'">
                            <label :for="index" class="xsLight">{{ $t('Font Size - ({0} pixels)', [textData.title_size])}}</label>
                            <div class="mt-1">
                                <input type="range" v-model="textData.title_size" min="10" max="35" class="h-12 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300" />
                            </div>
                        </div>
                        <div class="relative flex items-start"  v-if="index === 'showLine'">
                            <div class="flex h-6 items-center">
                                <input :id="index"  v-model="textData.showLine" :checked="textData.showLine" aria-describedby="comments-description" name="comments" type="checkbox" class="input-checklist" />
                            </div>
                            <div class="ml-3 text-sm leading-6">
                                <label :for="index" class="xsLight">{{ $t('Show a separator line')}} </label>
                            </div>
                        </div>
                        <div class="relative flex items-start"  v-if="index === 'checked'">
                            <div class="flex h-6 items-center">
                                <input :id="index"  v-model="textData.checked" :checked="textData.checked" aria-describedby="comments-description" name="comments" type="checkbox" class="input-checklist" />
                            </div>
                            <div class="ml-3 text-sm leading-6">
                                <label :for="index" class="xsLight">{{ $t('This checkbox is activated by default')}} </label>
                            </div>
                        </div>
                    </div>
                    <div v-if="textData.options?.length > 0" class="grid grid-cols-1 gap-4">
                        <div class="" v-for="(field, optionIndex) in textData.options">
                            <div>
                                <BaseInput v-model="textData.options[optionIndex].value" :label="'Option (' + (optionIndex + 1) + ')'" :id="'option-' + optionIndex" />
                                <span v-if="optionIndex !== 0" class="text-xs text-end underline underline-offset-2 text-artwork-buttons-create cursor-pointer" @click="removeOption(optionIndex)">
                                    Option ({{ optionIndex + 1 }}) {{ $t('Remove') }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center justify-end">
                            <div class="text-xs underline underline-offset-2 text-artwork-buttons-create cursor-pointer" @click="addMoreOneOption">{{ $t('Add another option')}}</div>
                        </div>
                        <div v-if="textData.options[0].value">
                            <Listbox as="div" v-model="textData.selected">
                                <div class="relative mt-2">
                                    <ListboxButton class="menu-button-no-padding relative">
                                        <div class="truncate">
                                            <div class="top-2 left-4 absolute text-gray-500 text-xs">
                                                {{ $t('Standard Option') }}
                                            </div>
                                            <div class="pt-6 pb-2 flex items-center gap-x-2">
                                                <div class="truncate">
                                                    {{ $t('Standard Option') }}
                                                </div>
                                            </div>
                                        </div>
                                        <IconChevronDown class="h-5 w-5 text-primary" aria-hidden="true"/>
                                    </ListboxButton>
                                    <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                        <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                                            <ListboxOption as="template" v-for="option in textData.options" :key="option.value" :value="option.value" v-slot="{ active, selected }">
                                                <li :class="[active ? 'bg-indigo-600 text-white' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                                    <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ option.value }}</span>

                                                    <span v-if="selected" :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                                <IconCircleCheck class="h-5 w-5" aria-hidden="true" />
                                                              </span>
                                                </li>
                                            </ListboxOption>
                                        </ListboxOptions>
                                    </transition>
                                </div>
                            </Listbox>
                            <div class="flex items-center justify-end mt-3">
                                <div class="text-xs underline underline-offset-2 text-artwork-buttons-create cursor-pointer" @click="textData.selected = ''">{{ $t('Remove default option') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="this.isComponentQualifiedForPermissions()" class="mb-4">
                <div class="mt-3 mb-2 font-bold text-sm">
                    {{ $t('Configure component permissions') }}
                </div>
                <div class="flex flex-col space-y-1">
                    <div class="flex flex-row items-center space-x-1">
                        <input id="allSeeAndEdit"
                               type="radio"
                               v-model="this.modulePermissions.permission_type"
                               value="allSeeAndEdit"
                        />
                        <label for="allSeeAndEdit" class="xsLight">
                            {{ $t('Everyone can see and edit') }}
                        </label>
                    </div>
                    <div class="flex flex-col space-y-1 relative">
                        <div class="flex flex-row space-x-1">
                            <input id="allSeeSomeEdit" type="radio" v-model="this.modulePermissions.permission_type" value="allSeeSomeEdit"/>
                            <label for="allSeeSomeEdit" class="xsLight">
                                {{ $t('Everyone can see, but editing is just allowed for:') }}
                            </label>
                        </div>
                        <div  v-if="this.modulePermissions.permission_type === 'allSeeSomeEdit'">
                            <div class="mt-4">
                                <BaseInput
                                    :label="$t('Search for teams and/or users')"
                                    @input="this.searchUsersAndTeams()"
                                    v-model="this.userAndTeamsQuery"
                                    id="searchUsersAndTeams"/>
                            </div>
                            <div v-if="(this.userAndTeamsSearchResult?.users.length > 0 || this.userAndTeamsSearchResult?.departments.length > 0) && this.userAndTeamsQuery.length > 0"
                                 class="absolute z-10 mt-1 w-full max-h-60 bg-primary shadow-lg rounded-lg
                                                            text-base ring-1 ring-black ring-opacity-5
                                                            overflow-auto focus:outline-none sm:text-sm">
                                <div class="border-gray-200">
                                    <div v-for="(user, index) in this.userAndTeamsSearchResult.users" :key="index"
                                         class="flex items-center cursor-pointer">
                                        <div class="flex-1 text-sm py-4">
                                            <p @click="this.addUser(user)"
                                               class="font-bold px-4 flex text-white items-center hover:border-l-4 hover:border-l-success">
                                                <img :src="user.profile_photo_url" :alt="user.name"
                                                     class="rounded-full h-8 w-8 object-cover"/>
                                                <span class="ml-2 truncate">
                                                                        {{ user.first_name }} {{ user.last_name }}
                                                                    </span>
                                            </p>
                                        </div>
                                    </div>
                                    <div v-for="(department, index) in this.userAndTeamsSearchResult.departments"
                                         :key="index"
                                         class="flex items-center cursor-pointer">
                                        <div class="flex-1 text-sm py-4">
                                            <p @click="this.addDepartment(department)"
                                               class="font-bold flex items-center px-4 text-white hover:border-l-4 hover:border-l-success">
                                                <TeamIconCollection :iconName="department.svg_name"
                                                                    :alt="department.name"
                                                                    class="rounded-full h-8 w-8 object-cover"/>
                                                <span class="ml-2">
                                                    {{ department.name }}
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div v-for="user in this.modulePermissions.users"
                                     class="flex flex-row justify-between mt-4 mr-1 items-center font-bold text-primary border-1 border-b pb-3">
                                    <div class="flex items-center">
                                        <img class="flex h-11 w-11 rounded-full"
                                             :src="user.profile_photo_url"
                                             alt=""/>
                                        <span class="flex ml-4">
                                                                {{ user.first_name }} {{ user.last_name }}
                                                            </span>
                                    </div>
                                    <button type="button" @click="this.removeUser(user)">
                                        <XCircleIcon class="text-primary h-5 w-5 hover:text-error"/>
                                    </button>
                                </div>
                                <div v-for="department in this.modulePermissions.departments"
                                     class="flex flex-row justify-between mt-4 mr-1 rounded-full items-center font-bold text-primary">
                                    <div class="flex items-center">
                                        <TeamIconCollection :iconName="department.svg_name" :alt="department.name"
                                                            class="rounded-full h-11 w-11 object-cover"/>
                                        <span class="flex ml-4">
                                                                {{ department.name }}
                                                            </span>
                                    </div>
                                    <button type="button" @click="this.removeDepartment(department)">
                                        <XCircleIcon class="text-primary h-5 w-5 hover:text-error"/>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <div class="flex flex-row items-center space-x-1">
                            <input id="someSeeSomeEdit" type="radio" v-model="this.modulePermissions.permission_type" value="someSeeSomeEdit"/>
                            <label for="someSeeSomeEdit" class="xsLight">Sehen darf nur:</label>
                        </div>
                        <div class="mt-4 relative" v-if="this.modulePermissions.permission_type === 'someSeeSomeEdit'">
                            <BaseInput
                                id="searchUsersAndTeams"
                                :label="$t('Search for teams and/or users')"
                                @input="this.searchUsersAndTeams()"
                                v-model="this.userAndTeamsQuery"
                            />
                            <div v-if="(this.userAndTeamsSearchResult?.users.length > 0 || this.userAndTeamsSearchResult?.departments.length > 0) && this.userAndTeamsQuery.length > 0"
                                 class="absolute z-10 mt-1 w-full max-h-60 bg-primary shadow-lg rounded-lg
                                                            text-base ring-1 ring-black ring-opacity-5
                                                            overflow-auto focus:outline-none sm:text-sm">
                                <div class="border-gray-200">
                                    <div v-for="(user, index) in this.userAndTeamsSearchResult.users" :key="index"
                                         class="flex items-center cursor-pointer">
                                        <div class="flex-1 text-sm py-4">
                                            <p @click="this.addUser(user)"
                                               class="font-bold px-4 flex text-white items-center hover:border-l-4 hover:border-l-success">
                                                <img :src="user.profile_photo_url" :alt="user.name"
                                                     class="rounded-full h-8 w-8 object-cover"/>
                                                <span class="ml-2 truncate">
                                                                        {{ user.first_name }} {{ user.last_name }}
                                                                    </span>
                                            </p>
                                        </div>
                                    </div>
                                    <div v-for="(department, index) in this.userAndTeamsSearchResult.departments"
                                         :key="index"
                                         class="flex items-center cursor-pointer">
                                        <div class="flex-1 text-sm py-4">
                                            <p @click="this.addDepartment(department)"
                                               class="font-bold flex items-center px-4 text-white hover:border-l-4 hover:border-l-success">
                                                <TeamIconCollection :iconName="department.svg_name"
                                                                    :alt="department.name"
                                                                    class="rounded-full h-8 w-8 object-cover"/>
                                                <span class="ml-2">
                                                                        {{ department.name }}
                                                                    </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div v-for="user in this.modulePermissions.users"
                                     class="flex flex-row justify-between mt-4 mr-1 items-center font-bold text-primary border-1 border-b pb-3">
                                    <div class="flex flex-row items-center space-x-3">
                                        <img class="flex h-11 w-11 rounded-full"
                                             :src="user.profile_photo_url"
                                             alt=""/>
                                        <span class="flex ml-4">
                                            {{ user.first_name }} {{ user.last_name }}
                                        </span>
                                        <div class="flex flex-row space-x-1">
                                            <input v-model="user.pivot.can_write"
                                                   type="checkbox"
                                                   class="input-checklist"/>
                                            <p :class="[user.pivot.can_write ? 'text-primary font-black' : 'text-secondary']"
                                               class="my-auto text-sm">{{ $t('Write permission') }}</p>
                                        </div>
                                    </div>
                                    <button type="button" @click="this.removeUser(user)">
                                        <XCircleIcon class="ml-3 text-primary h-5 w-5 hover:text-error "/>
                                    </button>
                                </div>
                                <div v-for="department in this.modulePermissions.departments"
                                     class="flex flex-row justify-between mt-4 mr-1 items-center font-bold text-primary border-1 border-b pb-3">
                                    <div class="flex flex-row items-center space-x-3">
                                        <TeamIconCollection :iconName="department.svg_name" :alt="department.name"
                                                            class="rounded-full h-11 w-11 object-cover"/>
                                        <span class="flex ml-4">
                                            {{ department.name }}
                                        </span>
                                        <div class="flex flex-row space-x-1">
                                            <input v-model="department.pivot.can_write"
                                                   type="checkbox"
                                                   class="input-checklist"/>
                                            <p :class="[department.pivot.can_write ? 'text-primary font-black' : 'text-secondary']"
                                               class="my-auto text-sm">{{ $t('Write permission') }}</p>
                                        </div>
                                    </div>
                                    <button type="button" @click="this.removeDepartment(department)">
                                        <XCircleIcon class="text-primary h-5 w-5 hover:text-error"/>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else-if="!this.isComponentQualifiedForPermissions() && !(this.componentToEdit?.type === 'Title')"
                 class="xsLight">
                {{ $t('The permissions for this component are administered via the user settings and the project.') }}
            </div>
            <div class="flex justify-between items-center mt-5">
                <FormButton
                    @click="this.updateOrSaveComponent(true)"
                    :text="this.isCreateMode() ? $t('Create') : $t('Save')" />
                <p class="cursor-pointer text-sm mt-3 text-secondary" @click="this.closeModal()">
                    {{ $t('No, not really') }}
                </p>
            </div>
        </div>
    </BaseModal>
</template>

<script>
import {defineComponent} from "vue";
import IconLib from "@/Mixins/IconLib.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import {
    Dialog,
    DialogPanel,
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
    TransitionChild,
    TransitionRoot
} from "@headlessui/vue";
import inputComponent from "@/Layouts/Components/InputComponent.vue";
import Input from "@/Layouts/Components/InputComponent.vue";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import {XCircleIcon} from "@heroicons/vue/solid";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

export default defineComponent({
    name: "ComponentModal",
    mixins: [IconLib],
    components: {
        BaseInput,
        BaseModal,
        TextInputComponent,
        ModalHeader,
        Input,
        XCircleIcon,
        TeamIconCollection,
        Dialog,
        DialogPanel,
        TransitionChild,
        TransitionRoot,
        Listbox,
        ListboxOption,
        ListboxOptions,
        ListboxButton,
        ListboxLabel,
        FormButton,
        inputComponent
    },
    emits: ['close'],
    props: [
        'show',
        'mode',
        'tabComponentTypes',
        'componentToEdit'
    ],
    data() {
        return {
            userAndTeamsQuery: '',
            userAndTeamsSearchResult: null,
            modulePermissions: {
                permission_type: this.isCreateMode() ? 'allSeeAndEdit' : this.componentToEdit.permission_type,
                users: this.isCreateMode() ? [] : this.componentToEdit.users,
                departments: this.isCreateMode() ? [] : this.componentToEdit.departments
            },
            textData: this.isCreateMode() ?
                (this.tabComponentTypes ? JSON.parse(JSON.stringify(this.tabComponentTypes['TextField'].availableFields)) : {}) :
                (this.componentToEdit ? JSON.parse(JSON.stringify(this.componentToEdit.data)) : {}),
            componentName: this.isCreateMode() ? '' : (this.componentToEdit ? this.componentToEdit.name : ''),
            selectedType: this.isCreateMode() ? this.tabComponentTypes['TextField'] : null,
            helpTexts: {
                name: null
            }
        };
    },
    methods: {
        isCreateMode() {
            return this.mode === 'create';
        },
        isComponentQualifiedForPermissions() {
            if (this.isCreateMode()) {
                return this.selectedType.name !== 'SeparatorComponent';
            }

            return this.componentToEdit.type !== 'Title' &&
                this.componentToEdit.type !== 'SeparatorComponent' &&
                this.componentToEdit.type !== 'ShiftTab' &&
                this.componentToEdit.type !== 'BudgetTab' &&
                this.componentToEdit.type !== 'CalendarTab';
        },
        resetSearch() {
            this.userAndTeamsQuery = '';
            this.userAndTeamsSearchResult = null;
        },
        findModulePermissionsUserIndex(userId) {
            return this.modulePermissions.users.findIndex((user) => user.id === userId);
        },
        findModulePermissionsDepartmentIndex(departmentId) {
            return this.modulePermissions.departments.findIndex((department) => department.id === departmentId);
        },
        addUser(user) {
            if (this.findModulePermissionsUserIndex(user.id) < 0) {
                this.modulePermissions.users.push(user);
            }

            this.resetSearch();
        },
        removeUser(user) {
            this.modulePermissions.users.splice(
                this.findModulePermissionsUserIndex(user.id),
                1
            );
        },
        addDepartment(department) {
            if (this.findModulePermissionsDepartmentIndex(department.id) < 0) {
                this.modulePermissions.departments.push(department);
            }

            this.resetSearch();
        },
        removeDepartment(department) {
            this.modulePermissions.departments.splice(
                this.findModulePermissionsDepartmentIndex(department.id),
                1
            );
        },
        searchUsersAndTeams() {
            if (this.userAndTeamsQuery.length > 0) {
                axios.get(
                    route('users_departments.search'),
                    {
                        params: {
                            query: this.userAndTeamsQuery
                        }
                    }
                ).then(
                    response => {
                        //if permission_type is someSeeSomeEdit append pivot object to search results
                        //needed as v-model for checkboxes as search results (user and departments) are not obtained
                        //by a Component-User/Department relation so there is no pivot object
                        if (this.modulePermissions.permission_type === 'someSeeSomeEdit') {
                            response.data.users.forEach((user) => {
                                user.pivot = {
                                    can_write: false
                                };
                            });

                            response.data.departments.forEach((department) => {
                                department.pivot = {
                                    can_write: false
                                }
                            });
                        }
                        this.userAndTeamsSearchResult = response.data;
                    }
                )
            }
        },
        addMoreOneOption() {
            this.textData.options.push({value: ''})
        },
        removeOption(index) {
            this.textData.options.splice(index, 1)
        },
        closeModal() {
            this.$emit('close');
        },
        updateOrSaveComponent() {
            this.helpTexts.name = null;
            if (this.componentName === '') {
                this.helpTexts.name = this.$t('Please enter a name.')
                return;
            }

            let isComponentQualifiedForPermissions = this.isComponentQualifiedForPermissions(),
                desiredRoute = this.isCreateMode() ?
                    route('component.store') :
                    route('component.update', {component: this.componentToEdit.id}),
                payload = {
                    name: this.componentName,
                    data: this.textData,
                    permission_type: isComponentQualifiedForPermissions ?
                        this.modulePermissions.permission_type :
                        null,
                    users: [],
                    departments: [],
                },
                options = {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        this.closeModal();
                    },
                };

            if (this.isCreateMode()) {
                payload.type = this.selectedType.name;
            }

            if (isComponentQualifiedForPermissions) {
                this.modulePermissions.users.forEach(
                    (user) => payload.users.push(
                        {
                            user_id: user.id,
                            can_write: user.pivot ? user.pivot.can_write : null
                        }
                    )
                );
                this.modulePermissions.departments.forEach(
                    (department) => payload.departments.push(
                        {
                            department_id: department.id,
                            can_write: department.pivot ? department.pivot.can_write : null
                        }
                    )
                );
            }

            if (this.isCreateMode()) {
                this.$inertia.post(desiredRoute, payload, options);
                return;
            }

            this.$inertia.patch(desiredRoute, payload, options);
        },
    },
    watch: {
        selectedType: {
            handler(){
                // add the selected type to the textData as copy
                this.textData = JSON.parse(JSON.stringify(this.selectedType.availableFields))
            },
            deep: true
        }
    }
});
</script>
