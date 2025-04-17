<template>
    <UserHeader title="Accommodations" description="Manage your accommodations.">

        <template #tabBar>
            <ArtworkBaseButton size="sm" variant="primary" type="button" @click="showCreateOrUpdateModal = true">
                {{ $t('Create new Accommodation') }}
            </ArtworkBaseButton>
        </template>


        <template #default>
            <ul role="list" class="mt-6 w-full">
                <li v-if="accommodations.length > 0" v-for="(accommodation,index) in accommodations" :key="accommodation.id" class="py-6 flex justify-between w-full">
                    <SingleAccommodation :accommodation="accommodation" />
                </li>
            </ul>
        </template>
    </UserHeader>

    <UpdateOrCreateAccommodation
        v-if="showCreateOrUpdateModal"
        @close="showCreateOrUpdateModal = false"
    />
</template>

<script setup>

import UserHeader from "@/Pages/Users/UserHeader.vue";
import {defineAsyncComponent, ref} from "vue";

const props = defineProps({
    accommodations: {
        type: Object,
        required: true,
        default: []
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