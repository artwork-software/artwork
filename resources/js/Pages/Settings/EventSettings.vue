<template>
    <app-layout :title="$t('Event Settings')">

           <EventSettingHeader>
               <template #actions>
                   <BaseUIButton variant="primary" hide-icon @click="openAddEventTypeModal">
                       <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                       {{ $t('New Event Type') }}
                   </BaseUIButton>
               </template>
               <SettingsGuideBanner
                   class="mb-6"
                   storage-key="settings-guide.event.types"
                   title="How does this area work?"
                   :paragraphs="[
                       'Event types categorise your events: each type has a colour and an abbreviation that identify its events in the calendar.',
                       'A type also controls what is required when creating an event — for example a mandatory project assignment or an individual event name.',
                       'Whether events of a type define the project period and who has to verify them in the planning calendar is set per type in the edit dialog.',
                   ]"
               />
               <div>
                   <BasePageTitle
                       title="Event Types"
                       description="Define event types to which events can be assigned later. You can also define whether they must be assigned to projects or whether they can have their own individual appointment name."
                   />
               </div>
               <ul role="list" class="mt-5">
                   <li v-for="(eventType,index) in event_types" :key="eventType.id" class="flex justify-between">
                       <div class="flex my-4">
                           <div>
                               <div class="block w-16 h-16 rounded-full" :style="{'backgroundColor' : eventType.hex_code }" />
                           </div>
                           <div class="ml-5 my-auto w-full justify-start mr-6">
                               <div class="flex my-auto">
                                   <p class="text-lg/[21px] font-semibold text-text">{{ eventType.name }}</p>
                                   <div class="ml-2 text-lg/[21px] font-semibold text-text">({{eventType.abbreviation}})</div>
                               </div>
                               <div class="flex mt-2 divide-x space-x-2">
                                   <div class="text-xs/[18px] text-text-subtle">
                                       {{
                                           eventType.project_mandatory ? $t('Project assignment mandatory') : $t('Project assignment optional')
                                       }}
                                   </div>
                                   <div class="text-xs/[18px] text-text-subtle pl-2">
                                       {{
                                           eventType.individual_name ? $t('individual event name mandatory') : $t('individual event name optional')
                                       }}
                                   </div>
                                   <div class="text-xs/[18px] text-text-subtle pl-2" v-if="eventType.relevant_for_project_period">
                                       {{
                                            $t('Relevant for project period')
                                       }}
                                   </div>
                               </div>
                               <div v-if="$page.props.event_direct_booking_only" class="text-text-subtle font-lexend font-extralight text-xs">
                                   {{ $t('Verification mode')}}: {{ $t('switched off by setting "Events always bookable directly"') }}
                               </div>
                               <div v-else class="text-text-subtle font-lexend font-extralight text-xs">
                                   {{ $t('Verification mode')}}: {{ $t(eventType.verification_mode) }}<span v-if="eventType.users?.length > 0">: </span>
                                   <span>
                                        {{ (eventType.users ?? []).map((user) => user.name).join(', ') }}
                                    </span>
                               </div>
                           </div>
                       </div>
                       <div class="flex items-center">
                           <BaseMenu has-no-offset white-menu-background>
                               <BaseMenuItem title="Edit event type" :icon="IconEdit" white-menu-background @click="openEditEventTypeModal(eventType)" />
                               <BaseMenuItem v-if="index !== 0" title="Delete event type" :icon="IconTrash" white-menu-background @click="openDeleteEventTypeModal(eventType)" />
                           </BaseMenu>
                       </div>
                   </li>
               </ul>
           </EventSettingHeader>

        <AddEditEventTypeModal
            :event-type="eventTypeToEdit"
            v-if="addingEventType"
            @close="closeAddEventTypeModal"
        />

        <DeleteEventTypeConfirmationModal
            v-if="deletingEventType"
            :event-type="eventTypeToDelete"
            @closed="closeDeleteEventTypeModal"
            @delete="deleteEventType"
        />
        <DeleteStandardEventTypeModal
            v-if="deletingUndefined"
            :event-type="eventTypeToDelete"
            @closed="closeDeletingUndefined"
        />
    </app-layout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import Permissions from "@/Mixins/Permissions.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import EventSettingHeader from "@/Pages/Settings/EventSettingComponents/EventSettingHeader.vue";
import AddEditEventTypeModal from "@/Pages/Settings/EventType/Components/Modals/AddEditEventTypModal.vue";
import DeleteEventTypeConfirmationModal from "@/Pages/Settings/EventType/Components/Modals/DeleteEventTypeConfirmationModal.vue";
import DeleteStandardEventTypeModal from "@/Pages/Settings/EventType/Components/Modals/DeleteStandardEventTypeModal.vue";
import {IconCirclePlus, IconEdit, IconTrash} from "@tabler/icons-vue";
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
export default {
    mixins: [Permissions],
    components: {
        BaseUIButton,
        SettingsGuideBanner,
        BasePageTitle,
        BaseMenuItem,
        EventSettingHeader,
        BaseMenu,
        AppLayout,
        AddEditEventTypeModal,
        DeleteEventTypeConfirmationModal,
        DeleteStandardEventTypeModal
    },
    props: ['event_types'],
    data() {
        return {
            addingEventType: false,
            deletingEventType: false,
            deletingUndefined: false,
            eventTypeToDelete: null,
            eventTypeToEdit: null,
        }
    },
    methods: {
        IconTrash,
        IconEdit,
        IconCirclePlus,
        openAddEventTypeModal() {
            this.eventTypeToEdit = null;
            this.addingEventType = true;
        },
        openEditEventTypeModal(eventType) {
            this.eventTypeToEdit = eventType;
            this.addingEventType = true
        },
        closeAddEventTypeModal() {
            this.addingEventType = false;
            this.eventTypeToEdit = null;
        },
        openDeleteEventTypeModal(eventType) {
            this.eventTypeToDelete = eventType;
            // Standard-Termintyp: nur Hinweis, kein zweiter Lösch-Dialog darüber
            if (this.eventTypeToDelete.id === 1) {
                this.deletingUndefined = true;
                return;
            }
            this.deletingEventType = true;
        },
        closeDeletingUndefined() {
            this.deletingUndefined = false;
            this.eventTypeToDelete = null
        },
        closeDeleteEventTypeModal() {
            this.deletingEventType = false;
            this.eventTypeToDelete = null;
        },
        deleteEventType() {
            if (!this.eventTypeToDelete) {
                return;
            }
            this.$inertia.delete(`../event_types/${this.eventTypeToDelete.id}`, {
                onSuccess: () => {
                    this.closeDeleteEventTypeModal();
                }
            });
        }
    },
}
</script>

<style>
input[type=color] {
    border-radius: 100%;
    border: 1px solid transparent;
}

input[type=color]::-webkit-color-swatch {
    border-radius: 100%;
    border: 1px solid transparent;
}

input[type=color]::-webkit-color-swatch-wrapper {
    border-radius: 100%;
    border: 1px solid transparent;
}

</style>
