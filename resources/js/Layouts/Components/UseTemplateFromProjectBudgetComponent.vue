<template>
    <ArtworkBaseModal
        title="Read from project"
        description="To make your work easier, use an existing calculation from another project."
        modal-size="sm:max-w-lg"
        @close="closeModal(false)"
    >
        <div v-if="selectedProject" class="space-y-1">
            <span class="mb-1 block font-lexend text-xs font-medium text-[#3F424A]">{{ $t('Selected project') }}</span>
            <div class="flex items-center justify-between gap-x-3 rounded-lg border border-border bg-surface-sunken px-3 py-2.5">
                <div class="min-w-0">
                    <div class="truncate text-sm font-semibold text-text">{{ selectedProject.name }}</div>
                    <div v-if="projectMeta(selectedProject)" class="truncate text-xs text-text-muted">
                        {{ projectMeta(selectedProject) }}
                    </div>
                </div>
                <BaseUIButton
                    label="Change"
                    variant="ghost"
                    size="sm"
                    hide-icon
                    :disabled="processing"
                    @click="resetSelection"
                />
            </div>
        </div>

        <div v-else>
            <BaseInput
                id="budgetTemplateProjectSearch"
                v-model="projectQuery"
                label="Search project"
                :placeholder="$t('Search projects...')"
                :show-loading="searching"
                autocomplete="off"
                autofocus
            />
            <ul
                v-if="searchResults.length > 0"
                class="mt-2 max-h-60 divide-y divide-border-subtle overflow-y-auto rounded-lg border border-border bg-surface"
            >
                <li v-for="project in searchResults" :key="project.id">
                    <button
                        type="button"
                        class="block w-full cursor-pointer px-3 py-2 text-left transition-colors duration-150 hover:bg-surface-hover focus-visible:bg-surface-hover motion-reduce:transition-none"
                        @click="selectProject(project)"
                    >
                        <span class="block truncate text-sm font-medium text-text">{{ project.name }}</span>
                        <span v-if="projectMeta(project)" class="block truncate text-xs text-text-muted">
                            {{ projectMeta(project) }}
                        </span>
                    </button>
                </li>
            </ul>
            <p
                v-else-if="hasSearched && !searching"
                class="mt-2 rounded-lg border border-dashed border-border px-3 py-4 text-center text-xs text-text-muted"
            >
                {{ $t('No projects found') }}
            </p>
        </div>

        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="closeModal(false)"
            />
            <BaseUIButton
                label="Import calculation"
                variant="primary"
                icon="IconFileImport"
                :processing="processing"
                :disabled="!selectedProject"
                @click="useProjectBudgetAsTemplate"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {onBeforeUnmount, ref, watch} from 'vue'
import axios from 'axios'
import debounce from 'lodash.debounce'
import {router} from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

defineOptions({name: 'UseTemplateFromProjectBudgetComponent'})

const props = defineProps({
    projectId: {type: Number, default: null},
})

const emit = defineEmits(['closed'])

const projectQuery = ref('')
const searchResults = ref([])
const selectedProject = ref(null)
const searching = ref(false)
const hasSearched = ref(false)
const processing = ref(false)

let latestSearchId = 0

const searchProjects = debounce(async (query) => {
    const searchId = ++latestSearchId
    try {
        const {data} = await axios.get(route('projects.search'), {params: {query}})
        if (searchId !== latestSearchId) {
            return
        }
        // Das eigene Projekt kann nicht als Quelle dienen.
        searchResults.value = (data ?? []).filter((project) => project.id !== props.projectId)
    } catch {
        if (searchId === latestSearchId) {
            searchResults.value = []
        }
    } finally {
        if (searchId === latestSearchId) {
            searching.value = false
            hasSearched.value = true
        }
    }
}, 300)

watch(projectQuery, (query) => {
    const trimmedQuery = query.trim()
    if (trimmedQuery === '') {
        latestSearchId++
        searchProjects.cancel()
        searchResults.value = []
        searching.value = false
        hasSearched.value = false
        return
    }
    searching.value = true
    searchProjects(trimmedQuery)
})

onBeforeUnmount(() => searchProjects.cancel())

/** Der Server liefert „d.m.Y H:i“; für die Trefferliste reicht das Datum. */
function formatDate(value) {
    return value ? String(value).split(' ')[0] : ''
}

function projectMeta(project) {
    const period = [formatDate(project.first_event_date), formatDate(project.last_event_date)]
        .filter(Boolean)
        .join(' - ')
    return [project.artists, period].filter(Boolean).join(' · ')
}

function selectProject(project) {
    selectedProject.value = project
}

function resetSelection() {
    selectedProject.value = null
}

function closeModal(imported) {
    emit('closed', imported)
}

function useProjectBudgetAsTemplate() {
    if (!selectedProject.value || processing.value) {
        return
    }
    processing.value = true
    router.post(
        route('project.budget.template.project'),
        {
            template_project_id: selectedProject.value.id,
            project_id: props.projectId,
        },
        {
            preserveScroll: true,
            onSuccess: () => closeModal(true),
            onFinish: () => {
                processing.value = false
            },
        }
    )
}
</script>
