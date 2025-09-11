<template>
    <UserHeader title="Accommodations" description="Manage your accommodations.">

        <template #tabBar>
            <ArtworkBaseModalButton size="sm" variant="primary" type="button" @click="showCreateOrUpdateModal = true">
                {{ $t('Create new Accommodation') }}
            </ArtworkBaseModalButton>
        </template>


        <template #default>
            <ul role="list" class="mt-6 w-full">
                <li v-if="accommodations.length > 0" v-for="(accommodation,index) in accommodations" :key="accommodation.id" class="py-6 flex justify-between w-full">
                    <SingleAccommodation :accommodation="accommodation" :room-types="roomTypes"/>
                </li>
            </ul>
        </template>


    </UserHeader>

    <UpdateOrCreateAccommodation
        v-if="showCreateOrUpdateModal"
        :room-types="roomTypes"
        @close="showCreateOrUpdateModal = false"
    />
</template>

<script setup>

import UserHeader from "@/Pages/Users/UserHeader.vue";
import {defineAsyncComponent, ref} from "vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";

const props = defineProps({
    accommodations: {
        type: Object,
        required: true,
        default: []
    },
    roomTypes: {
        type: Object,
        required: false,
        default: () => []
    }
})


const showCreateOrUpdateModal = ref(false)
const UpdateOrCreateAccommodation = defineAsyncComponent({
    loader: () => import('@/Pages/Accommodation/Components/UpdateOrCreateAccommodation.vue'),
    delay: 200,
    timeout: 3000,
})

const SingleAccommodation = defineAsyncComponent({
    loader: () => import('@/Pages/Accommodation/Components/SingleAccommodation.vue'),
    delay: 200,
    timeout: 3000,
})

const ArtworkBaseButton = defineAsyncComponent({
    loader: () => import('@/Artwork/Buttons/ArtworkBaseButton.vue'),
    delay: 200,
    timeout: 3000,
})
</script>


<style scoped>

</style>
