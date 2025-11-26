<template>
    <InventorySettingsHeader
        :title="$t('Tags & Tag Groups')"
        :description="$t('Manage your inventory tags and tag groups to better organize and categorize your items.')"
    >
        <template #actions>
            <button class="ui-button-add" @click="openCreateTagGroup">
                <component :is="IconPlus" stroke-width="1" class="size-5" />
                {{ $t('Add tag group') }}
            </button>
            <button class="ui-button-add" @click="openCreateTag">
                <component :is="IconPlus" stroke-width="1" class="size-5" />
                {{ $t('Add tag') }}
            </button>
        </template>

        <!-- Info-Alert zu Tags -->
        <BaseAlertComponent
            type="info"
            class="mb-4"
            :message="$t('Tags help you filter your inventory more precisely and control who is allowed to create, edit or delete tagged items.')"
        />

        <div class="space-y-6">
            <!-- Gruppen + Tags (mit Drag&Drop für Gruppen) -->
            <Draggable
                v-model="groupsState"
                item-key="id"
                handle=".drag-handle-group"
                ghost-class="opacity-60"
                :animation="150"
                class="grid grid-cols-1 lg:grid-cols-2 gap-5"
                @end="onGroupsReorder"
            >
                <template #item="{ element: group }">
                    <div
                        class="rounded-2xl border border-gray-100 bg-white/80 shadow-sm hover:shadow-md transition-shadow duration-150"
                    >
                        <!-- Gruppen-Header -->
                        <div
                            class="flex items-start justify-between gap-3 px-4 py-3 border-b border-gray-100 bg-gradient-to-r from-slate-50 via-white to-slate-50 rounded-t-2xl"
                        >
                            <div class="flex items-start gap-2 min-w-0">
                                <button
                                    type="button"
                                    class="drag-handle-group mt-0.5 text-gray-300 hover:text-gray-500"
                                    title="Reorder group"
                                >
                                    <component :is="IconGripVertical" class="h-4 w-4" />
                                </button>
                                <div class="space-y-0.5 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold text-gray-900 truncate">
                                            {{ group.name }}
                                        </span>
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600"
                                        >
                                            <span class="h-1 w-1 rounded-full bg-emerald-500" />
                                            {{ $t('Tags') }}: {{ group.tags.length }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-gray-400">
                                        {{ $t('Tags in this group will appear together in the filter sidebar.') }}
                                    </p>
                                </div>
                            </div>

                            <BaseMenu white-menu-background has-no-offset>
                                <BaseMenuItem
                                    white-menu-background
                                    :title="$t('Edit group')"
                                    @click="openEditTagGroup(group)"
                                />
                                <BaseMenuItem
                                    white-menu-background
                                    is-destructive
                                    :title="$t('Delete group')"
                                    @click="askDeleteTagGroup(group)"
                                />
                            </BaseMenu>
                        </div>

                        <!-- Gruppen-Body: Tags (mit Drag&Drop für Tags in Gruppe) -->
                        <div class="px-4 py-3">
                            <Draggable
                                v-if="group.tags.length"
                                v-model="group.tags"
                                item-key="id"
                                handle=".drag-handle-tag"
                                ghost-class="opacity-60"
                                :animation="150"
                                class="space-y-2"
                                @end="() => onTagsReorder(group)"
                            >
                                <template #item="{ element: tag }">
                                    <div
                                        class="flex items-center gap-3 rounded-xl border border-gray-100 bg-gray-50/60 px-3 py-2 hover:bg-gray-50 transition-colors"
                                    >
                                        <!-- Farbstreifen -->
                                        <div
                                            class="h-8 w-1.5 rounded-full"
                                            :style="{ backgroundColor: tag.color || '#4f46e5' }"
                                        />

                                        <!-- Main Content -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-medium text-gray-900 truncate">
                                                    {{ tag.name }}
                                                </span>

                                                <span
                                                    v-if="tag.has_restricted_permissions"
                                                    class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-medium text-amber-700 border border-amber-100"
                                                >
                                                    <component :is="IconLock" class="h-3 w-3" />
                                                    {{ $t('Restricted') }}
                                                </span>
                                            </div>

                                            <div
                                                class="mt-0.5 flex flex-wrap items-center gap-2 text-[11px] text-gray-500"
                                            >
                                                <!-- User-Info -->
                                                <span class="inline-flex items-center gap-1">
                                                    <component :is="IconUsers" class="h-3.5 w-3.5" />
                                                    <span>
                                                        {{ $t('Users') }}:
                                                        {{ (tag.allowed_users ?? []).length }}
                                                    </span>
                                                </span>

                                                <!-- Department-Info -->
                                                <span class="inline-flex items-center gap-1">
                                                    <component
                                                        :is="IconBuildingCommunity"
                                                        class="h-3.5 w-3.5"
                                                    />
                                                    <span>
                                                        {{ $t('Departments') }}:
                                                        {{ (tag.allowed_departments ?? []).length }}
                                                    </span>
                                                </span>

                                                <!-- Farbe als Hex -->
                                                <span
                                                    class="inline-flex items-center gap-1 text-gray-400"
                                                >
                                                    <span
                                                        class="inline-flex h-2 w-2 rounded-full border border-gray-200"
                                                        :style="{ backgroundColor: tag.color || '#4f46e5' }"
                                                    />
                                                    <span class="font-mono text-[10px]">
                                                        {{ tag.color }}
                                                    </span>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Drag-Handle & Menü -->
                                        <div class="flex items-center gap-1">
                                            <button
                                                type="button"
                                                class="drag-handle-tag text-gray-300 hover:text-gray-500"
                                                title="Reorder tag"
                                            >
                                                <component :is="IconGripVertical" class="h-4 w-4" />
                                            </button>
                                            <BaseMenu white-menu-background has-no-offset>
                                                <BaseMenuItem
                                                    white-menu-background
                                                    :title="$t('Edit tag')"
                                                    @click="openEditTag(tag)"
                                                />
                                                <BaseMenuItem
                                                    white-menu-background
                                                    is-destructive
                                                    :title="$t('Delete tag')"
                                                    @click="askDeleteTag(tag)"
                                                />
                                            </BaseMenu>
                                        </div>
                                    </div>
                                </template>
                            </Draggable>

                            <div
                                v-else
                                class="flex items-center justify-between gap-3 rounded-xl border border-dashed border-gray-200 bg-white px-3 py-3"
                            >
                                <div class="space-y-1">
                                    <p class="text-xs font-medium text-gray-700">
                                        {{ $t('No tags in this group yet.') }}
                                    </p>
                                    <p class="text-[11px] text-gray-400">
                                        {{ $t('Add tags and assign them to this group to keep your inventory structured.') }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-full border border-indigo-200 bg-indigo-50 px-2 py-1 text-[11px] font-medium text-indigo-700 hover:bg-indigo-100"
                                    @click="openCreateTagWithGroup(group)"
                                >
                                    <component :is="IconPlus" class="h-3.5 w-3.5" />
                                    {{ $t('Add tag') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </Draggable>

            <!-- Ungruppierte Tags (ggf. ebenfalls per Drag&Drop sortierbar) -->
            <div
                v-if="ungroupedTagsState.length"
                class="rounded-2xl border border-dashed border-gray-200 bg-white/70 p-4 shadow-sm"
            >
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                            <span
                                class="inline-flex h-6 w-6 items-center justify-center rounded-xl bg-slate-100 text-slate-600 text-xs font-bold"
                            >
                                ∞
                            </span>
                            {{ $t('Ungrouped tags') }}
                        </p>
                        <p class="mt-0.5 text-xs text-gray-500">
                            {{ $t('Tags without a group will still be available in filters, but are not grouped.') }}
                        </p>
                    </div>
                </div>

                <Draggable
                    v-model="ungroupedTagsState"
                    item-key="id"
                    handle=".drag-handle-tag-ungrouped"
                    ghost-class="opacity-60"
                    :animation="150"
                    class="space-y-2"
                    @end="onUngroupedTagsReorder"
                >
                    <template #item="{ element: tag }">
                        <div
                            class="flex items-center gap-3 rounded-xl border border-gray-100 bg-gray-50/60 px-3 py-2 hover:bg-gray-50 transition-colors"
                        >
                            <div
                                class="h-8 w-1.5 rounded-full"
                                :style="{ backgroundColor: tag.color || '#4f46e5' }"
                            />

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium text-gray-900 truncate">
                                        {{ tag.name }}
                                    </span>
                                    <span
                                        v-if="tag.has_restricted_permissions"
                                        class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-medium text-amber-700 border border-amber-100"
                                    >
                                        <component :is="IconLock" class="h-3 w-3" />
                                        {{ $t('Restricted') }}
                                    </span>
                                </div>

                                <div
                                    class="mt-0.5 flex flex-wrap items-center gap-2 text-[11px] text-gray-500"
                                >
                                    <span class="inline-flex items-center gap-1">
                                        <component :is="IconUsers" class="h-3.5 w-3.5" />
                                        <span>
                                            {{ $t('Users') }}:
                                            {{ (tag.allowed_users ?? []).length }}
                                        </span>
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <component
                                            :is="IconBuildingCommunity"
                                            class="h-3.5 w-3.5"
                                        />
                                        <span>
                                            {{ $t('Departments') }}:
                                            {{ (tag.allowed_departments ?? []).length }}
                                        </span>
                                    </span>
                                    <span class="inline-flex items-center gap-1 text-gray-400">
                                        <span
                                            class="inline-flex h-2 w-2 rounded-full border border-gray-200"
                                            :style="{ backgroundColor: tag.color || '#4f46e5' }"
                                        />
                                        <span class="font-mono text-[10px]">
                                            {{ tag.color }}
                                        </span>
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    class="drag-handle-tag-ungrouped text-gray-300 hover:text-gray-500"
                                    title="Reorder tag"
                                >
                                    <component :is="IconGripVertical" class="h-4 w-4" />
                                </button>
                                <BaseMenu white-menu-background has-no-offset>
                                    <BaseMenuItem
                                        white-menu-background
                                        :title="$t('Edit tag')"
                                        @click="openEditTag(tag)"
                                    />
                                    <BaseMenuItem
                                        white-menu-background
                                        is-destructive
                                        :title="$t('Delete tag')"
                                        @click="askDeleteTag(tag)"
                                    />
                                </BaseMenu>
                            </div>
                        </div>
                    </template>
                </Draggable>
            </div>
        </div>
    </InventorySettingsHeader>

    <!-- Modals -->
    <AddEditTagGroupModal
        v-if="showAddEditTagGroupModal"
        :tag-group="selectedTagGroup"
        :all-tags="tags"
        @close="showAddEditTagGroupModal = false"
        @saved="reloadAfterChange"
    />

    <AddEditTagModal
        v-if="showAddEditTagModal"
        :tag="selectedTag"
        :tag-groups="normalizedTagGroups"
        @close="showAddEditTagModal = false"
        @saved="reloadAfterChange"
    />

    <!-- Delete-Confirm -->
    <ArtworkBaseDeleteModal
        v-if="showDeleteModal"
        :title="deleteModalTitle"
        :description="deleteModalDescription"
        @close="closeDeleteModal"
        @delete="performDelete"
    />
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import {
    IconPlus,
    IconLock,
    IconUsers,
    IconBuildingCommunity,
    IconGripVertical
} from '@tabler/icons-vue'
import Draggable from 'vuedraggable'

import InventorySettingsHeader from '@/Pages/InventorySetting/Components/InventorySettingsHeader.vue'
import AddEditTagGroupModal from '@/Pages/InventorySetting/Components/AddEditTagGroupModal.vue'
import AddEditTagModal from '@/Pages/InventorySetting/Components/AddEditTagModal.vue'
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import BaseMenuItem from '@/Components/Menu/BaseMenuItem.vue'
import BaseAlertComponent from '@/Components/Alerts/BaseAlertComponent.vue'
import ArtworkBaseDeleteModal from "@/Artwork/Modals/ArtworkBaseDeleteModal.vue";
import {useI18n} from "vue-i18n";

const { t } = useI18n();

const props = defineProps({
    tagGroups: {
        type: Object,
        required: true
    },
    tags: {
        type: Array,
        required: true
    }
})

const showAddEditTagGroupModal = ref(false)
const showAddEditTagModal = ref(false)
const selectedTagGroup = ref(null)
const selectedTag = ref(null)

const normalizedTagGroups = computed(() => {
    return Array.isArray(props.tagGroups) ? props.tagGroups : (props.tagGroups.data || [])
})

/**
 * Lokaler State für Drag&Drop:
 * - groupsState: Gruppen inkl. tags[]
 * - ungroupedTagsState: Tags ohne Gruppe
 */
const groupsState = ref([])
const ungroupedTagsState = ref([])

function rebuildState() {
    const tagsMap = new Map()
    ;(props.tags || []).forEach(tag => {
        const key = tag.inventory_tag_group_id ?? '__ungrouped__'
        if (!tagsMap.has(key)) {
            tagsMap.set(key, [])
        }
        tagsMap.get(key).push({ ...tag })
    })

    groupsState.value = (normalizedTagGroups.value || []).map(group => ({
        ...group,
        tags: tagsMap.get(group.id) || []
    }))

    ungroupedTagsState.value = tagsMap.get('__ungrouped__') || []
}

// State initial aufbauen & bei Prop-Änderung aktualisieren
watch(
    () => [normalizedTagGroups.value, props.tags],
    () => rebuildState(),
    { immediate: true, deep: true }
)

function openCreateTagGroup() {
    selectedTagGroup.value = null
    showAddEditTagGroupModal.value = true
}

function openEditTagGroup(group) {
    selectedTagGroup.value = group
    showAddEditTagGroupModal.value = true
}

function openCreateTag() {
    selectedTag.value = null
    showAddEditTagModal.value = true
}

function openCreateTagWithGroup(group) {
    selectedTag.value = {
        inventory_tag_group_id: group.id
    }
    showAddEditTagModal.value = true
}

function openEditTag(tag) {
    selectedTag.value = tag
    showAddEditTagModal.value = true
}

/**
 * Delete-Modal State
 */
const showDeleteModal = ref(false)
const deleteContext = ref({
    type: null, // 'group' | 'tag'
    entity: null
})

const deleteModalTitle = computed(() => {
    if (!deleteContext.value.entity) return ''
    if (deleteContext.value.type === 'group') {
        return t('Delete tag group')
    }
    if (deleteContext.value.type === 'tag') {
        return t('Delete tag')
    }
    return ''
})

const deleteModalDescription = computed(() => {
    const entity = deleteContext.value.entity
    if (!entity) return ''
    if (deleteContext.value.type === 'group') {
        return t('Are you sure you want to delete the tag group “{0}”? Tags will remain, but no longer be grouped.', [entity.name])
    }
    if (deleteContext.value.type === 'tag') {
        return t('Are you sure you want to delete the tag “{0}”? It will be removed from all items.', [entity.name])
    }
    return ''
})

function askDeleteTagGroup(group) {
    deleteContext.value = {
        type: 'group',
        entity: group
    }
    showDeleteModal.value = true
}

function askDeleteTag(tag) {
    deleteContext.value = {
        type: 'tag',
        entity: tag
    }
    showDeleteModal.value = true
}

function closeDeleteModal() {
    showDeleteModal.value = false
    deleteContext.value = { type: null, entity: null }
}

function performDelete() {
    const ctx = deleteContext.value
    if (!ctx.entity || !ctx.type) {
        closeDeleteModal()
        return
    }

    if (ctx.type === 'group') {
        router.delete(route('settings.inventory-tag-groups.destroy', ctx.entity.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteModal()
                reloadAfterChange()
            }
        })
        return
    }

    if (ctx.type === 'tag') {
        router.delete(route('settings.inventory-tags.destroy', ctx.entity.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteModal()
                reloadAfterChange()
            }
        })
    }
}

/**
 * Reorder-Handler
 */
function onGroupsReorder() {
    const orderedIds = groupsState.value.map(g => g.id)
    router.post(
        route('settings.inventory-tag-groups.reorder'),
        { ordered_ids: orderedIds },
        {
            preserveScroll: true
        }
    )
}

function onTagsReorder(group) {
    const orderedIds = (group.tags || []).map(t => t.id)
    router.post(
        route('settings.inventory-tags.reorder'),
        {
            group_id: group.id,
            ordered_ids: orderedIds
        },
        {
            preserveScroll: true
        }
    )
}

function onUngroupedTagsReorder() {
    const orderedIds = ungroupedTagsState.value.map(t => t.id)
    router.post(
        route('settings.inventory-tags.reorder'),
        {
            group_id: null,
            ordered_ids: orderedIds
        },
        {
            preserveScroll: true
        }
    )
}

function reloadAfterChange() {
    router.reload({
        only: ['tagGroups', 'tags'],
        preserveScroll: true
    })
}
</script>

<style scoped>
.opacity-60 {
    opacity: 0.6;
}
</style>
