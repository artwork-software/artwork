<template>
    <div class="absolute rounded-lg h-full w-full items-center justify-center hidden group-hover:flex bg-black/40">
        <div class="flex items-center justify-center gap-x-2">
            <div class="rounded-full p-2 bg-artwork-buttons-create cursor-pointer" @click="showEditComponentModal = true">
                <IconEdit class="text-white h-4 w-4" />
            </div>
            <div class="rounded-full p-2 bg-red-600 cursor-pointer" v-if="!component.special" @click="showConfirmDeleteModal = true">
                <IconTrash class="text-white h-4 w-4" />
            </div>
        </div>
    </div>
    <div class="flex items-center justify-center mb-2">
        <ComponentIcons :type="component.type" />
    </div>
    <div class="text-center text-sm font-bold w-20">
         <div v-if="component.special" class="truncate">
                {{ $t(component.name) }}
         </div>
        <div v-else class="w-20 truncate">
            {{ component.name }}
            <div class="text-[8px] text-gray-500 font-light truncate" v-if="component.data.height">
                {{ component.data.height }} Pixel <span v-if="component.data.showLine === true">| {{ $t('Show a separator line')}}</span>
            </div>
            <div class="text-[8px] text-gray-500 font-light truncate" v-if="component.data.title_size">
                {{ component.data.title_size }} Pixel
            </div>
        </div>
    </div>
    <ConfirmDeleteModal
        v-if="showConfirmDeleteModal"
        @closed="showConfirmDeleteModal = false"
        @delete="deleteComponent"
        :title="$t('Do you really want to delete the component {0}?', [component.name])"
        :description="$t('This action cannot be undone. Do you really want to delete this component? This will also delete all data associated with this component.')"
    />
    <ComponentModal v-if="showEditComponentModal"
                    :show="showEditComponentModal"
                    mode="edit"
                    :component-to-edit="component"
                    @close="showEditComponentModal = false"
    />
</template>

<script>
import ComponentIcons from "@/Components/Globale/ComponentIcons.vue";
import IconLib from "@/mixins/IconLib.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import ComponentModal from "@/Pages/Settings/ComponentManagement/Components/ComponentModal.vue";

export default {
    name: "SingleComponent",
    mixins: [IconLib],
    components: {
        ComponentModal,
        ConfirmDeleteModal,
        ComponentIcons
    },
    props: ['component'],
    data() {
        return {
            showEditComponentModal: false,
            showConfirmDeleteModal: false,
        }
    },
    methods: {
        deleteComponent() {
            this.$inertia.delete(route('component.destroy', {component: this.component.id}), {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    this.showConfirmDeleteModal = false;
                }
            })
        }
    }
}
</script>
