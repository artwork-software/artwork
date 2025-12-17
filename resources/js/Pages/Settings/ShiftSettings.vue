<template>
    <ShiftSettingsHeader :title="$t('Shift Settings')">
        <div class="my-10">
                <div class="card white p-5">

                    <div class="flex items-center justify-between">
                        <span class="flex grow flex-col">
                            <label class="font-lexend font-bold mb-1 text-gray-900" id="availability-label">
                                {{ $t('Duty roster release workflow') }}
                            </label>
                            <span class="text-sm text-gray-500 w-1/2" id="availability-description">
                                {{ $t('Activates a two-stage approval process for schedules.If activated, authorized persons can send time periods (e.g. entire calendar weeks) to selected users for approval. They receive a notification and can approve or reject the schedule. If deactivated, authorized persons can approve schedules directly, without an additional approval process.') }}
                            </span>
                        </span>
                        <SwitchIconTooltip
                            v-model="shiftCommitWorkflow"
                            :tooltip-text="$t('Duty roster release workflow')"
                            size="md"
                            @change="changeShiftCommitWorkflow"
                            icon="IconCheck"
                        />
                    </div>



                    <div v-if="shiftCommitWorkflow">
                        <div class="mt-5 w-1/2">
                            <UserSearch
                                :label="$t('Select users who can confirm the shift commit requests')"
                                @user-selected="addUserToWorkflow"
                            />
                        </div>


                        <div>
                            <div v-if="shiftCommitWorkflowUsers?.length > 0" class="flex flex-wrap items-center gap-4 mt-3">
                                <div v-for="(object, index) in shiftCommitWorkflowUsers" class="group block shrink-0 bg-white w-fit pr-3 rounded-full border border-gray-100">
                                    <div class="flex items-center">
                                        <div>
                                            <img class="inline-block size-9 rounded-full object-cover" :src="object.user.profile_photo_url" alt="" />
                                        </div>
                                        <div class="mx-2">
                                            <p class="xsDark group-hover:text-gray-900">{{ object.user.full_name }}</p>
                                        </div>
                                        <div class="flex items-center">
                                            <button type="button" @click="removeUserFormShiftWorkFlow(object.id)">
                                                <PropertyIcon name="IconX" class="h-4 w-4 text-gray-400 hover:text-error" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>


            <div class="card white p-5">
                <div class="flex items-center justify-between gap-x-3">
                    <div class="w-1/2">
                        <BasePageTitle
                            :title="$t('Crafts')"
                            :description="$t('Define crafts to which you can later assign employees and shifts. Additionally, you can specify which users are allowed to assign what type of employee shifts.')"
                        />
                    </div>
                    <div class="flex items-center justify-end">
                        <BaseUIButton @click="openAddCraftsModal = true" label="New Craft" use-translation is-add-button />
                    </div>
                </div>
                <draggable
                    ghost-class="opacity-50"
                    key="draggableKey"
                    item-key="id"
                    :list="crafts"
                    @start="dragging = true"
                    @end="dragging = false"
                    @change="reorderCrafts(crafts)"
                    class="space-y-3 mt-5"
                >
                    <template #item="{ element }">
                        <div
                            class="group relative w-full rounded-2xl border border-zinc-200 bg-white transition hover:border-zinc-300"
                            :class="dragging ? 'cursor-grabbing' : 'cursor-grab'"
                        >

                            <!-- Inhalt -->
                            <div class="pl-4 pr-3 sm:pl-6 sm:pr-4 py-4">
                                <div class="flex items-start gap-4">
                                    <!-- Drag-Handle -->
                                    <div class="mt-1 shrink-0 opacity-60 group-hover:opacity-100 transition">
                                        <PropertyIcon name="IconGripVertical" class="size-5" aria-hidden="true" />
                                    </div>

                                    <!-- Header: Name + Kürzel + Badges -->
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center flex-wrap gap-2">
                                            <div class="flex items-center gap-2">
                                              <span
                                                  class="inline-block size-5 rounded-full ring-2"
                                                  :style="{
                                                  backgroundColor: element.color + '33',
                                                  boxShadow: `inset 0 0 0 1px ${element.color}`
                                                }"
                                                  aria-hidden="true"
                                              />
                                                                            <h3 class="text-sm font-semibold text-zinc-900 leading-6">
                                                                                {{ element.name }}
                                                                                <span class="text-zinc-500">({{ element.abbreviation }})</span>
                                                                            </h3>
                                                                        </div>

                                                                        <span
                                                                            v-if="element.universally_applicable"
                                                                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs ring-1 ring-inset
                                                     ring-zinc-200 bg-zinc-50 text-zinc-700"
                                                                        >
                                              <PropertyIcon name="IconShieldCheck" class="size-3.5" />
                                              {{ $t('Universally applicable') }}
                                            </span>

                                                                        <span
                                                                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs ring-1 ring-inset
                                                     ring-zinc-200 bg-zinc-50 text-zinc-700"
                                                                        >
                                              <PropertyIcon name="IconUsersGroup" class="size-3.5" />
                                              <span v-if="element.assignable_by_all">
                                                {{ $t('Assignable by all schedulers') }}
                                              </span>
                                              <span v-else>
                                                {{ $t('Restricted assignment') }}
                                              </span>
                                            </span>
                                        </div>

                                        <!-- Subtext-Zeilen -->
                                        <div class="mt-2 space-y-1.5 text-xs text-zinc-600">
                                            <p v-if="element.assignable_by_all" class="leading-5">
                                                {{ $t('Assignable by all schedulers') }}
                                            </p>
                                            <p v-else class="leading-5">
                                                {{ $t('Can only be assigned by:') }}
                                                <span class="text-zinc-800">
                                            {{ (element.craft_shift_planer || []).map(u => u.full_name).join(', ') || '—' }}
                                          </span>
                                            </p>

                                            <p v-if="element.inventory_planned_by_all" class="leading-5">
                                                {{ $t('Inventory can be planned by all planners') }}
                                            </p>
                                            <p v-else class="leading-5">
                                                {{ $t('Inventory can only be planned by:') }}
                                                <span class="text-zinc-800">
                                                {{ (element.craft_inventory_planer || []).map(u => u.full_name).join(', ') || '—' }}
                                              </span>
                                            </p>

                                            <p class="leading-5 flex items-center gap-1.5">
                                                <PropertyIcon name="IconBell" class="size-3.5 shrink-0" />
                                                <span v-if="element.notify_days > 0">
                                                    {{ $t('Notification of shifts with open demand is sent {0} day(s) before the start of the shift', [element.notify_days]) }}
                                                </span>
                                                <span v-else>
                                                    {{ $t('Notification of shifts that are not fully staffed takes place on the same day as the shift starts') }}
                                                </span>
                                            </p>
                                        </div>

                                        <!-- Qualifications -->
                                        <div v-if="(element.qualifications || []).length" class="mt-3 flex flex-wrap gap-2">
                                            <span
                                                v-for="q in element.qualifications"
                                                :key="q.id"
                                                class="inline-flex items-center gap-1 rounded-full border border-zinc-200 bg-white px-2 py-1 text-xs text-zinc-700"
                                            >
                                              <PropertyIcon :name="q.icon" class="size-3.5" />
                                              {{ q.name }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="mt-1 flex items-start gap-2">
                                        <BaseMenu white-menu-background has-no-offset>
                                            <BaseMenuItem
                                                white-menu-background
                                                @click="updateCraft(element)"
                                                :title="$t('Edit')"
                                                icon="IconEdit"
                                            />
                                            <BaseMenuItem
                                                white-menu-background
                                                @click="openDeleteCraftModal(element)"
                                                :title="$t('Delete')"
                                                icon="IconTrash"
                                            />
                                        </BaseMenu>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </draggable>
            </div>
            <!--<div class="mt-10 card white p-5">
                <BasePageTitle
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
            </div>-->

            <GlobalQualificationsSettingsCard :global-qualifications="globalQualifications" />

        <section class="mt-10">
            <!-- Card -->
            <div class="rounded-2xl border border-zinc-200 bg-white/95 shadow-sm backdrop-blur">
                <!-- Header -->
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between px-5 py-4">
                    <BasePageTitle
                        :title="$t('Craft functions')"
                        :description="$t('Create or edit craft functions.')"
                    />
                    <div class="flex items-center gap-2">
                        <BaseUIButton
                            @click="openShiftQualificationModal('create')"
                            label="Neue Qualifikation"
                            use-translation
                            is-add-button
                        />
                    </div>
                </div>

                <!-- Content -->
                <div class="px-5 py-4">
                    <!-- Empty state -->
                    <div v-if="shiftQualifications.length === 0" class="flex items-center justify-between rounded-xl border border-dashed border-zinc-300 bg-zinc-50 px-5 py-8">
                        <div class="flex items-start gap-3">
                            <div class="rounded-xl bg-white p-3 shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-width="1.5" d="M12 6v6l4 2" />
                                    <circle cx="12" cy="12" r="9" stroke-width="1.5" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-zinc-900">
                                    {{ $t('No qualifications have been created yet.') }}
                                </p>
                                <p class="mt-1 text-xs text-zinc-600">
                                    {{ $t('Create your first qualification to use it in shifts and staffing rules.') }}
                                </p>
                            </div>
                        </div>
                        <BaseUIButton
                            @click="openShiftQualificationModal('create')"
                            size="sm"
                            label="Neue Qualifikation"
                            use-translation
                            is-add-button
                        />
                    </div>

                    <!-- List -->
                    <ul v-else role="list" class="space-y-2">
                        <transition-group
                            name="list-fade"
                            tag="div"
                            class="space-y-2 divide-y divide-zinc-200 divide-dashed">
                            <li v-for="shiftQualification in shiftQualifications"
                                :key="shiftQualification.id"
                                class="group bg-white px-4 py-3 transition">
                                <div class="flex items-center justify-between gap-4 pb-2">
                                    <!-- Left: Icon + name + meta -->
                                    <div class="min-w-0 flex items-center gap-3">
                                        <div class="mt-0.5 rounded-lg bg-zinc-50 p-2 ring-1 ring-inset ring-zinc-200">
                                            <PropertyIcon
                                                stroke-width="1.5"
                                                class="text-zinc-900 size-7"
                                                :name="shiftQualification.icon"
                                            />
                                        </div>
                                        <div class="min-w-0">
                                            <div class="">
                                                <h3 class="truncate text-sm font-medium text-zinc-900">
                                                    {{ shiftQualification.name }}
                                                </h3>

                                                <!-- Availability badge -->
                                                <span v-if="shiftQualification.available" class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                                    {{ $t('Considered for new shifts') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right: actions -->
                                    <div class="shrink-0">
                                        <BaseMenu white-menu-background has-no-offset>
                                            <BaseMenuItem
                                                white-menu-background
                                                @click="openShiftQualificationModal('edit', shiftQualification)"
                                                title="Edit"
                                                icon="IconEdit"
                                            />
                                            <BaseMenuItem
                                                v-if="shiftQualification.id > 1"
                                                white-menu-background
                                                @click="openDeleteQualificationModal(shiftQualification)"
                                                title="Delete"
                                                icon="IconTrash"
                                            />
                                        </BaseMenu>
                                    </div>
                                </div>
                            </li>
                        </transition-group>
                    </ul>
                </div>
            </div>
        </section>
            <div class="card white p-5 mt-10">
                <div class="flex items-center justify-between">
                    <BasePageTitle
                        class=""
                        :title="$t('Time presets for shifts')"
                        :description="$t('Create time presets for layers to be able to assign them quickly and easily later.')"
                    />

                    <BaseUIButton @click="showAddShiftPresetModal = true" label="New time preset" use-translation is-add-button />
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
                        <li v-for="(shiftTimePreset) in shiftTimePresets" :key="shiftTimePreset.id" class="py-4 pr-4 flex justify-between items-center border-b border-zinc-200">
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
                                <PropertyIcon name="IconEdit" stroke-width="1.5" class="h-5 w-5 cursor-pointer" aria-hidden="true" @click="openAddEditShiftPresetModal(shiftTimePreset)"/>

                                <PropertyIcon name="IconTrash" stroke-width="1.5" class="h-5 w-5 text-red-500 cursor-pointer" aria-hidden="true" @click="openDeleteShiftTimePresetModal(shiftTimePreset)"/>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="flex flex-col my-10 gap-2 card white p-5">
                <BasePageTitle :title="$t('Sort settings')"
                                  :description="$t('Configure the behaviour of shift plans sort opportunity.')"/>
                <SwitchGroup as="div" class="flex flex-row items-center gap-x-2 cursor-pointer mt-4">
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
        <AddCraftsModal @closed="closeAddCraftModal" v-if="openAddCraftsModal" :craft-to-edit="craftToEdit" :users-with-permission="usersWithPermission" :users-with-inventory-permission="usersWithInventoryPermission" :prop-qualifications="shiftQualifications" />
        <ConfirmDeleteModal :title="confirmDeleteTitle" :description="confirmDeleteDescription" @closed="closedDeleteCraftModal" @delete="submitDelete" v-if="openConfirmDeleteModal" />
    </ShiftSettingsHeader>
</template>
<script>
import {defineComponent} from 'vue'
import ShiftSettingsHeader from "@/Pages/Settings/Components/ShiftSettingsHeader.vue";
import {CheckIcon, DotsVerticalIcon} from "@heroicons/vue/solid";
import {ChevronDownIcon, DuplicateIcon, PencilAltIcon, TrashIcon, XIcon} from "@heroicons/vue/outline";
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
import {router, useForm, usePage} from "@inertiajs/vue3";
import ShiftQualificationIconCollection from "@/Layouts/Components/ShiftQualificationIconCollection.vue";
import GlassyIconButton from "@/Artwork/Buttons/GlassyIconButton.vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import Button from "@/Jetstream/Button.vue";
import {IconCheck, IconEdit, IconGripVertical, IconPlus, IconTrash} from "@tabler/icons-vue";
import BaseTabs from "@/Artwork/Tabs/BaseTabs.vue";
import ShiftTabs from "@/Pages/Shifts/Components/ShiftTabs.vue";
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import SwitchIconTooltip from "@/Artwork/Toggles/SwitchIconTooltip.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import GlobalQualificationsSettingsCard from "@/Pages/Settings/ShiftSettingsComponents/GlobalQualificationsSettingsCard.vue";

export default defineComponent({
    name: "ShiftSettings",
    mixins: [IconLib, ColorHelper],
    components: {
        GlobalQualificationsSettingsCard,
        PropertyIcon,
        SwitchIconTooltip,
        BaseMenuItem,
        BaseUIButton,
        BaseButton,
        BasePageTitle,
        ShiftSettingsHeader,
        ShiftTabs,
        BaseTabs,
        Button, XIcon,
        UserSearch,
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
        DotsVerticalIcon
    },
    props: [
        'crafts',
        'eventTypes',
        'usersWithPermission',
        'shiftQualifications',
        'shiftTimePresets',
        'usersWithInventoryPermission',
        'shiftSettings',
        'shiftCommitWorkflowUsers',
        'globalQualifications'
    ],
    data(){
        return {
            shiftCommitWorkflow: usePage().props.shiftCommitWorkflow,
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
            userForWorkflowForm: useForm({
                // users form this.shiftCommitWorkflowUsers but only id is needed
                users: this.shiftCommitWorkflowUsers.map(user => user.id) || []
            }),
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
                },
                {
                    name: this.$t('Shift warnings - rules'),
                    href: route('shift-rules.index'),
                    current: route().current('shift-rules.index'),
                    show: true,
                    icon: 'IconGavel'
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
        IconCheck,
        IconEdit,
        IconTrash,
        IconPlus,
        IconGripVertical,
        addUserToWorkflow(user) {
            const userAlreadyExistsOnServer =
                this.shiftCommitWorkflowUsers.some(wu => wu.user?.id === user.id);

            const userAlreadyQueuedLocally =
                this.userForWorkflowForm.users.includes(user.id);

            if (this.userForWorkflowForm.processing || userAlreadyExistsOnServer || userAlreadyQueuedLocally) {
                console.warn('User already exists / queued or form is processing.');
                return;
            }

            // WICHTIG: Payload immer nur "dieser eine User"
            this.userForWorkflowForm.users = [user.id];

            this.userForWorkflowForm.patch(route('shift.settings.update.shift-commit-workflow-users'), {
                preserveScroll: true,
                preserveState: false,
                onSuccess: () => {
                    // Array leeren, damit der nächste Klick keinen "Altbestand" mitsendet
                    this.userForWorkflowForm.users = [];
                },
                onError: () => {
                    this.userForWorkflowForm.users = [];
                },
                onFinish: () => {
                    // zur Sicherheit auch hier leeren (falls onSuccess/onError nicht greift)
                    this.userForWorkflowForm.users = [];
                },
            });
        },

        removeUserFormShiftWorkFlow(objectId){
            this.$inertia.delete(route('shift.settings.remove.shift-commit-workflow-user', objectId), {
                preserveScroll: true,
                preserveState: false,
                onSuccess: () => {
                }
            });
        },
        changeShiftCommitWorkflow(){
            console.log(this.shiftCommitWorkflow)
            this.$inertia.patch(route('shift.settings.update.shift-commit-workflow'), {
                shift_commit_workflow: this.shiftCommitWorkflow
            }, {
                preserveScroll: true,
                preserveState: false
            });
        },
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
