<template>
    <div class="flex items-center print:hidden">
        <div class="flex overflow-hidden items-center">
            <UserPopoverTooltip
                v-for="(user, index) in project.team"
                :key="index"
                :user="user"
                width="8"
                height="8"
                :classes="'border-2 cursor-pointer border-white rounded-full ' + (index > 0 ? '-ml-2' : '')"
            />
       </div>
    </div>

    <div class="hidden print:block ">
        <h3 class="headline3 hidden print:block print:mb-2">{{ $t('Project team') }}</h3>
        <div v-if="project.team?.length > 0" class="space-y-1 mt-3">
            <div v-for="(user, index) in project.team" class="group block shrink-0 bg-white w-fit pr-3 rounded-full border border-gray-100">
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

</template>

<script setup>

import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import Button from "@/Jetstream/Button.vue";
import {XIcon} from "@heroicons/vue/outline";

const props = defineProps({
    project: {
        type: Object,
        required: true,
    }
})

</script>

<style scoped>

</style>
