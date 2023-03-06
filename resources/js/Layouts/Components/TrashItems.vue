<template>
    <div v-if="items.length > 0" v-for="(item,index) in items" :key="item.id"
         class="border-b-2 border-gray-200 w-full">
        <div
            class="py-5 flex items-center">
            <BaseFilterTag :filter="item.name" @remove-filter="forceDelete(item)" />
            <div class="ml-2">{{type}}</div>
        </div>
    </div>
</template>

<script setup>

import BaseFilterTag from "@/Layouts/Components/BaseFilterTag.vue";

const props = defineProps({
    items: Array,
    type: String,
    model: String
})

const forceDelete = (item) => {
    console.log("Force deleting " + item.name)
    axios.delete(route(`${props.model}.force`, { id: item.id}))
}

</script>

<style scoped>

</style>
