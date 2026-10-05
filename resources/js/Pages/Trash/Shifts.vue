<template>
    <TrashSearchAndActions
        property-name="trashed_shifts"
        :total="trashed_shifts.total"
        @delete-all="showConfirmDeleteAll = true"
    />

    <p v-if="trashed_shifts.total === 0" class="mt-6 text-sm text-text-subtle">
        {{ $t('There are no deleted shifts in the recycle bin.') }}
    </p>

    <div
        v-for="shift in trashed_shifts.data"
        :key="shift.id"
        class="flex items-center w-full bg-white my-2 border border-border-subtle rounded-lg px-4 py-3"
    >
        <div
            class="w-1.5 self-stretch rounded-full shrink-0"
            :style="{ backgroundColor: shift.craft?.color ?? '#a3a3a3' }"
        />
        <div class="ml-4 min-w-0">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <span class="font-medium text-text">{{ shift.date }}</span>
                <span class="text-text-muted">{{ shift.time }}</span>
                <span
                    v-if="shift.craft"
                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                    :style="{ backgroundColor: (shift.craft.color ?? '#3730a3') + '15', color: shift.craft.color ?? '#3730a3' }"
                    :title="shift.craft.name"
                >
                    {{ shift.craft.abbreviation || shift.craft.name }}
                </span>
                <span
                    v-if="shift.is_committed"
                    class="inline-flex items-center gap-1 rounded-full border border-border-subtle px-2 py-0.5 text-[11px] font-medium text-text-subtle"
                >
                    <IconLock class="size-3" />
                    {{ $t('Committed') }}
                </span>
            </div>
            <div class="mt-0.5 text-sm text-text-muted truncate">
                <span>{{ shift.room_name ?? $t('No room') }}</span>
                <span v-if="shift.room_trashed" class="text-danger"> ({{ $t('room in recycle bin') }})</span>
                <template v-if="shift.project_name">
                    <span class="text-text-subtle"> · </span>
                    <span>{{ shift.project_name }}</span>
                </template>
                <template v-if="shift.description">
                    <span class="text-text-subtle"> · </span>
                    <span>{{ shift.description }}</span>
                </template>
            </div>
            <div class="mt-0.5 text-xs text-text-subtle">
                {{ $t('Deleted on') }} {{ shift.deleted_at }}
                <template v-if="shift.worker_count > 0">
                    · {{ $t('{count} assigned persons are restored as well', { count: shift.worker_count }) }}
                </template>
            </div>
        </div>
        <div class="ml-auto flex items-center">
            <BaseMenu>
                <MenuItem v-slot="{ active }">
                    <Link
                        as="button"
                        method="patch"
                        :href="route('shifts.trashed.restore', { shiftId: shift.id })"
                        preserve-scroll
                        :disabled="shift.room_trashed"
                        :title="shift.room_trashed ? $t('The room of this shift is in the recycle bin or no longer exists. Restore the room first.') : ''"
                        :class="[active && !shift.room_trashed ? 'bg-text-inverse/10 text-accent-700' : 'text-text-subtle',
                                 shift.room_trashed ? 'cursor-not-allowed opacity-50' : '',
                                 'group flex items-center px-4 py-2 w-full text-sm subpixel-antialiased']"
                    >
                        <IconRefresh class="mr-3 h-5 w-5 text-primaryText group-hover:text-accent-700" aria-hidden="true" />
                        {{ $t('Restore') }}
                    </Link>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                    <Link
                        as="button"
                        method="delete"
                        :href="route('shifts.trashed.force', { shiftId: shift.id })"
                        preserve-scroll
                        :class="[active ? 'bg-text-inverse/10 text-accent-700' : 'text-text-subtle',
                                 'group flex items-center px-4 py-2 w-full text-sm subpixel-antialiased']"
                    >
                        <IconTrash class="mr-3 h-5 w-5 text-primaryText group-hover:text-accent-700" aria-hidden="true" />
                        {{ $t('Delete permanently') }}
                    </Link>
                </MenuItem>
            </BaseMenu>
        </div>
    </div>

    <BasePaginator
        v-if="trashed_shifts.total > 0"
        :entities="trashed_shifts"
        property-name="trashed_shifts"
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
import {IconLock, IconRefresh, IconTrash} from "@tabler/icons-vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import TrashLayout from "@/Layouts/TrashLayout.vue";
import {MenuItem} from "@headlessui/vue";
import {Link} from "@inertiajs/vue3";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import TrashSearchAndActions from "@/Pages/Trash/Components/TrashSearchAndActions.vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";

export default {
    name: "Shifts",
    layout: [AppLayout, TrashLayout],
    props: ['trashed_shifts'],
    components: {
        BaseMenu,
        BasePaginator,
        TrashSearchAndActions,
        ConfirmDeleteModal,
        MenuItem,
        IconLock,
        IconRefresh,
        IconTrash,
        Link,
    },
    data() {
        return {
            showConfirmDeleteAll: false,
        }
    },
    methods: {
        forceDeleteAll() {
            this.$inertia.delete(route('shifts.trashed.force-all'), {
                preserveScroll: true,
                onSuccess: () => {
                    this.showConfirmDeleteAll = false;
                }
            });
        },
    }
}
</script>
