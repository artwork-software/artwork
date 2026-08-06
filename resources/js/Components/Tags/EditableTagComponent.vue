<script>
import {IconX} from "@tabler/icons-vue";
import EditProjectSettingsModal from "@/Layouts/Components/EditProjectSettingsModal.vue";
import {useColorHelper} from "@/Composeables/UseColorHelper.js";

export default {
    name: "EditableTagComponent",
    components: {EditProjectSettingsModal, IconX},
    emits: ['openDeleteModal' , 'openEditModal'],
    setup() {
        const {backgroundColorWithOpacityOld: backgroundColorWithOpacity, TextColorWithDarken} = useColorHelper();
        return {backgroundColorWithOpacity, TextColorWithDarken};
    },
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
            <IconX class="ml-1 h-4 w-4 hover:text-danger "/>
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
