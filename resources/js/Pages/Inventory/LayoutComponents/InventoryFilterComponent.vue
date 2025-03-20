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
                <div  v-for="filterProperty in newFilterObject" :key="filterProperty.id">
                    <div>
                        <label for="filter" class="xxsDark mb-1">{{ filterProperty.name }}</label>
                    </div>
                    <div class="flex items-center border border-gray-300 rounded-lg focus-within:ring-2 focus-within:ring-blue-500">
                        <select v-model="filterProperty.operator" class="text-gray-700 text-sm px-2 py-2 border-none rounded-l-lg focus:outline-none focus:ring-0">
                            <option v-for="filter in filterTypes" :key="filter.type" :value="filter.type">
                                {{ filter.name }}
                            </option>
                        </select>
                        <input
                            v-model="filterProperty.value"
                            :type="filterProperty.type"
                            class="w-full px-3 py-2 xsDark placeholder:xsLight rounded-r-lg border-none focus:outline-none focus:ring-0"
                            :placeholder="filterProperty.name"
                        />
                    </div>
                </div>
            </div>
            <div class="mt-3 flex justify-end">
                <SmallFormButton type="submit">
                    {{ $t('Apply Filter') }}
                </SmallFormButton>
            </div>
        </div>
    </div>
</template>

<script setup>

import {onMounted, ref} from "vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import SmallFormButton from "@/Components/Buttons/SmallFormButton.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";

const props = defineProps({
    filterableProperties: {
        type: Object,
        required: true
    },
})

const showFilter = ref(false);

const filterTypes = [
    { name: 'Enthält', type: 'like', allowedTypes: ['string'] },
    { name: 'Beginnt mit', type: 'starts_with', allowedTypes: ['string'] },
    { name: 'Endet mit', type: 'ends_with', allowedTypes: ['string'] },
    { name: 'Genau', type: 'exact', allowedTypes: ['string'] },
    { name: 'Kleiner als', type: 'less_than', allowedTypes: ['string'] },
    { name: 'Größer als', type: 'greater_than', allowedTypes: ['string'] },
    { name: 'Bis', type: 'until', allowedTypes: ['string'] },
    { name: 'Von', type: 'from', allowedTypes: ['string'] },
    { name: 'Gleich', type: 'equals', allowedTypes: ['string'] },
    { name: 'Ungleich', type: 'not_equals', allowedTypes: ['boolean'] },
    { name: 'Ist leer', type: 'is_null', allowedTypes: ['string'] },
    { name: 'Enthält nicht', type: 'not_like', allowedTypes: ['string'] },
    { name: 'Datum vor', type: 'date_before', allowedTypes: ['date', 'datetime', 'time'] },
    { name: 'Datum nach', type: 'date_after', allowedTypes: ['date', 'datetime', 'time'] },
];

const newFilterObject = ref([]);
onMounted(() => {
    props.filterableProperties.forEach((property) => {
        if (property.type !== "file") {
            newFilterObject.value.push({
                id: property.id,
                name: property.name,
                operator: 'like',
                value: '',
                type: property.type
            })
        }

    })
})

</script>

<style scoped>

</style>