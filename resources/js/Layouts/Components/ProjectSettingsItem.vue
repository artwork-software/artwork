<template>
    <div class="mt-16 max-w-2xl">
        <h2 class="headline2 my-2">{{ props.title }}</h2>
        <div class="xsLight">
            {{ props.description }}
        </div>
    </div>
    <div class="mt-8 flex w-full flex-wrap">
        <div class="relative flex max-w-lg w-full">
            <input id="input" v-model="input" type="text" @keyup.enter="add"
                   class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                   placeholder="placeholder"/>
            <label for="input"
                   class="absolute left-0 -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">
                {{ props.inputLabel }}
            </label>
            <div class="m-2 -ml-8 -mt-1">
                <button
                    :class="[input === '' ? 'bg-secondary': 'bg-buttonBlue hover:bg-buttonHover focus:outline-none', 'rounded-full mt-2 ml-1 items-center text-sm p-1 border border-transparent uppercase shadow-sm text-secondaryHover']"
                    @click="add" :disabled="!input">
                    <CheckIcon class="h-5 w-5"></CheckIcon>
                </button>
            </div>
        </div>
        <div class="flex flex-wrap w-full max-w-xl">
            <div v-if="itemStyle === 'tag'">
                        <span v-for="item in props.items"
                              class="rounded-full items-center font-medium text-tagText
                                            border bg-tagBg border-tag px-3 mt-2 text-sm mr-1 mb-1 h-8 inline-flex">
                                            {{ item.name }}
                                            <button type="button" @click="emit('openDeleteModal', item)">
                                                <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
                                            </button>
                        </span>
            </div>
            <div v-else>
                <draggable ghost-class="opacity-50"
                           key="draggableKey"
                           item-key="draggableID" :list="items"
                           @start="dragging=true" @end="dragging=false"
                           @change="updateItemOrder(items)">
                    <template #item="{element}" :key="element.id">
                        <div class="flex"
                             @mouseover="showDeleteIcon = element.id"
                             @mouseout="showDeleteIcon = null"
                             :key="element.id"
                        >
                            <div class="mt-4 w-full"
                                 :class="dragging? 'cursor-grabbing' : 'cursor-grab'">
                                <div class="flex pr-12">
                                    <div class="flex -mt-1 items-center">
                                        <DotsVerticalIcon
                                            class="h-5 w-5 -mr-3.5 text-secondary"></DotsVerticalIcon>
                                        <DotsVerticalIcon
                                            class="h-5 w-5 text-secondary"></DotsVerticalIcon>
                                    </div>
                                    <p class="ml-4 my-auto text-lg font-black">
                                        {{ element.name }}</p>
                                    <button v-show="showDeleteIcon === element.id" type="button" class="ml-6" @click="emit('openDeleteModal', element)">
                                        <XCircleIcon class="h-4 w-4 hover:text-error" />
                                    </button>
                                </div>
                            </div>

                        </div>
                    </template>
                </draggable>
            </div>

        </div>
    </div>
</template>

<script setup>
import {XIcon, XCircleIcon} from "@heroicons/vue/outline"
import {CheckIcon, DotsVerticalIcon} from "@heroicons/vue/solid";
import {ref} from "vue";
import draggable from "vuedraggable";
import {useForm} from "@inertiajs/inertia-vue3";

const props = defineProps({
    title: String,
    description: String,
    items: Array,
    inputLabel: String,
    itemStyle: {
        type: String,
        default: 'tag'
    }
})

const emit = defineEmits(['openDeleteModal', 'add'])

const showDeleteIcon = ref(null)
const input = ref('')
const dragging = ref(false)

const itemOrderForm = useForm({
    headlines: []
})

const add = () => {
    emit('add', input.value)
    input.value = ''
}

const updateItemOrder = (items) => {
    items.map((item, index) => {
        item.order = index + 1
    })

    itemOrderForm.headlines = items

    itemOrderForm.put('/project_headlines/order')
}

</script>

<style scoped>

</style>
