<template>
    <app-layout :title="$t(title)">
        <div class="container mx-auto mt-6">
            <div class="flex items-start justify-between gap-6">
                <PageTitle :title="$t(title) ?? $t('Users & teams')" :description="$t(description)" />

            </div>
            <UserTabs />
            <div class="mt-6">
                <slot name="tabBar"></slot>
            </div>

            <div class="mt-8">
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
