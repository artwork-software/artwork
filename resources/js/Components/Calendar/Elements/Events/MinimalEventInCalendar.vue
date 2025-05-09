<template>
    <div class="rounded-lg p-4 animate-pulse blink-loader bg-white shadow">
        <div class="h-4 bg-gray-200 rounded w-1/3 mb-2"></div>
        <div class="flex space-x-2 mb-4">
            <div class="h-6 bg-gray-200 rounded flex-1"></div>
            <div class="h-6 bg-gray-200 rounded flex-1"></div>
        </div>
        <div class="flex items-center space-x-2 mb-4">
            <div class="h-4 w-4 bg-gray-200 rounded-full"></div>
            <div class="h-4 bg-gray-200 rounded w-1/4"></div>
        </div>
        <div class="space-y-2">
            <div class="h-3 bg-gray-200 rounded"></div>
            <div class="h-3 bg-gray-200 rounded w-5/6"></div>
            <div class="h-3 bg-gray-200 rounded w-2/3"></div>
        </div>
        <div class="flex justify-end space-x-2 mt-4">
            <div class="h-5 w-5 bg-gray-200 rounded-full"></div>
            <div class="h-5 w-5 bg-gray-200 rounded-full"></div>
            <div class="h-5 w-5 bg-gray-200 rounded-full"></div>
        </div>
    </div>
</template>

<script setup>
</script>

<style scoped>
@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}
.blink-loader {
    animation: blink 1.5s ease-in-out infinite;
}
</style>
