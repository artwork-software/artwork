<template>
    <AppLayout :title="$t('Shift Settings')">
        <div class="max-w-screen-lg ml-14 mr-40">
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
            <div class="flex items-center justify-between gap-x-3">
                <TinyPageHeadline
                    :title="$t('Crafts')"
                    :description="$t('Define crafts to which you can later assign employees and shifts. Additionally, you can specify which users are allowed to assign what type of employee shifts.')"
                />

               <div class="w-72">
                   <AddButtonSmall :text="$t('New Craft')" class="mt-5" @click="openAddCraftsModal = true" />
               </div>

            </div>
            <ul role="list" class="divide-y divide-gray-100">
                <li v-for="craft in crafts" :key="craft" class="flex justify-between gap-x-6 py-5">
                    <div class="flex gap-x-4">
                        <div class="min-w-0 flex-auto">
                            <p class="text-sm font-semibold leading-6 text-gray-900 flex items-center gap-x-2">
                                <span class="h-5 w-5 block rounded-full border" :style="{backgroundColor: backgroundColorWithOpacity(craft.color), borderColor: TextColorWithDarken(craft.color, 90)}"/>
                                {{ craft.name }} ({{ craft.abbreviation }})
                            </p>
                            <div class="" v-if="craft.assignable_by_all">
                                <p class="mt-1 truncate xsLight">{{$t('Assignable by all schedulers')}}</p>
                            </div>
                            <div v-else>
                                <p class="mt-1 truncate xsLight">
                                    {{$t('Can only be assigned by:')}}
                                    <span class="" v-for="(user, index) in craft.users">
                                        {{ user.full_name }}<span>, </span>
                                    </span>
                                </p>
                            </div>
                            <div class="mt-1 truncate xsLight">
                                <div v-if="craft.notify_days > 0">
                                    {{ $t('Notification of shifts with open demand is sent {0} day(s) before the start of the shift', [craft.notify_days]) }}
                                </div>
                                <div v-else>
                                    {{ $t('Notification of shifts that are not fully staffed takes place on the same day as the shift starts') }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="hidden sm:flex sm:flex-col sm:items-end">
                        <BaseMenu class="mt-3">
                            <MenuItem @click="updateCraft(craft)"
                                      v-slot="{ active }">
                                <a :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                    <IconEdit stroke-width="1.5"
                                              class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                              aria-hidden="true"/>
                                    {{$t('Edit')}}
                                </a>
                            </MenuItem>
                            <MenuItem @click="openDeleteCraftModal(craft)"
                                      v-slot="{ active }">
                                <a :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                    <IconTrash stroke-width="1.5"
                                               class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                               aria-hidden="true"/>
                                    {{$t('Delete')}}
                                </a>
                            </MenuItem>
                        </BaseMenu>
                    </div>
                </li>
            </ul>
            <div class="mt-10">
                <TinyPageHeadline
                    :title="$t('Shift-relevant Event Types')"
                    :description="$t('Determine which types of events are displayed as shift-relevant by default. These will then automatically appear in the \'shifts\' tab of the project. You can also define additional events as shift-relevant for each project.')"
                />
                <div class="mt-3">
                    <Listbox as="div">
                        <div class="relative mt-2 w-1/2">
                            <ListboxButton class="w-full h-10 border-gray-300 inputMain xsDark placeholder-secondary disabled:border-none flex-grow">
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
            <div>
                <div class="flex items-center justify-between">
                    <TinyPageHeadline class="mt-10"
                        :title="$t('Qualifications')"
                        :description="$t('Create or edit qualifications')"
                    />
                    <AddButtonSmall text="Neue Qualifikation" class="mt-5" @click="this.openShiftQualificationModal('create')" />
                </div>
                <div class="mt-5">
                    <div class="mb-5 xsLight" v-if="shiftQualifications.length === 0">
                        {{$t('No qualifications have been created yet.')}}
                    </div>
                    <ul v-else role="list" class="w-full">
                        <li v-for="(shiftQualification) in shiftQualifications"
                            :key="shiftQualification.id"
                            @click="openShiftQualificationModal('edit', shiftQualification)"
                            class="cursor-pointer py-4 pr-4 flex justify-between items-center border-b-2"
                        >
                            <span class="sDark cursor-pointer">
                                {{ shiftQualification.name }}
                                <span v-if="shiftQualification.available"
                                      class="xxsLight">
                                    {{$t('(Considered for new shifts)')}}
                                </span>
                            </span>
                            <IconEdit stroke-width="1.5" class="h-5 w-5" aria-hidden="true"/>
                        </li>
                    </ul>
                </div>
            </div>
            <div>
                <div class="flex items-center justify-between">
                    <TinyPageHeadline
                        class="mt-14"
                        :title="$t('Time presets for shifts')"
                        :description="$t('Create time presets for layers to be able to assign them quickly and easily later.')"
                    />
                    <AddButtonSmall :text="$t('New time preset')" class="mt-5" @click="showAddShiftPresetModal = true" />
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

                                <IconTrash stroke-width="1.5" class="h-5 w-5 text-red-500 cursor-pointer" aria-hidden="true" @click="deleteShiftTimePreset(shiftTimePreset)"/>
                            </div>
                        </li>
                    </ul>
                </div>
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
        <AddCraftsModal @closed="closeAddCraftModal" v-if="openAddCraftsModal" :craft-to-edit="craftToEdit" :users-with-permission="usersWithPermission" />
        <ConfirmDeleteModal :title="$t('Delete craft')" :description="$t('Are you sure you want to delete the selected craft?')" @closed="closedDeleteCraftModal" @delete="submitDelete" v-if="openConfirmDeleteModal" />
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
    MenuItems
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

export default defineComponent({
    name: "ShiftSettings",
    mixins: [IconLib, ColorHelper],
    components: {
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
    props: ['crafts', 'eventTypes', 'usersWithPermission', 'shiftQualifications', 'shiftTimePresets'],
    data(){
        return {
            selectedEventType: null,
            openAddCraftsModal: false,
            craftToEdit: null,
            openConfirmDeleteModal: false,
            craftToDelete: null,
            showShiftQualificationModal: false,
            shiftQualificationModalMode: null,
            shiftQualificationModalShiftQualification: null,
            showAddShiftPresetModal: false,
            presetToEdit: null,
            tabs: [
                {
                    name: this.$t('Shift Settings'),
                    href: route('shift.settings'),
                    current: route().current('shift.settings'),
                    show: true
                },
                {
                    name: this.$t('Day Services'),
                    href: route('day-service.index'),
                    current: route().current('day-service.index'),
                    show: true
                },
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
            this.openConfirmDeleteModal = true;
        },
        closedDeleteCraftModal(){
            this.openConfirmDeleteModal = false;
            this.craftToDelete = null;
        },
        submitDelete(){
            this.$inertia.delete(route('craft.delete', this.craftToDelete.id), {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => {
                    this.closedDeleteCraftModal();
                }
            })
        }
    }
})
</script>


<style scoped>

</style>
