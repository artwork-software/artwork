<template>
    <div v-if="list.length > 0" v-for="item in list"
         class="flex w-full mb-2">
        <input type="checkbox" v-model="item.checked"
               @change="$emit('change-filter-items', { list: list, item: item, filterName: filterName})"
               class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
        <p :class="[item.checked ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
           class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
            {{ item.name }}</p>
    </div>
    <div v-else class="text-secondary">{{ textIfEmpty }}</div>
</template>

<script>
import Permissions from "@/mixins/Permissions.vue";

export default {
    name: "BaseFilterCheckboxList",
    mixins: [Permissions],
    props: {
        list: Array,
        textIfEmpty: String,
        filterName: String
    },
    emits: ['change-filter-items']
}
</script>

<style scoped>

</style>
