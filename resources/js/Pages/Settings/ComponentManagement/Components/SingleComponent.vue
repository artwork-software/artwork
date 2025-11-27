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
                <IconEdit class="size-4 text-blue-600" />
            </button>

            <button
                v-if="!component?.special"
                type="button"
                class="ui-button bg-red-50 hover:bg-red-100 transition"
                @click="showConfirmDeleteModal = true"
                :aria-label="$t('Delete')"
            >
                <IconTrash class="size-4 text-red-600" />
            </button>
        </div>
    </div>

    <!-- Inhalt -->
    <div class="mb-2 flex items-center justify-center">
        <ComponentIcons :type="component?.type" />
    </div>

    <div class="w-24 text-center text-sm font-semibold">
        <div class="w-24 truncate">
            {{ $t(component?.name) }}
            <div
                class="truncate text-[10px] font-normal text-gray-500"
                v-if="component?.data?.height"
            >
                {{ component.data.height }} {{ $t('Pixel') }}
                <span v-if="component.data.showLine === true"> | {{ $t('Show a separator line') }}</span>
            </div>
            <div
                class="truncate text-[10px] font-normal text-gray-500"
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
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { IconEdit, IconTrash } from '@tabler/icons-vue'
import ComponentIcons from '@/Components/Globale/ComponentIcons.vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'
import ComponentModal from '@/Pages/Settings/ComponentManagement/Components/ComponentModal.vue'

defineOptions({ name: 'SingleComponent' })

const props = defineProps<{
    component: {
        id?: number|string
        name: string
        type?: string
        data?: any
        special?: boolean
    }
}>()

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
