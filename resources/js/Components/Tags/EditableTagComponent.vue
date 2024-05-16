<script>
import {XIcon} from "@heroicons/vue/outline";
import EditProjectSettingsModal from "@/Layouts/Components/EditProjectSettingsModal.vue";
import ColorHelper from "@/Mixins/ColorHelper.vue";

export default {
    name: "EditableTagComponent",
    components: {EditProjectSettingsModal, XIcon},
    emits: ['openDeleteModal' , 'openEditModal'],
    mixins: [ColorHelper],
    data(){
        return {
            showEditModal: false,
        }
    },
    props: {
        item: Object
    },
    methods: {
        update(itemCopy) {
            this.$emit('openEditModal', itemCopy)
        }
    },
}
</script>

<template>
    <span class="rounded-full items-center font-medium border px-3 mt-2 text-sm mr-1 mb-1 h-8 inline-flex" :style="{backgroundColor: backgroundColorWithOpacity(item.color), color: TextColorWithDarken(item.color), borderColor: TextColorWithDarken(item.color)}">
        <span class="cursor-pointer" @click="showEditModal = true">
            {{ item.name }}
        </span>

        <button type="button" @click="$emit('openDeleteModal', item)">
            <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
        </button>
    </span>

    <EditProjectSettingsModal
        v-if="showEditModal"
        @close="showEditModal = false"
        :item="item"
        :title="$t('Edit')"
        :description="$t('')"
        @update="update"
    />
</template>

<style scoped>

</style>
