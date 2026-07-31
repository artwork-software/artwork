<template>
    <TrashSearchAndActions
        property-name="trashed_contacts"
        :total="trashed_contacts.total"
        @delete-all="showConfirmDeleteAll = true"
    />

    <div
        v-for="contact in trashed_contacts.data"
        :key="contact.id"
        class="flex items-center w-full bg-white my-2 border border-gray-200 rounded-lg px-4 py-3"
    >
        <img :src="contact.profile_photo_url" alt="" class="size-10 rounded-full object-cover shrink-0" />
        <div class="ml-4 min-w-0">
            <div class="font-medium text-gray-900 truncate">{{ contact.display_name }}</div>
            <div class="flex items-center gap-2 mt-0.5">
                <span
                    v-if="contact.contact_type"
                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                    :style="contact.contact_type.color
                        ? { backgroundColor: contact.contact_type.color + '15', color: contact.contact_type.color }
                        : { backgroundColor: '#eef2ff', color: '#3730a3' }"
                >
                    {{ $t(contact.contact_type.name) }}
                </span>
                <span class="text-xs text-gray-400">{{ $t('Deleted on') }} {{ contact.deleted_at }}</span>
            </div>
        </div>
        <div class="ml-auto flex items-center">
            <BaseMenu>
                <MenuItem v-slot="{ active }">
                    <Link
                        as="button"
                        method="patch"
                        :href="route('crm.contacts.restore', { id: contact.id })"
                        :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary',
                                 'group flex items-center px-4 py-2 w-full text-sm subpixel-antialiased']"
                    >
                        <RefreshIcon class="mr-3 h-5 w-5 text-primaryText group-hover:text-artwork-buttons-hover" aria-hidden="true" />
                        {{ $t('Restore') }}
                    </Link>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                    <Link
                        as="button"
                        method="delete"
                        :href="route('crm.contacts.force', { id: contact.id })"
                        :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary',
                                 'group flex items-center px-4 py-2 w-full text-sm subpixel-antialiased']"
                    >
                        <TrashIcon class="mr-3 h-5 w-5 text-primaryText group-hover:text-artwork-buttons-hover" aria-hidden="true" />
                        {{ $t('Delete permanently') }}
                    </Link>
                </MenuItem>
            </BaseMenu>
        </div>
    </div>

    <BasePaginator
        v-if="trashed_contacts.total > 0"
        :entities="trashed_contacts"
        property-name="trashed_contacts"
        class="mt-6"
    />

    <ConfirmDeleteModal
        v-if="showConfirmDeleteAll"
        :title="$t('Delete all')"
        :description="$t('Are you sure you want to permanently delete all items in the recycle bin for this category?')"
        @closed="showConfirmDeleteAll = false"
        @delete="forceDeleteAll"
    />
</template>

<script>
import AppLayout from "@/Layouts/AppLayout.vue";
import TrashLayout from "@/Layouts/TrashLayout.vue";
import {RefreshIcon} from "@heroicons/vue/solid";
import {TrashIcon} from "@heroicons/vue/outline";
import {MenuItem} from "@headlessui/vue";
import {Link} from "@inertiajs/vue3";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import TrashSearchAndActions from "@/Pages/Trash/Components/TrashSearchAndActions.vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";

export default {
    name: "CrmContacts",
    layout: [AppLayout, TrashLayout],
    props: ['trashed_contacts'],
    components: {
        BaseMenu,
        BasePaginator,
        TrashSearchAndActions,
        ConfirmDeleteModal,
        MenuItem,
        RefreshIcon,
        TrashIcon,
        Link,
    },
    data() {
        return {
            showConfirmDeleteAll: false,
        }
    },
    methods: {
        forceDeleteAll() {
            this.$inertia.delete(route('crm.contacts.force.all'), {
                onSuccess: () => {
                    this.showConfirmDeleteAll = false;
                }
            });
        },
    }
}
</script>
