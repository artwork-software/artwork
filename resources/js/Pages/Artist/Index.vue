<template>
    <UserHeader title="Artists" description="All the artists in the system">

        <template #tabBar>

        </template>


        <template #default>
            <div class="">
                <div class="sm:flex sm:items-center">
                    <div class="sm:flex-auto">
                        <h1 class="text-base font-semibold text-gray-900 dark:text-white">{{ $t('Artists')}}</h1>
                        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                            {{ $t('A list of all the artists in the system including their name, title and more') }}
                        </p>
                    </div>
                    <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none flex items-center gap-x-4">
                        <ToolTipComponent
                            icon="IconFileExport"
                            :tooltip-text="$t('Export artists')"
                            direction="bottom"
                            @click="exportArtist"
                        />
                        <ArtworkBaseModalButton variant="primary" @click="showCreateOrUpdateArtistModal = true">
                            {{ $t('Add artist') }}
                        </ArtworkBaseModalButton>
                    </div>
                </div>
                <div class="mt-8 flow-root">
                    <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                            <table class="min-w-full divide-y divide-gray-300 dark:divide-white/15">
                                <thead>
                                <tr class="divide-x divide-gray-200 dark:divide-white/10">
                                    <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-0 dark:text-white">
                                        {{  $t('Name artist') }}</th>
                                    <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">{{ $t('Position') }}</th>
                                    <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">{{ $t('phone number') }}</th>
                                    <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pr-0 dark:text-white">{{ $t('Actions')}}</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-white/10 dark:bg-gray-900">
                                <tr v-for="artist in artists" :key="artist.id" class="divide-x divide-gray-200 dark:divide-white/10">
                                    <SingleArtistInTable :artist="artist" />
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <CreateOrUpdateArtistModal
                v-if="showCreateOrUpdateArtistModal"
                @close="showCreateOrUpdateArtistModal = false"
            />


        </template>
    </UserHeader>
</template>

<script setup>
import UserHeader from "@/Pages/Users/UserHeader.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import {defineAsyncComponent, ref} from "vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";

const props = defineProps({
    artists: {
        type: Object,
        required: true,
        default: () => []
    }
})

const showCreateOrUpdateArtistModal = ref(false)

const SingleArtistInTable = defineAsyncComponent({
    loader: () => import('@/Pages/Artist/Components/SingleArtistInTable.vue'),
    delay: 200,
    timeout: 3000,
})

const CreateOrUpdateArtistModal = defineAsyncComponent({
    loader: () => import('@/Pages/Artist/Components/CreateOrUpdateArtistModal.vue'),
    delay: 200,
    timeout: 3000,
})

const exportArtist = () => {
    window.open(
        route(
            'artist.export',
        ),
        '_blank',
        'noopener'
    );
}
</script>

<style scoped>

</style>