<template>
    <!-- Overlay-Actions -->
    <div
        class="absolute inset-0 hidden items-center justify-center rounded-lg bg-black/35 backdrop-blur-[1px] group-hover:flex"
    >
        <div class="flex items-center gap-2">
            <button
                type="button"
                class="ui-button bg-white/80 hover:bg-white transition"
                @click="showEditComponentModal = true"
                :aria-label="$t('Edit')"
            >
                <IconEdit class="size-4 text-accent-600" />
            </button>

            <button
                v-if="!component?.special"
                type="button"
                class="ui-button bg-danger-surface hover:bg-danger-surface transition"
                @click="showConfirmDeleteModal = true"
                :aria-label="$t('Delete')"
            >
                <IconTrash class="size-4 text-danger" />
            </button>
        </div>
    </div>

    <!-- Verwendungs-Badge -->
    <span
        v-if="usages.length > 0"
        class="absolute left-1 top-1 inline-flex items-center rounded-full border border-success-border bg-success-surface px-1.5 py-0.5 text-[10px] leading-3 text-success"
        :title="usageTitle"
    >
        {{ usages.length }}×
    </span>
    <span
        v-else
        class="absolute left-1 top-1 inline-flex items-center rounded-full border border-border-subtle bg-surface-sunken px-1.5 py-0.5 text-[10px] leading-3 text-text-subtle"
        :title="$t('Not used in any tab')"
    >
        0×
    </span>

    <!-- Inhalt -->
    <div class="mb-2 flex items-center justify-center">
        <ComponentIcons :type="component?.type" />
    </div>

    <div class="w-24 text-center text-sm font-semibold">
        <div class="w-24 truncate">
            {{ $t(component?.name) }}
            <!-- Ordner: Titel, wie er im Projekt angezeigt wird -->
            <div
                v-if="component?.type === 'DisclosureComponent' && component?.data?.label"
                class="flex items-center justify-center gap-0.5 text-[10px] font-normal text-text-muted"
                :title="$t('This title is displayed in the project')"
            >
                <IconFolder class="size-3 shrink-0 text-text-subtle" />
                <span class="truncate">{{ component.data.label }}</span>
            </div>
            <div
                class="truncate text-[10px] font-normal text-text-subtle"
                v-if="component?.data?.height"
            >
                {{ component.data.height }} {{ $t('Pixel') }}
                <span v-if="component.data.showLine === true"> | {{ $t('Show a separator line') }}</span>
            </div>
            <div
                class="truncate text-[10px] font-normal text-text-subtle"
                v-if="component?.data?.title_size"
            >
                {{ component.data.title_size }} {{ $t('Pixel') }}
            </div>
        </div>
    </div>

    <!-- Delete Confirm -->
    <ConfirmDeleteModal
        v-if="showConfirmDeleteModal"
        @closed="showConfirmDeleteModal = false"
        @delete="deleteComponent"
        :title="$t('Do you really want to delete the component {0}?', [component?.name])"
        :description="$t('This action cannot be undone. Do you really want to delete this component? This will also delete all data associated with this component.')"
    />

    <!-- Edit Modal -->
    <ComponentModal
        v-if="showEditComponentModal"
        :show="showEditComponentModal"
        mode="edit"
        :component-id="component.id"
        @close="showEditComponentModal = false"
    />
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { IconEdit, IconTrash, IconFolder } from '@tabler/icons-vue'
import ComponentIcons from '@/Components/Globale/ComponentIcons.vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'
import ComponentModal from '@/Pages/Settings/ComponentManagement/Components/ComponentModal.vue'

defineOptions({ name: 'SingleComponent' })

const props = withDefaults(defineProps<{
    component: {
        id?: number|string
        name: string
        type?: string
        data?: any
        special?: boolean
    }
    usages?: Array<{ tab: string; folder: string|null; sidebar: string|null }>
}>(), {
    usages: () => []
})

// Tooltip: alle Einsatzorte der Komponente, ein Ort pro Zeile
const usageTitle = computed(() =>
    props.usages
        .map((u) => {
            if (u.folder) return `${u.tab} → 📁 ${u.folder}`
            if (u.sidebar) return `${u.tab} → ▐ ${u.sidebar}`
            return u.tab
        })
        .join('\n')
)

const showEditComponentModal = ref(false)
const showConfirmDeleteModal = ref(false)

function deleteComponent() {
    if (!props.component?.id) {
        showConfirmDeleteModal.value = false
        return
    }
    router.delete(route('component.destroy', { component: props.component.id }), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => (showConfirmDeleteModal.value = false)
    })
}
</script>
