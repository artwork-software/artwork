<template>
    <div v-if="multiEdit">
        <div class="flex items-center mx-2">
            <input v-model="item.checked" aria-describedby="comments-description" name="comments" type="checkbox" class="border-gray-300 text-green-600 focus:ring-green-600 h-4 w-4" />
        </div>
    </div>
    <div class="drag-item w-48 p-2 bg-gray-50/10 text-white text-xs rounded-lg flex items-center gap-2" draggable="true" @dragstart="onDragStart">
        <div class="cursor-pointer w-full">
            <div class="w-full flex items-center justify-between h-8">
                <div>
                    {{ item?.name }}
                </div>
                <div class="text-[9px] bg-gray-100/10 rounded-full px-2 py-1">
                    {{ item?.count }}
                </div>
            </div>
        </div>
    </div>

</template>

<script setup>


const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    multiEdit: {
        type: Boolean,
        required: false,
        default: false
    }
})


const onDragStart = (event) => {
    const transferItem = {
        id: props.item.id,
        name: props.item.name,
    }
    event.dataTransfer.setData('application/json', JSON.stringify(transferItem));
}

</script>

<style scoped>

</style>
