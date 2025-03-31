<template>
    <div class="flex items-center cursor-pointer" :key="chat.id">
        <div class="mr-4 shrink-0">
            <img class="size-14 min-h-14 min-w-14 rounded-full object-cover" v-if="!props.chat.is_group" :src="getUserWhereAreNotMe.profile_photo_url" alt="Jese image">
            <component v-else is="IconUsersGroup" class="size-14 min-h-15 min-w-14 rounded-full object-cover p-3 bg-blue-50 text-blue-900 border border-blue-100" />
        </div>
        <div>
            <h4 class="font-lexend text-sm font-bold">{{ chat.name }}</h4>
            <p class="mt-1 font-lexend text-gray-500 text-xs" v-html="chat.last_message">

            </p>
        </div>
    </div>
</template>

<script setup>

import {computed} from "vue";

const props = defineProps({
    chat: {
        type: Object,
        required: true
    }
})

const getUserWhereAreNotMe = computed(() => {
    if (props.chat.is_group) {
        return {
            profile_photo_url: '/storage/profile-photos/users-group.png',
        }
    } else {
        return props.chat.users[0]
    }
})

</script>

<style scoped>

</style>