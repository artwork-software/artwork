<template>
    <AppLayout :title="$t('Shift Settings')">
        <div class="artwork-container">
            <div class="">
                <h2 class="headline1">{{$t('Shift Settings')}}</h2>
                <div class="xsLight mt-2">
                    {{$t('Define global settings for shift scheduling.')}}
                </div>
            </div>
            <TabComponent :tabs="tabs" />
            <div class="mt-10">
                <h3 class="headline2 mb-2">{{}}</h3>
                <p class="xsLight">
                    {{}}
                </p>
            </div>
            <div class="card white p-5">
                <div class="flex items-center justify-between gap-x-3">
                    <div class="w-1/2">
                        <TinyPageHeadline
                            :title="$t('Crafts')"
                            :description="$t('Define crafts to which you can later assign employees and shifts. Additionally, you can specify which users are allowed to assign what type of employee shifts.')"
                        />
                    </div>
                    <div class="flex items-center justify-end">
                        <GlassyIconButton text="New Craft" icon="IconPlus" @click="openAddCraftsModal = true" />
                    </div>
                </div>
                <draggable ghost-class="opacity-50" key="draggableKey" item-key="id" :list="crafts" @start="dragging=true" @end="dragging=false" @change="reorderCrafts(crafts)">
                    <template #item="{element}" :key="element.id">
                        <div :key="element" class="flex justify-between gap-x-6 py-5" :class="dragging? 'cursor-grabbing' : 'cursor-grab'">
                            <div class="flex gap-x-4">
                                <div class="min-w-0 flex-auto">
                                    <p class="text-sm font-semibold leading-6 text-gray-900 flex items-center gap-x-2">
                                        <span class="h-5 w-5 block rounded-full border" :style="{backgroundColor: backgroundColorWithOpacity(element.color), borderColor: TextColorWithDarken(element.color, 90)}"/>
                                        {{ element.name }} ({{ element.abbreviation }})
                                    </p>
                                    <div v-if="element.universally_applicable" class="mt-1 truncate xsLight">
                                        {{ $t('Universally applicable') }}
                                    </div>
                                    <div class="" v-if="element.assignable_by_all">
                                        <p class="mt-1 truncate xsLight">{{$t('Assignable by all schedulers')}}</p>
                                    </div>
                                    <div v-else>
                                        <p class="mt-1 truncate xsLight">
                                            {{$t('Can only be assigned by:')}}
                                            {{ element.craft_shift_planer.map((user) => user.full_name).join(', ') }}
                                        </p>
                                    </div>
                                    <div class="" v-if="element.inventory_planned_by_all">
                                        <p class="mt-1 truncate xsLight">
                                            {{$t('Inventory can be planned by all planners')}}
                                        </p>
                                    </div>
                                    <div v-else>
                                        <p class="mt-1 truncate xsLight">
                                            {{$t('Inventory can only be planned by:')}}
                                            {{ element.craft_inventory_planer.map((user) => user.full_name).join(', ') }}
                                        </p>
                                    </div>
                                    <div class="mt-1 truncate xsLight">
                                        <div v-if="element.notify_days > 0">
                                            {{ $t('Notification of shifts with open demand is sent {0} day(s) before the start of the shift', [element.notify_days]) }}
                                        </div>
                                        <div v-else>
                                            {{ $t('Notification of shifts that are not fully staffed takes place on the same day as the shift starts') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <component is="IconGripVertical" class="h-5 w-5" />
                                <BaseMenu>
                                    <MenuItem @click="updateCraft(element)"
                                              v-slot="{ active }">
                                        <a :class="[active ? '' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <IconEdit stroke-width="1.5"
                                                      class="mr-3 h-5 w-5 text-primaryText "
                                                      aria-hidden="true"/>
                                            {{$t('Edit')}}
                                        </a>
                                    </MenuItem>
                                    <MenuItem @click="openDeleteCraftModal(element)"
                                              v-slot="{ active }">
                                        <a :class="[active ? '' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <IconTrash stroke-width="1.5"
                                                       class="mr-3 h-5 w-5 text-primaryText "
                                                       aria-hidden="true"/>
                                            {{$t('Delete')}}
                                        </a>
                                    </MenuItem>
                                </BaseMenu>
                            </div>
                        </div>

                    </template>
                </draggable>
            </div>
            <div class="mt-10 card white p-5">
                <TinyPageHeadline
                    :title="$t('Shift-relevant Event Types')"
                    :description="$t('Determine which types of events are displayed as shift-relevant by default. These will then automatically appear in the \'shifts\' tab of the project. You can also define additional events as shift-relevant for each project.')"
                />
                <div class="mt-3">
                    <Listbox as="div">
                        <div class="relative mt-2 w-1/2">
                            <ListboxButton class="menu-button">
                                <span class="block truncate text-left pl-3">{{$t('Select Event Types')}}</span>
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                    <IconChevronDown  stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </span>
                            </ListboxButton>

                            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions class="absolute z-50 mt-1 max-h-28 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                                    <ListboxOption as="template" v-for="type in notRelevantEventTypes" :key="type.id" :value="type" v-slot="{ active, selected }">
                                        <li @click="addRelevantEventType(type)" :class="[active ? 'bg-artwork-buttons-create text-white' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                            <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ type.name }}</span>
                                            <span v-if="selected" :class="[active ? 'text-white' : 'text-artwork-buttons-create', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                <IconCheck stroke-width="1.5" class="h-5 w-5" aria-hidden="true" />
                                            </span>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </transition>
                        </div>
                    </Listbox>
                </div>
                <div class="mt-3 flex flex-wrap">
                    <TagComponent v-for="type in relevantEventTypes" :method="removeRelevantEventType" :displayed-text="type.name" :property="type" />
                </div>
            </div>
            <div class="card white p-5 mt-10">
                <div class="flex items-center justify-between">
                    <TinyPageHeadline class=""
                        :title="$t('Qualifications')"
                        :description="$t('Create or edit qualifications')"
                    />
                    <GlassyIconButton text="Neue Qualifikation" icon="IconPlus" @click="this.openShiftQualificationModal('create')" />
                </div>
                <div class="mt-5">
                    <div class="mb-5 xsLight" v-if="shiftQualifications.length === 0">
                        {{$t('No qualifications have been created yet.')}}
                    </div>
                    <ul v-else role="list" class="w-full">
                        <li v-for="(shiftQualification) in shiftQualifications"
                            :key="shiftQualification.id"

                            class="cursor-pointer py-4 pr-4 flex justify-between items-center border-b-2"
                        >

                            <span class="sDark cursor-pointer flex justify-center">
                                <component
                                    stroke-width="1.5"
                                    class="text-black mx-1 size-5"
                                    :is="shiftQualification.icon"
                                />
                                {{ shiftQualification.name }}
                                <span v-if="shiftQualification.available"
                                      class="xxsLight ml-1 mt-1">
                                    {{$t('(Considered for new shifts)')}}
                                </span>
                            </span>
                            <div class="flex gap-x-2">
                                <IconEdit stroke-width="1.5" class="h-5 w-5" aria-hidden="true" @click="openShiftQualificationModal('edit', shiftQualification)"/>
                                <IconTrash v-if="shiftQualification.id > 1" stroke-width="1.5" class="h-5 w-5 text-red-500 cursor-pointer" aria-hidden="true" @click="openDeleteQualificationModal(shiftQualification)"/>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card white p-5 mt-10">
                <div class="flex items-center justify-between">
                    <TinyPageHeadline
                        class=""
                        :title="$t('Time presets for shifts')"
                        :description="$t('Create time presets for layers to be able to assign them quickly and easily later.')"
                    />
                    <GlassyIconButton text="New time preset" icon="IconPlus" @click="showAddShiftPresetModal = true" />
                </div>
                <div class="mt-5">
                    <AlertComponent
                        type="info"
                        show-icon
                        icon-size="h-6 w-6"
                        v-if="shiftTimePresets.length === 0"
                        :text="$t('No time presets for shifts have been created yet.')"
                        text-size="xsLight"
                    />
                    <ul v-else role="list" class="w-full">
                        <li v-for="(shiftTimePreset) in shiftTimePresets" :key="shiftTimePreset.id" class="py-4 pr-4 flex justify-between items-center border-b-2">
                            <div class="sDark">
                                <div>
                                    {{ shiftTimePreset.name }}
                                </div>
                                <div class="flex items-center gap-x-2 text-gray-500 text-xs">
                                    <div>{{ shiftTimePreset.start_time }} - {{ shiftTimePreset.end_time}} </div>
                                    <div v-if="shiftTimePreset.break_time !== 0">{{ $t('Break time')}}: {{ shiftTimePreset.break_time }}
                                        {{ $t('Minutes') }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-x-3">
                                <IconEdit stroke-width="1.5" class="h-5 w-5 cursor-pointer" aria-hidden="true" @click="openAddEditShiftPresetModal(shiftTimePreset)"/>

                                <IconTrash stroke-width="1.5" class="h-5 w-5 text-red-500 cursor-pointer" aria-hidden="true" @click="openDeleteShiftTimePresetModal(shiftTimePreset)"/>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="flex flex-col my-10 gap-2 card white p-5">
                <TinyPageHeadline :title="$t('Sort settings')"
                                  :description="$t('Configure the behaviour of shift plans sort opportunity.')"/>
                <SwitchGroup as="div" class="flex flex-row items-center gap-x-2 cursor-pointer">
                    <SwitchLabel as="span" class='text-sm'>
                        <span :class="[!shiftSettings.use_first_name_for_sort ? 'font-bold' : 'font-medium', 'text-gray-900']">{{ $t('Sort by first name')}}</span>
                    </SwitchLabel>
                    <Switch v-model="shiftSettings.use_first_name_for_sort"
                            @update:model-value="this.updateShiftSettingUseFirstNameSort"
                            :class="[
                                shiftSettings.use_first_name_for_sort ?
                                    'bg-artwork-buttons-create' :
                                    'bg-gray-200',
                                'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2'
                            ]">
                        <span aria-hidden="true" :class="[shiftSettings.use_first_name_for_sort ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                    </Switch>
                    <SwitchLabel as="span" class="text-sm">
                        <span :class="[shiftSettings.use_first_name_for_sort ? 'font-bold' : 'font-medium', 'text-gray-900']">{{ $t('Sort by last name')}}</span>
                    </SwitchLabel>
                </SwitchGroup>
            </div>
        </div>
        <ShiftQualificationModal
            v-if="this.showShiftQualificationModal"
            :show="this.showShiftQualificationModal"
            :mode="this.shiftQualificationModalMode"
            :shift-qualification="this.shiftQualificationModalShiftQualification"
            @close="this.closeShiftQualificationModal"
        />
        <success-modal
            v-if="this.$page.props.flash.success?.shift_qualification"
            :title="$t('Qualification')"
            :description="this.$page.props.flash.success?.shift_qualification"
            :button="$t('Close message')"
            @closed="this.$page.props.flash.success.shift_qualification = null"
        />
        <error-component
            v-if="this.$page.props.flash.error?.shift_qualification"
            :titel="$t('Qualification')"
            :description="this.$page.props.flash.error?.shift_qualification"
            @closed="this.$page.props.flash.error.shift_qualification = null"
            :confirm="$t('Close message')"
        />
        <AddEditShiftTimePreset :time-preset="presetToEdit" @closed="closeShiftPresetModal" v-if="showAddShiftPresetModal" />
        <AddCraftsModal @closed="closeAddCraftModal" v-if="openAddCraftsModal" :craft-to-edit="craftToEdit" :users-with-permission="usersWithPermission" :users-with-inventory-permission="usersWithInventoryPermission" />
        <ConfirmDeleteModal :title="confirmDeleteTitle" :description="confirmDeleteDescription" @closed="closedDeleteCraftModal" @delete="submitDelete" v-if="openConfirmDeleteModal" />
    </AppLayout>
</template>
<script>
import {defineComponent} from 'vue'
import AppLayout from "@/Layouts/AppLayout.vue";
import {CheckIcon, DotsVerticalIcon} from "@heroicons/vue/solid";
import {ChevronDownIcon, DuplicateIcon, PencilAltIcon, TrashIcon} from "@heroicons/vue/outline";
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
    Switch,
    SwitchGroup,
    SwitchLabel
} from "@headlessui/vue";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import AddCraftsModal from "@/Layouts/Components/AddCraftsModal.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import ShiftQualificationModal from "@/Layouts/Components/ShiftQualificationModal.vue";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import ErrorComponent from "@/Layouts/Components/ErrorComponent.vue";
import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";
import IconLib from "@/Mixins/IconLib.vue";
import TabComponent from "@/Components/Tabs/TabComponent.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import ColorHelper from "@/Mixins/ColorHelper.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import AddEditShiftTimePreset from "@/Pages/Settings/Components/AddEditShiftTimePreset.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import draggable from "vuedraggable";
import {router} from "@inertiajs/vue3";
import ShiftQualificationIconCollection from "@/Layouts/Components/ShiftQualificationIconCollection.vue";
import GlassyIconButton from "@/Artwork/Buttons/GlassyIconButton.vue";

export default defineComponent({
    name: "ShiftSettings",
    mixins: [IconLib, ColorHelper],
    components: {
        GlassyIconButton,
        ShiftQualificationIconCollection,
        SwitchLabel,
        Switch,
        SwitchGroup,
        draggable,
        AlertComponent,
        AddEditShiftTimePreset,
        TinyPageHeadline,
        BaseMenu,
        TabComponent,
        AddButtonSmall,
        ErrorComponent,
        SuccessModal,
        ShiftQualificationModal,
        ConfirmDeleteModal,
        TagComponent,
        AddCraftsModal,
        ChevronDownIcon,
        CheckIcon,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        Listbox,
        PencilAltIcon,
        MenuItem,
        Menu,
        MenuButton,
        SvgCollection,
        MenuItems,
        DuplicateIcon,
        TrashIcon,
        DotsVerticalIcon,
        AppLayout
    },
    props: [
        'crafts',
        'eventTypes',
        'usersWithPermission',
        'shiftQualifications',
        'shiftTimePresets',
        'usersWithInventoryPermission',
        'shiftSettings'
    ],
    data(){
        return {
            selectedEventType: null,
            openAddCraftsModal: false,
            craftToEdit: null,
            openConfirmDeleteModal: false,
            craftToDelete: null,
            shiftQualificationToDelete: null,
            shiftTimePresetToDelete: null,
            showShiftQualificationModal: false,
            shiftQualificationModalMode: null,
            shiftQualificationModalShiftQualification: null,
            showAddShiftPresetModal: false,
            presetToEdit: null,
            dragging: false,
            confirmDeleteTitle: '',
            confirmDeleteDescription: '',
            deleteType: '',
            tabs: [
                {
                    name: this.$t('Shift Settings'),
                    href: route('shift.settings'),
                    current: route().current('shift.settings'),
                    show: true,
                    icon: 'IconCalendarUser'
                },
                {
                    name: this.$t('Day Services'),
                    href: route('day-service.index'),
                    current: route().current('day-service.index'),
                    show: true,
                    icon: 'IconHours24'
                },
                {
                    name: this.$t('Work Time Pattern'),
                    href: route('shift.work-time-pattern'),
                    current: route().current('shift.work-time-pattern'),
                    show: true,
                    icon: 'IconClockCog'
                },
                {
                    name: this.$t('User Contracts'),
                    href: route('user-contract-settings.index'),
                    current: route().current('user-contract-settings.index'),
                    show: true,
                    icon: 'IconContract'
                }
            ]
        }
    },
    computed: {
        relevantEventTypes(){
            const types = [];
            this.eventTypes.forEach((type) => {
                if(type.relevant_for_shift){
                    types.push(type)
                }
            })
            return types;
        },
        notRelevantEventTypes(){
            const types = [];
            this.eventTypes.forEach((type) => {
                if(!type.relevant_for_shift && type.id !== 1){
                    types.push(type)
                }
            })
            return types;
        }
    },
    methods: {
        openAddEditShiftPresetModal(shiftTimePreset){
            this.presetToEdit = shiftTimePreset;
            this.showAddShiftPresetModal = true;
        },
        closeShiftPresetModal(){
            this.presetToEdit = null;
            this.showAddShiftPresetModal = false;
        },
        deleteShiftTimePreset(preset){
            this.$inertia.delete(route('shift-time-preset.destroy', preset.id), {
                preserveScroll: true,
                preserveState: true
            })
        },
        openShiftQualificationModal(mode, shiftQualification = null) {
            this.shiftQualificationModalMode = mode;
            this.shiftQualificationModalShiftQualification = shiftQualification;
            this.showShiftQualificationModal = true;
        },
        closeShiftQualificationModal() {
            this.showShiftQualificationModal = false;
            this.shiftQualificationModalShiftQualification = null;
            this.shiftQualificationModalMode = null;
        },
        closeAddCraftModal(){
            this.openAddCraftsModal = false;
            this.craftToEdit = null;
        },
        addRelevantEventType(type){
            this.$inertia.patch(route('event-type.update.relevant', type), {
                relevant_for_shift: true
            });
        },
        removeRelevantEventType(type){
            this.$inertia.patch(route('event-type.update.relevant', type), {
                relevant_for_shift: false
            });

            return true;
        },
        updateCraft(craft){
            this.craftToEdit = craft;
            this.openAddCraftsModal = true;
        },
        openDeleteCraftModal(craft){
            this.craftToDelete = craft;
            this.confirmDeleteTitle = this.$t('Delete craft');
            this.confirmDeleteDescription = this.$t('Are you sure you want to delete the selected craft?');
            this.deleteType = 'craft';
            this.openConfirmDeleteModal = true;
        },
        closedDeleteCraftModal(){
            this.openConfirmDeleteModal = false;
            this.craftToDelete = null;
        },
        openDeleteQualificationModal(shiftQualificationToDelete){
            this.shiftQualificationToDelete = shiftQualificationToDelete;
            this.confirmDeleteTitle = this.$t('Delete qualification');
            this.confirmDeleteDescription = this.$t('Do you really want to delete the selected qualification?');
            this.deleteType = 'qualification';
            this.openConfirmDeleteModal = true;
        },
        closedDeleteQualificationModal(){
            this.openConfirmDeleteModal = false;
            this.deleteType = '';
            this.shiftQualificationToDelete = null;
        },
        openDeleteShiftTimePresetModal(preset){
            this.confirmDeleteTitle = this.$t('Delete time preset');
            this.confirmDeleteDescription = this.$t('Do you really want to delete the selected time preset?');
            this.deleteType = 'preset';
            this.shiftTimePresetToDelete = preset;
            this.openConfirmDeleteModal = true;
        },
        closeDeleteShiftTimePresetModal(){
            this.openConfirmDeleteModal = false;
            this.deleteType = '';
            this.shiftTimePresetToDelete = null;
        },
        submitDelete(){
            if (this.deleteType === 'craft') {
                this.$inertia.delete(route('craft.delete', this.craftToDelete.id), {
                    preserveScroll: true,
                    preserveState: true,
                    onFinish: () => {
                        this.closedDeleteCraftModal();
                    }
                });
            }
            if (this.deleteType === 'qualification'){
                this.$inertia.delete(
                    route(
                        'shift-qualifications.destroy',
                        {
                            shift_qualification: this.shiftQualificationToDelete.id
                        }
                    ),
                    {
                        preserveScroll: true,
                        onSuccess: this.closedDeleteQualificationModal
                    }
                );
            }
            if (this.deleteType === 'preset') {
                this.deleteShiftTimePreset(this.shiftTimePresetToDelete);
                this.closeDeleteShiftTimePresetModal();
            }
        },
        reorderCrafts(crafts) {
            crafts.map((craft, index) => {
                craft.position = index + 1
            })

            router.post(route('craft.reorder'), {
                crafts: crafts
            });
        },
        updateShiftSettingUseFirstNameSort(useFirstNameForSort) {
            router.patch(
                route('shift.settings.update.shift-settings.use-first-name-for-sort'),
                {
                    use_first_name_for_sort: useFirstNameForSort
                },
                {
                    preserveScroll: true
                }
            )
        }
    }
})
</script>
