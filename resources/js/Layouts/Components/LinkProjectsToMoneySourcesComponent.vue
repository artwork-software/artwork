<template>
    <ArtworkBaseModal
        title="Link projects"
        description="Assign projects to this funding source. Only these projects can later be linked to this source of funding."
        modal-size="sm:max-w-lg"
        @close="closeModal(false)"
    >
        <div class="space-y-4">
            <div>
                <BaseInput
                    id="linkProjectsSearch"
                    v-model="projectQuery"
                    label="Search for projects"
                    autocomplete="off"
                    autofocus
                />
                <ul
                    v-if="projectSearchResults.length > 0 && projectQuery.length > 0"
                    class="mt-2 max-h-60 divide-y divide-border-subtle overflow-y-auto rounded-lg border border-border bg-surface"
                >
                    <li v-for="project in projectSearchResults" :key="project.id">
                        <button
                            type="button"
                            class="block w-full cursor-pointer truncate px-3 py-2 text-left text-sm font-medium text-text transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover focus-visible:bg-surface-hover"
                            @click="addProjectToArray(project)"
                        >
                            {{ project.name }}
                        </button>
                    </li>
                </ul>
            </div>

            <LastedProjects
                :limit="10"
                @select="addProjectToArray"
            />

            <div v-if="linkedProjectsArray.length > 0">
                <p class="mb-2 font-lexend text-xs font-medium text-text-muted">{{ $t('Linked projects') }}</p>
                <ul class="max-h-72 divide-y divide-border-subtle overflow-y-auto rounded-lg border border-border bg-surface">
                    <li
                        v-for="project in linkedProjectsArray"
                        :key="project.id"
                        class="flex items-center justify-between gap-x-3 px-3 py-2"
                    >
                        <span class="truncate text-sm font-medium text-text">{{ project.name }}</span>
                        <button
                            type="button"
                            class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-text-subtle transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover hover:text-danger"
                            :aria-label="$t('Remove project')"
                            @click="deleteProjectFromArray(project)"
                        >
                            <PropertyIcon name="IconX" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                        </button>
                    </li>
                </ul>
            </div>
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
                label="Save"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="processing"
                :disabled="processing"
                @click="updateLinkedProjects"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {ref, watch} from 'vue'
import axios from 'axios'
import {router} from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import LastedProjects from '@/Artwork/LastedProjects.vue'

defineOptions({name: 'LinkProjectsToMoneySourcesComponent'})

const props = defineProps({
    moneySource: {type: Object, required: true},
    linkedProjects: {type: Array, default: () => []},
})

const emit = defineEmits(['closed'])

const projectQuery = ref('')
const projectSearchResults = ref([])
// Kopie: früher wurde das übergebene Array direkt verändert, auch beim Abbrechen
const linkedProjectsArray = ref([...(props.linkedProjects ?? [])])
const processing = ref(false)

// Spät eintreffende Antworten einer älteren Eingabe nicht über neuere schreiben
let latestSearchRequest = 0
watch(projectQuery, (query) => {
    if (query.length === 0) {
        projectSearchResults.value = []
        return
    }
    const requestNumber = ++latestSearchRequest
    axios.get('/projects/search', {params: {query}}).then((response) => {
        if (requestNumber === latestSearchRequest) {
            projectSearchResults.value = response.data
        }
    })
})

function closeModal(saved) {
    emit('closed', saved)
}

function addProjectToArray(projectToAdd) {
    projectQuery.value = ''
    if (!projectToAdd?.id || linkedProjectsArray.value.some((project) => project.id === projectToAdd.id)) {
        return
    }
    linkedProjectsArray.value.push(projectToAdd)
}

function deleteProjectFromArray(projectToRemove) {
    linkedProjectsArray.value = linkedProjectsArray.value.filter((project) => project.id !== projectToRemove.id)
}

function updateLinkedProjects() {
    if (processing.value) {
        return
    }
    processing.value = true
    router.patch(
        route('money_sources.update_projects', props.moneySource.id),
        {linkedProjectIds: linkedProjectsArray.value.map((project) => project.id)},
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => closeModal(true),
            onFinish: () => {
                processing.value = false
            },
        }
    )
}
</script>
