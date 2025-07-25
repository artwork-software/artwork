<template>
    <BaseModal modal-size="max-w-4xl" @closed="$emit('close')" full-modal>
        <div class="p-5">
            <div>
                <ModalHeader
                    :title="$t('Calendar Filter')"
                />
            </div>

            <div>
                <div class="flex items-start justify-between">
                    <div>
                        <TinyPageHeadline
                            :title="$t('Saved filters')"
                            :description="$t('Your saved filters. Click on a filter to apply it.')"
                            v-if="!saveFilterOption"
                        />
                        <TinyPageHeadline
                            :title="$t('Save filter')"
                            :description="$t('Save your current filter settings.')"
                            v-else
                        />
                    </div>
                    <div class="select-none">
                        <div v-if="!saveFilterOption" @click="saveFilterOption = true" class="underline text-artwork-buttons-create text-sm underline-offset-2 cursor-pointer hover:text-artwork-buttons-hover duration-200 ease-in-out">{{ $t('Save') }}</div>
                        <div v-else @click="saveFilterOption = false" class="underline text-red-500 text-sm underline-offset-2 cursor-pointer hover:text-red-600 duration-200 ease-in-out">{{ $t('Cancel') }}</div>
                    </div>
                </div>

                <div class="mb-4 pb-4 border-b-2 border-dashed border-gray-300">
                    <div v-if="usePage().props.personalFilters?.length > 0 && !saveFilterOption" class="flex items-center gap-4 mt-3">
                        <div v-for="(filter, index) in usePage().props.personalFilters" class="group block cursor-pointer shrink-0 bg-blue-50  w-fit px-2 py-1.5 rounded-full border border-blue-200">
                            <div class="flex items-center">
                                <div class="mx-2" @click="activateFilter(filter)">
                                    <p class="text-blue-500 text-xs font-bold group-hover:text-blue-600">{{ filter.name}}</p>
                                </div>
                                <div class="flex items-center">
                                    <button type="button" @click="removeFilter(filter)">
                                        <XIcon class="size-4 text-blue-500 hover:text-error" />
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
                            <SmallFormButton @click="saveFilter" type="button" class="bg-artwork-buttons-create text-white">
                                {{ $t('Save') }}
                            </SmallFormButton>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="flex items-start justify-between">
                    <div>
                        <TinyPageHeadline
                            :title="$t('Active filters')"
                            :description="$t('Your active filters. Click on a filter to remove it.')"
                        />
                    </div>
                </div>

                <div class="mb-4 pb-4 border-b-2 border-dashed border-gray-300">
                    <div class="flex flex-wrap items-center gap-2 mt-3">
                        <div v-for="(filter, index) in activeFilters" class="group block cursor-pointer shrink-0 bg-blue-50  w-fit px-2 py-1.5 rounded-full border border-blue-200">
                            <div class="flex items-center">
                                <div class="mx-2">
                                    <p class="text-blue-500 text-xs font-bold group-hover:text-blue-600">
                                        <span v-if="filter.id === 'adjoiningNoAudience' || filter.id === 'adjoiningNotLoud'">{{ $t(filter?.name)}}</span>
                                        <span v-else>{{ filter?.name }}</span>
                                    </p>
                                </div>
                                <div class="flex items-center">
                                    <button type="button" @click="removeActiveFilter(filter)">
                                        <XIcon class="size-4 text-blue-500 hover:text-error" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-1">
                <div v-for="(filterMainCategory, index) in filteredOptionsByCategories" :key="index" class="py-1">
                    <div class="font-extrabold font-lexend text-white bg-gray-900 rounded-lg px-4 py-2 shadow">
                        {{ $t(index) }}
                    </div>

                    <div class="space-y-2 mt-2">
                        <div v-for="(filterSubCategory, index) in filterMainCategory" :key="index">
                            <div class="shadow px-3 border border-gray-200 rounded-lg ">
                                <div class="flex items-center select-none justify-between duration-200 ease-in-out cursor-pointer py-3" @click="filterSubCategory.open = !filterSubCategory.open">
                                    <div class="font-bold ">
                                        {{ $t(index) }}
                                    </div>
                                    <div class="flex items-center gap-5">
                                        <span class="inline-flex items-center rounded-lg bg-green-50 px-2 py-1 text-xs font-medium text-green-600 ring-1 ring-inset ring-green-500/10" :class="filterSubCategory.filter(filter => filter.checked).length > 0 ? 'visible' : 'invisible'">
                                            <!-- count of checked filters in subcategory -->
                                            {{ filterSubCategory.filter(filter => filter.checked).length }} {{ $t('selected') }}
                                        </span>
                                        <component is="IconChevronDown" class="w-4 h-4 text-gray-400" :class="filterSubCategory.open ? 'rotate-180' : ''" />
                                    </div>
                                </div>

                                <div v-if="filterSubCategory.open">
                                    <div class="grid gird-cols-1 md:grid-cols-4 gap-4 my-3">
                                        <div v-for="(filter, index) in filterSubCategory" :key="index">
                                            <div class="flex items-center gap-x-2">
                                                <div class="flex h-6 shrink-0 items-center">
                                                    <div class="group grid size-4 grid-cols-1">
                                                        <input v-model="filter.checked" id="candidates" aria-describedby="candidates-description" name="candidates" type="checkbox" class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto" />
                                                        <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                                            <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                            <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                    </div>
                                                </div>
                                                <div class="text-sm flex items-center gap-x-1">
                                                    <div v-if="filter.icon" class="flex items-center gap-2">
                                                        <component :is="filter.icon" class="size-4" stroke-width="1.5"/>

                                                    </div>
                                                    <label :for="removeSpaceFromKey(filter.name)" class="font-medium text-gray-900">
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
            </div>
        </div>

        <div class="bg-gray-100 px-8 py-4 -mx-4 -mb-4 rounded-b-3xl">
            <div class="flex items-center justify-between">
                <div>
                    <div @click="resetFilter" class="underline text-artwork-buttons-create text-xs underline-offset-2 cursor-pointer hover:text-artwork-buttons-hover duration-200 ease-in-out">{{ $t('Reset') }}</div>
                </div>
                <div class="flex items-center gap-4">
                    <ArtworkBaseModalButton variant="primary" @click="applyFilter">
                        {{ $t('Apply') }}
                    </ArtworkBaseModalButton>

                </div>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import {computed, onMounted, ref} from "vue";
