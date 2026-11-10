<template>
    <ArtworkBaseModal
        title="Confirm occupancy with room change"
        :description="periodText"
        modal-size="sm:max-w-xl"
        @close="closeModal(false)"
    >
        <div class="space-y-4">
            <div class="flex items-start justify-between gap-x-4">
                <div class="flex min-w-0 items-center gap-x-2">
                    <span
                        class="size-4 shrink-0 rounded-full"
                        :style="{ backgroundColor: eventType?.hex_code }"
                    />
                    <span class="truncate font-lexend text-sm font-bold text-text">
                        {{ eventType?.name }}
                    </span>
                    <img v-if="request.audience" src="/Svgs/IconSvgs/icon_public.svg" class="size-4 shrink-0" :alt="$t('With audience')" v-tooltip.bottom="{ value: $t('With audience'), class: 'aw-tooltip' }"/>
                    <img v-if="request.is_loud" src="/Svgs/IconSvgs/icon_loud.svg" class="size-4 shrink-0" :alt="$t('It gets loud')" v-tooltip.bottom="{ value: $t('It gets loud'), class: 'aw-tooltip' }"/>
                </div>
                <div class="flex shrink-0 items-center gap-x-2 text-xs text-text-subtle">
                    {{ $t('Created by') }}
                    <UserPopoverTooltip
                        v-if="creator"
                        :user="creator"
                        :id="creator.id"
                        height="4"
                        width="4"
                    />
                    <span v-else class="font-semibold">{{ $t('Deleted user') }}</span>
                </div>
            </div>

            <div class="space-y-1 text-sm text-text">
                <div v-if="project?.id" class="flex items-center gap-x-2 text-xs text-text-subtle">
                    {{ $t('assigned to') }}
                    <a
                        :href="route('projects.tab', {project: project.id, projectTab: first_project_calendar_tab_id})"
                        class="font-bold text-accent-600 hover:underline"
                    >
                        {{ project?.name }}
                    </a>
                </div>
                <div v-else class="text-xs text-text-subtle">
                    {{ $t('No project assignment') }}
                </div>
                <div v-if="request.description" class="text-xs text-text-subtle">
                    {{ request.description }}
                </div>
            </div>

            <ArtworkBaseListbox
                v-model="room"
                :items="rooms"
                by="id"
                option-label="name"
                label="New room"
                placeholder="Select room"
            />
        </div>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                @click="closeModal(false)"
            />
            <BaseUIButton
                label="Confirm occupancy with room change"
                variant="primary"
                icon="IconCheck"
                :disabled="!room"
                @click="closeModal(true)"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script>
import Permissions from "@/Mixins/Permissions.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";

export default {
    name: 'AnswerEventRequestWithRoomChangeComponent',
    mixins: [Permissions],
    components: {
        ArtworkBaseModal,
        ArtworkBaseListbox,
        BaseUIButton,
        UserPopoverTooltip,
    },
    props: [
        'type',
        'request',
        'rooms',
        'projects',
        'eventTypes',
        'creator',
        'first_project_calendar_tab_id'
    ],
    emits: ['closed'],
    computed: {
        periodText() {
            if (!this.request?.start_time || !this.request?.end_time) {
                return '';
            }
            if (this.getDateOfDate(this.request.start_time) === this.getDateOfDate(this.request.end_time)) {
                return this.formatDate(this.request.start_time) + ' - ' + this.getTimeOfDate(this.request.end_time);
            }
            return this.formatDate(this.request.start_time) + ' - ' + this.formatDate(this.request.end_time);
        },
    },
    methods: {
        closeModal(bool) {
            // Der Parent liest room_id aus dem Request-Objekt und schickt es per PUT
            if (this.room) {
                this.request.room_id = this.room.id;
            }
            this.request.occupancy_option = false;
            this.$emit('closed', bool);
        },
        formatDate(isoDate) {
            if(isoDate?.split('T').length > 1){
                return isoDate.split('T')[0].substring(8, 10) + '.' + isoDate.split('T')[0].substring(5, 7) + '.' + isoDate.split('T')[0].substring(0, 4) + ', ' + isoDate.split('T')[1].substring(0, 5)
            }else if(isoDate?.split(' ').length > 1){
                return isoDate.split(' ')[0].substring(8, 10) + '.' + isoDate.split(' ')[0].substring(5, 7) + '.' + isoDate.split(' ')[0].substring(0, 4) + ', ' + isoDate.split(' ')[1].substring(0, 5)
            }
        },
        getTimeOfDate(isoDate) {
            return isoDate.split('T')[1].substring(0, 5);
        },
        getDateOfDate(isoDate) {
            return isoDate.split('T')[0];
        },
    },
    data() {
        return {
            eventType: this.eventTypes.find(eventType => eventType.id === this.request.event_type_id),
            room: this.rooms.find(room => room.id === this.request.room_id) ?? null,
            project: this.projects.find(project => project.id === this.request.project_id),
        }
    }
}
</script>
