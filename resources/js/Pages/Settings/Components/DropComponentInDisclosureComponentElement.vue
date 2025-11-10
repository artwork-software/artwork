<template>
    <div
        @dragleave="dropOver = false"
        @dragover="onDragOver"
        @drop="onDrop"
        :class="[
            'flex items-center h-4 min-h-4 rounded cursor-pointer transition',
            isDragging ? 'border-2 border-dashed' : '',
            isDragging && dropOver
                ? 'border-emerald-400 bg-emerald-50/60 ring-2 ring-emerald-400/30'
                : (isDragging ? 'border-zinc-300 bg-zinc-50/40' : '')
        ]"
        :aria-hidden="!isDragging"
        :aria-dropeffect="isDragging ? 'copy' : undefined"
    >
        <div v-if="isDragging" class="h-full w-full flex items-center justify-center gap-2 text-[11px] text-zinc-600 pointer-events-none">
            <span class="font-medium" :class="dropOver ? 'text-emerald-700' : ''">Hier in Disclosure ablegen</span>
        </div>
        <span v-else-if="dropOver" class="text-xs text-gray-300 w-full flex items-center justify-center pointer-events-none">
            Zum hinzufügen hier loslassen
        </span>
    </div>
</template>

<script setup>

import {onMounted, onUnmounted, ref} from "vue";
import {router} from "@inertiajs/vue3";
import { EventListenerForDragging } from "@/Composeables/EventListenerForDragging.js";

const props = defineProps({
    element: {
        type: Object,
        required: true,
    },
    index: {
        type: Number,
        required: true,
    }
})

const dropOver = ref(false);
const { isDragging, addEventListenerForDraggingStart, removeEventListenerForDraggingStart } = EventListenerForDragging();
let listeners = null;

onMounted(() => {
    listeners = addEventListenerForDraggingStart();
});

onUnmounted(() => {
    removeEventListenerForDraggingStart(listeners);
});

const onDragOver = (event) => {
    dropOver.value = true;
    event.preventDefault();
}

const onDrop = (event) => {
    event.preventDefault();

    // add check if JSON is valid
    if (!event.dataTransfer?.getData('application/json')) {
        dropOver.value = false;
        return false;
    }

    const data = JSON?.parse(event.dataTransfer?.getData('application/json'));

    if(data.drop_type !== 'component') {
        dropOver.value = false;
        return false;
    }

    if(data.type === 'DisclosureComponent') {
        dropOver.value = false;
        return false;
    }

    if(data.special){
        dropOver.value = false;
        return false;
    }


    router.post(route('project-management-builder.add.disclosure.component'), {
        order: props.index,
        component_id: data.id,
        disclosure_id: props.element.component.id
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            dropOver.value = false;
        }
    });
}

</script>

<style scoped>

</style>
