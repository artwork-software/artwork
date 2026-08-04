<template>
    <AppLayout :title="$t('Map type values')">
        <div class="mt-5 mx-auto container pb-20">
            <ToolbarHeader
                :icon="IconCategory"
                :title="$t('Map type values')"
                icon-bg-class="bg-accent-50 text-accent-700"
                :description="$t('{count} rows to import', { count: totalRows })"
            >
                <template #actions>
                    <button class="ui-button" @click="cancel">
                        {{ $t('Cancel') }}
                    </button>
                </template>
            </ToolbarHeader>

            <ImportStepper :steps="['Upload file', 'Map type values', 'Map columns']" :current-step="2" />

            <!-- Type Column Selection -->
            <div class="max-w-md mb-8">
                <ArtworkBaseListbox
                    :model-value="selectedColumn"
                    @update:model-value="onColumnChange"
                    :items="headerOptions"
                    by="value"
                    option-label="label"
                    :label="$t('Select type column')"
                    :placeholder="$t('Select type column')"
                    :enable-search="false"
                />
            </div>

            <!-- Loading -->
            <div v-if="loadingValues" class="flex items-center gap-2 text-sm text-text-subtle mb-6">
                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                {{ $t('Loading') }}...
            </div>

            <!-- Value Mapping Table -->
            <div v-if="uniqueValues.length > 0 && !loadingValues" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border-subtle">
                    <thead class="bg-surface-sunken">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Value in file') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Rows') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Assign to contact type') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-border-subtle">
                        <tr v-for="(entry, idx) in uniqueValues" :key="entry.value">
                            <td class="px-4 py-3 text-sm font-medium text-text whitespace-nowrap">
                                {{ entry.value }}
                            </td>
                            <td class="px-4 py-3 text-sm text-text-subtle">
                                {{ entry.count }}
                            </td>
                            <td class="px-4 py-3 min-w-[280px]">
                                <Listbox as="div" :model-value="valueMapping[idx]" @update:model-value="val => valueMapping[idx] = val">
                                    <div class="relative">
                                        <ListboxButton class="menu-button bg-white">
                                            <div class="flex items-center gap-2 truncate">
                                                <template v-if="valueMapping[idx]">
                                                    <PropertyIcon v-if="getContactType(valueMapping[idx])?.icon" :name="getContactType(valueMapping[idx]).icon" class="size-5 flex-shrink-0" :style="getContactType(valueMapping[idx])?.color ? { color: getContactType(valueMapping[idx]).color } : {}" />
                                                    <span
                                                        v-if="getContactType(valueMapping[idx])?.color"
                                                        class="inline-block size-2.5 rounded-full flex-shrink-0"
                                                        :style="{ backgroundColor: getContactType(valueMapping[idx]).color }"
                                                    ></span>
                                                    <span>{{ $t(getContactType(valueMapping[idx])?.name ?? '') }}</span>
                                                </template>
                                                <template v-else>
                                                    <span class="text-text-subtle">{{ $t('Skip these rows') }}</span>
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
                                                <!-- Skip option -->
                                                <ListboxOption as="template" :value="null" v-slot="{ active, selected: isSelected }">
                                                    <li :class="[active ? 'bg-accent-600 text-white' : isSelected ? '!bg-artwork-action-buttons/10' : 'text-text', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                                        <span :class="[isSelected ? 'font-semibold' : 'font-normal', 'block truncate text-text-subtle']">{{ $t('Skip these rows') }}</span>
                                                    </li>
                                                </ListboxOption>

                                                <li class="border-t border-border-subtle my-1"></li>

                                                <!-- Contact types -->
                                                <ListboxOption
                                                    as="template"
                                                    v-for="type in contactTypes"
                                                    :key="type.id"
                                                    :value="type.id"
                                                    v-slot="{ active, selected: isSelected }"
                                                >
                                                    <li :class="[active ? 'bg-accent-600 text-white' : isSelected ? '!bg-artwork-action-buttons/10' : 'text-text', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                                        <div class="flex items-center gap-2">
                                                            <PropertyIcon v-if="type.icon" :name="type.icon" class="size-5 flex-shrink-0" :style="type.color ? { color: active ? 'white' : type.color } : {}" />
                                                            <span
                                                                v-if="type.color"
                                                                class="inline-block size-2.5 rounded-full flex-shrink-0"
                                                                :style="{ backgroundColor: active ? 'white' : type.color }"
                                                            ></span>
                                                            <span :class="[isSelected ? 'font-semibold' : 'font-normal', 'block truncate']">
                                                                {{ $t(type.name) }}
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

            <!-- No column selected info -->
            <div v-if="!selectedColumn && !loadingValues" class="text-sm text-text-subtle bg-surface-sunken rounded-md p-6 text-center">
                {{ $t('Select a column above to see the unique values.') }}
            </div>

            <!-- Missing contact type hint -->
            <div v-if="selectedColumn && !loadingValues" class="mt-4 text-xs text-text-subtle">
                {{ $t('Contact type missing?') }}
                <a :href="route('crm.settings.index')" target="_blank" class="font-medium text-accent-600 hover:text-accent-600">
                    {{ $t('Create it in the CRM settings (opens in a new tab)') }}
                </a>
                — {{ $t('then restart the import so the new type appears here.') }}
            </div>

            <!-- Submit -->
            <div class="mt-8 flex justify-end gap-3">
                <button class="ui-button" @click="cancel">
                    {{ $t('Cancel') }}
                </button>
                <button
                    :disabled="!canSubmit || form.processing"
                    class="ui-button-add disabled:bg-surface-canvas disabled:border-border-subtle disabled:text-text-subtle disabled:cursor-not-allowed"
                    @click="submit"
                >
                    <span v-if="form.processing" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        {{ $t('Loading') }}...
                    </span>
                    <span v-else>{{ $t('Next: Map columns') }}</span>
                </button>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { useTranslation } from '@/Composeables/Translation.js'
