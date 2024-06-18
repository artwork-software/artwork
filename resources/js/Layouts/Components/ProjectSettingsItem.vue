<template>
    <div class="mt-16 max-w-2xl">
        <h2 class="headline2 my-2">{{ title }}</h2>
        <div class="xsLight">
            {{ description }}
        </div>
    </div>
    <div class="mt-8 flex w-full flex-wrap gap-x-1">
        <div class="justify-content-center relative items-center flex cursor-pointer rounded-full focus:outline-none">
            <ColorPickerComponent @update-color="UpdateColor"  />
        </div>

        <div class="relative flex max-w-lg w-full">
            <input :id="inputLabel" v-model="input" type="text" @keyup.enter="add" class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent" placeholder="placeholder"/>
            <label :for="inputLabel" class="absolute left-0 -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">
                {{ inputLabel }}
            </label>
            <div class="m-2 -ml-8 -mt-1">
                <button
                    :class="[input === '' ? 'bg-secondary': 'bg-artwork-buttons-create hover:bg-artwork-buttons-hover focus:outline-none', 'rounded-full mt-2 ml-1 items-center text-sm p-1 border border-transparent uppercase shadow-sm text-white']"
                    @click="add" :disabled="!input">
                    <CheckIcon class="h-5 w-5"></CheckIcon>
                </button>
            </div>
        </div>
        <div class="flex flex-wrap w-full max-w-xl mt-2">
            <div v-if="itemStyle === 'tag'">
                <EditableTagComponent v-for="item in itemsWithColor" :key="item.id" :item="item" @openDeleteModal="$emit('openDeleteModal', item)" @openEditModal="update" />
            </div>
        </div>
    </div>
</template>

<script>
import {XIcon, XCircleIcon, PencilIcon} from "@heroicons/vue/outline"
import {CheckIcon, DotsVerticalIcon} from "@heroicons/vue/solid";
import {ref} from "vue";
import draggable from "vuedraggable";
import {useForm} from "@inertiajs/vue3";
import ColorPickerComponent from "@/Components/Globale/ColorPickerComponent.vue";
import EditableTagComponent from "@/Components/Tags/EditableTagComponent.vue";

export default {
    name: "ProjectSettingsItem",
    components: {
        EditableTagComponent,
        ColorPickerComponent,
        draggable,
        XIcon,
        XCircleIcon,
        PencilIcon,
        CheckIcon,
        DotsVerticalIcon
    },
    props: {
        title: String,
        description: String,
        items: Array,
        inputLabel: String,
        itemStyle: {
            type: String,
            default: 'tag'
        }
    },
    emits: ['openEditModal', 'openDeleteModal', 'add'],
    data() {
        return {
            showEditIcons: null,
            input: '',
            dragging: false,
            hex_code: '#ffffff',
        }
    },
    computed: {
        itemsWithColor(){
            return this.items.map(item => {
                return {
                    ...item,
                    color: item.color ? item.color : '#ffffff'
                }
            })
        }
    },
    methods: {
        add() {
            this.$emit('add', this.input, this.hex_code)
            this.input = ''
        },
        updateItemOrder(items) {
            items.map((item, index) => {
                item.order = index + 1
            })

            this.itemOrderForm.headlines = items

            this.itemOrderForm.put('/project_headlines/order')
        },
        UpdateColor(color) {
            this.hex_code = color
        },
        update(itemCopy) {
            this.$emit('openEditModal', itemCopy)
        }
    },
}


</script>
<style>
input[type=color] {
    border-radius: 100%;
    border: 1px solid transparent;
}

input[type=color]::-webkit-color-swatch {
    border-radius: 100%;
    border: 1px solid transparent;
}

input[type=color]::-webkit-color-swatch-wrapper {
    border-radius: 100%;
    border: 1px solid transparent;
}

</style>
