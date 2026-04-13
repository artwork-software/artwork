<template>
    <ArtworkBaseModal
        modal-size="max-w-3xl"
        :title="$t('Export contacts')"
        :description="$t('Select columns and filters for Excel export.')"
        @close="$emit('close')"
        full-modal
    >
        <div class="space-y-6 mt-4">
            <!-- Columns Selection -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-medium text-gray-900">{{ $t('Columns') }}</h3>
                    <div class="flex gap-2">
                        <button type="button" class="text-xs text-indigo-600 hover:text-indigo-800" @click="selectAllColumns">
                            {{ $t('Select all') }}
                        </button>
                        <span class="text-gray-300">|</span>
                        <button type="button" class="text-xs text-indigo-600 hover:text-indigo-800" @click="deselectAllColumns">
                            {{ $t('Deselect all') }}
                        </button>
                    </div>
                </div>

                <!-- Base columns -->
                <div class="flex flex-wrap gap-3 mb-3">
                    <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                        <input type="checkbox" value="display_name" v-model="selectedColumns" class="rounded border-gray-300 text-indigo-600 h-4 w-4" />
                        Name
                    </label>
                    <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                        <input type="checkbox" value="contact_type" v-model="selectedColumns" class="rounded border-gray-300 text-indigo-600 h-4 w-4" />
                        {{ $t('Contact type') }}
                    </label>
                    <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                        <input type="checkbox" value="created_at" v-model="selectedColumns" class="rounded border-gray-300 text-indigo-600 h-4 w-4" />
                        {{ $t('Created at') }}
                    </label>
                </div>

                <!-- Property columns -->
                <div v-if="allProperties.length" class="border-t border-gray-100 pt-3">
                    <p class="text-xs text-gray-500 mb-2">{{ $t('Properties') }}</p>
                    <div class="flex flex-wrap gap-3">
                        <label
                            v-for="prop in allProperties"
                            :key="prop.id"
                            class="flex items-center gap-1.5 text-sm cursor-pointer"
                        >
                            <input
                                type="checkbox"
                                :value="'prop_' + prop.id"
                                v-model="selectedColumns"
                                class="rounded border-gray-300 text-indigo-600 h-4 w-4"
                            />
                            {{ prop.name }}
                        </label>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="border-t border-gray-200 pt-4">
                <h3 class="text-sm font-medium text-gray-900 mb-3">{{ $t('Filter') }}</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Contact Type Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('Contact type') }}</label>
                        <div class="flex flex-wrap gap-2">
                            <label
                                v-for="type in contactTypes"
                                :key="type.id"
                                class="flex items-center gap-1.5 text-sm cursor-pointer"
                            >
                                <input
                                    type="checkbox"
                                    :value="type.id"
                                    v-model="filterContactTypeIds"
                                    class="rounded border-gray-300 text-indigo-600 h-4 w-4"
                                />
                                <span
                                    v-if="type.color"
                                    class="inline-block size-2.5 rounded-full flex-shrink-0"
                                    :style="{ backgroundColor: type.color }"
                                ></span>
                                {{ $t(type.name) }}
                            </label>
                        </div>
                    </div>

                    <!-- Date Range Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('Time period') }}</label>
                        <div class="flex items-center gap-2">
                            <BaseInput type="date" id="export_date_from" v-model="filterDateFrom" :label="$t('From')" />
                            <BaseInput type="date" id="export_date_to" v-model="filterDateTo" :label="$t('To')" />
                        </div>
                    </div>

                    <!-- Project Filter -->
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('Projects') }}</label>
                        <div class="relative">
                            <input
                                type="text"
                                v-model="projectSearch"
                                @input="searchProjects"
                                :placeholder="$t('Search projects...')"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                            <!-- Search Results Dropdown -->
                            <div
                                v-if="projectResults.length && projectSearch"
                                class="absolute z-10 mt-1 max-h-40 w-full overflow-auto rounded-md bg-white py-1 text-sm ring-1 shadow-lg ring-black/5"
                            >
                                <button
                                    v-for="project in projectResults"
                                    :key="project.id"
                                    type="button"
                                    class="block w-full text-left px-3 py-2 hover:bg-indigo-50"
                                    @click="addProject(project)"
                                >
                                    {{ project.name }}
                                </button>
                            </div>
                        </div>
                        <!-- Selected Projects -->
                        <div v-if="selectedProjects.length" class="mt-2 flex flex-wrap gap-2">
                            <span
                                v-for="project in selectedProjects"
                                :key="project.id"
                                class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700 border border-indigo-200"
                            >
                                {{ project.name }}
                                <button type="button" @click="removeProject(project.id)" class="hover:text-red-600">
                                    <XIcon class="size-3.5" />
                                </button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="py-4">
            <div class="flex items-center justify-between">
                <button type="button" class="text-sm text-gray-500 hover:text-gray-700" @click="resetAll">
                    {{ $t('Reset') }}
                </button>
                <div class="flex items-center gap-3">
                    <button type="button" class="ui-button" @click="$emit('close')">
                        {{ $t('Cancel') }}
                    </button>
                    <button
                        type="button"
                        class="ui-button-add"
                        :disabled="selectedColumns.length === 0 || exporting"
                        @click="doExport"
                    >
                        <span v-if="exporting" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            {{ $t('Exporting') }}...
                        </span>
                        <span v-else>{{ $t('Export Excel') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useTranslation } from '@/Composeables/Translation.js'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import { XIcon } from '@heroicons/vue/outline'
import axios from 'axios'
import debounce from 'lodash.debounce'

const props = defineProps({
    contactTypes: { type: Array, required: true },
})

const emit = defineEmits(['close'])

const $t = useTranslation()

// Columns
const selectedColumns = ref(['display_name', 'contact_type'])

const allProperties = computed(() => {
    const seen = new Set()
    const result = []
    for (const type of props.contactTypes) {
        for (const prop of (type.properties ?? [])) {
            if (!seen.has(prop.id) && prop.type !== 'upload') {
                seen.add(prop.id)
                result.push(prop)
            }
        }
    }
    return result
})

const selectAllColumns = () => {
    selectedColumns.value = [
        'display_name',
        'contact_type',
        'created_at',
        ...allProperties.value.map(p => 'prop_' + p.id),
    ]
}

const deselectAllColumns = () => {
    selectedColumns.value = []
}

// Filters
const filterContactTypeIds = ref([])
const filterDateFrom = ref('')
const filterDateTo = ref('')

// Project search
const projectSearch = ref('')
const projectResults = ref([])
const selectedProjects = ref([])

const searchProjects = debounce(async () => {
    if (!projectSearch.value || projectSearch.value.length < 2) {
        projectResults.value = []
        return
    }
    try {
        const { data } = await axios.get(route('projects.search'), {
            params: { search: projectSearch.value },
        })
        // Filter out already selected
        const selectedIds = new Set(selectedProjects.value.map(p => p.id))
        projectResults.value = (data ?? []).filter(p => !selectedIds.has(p.id)).slice(0, 10)
    } catch {
        projectResults.value = []
    }
}, 300)

const addProject = (project) => {
    selectedProjects.value.push({ id: project.id, name: project.name })
    projectSearch.value = ''
    projectResults.value = []
}

const removeProject = (id) => {
    selectedProjects.value = selectedProjects.value.filter(p => p.id !== id)
}

const resetAll = () => {
    selectedColumns.value = ['display_name', 'contact_type']
    filterContactTypeIds.value = []
    filterDateFrom.value = ''
    filterDateTo.value = ''
    selectedProjects.value = []
    projectSearch.value = ''
}

// Export
const exporting = ref(false)

const doExport = async () => {
    exporting.value = true
    try {
        const response = await axios.post(route('crm.export'), {
            columns: selectedColumns.value,
            contact_type_ids: filterContactTypeIds.value.length ? filterContactTypeIds.value : null,
            project_ids: selectedProjects.value.length ? selectedProjects.value.map(p => p.id) : null,
            date_from: filterDateFrom.value || null,
            date_to: filterDateTo.value || null,
        }, {
            responseType: 'blob',
        })

        // Download file
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        const filename = response.headers['content-disposition']
            ?.match(/filename="?(.+)"?/)?.[1]
            ?? 'crm-export.xlsx'
        link.setAttribute('download', filename)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)

        emit('close')
    } catch (e) {
        console.error('Export failed', e)
    } finally {
        exporting.value = false
    }
}
</script>
