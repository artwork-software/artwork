<template>
    <div class="w-[98%] flex justify-between items-center mt-4 mb-2" :class="atAGlance || dateValue[0] === dateValue[1]? 'ml-14' : ''">
        <div class="inline-flex items-center">
            <date-picker-component v-if="dateValue" :project="project" :dateValueArray="dateValue" :is_shift_plan="false"></date-picker-component>
            <div v-if="!project">
                <div v-if="dateValue && dateValue[0] === dateValue[1]">
                    <button  class="ml-2 -mt-2 text-black previousDay" @click="previousDay">

                        <ChevronLeftIcon class="h-5 w-5 text-primary"/>
                    </button>
                    <button class="ml-2 -mt-2 text-black nextDay" @click="nextDay">
                        <ChevronRightIcon class="h-5 w-5 text-primary"/>
                    </button>
                </div>
                <div v-else>
                    <button  class="ml-2 -mt-2 text-black previousTimeRange" @click="previousTimeRange">
                        <ChevronLeftIcon class="h-5 w-5 text-primary"/>
                    </button>
                    <button class="ml-2 -mt-2 text-black nextTimeRange" @click="nextTimeRange">
                        <ChevronRightIcon class="h-5 w-5 text-primary"/>
                    </button>
                </div>
            </div>
            <div v-if="dateValue[0] !== dateValue[1]" class="flex items-center">
              <SwitchGroup v-if="!roomMode" as="div" class="flex items-center ml-2">
                <Switch v-model="atAGlance" @click="changeAtAGlance()"
                        class="group relative inline-flex h-5 w-10 flex-shrink-0 cursor-pointer items-center justify-center rounded-full focus:outline-none">
                    <span class="sr-only">Use setting</span>
                    <span aria-hidden="true" :class="this.project ? 'bg-lightBackgroundGray' : 'bg-white'" class="pointer-events-none absolute h-full w-full rounded-md"/>
                    <span aria-hidden="true"
                          :class="[atAGlance ? 'bg-indigo-600' : 'bg-gray-200', 'pointer-events-none absolute mx-auto h-4 w-9 rounded-full transition-colors duration-200 ease-in-out']"/>
                    <span aria-hidden="true"
                          :class="[atAGlance ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none absolute left-0 inline-block h-5 w-5 transform rounded-full border border-gray-200 bg-white shadow ring-0 transition-transform duration-200 ease-in-out']"/>
                </Switch>
                <SwitchLabel as="span" class="ml-3 text-sm">
                    <span class="font-medium text-gray-900">{{ $t('At a glance')}}</span>
                </SwitchLabel>
            </SwitchGroup>
                <SwitchGroup v-if="!roomMode"  as="div" class="flex items-center ml-3">
                    <Switch v-model="multiEdit" @click="changeMultiEdit(multiEdit)"
                            class="group relative inline-flex h-5 w-10 flex-shrink-0 cursor-pointer items-center justify-center rounded-full focus:outline-none">
                        <span class="sr-only">Use setting</span>
                        <span aria-hidden="true" :class="this.project ? 'bg-lightBackgroundGray' : 'bg-white'" class="pointer-events-none absolute h-full w-full rounded-md"/>
                        <span aria-hidden="true"
                              :class="[multiEdit ? 'bg-indigo-600' : 'bg-gray-200', 'pointer-events-none absolute mx-auto h-4 w-9 rounded-full transition-colors duration-200 ease-in-out']"/>
                        <span aria-hidden="true"
                              :class="[multiEdit ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none absolute left-0 inline-block h-5 w-5 transform rounded-full border border-gray-200 bg-white shadow ring-0 transition-transform duration-200 ease-in-out']"/>
                    </Switch>
                    <SwitchLabel as="span" class="ml-3 text-sm">
                        <span class="font-medium text-gray-900">{{ $t('Multiedit')}}</span>
                    </SwitchLabel>
                </SwitchGroup>
            </div>

        </div>

        <div class="flex items-center">
            <div class="flex items-center">
                <ZoomInIcon @click="incrementZoomFactor" :disabled="zoomFactor <= 0.2" v-if="!atAGlance && isFullscreen"
                            class="h-7 w-7 mx-2 cursor-pointer"></ZoomInIcon>
                <ZoomOutIcon @click="decrementZoomFactor" :disabled="zoomFactor >= 1.4"
                             v-if="!atAGlance && isFullscreen" class="h-7 w-7 mx-2 cursor-pointer"></ZoomOutIcon>
                <img alt="Fullscreen" v-if="!atAGlance && !isFullscreen" @click="enterFullscreenMode"
                     src="/Svgs/IconSvgs/icon_zoom_out.svg" class="h-6 w-6 mx-2 cursor-pointer"/>
                <IndividualCalendarFilterComponent
                    class="mt-1"
                    :filter-options="filterOptions"
                    :personal-filters="personalFilters"
                    :at-a-glance="atAGlance"
                    :type="project ? 'project' : 'individual'"
                    :user_filters="user_filters"
                    :extern-updated="externUpdate"
                />


                <Menu as="div" class="relative inline-block items-center text-left">
                    <div class="">
                        <MenuButton>
                            <span class="inline-flex">
                                <button
                                        type="button"
                                        class="text-sm flex items-center my-auto text-primary font-semibold focus:outline-none transition">
                                    <img src="/Svgs/IconSvgs/icon_settings.svg" class="h-6 w-6 mx-2"/>
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
                        <MenuItems class="w-80 absolute right-0 top-12 origin-top-right rounded-sm bg-primary ring-1 ring-black p-2 text-white opacity-100 z-50">
                            <div class="w-44 p-6">
                                <div class="flex py-1" v-if="!project">
                                    <input v-model="userCalendarSettings.project_status"
                                           type="checkbox"
                                           class="checkBoxOnDark"/>
                                    <p :class="userCalendarSettings.project_status ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                       class=" ml-4 my-auto text-secondary">{{ $t('Project Status')}}</p>
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
            <button v-if="$can('request room occupancy')"
                    @click="openEventComponent()"
                    type="button"
                    class="flex p-2 px-3 mt-1 items-center border border-transparent rounded-full shadow-sm text-white hover:shadow-blueButton focus:outline-none bg-buttonBlue hover:bg-buttonHover">
                <PlusCircleIcon class="h-4 w-4 mr-2" aria-hidden="true"/>
                <p class="text-sm">{{$t('New occupancy')}}</p>
            </button>
            <div @click="showPDFConfigModal = true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-7 w-7 mx-2 cursor-pointer">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                </svg>
            </div>
        </div>


    </div>

    <div class="my-3 w-full">
        <div class="mb-1 ml-4 max-w-7xl">
            <div class="flex">
                <BaseFilterTag v-for="activeFilter in activeFilters" :filter="activeFilter" @removeFilter="removeFilter"/>
            </div>

        </div>
    </div>

    <PdfConfigModal v-if="showPDFConfigModal" @closed="showPDFConfigModal = false" :project="project" :pdf-title="project ? project.name : 'Raumbelegung'"/>
