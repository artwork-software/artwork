<template>
    <UserHeader title="Artists" description="All the artists in the system">

        <template #tabBar>
            <ToolbarHeader
                :icon="IconPalette"
                title="Artists"
                icon-bg-class="bg-zinc-600/10 text-zinc-700"
                :description="artists?.length ? `${artists?.length} ${$t('Artists')}` : ''"
                :search-enabled="false"
            >
                <template #actions>
                    <div class="ui-button">
                        <ToolTipComponent
                            :icon="IconFileExport"
                            :tooltip-text="$t('Export artists')"
                            direction="bottom"
                            @click="exportArtist"
                            icon-size="size-5"
                        />
                    </div>
                    <button class="ui-button-add" @click="showCreateOrUpdateArtistModal = true">
                        <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                        {{ $t('Add artist') }}
                    </button>
                </template>
            </ToolbarHeader>
        </template>


        <template #default>
            <BaseTable
                :rows="artists"
                :columns="cols"
                row-key="id"
                v-model:page="page"
                empty-title="Keine Künstler"
                empty-message="Derzeit sind keine Einträge vorhanden."
            >

                <!-- Name (Avatar + Name + Email) -->
                <template #cell-name="{ row }">
                    <div class="flex items-center">
                        <div class="size-11 shrink-0">
                            <img :src="row.profile_photo_url" alt="" class="size-11 rounded-full object-cover" />
                        </div>
                        <div class="ml-4">
                            <div class="font-medium text-gray-900">{{ row.name }}</div>
                            <div class="mt-1 text-gray-500">{{ row.email }}</div>
                        </div>
                    </div>
                </template>

                <!-- Actions -->
                <template #row-actions="{ row }">
                    <BaseMenu has-no-offset white-menu-background>
                        <BaseMenuItem :icon="IconEdit" title="Edit" white-menu-background  @click="openEditModal(row)" />
                        <BaseMenuItem :icon="IconTrash" title="Delete" white-menu-background  @click="openDeleteModal(row)" />
                    </BaseMenu>
                </template>
            </BaseTable>
            <!--<div class="">
                <div class="sm:flex sm:items-center">
                    <div class="sm:flex-auto">
                        <h1 class="text-base font-semibold text-gray-900 dark:text-white">{{ $t('Artists')}}</h1>
                        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                            {{ $t('A list of all the artists in the system including their name, title and more') }}
                        </p>
                    </div>
                    <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none flex items-center gap-x-4">
                        <ToolTipComponent
                            :icon="IconFileExport"
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
            </div>-->

            <CreateOrUpdateArtistModal
                v-if="showCreateOrUpdateArtistModal"
                @close="closeAddEditArtistModal"
                :artist="artist"
            />

            <ConfirmDeleteModal
                v-if="showConfirmDeleteModal"
                :title="$t('Delete artist')"
                :description="$t('Are you sure you want to delete this artist?')"
                @closed="showConfirmDeleteModal = false"
                @delete="deleteArtist"
            />


        </template>
    </UserHeader>
</template>

<script setup lang="ts">
import UserHeader from "@/Pages/Users/UserHeader.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import {defineAsyncComponent, nextTick, ref} from "vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import {IconPalette, IconCirclePlus, IconFileExport, IconEdit, IconTrash} from "@tabler/icons-vue";
import ToolbarHeader from "@/Artwork/Toolbar/ToolbarHeader.vue";
import {Link, router} from "@inertiajs/vue3";
import BaseTable, { type TableColumn } from '@/Artwork/Table/BaseTable.vue'
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";

const props = defineProps({
    artists: {
        type: Object,
        required: true,
        default: () => []
    }
})

const cols = ref<TableColumn[]>([
    { key: 'name',  label: 'Name',  sortable: false },
    { key: 'civil_name', label: 'Civil name', sortable: false },
    { key: 'phone_number', label: 'Phone', sortable: false },
    { key: 'position', label: 'Position', sortable: false },
])

const page = ref(1)
const artist = ref([] as any)
const showConfirmDeleteModal = ref(false)

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

const openEditModal = (acc: Object) => {
    artist.value = acc
    showCreateOrUpdateArtistModal.value = false
    nextTick(() => {
        showCreateOrUpdateArtistModal.value = true
    })
}

const openDeleteModal = (acc: Object) => {
    artist.value = acc
    showConfirmDeleteModal.value = false
    nextTick(() => {
        showConfirmDeleteModal.value = true
    })
}

const deleteArtist = () => {
    router.delete(route('artist.destroy', artist.value.id), {
        preserveState: true,
        onSuccess: () => {
            showConfirmDeleteModal.value = false
            artist.value = []
        },
        onError: () => {
            showConfirmDeleteModal.value = false
            artist.value = []
        }
    })
}

const closeAddEditArtistModal = () => {
    showCreateOrUpdateArtistModal.value = false
    artist.value = []
}
</script>

<style scoped>

</style>
