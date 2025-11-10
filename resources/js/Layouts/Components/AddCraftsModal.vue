<template>
<ArtworkBaseModal v-if="open" @close="closeModal" title="Craft" description="Define the specifications of your trade.">
    <div class="grid grid-cols-1 sm:grid-cols-7 gap-4 my-4">
        <div class="col-span-1">
            <ColorPickerComponent :color="craft.color" @updateColor="addColor"/>
        </div>
        <div class="col-span-3">
            <BaseInput
                :label="$t('Name of the craft') + '*'"
                v-model="craft.name"
                id="name"
                required
            />
        </div>
        <div class="col-span-3">
            <BaseInput
                :label="$t('Abbreviation') + '*'"
                v-model="craft.abbreviation"
                :maxlength="3"
                id="abbreviation"
                required
            />
        </div>
    </div>
    <div class="">
        <BaseInput type="number"
               v-model="craft.notify_days"
               :maxlength="3"
               required
                id="notify_days"
               :label="$t('Days until notification if shift not fully staffed')"
               :min="0" :max="100"
        />
    </div>
    <div class="my-3">
        <div class="relative flex items-start mb-2">
            <div class="flex h-6 items-center">
                <input id="universally_applicable" v-model="craft.universally_applicable" aria-describedby="comments-description" name="comments" type="checkbox" class="input-checklist" />
            </div>
            <div class="ml-2 text-sm leading-6">
                <label for="universally_applicable" class="font-medium">{{ $t('Universally applicable') }}</label>
            </div>
        </div>
    </div>
    <div class="mt-3">
        <SwitchGroup as="div" class="flex items-center gap-2">
            <SwitchLabel as="span" class="mr-3 text-sm">
                <span class="font-medium text-gray-900" :class="enabled ? '!text-gray-400' : ''">{{ $t('Allocable to a limited extent')}}</span>
            </SwitchLabel>
            <Switch v-model="enabled" :class="[enabled ? 'bg-artwork-buttons-create' : 'bg-gray-200', 'relative inline-flex h-3 w-8 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2']">
                <span aria-hidden="true" :class="[enabled ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
            </Switch>
            <SwitchLabel as="span" class="ml-3 text-sm">
                <span class="font-medium text-gray-900" :class="!enabled ? '!text-gray-400' : ''">{{ $t('Can be scheduled by all shift planners')}}</span>
            </SwitchLabel>
        </SwitchGroup>
    </div>
    <div v-if="!enabled" class="">
        <Listbox as="div">
            <div class="relative mt-2">
                <ListboxButton class="menu-button">
                    <span class="block truncate text-left">
                        {{ $t('Select users')}}
                    </span>
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                        <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                    </span>
                </ListboxButton>
                <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                    <ListboxOptions class="absolute z-50 mt-1 max-h-28 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                        <ListboxOption as="template" v-for="user in usersWithPermission" :key="user.id" :value="user" v-slot="{ active, selected }">
                            <li @click="addOrRemoveFormUserList(user, 'shift_planer')" :class="'relative cursor-default select-none py-2 pl-3 pr-9'">
                                <span>{{ user.full_name }}</span>
                            </li>
                        </ListboxOption>
                    </ListboxOptions>
                </transition>
            </div>
        </Listbox>
        <div class="mt-3">
            <div v-for="user in craftShiftPlaner" class="my-2">
                <div class="flex col-span-2">
                    <div class="flex items-center">
                        <img class="flex h-11 w-11 rounded-full" :src="user.profile_photo_url" alt=""/>
                        <span class="flex ml-4">
                            {{ user.first_name }} {{ user.last_name }}
                        </span>
                    </div>
                    <button type="button" @click="addOrRemoveFormUserList(user, 'shift_planer')">
                        <span class="sr-only">{{ $t('Remove user from team')}}</span>
                        <XCircleIcon class="ml-3 text-artwork-buttons-create h-5 w-5 hover:text-error "/>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-3">
        <div class="my-5">
            <BasePageTitle
                :title="$t('Inventory settings')"
                :description="$t('Here you can specify who is responsible for planning the inventory. Only users who are entered here can plan the inventory for this trade. The users must have the right to plan inventory.')"
            />
        </div>
        <SwitchGroup as="div" class="flex items-center gap-2">
            <SwitchLabel as="span" class="mr-3 text-sm">
                <span class="font-medium text-gray-900" :class="inventoryPlannedByAll ? '!text-gray-400' : ''">{{ $t('Explicitly selected persons') }}</span>
            </SwitchLabel>
            <Switch v-model="inventoryPlannedByAll" :class="[inventoryPlannedByAll ? 'bg-artwork-buttons-create' : 'bg-gray-200', 'relative inline-flex h-3 w-8 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2']">
                <span aria-hidden="true" :class="[inventoryPlannedByAll ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
            </Switch>
            <SwitchLabel as="span" class="ml-3 text-sm">
                <span class="font-medium text-gray-900" :class="!inventoryPlannedByAll ? '!text-gray-400' : ''">
                    {{ $t('From all planners') }}
                </span>
            </SwitchLabel>
        </SwitchGroup>
    </div>
    <div v-if="!inventoryPlannedByAll">
        <Listbox as="div">
            <div class="relative mt-2">
                <ListboxButton class="menu-button">
                    <span class="block truncate text-left">
                        {{ $t('Select users')}}
                    </span>
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                        <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                    </span>
                </ListboxButton>
                <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                    <ListboxOptions class="absolute z-50 mt-1 max-h-28 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                        <ListboxOption as="template" v-for="user in usersWithInventoryPermission" :key="user.id" :value="user" v-slot="{ active, selected }">
                            <li @click="addOrRemoveFormUserList(user, 'inventory')" :class="'relative cursor-default select-none py-2 pl-3 pr-9'">
                                <span>{{ user.full_name }}</span>
                            </li>
                        </ListboxOption>
                    </ListboxOptions>
                </transition>
            </div>
        </Listbox>
        <div class="mt-3">
            <div v-for="user in craftInventoryPlaner" class="my-2">
                <div class="flex col-span-2">
                    <div class="flex items-center">
                        <img class="flex h-11 w-11 rounded-full" :src="user.profile_photo_url" alt=""/>
                        <span class="flex ml-4">
                            {{ user.first_name }} {{ user.last_name }}
                        </span>
                    </div>
                    <button type="button" @click="addOrRemoveFormUserList(user, 'inventory')">
                        <span class="sr-only">{{ $t('Remove user from team')}}</span>
                        <IconCircleX stroke-width="1.5" class="ml-3 text-primary h-5 w-5 hover:text-error "/>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="my-5">
        <TinyPageHeadline
            :title="$t('Craft manager')"
            :description="$t('Here you can specify the department management for this craft. It will be highlighted in the overview.')"
        />
    </div>
    <div class="mt-8">
        <UserSearch :label="'Add department management'"
                    @user-selected="addSelectedToCraftManagers"
                    :search-workers="false"
                    :current-craft="craft"
                    :dont-close-on-select="false"/>
        <div class="mt-3">
            <div v-for="(user,index) in this.managers" class="my-2">
                <div class="flex col-span-2">
                    <div class="flex items-center">
                        <img class="flex h-11 w-11 rounded-full" :src="user.profile_photo_url" alt=""/>
                        <span class="flex ml-4">
                            {{ user.first_name }} {{ user.last_name }}
                        </span>
                    </div>
                    <button type="button" @click="this.deleteDepartmentManager(user)">
                        <span class="sr-only">{{ $t('Delete department management') }}</span>
                        <XCircleIcon class="ml-3 text-artwork-buttons-create h-5 w-5 hover:text-error "/>
                    </button>
                </div>
            </div>
        </div>
