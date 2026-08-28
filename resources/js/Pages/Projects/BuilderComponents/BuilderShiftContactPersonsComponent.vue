<template>
    <div>
        <div v-if="project.shift_contact?.length > 0" class="space-y-1">
            <div v-for="(user, index) in project.shift_contact" :key="index" class="group block shrink-0 bg-white w-fit pr-3 rounded-full border border-border-subtle">
                <div class="flex items-center">
                    <div>
                        <img class="inline-block size-9 rounded-full object-cover" :src="user.profile_photo_url" alt="" />
                    </div>
                    <div class="mx-2">
                        <p class="text-sm/5 font-semibold text-text">{{ user.full_name }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div v-else class="text-sm text-text-subtle">
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
