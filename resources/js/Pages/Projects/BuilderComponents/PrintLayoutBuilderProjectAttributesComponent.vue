<template>
    <div>
        <h3 class="text-[11px] font-semibold uppercase tracking-wide text-secondary mb-2">{{ $t('Project properties') }}</h3>
        <div v-for="(category, index) in project.attributes" :key="index">
            <div v-if="category.length > 0" class="mb-2">
                <h4 class="xsDark mb-1">{{ $t(index) }}</h4>
                <div class="flex items-center flex-wrap gap-2">
                    <div v-for="(attribute, attrIndex) in category" :key="attrIndex" class="inline-flex items-center bg-white rounded-full border border-gray-100 pr-3">
                        <div class="inline-block size-7 rounded-full" :style="{ backgroundColor: attribute.color}" />
                        <div class="mx-2">
                            <p class="xsDark">{{ attribute.name }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="!project.attributes || Object.keys(project.attributes).length === 0" class="text-sm text-secondary">
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
