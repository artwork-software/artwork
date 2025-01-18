<template>
    <div  @dragover="onDragOver" @drop="onDrop" @dragleave="dropOver = false" class="w-4 h-full rounded-lg" :class="dropOver ? 'bg-gray-100' : ''">

    </div>
</template>

<script setup>

import {ref} from "vue";
import {router} from "@inertiajs/vue3";

const props = defineProps({
    order: {
        type: Number,
        required: true,
    }
})


const dropOver = ref(false);

const onDragOver = (event) => {
    dropOver.value = true;
    event.preventDefault();
}

const onDrop = (event) => {
    event.preventDefault();
    const component = JSON.parse(event.dataTransfer.getData('component'));
    dropOver.value = false;
    router.post(route('project-management-builder.store', {component: component.id}), {
        order: props.order
    });
}

</script>

<style scoped>

</style>