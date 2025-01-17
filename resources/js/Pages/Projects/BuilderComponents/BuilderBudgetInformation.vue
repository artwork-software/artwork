<template>
    <div class="xxsLight">
        <div class="w-full items-center">
            <div class="">{{ $t('Cost unit:') }} {{ project?.cost_center?.name }}</div>
        </div>
        <div class="">{{ $t('Copyright') }}: {{ project.own_copyright ? $t('Yes') : $t('No') }}</div>
        <div v-if="project.own_copyright">
            {{ project?.collecting_society?.name }},
            {{ project.law_size === "SMALL" ? $t('Small law') : $t('Big law') }},
            {{ project.live_music ? $t('with live music') : $t('without live music') }}
        </div>
        <div class="font-bold" v-if="project.own_copyright">
            {{ project.cost_center_description }}
        </div>
    </div>
</template>

<script setup>

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
})

</script>

<style scoped>

</style>