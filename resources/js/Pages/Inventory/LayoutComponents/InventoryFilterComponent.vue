<template>
    <div class="select-none border-b border-gray-300" :class="showFilter ? 'pb-4' : ''">
        <div
            class="flex items-start gap-x-4 cursor-pointer hover:text-artwork-buttons-hover"
            @click="showFilter = !showFilter"
        >
            <BasePageTitle
                :title="$t('Filter')"
            />
            <component
                :is="IconChevronDown"
                class="size-5 mt-0.5 transition-transform duration-150"
                :class="showFilter ? 'rotate-180 transform' : ''"
            />
        </div>

        <div v-if="showFilter">
            <!-- Property-Filter -->
            <div
                class="grid gap-4 mt-3"
                style="grid-template-columns: repeat(auto-fit, minmax(200px, max-content));"
            >
                <div
                    v-for="filterProperty in newFilterObject"
                    :key="filterProperty.id"
                >
                    <div>
                        <label class="font-lexend text-xs mb-1 whitespace-nowrap">
                            {{ filterProperty.name }}
                        </label>
                    </div>

                    <!-- Text/Number/Date etc. -->
                    <div
                        class="flex items-center border border-gray-200 rounded-lg focus-within:ring-2 focus-within:ring-blue-500"
                        v-if="filterProperty.type !== 'selection' && filterProperty.type !== 'checkbox'"
                    >
                        <select
                            v-model="filterProperty.operator"
                            v-if="getAllowedFilters(filterProperty.type).length > 0"
                            class="text-gray-700 min-w-28 text-sm px-2 py-2 border-none rounded-l-lg focus:outline-none focus:ring-0"
                        >
                            <option
                                v-for="filter in getAllowedFilters(filterProperty.type)"
                                :key="filter.type"
                                :value="filter.type"
                            >
                                {{ filter.name }}
                            </option>
                        </select>
                        <input
                            v-model="filterProperty.value"
                            :type="filterProperty.type"
                            class="w-full px-3 py-2 xsDark placeholder:xsLight shadow-sm h-10 rounded-lg border-none focus:outline-none focus:ring-0"
                            :placeholder="filterProperty.name"
                        />
                    </div>

                    <!-- Selection -->
                    <div
                        v-if="filterProperty.type === 'selection'"
                        class="w-full"
                    >
                        <select
                            id="location"
                            name="location"
                            @change="submitFilter"
                            v-model="filterProperty.value"
                            class="block shadow-sm w-full h-10 rounded-lg border border-gray-200 bg-white text-xs text-gray-900 outline-0 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-0 ring-0 focus:ring-0"
                        >
                            <option
                                v-for="value in filterProperty.select_values"
                                :value="value"
                                :key="value"
                            >
                                {{ value }}
                            </option>
                        </select>
                    </div>

                    <!-- Checkbox -->
                    <div
                        v-if="filterProperty.type === 'checkbox'"
                        class="w-full h-full mt-1.5"
                    >
                        <div class="flex gap-3">
                            <div class="flex h-6 shrink-0 items-center">
                                <div class="group grid size-4 grid-cols-1">
                                    <input
                                        v-model="filterProperty.value"
                                        @change="submitFilter"
                                        id="comments"
                                        aria-describedby="comments-description"
                                        name="comments"
                                        type="checkbox"
                                        class="input-checklist"
                                    />
                                </div>
                            </div>
                            <div class="text-sm/6">
                                <label for="comments" class="font-medium text-gray-900">
                                    {{ filterProperty.name }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 🔹 Tag-Filter -->
            <div class="mt-5 space-y-2" v-if="tags && tags.length">
                <div class="flex items-center gap-2">
                    <span class="font-lexend text-xs text-gray-700">
                        {{ $t('Tags') }}
                    </span>
                </div>

                <!-- einfache Multi-Select / Pills -->
                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="tag in tags"
                        :key="tag.id"
                        type="button"
                        class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-medium transition-colors"
                        :class="selectedTagIds.includes(tag.id)
                            ? 'bg-indigo-50 border-indigo-200 text-indigo-700'
                            : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
                        @click="toggleTag(tag.id)"
                    >
                        <span
                            class="inline-block h-2 w-2 rounded-full border border-white/60"
                            :style="{ backgroundColor: tag.color || '#4f46e5' }"
                        />
                        <span class="truncate max-w-[120px]">
                            {{ tag.name }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-3 flex items-center gap-x-5">
                <SmallFormButton type="button" @click="submitFilter">
                    {{ $t('Apply Filter') }}
                </SmallFormButton>

                <SmallFormButton
                    v-if="checkIfAnyFilterIsSet"
                    type="button"
                    @click="resetFilter"
                >
                    {{ $t('Reset') }}
                </SmallFormButton>
            </div>
        </div>

        <!-- Aktive Filter Chips -->
        <div
            class="my-3 flex flex-wrap gap-2"
            v-if="newFilterObject.length > 0 || selectedTagIds.length > 0"
        >
            <!-- Property-Filter Tags -->
            <div
                v-for="(filter, index) in newFilterObject"
                :key="'prop-' + index"
            >
                <div
                    v-if="filter.value"
                    class="flex items-center bg-blue-50 rounded-full px-3 py-1 text-sm font-medium text-blue-700 border border-blue-100"
                >
                    <span>
                        {{ filter.name }}{{ isBool(filter.value) ? '' : ': ' + filter.value }}
                    </span>
                    <button
                        type="button"
                        @click="removeFilter(filter)"
                        class="ml-2 text-blue-500 hover:text-blue-700"
                    >
                        <component :is="IconX" class="size-4" />
                    </button>
                </div>
            </div>

            <!-- 🔹 Tag-Filter Chips -->
            <div
                v-for="tag in activeTagObjects"
                :key="'tag-' + tag.id"
            >
                <div
                    class="flex items-center rounded-full px-3 py-1 text-sm font-medium border"
                    :style="tagChipStyle(tag)"
                >
                    <span>{{ tag.name }}</span>
                    <button
                        type="button"
                        @click="removeTag(tag.id)"
                        class="ml-2 hover:opacity-80"
                    >
                        <component :is="IconX" class="size-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, inject } from 'vue'
import SmallFormButton from '@/Components/Buttons/SmallFormButton.vue'
import { router, usePage } from '@inertiajs/vue3'
import { isBool } from '@aesoper/normal-utils'
import { IconChevronDown, IconX } from '@tabler/icons-vue'
import BasePageTitle from '@/Artwork/Titles/BasePageTitle.vue'
import { useTranslation } from '@/Composeables/Translation.js'

const $t = useTranslation()

const props = defineProps({
    filterableProperties: {
        type: Object,
        required: true,
    },
})

const showFilter = ref(false)

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
]

const newFilterObject = ref([])

// Tags kommen aus Index via Inertia (InventoryTag::... -> 'tags')
const tagGroups = inject('tagGroups', [])
const tags = inject('tags', [])

// 🔹 ausgewählte Tags (IDs)
const selectedTagIds = ref([])

onMounted(() => {
    const page = usePage()
    let activeFilters = []

    try {
        const queryFilters = page.props.urlParameters?.filters

        if (Array.isArray(queryFilters)) {
            activeFilters = queryFilters
        } else if (typeof queryFilters === 'string') {
            activeFilters = JSON.parse(queryFilters)
        }
    } catch (e) {
        console.warn('Fehler beim Parsen der Filter:', e)
    }

    // Property-Filter initialisieren
    props.filterableProperties.forEach((property) => {
        if (property.type !== 'file') {
            const existingFilter = activeFilters.find((f) => f.property_id === property.id)

            newFilterObject.value.push({
                id: property.id,
                name: property.name,
                operator: existingFilter?.operator ?? 'like',
                value: existingFilter?.value ?? '',
                type: property.type,
                select_values: property.select_values,
            })
        }
    })

    // 🔹 Tag-Filter aus URL lesen (tag_ids)
    const urlTagIds = page.props.urlParameters?.tag_ids ?? []

    if (Array.isArray(urlTagIds)) {
        selectedTagIds.value = urlTagIds.map((id) => Number(id)).filter((n) => !Number.isNaN(n))
    } else if (typeof urlTagIds === 'string' && urlTagIds.length) {
        selectedTagIds.value = urlTagIds
            .split(',')
            .map((id) => parseInt(id, 10))
            .filter((n) => !Number.isNaN(n))
    }
})

const submitFilter = () => {
    const cleanFilters = newFilterObject.value
        .filter((f) => f.value !== '' && f.value !== null && f.value !== false)
        .map((f) => ({
            property_id: f.id,
            operator: f.operator,
            value: f.value,
        }))

    router.reload({
        data: {
            filters: JSON.stringify(cleanFilters),
            tag_ids: selectedTagIds.value,
        },
    })
}

const checkIfAnyFilterIsSet = computed(() => {
    const hasProps = newFilterObject.value.some((filter) => {
        if (filter.type === 'checkbox') {
            return filter.value
        } else if (filter.type === 'selection') {
            return filter.value !== ''
        } else {
            return filter.value !== '' && filter.value !== null && filter.value !== false
        }
    })

    const hasTags = selectedTagIds.value.length > 0

    return hasProps || hasTags
})

const getAllowedFilters = (type) => {
    if (!type) return []
    return filterTypes.filter((f) => f.allowedTypes.includes(type))
}

const removeFilter = (filter) => {
    filter.value = ''
    submitFilter()
}

const resetFilter = () => {
    newFilterObject.value.forEach((filter) => {
        filter.value = ''
        filter.operator = 'like'
    })

    selectedTagIds.value = []

    router.reload({
        data: {
            filters: JSON.stringify([]),
            tag_ids: [],
        },
    })
}

/**
 * 🔹 Tag-Helpers
 */
const toggleTag = (tagId) => {
    const idx = selectedTagIds.value.indexOf(tagId)
    if (idx === -1) {
        selectedTagIds.value.push(tagId)
    } else {
        selectedTagIds.value.splice(idx, 1)
    }
}

const removeTag = (tagId) => {
    selectedTagIds.value = selectedTagIds.value.filter((id) => id !== tagId)
    submitFilter()
}

const activeTagObjects = computed(() => {
    if (!Array.isArray(tags) || !selectedTagIds.value.length) return []
    return tags.filter((t) => selectedTagIds.value.includes(t.id))
})

const tagChipStyle = (tag) => {
    const base = tag?.color || '#2563eb' // fallback: Indigo
    return {
        backgroundColor: base + '10',
        borderColor: base + '40',
        color: base,
    }
}
</script>

<style scoped>
</style>
