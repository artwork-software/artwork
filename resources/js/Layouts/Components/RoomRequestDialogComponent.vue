<template>
    <ArtworkBaseModal
        title="Comment on occupancy"
        modal-size="sm:max-w-2xl"
        @close="closeModal"
    >
        <div class="space-y-4">
            <!--   Ersteller*in   -->
            <div class="flex items-center gap-x-2 text-xs text-text-subtle">
                {{ $t('Created by') }}
                <UserPopoverTooltip
                    v-if="event?.created_by"
                    :user="event.created_by"
                    :id="event.created_by.id"
                    height="6"
                    width="6"
                />
                <span v-else class="font-semibold">{{ $t('Deleted user') }}</span>
            </div>

            <!--    Termintyp und Name    -->
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <div v-if="!canEdit || disableEventTypeSelector" class="flex min-w-0 items-center gap-x-2">
                        <span class="size-4 shrink-0 rounded-full" :style="{ backgroundColor: selectedEventType?.hex_code }"/>
                        <span class="truncate font-lexend text-sm font-bold text-text">{{ selectedEventType?.name }}</span>
                    </div>
                    <ArtworkBaseListbox
                        v-else
                        v-model="selectedEventType"
                        :items="eventTypes"
                        by="id"
                        option-label="name"
                        label="Event type"
                        show-color-indicator
                        color-property="hex_code"
                    />
                    <p v-if="error?.eventType" class="mt-1 text-[11.5px] text-danger">{{ error.eventType.join('. ') }}</p>
                </div>

                <BaseInput
                    v-if="canEdit"
                    id="eventTitle"
                    v-model="eventName"
                    label="Event name"
                    :required="!!selectedEventType?.individual_name"
                    :error="selectedEventType?.individual_name ? (error?.eventName?.join('. ') ?? '') : ''"
                />
                <div v-else-if="eventName" class="flex items-center text-sm font-semibold text-text">
                    {{ eventName }}
                </div>
            </div>

            <!-- Termineigenschaften -->
            <div v-if="canEdit" class="flex flex-wrap items-center gap-x-6 gap-y-2">
                <BaseCheckbox id="roomRequestAudience" v-model="audience" :disabled="!canEdit">
                    <template #label>
                        <span class="inline-flex items-center gap-x-1.5">
                            <img src="/Svgs/IconSvgs/icon_public.svg" class="size-4" alt=""/>
                            {{ $t('With audience') }}
                        </span>
                    </template>
                </BaseCheckbox>
                <BaseCheckbox id="roomRequestIsLoud" v-model="isLoud" :disabled="!canEdit">
                    <template #label>
                        <span class="inline-flex items-center gap-x-1.5">
                            <img src="/Svgs/IconSvgs/icon_loud.svg" class="size-4" alt=""/>
                            {{ $t('It gets loud') }}
                        </span>
                    </template>
                </BaseCheckbox>
            </div>

            <!--    Nur lesen: Projekt, Raum und Zeitraum    -->
            <div v-if="!canEdit" class="space-y-1 text-sm text-text">
                <div v-if="selectedProject?.id" class="flex items-center gap-x-2 text-xs text-text-subtle">
                    {{ $t('assigned to') }}
                    <a
                        :href="route('projects.tab', {project: selectedProject.id, projectTab: first_project_calendar_tab_id})"
                        class="font-bold text-accent-600 hover:underline"
                    >
                        {{ selectedProject?.name }}
                    </a>
                </div>
                <div v-else class="text-xs text-text-subtle">
                    {{ $t('No project assignment') }}
                </div>
                <div v-if="startDate && endDate" class="font-semibold tabular-nums">
                    <template v-if="startDate === endDate">
                        {{ selectedRoom?.name }},
                        {{ startDate.toString().substring(10, 8) }}.{{ startDate.toString().substring(7, 5) }}.{{ startDate.toString().substring(4, 0) }},
                        {{ startTime }} - {{ endTime }}
                    </template>
                    <template v-else>
                        {{ selectedRoom?.name }},
                        {{ startDate.toString().substring(10, 8) }}.{{ startDate.toString().substring(7, 5) }}.{{ startDate.toString().substring(4, 0) }},
                        {{ startTime }} -
                        {{ endDate.toString().substring(10, 8) }}.{{ endDate.toString().substring(7, 5) }}.{{ endDate.toString().substring(4, 0) }},
                        {{ endTime }}
                    </template>
                </div>
                <div v-if="audience || isLoud" class="flex flex-wrap gap-2 pt-1">
                    <TagComponent v-if="audience" icon="audience" :displayed-text="$t('With audience')" hideX="true"/>
                    <TagComponent v-if="isLoud" :displayed-text="$t('It gets loud')" hideX="true"/>
                </div>
            </div>

            <!--    Projekt    -->
            <div v-if="canEdit" class="space-y-2">
                <div v-if="!creatingProject" class="flex items-center gap-x-2 text-xs text-text-subtle">
                    <template v-if="selectedProject?.id">
                        {{ $t('Currently assigned to:') }}
                        <a
                            :href="route('projects.tab', {project: selectedProject.id, projectTab: first_project_calendar_tab_id})"
                            class="font-bold text-accent-600 hover:underline"
                        >
                            {{ selectedProject?.name }}
                        </a>
                        <button
                            type="button"
                            class="inline-flex size-6 cursor-pointer items-center justify-center rounded-md text-text-subtle transition-colors duration-150 hover:bg-surface-hover hover:text-danger motion-reduce:transition-none"
                            :aria-label="$t('Remove project')"
                            v-tooltip.bottom="{ value: $t('Remove project'), class: 'aw-tooltip' }"
                            @click="selectedProject = null"
                        >
                            <PropertyIcon name="IconX" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                        </button>
                    </template>
                    <template v-else>
                        {{ selectedProject?.name ?? $t('Not assigned to a project') }}
                    </template>
                </div>
                <div v-else class="text-xs text-text-subtle">
                    {{ $t('The project is created when it is saved.') }}
                </div>

                <div v-if="projectCreationFromEventsEnabled" class="flex items-center gap-x-3">
                    <SwitchDualLabel
                        v-model="creatingProject"
                        :left-label="$t('Existing project')"
                        :right-label="$t('New project')"
                        icon="IconGeometry"
                        :disabled="!canEdit"
                    />
                    <div v-if="showHints" class="flex items-center gap-x-1 text-xs text-text-subtle">
                        <SvgCollection svgName="arrowLeft"/>
                        {{ $t('Create a new project at the same time') }}
                    </div>
                </div>

                <div class="relative">
                    <BaseInput
                        id="roomRequestProjectName"
                        v-model="projectName"
                        :label="creatingProject ? 'New project name' : 'Search project'"
                        :show-label="false"
                        :placeholder="creatingProject ? $t('New project name') : $t('Search project')"
                        autocomplete="off"
                    />
                    <div
                        v-if="projectSearchResults.length > 0 && !creatingProject"
                        class="absolute z-10 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-border-subtle bg-surface py-1 text-sm shadow-overlay"
                    >
                        <button
                            v-for="(project, index) in projectSearchResults"
                            :key="index"
                            type="button"
                            class="block w-full cursor-pointer truncate px-3 py-2 text-left text-text hover:bg-surface-hover"
                            @click="chooseProject(project)"
                        >
                            {{ project.name }}
                        </button>
                    </div>
                </div>
                <LastedProjects
                    :limit="10"
                    @select="chooseProject"
                />
                <p v-if="error?.projectId" class="text-[11.5px] text-danger">{{ error.projectId.join('. ') }}</p>
                <p v-if="error?.projectName" class="text-[11.5px] text-danger">{{ error.projectName.join('. ') }}</p>
            </div>

            <!--    Zeitraum    -->
            <div v-if="canEdit" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <div class="flex items-end gap-2">
                        <BaseInput
                            id="startDate"
                            type="date"
                            v-model="startDate"
                            label="Start"
                            required
                            :disabled="!canEdit"
                            @change="() => $nextTick(checkChanges)"
                        />
                        <BaseInput
                            id="changeStartTime"
                            type="time"
                            v-model="startTime"
                            label="Start time"
                            required
                            :disabled="!canEdit"
                            @change="() => $nextTick(checkChanges)"
                        />
                    </div>
                    <p v-if="error?.start" class="mt-1 text-[11.5px] text-danger">{{ error.start.join('. ') }}</p>
                </div>
                <div>
                    <div class="flex items-end gap-2">
                        <BaseInput
                            id="endDate"
                            type="date"
                            v-model="endDate"
                            label="End"
                            required
                            :disabled="!canEdit"
                            @change="() => $nextTick(checkChanges)"
                        />
                        <BaseInput
                            id="changeEndTime"
                            type="time"
                            v-model="endTime"
                            label="End time"
                            required
                            :disabled="!canEdit"
                            @change="() => $nextTick(checkChanges)"
                        />
                    </div>
                    <p v-if="error?.end" class="mt-1 text-[11.5px] text-danger">{{ error.end.join('. ') }}</p>
                </div>
            </div>

            <!--    Raum    -->
            <div v-if="canEdit">
                <ArtworkBaseListbox
                    v-model="selectedRoom"
                    :items="rooms"
                    by="id"
                    option-label="name"
                    placeholder="Select room"
                >
                    <template #label>
                        {{ $t('Room') }} <span class="text-danger">*</span>
                    </template>
                    <template #option="{ item, selected }">
                        <div class="flex items-center gap-2">
                            <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ item.name }}</span>
                            <PropertyIcon
                                v-if="roomCollisionArray[item.id] > 0"
                                name="IconAlertTriangle"
                                class="size-4 shrink-0 text-warning"
                                stroke-width="1.5"
                                :aria-label="$t('Conflict')"
                            />
                        </div>
                        <span v-if="selected" class="absolute inset-y-0 right-0 flex items-center pr-4 text-accent-600">
                            <PropertyIcon name="IconCheck" class="size-4" aria-hidden="true"/>
                        </span>
                    </template>
                </ArtworkBaseListbox>
                <p v-if="error?.roomId" class="mt-1 text-[11.5px] text-danger">{{ error.roomId.join('. ') }}</p>
            </div>

            <!--    Beschreibung    -->
            <BaseTextarea
                v-if="canEdit"
                id="description"
                v-model="description"
                label="Description"
                :placeholder="$t('What do I need to bear in mind for the event?')"
                :disabled="!canEdit"
                :rows="4"
            />
            <div v-else-if="description" class="text-sm text-text">
                {{ description }}
            </div>

            <!--    Kommentare    -->
            <template v-if="showComments">
                <div
                    v-for="comment in event?.comments ?? []"
                    :key="comment.id"
                    class="rounded-lg border border-border-subtle bg-surface-sunken p-3"
                >
                    <div class="flex items-center gap-x-2 text-xs text-text-subtle">
                        <NewUserToolTip :id="comment.id" :user="comment.user" :height="6" :width="6"/>
                        {{ comment.created_at }}
                    </div>
                    <div class="mt-2 text-sm text-text">
                        {{ comment.comment }}
                    </div>
                </div>
            </template>

            <BaseTextarea
                v-if="canEdit"
                id="newComment"
                v-model="newComment"
                label="Comment"
                required
                :placeholder="$t('Enter your answer')"
                :disabled="!canEdit"
                :rows="4"
            />
        </div>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="sendingAnswer"
                @click="closeModal(false)"
            />
            <BaseUIButton
                label="Send answer"
                variant="primary"
                icon="IconSend"
                :processing="sendingAnswer"
                :disabled="isSendAnswerDisabled"
                @click="updateAndAnswerEvent()"
            />
        </template>
    </ArtworkBaseModal>
