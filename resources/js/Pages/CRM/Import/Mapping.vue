<template>
    <AppLayout :title="$t('Map columns')">
        <div class="mt-5 mx-auto container pb-20">
            <ToolbarHeader
                :icon="IconTableColumn"
                :title="$t('Map columns')"
                icon-bg-class="bg-accent-50 text-accent-700"
                :description="$t('{count} rows to import', { count: totalRows })"
            >
                <template #actions>
                    <BaseUIButton hide-icon @click="cancel">
                        {{ $t('Cancel') }}
                    </BaseUIButton>
                </template>
            </ToolbarHeader>

            <ImportStepper :steps="['Upload file', 'Map columns']" :current-step="2" />

            <!-- Where do the artwork fields come from? -->
            <div class="mb-4 rounded-md bg-accent-50 border border-accent-200 p-4">
                <p class="text-sm text-accent-700">
                    {{ $t('The selectable artwork fields are the properties of the property groups assigned to the contact type "{type}".', { type: $t(contactType.name) }) }}
                    {{ $t('If a field is missing here, first assign the matching property group to the contact type in the') }}
                    <a :href="route('crm.settings.index')" target="_blank" class="font-medium underline hover:text-accent-700">{{ $t('CRM Settings') }}</a>
                    {{ $t('and then restart the import.') }}
                </p>
            </div>

            <!-- Warnings -->
            <div v-if="!hasDisplayNameMapping && !hasNameFallback" class="mb-4 rounded-md bg-warning-surface border border-warning-border p-4">
                <p class="text-sm font-medium text-warning">{{ $t('Name is required for import. Please map a column to "Name" or map at least first name or last name.') }}</p>
            </div>
            <div v-else-if="!hasDisplayNameMapping && hasNameFallback" class="mb-4 rounded-md bg-accent-50 border border-accent-200 p-4">
                <p class="text-sm text-accent-700">{{ $t('Name will be generated automatically from first name and/or last name.') }}</p>
            </div>
            <div v-if="unmappedRequiredProperties.length > 0" class="mb-4 rounded-md bg-accent-50 border border-accent-200 p-4">
                <p class="text-sm text-accent-700">
                    {{ $t('The following required fields are not mapped:') }}
                    <span class="font-medium">{{ unmappedRequiredProperties.map(p => p.name).join(', ') }}</span>.
                    {{ $t('Contacts will be created without these values.') }}
                </p>
            </div>

            <!-- Mapping Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border-subtle">
                    <thead class="bg-surface-sunken">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Column') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Preview') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Map to') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-border-subtle">
                        <tr v-for="(header, colIndex) in headers" :key="colIndex">
                            <td class="px-4 py-3 text-sm font-medium text-text whitespace-nowrap">
                                {{ header }}
                            </td>
                            <td class="px-4 py-3 text-sm text-text-subtle">
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
                                                    <span class="inline-flex items-center justify-center size-5 rounded bg-accent-100 text-accent-700">
                                                        <IconUser class="size-3.5" />
                                                    </span>
                                                    <span>Name (display_name)</span>
                                                </template>
                                                <template v-else-if="columnMapping[colIndex]?.startsWith('prop_')">
                                                    <span
                                                        class="inline-flex items-center justify-center size-5 rounded"
                                                        :class="getPropertyOption(columnMapping[colIndex])?.required ? 'bg-warning-surface text-warning' : 'bg-surface-sunken text-text-subtle'"
                                                    >
                                                        <component :is="getPropertyTypeIcon(getPropertyOption(columnMapping[colIndex])?.propertyType)" class="size-3.5" />
                                                    </span>
                                                    <span>{{ getPropertyOption(columnMapping[colIndex])?.label }}</span>
                                                </template>
                                                <template v-else>
                                                    <span class="text-text-subtle">{{ $t('Skip') }}</span>
                                                </template>
                                            </div>
                                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                                <IconChevronDown class="h-5 w-5 text-text-subtle" aria-hidden="true" />
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
                                                    <li :class="[active ? 'bg-accent-600 text-white' : isSelected ? '!bg-accent-600/10' : 'text-text', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                                        <span :class="[isSelected ? 'font-semibold' : 'font-normal', 'block truncate text-text-subtle']">{{ $t('Skip') }}</span>
                                                    </li>
                                                </ListboxOption>

                                                <!-- Name -->
                                                <ListboxOption as="template" value="display_name" :disabled="isOptionTaken('display_name', colIndex)" v-slot="{ active, selected: isSelected, disabled: isDisabled }">
                                                    <li :class="[isDisabled ? 'text-text-subtle cursor-not-allowed' : active ? 'bg-accent-600 text-white' : isSelected ? '!bg-accent-600/10' : 'text-text', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                                        <div class="flex items-center gap-2">
                                                            <span class="inline-flex items-center justify-center size-5 rounded" :class="active && !isDisabled ? 'bg-accent-600 text-white' : 'bg-accent-100 text-accent-700'">
                                                                <IconUser class="size-3.5" />
                                                            </span>
                                                            <span :class="[isSelected ? 'font-semibold' : 'font-normal', 'block truncate']">Name (display_name)</span>
                                                        </div>
                                                        <span v-if="isSelected" :class="[active ? 'text-white' : 'text-accent-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                            <IconCheck class="h-5 w-5" aria-hidden="true" />
                                                        </span>
                                                    </li>
                                                </ListboxOption>

                                                <!-- Separator -->
                                                <li class="border-t border-border-subtle my-1"></li>

                                                <!-- Properties -->
                                                <ListboxOption
                                                    as="template"
                                                    v-for="opt in propertyOptions"
                                                    :key="opt.value"
                                                    :value="opt.value"
                                                    :disabled="isOptionTaken(opt.value, colIndex)"
                                                    v-slot="{ active, selected: isSelected, disabled: isDisabled }"
                                                >
                                                    <li :class="[isDisabled ? 'text-text-subtle cursor-not-allowed' : active ? 'bg-accent-600 text-white' : isSelected ? '!bg-accent-600/10' : 'text-text', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                                        <div class="flex items-center gap-2">
                                                            <span
                                                                class="inline-flex items-center justify-center size-5 rounded"
                                                                :class="isDisabled ? 'bg-surface-sunken text-text-subtle' : active ? 'bg-accent-600 text-white' : opt.required ? 'bg-warning-surface text-warning' : 'bg-surface-sunken text-text-subtle'"
                                                            >
                                                                <component :is="getPropertyTypeIcon(opt.propertyType)" class="size-3.5" />
                                                            </span>
                                                            <span :class="[isSelected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ opt.label }}</span>
                                                            <span v-if="opt.required && !isDisabled" class="text-xs font-medium ml-auto" :class="active ? 'text-accent-200' : 'text-warning'">
                                                                {{ $t('Required') }}
                                                            </span>
                                                        </div>
                                                        <span v-if="isSelected" :class="[active ? 'text-white' : 'text-accent-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
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

            <!-- Duplicate detection -->
            <div class="mt-6 rounded-lg border border-border-subtle bg-surface-sunken p-4 max-w-2xl">
                <ArtworkBaseToggle
                    v-model="dupeEnabled"
                    :label="$t('Detect existing contacts')"
                    :description="$t('Rows that match an existing contact are skipped or update it instead of creating a duplicate.')"
                />
                <div v-if="dupeEnabled" class="mt-4 grid gap-4 sm:grid-cols-2">
                    <ArtworkBaseListbox
                        v-model="dupeMatchOption"
                        :items="dupeMatchOptions"
                        by="value"
                        option-label="label"
                        option-key="value"
                        :label="$t('Match by')"
                        :enable-search="false"
                    />
                    <div>
                        <span class="componentLabel">{{ $t('When a match is found') }}</span>
                        <div class="mt-2 space-y-2">
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="radio" value="skip" v-model="dupeAction" class="text-accent-600 border-border" />
                                {{ $t('Skip row') }}
                            </label>
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="radio" value="update" v-model="dupeAction" class="text-accent-600 border-border" />
                                {{ $t('Update existing contact with the values from the file') }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="mt-8 flex justify-end gap-3">
                <BaseUIButton hide-icon @click="cancel">
                    {{ $t('Cancel') }}
                </BaseUIButton>
                <BaseUIButton
                    :disabled="!canSubmit || form.processing"
                    variant="primary"
                    hide-icon
                    class="disabled:bg-surface-canvas disabled:border-border-subtle disabled:text-text-subtle disabled:cursor-not-allowed"
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
                </BaseUIButton>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { useTranslation } from '@/Composeables/Translation.js'
import AppLayout from '@/Layouts/AppLayout.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import ToolbarHeader from '@/Artwork/Toolbar/ToolbarHeader.vue'
import ImportStepper from '@/Pages/CRM/Import/Components/ImportStepper.vue'
import ArtworkBaseToggle from '@/Artwork/Toggles/ArtworkBaseToggle.vue'
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue'
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
    duplicates: null,
})