import SmallFormButton from "@/Components/Buttons/SmallFormButton.vue";
import {XIcon} from "@heroicons/vue/outline";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import {router, useForm, usePage} from "@inertiajs/vue3";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";

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

const filteredOptionsByCategories = computed(() => {
    let roomFilters = Object.keys(props.filterOptions).filter(key => key.includes('room'));
    let eventFilters = Object.keys(props.filterOptions).filter(key => key.includes('event'));
    let areaFilters = Object.keys(props.filterOptions).filter(key => key.includes('area'));
    let filteredOptions = {
        roomFilters: {},
        areaFilters: {},
        eventFilters: {},
    }

    areaFilters.forEach(filter => {
        filteredOptions.areaFilters[filter] = props.filterOptions[filter];
    })

    roomFilters.forEach(filter => {
        filteredOptions.roomFilters[filter] = props.filterOptions[filter];
    })

    eventFilters.forEach(filter => {
        filteredOptions.eventFilters[filter] = props.filterOptions[filter];
    })

    return filteredOptions;
})

const removeSpaceFromKey = (key) => {
    return key.replace(/\s/g, '');
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

    applyFilter();
}

const arrayToIds = (array) => {
    return array?.filter(item => item.checked).map(item => item.id) ?? null;
}

const returnNullIfFalse = (variable) => {
    if (!variable) {
        return false
    }
    return variable
}

const applyFilter = () => {
    // Get all area filters from areaFilters
    let areaFilterIds = [];
    Object.keys(filteredOptionsByCategories.value.areaFilters).forEach(areaKey => {
        const areaFilterArray = filteredOptionsByCategories.value.areaFilters[areaKey];
        if (areaFilterArray && Array.isArray(areaFilterArray)) {
            areaFilterIds = [...areaFilterIds, ...areaFilterArray.filter(item => item.checked).map(item => item.id)];
        }
    });

    router.patch(route('update.user.calendar.filter', usePage().props.auth.user.id), {
        rooms: arrayToIds(filteredOptionsByCategories.value.roomFilters.rooms),
        areas: areaFilterIds.length > 0 ? areaFilterIds : null,
        event_types: arrayToIds(filteredOptionsByCategories.value.eventFilters.event_types),
        room_attributes: arrayToIds(filteredOptionsByCategories.value.roomFilters.room_attributes),
        room_categories: arrayToIds(filteredOptionsByCategories.value.roomFilters.room_categories),
        event_properties: arrayToIds(filteredOptionsByCategories.value.eventFilters.event_properties),
    }, {
        preserveScroll: true,
        preserveState: false,
    })
}

const saveFilter = () => {
    // Get all area filters from areaFilters
    let areaFilterIds = [];
    Object.keys(filteredOptionsByCategories.value.areaFilters).forEach(areaKey => {
        const areaFilterArray = filteredOptionsByCategories.value.areaFilters[areaKey];
        if (areaFilterArray && Array.isArray(areaFilterArray)) {
            areaFilterIds = [...areaFilterIds, ...areaFilterArray.filter(item => item.checked).map(item => item.id)];
        }
    });

    // save filter to user filters
    router.post(route('filter.store'), {
        name: saveFilterForm.name,
        rooms: arrayToIds(filteredOptionsByCategories.value.roomFilters.rooms),
        areas: areaFilterIds.length > 0 ? areaFilterIds : null,
        event_types: arrayToIds(filteredOptionsByCategories.value.eventFilters.event_types),
        room_attributes: arrayToIds(filteredOptionsByCategories.value.roomFilters.room_attributes),
        room_categories: arrayToIds(filteredOptionsByCategories.value.roomFilters.room_categories),
        event_properties: arrayToIds(filteredOptionsByCategories.value.eventFilters.event_properties),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            saveFilterForm.reset();
            saveFilterOption.value = false;
            router.reload({
                only: ['personalFilters']
            })
        }
    })
}

const removeFilter = (filter) => {
    // remove filter from user filters
    router.delete(route('filter.destroy', filter.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            router.reload({
                only: ['personalFilters']
            })
        }
    })
}

const activateFilter = (filter) => {
    router.post(route('filter.activate', {filter: filter.id, user: usePage().props.auth.user.id}),{}, {
        preserveScroll: true,
        preserveState: false,
    })
}

onMounted(() => {
    // set all filters to checked if they are in user filters
    Object.keys(filteredOptionsByCategories.value).forEach(category => {
        Object.keys(filteredOptionsByCategories.value[category]).forEach(subCategory => {
            filteredOptionsByCategories.value[category][subCategory].forEach(filter => {
                filter.checked = !!props.user_filters[subCategory]?.includes(filter.id);
                filter.value = subCategory;
            })
        })
    })
})
</script>

<style scoped>

</style>
