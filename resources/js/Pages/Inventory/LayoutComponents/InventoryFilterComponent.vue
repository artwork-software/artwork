<template>
    <div class="select-none border-b" :class="showFilter ? 'pb-4' : ''">
        <div class="flex items-start gap-x-4 cursor-pointer hover:text-artwork-buttons-hover" @click="showFilter = !showFilter">
            <TinyPageHeadline
                title="Filter"
                description=""
            />
            <component is="IconChevronDown" class="size-5 mt-0.5" :class="showFilter ? 'rotate-180 transform' : ''" />
        </div>
        <div v-if="showFilter">
            <div class="grid grid-cols-1 md:grid-cols-4 xl:grid-cols-6 gap-4">
                <div v-for="filterProperty in newFilterObject" :key="filterProperty.id">
                    <div>
                        <label class="font-lexend text-xs mb-1">{{ filterProperty.name }}</label>
                    </div>
                    <div class="flex items-center border border-gray-200 rounded-lg focus-within:ring-2 focus-within:ring-blue-500">
                        <select v-model="filterProperty.operator" v-if="getAllowedFilters(filterProperty.type).length > 0" class="text-gray-700 min-w-28 text-sm px-2 py-2 border-none rounded-l-lg focus:outline-none focus:ring-0">
                            <option v-for="filter in getAllowedFilters(filterProperty.type)" :key="filter.type" :value="filter.type">
                                {{ filter.name }}
                            </option>
                        </select>
                        <input
                            v-if="filterProperty.type !== 'selection'"
                            v-model="filterProperty.value"
                            :type="filterProperty.type"
                            class="w-full px-3 py-2 xsDark placeholder:xsLight h-10 rounded-lg border-none focus:outline-none focus:ring-0 checked:text-green-500"
                            :placeholder="filterProperty.name"
                        />
                        <div v-else class="w-full h-full">
                            <select id="location" name="location" v-model="filterProperty.value" class="block w-full h-10 rounded-md bg-white border-none text-xs text-gray-900 outline-0 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-0 ring-0 focus:ring-0">
                                <option v-for="value in filterProperty.select_values" :value="value" :key="value">{{ value }}</option>
                            </select>
                        </div>
                    </div>
                </div>

            </div>
            <div class="mt-3 flex justify-between">
                <SmallFormButton type="button" @click="submitFilter">
                    {{ $t('Apply Filter') }}
                </SmallFormButton>
            </div>
        </div>
        <div class="my-3">
            <!-- filter Tags -->
            <div v-if="newFilterObject.length > 0" v-for="(filter, index) in newFilterObject" :key="index" class="flex flex-wrap gap-2">
                <div v-if="filter.value" class="flex items-center bg-blue-50 rounded-full px-3 py-1 text-sm font-medium text-blue-700 border border-blue-100">
                    <span>{{ filter.name }}: {{ filter.value }}</span>
                    <button type="button" @click="removeFilter(filter)" class="ml-2 text-blue-500 hover:text-blue-700">
                        <component is="IconX" class="size-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>

import {onMounted, ref} from "vue";
import SmallFormButton from "@/Components/Buttons/SmallFormButton.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import {router, usePage} from "@inertiajs/vue3";

const props = defineProps({
    filterableProperties: {
        type: Object,
        required: true
    },
})

const showFilter = ref(false);

const filterTypes = [
    { name: 'Enthält', type: 'like', allowedTypes: ['string', 'room', 'manufacturer'] },
    { name: 'Beginnt mit', type: 'starts_with', allowedTypes: ['string', 'room', 'manufacturer'] },
    { name: 'Endet mit', type: 'ends_with', allowedTypes: ['string', 'room', 'manufacturer'] },
    { name: 'Genau', type: 'exact', allowedTypes: ['string', 'date'] },
    { name: 'Kleiner als', type: 'less_than', allowedTypes: ['string', 'date'] },
    { name: 'Größer als', type: 'greater_than', allowedTypes: ['string', 'date'] },
    { name: 'Bis', type: 'until', allowedTypes: ['date'] },
    { name: 'Von', type: 'from', allowedTypes: ['date'] },
    { name: 'Gleich', type: 'equals', allowedTypes: ['string', 'date', 'room', 'manufacturer'] },
    { name: 'Ungleich', type: 'not_equals', allowedTypes: ['boolean', 'date'] },
    { name: 'Ist leer', type: 'is_null', allowedTypes: ['string', 'date', 'boolean'] },
    { name: 'Enthält nicht', type: 'not_like', allowedTypes: ['string'] },
    { name: 'Datum vor', type: 'date_before', allowedTypes: ['date', 'datetime', 'time'] },
    { name: 'Datum nach', type: 'date_after', allowedTypes: ['date', 'datetime', 'time'] },
];

const newFilterObject = ref([]);

onMounted(() => {
    let activeFilters = [];

    try {
        const queryFilters = usePage().props.urlParameters.filters;

        if (Array.isArray(queryFilters)) {
            activeFilters = queryFilters;
        } else if (typeof queryFilters === 'string') {
            activeFilters = JSON.parse(queryFilters);
        }
    } catch (e) {
        console.warn('Fehler beim Parsen der Filter:', e);
    }

    props.filterableProperties.forEach((property) => {
        if (property.type !== "file") {
            const existingFilter = activeFilters.find(f => f.property_id === property.id);

            newFilterObject.value.push({
                id: property.id,
                name: property.name,
                operator: existingFilter?.operator ?? 'like',
                value: existingFilter?.value ?? '',
                type: property.type,
                select_values: property.select_values,
            });
        }
    });
});

const submitFilter = () => {
    const cleanFilters = newFilterObject.value
        .filter(f => f.value !== '' && f.value !== null)
        .map(f => ({
            property_id: f.id,
            operator: f.operator,
            value: f.value,
        }));

    router.reload({
        data: {
            filters: JSON.stringify(cleanFilters),
        },
    });
}

const getAllowedFilters = (type) => {
    if (!type) return [];
    return filterTypes.filter(f => f.allowedTypes.includes(type));
}

const removeFilter = (filter) => {
    filter.value = '';
    submitFilter()
}

</script>

<style scoped>

</style>