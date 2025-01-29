<template>
    <div>
        <div class="flex items-center">
            <div class="flex -space-x-2 overflow-hidden items-center">
                <UserPopoverTooltip v-for="user in project.shift_contact" :user="user" width="8" height="8" classes="border-2 border-white rounded-full" />
            </div>
        </div>
    </div>
</template>

<script setup>

import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";

const props = defineProps({
    project: {
        type: Object,
        required: true,
    }
})

</script>

<style scoped>

</style>