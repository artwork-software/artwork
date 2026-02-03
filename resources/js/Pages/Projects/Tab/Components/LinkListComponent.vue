<template>
    <div class="my-2 flex items-start gap-x-4">
        <div>
            <!-- Header (Label + Edit Icon) -->
            <div class="flex items-center gap-x-2">
                <label
                    class="block text-sm font-bold leading-6"
                    :class="inSidebar ? 'text-white' : 'text-gray-900'"
                >
                    {{ projectData.data.label }}
                </label>

                <component
                    :is="IconEdit"
                    class="inline size-4 ml-2 cursor-pointer text-gray-400 hover:text-gray-600"
                    v-if="canEditComponent"
                    @click="toggleEdit"
                />
            </div>

            <!-- EDIT MODE -->
            <div class="mt-2 w-96" v-if="showEditor">
                <div class="space-y-3">
                    <!-- Template Actions -->
                    <div class="flex items-center justify-between pb-2 border-b" :class="inSidebar ? 'border-zinc-600' : 'border-gray-200'">
                        <button
                            type="button"
                            class="text-xs underline underline-offset-2 flex items-center gap-1"
                            :class="inSidebar ? 'text-zinc-200 hover:text-white' : 'text-indigo-600 hover:text-indigo-700'"
                            @click="openTemplateModal"
                        >
                            <component :is="IconTemplate" class="size-4" />
                            {{ $t('Templates') }}
                        </button>
                        <button
                            type="button"
                            class="text-xs underline underline-offset-2 flex items-center gap-1"
                            :class="inSidebar ? 'text-zinc-200 hover:text-white' : 'text-indigo-600 hover:text-indigo-700'"
                            @click="openSaveTemplateModal"
                            :disabled="links.length === 0"
                        >
                            <component :is="IconDeviceFloppy" class="size-4" />
                            {{ $t('Save as Template') }}
                        </button>
                    </div>

                    <draggable
                        v-model="links"
                        item-key="id"
                        ghost-class="opacity-50"
                        handle=".drag-handle"
                        @start="dragging = true"
                        @end="dragging = false"
                    >
                        <template #item="{ element, index }">
                            <div
                                :key="element.id"
                                class="rounded-lg border p-3 mb-3"
                                :class="inSidebar ? 'border-zinc-600 bg-artwork-navigation-background/40' : 'border-gray-200 bg-white'"
                            >
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="drag-handle cursor-grab" :class="dragging ? 'cursor-grabbing' : ''">
                                        <component :is="IconGripVertical" class="size-4 text-gray-400" />
                                    </div>
                                    <span class="text-xs text-gray-500">{{ index + 1 }}</span>
                                </div>
                                <div class="grid grid-cols-1 gap-3">
                                    <BaseInput
                                        :id="`${projectData.id}-label-${index}`"
                                        type="text"
                                        :disabled="!canEditComponent"
                                        v-model="element.label"
                                        :label="index === 0 ? (projectData.data.placeholder_label || $t('Display')) : ''"
                                        :placeholder="index > 0 ? (projectData.data.placeholder_label || $t('Display')) : ''"
                                        without-translation
                                        :input-classes="inSidebar ? '!bg-artwork-navigation-background !border-zinc-600 !text-white' : ''"
                                    />

                                    <BaseInput
                                        :id="`${projectData.id}-url-${index}`"
                                        type="text"
                                        :disabled="!canEditComponent"
                                        v-model="element.url"
                                        :label="index === 0 ? (projectData.data.placeholder_url || 'Link') : ''"
                                        :placeholder="index > 0 ? (projectData.data.placeholder_url || 'Link') : ''"
                                        without-translation
                                        :input-classes="inSidebar ? '!bg-artwork-navigation-background !border-zinc-600 !text-white' : ''"
                                    />
                                </div>

                                <div class="mt-2 flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        class="text-xs underline underline-offset-2"
                                        :class="[
                                            inSidebar ? 'text-zinc-200 hover:text-red-300' : 'text-gray-500 hover:text-red-600',
                                            links.length === 1 ? 'opacity-40 cursor-not-allowed' : ''
                                        ]"
                                        @click="removeRow(index)"
                                        :disabled="links.length === 1"
                                    >
                                        {{ $t('Delete') }}
                                    </button>
                                </div>
                            </div>
                        </template>
                    </draggable>

                    <!-- Actions -->
                    <div class="flex items-center justify-between pt-1">
                        <button
                            type="button"
                            class="text-xs underline underline-offset-2 flex items-center gap-1"
                            :class="[
                                inSidebar ? 'text-zinc-200 hover:text-white' : 'text-indigo-600 hover:text-indigo-700',
                                isMaxReached ? 'opacity-40 cursor-not-allowed' : ''
                            ]"
                            @click="addRow"
                            :disabled="isMaxReached"
                        >
                            <component :is="IconPlus" class="size-4" />
                            {{ $t('Add Link') }}
                        </button>

                        <BaseUIButton
                            :label="$t('Save')"
                            is-add-button
                            @click="saveLinks"
                            :disabled="saving || !canEditComponent"
                        />
                    </div>

                    <div v-if="error" class="text-xs" :class="inSidebar ? 'text-red-300' : 'text-red-600'">
                        {{ error }}
                    </div>
                </div>
            </div>

            <!-- VIEW MODE -->
            <div v-else class="mt-2">
                <div v-if="displayLinks.length > 0" class="space-y-1">
                    <template v-for="(l, idx) in displayLinks" :key="`link-view-${idx}`">
                        <!-- Link with URL -->
                        <a
                            v-if="l.url && l.url.length > 0"
                            :href="safeHref(l.url)"
                            target="_blank"
                            rel="noopener noreferrer nofollow"
                            class="block hover:underline cursor-pointer"
                            :class="inSidebar ? 'text-blue-300 hover:text-blue-200' : 'text-blue-600'"
                        >
                            {{ l.label && l.label.length > 0 ? l.label : l.url }}
                        </a>
                        <!-- Display text only (no link) -->
                        <span
                            v-else
                            class="block"
                            :class="inSidebar ? 'text-zinc-300' : 'text-gray-700'"
                        >
                            {{ l.label }}
                        </span>
                    </template>
                </div>

                <div v-else class="text-sm" :class="inSidebar ? 'text-zinc-300' : 'text-gray-400'">
                    —
                </div>
            </div>
        </div>

        <InfoButtonComponent :component="component" v-if="component" />
    </div>

    <!-- Template Selection Modal -->
    <ArtworkBaseModal
        v-if="showTemplateModal"
        :title="$t('Link List Templates')"
        :description="$t('Select a template to add entries or manage existing templates')"
        modal-size="sm:max-w-xl"
        @close="showTemplateModal = false"
    >
        <div class="space-y-4">
            <!-- Template List -->
            <div v-if="templates.length > 0" class="space-y-2 max-h-64 overflow-y-auto">
                <div
                    v-for="template in templates"
                    :key="template.id"
                    class="flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:bg-gray-50"
                >
                    <div class="flex-1">
                        <p class="font-medium text-sm">{{ template.name }}</p>
                        <p class="text-xs text-gray-500">{{ template.entries.length }} {{ $t('entries') }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="text-xs px-2 py-1 rounded bg-indigo-100 text-indigo-700 hover:bg-indigo-200"
                            @click="applyTemplate(template)"
                        >
                            {{ $t('Apply') }}
                        </button>
                        <button
                            type="button"
                            class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-700 hover:bg-gray-200"
                            @click="editTemplate(template)"
                        >
                            <component :is="IconEdit" class="size-3" />
                        </button>
                        <button
                            type="button"
                            class="text-xs px-2 py-1 rounded bg-red-100 text-red-700 hover:bg-red-200"
                            @click="deleteTemplate(template)"
                        >
                            <component :is="IconTrash" class="size-3" />
                        </button>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-8 text-gray-500">
                {{ $t('No templates available') }}
            </div>
        </div>
    </ArtworkBaseModal>

    <!-- Save Template Modal -->
    <ArtworkBaseModal
        v-if="showSaveTemplateModal"
        :title="$t('Save as Template')"
        :description="$t('Save current link list entries as a reusable template')"
        modal-size="sm:max-w-md"
        @close="showSaveTemplateModal = false"
    >
        <div class="space-y-4">
            <BaseInput
                id="template-name"
                type="text"
                v-model="newTemplateName"
                :label="$t('Template Name')"
            />
            <p class="text-xs text-gray-500">
                {{ $t('Only display texts will be saved, not the actual links.') }}
            </p>
            <div class="flex justify-end gap-2">
                <button
                    type="button"
                    class="px-4 py-2 text-sm rounded border border-gray-300 hover:bg-gray-50"
                    @click="showSaveTemplateModal = false"
                >
                    {{ $t('Cancel') }}
                </button>
                <button
                    type="button"
                    class="px-4 py-2 text-sm rounded bg-indigo-600 text-white hover:bg-indigo-700"
                    @click="saveAsTemplate"
                    :disabled="!newTemplateName.trim()"
                >
                    {{ $t('Save') }}
                </button>
            </div>
        </div>
    </ArtworkBaseModal>

    <!-- Edit Template Modal -->
    <ArtworkBaseModal
        v-if="showEditTemplateModal"
        :title="$t('Edit Template')"
        :description="$t('Modify template name and entries')"
        modal-size="sm:max-w-xl"
        @close="showEditTemplateModal = false"
    >
        <div class="space-y-4">
            <BaseInput
                id="edit-template-name"
                type="text"
                v-model="editingTemplate.name"
                :label="$t('Template Name')"
            />

            <div class="space-y-2 max-h-64 overflow-y-auto">
                <draggable
                    v-model="editingTemplate.entries"
                    item-key="id"
                    ghost-class="opacity-50"
                    handle=".edit-drag-handle"
                >
                    <template #item="{ element, index }">
                        <div class="flex items-center gap-2 p-2 rounded border border-gray-200">
                            <div class="edit-drag-handle cursor-grab">
                                <component :is="IconGripVertical" class="size-4 text-gray-400" />
                            </div>
                            <BaseInput
                                :id="`edit-entry-${index}`"
                                type="text"
                                v-model="element.display"
                                :label="index === 0 ? $t('Display Text') : ''"
                                class="flex-1"
                            />
                            <button
                                type="button"
                                class="text-red-500 hover:text-red-700"
                                @click="removeEditEntry(index)"
                                :disabled="editingTemplate.entries.length === 1"
                            >
                                <component :is="IconTrash" class="size-4" />
                            </button>
                        </div>
                    </template>
                </draggable>
            </div>

            <button
                type="button"
                class="text-xs underline underline-offset-2 flex items-center gap-1 text-indigo-600 hover:text-indigo-700"
                @click="addEditEntry"
            >
                <component :is="IconPlus" class="size-4" />
                {{ $t('Add Entry') }}
            </button>

            <div class="flex justify-end gap-2 pt-4 border-t">
                <button
                    type="button"
                    class="px-4 py-2 text-sm rounded border border-gray-300 hover:bg-gray-50"
                    @click="showEditTemplateModal = false"
                >
                    {{ $t('Cancel') }}
                </button>
                <button
                    type="button"
                    class="px-4 py-2 text-sm rounded bg-indigo-600 text-white hover:bg-indigo-700"
                    @click="updateTemplate"
                    :disabled="!editingTemplate.name.trim() || editingTemplate.entries.length === 0"
                >
                    {{ $t('Save') }}
                </button>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue"
import axios from "axios"
import draggable from "vuedraggable"
import { useProjectDataListener } from "@/Composeables/Listener/useProjectDataListener.js"
import InfoButtonComponent from "@/Pages/Projects/Tab/Components/InfoButtonComponent.vue"
import BaseInput from "@/Artwork/Inputs/BaseInput.vue"
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue"
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue"
import { IconEdit, IconPlus, IconGripVertical, IconTemplate, IconDeviceFloppy, IconTrash } from "@tabler/icons-vue"

defineOptions({ name: "LinkListComponent" })

const props = defineProps({
    data: { type: Object, required: true },
    projectId: { type: [String, Number], required: true },
    inSidebar: { type: Boolean, default: false },
    canEditComponent: { type: Boolean, default: true },
    component: { type: Object, default: null },
})

const projectData = computed(() => props.data)
const showEditor = ref(false)
const saving = ref(false)
const error = ref(null)
const dragging = ref(false)

// Template state
const showTemplateModal = ref(false)
const showSaveTemplateModal = ref(false)
const showEditTemplateModal = ref(false)
const templates = ref([])
const newTemplateName = ref('')
const editingTemplate = ref({ id: null, name: '', entries: [] })

let idCounter = 0
const generateId = () => `link-${Date.now()}-${idCounter++}`

const maxItems = computed(() => {
    const v = projectData.value?.data?.max_items
    const n = Number(v)
    return Number.isFinite(n) && n > 0 ? n : 50
})
const isMaxReached = computed(() => links.value.length >= maxItems.value)

const links = ref(readLinks(props.data))

onMounted(() => {
    useProjectDataListener(props.data, props.projectId).init()
    loadTemplates()
})

function toggleEdit() {
    if (!props.canEditComponent) return
    showEditor.value = !showEditor.value
    error.value = null
}

function readLinks(dataObj) {
    const source = dataObj?.project_value?.data?.links ?? dataObj?.data?.links ?? []
    const arr = Array.isArray(source) ? source : []

    const normalized = arr.map((x) => ({
        id: generateId(),
        label: String(x?.label ?? ""),
        url: String(x?.url ?? ""),
    }))

    return normalized.length > 0 ? normalized : [{ id: generateId(), label: "", url: "" }]
}

const displayLinks = computed(() => {
    return readLinks(props.data)
        .map((l) => ({
            label: (l.label ?? "").trim(),
            url: (l.url ?? "").trim(),
        }))
        // Allow entries with only display text (no URL required)
        .filter((l) => l.label.length > 0 || l.url.length > 0)
})

function addRow() {
    if (isMaxReached.value) return
    links.value.push({ id: generateId(), label: "", url: "" })
}

function removeRow(idx) {
    if (links.value.length === 1) return
    links.value.splice(idx, 1)
}

function normalizeUrl(url) {
    const u = (url ?? "").trim()
    if (!u) return ""
    // wenn kein Scheme, https:// davor
    if (!/^https?:\/\//i.test(u)) return `https://${u}`
    return u
}

function safeHref(url) {
    const u = normalizeUrl(url)
    // nur http(s)
    if (!/^https?:\/\//i.test(u)) return "#"
    return u
}

function cleanLinks(rows) {
    return (rows ?? [])
        .map((r) => ({
            label: (r.label ?? "").trim(),
            url: normalizeUrl(r.url ?? ""),
        }))
        // Allow entries with only label (display text) - no URL required
        .filter((r) => r.label.length > 0 || r.url.length > 0)
        .slice(0, maxItems.value)
}

async function saveLinks() {
    error.value = null

    const payload = { links: cleanLinks(links.value) }

    try {
        saving.value = true
        await axios.patch(
            route("project.tab.component.update", {
                project: props.projectId,
                component: props.data.id,
            }),
            { data: payload }
        )

        showEditor.value = false
    } catch (e) {
        console.error("Fehler beim Aktualisieren:", e)
        error.value = "Speichern fehlgeschlagen."
    } finally {
        saving.value = false
    }
}

// Template functions
async function loadTemplates() {
    try {
        const response = await axios.get(route('link_list_templates.index'))
        templates.value = response.data
    } catch (e) {
        console.error('Error loading templates:', e)
    }
}

function openTemplateModal() {
    loadTemplates()
    showTemplateModal.value = true
}

function openSaveTemplateModal() {
    newTemplateName.value = ''
    showSaveTemplateModal.value = true
}

async function saveAsTemplate() {
    if (!newTemplateName.value.trim()) return

    const entries = links.value
        .filter(l => l.label.trim().length > 0 || l.url.trim().length > 0)
        .map(l => ({ display: l.label.trim() || l.url.trim() }))

    if (entries.length === 0) return

    try {
        await axios.post(route('link_list_templates.store'), {
            name: newTemplateName.value.trim(),
            entries: entries
        })
        showSaveTemplateModal.value = false
        loadTemplates()
    } catch (e) {
        console.error('Error saving template:', e)
    }
}

function applyTemplate(template) {
    // Add template entries to existing links (don't replace)
    const newEntries = template.entries.map(e => ({
        id: generateId(),
        label: e.display || '',
        url: ''
    }))

    // Check max items limit
    const availableSlots = maxItems.value - links.value.length
    const entriesToAdd = newEntries.slice(0, availableSlots)

    links.value = [...links.value, ...entriesToAdd]
    showTemplateModal.value = false
}

function editTemplate(template) {
    editingTemplate.value = {
        id: template.id,
        name: template.name,
        entries: template.entries.map((e, idx) => ({
            id: `edit-${idx}`,
            display: e.display
        }))
    }
    showEditTemplateModal.value = true
    showTemplateModal.value = false
}

function addEditEntry() {
    editingTemplate.value.entries.push({
        id: `edit-${Date.now()}`,
        display: ''
    })
}

function removeEditEntry(index) {
    if (editingTemplate.value.entries.length === 1) return
    editingTemplate.value.entries.splice(index, 1)
}

async function updateTemplate() {
    if (!editingTemplate.value.name.trim()) return

    const entries = editingTemplate.value.entries
        .filter(e => e.display.trim().length > 0)
        .map(e => ({ display: e.display.trim() }))

    if (entries.length === 0) return

    try {
        await axios.patch(route('link_list_templates.update', { linkListTemplate: editingTemplate.value.id }), {
            name: editingTemplate.value.name.trim(),
            entries: entries
        })
        showEditTemplateModal.value = false
        loadTemplates()
    } catch (e) {
        console.error('Error updating template:', e)
    }
}

async function deleteTemplate(template) {
    if (!confirm('Vorlage wirklich löschen?')) return

    try {
        await axios.delete(route('link_list_templates.destroy', { linkListTemplate: template.id }))
        loadTemplates()
    } catch (e) {
        console.error('Error deleting template:', e)
    }
}

// Sync wenn Broadcast/Props aktualisiert werden
watch(
    () => props.data,
    (newVal) => {
        links.value = readLinks(newVal)
    },
    { deep: true }
)
</script>

<style scoped>
</style>