<!--            <div class="flex items-center">-->
<!--                <img class="h-5 w-5 mr-2 object-cover rounded-full"-->
<!--                     :src="user.profile_photo_url"-->
<!--                     alt=""/>-->
<!--                <template v-if="user.provider_name">-->
<!--                    {{ user.provider_name }}-->
<!--                </template>-->
<!--                <template v-else>-->
<!--                    {{ user.first_name }} {{ user.last_name }}-->
<!--                </template>-->
<!--            </div>-->
<!--            <button type="button" @click="this.deleteDepartmentManager(user)">-->
<!--                <span class="sr-only">{{ $t('Delete department management') }}</span>-->
<!--                <XCircleIcon class="ml-2 mt-1 h-5 w-5 hover:text-error text-white bg-artwork-navigation-background rounded-full"/>-->
<!--            </button>-->
<!--        </div>-->
    </div>
    <div class="flex items-center justify-center mt-5">
        <FormButton :text="$t('Save')" @click="saveCraft"/>
    </div>
</ArtworkBaseModal>
</template>

<script>
import {defineComponent} from 'vue'
import {
    Dialog,
    DialogPanel,
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    Switch,
    SwitchGroup,
    SwitchLabel,
    TransitionChild,
    TransitionRoot
} from "@headlessui/vue";
import {CheckIcon, XCircleIcon, XIcon} from "@heroicons/vue/solid";
import {ChevronDownIcon} from "@heroicons/vue/outline";
import Input from "@/Jetstream/Input.vue";
import {useForm} from "@inertiajs/vue3";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import ColorPickerComponent from "@/Components/Globale/ColorPickerComponent.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import NumberInputComponent from "@/Components/Inputs/NumberInputComponent.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";

