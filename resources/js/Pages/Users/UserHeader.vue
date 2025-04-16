<template>
    <app-layout :title="$t(title)">
        <div class="mt-5 mx-auto container">
            <div>
                <PageTitle :title="$t(title) ?? $t('Users & teams')" :description="$t(description)" />

            </div>

            <div class="flex items-center justify-between">
                <UserTabs />

                <slot name="tabBar">

                </slot>
            </div>

            <div class="mt-10">
                <slot name="default"></slot>
            </div>
        </div>
    </app-layout>
</template>

<script>
import {defineComponent} from 'vue'
import UserTabs from "@/Pages/Users/Components/UserTabs.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PageTitle from "@/Artwork/Titles/PageTitle.vue";

export default defineComponent({
    name: "UserHeader",
    props: ['title', 'description'],
    components: {PageTitle, UserTabs, AppLayout}
})
</script>

<style scoped>

</style>
