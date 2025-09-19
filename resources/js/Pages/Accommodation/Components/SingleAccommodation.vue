<template>
    <div class="flex w-full items-center">
        <img class="h-14 w-14 rounded-full object-cover flex-shrink-0 flex justify-start"
             :src="accommodation.profile_photo_url ?? accommodation.profile_image"
             alt=""/>
        <div class="ml-3 my-auto w-full justify-start mr-6">
            <div class="flex my-auto">
                <Link :href="route('accommodation.show', accommodation.id)"
                      class="mr-3 sDark">
                    <div class="w-full space-y-1">
                        <div class="font-lexend font-medium text-gray-900">{{ accommodation.name }}</div>
                        <div class="text-xs font-medium text-gray-900">{{ accommodation.street }}</div>
                        <div class="text-xs font-medium text-gray-900" v-if="accommodation.zip_code && accommodation.location">{{ accommodation.zip_code }}, {{ accommodation.location }}</div>
                        <div class="text-xs font-medium text-gray-900" v-if="accommodation.phone_number">{{ $t('Phone number')}}: {{ accommodation.phone_number }}</div>
                        <div class="text-xs font-medium text-gray-900" v-if="accommodation.email">{{ $t('Email')}}: {{ accommodation.email }}</div>
                    </div>
                </Link>
            </div>
        </div>
    </div>
    <div class="flex items-center">
        <BaseMenu has-no-offset white-menu-background>
            <BaseMenuItem @click="showCreateOrUpdateModal = true" title="Edit" :icon="IconEdit" white-menu-background/>
            <BaseMenuItem @click="showDeleteModal = true" title="Delete" :icon="IconTrash" white-menu-background/>
        </BaseMenu>
    </div>


    <UpdateOrCreateAccommodation
        v-if="showCreateOrUpdateModal"
        :room-types="roomTypes"
        @close="showCreateOrUpdateModal = false"
        :accommodation="accommodation"
    />

    <ArtworkBaseDeleteModal
        v-if="showDeleteModal"
        title="Delete accommodation"
        description="Are you sure you want to delete this accommodation?"
        @close="showDeleteModal = false"
        @delete="deleteAccommodation"
    />
</template>

<script setup>

import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import {Link, router} from "@inertiajs/vue3";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import {defineAsyncComponent, ref} from "vue";
import ArtworkBaseDeleteModal from "@/Artwork/Modals/ArtworkBaseDeleteModal.vue";
import {IconEdit, IconTrash} from "@tabler/icons-vue";

const props = defineProps({
    accommodation: {
        type: Object,
        required: true
    },
    roomTypes: {
        type: Object,
        required: false,
        default: () => []
    }
})

const showCreateOrUpdateModal = ref(false)
const showDeleteModal = ref(false)

const UpdateOrCreateAccommodation = defineAsyncComponent({
    loader: () => import('@/Pages/Accommodation/Components/UpdateOrCreateAccommodation.vue'),
    delay: 200,
    timeout: 3000,
})


const deleteAccommodation = () => {
    router.delete(route('accommodation.destroy', props.accommodation.id), {
        preserveState: true,
        onSuccess: () => {
            showDeleteModal.value = false
        },
        onError: () => {
            showDeleteModal.value = false
        }
    })
}

</script>

<style scoped>

</style>