</template>


<script>
import {parseYmd, toYmd} from "@/Helper/IsoWeek.js";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import {useForm} from "@inertiajs/vue3";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import dayjs from "dayjs";
import Permissions from "@/Mixins/Permissions.vue";
import LastedProjects from "@/Artwork/LastedProjects.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseCheckbox from "@/Artwork/Inputs/BaseCheckbox.vue";
import SwitchDualLabel from "@/Artwork/Toggles/SwitchDualLabel.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

export default {
    name: 'EventComponent',
    mixins: [Permissions],
    components: {
        ArtworkBaseModal,
        ArtworkBaseListbox,
        BaseUIButton,
        BaseInput,
        BaseTextarea,
        BaseCheckbox,
        SwitchDualLabel,
        PropertyIcon,
        LastedProjects,
        NewUserToolTip,
        UserPopoverTooltip,
        SvgCollection,
        TagComponent,
    },
    data() {
        return {
            sendingAnswer: false,
            startDate: null,
            startTime: null,
            endDate: null,
            endTime: null,
            isLoud: false,
            audience: false,
            showSeriesEdit: false,
            allSeriesEvents: false,
            frequencies: [
                {
                    id: 1,
                    name: this.$t('Daily')
                },
                {
                    id: 2,
                    name: this.$t('Weekly')
                },
                {
                    id: 3,
                    name: this.$t('Every 2 weeks')
                },
                {
                    id: 4,
                    name: this.$t('Monthly')
                }
            ],
            series: false,
            seriesEndDate: null,
            selectedFrequency: {
                id: 2,
                name: this.$t('Weekly')
            } ,
            projectName: null,
            title: null,
            isOption: true,
            eventName: null,
            eventTypeName: null,
            selectedEventType: this.eventTypes[0],
            selectedProject: null,
            selectedRoom: null,
            error: null,
            creatingProject: false,
            projectSearchResults: [],
            description: null,
            canEdit: null,
            declinedRoomId: null,
            deleteComponentVisible: false,
            adminComment: '',
            optionString: null,
            accept: true,
            optionAccept: false,
            disableEventTypeSelector: false,
            answerRequestForm: useForm({
                accepted: false,
            }),
            collisionRoomForm: this.$inertia.form({
                _method: 'POST',
                start: null,
                end: null,
                roomId: null
            }),
            newComment: '',
            roomCollisionArray: []
        }
    },
    props: [
        'showHints',
        'eventTypes',
        'rooms',
        'isAdmin',
        'event',
        'project',
        'wantedRoomId',
        'roomCollisions',
        'showComments',
        'first_project_calendar_tab_id'
    ],
    emits: ['closed'],
    watch: {
        projectName: {
            deep: true,
            handler() {
                if (this.creatingProject || !this.projectName) {
                    this.projectSearchResults = [];
                    return;
                }
                axios.get('/projects/search', {params: {query: this.projectName}})
                    .then(response => this.projectSearchResults = response.data)
            },
        },
        event: {
            immediate: true,
            deep: true,
            handler: function () {
                this.openModal()
            },
        },
    },
    computed: {
        // Kalender-Einstellung: ohne Projektanlage aus Terminen nur bestehende Projekte verknüpfen
        projectCreationFromEventsEnabled() {
            return this.$page.props.project_creation_from_events_enabled !== false;
        },
        isSendAnswerDisabled() {
            return this.sendingAnswer
                || !this.selectedRoom
                || this.endDate > this.seriesEndDate
                || (this.series && !this.seriesEndDate)
                || !this.startDate || !this.startTime || !this.endDate || !this.endTime
                || this.newComment === '';
        },
        roomAdminIds() {
            let adminIds = [];
            this.selectedRoom?.room_admins?.forEach(admin => {
                adminIds.push(admin.id);
            })
            return adminIds;
        },
    },
    methods: {
        checkButtonDisabled(){
            if (this.series) {
                if (this.seriesEndDate) {
                    const eventEndDate = new Date(this.endFull);
                    const endDateSeries = new Date(this.seriesEndDate);
                    return endDateSeries < eventEndDate;
                }
                return true;
            }
            return false;
        },
        openModal() {
            this.canEdit = (!this.event?.id) || this.event?.canEdit;
            if (!this.event) {
                if (this.project) {
                    this.selectedProject = {id: this.project.id, name: this.project.name};
                }
                return;
            }
            const start = dayjs(this.event.start);
            const end = dayjs(this.event.end);

            this.startDate = start?.format('YYYY-MM-DD');
            this.startTime = start?.format('HH:mm');
            this.endDate = end?.format('YYYY-MM-DD');
            this.endTime = end?.format('HH:mm');
            this.isLoud = this.event.isLoud;
            this.audience = this.event.audience;
            this.title = this.event.title;
            this.eventName = this.event.eventName;
            if (!this.event.eventTypeId) {
                this.selectedEventType = this.eventTypes[0];
            } else {
                this.selectedEventType = this.eventTypes.find(type => type.id === this.event.eventTypeId);
                if(this.selectedEventType.id === 1){
                    this.disableEventTypeSelector = true;
                }
            }
            this.series = this.event.is_series;
            if (this.series) {
                this.seriesEndDate = this.event.series.end_date;
            }
            this.frequencies.forEach((frequency) => {
                if(frequency.id === this.event.series?.frequency_id) {
                    this.selectedFrequency = frequency;
                }
            })
            this.selectedProject = {id: this.event.projectId, name: this.event.projectName};
            if (this.wantedRoomId) {
                this.selectedRoom = this.rooms.find(room => room.id === this.wantedRoomId);
            } else if (this.event) {
                this.selectedRoom = this.rooms.find(type => type.id === this.event.roomId);
            }

            this.description = this.event.description;

            this.checkCollisions();
        },
        resetForm() {
            this.startDate = null;
            this.startTime = null;
            this.endDate = null;
            this.endTime = null;
            this.selectedRoom = null;
            this.selectedProject = null;
        },
        closeModal(bool) {
            // Erst nach gespeicherter Antwort „geschlossen“ melden: die Benachrichtigung startet darauf
            // sofort einen eigenen Inertia-Request (Benachrichtigung entfernen), der diesen sonst
            // abbrach, die Antwort ging verloren
            if (bool && this.newComment) {
                this.$inertia.post(this.route('event.answer', {event: this.event.id}), {
                    comment: this.newComment,
                }, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        this.resetForm();
                        this.$emit('closed', true);
                    },
                    // Termin ist gespeichert, nur die Antwort nicht: Dialog offen und ausgefüllt lassen,
                    // damit erneut gesendet werden kann (Fehlermeldung kommt global)
                    onFinish: () => {
                        this.sendingAnswer = false;
                    },
                })
                return;
            }
            this.sendingAnswer = false;
            this.resetForm();
            this.$emit('closed', bool);
        },
        formatDate(date, time) {
            if (date === null || time === null) return null;
            return (new Date(date + ' ' + time)).toISOString()
        },
        checkChanges() {
            this.updateTimes();
            this.checkCollisions();
        },
        async checkCollisions() {
            if (this.startTime && this.startDate && this.endTime && this.endDate) {
                let startFull = this.formatDate(this.startDate, this.startTime);
                let endFull = this.formatDate(this.endDate, this.endTime);
                await axios.post('/collision/room', {
                    params: {
                        start: startFull,
                        end: endFull,
                        currentEventId: this.event.id
                    }
                }, { skipErrorToast: true }) // Lesezugriff im Hintergrund
                    .then(response => this.roomCollisionArray = response.data)
                    .catch(() => { /* Kollisionsanzeige ist best effort */ });
            }
        },
        updateTimes() {
            if (this.startDate) {
                if (!this.endDate) {
                    this.endDate = this.startDate;
                }
                if (this.startTime) {
                    if (!this.endTime) {
                        if (this.startTime === '23:00') {
                            this.endTime = '23:59';
                        } else {
                            let startHours = this.startTime.slice(0, 2);
                            if (startHours === '23') {
                                this.endTime = '00:' + this.startTime.slice(3, 5);
                                // Ende = Folgetag des Enddatums (vorher: Tag des Monats auf das
                                // heutige Datum gesetzt und per UTC formatiert → falscher Monat/Vortag)
                                const nextDay = parseYmd(this.endDate);
                                if (nextDay) {
                                    nextDay.setDate(nextDay.getDate() + 1);
                                    this.endDate = toYmd(nextDay);
                                }
                            } else {
                                this.endTime = this.getNextHourString(this.startTime)
                            }
                        }
                    }
                }
            }
            this.validateStartBeforeEndTime();
            this.checkCollisions();
        },
        async validateStartBeforeEndTime() {
            this.error = null;
            if (this.startDate && this.endDate && this.startTime && this.endTime) {
                this.setCombinedTimeString(this.startDate, this.startTime, 'start');
                this.setCombinedTimeString(this.endDate, this.endTime, 'end');
                return await axios
                    .post('/events', {start: this.startFull, end: this.endFull}, {headers: {'X-Dry-Run': true}})
                    .catch(error => this.error = error.response.data.errors);
            }
        },
        addMinutes(date, minutes) {
            date.setMinutes(date.getMinutes() + minutes);
            return date;
        },
        setCombinedTimeString(date, time, target) {
            let combinedDateString = (date.toString() + ' ' + time);
            const offset = new Date(combinedDateString).getTimezoneOffset()

            if (target === 'start') {
                if (offset === -60) {
                    this.startFull = new Date(
                        new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 60)
                    ).toISOString().slice(0, 16);
                } else {
                    this.startFull = new Date(
                        new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 120)
                    ).toISOString().slice(0, 16);
                }
            } else if (target === 'end') {
                if (offset === -60) {
                    this.endFull = new Date(
                        new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 60)
                    ).toISOString().slice(0, 16);
                } else {
                    this.endFull = new Date(
                        new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 120)
                    ).toISOString().slice(0, 16);
                }
            }
        },
        getNextHourString(timeString) {
            let hours = timeString.slice(0, 2);
            let minutes = timeString.slice(3, 5);
            if ((Number(hours) + 1) < 10) {
                return '0' + (Number(hours) + 1) + ':' + minutes;
            } else {
                return (Number(hours) + 1) + ':' + minutes;
            }
        },
        async updateAndAnswerEvent() {
            if (this.sendingAnswer) {
                return;
            }
            this.sendingAnswer = true;
            return await axios
                .put('/events/' + this.event?.id, this.eventData())
                .then(() => { this.closeModal(true);})
                .catch(error => {
                    this.sendingAnswer = false;
                    this.error = error.response?.data?.errors;
                });
        },
        async singleSaveEvent(){
            return await axios
                .put('/events/' + this.event?.id, this.eventData())
                .then(() => { this.closeModal(true); this.closeSeriesEditModal() })
                .catch(error => this.error = error.response.data.errors);
        },
        async saveAllSeriesEvents(){
            this.allSeriesEvents = true;
            return await axios
                .put('/events/' + this.event?.id, this.eventData())
                .then(() => { this.closeModal(true); this.closeSeriesEditModal() })
                .catch(error => this.error = error.response.data.errors);
        },
        closeSeriesEditModal(){
            this.showSeriesEdit = false;
        },
        async afterConfirm(bool) {
            if (!bool) return this.deleteComponentVisible = false;

            return await axios
                .delete(`/events/${this.event.id}`)
                .then(() => this.closeModal(true));
        },
        async approveRequest(event) {
            this.answerRequestForm.accepted = true;
            this.answerRequestForm.put(route('events.accept', {event: event.id}));
            this.closeModal(true)
        },
        async declineRequest(event) {
            this.answerRequestForm.accepted = false;
            this.answerRequestForm.put(route('events.accept', {event: event}));
            this.closeModal(true)
        },
        chooseProject(project) {
            this.selectedProject = project;
            this.projectName = '';
        },
        eventData() {
            return {
                title: this.title,
                eventName: this.eventName,
                start: this.formatDate(this.startDate, this.startTime),
                end: this.formatDate(this.endDate, this.endTime),
                roomId: this.selectedRoom?.id,
                description: this.description,
                audience: this.audience,
                isLoud: this.isLoud,
                isOption: this.isOption,
                eventNameMandatory: this.selectedEventType?.individual_name,
                projectId: this.selectedProject?.id ?? null,
                projectName: this.creatingProject ? this.projectName : '',
                eventTypeId: this.selectedEventType?.id,
                projectIdMandatory: this.selectedEventType?.project_mandatory && !this.creatingProject,
                creatingProject: this.creatingProject,
                declinedRoomId: this.declinedRoomId,
                is_series: this.series,
                seriesFrequency: this.selectedFrequency.id,
                seriesEndDate: this.seriesEndDate,
                allSeriesEvents: this.allSeriesEvents,
                adminComment: this.adminComment,
                optionString: this.optionAccept ? this.optionString : null,
                accept: this.accept,
                optionAccept: this.optionAccept,
                noNotifications: true
            };
        },
    },
}
</script>