export default defineComponent({
    name: "AddCraftsModal",
    mixins: [IconLib],
    components: {
        ArtworkBaseModal,
        BasePageTitle,
        BaseInput,
        UserSearch,
        TinyPageHeadline,
        NumberInputComponent,
        TextInputComponent,
        ModalHeader,
        BaseModal,
        ColorPickerComponent,
        FormButton,
        XCircleIcon,
        TagComponent,
        Input,
        ChevronDownIcon,
        CheckIcon,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        Listbox,
        Dialog,
        TransitionChild,
        XIcon,
        TransitionRoot,
        DialogPanel,
        SwitchGroup,
        Switch,
        SwitchLabel
    },
    props: ['craftToEdit', 'usersWithPermission', 'usersWithInventoryPermission'],
    data(){
        return {
            open: true,
            craft: useForm({
                name: this.craftToEdit ? this.craftToEdit.name : '',
                abbreviation: this.craftToEdit ? this.craftToEdit.abbreviation : '',
                users: [],
                assignable_by_all: true,
                inventory_planned_by_all: true,
                color: this.craftToEdit ? this.craftToEdit.color : '#ffffff',
                notify_days: this.craftToEdit ? this.craftToEdit.notify_days : 0,
                universally_applicable: this.craftToEdit ? this.craftToEdit.universally_applicable : false,
                users_for_inventory: [],
                managersToBeAssigned: []
            }),
            enabled: this.craftToEdit ? this.craftToEdit.assignable_by_all : true,
            craftInventoryPlaner: this.craftToEdit ? this.craftToEdit.craft_inventory_planer : [],
            inventoryPlannedByAll: this.craftToEdit ? this.craftToEdit.inventory_planned_by_all : true,
            craftShiftPlaner: this.craftToEdit ? this.craftToEdit.craft_shift_planer : [],
            managers: this.craftToEdit ? this.craftToEdit.managing_freelancers.concat(
                    this.craftToEdit.managing_service_providers.concat(
                        this.craftToEdit.managing_users
                    )
                ) : []
        }
    },
    unmounted() {
        this.craft.reset('name', 'abbreviation', 'users', 'assignable_by_all', 'users_for_inventory', 'inventory_planned_by_all')
    },
    emits: ['closed'],
    methods: {
        closeModal(bool){
            this.craft.reset('name', 'abbreviation', 'users', 'assignable_by_all', 'users_for_inventory', 'inventory_planned_by_all')
            this.$emit('closed', bool)
        },
        addOrRemoveFormUserList(user, type = 'shift_planer'){
            if(type === 'shift_planer') {
                const userIds = this.craftShiftPlaner.map(user => user.id);
                if(userIds.includes(user.id)){
                    this.craftShiftPlaner = this.craftShiftPlaner.filter(u => u.id !== user.id)
                } else {
                    this.craftShiftPlaner.push(user)
                }
            }

            if (type === 'inventory') {
                const userIds = this.craftInventoryPlaner.map(user => user.id);
                if(userIds.includes(user.id)){
                    this.craftInventoryPlaner = this.craftInventoryPlaner.filter(u => u.id !== user.id)
                } else {
                    this.craftInventoryPlaner.push(user)
                }
            }
        },
        saveCraft(){
            this.managers.forEach((manager) => {
                this.craft.managersToBeAssigned.push({
                    manager_id: manager.id ?? manager.pivot.craft_manager_id,
                    manager_type: manager.manager_type ?? manager.pivot.craft_manager_type
                });
            });

            if (this.craft.notify_days < 0) {
                this.craft.notify_days = 0;
            }

            if (!this.enabled) {
                this.craft.assignable_by_all = false
                this.craftShiftPlaner.forEach((user) => {
                    this.craft.users.push(user.id);
                });
            } else {
                this.craft.assignable_by_all = true;
                this.craft.users = [];
            }

            if(!this.inventoryPlannedByAll){
                this.craft.inventory_planned_by_all = false
                this.craftInventoryPlaner.forEach((user) => {
                    this.craft.users_for_inventory.push(user.id);
                })
            } else {
                this.craft.inventory_planned_by_all = true;
                this.craft.users_for_inventory = [];
            }

            if(this.craftToEdit){
                this.craft.patch(route('craft.update', this.craftToEdit.id), {
                    preserveState: true,
                    preserveScroll: true,
                    onFinish: () => {
                        this.craft.reset('name', 'abbreviation', 'users', 'assignable_by_all')
                        this.closeModal(true)
                    }
                })
            } else {
                this.craft.post(route('craft.store'), {
                    preserveState: true,
                    preserveScroll: true,
                    onFinish: () => {
                        this.craft.reset('name', 'abbreviation', 'users', 'assignable_by_all')
                        this.closeModal(true)
                    }
                })
            }
        },
        addColor(color) {
            this.craft.color = color;
        },
        addSelectedToCraftManagers(user) {
            if (this.managers.findIndex((manager) => user.id === manager.id) > -1) {
                return;
            }

            this.managers.push(user);
        },
        deleteDepartmentManager(manager) {
            this.managers.splice(
                this.managers.findIndex((currentManager) => currentManager.id === manager.id),
                1
            );
        }
    }
})
</script>