// -- Duplicate detection --
const dupeEnabled = ref(false)
const dupeMatchOption = ref(null)
const dupeAction = ref('skip')

// Abgleich ist nur über den Namen oder eine tatsächlich zugeordnete Eigenschaft möglich
const dupeMatchOptions = computed(() => {
    const options = [{ value: 'display_name', label: 'Name' }]
    for (const val of Object.values(columnMapping.value)) {
        if (typeof val === 'string' && val.startsWith('prop_')) {
            const opt = propertyOptions.value.find(o => o.value === val)
            if (opt) options.push({ value: opt.value, label: opt.label })
        }
    }
    return options
})

watch(dupeMatchOptions, (options) => {
    if (!dupeMatchOption.value || !options.some(o => o.value === dupeMatchOption.value.value)) {
        dupeMatchOption.value = options[0]
    }
}, { immediate: true })

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

const firstNameAliases = ['vorname', 'first name', 'first_name', 'firstname']
const lastNameAliases = ['nachname', 'last name', 'last_name', 'lastname', 'familienname', 'surname']

const hasDisplayNameMapping = computed(() =>
    Object.values(columnMapping.value).includes('display_name')
)

const hasNameFallback = computed(() => {
    const mappedPropIds = Object.values(columnMapping.value)
        .filter(v => v.startsWith('prop_'))
        .map(v => parseInt(v.replace('prop_', '')))

    const mappedProps = (props.contactType.properties ?? []).filter(p => mappedPropIds.includes(p.id))
    const hasFirst = mappedProps.some(p => firstNameAliases.includes(p.name.toLowerCase().trim()))
    const hasLast = mappedProps.some(p => lastNameAliases.includes(p.name.toLowerCase().trim()))
    return hasFirst || hasLast
})

const canSubmit = computed(() => hasDisplayNameMapping.value || hasNameFallback.value)

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
        display_name: displayNameColIndex !== undefined ? parseInt(displayNameColIndex) : null,
        properties,
    }

    form.duplicates = dupeEnabled.value
        ? {
            enabled: true,
            match_by: dupeMatchOption.value?.value ?? 'display_name',
            action: dupeAction.value,
        }
        : null

    form.post(route('crm.import.execute'))
}

const cancel = () => {
    router.delete(route('crm.import.cancel'))
}
</script>
