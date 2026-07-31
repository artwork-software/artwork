<template>
    <AppLayout :title="$t('Map columns per type')">
        <div class="mt-5 mx-auto container pb-20">
            <ToolbarHeader
                :icon="IconTableColumn"
                :title="$t('Map columns per type')"
                icon-bg-class="bg-indigo-600/10 text-indigo-700"
                :description="$t('{count} rows to import', { count: totalRows })"
            >
                <template #actions>
                    <button class="ui-button" @click="cancel">
                        {{ $t('Cancel') }}
                    </button>
                </template>
            </ToolbarHeader>

            <ImportStepper :steps="['Upload file', 'Map type values', 'Map columns']" :current-step="3" />

            <!-- Where do the artwork fields come from? -->
            <div class="mb-4 rounded-md bg-indigo-50 border border-indigo-100 p-4">
                <p class="text-sm text-indigo-900">
                    {{ $t('The selectable artwork fields per tab are the properties of the property groups assigned to the respective contact type.') }}
                    {{ $t('If a field is missing here, first assign the matching property group to the contact type in the') }}
                    <a :href="route('crm.settings.index')" target="_blank" class="font-medium underline hover:text-indigo-700">{{ $t('CRM Settings') }}</a>
                    {{ $t('and then restart the import.') }}
                </p>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px space-x-4" aria-label="Tabs">
                    <button
                        v-for="type in contactTypes"
                        :key="type.id"
                        @click="activeTab = type.id"
                        :class="[
                            activeTab === type.id
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'flex items-center gap-2 whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium'
                        ]"
                    >
                        <PropertyIcon v-if="type.icon" :name="type.icon" class="size-5 flex-shrink-0" :style="type.color ? { color: type.color } : {}" />
                        <span
                            v-if="type.color"
                            class="inline-block size-2.5 rounded-full flex-shrink-0"
                            :style="{ backgroundColor: type.color }"
                        ></span>
                        {{ $t(type.name) }}
                        <span v-if="!hasValidNameFor(type.id)" class="size-2 rounded-full bg-amber-400"></span>
                        <span v-else class="size-2 rounded-full bg-green-400"></span>
                    </button>
                </nav>
            </div>

            <!-- Tab Content (v-if to avoid HeadlessUI reactivity issues with v-show) -->
            <template v-for="type in contactTypes" :key="type.id">
                <div v-if="activeTab === type.id">
                    <!-- Active type heading -->
                    <div class="mt-6 mb-4 flex items-center gap-3">
                        <PropertyIcon v-if="type.icon" :name="type.icon" class="size-6 flex-shrink-0" :style="type.color ? { color: type.color } : {}" />
                        <h3 class="text-base font-semibold text-gray-900">
                            {{ $t('Mapping for contact type') }} "{{ $t(type.name) }}"
                        </h3>
                    </div>

                    <!-- Warnings -->
                    <div v-if="!hasDisplayNameMappingFor(type.id) && !hasNameFallbackFor(type.id)" class="mb-4 rounded-md bg-yellow-50 border border-yellow-200 p-4">
                        <p class="text-sm font-medium text-yellow-800">{{ $t('Name is required for import. Please map a column to "Name" or map at least first name or last name.') }}</p>
                    </div>
                    <div v-else-if="!hasDisplayNameMappingFor(type.id) && hasNameFallbackFor(type.id)" class="mb-4 rounded-md bg-blue-50 border border-blue-200 p-4">
                        <p class="text-sm text-blue-800">{{ $t('Name will be generated automatically from first name and/or last name.') }}</p>
                    </div>
                    <div v-if="unmappedRequiredPropertiesFor(type.id).length > 0" class="mb-4 rounded-md bg-blue-50 border border-blue-200 p-4">
                        <p class="text-sm text-blue-800">
                            {{ $t('The following required fields are not mapped:') }}
                            <span class="font-medium">{{ unmappedRequiredPropertiesFor(type.id).map(p => p.name).join(', ') }}</span>.
                            {{ $t('Contacts will be created without these values.') }}
                        </p>
                    </div>

                    <!-- Mapping Table -->
                    <div>
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
                                        <span v-if="colIndex === typeColumnIndex" class="ml-1 inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700">
                                            {{ $t('Type column') }}
                                        </span>
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
                                        <Listbox as="div" :model-value="getMappingValue(type.id, colIndex)" @update:model-value="val => setMappingValue(type.id, colIndex, val)">
                                            <div class="relative">
                                                <ListboxButton class="menu-button bg-white">
                                                    <div class="flex items-center gap-2 truncate">
                                                        <template v-if="getMappingValue(type.id, colIndex) === 'display_name'">
                                                            <span class="inline-flex items-center justify-center size-5 rounded bg-indigo-100 text-indigo-700">
                                                                <IconUser class="size-3.5" />
                                                            </span>
                                                            <span>Name (display_name)</span>
                                                        </template>
                                                        <template v-else-if="getMappingValue(type.id, colIndex)?.startsWith?.('prop_')">
                                                            <span
                                                                class="inline-flex items-center justify-center size-5 rounded"
                                                                :class="getPropertyOption(type.id, getMappingValue(type.id, colIndex))?.required ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500'"
                                                            >
                                                                <component :is="getPropertyTypeIcon(getPropertyOption(type.id, getMappingValue(type.id, colIndex))?.propertyType)" class="size-3.5" />
                                                            </span>
                                                            <span>{{ getPropertyOption(type.id, getMappingValue(type.id, colIndex))?.label }}</span>
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
                                                            <ListboxOption as="template" value="display_name" :disabled="isOptionTakenFor(type.id, 'display_name', colIndex)" v-slot="{ active, selected: isSelected, disabled: isDisabled }">
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

                                                            <li class="border-t border-gray-100 my-1"></li>

                                                            <!-- Properties -->
                                                            <ListboxOption
                                                                as="template"
                                                                v-for="opt in propertyOptionsFor(type.id)"
                                                                :key="opt.value"
                                                                :value="opt.value"
                                                                :disabled="isOptionTakenFor(type.id, opt.value, colIndex)"
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
                </div>
            </template>

            <!-- Duplicate detection -->
            <div class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-4 max-w-2xl">
                <ArtworkBaseToggle
                    v-model="dupeEnabled"
                    :label="$t('Detect existing contacts')"
                    :description="$t('Matching is done via the contact name within the respective contact type.')"
                />
                <div v-if="dupeEnabled" class="mt-4">
                    <span class="componentLabel">{{ $t('When a match is found') }}</span>
                    <div class="mt-2 space-y-2">
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="radio" value="skip" v-model="dupeAction" class="text-indigo-600 border-gray-300" />
                            {{ $t('Skip row') }}
                        </label>
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="radio" value="update" v-model="dupeAction" class="text-indigo-600 border-gray-300" />
                            {{ $t('Update existing contact with the values from the file') }}
                        </label>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="mt-8 flex flex-col items-end gap-2">
                <div class="flex gap-3">
                    <button class="ui-button" @click="cancel">
                        {{ $t('Cancel') }}
                    </button>
                    <button
                        :disabled="!canSubmit || form.processing"
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
                <!-- Disabled reason -->
                <p v-if="!canSubmit && invalidTypeNames.length > 0" class="text-sm text-amber-600">
                    {{ $t('Name mapping missing for:') }} {{ invalidTypeNames.join(', ') }}
                </p>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { useTranslation } from '@/Composeables/Translation.js'
