<template>
    <div class="relative">
        <ToolTipComponent
            direction="bottom"
            :tooltip-text="$t('Inventory Filter')"
            :icon="IconFilter"
            icon-size="h-7 w-7"
            @click="showInventoryFilterModal = true"/>

        <span class="absolute flex size-2.5 top-0 right-0 pointer-events-none" v-if="checkIfAnyFilterIsActive">
              <span class="absolute inline-flex h-full w-full motion-safe:animate-ping rounded-full bg-blue-400 opacity-75"></span>
              <span class="relative inline-flex size-2.5 rounded-full bg-blue-500"></span>
        </span>
    </div>

    <teleport to="body">
        <InventoryFilterModal
            v-if="showInventoryFilterModal"
            @close="closeFilterModal"

        />
    </teleport>
</template>

<script setup>
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue';
import { usePage } from '@inertiajs/vue3';
import { computed, defineAsyncComponent, ref } from 'vue';
import {IconFilter} from "@tabler/icons-vue";

const props = defineProps({

});

const showInventoryFilterModal = ref(false);
const emit = defineEmits(['close']);

const checkIfAnyFilterIsActive = computed(() => {
    const ignoredKeys = ['created_at', 'updated_at', 'id', 'user_id'];
    return Object.entries(usePage().props.user_filter || {}).some(([key, value]) => {
        if (ignoredKeys.includes(key)) return false;
        if (Array.isArray(value)) return value.length > 0;
        return value !== null && value !== '';
    });
});

const InventoryFilterModal = defineAsyncComponent({
    loader: () => import('./InventoryFilterModal.vue'),
    delay: 200,
    timeout: 5000
});

const closeFilterModal = () => {
    showInventoryFilterModal.value = false;
    emit('close');
};
</script>

<style scoped>
</style>
