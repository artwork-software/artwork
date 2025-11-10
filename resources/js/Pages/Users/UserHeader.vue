<template>
    <app-layout :title="$t(title)">
        <div class="container mx-auto mt-6">
            <div class="my-6">
                <slot name="tabBar"></slot>
            </div>
            <div>
                <UserTabs />
            </div>
            <div class="mt-6">
                <slot name="default"></slot>
            </div>
        </div>
    </app-layout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import UserTabs from '@/Pages/Users/Components/UserTabs.vue'
import PageTitle from '@/Artwork/Titles/PageTitle.vue'

defineProps({
    title: { type: String, required: true },
    description: { type: String, required: true },
})
</script>
