<template>
    <UserEditHeader :current-tab="currentTab" :user_to_edit="userToEdit">
    </UserEditHeader>
</template>

<script setup>

import UserEditHeader from "@/Pages/Users/Components/UserEditHeader.vue";

const props = defineProps({
    userToEdit: {
        type: Object,
        required: true
    },
    workTimePattern: {
        type: Object,
        required: true
    },
    currentTab: {
        type: String,
        required: true
    }
})

</script>

<style scoped>

</style>