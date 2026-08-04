<template>
    <div class="rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5">
        <div class="">
            <BasePageTitle :title="title" :description="description" />
        </div>
        <div class="mt-8 flex items-center w-full flex-wrap gap-x-2">
            <div class="justify-content-center relative items-center flex cursor-pointer rounded-full focus:outline-none">
                <ColorPickerComponent @update-color="UpdateColor"  />
            </div>

            <div class="relative flex max-w-lg w-full">
                <BaseInput :id="inputLabel" v-model="input" :label="inputLabel" @keyup.enter="add" />

            </div>
            <div class="">
                <BaseUIButton variant="primary" hide-icon @click="add" :disabled="!input">
                    <component :is="IconCirclePlus" class="size-6" stroke-width="1"/>
                    {{ $t('Add') }}
                </BaseUIButton>
                <!--<button text="Save" :icon="IconCheck" @click="add" :disabled="!input"/>-->
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
import {ref} from "vue";
import draggable from "vuedraggable";
import {useForm} from "@inertiajs/vue3";
import ColorPickerComponent from "@/Components/Globale/ColorPickerComponent.vue";
import EditableTagComponent from "@/Components/Tags/EditableTagComponent.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import {IconCheck, IconCirclePlus, IconCircleX, IconDotsVertical, IconPencil, IconX} from "@tabler/icons-vue";
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

export default {
    name: "ProjectSettingsItem",
    components: {
        BaseUIButton,
        BasePageTitle,
        BaseInput,
        TextInputComponent,
        EditableTagComponent,
        ColorPickerComponent,
        draggable,
        IconX,
        IconCircleX,
        IconPencil,
        IconCheck,
        IconDotsVertical
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
        IconCirclePlus,
        IconCheck,
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
