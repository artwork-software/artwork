<template>
    <div id="bar" class="p-2 top-0 left-16 z-40 bg-secondaryHover flex justify-between items-center" :class="[project ? isPageScrolled ? 'fixed w-[calc(100%-4rem)] ' : 'sticky w-full' : 'fixed  w-[calc(100%-4rem)] ']">
        <div class="inline-flex items-center gap-x-3">
            <date-picker-component v-if="dateValue" :project="project" :dateValueArray="dateValue" :is_shift_plan="false"></date-picker-component>
            <div v-if="!project">
                <div v-if="dateValue && dateValue[0] === dateValue[1]" class="flex items-center">
                    <button class="ml-2 text-black previousDay" @click="previousDay">
                        <IconChevronLeft  class="h-5 w-5 text-primary"/>
                    </button>
                    <button class="ml-2 text-black nextDay" @click="nextDay">
                        <IconChevronRight class="h-5 w-5 text-primary"/>
                    </button>
                </div>
                <div v-else class="flex items-center">
                    <button  class="ml-2 text-black previousTimeRange" @click="previousTimeRange">
                        <IconChevronLeft class="h-5 w-5 text-primary"/>
                    </button>
                    <button class="ml-2 text-black nextTimeRange" @click="nextTimeRange">
                        <IconChevronRight class="h-5 w-5 text-primary"/>
                    </button>
                </div>
            </div>
            <div class="flex items-center">
                <div @click="showCalendarAboSettingModal = true" class="flex items-center gap-x-1 text-sm group cursor-pointer">
                    <IconCalendarStar class="h-5 w-5 group-hover:text-yellow-500 duration-150 transition-all ease-in-out"/>
                    {{ $t('Subscribe to calendar') }}
                </div>
            </div>

        </div>

        <div class="flex items-center gap-x-2">
            <div v-if="dateValue[0] !== dateValue[1]" class="flex items-center">
               <div class="flex items-center gap-x-2">
                   <Switch @click="changeMultiEdit(multiEdit)" v-if="!roomMode" v-model="multiEdit" :class="[multiEdit ? 'bg-artwork-buttons-hover' : 'bg-gray-200', 'relative inline-flex items-center h-6 w-14 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-none']">
                       <span class="sr-only">Use setting</span>
                       <span :class="[multiEdit ? 'translate-x-7' : 'translate-x-0', 'pointer-events-none relative inline-block h-8 w-8 border border-gray-300 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                          <span :class="[multiEdit ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                             <IconPencil stroke-width="1.5" class="w-5 h-5" />
                          </span>
                          <span :class="[multiEdit ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                              <IconPencil stroke-width="1.5" class="w-5 h-5" />
                          </span>
                    </span>
                   </Switch>
                   <Switch @click="changeAtAGlance()" v-if="!roomMode" v-model="atAGlance" :class="[atAGlance ? 'bg-artwork-buttons-hover' : 'bg-gray-200', 'relative inline-flex items-center h-6 w-14 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-none']">
                       <span class="sr-only">Use setting</span>
                       <span :class="[atAGlance ? 'translate-x-7' : 'translate-x-0', 'pointer-events-none relative inline-block h-8 w-8 border border-gray-300 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                          <span :class="[atAGlance ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                             <IconList stroke-width="1.5" class="w-5 h-5" />
                          </span>
                          <span :class="[atAGlance ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                              <IconList stroke-width="1.5" class="w-5 h-5" />
                          </span>
                    </span>
                   </Switch>
               </div>
            </div>
            <div class="flex items-center gap-x-4">
                <IconZoomIn @click="incrementZoomFactor" :disabled="zoomFactor <= 0.2" v-if="!atAGlance"
                            class="h-7 w-7 text-artwork-buttons-context cursor-pointer"></IconZoomIn>
                <IconZoomOut @click="decrementZoomFactor" :disabled="zoomFactor >= 1.4"
                             v-if="!atAGlance" class="h-7 w-7 text-artwork-buttons-context cursor-pointer"></IconZoomOut>
                <IconArrowsDiagonal  class="h-7 w-7 text-artwork-buttons-context cursor-pointer" @click="enterFullscreenMode" v-if="!atAGlance && !isFullscreen"/>
                <IndividualCalendarFilterComponent
                    class=""
                    :filter-options="filterOptions"
                    :personal-filters="personalFilters"
                    :at-a-glance="atAGlance"
                    :type="project ? 'project' : 'individual'"
                    :user_filters="user_filters"
                    :extern-updated="externUpdate"
                />


                <Menu as="div" class="relative inline-block items-center text-left">
                    <div class="flex items-center">
                        <MenuButton>
                            <span class="items-center flex">
                                <button type="button" class="text-sm flex items-center my-auto text-primary font-semibold focus:outline-none transition">
                                    <IconSettings class="h-7 w-7 text-artwork-buttons-context"/>
                                </button>
                                <span v-if="$page.props.user.calendar_settings.project_status || $page.props.user.calendar_settings.options || $page.props.user.calendar_settings.project_management || $page.props.user.calendar_settings.repeating_events || $page.props.user.calendar_settings.work_shifts"
                                      class="rounded-full border-2 border-error w-2 h-2 bg-error absolute ml-6 ring-white ring-1">
                                </span>
                            </span>
                        </MenuButton>
                    </div>
                    <transition
                        enter-active-class="transition duration-50 ease-out"
                        enter-from-class="transform scale-100 opacity-100"
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-75 ease-in"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0"
                    >
                        <MenuItems class="w-80 absolute right-0 top-12 origin-top-right rounded-sm bg-artwork-navigation-background ring-1 ring-black p-2 text-white opacity-100 z-50">
                            <div class="w-76 p-6">
                                <div class="flex py-1" v-if="!project">
                                    <input v-model="userCalendarSettings.project_status"
                                           type="checkbox"
                                           class="checkBoxOnDark"/>
                                    <div :class="userCalendarSettings.project_status ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                       class=" ml-4 my-auto text-secondary">{{ $t('Project Status')}}</div>
                                </div>
                                <div class="flex py-1">
                                    <input v-model="userCalendarSettings.options"
                                           type="checkbox"
                                           class="checkBoxOnDark"/>
                                    <p :class="userCalendarSettings.options ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                       class=" ml-4 my-auto text-secondary">{{ $t('Option prioritization')}}</p>
                                </div>
                                <div class="flex py-1" v-if="!project">
                                    <input v-model="userCalendarSettings.project_management"
                                           type="checkbox"
                                           class="checkBoxOnDark"/>
                                    <p :class="userCalendarSettings.project_management ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                       class=" ml-4 my-auto text-secondary">{{$t('Project managers')}}</p>
                                </div>
                                <div class="flex py-1">
                                    <input v-model="userCalendarSettings.repeating_events"
                                           type="checkbox"
                                           class="checkBoxOnDark"/>
                                    <p :class="userCalendarSettings.repeating_events ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                       class=" ml-4 my-auto text-secondary">{{ $t('Repeat event')}}</p>
                                </div>
                                <div class="flex py-1" v-if="this.$canAny(['can manage workers', 'can plan shifts'])">
                                    <input v-model="userCalendarSettings.work_shifts"
                                           type="checkbox"
                                           class="checkBoxOnDark"/>
                                    <p :class="userCalendarSettings.work_shifts ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                       class=" ml-4 my-auto text-secondary">{{$t('Shifts')}}</p>
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <button class="text-sm mx-3 mb-4" @click="saveUserCalendarSettings">{{ $t('Save') }}</button>
                            </div>
                        </MenuItems>
                    </transition>
                </Menu>
            </div>

            <div @click="showPDFConfigModal = true">
                <IconFileExport class="h-7 w-7 text-artwork-buttons-context cursor-pointer" />
            </div>

            <PlusButton v-if="$can('request room occupancy')" @click="openEventComponent()" />
            <AddButtonSmall
                v-if="$can('request room occupancy')"
                @click="openEventComponent()"
                :text="$t('New occupancy')"
                class="hidden"
            />
        </div>


    </div>

    <div class="w-full overflow-y-scroll" :class="activeFilters.length > 0 ? 'mt-10' : 'my-3'">
        <div class="mb-1 ml-4 max-w-7xl">
            <div class="flex">
                <BaseFilterTag v-for="activeFilter in activeFilters" :filter="activeFilter" @removeFilter="removeFilter"/>
            </div>

        </div>
    </div>

    <PdfConfigModal v-if="showPDFConfigModal" @closed="showPDFConfigModal = false" :project="project" :pdf-title="project ? project.name : 'Raumbelegung'"/>

    <GeneralCalendarAboSettingModal
        v-if="showCalendarAboSettingModal"
        @close="closeCalendarAboSettingModal"
        :event-types="filterOptions.eventTypes"
        :areas="filterOptions.areas"
        :rooms="filterOptions.rooms"
    />

    <CalendarAboInfoModal v-if="showCalendarAboInfoModal" @close="showCalendarAboInfoModal = false" />
</template>

<script>
import Button from "@/Jetstream/Button.vue";
import {PlusCircleIcon, CalendarIcon, ZoomInIcon, ZoomOutIcon} from '@heroicons/vue/outline'
import {Menu, MenuButton, MenuItems, Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";
import {ChevronDownIcon, ChevronLeftIcon, ChevronRightIcon} from "@heroicons/vue/solid";
import IndividualCalendarFilterComponent from "@/Layouts/Components/IndividualCalendarFilterComponent.vue";
import DatePickerComponent from "@/Layouts/Components/DatePickerComponent.vue";
import Dropdown from "@/Jetstream/Dropdown.vue";
import BaseFilterTag from "@/Layouts/Components/BaseFilterTag.vue";
import Permissions from "@/Mixins/Permissions.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import BaseFilter from "@/Layouts/Components/BaseFilter.vue";
import {router} from "@inertiajs/vue3";
import PdfConfigModal from "@/Layouts/Components/PdfConfigModal.vue";
import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";
import IconLib from "@/Mixins/IconLib.vue";
import PlusButton from "@/Layouts/Components/General/Buttons/PlusButton.vue";
import GeneralCalendarAboSettingModal from "@/Pages/Events/Components/GeneralCalendarAboSettingModal.vue";
import CalendarAboInfoModal from "@/Pages/Shifts/Components/CalendarAboInfoModal.vue";


export default {
    name: "CalendarFunctionBar",
    mixins: [Permissions, IconLib],
    components: {
        CalendarAboInfoModal,
        GeneralCalendarAboSettingModal,
        PlusButton,
        AddButtonSmall,
        PdfConfigModal,
        BaseFilter,
        BaseFilterTag,
        Dropdown,
        Menu,
        MenuItems,
        MenuButton,
        Button,
        PlusCircleIcon,
        CalendarIcon,
        ChevronDownIcon,
        IndividualCalendarFilterComponent,
        ChevronLeftIcon,
        ChevronRightIcon,
        SwitchGroup,
        SwitchLabel,
        Switch,
        DatePickerComponent,
        ZoomInIcon,
        ZoomOutIcon,
    },
    props: [
        'atAGlance',
        'dateValue',
        'isFullscreen',
        'zoomFactor',
        'project',
        'roomMode',
        'filterOptions',
        'personalFilters',
        'user_filters'
    ],
    emits: ['changeAtAGlance', 'changeMultiEdit', 'enterFullscreenMode', 'incrementZoomFactor', 'decrementZoomFactor','nextDay','previousDay','openEventComponent','previousTimeRange','nextTimeRange'],
    data() {
        return {
            atAGlance: this.atAGlance,
            calendarSettingsOpen: false,
            multiEdit: false,
            activeFilters: [],
            userCalendarSettings: useForm({
                project_status: this.$page.props.user.calendar_settings ? this.$page.props.user.calendar_settings.project_status : false,
                options: this.$page.props.user.calendar_settings ? this.$page.props.user.calendar_settings.options : false,
                project_management: this.$page.props.user.calendar_settings ? this.$page.props.user.calendar_settings.project_management : false,
                repeating_events: this.$page.props.user.calendar_settings ? this.$page.props.user.calendar_settings.repeating_events : false,
                work_shifts: this.$page.props.user.calendar_settings ? this.$page.props.user.calendar_settings.work_shifts : false
            }),
            externUpdate: false,
            showPDFConfigModal: false,
            isPageScrolled: false,
            showCalendarAboSettingModal: false,
            showCalendarAboInfoModal: false,
        }
    },
    methods: {
        usePage,
        closeCalendarAboSettingModal(bool){
            this.showCalendarAboSettingModal = false;
            if(bool){
                this.showCalendarAboInfoModal = true;
            }
        },
        changeAtAGlance() {
            this.$emit('changeAtAGlance')
        },
        changeMultiEdit(multiEdit) {
            this.$emit('changeMultiEdit', !multiEdit)
        },
        enterFullscreenMode() {
            this.$emit('enterFullscreenMode')
        },

        incrementZoomFactor() {
            this.$emit('incrementZoomFactor')
        },
        decrementZoomFactor() {
            this.$emit('decrementZoomFactor')
        },
        nextDay(){
            this.$emit('nextDay')
        },
        previousDay(){
            this.$emit('previousDay')
        },
        openEventComponent(){
            this.$emit('openEventComponent')
        },
        previousTimeRange(){
            this.$emit('previousTimeRange')
        },
        nextTimeRange(){
            this.$emit('nextTimeRange')
        },
        filtersChanged(activeFilters) {
            this.activeFilters = activeFilters
        },
        saveUserCalendarSettings() {
            this.userCalendarSettings.patch(route('user.calendar_settings.update', {user: this.$page.props.user.id}))
        },
        removeFilter(filter) {

            if(filter.value === 'isLoud'){
                this.updateFilterValue('is_loud', false);
            }

            if(filter.value === 'isNotLoud'){
                this.updateFilterValue('is_not_loud', false)
            }

            if(filter.value === 'adjoiningNoAudience'){
                this.updateFilterValue('adjoining_no_audience', false)
            }

            if(filter.value === 'adjoiningNotLoud'){
                this.updateFilterValue('adjoining_not_loud', false)
            }

            if(filter.value === 'hasAudience'){
                this.updateFilterValue('has_audience', false)
            }

            if(filter.value === 'hasNoAudience'){
                this.updateFilterValue('has_no_audience', false)
            }

            if(filter.value === 'showAdjoiningRooms'){
                this.updateFilterValue('show_adjoining_rooms', false)
            }

            if(filter.value === 'rooms'){
                this.user_filters.rooms.splice(this.user_filters.rooms.indexOf(filter.id), 1);
                this.updateFilterValue('rooms', this.user_filters.rooms.length > 0 ? this.user_filters.rooms : null)
            }

            if(filter.value === 'room_categories'){
                this.user_filters.room_categories.splice(this.user_filters.room_categories.indexOf(filter.id), 1);
                this.updateFilterValue('room_categories', this.user_filters.room_categories.length > 0 ? this.user_filters.room_categories : null)
            }

            if(filter.value === 'areas'){
                this.user_filters.areas.splice(this.user_filters.areas.indexOf(filter.id), 1);
                this.updateFilterValue('areas', this.user_filters.areas.length > 0 ? this.user_filters.areas : null)
            }

            if(filter.value === 'event_types'){
                this.user_filters.event_types.splice(this.user_filters.event_types.indexOf(filter.id), 1);
                this.updateFilterValue('event_types', this.user_filters.event_types.length > 0 ? this.user_filters.event_types : null)
            }

            if(filter.value === 'room_attributes'){
                this.user_filters.room_attributes.splice(this.user_filters.room_attributes.indexOf(filter.id), 1);
                this.updateFilterValue('room_attributes', this.user_filters.room_attributes.length > 0 ? this.user_filters.room_attributes : null)
            }
        },

        updateFilterValue(key, value){
            router.patch(route('user.calendar.filter.single.value.update', {user: this.$page.props.user.id}), {
                key: key,
                value: value
            }, {
                preserveScroll: true,
            });
        },

        arrayToIds(array) {
            const filteredArray = array.filter(item => item.checked === true)

            if (filteredArray.length === 0) {
                return null
            }

            return filteredArray.map(elem => elem.id)
        },
        handleScroll() {
            this.isPageScrolled = window.scrollY > 358;
        },
    },
    mounted(){
        this.handleScroll();
        window.addEventListener('scroll', this.handleScroll);
    },
    beforeDestroy() {
        window.removeEventListener('scroll', this.handleScroll);
    },
    computed: {
        activeFilters: function () {
            let activeFiltersArray = []
            this.filterOptions.rooms.forEach((room) => {
                if(this.user_filters.rooms?.includes(room.id)){
                    activeFiltersArray.push(room)
                }
            })

            this.filterOptions.areas.forEach((area) => {
                if(this.user_filters.areas?.includes(area.id)){
                    activeFiltersArray.push(area)
                }
            })

            this.filterOptions.eventTypes.forEach((eventType) => {
                if(this.user_filters.event_types?.includes(eventType.id)){
                    activeFiltersArray.push(eventType)
                }
            })

            this.filterOptions.roomCategories.forEach((category) => {
                if(this.user_filters.room_categories?.includes(category.id)){
                    activeFiltersArray.push(category)
                }
            })

            this.filterOptions.roomAttributes.forEach((attribute) => {
                if(this.user_filters.room_attributes?.includes(attribute.id)){
                    activeFiltersArray.push(attribute)
                }
            })

            if(this.user_filters.is_loud){
                activeFiltersArray.push({name: "Laute Termine", value: 'isLoud', user_filter_key: 'is_loud'})
            }

            if(this.user_filters.is_not_loud){
                activeFiltersArray.push({name: "Ohne laute Termine", value: 'isNotLoud', user_filter_key: 'is_not_loud'})
            }

            if(this.user_filters.adjoining_no_audience){
                activeFiltersArray.push({name: "Ohne Nebenveranstaltung mit Publikum", value: 'adjoiningNoAudience', user_filter_key: 'adjoining_no_audience'})
            }

            if(this.user_filters.adjoining_not_loud){
                activeFiltersArray.push({name: "Ohne laute Nebenveranstaltung", value: 'adjoiningNotLoud', user_filter_key: 'adjoining_not_loud'})
            }

            if(this.user_filters.has_audience){
                activeFiltersArray.push({name: "Mit Publikum", value: 'hasAudience', user_filter_key: 'has_audience'})
            }

            if(this.user_filters.has_no_audience){
                activeFiltersArray.push({name: "Ohne Publikum", value: 'hasNoAudience', user_filter_key: 'has_no_audience'})
            }

            if(this.user_filters.show_adjoining_rooms){
                activeFiltersArray.push({name: "Nebenräume anzeigen", value: 'showAdjoiningRooms', user_filter_key: 'show_adjoining_rooms'})
            }

            return activeFiltersArray
        }
    }
}
</script>

<style scoped>
</style>