import AppLayout from '@/Layouts/AppLayout.vue'
import ToolbarHeader from '@/Artwork/Toolbar/ToolbarHeader.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import ImportStepper from '@/Pages/CRM/Import/Components/ImportStepper.vue'
import ArtworkBaseToggle from '@/Artwork/Toggles/ArtworkBaseToggle.vue'
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
    contactTypes: { type: Array, required: true },
    headers: { type: Array, required: true },
    preview: { type: Array, required: true },
    totalRows: { type: Number, required: true },
    typeColumnIndex: { type: Number, required: true },
    typeValueMapping: { type: Array, required: true },
})

const $t = useTranslation()

const activeTab = ref(props.contactTypes[0]?.id ?? null)

// Per-type column mappings: { typeId: { colIndex: mappingValue } }
const typeMappings = reactive({})

const form = useForm({
    type_mappings: [],
    duplicates: null,
})

const dupeEnabled = ref(false)
const dupeAction = ref('skip')

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

const propertyOptionsFor = (typeId) => {
    const type = props.contactTypes.find(t => t.id === typeId)
    if (!type) return []
    return (type.properties ?? []).map(prop => ({
        value: 'prop_' + prop.id,
        label: prop.name,
        propertyType: prop.type,
        required: !!prop.pivot?.is_required,
    }))
}

const getPropertyOption = (typeId, value) =>
    propertyOptionsFor(typeId).find(o => o.value === value)

const getMappingValue = (typeId, colIndex) =>
    typeMappings[typeId]?.[colIndex] ?? ''

const setMappingValue = (typeId, colIndex, val) => {
    if (!typeMappings[typeId]) {
        typeMappings[typeId] = {}
    }
    typeMappings[typeId][colIndex] = val
}

const isOptionTakenFor = (typeId, optionValue, currentColIndex) => {
    const mapping = typeMappings[typeId] ?? {}
    for (const [colIdx, val] of Object.entries(mapping)) {
        if (parseInt(colIdx) !== currentColIndex && val === optionValue) {
            return true
        }
    }
    return false
}

const firstNameAliases = ['vorname', 'first name', 'first_name', 'firstname']
const lastNameAliases = ['nachname', 'last name', 'last_name', 'lastname', 'familienname', 'surname']

const hasDisplayNameMappingFor = (typeId) => {
    const mapping = typeMappings[typeId] ?? {}
    return Object.values(mapping).includes('display_name')
}

