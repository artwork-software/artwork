<template>
    <app-layout :title="title ?? $t('Users & teams')">
        <div class=" ml-14 mr-10">
            <h2 class="headline1">{{ $t('Users & teams')}}</h2>

            <UserTabs />

            <slot></slot>
        </div>
    </app-layout>
</template>

<script>
import {defineComponent} from 'vue'
import UserTabs from "@/Pages/Users/Components/UserTabs.vue";
import AppLayout from "@/Layouts/AppLayout.vue";

export default defineComponent({
    name: "UserHeader",
    props: ['title'],
    components: {UserTabs, AppLayout}
})
</script>

<style scoped>

</style>