import AppLayout from '@/Layouts/AppLayout.vue'
import ToolbarHeader from '@/Artwork/Toolbar/ToolbarHeader.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue'
import ImportStepper from '@/Pages/CRM/Import/Components/ImportStepper.vue'
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
} from '@headlessui/vue'
import { IconCategory, IconCheck, IconChevronDown } from '@tabler/icons-vue'
import axios from 'axios'

const props = defineProps({
    contactTypes: { type: Array, required: true },
    headers: { type: Array, required: true },
    preview: { type: Array, required: true },
    totalRows: { type: Number, required: true },
})

const $t = useTranslation()

const selectedColumn = ref(null)
const uniqueValues = ref([])
const valueMapping = ref([])
const loadingValues = ref(false)

const headerOptions = computed(() =>
    props.headers.map((h, i) => ({ value: i, label: h }))
)

const form = useForm({
    type_column_index: null,
    type_value_mapping: [],
})

const getContactType = (id) => props.contactTypes.find(t => t.id === id)

const onColumnChange = async (item) => {
    selectedColumn.value = item

    if (!item) {
        uniqueValues.value = []
        valueMapping.value = []
        return
    }

    const colIndex = item.value

    loadingValues.value = true
    try {
        const response = await axios.get(route('crm.import.column-values', { columnIndex: colIndex }), {
            headers: { 'Accept': 'application/json' },
        })
        uniqueValues.value = response.data.values
        // Auto-map: try to match value to contact type name
        valueMapping.value = uniqueValues.value.map(entry => {
            const match = props.contactTypes.find(
                t => t.name.toLowerCase().trim() === entry.value.toLowerCase().trim()
            )
            return match ? match.id : null
        })
    } catch {
        uniqueValues.value = []
        valueMapping.value = []
    } finally {
        loadingValues.value = false
    }
}

const canSubmit = computed(() => {
    if (!selectedColumn.value) return false
    if (uniqueValues.value.length === 0) return false
    // At least one value must be assigned to a contact type (not skipped)
    return valueMapping.value.some(v => v !== null && v !== undefined)
})

const submit = () => {
    form.type_column_index = selectedColumn.value.value
    form.type_value_mapping = uniqueValues.value.map((entry, idx) => ({
        type_value: entry.value,
        crm_contact_type_id: valueMapping.value[idx] || null,
    }))

    form.post(route('crm.import.map-types'))
}

const cancel = () => {
    router.delete(route('crm.import.cancel'))
}
</script>
