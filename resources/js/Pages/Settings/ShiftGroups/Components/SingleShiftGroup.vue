<template>
    <div class="">
        <div class="flex items-center gap-x-4">
            <div
                class="mt-0.5 rounded-lg p-2 ring-1 ring-inset"
                :style="{ backgroundColor: (shiftGroup.color ?? '#9E1C60') + '20', '--tw-ring-color': (shiftGroup.color ?? '#9E1C60') + '70', color: shiftGroup.color ?? '#9E1C60' }"
            >
                <PropertyIcon
                    stroke-width="1.5"
                    class="size-7"
                    :name="shiftGroup.icon ?? 'IconBan'"
                />
            </div>

            <div class="flex items-center gap-x-2">
                <span
                    class="inline-flex size-2.5 shrink-0 rounded-full"
                    :style="{ backgroundColor: shiftGroup.color ?? '#9E1C60' }"
                    :title="shiftGroup.color"
                />
                {{ shiftGroup.name }}
            </div>
        </div>
    </div>
    <div class="">
        <BaseMenu white-menu-background has-no-offset >
            <BaseMenuItem title="Edit" icon="IconEdit" @click="showCreateOrUpdateShiftGroupModal = true" white-menu-background />
            <BaseMenuItem title="Delete" icon="IconTrash"  white-menu-background @click="showDeleteShiftGroupModal = true" />
        </BaseMenu>
    </div>

    <CreateOrUpdateShiftGroupModal
        v-if="showCreateOrUpdateShiftGroupModal"
        @close="showCreateOrUpdateShiftGroupModal = false"
        :shift-group="shiftGroup"
    />

    <ArtworkBaseDeleteModal
        :title="$t('Delete Shift Group')"
        :description="$t('Are you sure you want to delete the shift group {0}? This action cannot be undone.', [shiftGroup.name])"
        v-if="showDeleteShiftGroupModal"
        @close="showDeleteShiftGroupModal = false"
        @delete="deleteShiftGroup"
    />
</template>

<script setup>
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import {defineAsyncComponent, ref} from "vue";
import {router} from "@inertiajs/vue3";
import ArtworkBaseDeleteModal from "@/Artwork/Modals/ArtworkBaseDeleteModal.vue";

const props = defineProps({
    shiftGroup: {
        type: Object,
        required: true,
        default: () => ({
            id: null,
            name: '',
            color: '#9E1C60',
            icon: 'IconBan',
        }),
    }
})

const showCreateOrUpdateShiftGroupModal = ref(false);
const showDeleteShiftGroupModal = ref(false);

const CreateOrUpdateShiftGroupModal = defineAsyncComponent({
    loader: () => import('@/Pages/Settings/ShiftGroups/Components/CreateOrUpdateShiftGroupModal.vue'),
    delay: 200,
})

const deleteShiftGroup = () => {
    router.delete(route('shift-groups.destroy', {
        shiftGroup: props.shiftGroup.id
    }), {
        onSuccess: () => {
            showDeleteShiftGroupModal.value = false;
        }
    });
}
</script>
<style scoped>

</style>
