<template>
    <div>
        <div class="flex items-center print:hidden">
            <div class="flex -space-x-2 overflow-hidden items-center">
                <UserPopoverTooltip v-for="user in project.shift_contact" :user="user" width="8" height="8" classes="border-2 border-white rounded-full" />
            </div>
        </div>

        <div class="hidden print:block ">
            <h3 class="headline3 hidden print:block print:mb-2">{{ $t('Contact persons') }}</h3>
            <div v-if="project.shift_contact?.length > 0" class="space-y-1 mt-3">
                <div v-for="(user, index) in project.shift_contact" class="group block shrink-0 bg-white w-fit pr-3 rounded-full border border-gray-100">
                    <div class="flex items-center">
                        <div>
                            <img class="inline-block size-9 rounded-full object-cover" :src="user.profile_photo_url" alt="" />
                        </div>
                        <div class="mx-2">
                            <p class="xsDark group-hover:text-gray-900">{{ user.full_name}}</p>
                        </div>
                    </div>
                </div>
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