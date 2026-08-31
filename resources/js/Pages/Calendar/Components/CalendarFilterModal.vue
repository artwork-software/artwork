<template>
    <ArtworkBaseModal modal-size="max-w-4xl" title="Calendar Filter" description="Allows you to show and hide specific calendar contents, ideal for quickly finding the relevant information." @close="$emit('close')" full-modal>
        <div class="">
            <div>
                <div class="flex items-start justify-between">
                    <div>
                        <BasePageTitle
                            :title="$t('Saved filters')"
                            :description="$t('Your saved filters. Click on a filter to apply it.')"
                            v-if="!saveFilterOption"
                        />
                        <BasePageTitle
                            :title="$t('Save filter')"
                            :description="$t('Save your current filter settings.')"
                            v-else
                        />
                    </div>
                    <div class="select-none">
                        <div v-if="!saveFilterOption" @click="saveFilterOption = true" class="underline text-accent-600 text-sm underline-offset-2 cursor-pointer hover:text-accent-700 duration-200 ease-in-out">{{ $t('Save') }}</div>
                        <div v-else @click="saveFilterOption = false" class="underline text-danger text-sm underline-offset-2 cursor-pointer hover:text-danger/80 duration-200 ease-in-out">{{ $t('Cancel') }}</div>
                    </div>
                </div>

                <div class="mb-4 pb-4 border-b-2 border-dashed border-border">
                    <div v-if="usePage().props.personalFilters?.length > 0 && !saveFilterOption" class="flex flex-wrap items-center gap-2 mt-3">
                        <div v-for="(filter, index) in usePage().props.personalFilters" class="group block cursor-pointer shrink-0 bg-accent-50  w-fit px-2 py-1.5 rounded-full border border-accent-200">
                            <div class="flex items-center">
                                <div class="mx-2" @click="activateFilter(filter)">
                                    <p class="text-accent-600 text-xs group-hover:text-accent-700">{{ filter.name}}</p>
                                </div>
                                <div class="flex items-center">
                                    <button type="button" @click="removeFilter(filter)">
                                        <IconX class="size-4 text-accent-600 hover:text-danger" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="saveFilterOption">
                        <div class="flex items-center gap-x-4 mt-3">
                            <BaseInput
                                id="filterName"
                                v-model="saveFilterForm.name"
                                label="Filter name"
                            />
                            <BaseUIButton @click="saveFilter" type="button" label="Save" use-translation is-add-button/>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="flex items-start justify-between">
                    <div>
                        <BasePageTitle
                            :title="$t('Active filters')"
                            :description="$t('Your active filters. Click on a filter to remove it.')"
                        />
                    </div>
                </div>

                <div class="mb-4 pb-4 border-b-2 border-dashed border-border">
                    <div class="flex flex-wrap items-center gap-2 mt-3">
                        <div v-if="staffingFilterContext && showOnlyNotFullyStaffed" class="group block cursor-pointer shrink-0 bg-accent-50 w-fit px-2 py-1.5 rounded-full border border-accent-200">
                            <div class="flex items-center">
                                <div class="mx-2">
                                    <p class="text-accent-600 text-xs group-hover:text-accent-700">{{ $t('Only show shifts that are not fully staffed') }}</p>
                                </div>
                                <div class="flex items-center">
                                    <button type="button" @click="showOnlyNotFullyStaffed = false">
                                        <IconX class="size-4 text-accent-600 hover:text-danger" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-for="(filter, index) in activeFilters" class="group block cursor-pointer shrink-0 bg-accent-50  w-fit px-2 py-1.5 rounded-full border border-accent-200">
                            <div class="flex items-center">
                                <div class="mx-2">
                                    <p class="text-accent-600 text-xs group-hover:text-accent-700">
                                        <span v-if="filter.id === 'adjoiningNoAudience' || filter.id === 'adjoiningNotLoud'">{{ $t(filter?.name)}}</span>
                                        <span v-else>{{ filter?.name }}</span>
                                    </p>
                                </div>
                                <div class="flex items-center">
                                    <button type="button" @click="removeActiveFilter(filter)">
                                        <IconX class="size-4 text-accent-600 hover:text-danger" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-1">
                <div v-for="(filterMainCategory, mainKey) in filteredOptionsByCategories" :key="mainKey"
                     v-show="Object.values(filterMainCategory).some(sub => sub.length > 0)" class="py-1">
                    <div class="flex items-center gap-x-1.5 text-text-inverse bg-surface-inverse rounded-lg px-4 py-2 font-lexend shadow text-sm">
                        {{ $t(mainKey) }}
                        <ToolTipComponent
                            v-if="mainKey === 'projectStateFilters'"
                            direction="right"
                            :tooltip-text="$t('project_state_filter_info')"
                            icon="IconInfoCircle"
                            icon-size="size-4"
                            white-icon
                            classes-button=""
                            tooltip-css-class="aw-tooltip-wide"
                        />
                    </div>

                    <div class="space-y-2 mt-2">
                        <div v-for="(filterSubCategory, subKey) in filterMainCategory" :key="subKey"
                             v-show="filterSubCategory.length > 0">
                            <div class="rounded-lg bg-surface border border-border-subtle w-full shadow-raised px-4 ">
                                <div class="flex items-center select-none justify-between duration-200 ease-in-out cursor-pointer py-3" @click="toggleOpen(mainKey, subKey)">
                                    <div class="text-sm text-text">
                                        {{ $t(subKey) }}
                                    </div>
                                    <div class="flex items-center gap-5">
                                        <span class="inline-flex items-center rounded-lg bg-success-surface px-2 py-1 text-xs/4 text-success ring-1 ring-inset ring-success-border" :class="filterSubCategory.filter(filter => filter.checked).length > 0 ? 'visible' : 'invisible'">
                                            <!-- count of checked filters in subcategory -->
                                            {{ filterSubCategory.filter(filter => filter.checked).length }} {{ $t('selected') }}
                                        </span>
                                        <component :is="IconChevronDown" class="w-4 h-4 text-text-subtle" :class="isOpen(mainKey, subKey) ? 'rotate-180' : ''" />
                                    </div>
                                </div>

                                <div v-if="isOpen(mainKey, subKey)">
                                    <div class="grid gird-cols-1 md:grid-cols-4 gap-4 my-3">
                                        <div v-for="(filter, index) in filterSubCategory" :key="index">
                                            <div class="flex items-center gap-x-2"
                                                 :class="filter.color ? 'rounded-lg px-2 py-1' : ''"
                                                 :style="filter.color ? { backgroundColor: colorWithLightOpacity(filter.color) } : null">
                                                <div class="flex h-6 shrink-0 items-center">
                                                    <div class="group grid size-4 grid-cols-1">
                                                        <input v-model="filter.checked" :id="removeSpaceFromKey(filter.name)" :aria-describedby="removeSpaceFromKey(filter.name) + '-description'" :name="removeSpaceFromKey(filter.name)" type="checkbox" class="col-start-1 row-start-1 appearance-none rounded-sm border border-border bg-white checked:border-accent-600 checked:bg-accent-600 indeterminate:border-accent-600 indeterminate:bg-accent-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600 disabled:border-border disabled:bg-surface-sunken disabled:checked:bg-surface-sunken forced-colors:appearance-auto" />
                                                        <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-border-strong" viewBox="0 0 14 14" fill="none">
                                                            <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                            <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                    </div>
                                                </div>
                                                <div class="text-sm flex items-center gap-x-1">
                                                    <div v-if="filter.icon" class="flex items-center gap-2">
                                                        <component :is="filter.icon" class="size-4" stroke-width="1.5"/>
                                                    </div>
                                                    <label :for="removeSpaceFromKey(filter.name)" class="text-text">
                                                        {{ filter.name }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Schichtfilter: Besetzungsfilter — schreibt das Schichtplan-Setting
                     show_only_not_fully_staffed_shifts (gleiche Wahrheit wie das
                     Anzeigeeinstellungs-Modal), kein user_filters-Eintrag -->
                <div v-if="staffingFilterContext" class="py-1">
                    <div class="flex items-center gap-x-1.5 text-text-inverse bg-surface-inverse rounded-lg px-4 py-2 font-lexend shadow text-sm">
                        {{ $t('shiftFilters') }}
                    </div>
                    <div class="mt-2 rounded-lg bg-surface border border-border-subtle w-full shadow-raised px-4 py-3">
                        <BaseCheckbox
                            v-model="showOnlyNotFullyStaffed"
                            id="filter_show_only_not_fully_staffed_shifts"
                            name="filter_show_only_not_fully_staffed_shifts"
                            :label="$t('Only show shifts that are not fully staffed')"
                            :description="$t('Only displays shifts where at least one position still has capacity for additional staff.')"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="py-4">
            <div class="flex items-center justify-between">
                <div>
                    <BaseUIButton @click="resetFilter" type="button" label="Reset" use-translation icon="IconRestore"/>
                </div>
                <div class="flex items-center gap-4">
                    <BaseUIButton @click="applyFilter" type="button" label="Apply" use-translation is-add-button icon="IconCircleCheck"/>

                </div>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>

import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import {computed, onMounted, ref} from "vue";
import {router, useForm, usePage} from "@inertiajs/vue3";
import axios from "axios";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseCheckbox from "@/Artwork/Inputs/BaseCheckbox.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import {IconChevronDown, IconX} from "@tabler/icons-vue";
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";

// Local open/close state per subcategory to avoid mutating computed arrays
const openState = ref({});
const keyFor = (mainKey, subKey) => `${mainKey}::${subKey}`;
const isOpen = (mainKey, subKey) => !!openState.value[keyFor(mainKey, subKey)];
const toggleOpen = (mainKey, subKey) => {
    const k = keyFor(mainKey, subKey);
    openState.value[k] = !openState.value[k];
};

const props = defineProps({
    filterOptions: {
        type: Object,
        required: true
    },
    personalFilters: {
        type: Object,
        required: true
    },
    user_filters: {
        type: Object,
        required: true
    },
    inShiftPlan: {
        type: Boolean,
        default: false
    },
    filterType: {
        type: String,
        default: 'calendar_filter'
    }
})

const emits = defineEmits([
    'close'
])


const saveFilterOption = ref(false);
const saveFilterForm = useForm({
    name: ''
})

const activeFilters = computed(() => {
    let activeFilters = [];
    Object.keys(filteredOptionsByCategories.value).forEach(category => {
        Object.keys(filteredOptionsByCategories.value[category]).forEach(subCategory => {
            activeFilters.push(...filteredOptionsByCategories.value[category][subCategory].filter(filter => filter.checked));
        })
    })

    return activeFilters;
})

// Schicht-Kontexte (Schichtplan & Co.) — dort gibt es Gewerke, aber keinen Projektstatus-Filter
const isShiftFilterContext = computed(() =>
    props.filterType === 'shift_filter' ||
    props.filterType === 'shift_daily_filter' ||
    props.filterType === 'shift_list_view_filter' ||
    props.filterType === 'project_shift_filter' ||
    props.inShiftPlan
);

// Besetzungsfilter nur in Dienstplan-Wochen-/Tagesansicht: das Setting liegt auf
// user_shift_plan_settings bzw. user_shift_plan_daily_settings und wird von
// ShiftPlan.vue/ShiftPlanDailyView.vue ausgewertet
const staffingFilterContext = computed(() =>
    props.filterType === 'shift_filter' || props.filterType === 'shift_daily_filter'
);

const currentShiftPlanSettings = computed(() => {
    const pageProps = usePage().props;
    // Spiegel von calendarSettings in ShiftPlan.vue (gleiche Fallback-Kette)
    return props.filterType === 'shift_daily_filter'
        ? (pageProps.shift_plan_daily_settings ?? pageProps.shift_plan_settings ?? pageProps.auth.user.calendar_settings)
        : (pageProps.shift_plan_settings ?? pageProps.auth.user.calendar_settings);
});

const showOnlyNotFullyStaffed = ref(false);

const persistStaffingFilterIfChanged = async () => {
    if (!staffingFilterContext.value) {
        return;
    }
    const current = !!currentShiftPlanSettings.value?.show_only_not_fully_staffed_shifts;
    if (current === showOnlyNotFullyStaffed.value) {
        return;
    }
    // Gleicher Endpunkt wie das Anzeigeeinstellungs-Modal; Request::only() im Backend
    // übernimmt nur mitgesendete Felder, die übrigen Settings bleiben unberührt
    await axios.patch(route('user.calendar_settings.update', usePage().props.auth.user.id), {
        is_shift_plan: true,
        is_daily_view: props.filterType === 'shift_daily_filter',
        show_only_not_fully_staffed_shifts: showOnlyNotFullyStaffed.value,
    });
};

const filteredOptionsByCategories = computed(() => {
    let roomFilters = Object.keys(props.filterOptions).filter(key => key.includes('room'));
    let eventFilters = Object.keys(props.filterOptions).filter(key => key.includes('event'));
    let areaFilters = Object.keys(props.filterOptions).filter(key => key.includes('area'));
    let craftFilter = Object.keys(props.filterOptions).filter(key => key.includes('craft'));
    let projectStateFilter = Object.keys(props.filterOptions).filter(key => key.includes('project_state'));
    let filteredOptions = {
        roomFilters: {},
        areaFilters: {},
        eventFilters: {},
    }

    // Areas are passed through unchanged
    areaFilters.forEach(filter => {
        filteredOptions.areaFilters[filter] = props.filterOptions[filter];
    })

    // Rooms: exclude rooms that are not relevant for disposition from appearing in the filter modal
    roomFilters.forEach(filter => {
        const list = props.filterOptions[filter] || [];
        // Only apply relevance filtering to the actual room list (usually key === 'rooms').
        // Other room-related groups (e.g., roomCategories, roomAttributes) should remain untouched.
        if (filter === 'rooms' || filter === 'room_ids') {
            // Exclude rooms explicitly marked as not relevant for disposition and sort alphabetically by name
            filteredOptions.roomFilters[filter] = list
                .filter(item => {
                    const rel = item?.relevant_for_disposition;
                    return !(rel === false || rel === 0 || rel === '0');
                })
                .sort((a, b) => String(a?.name ?? '').localeCompare(String(b?.name ?? ''), undefined, { sensitivity: 'base' }));
        } else {
            filteredOptions.roomFilters[filter] = list;
        }
    })

    // Events are passed through unchanged
    eventFilters.forEach(filter => {
        filteredOptions.eventFilters[filter] = props.filterOptions[filter];
    })

    // Crafts are only included for shift filter / shift plan
    if(isShiftFilterContext.value) {
        filteredOptions.craftFilters = {};
        craftFilter.forEach(filter => {
            filteredOptions.craftFilters[filter] = props.filterOptions[filter];
        })
    }

    // Project states are only offered in calendar contexts (not in the shift plan)
    if(!isShiftFilterContext.value) {
        filteredOptions.projectStateFilters = {};
        projectStateFilter.forEach(filter => {
            filteredOptions.projectStateFilters[filter] = props.filterOptions[filter];
        })
    }

    return filteredOptions;
})

const removeSpaceFromKey = (key) => {
    return key.replace(/\s/g, '');
}

// Raum-Einträge werden mit ihrer Raumfarbe leicht transparent hinterlegt
const colorWithLightOpacity = (color) => {
    const r = parseInt(color.slice(-6, -4), 16);
    const g = parseInt(color.slice(-4, -2), 16);
    const b = parseInt(color.slice(-2), 16);
    return `rgba(${r}, ${g}, ${b}, 0.15)`;
}

const removeActiveFilter = (filterToRemove) => {
    Object.keys(filteredOptionsByCategories.value).forEach((category) => {
        Object.keys(filteredOptionsByCategories.value[category]).forEach((subCategory) => {
            const filterItem = filteredOptionsByCategories.value[category][subCategory].find(
                (item) => item.id === filterToRemove.id && item.value === filterToRemove.value
            );

            if (filterItem) {
                filterItem.checked = false;
            }
        });
    });
};

const resetFilter = () => {
    // reset all filters to unchecked
    Object.keys(filteredOptionsByCategories.value).forEach(category => {
        Object.keys(filteredOptionsByCategories.value[category]).forEach(subCategory => {
            filteredOptionsByCategories.value[category][subCategory].forEach(filter => {
                filter.checked = false;
            })
        })
    })
    showOnlyNotFullyStaffed.value = false;

    applyFilter();
}

const extractCheckedIds = (filterGroup) => {
    const result = {};
    Object.entries(filteredOptionsByCategories.value[filterGroup]).forEach(([key, list]) => {
        const checked = list.filter(item => item.checked).map(item => item.id);
        result[key] = checked.length > 0 ? checked : null;
    });
    return result;
};

const applyFilter = async () => {
    await persistStaffingFilterIfChanged();

    const data = {
        filter_type: props.filterType,
    };

    Object.assign(data, extractCheckedIds('roomFilters'));
    Object.assign(data, extractCheckedIds('areaFilters'));
    Object.assign(data, extractCheckedIds('eventFilters'));
    if(isShiftFilterContext.value) {
        Object.assign(data, extractCheckedIds('craftFilters'));
    } else {
        Object.assign(data, extractCheckedIds('projectStateFilters'));
    }
    router.patch(route('update.user.calendar.filter', usePage().props.auth.user.id), data, {
        preserveScroll: true,
        preserveState: false,
        onFinish: () => {
            restoreFilterState()
        }
    });
}



const saveFilter = () => {
    const data = {
        filter_type: props.filterType,
        name: saveFilterForm.name
    };

    Object.assign(data, extractCheckedIds('roomFilters'));
    Object.assign(data, extractCheckedIds('areaFilters'));
    Object.assign(data, extractCheckedIds('eventFilters'));
    if(isShiftFilterContext.value) {
        Object.assign(data, extractCheckedIds('craftFilters'));
    } else {
        Object.assign(data, extractCheckedIds('projectStateFilters'));
    }
    router.post(route('filter.store', usePage().props.auth.user.id), data, {
        preserveScroll: true,
        onSuccess: () => {
            saveFilterForm.reset();
            saveFilterOption.value = false;
            router.reload({
                only: ['personalFilters']
            })
        },
        onFinish: () => {
            restoreFilterState()
        }
    });
}

const removeFilter = (filter) => {
    router.delete(route('filter.destroy', filter.id), {
        preserveScroll: true,
        onSuccess: () => {
            router.reload({
                only: ['personalFilters']
            })
        },
        onFinish: () => {
            restoreFilterState()
        }
    })
}

const activateFilter = (filter) => {
    router.post(route('filter.activate', {filter: filter.id, user: usePage().props.auth.user.id}),{}, {
        preserveScroll: true,
        preserveState: false,
    })
}

const restoreFilterState = () => {
    Object.keys(filteredOptionsByCategories.value).forEach(category => {
        Object.keys(filteredOptionsByCategories.value[category]).forEach(subCategory => {
            filteredOptionsByCategories.value[category][subCategory].forEach(filter => {
                filter.checked = !!props.user_filters?.[subCategory]?.includes(filter.id);
                filter.value = subCategory;
            })
        })
    })
}

onMounted(() => {
    restoreFilterState();
    showOnlyNotFullyStaffed.value = !!currentShiftPlanSettings.value?.show_only_not_fully_staffed_shifts;
});
</script>

<style scoped>

</style>
