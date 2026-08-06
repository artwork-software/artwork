<template>
    <ArtworkBaseModal
        @close="$emit('closed')"
        :title="$t('Restore deleted columns')"
        :description="$t('Deleted columns can be restored here, including their values and comments.')"
    >
        <div class="mx-4 mb-6">
            <div v-if="isLoading" class="text-text-subtle text-sm py-4">
                {{ $t('Loading data...') }}
            </div>
            <div v-else-if="trashedColumns.length === 0" class="text-text-subtle text-sm py-4">
                {{ $t('No deleted columns available.') }}
            </div>
            <ul v-else class="divide-y divide-border-subtle">
                <li
                    v-for="column in trashedColumns"
                    :key="column.id"
                    class="flex items-center justify-between py-3"
                >
                    <div>
                        <div class="text-sm/5 font-semibold text-text">{{ column.name }}</div>
                        <div class="text-xs text-text-subtle">
                            {{ $t('Deleted at') }}: {{ formatDeletedAt(column.deleted_at) }}
                        </div>
                    </div>
                    <BaseUIButton
                        :label="$t('Restore')"
                        icon="IconRestore"
                        :disabled="restoringColumnId !== null"
                        @click="restoreColumn(column)"
                    />
                </li>
            </ul>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const props = defineProps({
    table: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['closed', 'restored']);

const trashedColumns = ref([]);
const isLoading = ref(true);
const restoringColumnId = ref(null);

const loadTrashedColumns = async () => {
    isLoading.value = true;
    try {
        const { data } = await axios.get(
            route('project.budget.column.trashed', { table: props.table.id })
        );
        trashedColumns.value = data ?? [];
    } catch (error) {
        console.error(error);
        trashedColumns.value = [];
    } finally {
        isLoading.value = false;
    }
};

const restoreColumn = (column) => {
    restoringColumnId.value = column.id;
    router.patch(
        route('project.budget.column.restore', { column: column.id }),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                trashedColumns.value = trashedColumns.value.filter((entry) => entry.id !== column.id);
                emit('restored');
            },
            onFinish: () => {
                restoringColumnId.value = null;
            }
        }
    );
};

const formatDeletedAt = (deletedAt) => {
    if (!deletedAt) {
        return '-';
    }
    const date = new Date(deletedAt);
    return `${String(date.getDate()).padStart(2, '0')}.${String(date.getMonth() + 1).padStart(2, '0')}.${date.getFullYear()}`;
};

onMounted(loadTrashedColumns);
</script>
