<template>
    <div>
        <h3 class="text-[11px] font-semibold uppercase tracking-wide text-secondary mb-2">{{ $t('Project team') }}</h3>
        <div v-if="project.team?.length > 0" class="space-y-1">
            <div v-for="(user, index) in project.team" :key="index" class="group block shrink-0 bg-white w-fit pr-3 rounded-full border border-gray-100">
                <div class="flex items-center">
                    <div>
                        <img class="inline-block size-9 rounded-full object-cover" :src="user.profile_photo_url" alt="" />
                    </div>
                    <div class="mx-2">
                        <p class="xsDark group-hover:text-gray-900">{{ user.full_name }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div v-else class="text-sm text-secondary">
            {{ $t('No entries') }}
        </div>
    </div>
</template>
<script setup>
const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    component: {
        type: Object,
        required: false,
    },
})
</script>
<style scoped>
</style>
