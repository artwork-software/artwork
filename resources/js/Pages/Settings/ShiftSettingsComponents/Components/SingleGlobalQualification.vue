<template>
    <div class="">
        <div class="flex items-center gap-x-4">
            <div class="mt-0.5 rounded-lg bg-zinc-50 p-2 ring-1 ring-inset ring-zinc-200">
                <PropertyIcon
                    stroke-width="1.5"
                    class="text-black size-7"
                    :name="globalQualification.icon"
                />
            </div>

            {{ globalQualification.name }}
        </div>
    </div>
    <div class="">
        <BaseMenu white-menu-background has-no-offset >
            <BaseMenuItem title="Edit" icon="IconEdit" @click="showCreateOrUpdateGlobalQualificationModal = true" white-menu-background />
            <BaseMenuItem title="Delete" icon="IconTrash"  white-menu-background @click="showDeleteGlobalQualificationModal = true" />
        </BaseMenu>
    </div>

    <CreateOrUpdateGlobalQualificationModal
        v-if="showCreateOrUpdateGlobalQualificationModal"
        @close="showCreateOrUpdateGlobalQualificationModal = false"
        :global-qualification="globalQualification"
    />

    <ArtworkBaseDeleteModal
        :title="$t('Delete global qualification')"
        :description="$t('Are you sure you want to delete the global qualification {0}? This action cannot be undone.', [globalQualification.name])"
        v-if="showDeleteGlobalQualificationModal"
        @close="showDeleteGlobalQualificationModal = false"
        @delete="deleteGlobalQualification"
    />
</template>

<script setup>
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import {defineAsyncComponent, ref} from "vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import {router} from "@inertiajs/vue3";
import ArtworkBaseDeleteModal from "@/Artwork/Modals/ArtworkBaseDeleteModal.vue";

const props = defineProps({
    globalQualification: {
        type: Object,
        required: true,
        default: () => ({
            id: null,
            name: '',
            icon: 'default-icon',
        }),
    }
})

const showCreateOrUpdateGlobalQualificationModal = ref(false);
const showDeleteGlobalQualificationModal = ref(false);

const CreateOrUpdateGlobalQualificationModal = defineAsyncComponent({
    loader: () => import('@/Pages/Settings/ShiftSettingsComponents/Components/CreateOrUpdateGlobalQualificationModal.vue'),
    delay: 200,
})

const deleteGlobalQualification = () => {
    router.delete(route('global-qualification.delete', {
        globalQualification: props.globalQualification.id
    }), {
        onSuccess: () => {
            showDeleteGlobalQualificationModal.value = false;
        }
    });
}
</script>
<style scoped>

</style>
