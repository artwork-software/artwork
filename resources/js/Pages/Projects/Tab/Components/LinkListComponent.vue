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
                    <div
                        v-for="(row, idx) in links"
                        :key="`link-row-${idx}`"
                        class="rounded-lg border p-3"
                        :class="inSidebar ? 'border-zinc-600 bg-artwork-navigation-background/40' : 'border-gray-200 bg-white'"
                    >
                        <div class="grid grid-cols-1 gap-3">
                            <BaseInput
                                :id="`${projectData.id}-label-${idx}`"
                                type="text"
                                :disabled="!canEditComponent"
                                v-model="links[idx].label"
                                :label="idx === 0 ? (projectData.data.placeholder_label || $t('Display')) : ''"
                                without-translation
                                :input-classes="inSidebar ? '!bg-artwork-navigation-background !border-zinc-600 !text-white' : ''"
                            />

                            <BaseInput
                                :id="`${projectData.id}-url-${idx}`"
                                type="text"
                                :disabled="!canEditComponent"
                                v-model="links[idx].url"
                                :label="idx === 0 ? (projectData.data.placeholder_url || 'Link') : ''"
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
                                @click="removeRow(idx)"
                                :disabled="links.length === 1"
                            >
                                {{ $t('Delete') }}
                            </button>
                        </div>
                    </div>

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
                    <a
                        v-for="(l, idx) in displayLinks"
                        :key="`link-view-${idx}`"
                        :href="safeHref(l.url)"
                        target="_blank"
                        rel="noopener noreferrer nofollow"
                        class="block hover:underline"
                        :class="inSidebar ? 'text-blue-300 hover:text-blue-200' : 'text-blue-600'"
                    >
                        {{ l.label && l.label.length > 0 ? l.label : l.url }}
                    </a>
                </div>

                <div v-else class="text-sm" :class="inSidebar ? 'text-zinc-300' : 'text-gray-400'">
                    —
                </div>
            </div>
        </div>

        <InfoButtonComponent :component="component" v-if="component" />
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue"
import axios from "axios"
import { useProjectDataListener } from "@/Composeables/Listener/useProjectDataListener.js"
import InfoButtonComponent from "@/Pages/Projects/Tab/Components/InfoButtonComponent.vue"
import BaseInput from "@/Artwork/Inputs/BaseInput.vue"
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue"
import { IconEdit, IconPlus } from "@tabler/icons-vue"

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

const maxItems = computed(() => {
    const v = projectData.value?.data?.max_items
    const n = Number(v)
    return Number.isFinite(n) && n > 0 ? n : 50
})
const isMaxReached = computed(() => links.value.length >= maxItems.value)

const links = ref(readLinks(props.data))

onMounted(() => {
    useProjectDataListener(props.data, props.projectId).init()
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
        label: String(x?.label ?? ""),
        url: String(x?.url ?? ""),
    }))

    return normalized.length > 0 ? normalized : [{ label: "", url: "" }]
}

const displayLinks = computed(() => {
    return readLinks(props.data)
        .map((l) => ({
            label: (l.label ?? "").trim(),
            url: (l.url ?? "").trim(),
        }))
        .filter((l) => l.url.length > 0)
})

function addRow() {
    if (isMaxReached.value) return
    links.value.push({ label: "", url: "" })
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
        // leere raus
        .filter((r) => r.label.length > 0 || r.url.length > 0)
        // url muss vorhanden sein
        .filter((r) => r.url.length > 0)
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
