<template>
    <div class="flex justify-between items-start my-5">
        <div class="flex items-start gap-3">
            <img :src="'/Svgs/IconSvgs/icon_notification_' + notification.data.icon + '.svg'" alt="">
            <div class="">
                <div class="flex items-center gap-4">
                    <h4 class="sDark">{{ notification.data.title }}</h4>
                    <div class="flex items-center gap-2 xxsLight" v-if="notification.data?.description[0]">
                        {{notification.data?.description[0].text}}
                        von
                        <NewUserToolTip :id="notification?.id" :user="notification.data?.description[0]?.created_by" height="5" width="5"/>
                    </div>
                    <div class="flex items-center gap-2 xxsLight" v-else-if="notification.data.created_by">
                        {{ notification.data.created_at }}
                        von
                        <NewUserToolTip :id="notification?.id" :user="notification.data?.created_by" height="5" width="5"/>
                    </div>
                </div>
                <div class="xxsLight mt-2 flex gap-1 items-center" v-if="notification.data?.description" >
                    <div v-for="(description, index) in notification.data?.description">
                        <p v-if="description.type !== 'comment'">
                            <span v-if="notification.data.type === 'NOTIFICATION_CONFLICT' && index === '1'">Betrifft: </span>
                            <a :href="description.href" v-if="description.type === 'link'" class="text-indigo-800">{{ description.title }}</a>
                            <span v-else>{{ description.title }}</span>
                            <span v-if="description.title && index < 4"> |</span>
                        </p>
                    </div>
                </div>
                <p v-if="notification.data?.description[5]" class="mt-2 xxsLight">
                    {{ notification.data?.description[5]?.title }}
                </p>
                <div class="" v-if="notification.data.showHistory">
                    <div @click="openHistory" class="xxsLight cursor-pointer items-center flex text-buttonBlue">
                        <ChevronRightIcon class="h-3 w-3" />
                        <span>
                             Verlauf ansehen
                        </span>
                    </div>
                </div>
                <NotificationButtons v-if="!isArchive"
                    :buttons="notification.data.buttons"
                    @openDeclineModal="loadEventDataForDecline"
                    @openEventEditAccept="loadEventDataForEditAndAccept"
                    @deleteEvent="showDeleteConfirmModal = true"
                    @openProjectCalculation="openProjectBudget(notification.data?.projectId)"
                />
            </div>
        </div>
        <div class="">
            <img @click="setOnRead" v-show="notification.hovered"
                 v-if="notification.data?.changeType !== 'BUDGET_VERIFICATION_REQUEST'"
                 src="/Svgs/IconSvgs/icon_archive_white.svg"
                 class="h-6 w-6 p-1 ml-1 flex cursor-pointer bg-buttonBlue rounded-full"
                 aria-hidden="true" alt=""/>
        </div>
    </div>


    <ProjectHistoryWithoutBudgetComponent
        v-if="showProjectHistory"
        :project_history="historyObjects"
        @closed="showProjectHistory = false"
    />

    <DeclineEventModal
        :request-to-decline="event"
        :event-types="eventTypes"
        @closed="closeDeclineEventModal"
        @declined="setOnRead"
        v-if="showDeclineEventModal"
    />

    <event-component
        v-if="createEventComponentIsVisible"
        @closed="onEventComponentClose()"
        :showHints="$page.props?.can?.show_hints"
        :eventTypes="eventTypes"
        :rooms="rooms"
        :project="project"
        :event="event"
        :wantedRoomId="wantedSplit"
        :isAdmin="hasAdminRole() || $canAny(['create, delete and update rooms'])"
        :roomCollisions="roomCollisions"
    />

    <ConfirmDeleteModal
        @closed="showDeleteConfirmModal = false"
        @delete="deleteEvent"
        title="Termin Löschen?"
        description="Bist du sicher, dass du die ausgewählten Belegungen in den Papierkorb legen möchtest? Sämtliche Untertermine werden ebenfalls gelöscht."
        v-if="showDeleteConfirmModal"
    />



</template>

<script>
import NotificationButtons from "@/Layouts/Components/NotificationComponents/NotificationButtons.vue";
import {ChevronRightIcon} from "@heroicons/vue/solid";
import {Inertia} from "@inertiajs/inertia";
import DeclineEventModal from "@/Layouts/Components/DeclineEventModal.vue";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import NotificationDeclineEvent from "@/Layouts/Components/NotificationComponents/NotificationDeclineEvent.vue";
import ProjectHistoryWithoutBudgetComponent from "@/Layouts/Components/ProjectHistoryWithoutBudgetComponent.vue";
import {useForm} from "@inertiajs/inertia-vue3";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";

export default {
    name: "NotificationBlock",
    components: {
        ConfirmDeleteModal,
        EventComponent,
        ProjectHistoryWithoutBudgetComponent,
        NewUserToolTip,
        DeclineEventModal,
        NotificationButtons, ChevronRightIcon
    },
    props: ['notification', 'eventTypes', 'historyObjects', 'event', 'rooms', 'project', 'wantedSplit', 'roomCollisions', 'isArchive'],
    data(){
        return {
            showDeclineModal: false,
            showProjectHistory: false,
            showDeclineEventModal: false,
            setOnReadForm: useForm({
                notificationId: this.notification.id
            }),
            createEventComponentIsVisible: false,
            showDeleteConfirmModal: false
        }
    },
    computed: {
    },
    methods: {
        setOnRead() {
            this.setOnReadForm.patch(route('notifications.setReadAt'));
        },
        openHistory(){
            Inertia.reload({
                data: {
                    showHistory: true,
                    historyType: this.notification.data?.historyType,
                    modelId: this.notification.data?.modelId,
                },
                onFinish: () => {
                    if(this.notification.data?.historyType === 'project'){
                        this.showProjectHistory = true;
                    }
                }
            })
        },
        loadEventDataForDecline(){
            Inertia.reload({
                data: {
                    openDeclineEvent: true,
                    eventId: this.notification.data?.eventId
                },
                onFinish: () => {
                    this.showDeclineEventModal = true
                }
            })
        },
        closeDeclineEventModal(){
            this.showDeclineEventModal = false;
        },
        loadEventDataForEditAndAccept(){
            Inertia.reload({
                data: {
                    openEditEvent: true,
                    eventId: this.notification.data?.eventId
                },
                onFinish: () => {
                    this.createEventComponentIsVisible = true
                }
            })
            console.log(this.event);
        },
        onEventComponentClose(){

        },
        deleteEvent() {
            this.$inertia.delete(route('events.delete', this.notification.data?.eventId), {
                preserveScroll: true,
                preserveState: true
            })
            this.setOnRead();
            this.showDeleteConfirmModal = false;
        },
        openProjectBudget(projectId){
            window.location.href = route('projects.show', projectId) + '?openTab=budget';
        }
    }
}
</script>

<style scoped>

</style>
