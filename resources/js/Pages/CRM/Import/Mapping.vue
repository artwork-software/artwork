<template>
    <AppLayout :title="$t('Map columns')">
        <div class="mt-5 mx-auto container pb-20">
            <ToolbarHeader
                :icon="IconTableColumn"
                :title="$t('Map columns')"
                icon-bg-class="bg-indigo-600/10 text-indigo-700"
                :description="$t('{count} rows to import', { count: totalRows })"
            >
                <template #actions>
                    <button class="ui-button" @click="cancel">
                        {{ $t('Cancel') }}
                    </button>
                </template>
            </ToolbarHeader>

            <!-- Step Indicator -->
            <div class="mt-6 mb-8">
                <div class="flex items-center space-x-3">
                    <span class="flex items-center justify-center size-8 rounded-full bg-green-600 text-white text-sm font-bold">
                        <component :is="IconCheck" class="size-4" />
                    </span>
                    <span class="text-sm text-gray-500">{{ $t('Upload file') }}</span>
                    <div class="flex-1 h-px bg-indigo-300"></div>
                    <span class="flex items-center justify-center size-8 rounded-full bg-indigo-600 text-white text-sm font-bold">2</span>
                    <span class="text-sm font-medium text-gray-900">{{ $t('Map columns') }}</span>
                </div>
            </div>

            <!-- Warnings -->
            <div v-if="!hasDisplayNameMapping" class="mb-4 rounded-md bg-yellow-50 border border-yellow-200 p-4">
                <p class="text-sm font-medium text-yellow-800">{{ $t('Name is required for import. Please map a column to "Name".') }}</p>
            </div>
            <div v-if="unmappedRequiredProperties.length > 0" class="mb-4 rounded-md bg-blue-50 border border-blue-200 p-4">
                <p class="text-sm text-blue-800">
                    {{ $t('The following required fields are not mapped:') }}
                    <span class="font-medium">{{ unmappedRequiredProperties.map(p => p.name).join(', ') }}</span>.
                    {{ $t('Contacts will be created without these values.') }}
                </p>
            </div>

            <!-- Mapping Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ $t('Column') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ $t('Preview') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ $t('Map to') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="(header, colIndex) in headers" :key="colIndex">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 whitespace-nowrap">
                                {{ header }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                <div class="flex flex-col gap-0.5">
                                    <span
                                        v-for="(row, ri) in preview.slice(0, 3)"
                                        :key="ri"
                                        class="truncate max-w-xs"
                                        :title="String(row[colIndex] ?? '')"
                                    >
                                        {{ row[colIndex] ?? '-' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 min-w-[280px]">
                                <Listbox as="div" :model-value="columnMapping[colIndex]" @update:model-value="val => columnMapping[colIndex] = val">
                                    <div class="relative">
                                        <ListboxButton class="menu-button bg-white">
                                            <div class="flex items-center gap-2 truncate">
                                                <template v-if="columnMapping[colIndex] === 'display_name'">
                                                    <span class="inline-flex items-center justify-center size-5 rounded bg-indigo-100 text-indigo-700">
                                                        <IconUser class="size-3.5" />
                                                    </span>
                                                    <span>Name (display_name)</span>
                                                </template>
                                                <template v-else-if="columnMapping[colIndex]?.startsWith('prop_')">
                                                    <span
                                                        class="inline-flex items-center justify-center size-5 rounded"
                                                        :class="getPropertyOption(columnMapping[colIndex])?.required ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500'"
                                                    >
                                                        <component :is="getPropertyTypeIcon(getPropertyOption(columnMapping[colIndex])?.propertyType)" class="size-3.5" />
                                                    </span>
                                                    <span>{{ getPropertyOption(columnMapping[colIndex])?.label }}</span>
                                                </template>
                                                <template v-else>
                                                    <span class="text-gray-400">{{ $t('Skip') }}</span>
                                                </template>
                                            </div>
                                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                                <IconChevronDown class="h-5 w-5 text-gray-400" aria-hidden="true" />
                                            </span>
                                        </ListboxButton>

                                        <transition
                                            leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100"
                                            leave-to-class="opacity-0"
                                        >
                                            <ListboxOptions
                                                class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base ring-1 shadow-lg ring-black/5 focus:outline-hidden sm:text-sm"
                                            >
                                                <!-- Skip -->
                                                <ListboxOption as="template" value="" v-slot="{ active, selected: isSelected }">
                                                    <li :class="[active ? 'bg-indigo-600 text-white' : isSelected ? '!bg-artwork-action-buttons/10' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                                        <span :class="[isSelected ? 'font-semibold' : 'font-normal', 'block truncate text-gray-400']">{{ $t('Skip') }}</span>
                                                    </li>
                                                </ListboxOption>

                                                <!-- Name -->
                                                <ListboxOption as="template" value="display_name" :disabled="isOptionTaken('display_name', colIndex)" v-slot="{ active, selected: isSelected, disabled: isDisabled }">
                                                    <li :class="[isDisabled ? 'text-gray-300 cursor-not-allowed' : active ? 'bg-indigo-600 text-white' : isSelected ? '!bg-artwork-action-buttons/10' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                                        <div class="flex items-center gap-2">
                                                            <span class="inline-flex items-center justify-center size-5 rounded" :class="active && !isDisabled ? 'bg-indigo-500 text-white' : 'bg-indigo-100 text-indigo-700'">
                                                                <IconUser class="size-3.5" />
                                                            </span>
                                                            <span :class="[isSelected ? 'font-semibold' : 'font-normal', 'block truncate']">Name (display_name)</span>
                                                        </div>
                                                        <span v-if="isSelected" :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                            <IconCheck class="h-5 w-5" aria-hidden="true" />
                                                        </span>
                                                    </li>
                                                </ListboxOption>

                                                <!-- Separator -->
                                                <li class="border-t border-gray-100 my-1"></li>

                                                <!-- Properties -->
                                                <ListboxOption
                                                    as="template"
                                                    v-for="opt in propertyOptions"
                                                    :key="opt.value"
                                                    :value="opt.value"
                                                    :disabled="isOptionTaken(opt.value, colIndex)"
                                                    v-slot="{ active, selected: isSelected, disabled: isDisabled }"
                                                >
                                                    <li :class="[isDisabled ? 'text-gray-300 cursor-not-allowed' : active ? 'bg-indigo-600 text-white' : isSelected ? '!bg-artwork-action-buttons/10' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                                        <div class="flex items-center gap-2">
                                                            <span
                                                                class="inline-flex items-center justify-center size-5 rounded"
                                                                :class="isDisabled ? 'bg-gray-50 text-gray-300' : active ? 'bg-indigo-500 text-white' : opt.required ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500'"
                                                            >
                                                                <component :is="getPropertyTypeIcon(opt.propertyType)" class="size-3.5" />
                                                            </span>
                                                            <span :class="[isSelected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ opt.label }}</span>
                                                            <span v-if="opt.required && !isDisabled" class="text-xs font-medium ml-auto" :class="active ? 'text-indigo-200' : 'text-amber-500'">
                                                                {{ $t('Required') }}
                                                            </span>
                                                        </div>
                                                        <span v-if="isSelected" :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                            <IconCheck class="h-5 w-5" aria-hidden="true" />
                                                        </span>
                                                    </li>
                                                </ListboxOption>
                                            </ListboxOptions>
                                        </transition>
                                    </div>
                                </Listbox>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Submit -->
            <div class="mt-8 flex justify-end gap-3">
                <button class="ui-button" @click="cancel">
                    {{ $t('Cancel') }}
                </button>
                <button
                    :disabled="!hasDisplayNameMapping || form.processing"
                    class="ui-button-add disabled:opacity-50 disabled:cursor-not-allowed"
                    @click="submit"
                >
                    <span v-if="form.processing" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        {{ $t('Importing') }}...
                    </span>
                    <span v-else>{{ $t('Start import') }}</span>
                </button>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { useTranslation } from '@/Composeables/Translation.js'
import AppLayout from '@/Layouts/AppLayout.vue'
import ToolbarHeader from '@/Artwork/Toolbar/ToolbarHeader.vue'
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
} from '@headlessui/vue'
import {
    IconTableColumn, IconCheck, IconChevronDown, IconUser, IconTypography,
    IconCalendar, IconHash, IconCheckbox, IconList, IconLink, IconAlignLeft,
} from '@tabler/icons-vue'

const props = defineProps({
    contactType: { type: Object, required: true },
    headers: { type: Array, required: true },
    preview: { type: Array, required: true },
    totalRows: { type: Number, required: true },
})

const $t = useTranslation()

// columnMapping: colIndex -> 'display_name' | 'prop_{id}' | ''
const columnMapping = ref({})

const form = useForm({
    mapping: {},
})

// Auto-mapping on mount
onMounted(() => {
    const mapping = {}
    const nameAliases = ['name', 'display_name', 'display name', 'kontaktname', 'contact name', 'bezeichnung']

    props.headers.forEach((header, colIndex) => {
        const normalized = header.toLowerCase().trim()

        // Try display_name match
        if (!Object.values(mapping).includes('display_name') && nameAliases.includes(normalized)) {
            mapping[colIndex] = 'display_name'
            return
        }

        // Try property name match
        for (const prop of props.contactType.properties) {
            const propKey = 'prop_' + prop.id
            if (!Object.values(mapping).includes(propKey) && prop.name.toLowerCase().trim() === normalized) {
                mapping[colIndex] = propKey
                return
            }
        }

        mapping[colIndex] = ''
    })

    columnMapping.value = mapping
})

// Property options for listbox
const propertyOptions = computed(() =>
    (props.contactType.properties ?? []).map(prop => ({
        value: 'prop_' + prop.id,
        label: prop.name,
        propertyType: prop.type,
        required: !!prop.pivot?.is_required,
    }))
)

const getPropertyOption = (value) =>
    propertyOptions.value.find(o => o.value === value)

const isOptionTaken = (optionValue, currentColIndex) => {
    for (const [colIdx, val] of Object.entries(columnMapping.value)) {
        if (parseInt(colIdx) !== currentColIndex && val === optionValue) {
            return true
        }
    }
    return false
}

const propertyTypeIcons = {
    text: IconTypography,
    textarea: IconAlignLeft,
    date: IconCalendar,
    number: IconHash,
    checkbox: IconCheckbox,
    select: IconList,
    link: IconLink,
}

const getPropertyTypeIcon = (type) => propertyTypeIcons[type] ?? IconTypography

const hasDisplayNameMapping = computed(() =>
    Object.values(columnMapping.value).includes('display_name')
)

const unmappedRequiredProperties = computed(() => {
    const mappedPropIds = Object.values(columnMapping.value)
        .filter(v => v.startsWith('prop_'))
        .map(v => parseInt(v.replace('prop_', '')))

    return (props.contactType.properties ?? []).filter(
        p => p.pivot?.is_required && !mappedPropIds.includes(p.id)
    )
})

const submit = () => {
    // Build mapping payload
    const displayNameColIndex = Object.entries(columnMapping.value)
        .find(([, val]) => val === 'display_name')?.[0]

    const properties = {}
    for (const [colIdx, val] of Object.entries(columnMapping.value)) {
        if (val.startsWith('prop_')) {
            const propId = val.replace('prop_', '')
            properties[propId] = parseInt(colIdx)
        }
    }

    form.mapping = {
        display_name: parseInt(displayNameColIndex),
        properties,
    }

    form.post(route('crm.import.execute'))
}

const cancel = () => {
    router.delete(route('crm.import.cancel'))
}
</script>
