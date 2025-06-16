<template>
    <UserEditHeader :current-tab="currentTab" :user_to_edit="userToEdit">

        <div class="flex items-center justify-end">
            <GlassyIconButton text="Select Work Time Pattern" icon="IconClockSearch" />
        </div>

        <div class="card white p-5 mt-5">

        </div>

        <pre>
            {{ workTime }}
        </pre>
    </UserEditHeader>
</template>

<script setup>

import UserEditHeader from "@/Pages/Users/Components/UserEditHeader.vue";
import GlassyIconButton from "@/Artwork/Buttons/GlassyIconButton.vue";

const props = defineProps({
    userToEdit: {
        type: Object,
        required: true
    },
    workTime: {
        type: Object,
        required: false
    },
    currentTab: {
        type: String,
        required: true
    }
})

</script>

<style scoped>

</style>