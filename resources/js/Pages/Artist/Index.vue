<template>
    <UserHeader title="Artists" description="All the artists in the system">

        <template #tabBar>
            <ToolbarHeader
                :icon="IconPalette"
                title="Artists"
                band
                :description="artists?.length ? `${artists?.length} ${$t('Artists')}` : ''"
                :search-enabled="false"
            >
                <template #actions>
                    <ToolTipComponent
                        :icon="IconFileExport"
                        :tooltip-text="$t('Export artists')"
                        direction="bottom"
                        @click="exportArtist"
                        icon-size="size-5"
                        white-icon
                        classes-button="select-none size-[30px] min-h-0 p-0 inline-flex items-center justify-center rounded-md bg-white/8 hover:bg-white/16 cursor-pointer transition-[background-color] duration-150 ease-out"
                    />
                    <BaseUIButton variant="primary" on-band hide-icon @click="showCreateOrUpdateArtistModal = true">
                        <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                        {{ $t('Add artist') }}
                    </BaseUIButton>
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
                            <div class="font-medium text-text">{{ row.name }}</div>
                            <div class="mt-1 text-text-subtle">{{ row.email }}</div>
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
                        <h1 class="text-base font-semibold text-text">{{ $t('Artists')}}</h1>
                        <p class="mt-2 text-sm text-text-muted">
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
                        <BaseUIButton variant="primary" hide-icon @click="showCreateOrUpdateArtistModal = true">
                            {{ $t('Add artist') }}
                        </BaseUIButton>
                    </div>
                </div>
                <div class="mt-8 flow-root">
                    <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                            <table class="min-w-full divide-y divide-border">
                                <thead>
                                <tr class="divide-x divide-border-subtle">
                                    <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-text sm:pl-0">
                                        {{  $t('Name artist') }}</th>
                                    <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-text">{{ $t('Position') }}</th>
                                    <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-text">{{ $t('phone number') }}</th>
                                    <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-text sm:pr-0">{{ $t('Actions')}}</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-border-subtle bg-white">
                                <tr v-for="artist in artists" :key="artist.id" class="divide-x divide-border-subtle">
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
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
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
    { key: 'first_name', label: 'First name', sortable: false },
    { key: 'last_name', label: 'Last name', sortable: false },
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