const hasNameFallbackFor = (typeId) => {
    const mapping = typeMappings[typeId] ?? {}
    const mappedPropIds = Object.values(mapping)
        .filter(v => typeof v === 'string' && v.startsWith('prop_'))
        .map(v => parseInt(v.replace('prop_', '')))

    const type = props.contactTypes.find(t => t.id === typeId)
    const mappedProps = (type?.properties ?? []).filter(p => mappedPropIds.includes(p.id))
    const hasFirst = mappedProps.some(p => firstNameAliases.includes(p.name.toLowerCase().trim()))
    const hasLast = mappedProps.some(p => lastNameAliases.includes(p.name.toLowerCase().trim()))
    return hasFirst || hasLast
}

const hasValidNameFor = (typeId) => hasDisplayNameMappingFor(typeId) || hasNameFallbackFor(typeId)

const unmappedRequiredPropertiesFor = (typeId) => {
    const mapping = typeMappings[typeId] ?? {}
    const mappedPropIds = Object.values(mapping)
        .filter(v => typeof v === 'string' && v.startsWith('prop_'))
        .map(v => parseInt(v.replace('prop_', '')))

    const type = props.contactTypes.find(t => t.id === typeId)
    return (type?.properties ?? []).filter(
        p => p.pivot?.is_required && !mappedPropIds.includes(p.id)
    )
}

const canSubmit = computed(() => {
    return props.contactTypes.every(type => hasValidNameFor(type.id))
})

const invalidTypeNames = computed(() => {
    return props.contactTypes
        .filter(type => !hasValidNameFor(type.id))
        .map(type => $t(type.name))
})

// Auto-mapping on mount
onMounted(() => {
    const nameAliases = ['name', 'display_name', 'display name', 'kontaktname', 'contact name', 'bezeichnung']

    // Step 1: Auto-map each type individually
    props.contactTypes.forEach(type => {
        const mapping = {}

        props.headers.forEach((header, colIndex) => {
            const normalized = header.toLowerCase().trim()

            // Skip the type column itself
            if (colIndex === props.typeColumnIndex) {
                mapping[colIndex] = ''
                return
            }

            // Try display_name match
            if (!Object.values(mapping).includes('display_name') && nameAliases.includes(normalized)) {
                mapping[colIndex] = 'display_name'
                return
            }

            // Try property name match
            for (const prop of (type.properties ?? [])) {
                const propKey = 'prop_' + prop.id
                if (!Object.values(mapping).includes(propKey) && prop.name.toLowerCase().trim() === normalized) {
                    mapping[colIndex] = propKey
                    return
                }
            }

            mapping[colIndex] = ''
        })

        typeMappings[type.id] = mapping
    })

    // Step 2: Cross-type fill
    if (props.contactTypes.length > 1) {
        const firstType = props.contactTypes[0]
        const firstMapping = typeMappings[firstType.id] ?? {}

        props.contactTypes.slice(1).forEach(type => {
            const currentMapping = typeMappings[type.id]

            props.headers.forEach((header, colIndex) => {
                // Skip if already mapped or if it's the type column
                if (currentMapping[colIndex] && currentMapping[colIndex] !== '') return
                if (colIndex === props.typeColumnIndex) return

                const firstVal = firstMapping[colIndex]
                if (!firstVal || firstVal === '') return

                // Cross-fill display_name
                if (firstVal === 'display_name' && !Object.values(currentMapping).includes('display_name')) {
                    currentMapping[colIndex] = 'display_name'
                    return
                }

                // Cross-fill properties by name match
                if (firstVal.startsWith('prop_')) {
                    const firstPropId = parseInt(firstVal.replace('prop_', ''))
                    const firstProp = (firstType.properties ?? []).find(p => p.id === firstPropId)
                    if (!firstProp) return

                    const matchingProp = (type.properties ?? []).find(
                        p => p.name.toLowerCase().trim() === firstProp.name.toLowerCase().trim()
                    )

                    if (matchingProp) {
                        const targetKey = 'prop_' + matchingProp.id
                        if (!Object.values(currentMapping).includes(targetKey)) {
                            currentMapping[colIndex] = targetKey
                        }
                    }
                }
            })
        })
    }
})

const submit = () => {
    const mappings = props.contactTypes.map(type => {
        const mapping = typeMappings[type.id] ?? {}

        const displayNameColIndex = Object.entries(mapping)
            .find(([, val]) => val === 'display_name')?.[0]

        const properties = {}
        for (const [colIdx, val] of Object.entries(mapping)) {
            if (typeof val === 'string' && val.startsWith('prop_')) {
                const propId = val.replace('prop_', '')
                properties[propId] = parseInt(colIdx)
            }
        }

        return {
            crm_contact_type_id: type.id,
            display_name: displayNameColIndex !== undefined ? parseInt(displayNameColIndex) : null,
            properties,
        }
    })

    form.type_mappings = mappings
    form.duplicates = dupeEnabled.value
        ? { enabled: true, match_by: 'display_name', action: dupeAction.value }
        : null
    form.post(route('crm.import.execute'))
}

const cancel = () => {
    router.delete(route('crm.import.cancel'))
}
</script>
