<template>
    <div class="flex items-center cursor-pointer" :key="chat.id">
        <div class="mr-4 shrink-0">
            <img class="size-16 min-h-16 min-w-16 rounded-full object-cover" :src="getUserWhereAreNotMe.profile_photo_url" alt="Jese image">
        </div>
        <div>
            <h4 class="font-lexend text-sm font-bold">{{ chat.name }}</h4>
            <p class="mt-1 font-lexend text-gray-500 text-xs" v-html="chat.last_message">

            </p>
        </div>
    </div>

    <pre>
        {{ getUserWhereAreNotMe.profile_photo_url }}
    </pre>
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
            profile_photo_url: '/storage/app/public/profile-photos/users-group.png',
        }
    } else {
        return props.chat.users[0]
    }
})

</script>

<style scoped>

</style>