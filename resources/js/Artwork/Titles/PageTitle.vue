<template>
    <div class="py-2">
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-900">
            {{ title }}
        </h1>
        <p class="mt-1 text-sm text-zinc-600">
            {{ description }}
        </p>
    </div>
</template>

<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, required: true },
})
</script>
