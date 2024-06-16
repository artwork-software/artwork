<template>
    <td class="pl-3 border-b-4 text-sm subpixel-antialiased">{{ cell.value }}</td>
</template>

<script setup>
const props = defineProps({
    cell: Object
});
</script>
