<template>
    <div class="xxsLight line-clamp-3 print:hidden">
        <h3 class="headline3 hidden print:block print:mb-2">{{ $t('Project properties') }}</h3>
        <div v-for="(attributes, index) in project.attributes">
            {{ attributes.map((person) => person.name).join(', ') }}
        </div>
    </div>

    <div class="hidden print:block">
        <h3 class="headline3 hidden print:block print:mb-2">{{ $t('Project properties') }}</h3>
        <div v-for="(category, index) in project.attributes" class="">
            <div v-if="category.length > 0">
                <h3 class="xsDark mb-2">{{ $t(index) }}</h3>
                <div class="flex items-center gap-4">
                    <div v-for="(attribute, index) in category" class="group block shrink-0 bg-white w-fit pr-3 rounded-full border border-gray-100">
                        <div class="flex items-center">
                            <div class="inline-block size-7 rounded-full" :style="{ backgroundColor: attribute.color}" />
                            <div class="mx-2">
                                <p class="xsDark group-hover:text-gray-900">{{ attribute.name }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>

<script setup>

const props = defineProps({
    project: {
        type: Object,
        required: true,
    }
})

</script>

<style scoped>

</style>