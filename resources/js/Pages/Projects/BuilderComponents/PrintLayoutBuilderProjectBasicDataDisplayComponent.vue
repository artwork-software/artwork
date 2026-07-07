<template>
    <div class="space-y-2">
        <div class="font-lexend font-black tracking-wide text-primaryText text-md">
            {{ $t('Basic data') }}
        </div>
        <div class="space-y-1.5 text-sm text-primaryText">
            <div v-if="project.basic_data?.name">
                <span class="text-[11px] font-semibold uppercase tracking-wide text-secondary">{{ $t('Project name') }}</span>
                <div>{{ project.basic_data.name }}</div>
            </div>
            <div v-if="project.basic_data?.artists">
                <span class="text-[11px] font-semibold uppercase tracking-wide text-secondary">{{ $t('Artists') }}</span>
                <div>{{ project.basic_data.artists }}</div>
            </div>
            <div v-if="project.basic_data?.description">
                <span class="text-[11px] font-semibold uppercase tracking-wide text-secondary">{{ $t('Description') }}</span>
                <div>{{ project.basic_data.description }}</div>
            </div>
            <div v-if="project.basic_data?.number_of_participants">
                <span class="text-[11px] font-semibold uppercase tracking-wide text-secondary">{{ $t('Number of participants') }}</span>
                <div>{{ project.basic_data.number_of_participants }}</div>
            </div>
            <div v-if="project.basic_data?.state">
                <span class="text-[11px] font-semibold uppercase tracking-wide text-secondary">{{ $t('Status') }}</span>
                <div>
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium" :style="{ backgroundColor: project.basic_data.state.color, color: '#fff' }">
                        {{ project.basic_data.state.name }}
                    </span>
                </div>
            </div>
            <div v-if="project.basic_data?.categories?.length">
                <span class="text-[11px] font-semibold uppercase tracking-wide text-secondary">{{ $t('Categories') }}</span>
                <div>{{ project.basic_data.categories.map(c => c.name).join(', ') }}</div>
            </div>
            <div v-if="project.basic_data?.genres?.length">
                <span class="text-[11px] font-semibold uppercase tracking-wide text-secondary">{{ $t('Genres') }}</span>
                <div>{{ project.basic_data.genres.map(g => g.name).join(', ') }}</div>
            </div>
            <div v-if="project.basic_data?.sectors?.length">
                <span class="text-[11px] font-semibold uppercase tracking-wide text-secondary">{{ $t('Sectors') }}</span>
                <div>{{ project.basic_data.sectors.map(s => s.name).join(', ') }}</div>
            </div>
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
        required: true,
    },
})
</script>

<style scoped>
</style>