</template>

<script>
import Button from "@/Jetstream/Button";
import {PlusCircleIcon, CalendarIcon, ZoomInIcon, ZoomOutIcon} from '@heroicons/vue/outline'
import {Menu, MenuButton, MenuItems, Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";
import {ChevronDownIcon, ChevronLeftIcon, ChevronRightIcon} from "@heroicons/vue/solid";
import IndividualCalendarFilterComponent from "@/Layouts/Components/IndividualCalendarFilterComponent.vue";
import DatePickerComponent from "@/Layouts/Components/DatePickerComponent.vue";
import Dropdown from "@/Jetstream/Dropdown.vue";
import BaseFilterTag from "@/Layouts/Components/BaseFilterTag.vue";
import Permissions from "@/mixins/Permissions.vue";
import {useForm, usePage} from "@inertiajs/inertia-vue3";
import BaseFilter from "@/Layouts/Components/BaseFilter.vue";
import {Inertia} from "@inertiajs/inertia";
import PdfConfigModal from "@/Layouts/Components/PdfConfigModal.vue";


export default {
    name: "CalendarFunctionBar",
    mixins: [Permissions],
    components: {
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
        }
    },
    methods: {
        usePage,
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
            //console.log('nextTimeRange')
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
            Inertia.patch(route('user.calendar.filter.single.value.update', {user: this.$page.props.user.id}), {
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
