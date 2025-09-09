<template>
    <td class="py-4 pr-4 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-0 dark:text-white">{{ artist.name }}</td>
    <td class="p-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300">{{ artist.position }}</td>
    <td class="p-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300">{{ artist.phone_number }}</td>
    <td class="py-4 pr-4 pl-4 text-sm whitespace-nowrap text-gray-500 sm:pr-0 dark:text-gray-300 flex items-center gap-x-2">
        <button @click="showCreateOrUpdateArtistModal = true">
            <ToolTipComponent
                icon="IconEdit"
                :tooltip-text="$t('Edit artist')"
                direction="bottom"
            />
        </button>
        <button @click="showConfirmDeleteModal = true">
            <ToolTipComponent
                icon="IconTrash"
                :tooltip-text="$t('Delete artist')"
                direction="bottom"
            />
        </button>
    </td>

    <CreateOrUpdateArtistModal
        v-if="showCreateOrUpdateArtistModal"
        @close="showCreateOrUpdateArtistModal = false"
        :artist="artist"
    />

    <ConfirmDeleteModal
        v-if="showConfirmDeleteModal"
        title="Delete artist"
        description="Are you sure you want to delete this artist?"
        @closed="showConfirmDeleteModal = false"
        @delete="deleteArtist"
    />
</template>

<script setup lang="ts">
import {router} from "@inertiajs/vue3";
import {defineAsyncComponent, ref} from "vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import type { Artist } from "@/Pages/Artist/types/Artist";

const props = defineProps<{
    artist?: Artist;
}>()

const showCreateOrUpdateArtistModal = ref(false)
const showConfirmDeleteModal = ref(false)

const CreateOrUpdateArtistModal = defineAsyncComponent({
    loader: () => import('@/Pages/Artist/Components/CreateOrUpdateArtistModal.vue'),
    delay: 200,
    timeout: 3000,
})

const ConfirmDeleteModal = defineAsyncComponent({
    loader: () => import('@/Layouts/Components/ConfirmDeleteModal.vue'),
    delay: 200,
    timeout: 3000,
})

const deleteArtist = () => {
    router.delete(route('artist.destroy', props.artist?.id), {
        preserveScroll: true,
        onSuccess: () => {
            showConfirmDeleteModal.value = false
        }
    })
}
</script>

<style scoped>

</style>