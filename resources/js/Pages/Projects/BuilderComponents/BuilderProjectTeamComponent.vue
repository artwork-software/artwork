<template>
    <div>
        <div v-if="project.team?.length > 0" class="flex items-center -space-x-1.5">
            <div
                v-for="(user, index) in project.team"
                :key="index"
                class="relative shrink-0"
                @click.stop
            >
                <UserPopoverTooltip
                    :user="user"
                    height="8"
                    width="8"
                />
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
    },
    component: {
        type: Object,
        required: false,
    },
})
</script>
