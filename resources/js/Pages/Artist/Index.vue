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
                    <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                        <ArtworkBaseModalButton variant="primary" href="{{ route('artists.create') }}">
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
                                    <td class="py-4 pr-4 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-0 dark:text-white">{{ artist.name }}</td>
                                    <td class="p-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300">{{ artist.position }}</td>
                                    <td class="p-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300">{{ artist.phone_number }}</td>
                                    <td class="py-4 pr-4 pl-4 text-sm whitespace-nowrap text-gray-500 sm:pr-0 dark:text-gray-300">
                                        <a href="">
                                            <ToolTipComponent
                                                icon="IconEdit"
                                                :tooltip-text="$t('Edit artist')"
                                                direction="bottom"
                                            />
                                        </a>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </UserHeader>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import UserHeader from "@/Pages/Users/UserHeader.vue";
import ArtworkBaseButton from "@/Artwork/Buttons/ArtworkBaseButton.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";

const props = defineProps({
    artists: {
        type: Object,
        required: true,
        default: () => []
    }
})
</script>

<style scoped>

</style>