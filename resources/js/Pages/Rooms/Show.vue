<template>
    <app-layout :title="room.name">
        <div class="mx-auto max-w-screen-xl px-4 py-10 sm:px-6 lg:px-10">
            <!-- Kopf: Name, Areal und alle Raum-Einstellungen auf einen Blick -->
            <div class="flex items-start gap-x-4">
                <span class="mt-2.5 h-4 w-4 shrink-0 rounded-full ring-2 ring-white shadow" :style="{ backgroundColor: effectiveRoomColor }" />
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-x-1">
                        <h2 class="font-lexend font-black text-[clamp(24px,3vw,30px)]/[34px] text-text truncate">{{ room.name }}</h2>
                        <BaseMenu :right="false" v-if="canEditRoom" class="ml-1">
                            <MenuItem v-slot="{ active }">
                                <a @click="openEditRoomModal(room)"
                                   :class="[active ? 'bg-text-inverse/10 text-accent-700' : 'text-text-subtle', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased capitalize']">
                                    <PropertyIcon name="IconEdit" stroke-width="1.5"
                                              class="mr-3 h-5 w-5 text-primaryText group-hover:text-accent-700"
                                              aria-hidden="true"/>
                                    {{$t('edit')}}
                                </a>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                                <a href="#" @click="duplicateRoom(room)"
                                   :class="[active ? 'bg-text-inverse/10 text-accent-700' : 'text-text-subtle', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                    <PropertyIcon name="IconCopy" stroke-width="1.5"
                                              class="mr-3 h-5 w-5 text-primaryText group-hover:text-accent-700"
                                              aria-hidden="true"/>
                                    {{ $t('Duplicate')}}
                                </a>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                                <a @click="openSoftDeleteRoomModal(room)"
                                   :class="[active ? 'bg-text-inverse/10 text-accent-700' : 'text-text-subtle', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                    <PropertyIcon name="IconTrash"  stroke-width="1.5"
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-accent-700"
                                                aria-hidden="true"/>
                                    {{$t('In the recycle bin')}}
                                </a>
                            </MenuItem>
                        </BaseMenu>
                    </div>
                    <div class="mt-1 text-sm text-text-muted">{{ room.area?.name }}</div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span v-if="room.temporary === true" class="inline-flex items-center gap-x-1.5 rounded-full border border-border-subtle bg-white px-3 py-1 text-xs font-medium text-text">
                            <PropertyIcon name="IconClockHour4" class="h-4 w-4" stroke-width="1.5" />
                            {{ $t('Temporary room') }}: {{ room.start_date }} – {{ room.end_date }}
                        </span>
                        <span class="inline-flex items-center gap-x-1.5 rounded-full border border-border-subtle bg-white px-3 py-1 text-xs font-medium" :class="room.everyone_can_book ? 'text-text' : 'text-text-subtle'">
                            <PropertyIcon :name="room.everyone_can_book ? 'IconUsers' : 'IconLock'" class="h-4 w-4" stroke-width="1.5" />
                            {{ room.everyone_can_book ? $t('Can be booked by anyone') : $t('Not bookable by everyone') }}
                        </span>
                        <span class="inline-flex items-center gap-x-1.5 rounded-full border border-border-subtle bg-white px-3 py-1 text-xs font-medium" :class="room.relevant_for_disposition ? 'text-text' : 'text-text-subtle'">
                            <PropertyIcon :name="room.relevant_for_disposition ? 'IconCalendarCheck' : 'IconCalendarOff'" class="h-4 w-4" stroke-width="1.5" />
                            {{ room.relevant_for_disposition ? $t('Relevant for disposition') : $t('Not relevant for disposition') }}
                        </span>
                        <span v-if="room.capacity" class="inline-flex items-center gap-x-1.5 rounded-full border border-border-subtle bg-white px-3 py-1 text-xs font-medium text-text">
                            <PropertyIcon name="IconArmchair" class="h-4 w-4" stroke-width="1.5" />
                            {{ $t('Capacity') }}: {{ room.capacity }}
                        </span>
                    </div>
                    <div v-if="room.room_history[0]?.changes?.[0]"
                         class="mt-3 subpixel-antialiased text-text-subtle text-xs flex items-center">
                        <div>
                            {{$t('last modified')}}:
                        </div>
                        <UserPopoverTooltip :user="room.room_history[0].changes[0].changed_by"
                                            :id="room.room_history[0].changes[0].changed_by?.id"
                                            height="4" width="4" class="ml-2"/>
                        <span class="ml-2 subpixel-antialiased">
                            {{ room.room_history[0].created_at }}
                        </span>
                        <button class="ml-4 subpixel-antialiased flex items-center cursor-pointer text-accent-600"
                                @click="openRoomHistoryModal()">
                            <IconChevronRight
                                class="-mr-0.5 h-4 w-4 group-hover:text-accent-700"
                                aria-hidden="true"/>
                            {{$t('View history')}}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Inhalt: alles sofort sichtbar, Bearbeiten je Karte über das Icon -->
            <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <RoomDetailCard title="Description" icon="IconAlignLeft" :can-edit="canEditRoom" @action="openEditRoomModal(room)">
                    <p v-if="room.description" class="whitespace-pre-line text-text-muted">{{ room.description }}</p>
                    <p v-else class="text-text-subtle">{{ $t('No description yet') }}</p>
                </RoomDetailCard>

                <RoomDetailCard title="Access to room" icon="IconShieldLock" :can-edit="canEditRoom" @action="showRoomAccessModal = true">
                    <div class="space-y-5">
                        <RoomAccessGroup
                            label="Room admin"
                            empty-text="No room admins available yet"
                            :users="room.room_admins ?? []"
                            :departments="adminDepartments"
                        />
                        <div>
                            <RoomAccessGroup
                                label="Authorized to request"
                                empty-text="So far, no user is authorized to make inquiries"
                                :users="room.requestable_by ?? []"
                                :departments="requestingDepartments"
                            />
                            <div v-if="$page.props.event_direct_booking_only" class="ui-hint mt-1">
                                {{ $t('No effect while "Events always bookable directly" is active.') }}
                            </div>
                        </div>
                    </div>
                </RoomDetailCard>

                <RoomDetailCard title="Room properties" icon="IconTags" :can-edit="canEditRoom" @action="showRoomAttributeEditModal = true">
                    <div v-if="roomCategories?.length || roomAttributes?.length || adjoiningRooms?.length" class="space-y-4">
                        <div v-for="group in propertyGroups" :key="group.label" v-show="group.items.length">
                            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-text-subtle">{{ $t(group.label) }}</div>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="item in group.items" :key="item.id" class="rounded-full border border-border-subtle bg-surface-sunken px-3 py-1 text-sm">
                                    {{ item.name }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-text-subtle">{{ $t('No properties selected yet') }}</p>
                </RoomDetailCard>

                <RoomDetailCard title="Documents" icon="IconFileText" :can-edit="canEditRoom" action-icon="IconUpload" action-label="Upload"
                                @action="showFileUploadModal = true">
                    <ul v-if="room.room_files?.length" class="divide-y divide-border-subtle">
                        <li v-for="roomFile in room.room_files" :key="roomFile.id" class="flex items-center gap-x-3 py-2">
                            <PropertyIcon name="IconFile" class="h-4 w-4 shrink-0 text-text-subtle" stroke-width="1.5" />
                            <button type="button" class="min-w-0 flex-1 truncate text-left hover:text-accent-700" @click="downloadFile(roomFile)">
                                {{ roomFile.name }}
                            </button>
                            <button type="button" class="rounded-full p-1 text-text-subtle hover:bg-surface-sunken hover:text-accent-700"
                                    :title="$t('Download')" @click="downloadFile(roomFile)">
                                <span class="sr-only">{{ $t('Download') }}</span>
                                <PropertyIcon name="IconDownload" class="h-4 w-4" stroke-width="1.75" />
                            </button>
                            <button v-if="canEditRoom" type="button" class="rounded-full p-1 text-text-subtle hover:bg-surface-sunken hover:text-danger"
                                    :title="$t('Delete')" @click="openFileDeleteModal(roomFile)">
                                <span class="sr-only">{{ $t('Delete') }}</span>
                                <PropertyIcon name="IconTrash" class="h-4 w-4" stroke-width="1.75" />
                            </button>
                        </li>
                    </ul>
                    <p v-else class="text-text-subtle">{{ $t('No documents available') }}</p>
                </RoomDetailCard>
            </div>
        </div>
        <!-- Raum Bearbeiten-->
        <ArtworkBaseModal @close="closeEditRoomModal" v-if="showEditRoomModal" :title="$t('Edit room')" description="">
                <div class="mx-3">
                    <form @submit.prevent="editRoom" class="grid grid-cols-1 gap-4">
                        <div>
                            <BaseInput
                                id="roomNameEdit"
                                v-model="editRoomForm.name"
                                label="Room name"/>
                            <jet-input-error :message="editRoomForm.errors.name" class="mt-2"/>
                        </div>
                        <div>
                            <BaseTextarea
                                label="Short description"
                                v-model="editRoomForm.description"
                                :rows="4"
                                id="descriptionEdit"
                            />
                        </div>
                        <div class="rounded-xl border border-border-subtle bg-surface-sunken/50 p-4">
                            <div class="text-sm text-text mb-1">{{ $t('Color') }}</div>
                            <div class="flex items-center mb-3">
                                <input v-model="editRoomInheritColor" id="inheritRoomColor" type="checkbox" class="input-checklist"/>
                                <label for="inheritRoomColor"
                                       :class="[editRoomInheritColor ? 'text-text font-black' : 'text-text-subtle']"
                                       class="ml-4 my-auto text-sm cursor-pointer">
                                    {{ $t('Inherit color from area') }}
                                </label>
                            </div>
                            <div v-if="!editRoomInheritColor">
                                <ColorPickerComponent @updateColor="(color) => editRoomForm.color = color" :color="editRoomForm.color ?? editRoomAreaColor"/>
                            </div>
                            <jet-input-error :message="editRoomForm.errors.color" class="mt-2"/>
                        </div>
                        <div class="flex items-center">
                            <input v-model="editRoomForm.temporary"
                                   type="checkbox"
                                   class="input-checklist"/>
                            <p :class="[editRoomForm.temporary ? 'text-text font-black' : 'text-text-subtle']"
                               class="ml-4 my-auto text-sm">{{$t('Temporary room')}}</p>
                            <div v-if="this.$page.props.show_hints" class="flex mt-1">
                                <SvgCollection svgName="arrowLeft" class="h-6 w-6 ml-2 mr-2"/>
                                <span
                                    class="ml-1 my-auto ">{{$t('Set up a temporary room - e.g. if part of a room is partitioned off. This is only displayed in the calendar during this period.')}}</span>
                            </div>
                        </div>
                        <div v-if="editRoomForm.temporary">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <BaseInput type="date"
                                    v-model="editRoomForm.start_date_dt_local"
                                    id="startDate"
                                    label="Start date"/>
                                <BaseInput type="date"
                                    v-model="editRoomForm.end_date_dt_local"
                                    id="endDate"
                                    label="End date"
                                />
                            </div>
                            <jet-input-error :message="editRoomForm.errors.start_date || editRoomForm.errors.end_date" class="mt-2"/>
                        </div>

                        <!-- "Termine immer direkt buchbar": Anfrage-Einstellungen des Raums sind ohne Wirkung -->
                        <div v-if="$page.props.event_direct_booking_only" class="rounded-lg border border-warning-border bg-warning-surface px-3 py-2 text-xs text-warning">
                            {{ $t('The setting "Events always bookable directly" is active. "Can be booked by anyone" and "Authorized to request" currently have no effect – everyone who may create events books this room directly. Room admins can still edit and delete other people\'s events in this room.') }}
                        </div>
                        <div class="flex items-center">
                            <input v-model="editRoomForm.everyone_can_book"
                                   type="checkbox"
                                   class="input-checklist"/>
                            <p :class="[editRoomForm.everyone_can_book ? 'text-text font-black' : 'text-text-subtle']"
                               class="ml-4 my-auto text-sm">{{ $t('Can be booked by anyone')}}<span v-if="$page.props.event_direct_booking_only" class="ml-1 font-normal text-text-subtle">({{ $t('no effect') }})</span></p>
                            <div v-if="this.$page.props.show_hints" class="flex mt-1">
                                <SvgCollection svgName="arrowLeft" class="h-6 w-6 ml-2 mr-2"/>
                                <span
                                    class="ml-1 my-auto ">{{ $t('Decides whether this room can be booked by everyone or only by the room admins.')}}</span>
                            </div>
                        </div>
                        <div class="flex items-start gap-x-4">
                            <input v-model="editRoomForm.relevant_for_disposition"
                                   type="checkbox"
                                   class="input-checklist"/>
                            <div>
                                <p :class="[editRoomForm.relevant_for_disposition ? 'text-text font-black' : 'text-text-subtle']"
                                   class="my-auto text-sm">{{ $t('Relevant for disposition')}}</p>
                                <span class="text-xs" :class="[editRoomForm.relevant_for_disposition ? 'text-text font-black' : 'text-text-subtle']">{{ $t('Activate this field if the room is to be included in the calendars.')}}</span>
                            </div>
                        </div>

                        <div class="mt-4">
                            <BaseInput
                                type="number"
                                id="room_capacity"
                                v-model.number="editRoomForm.capacity"
                                :label="$t('Capacity')"
                                :min="0"
                                :step="1"
                                :error="editRoomForm.errors.capacity"
                            />
                            <p class="mt-1 text-xs text-text-subtle">{{ $t('Used by the BI module as the seat capacity per performance (occupancy rate); projects can override it.') }}</p>
                        </div>

                        <div class="flex justify-center pt-8">
                            <BaseUIButton
                                :disabled="editRoomForm.name.length === 0 || editRoomForm.processing"
                                type="submit"
                                is-add-button
                                :label="$t('Save')"
                            />
                        </div>

                    </form>
                </div>
        </ArtworkBaseModal>
        <!-- Success Modal -->
        <SuccessModal
            :title="successHeading"
            :description="successDescription"
            :show="showSuccessModal"
            @closed="closeSuccessModal"
        />
    </app-layout>

    <RoomAccessModal
        v-if="showRoomAccessModal"
        :show="showRoomAccessModal"
        :room="room"
        @close="showRoomAccessModal = false"
    />
    <RoomAttributeEditModal
        :show="showRoomAttributeEditModal"
        :room="room"
        :categories="roomCategories"
        :available-categories="available_categories"
        :adjoining-rooms="adjoiningRooms"
        :available-adjoining-rooms="available_rooms"
        :attributes="roomAttributes"
        :available-attributes="available_attributes"
        @close="showRoomAttributeEditModal = false"
    />
    <RoomFileUploadModal
        :show="showFileUploadModal"
        :close-modal="() => showFileUploadModal = false"
        :room-id="room.id"
    />
    <FileDeleteModal
        :show="showFileDeleteModal"
        :close-modal="() => showFileDeleteModal = false"
        :file="roomFileToDelete"
        type="room"
    />

    <!-- Room History Modal-->
    <room-history-component
        v-if="showRoomHistory"
        :room_history="room.room_history"
        @closed="closeRoomHistoryModal"
    />
    <!-- Delete Room Modal -->
    <ConfirmationComponent v-if="showSoftDeleteRoomModal"
                           :confirm="$t('Delete room')"
                           :titel="$t('Room in the recycle bin')"
                           :description="roomDeleteDescriptionText"
                           @closed="afterSoftDeleteRoomConfirm"/>
</template>

<script>
import {IconCheck, IconChevronDown, IconChevronRight, IconCircleX, IconCopy, IconDotsVertical, IconEdit, IconFileText, IconMinus, IconPlus, IconTrash, IconX} from "@tabler/icons-vue";

import AppLayout from '@/Layouts/AppLayout.vue'
import {
    Disclosure,
    DisclosureButton,
    DisclosurePanel,
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems
} from "@headlessui/vue";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import JetButton from "@/Jetstream/Button.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import JetInput from "@/Jetstream/Input.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import {Link, useForm} from "@inertiajs/vue3";
import RoomHistoryComponent from "@/Layouts/Components/RoomHistoryComponent.vue";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import RoomAccessModal from "@/Layouts/Components/RoomAccessModal.vue";
import RoomAttributeEditModal from "@/Layouts/Components/RoomAttributeEditModal.vue";
import RoomFileUploadModal from "@/Layouts/Components/RoomFileUploadModal.vue";
import FileDeleteModal from "@/Layouts/Components/FileDeleteModal.vue";
import RoomDetailCard from "@/Pages/Rooms/Components/RoomDetailCard.vue";
import RoomAccessGroup from "@/Pages/Rooms/Components/RoomAccessGroup.vue";
import Permissions from "@/Mixins/Permissions.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import DateInputComponent from "@/Components/Inputs/DateInputComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import ColorPickerComponent from "@/Components/Globale/ColorPickerComponent.vue";

export default {
    mixins: [Permissions],
    name: "Show",
    props: [
        'room',
        'is_room_admin',
        'available_categories',
        'roomCategoryIds',
        'roomCategories',
        'available_attributes',
        'roomAttributeIds',
        'roomAttributes',
        'available_rooms',
        'adjoiningRoomIds',
        'adjoiningRooms',
    ],
    components: {
        ColorPickerComponent,
        PropertyIcon,
        ArtworkBaseModal,
        BaseUIButton,
        BaseTextarea,
        DateInputComponent,
        TextareaComponent,
        TextInputComponent,
        ModalHeader,
        BaseModal,
        BaseMenu,
        FormButton,
        SuccessModal,
        ConfirmationComponent,
        UserPopoverTooltip,
        RoomAccessModal,
        RoomAttributeEditModal,
        RoomFileUploadModal,
        FileDeleteModal,
        RoomDetailCard,
        RoomAccessGroup,
        NewUserToolTip,
        IconPlus,
        IconMinus,
        Disclosure,
        DisclosureButton,
        DisclosurePanel,
        TeamIconCollection,
        AppLayout,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        IconX,
        IconEdit,
        IconTrash,
        IconDotsVertical,
        SvgCollection,
        IconCircleX,
        JetButton,
        JetDialogModal,
        JetInput,
        JetInputError,
        IconCheck,
        IconChevronDown,
        IconFileText,
        IconCopy,
        IconPlus,
        Link,
        Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        IconChevronRight,
        RoomHistoryComponent,
        BaseInput
    },
    computed: {
        // Bearbeiten-Icons nur für Raumverwaltung, Admins und Raumadmins (inkl. Team) – der Server prüft ohnehin
        canEditRoom() {
            return this.hasAdminRole() || this.$canAny(['create, delete and update rooms']) || this.is_room_admin;
        },
        effectiveRoomColor() {
            return this.room.color ?? this.room.area?.color ?? '#000000';
        },
        adminDepartments() {
            return (this.room.departments ?? []).filter((department) => department.is_admin);
        },
        requestingDepartments() {
            return (this.room.departments ?? []).filter((department) => department.can_request);
        },
        propertyGroups() {
            return [
                {label: 'Categories', items: this.roomCategories ?? []},
                {label: 'Properties', items: this.roomAttributes ?? []},
                {label: 'Adjoining rooms', items: this.adjoiningRooms ?? []},
            ];
        },
        requestsToShow: function () {
            let requestsToShow;
            if (this.hasAdminRole() || this.is_room_admin || this.$canAny(['create, delete and update rooms'])) {
                requestsToShow = this.room.event_requests
            }
            return requestsToShow
        },
        roomDeleteDescriptionText() {
            return this.$t('Are you sure you want to put the room {0} in the trash?', [this.roomToSoftDelete.name]);
        },
    },
    data() {
        return {
            showRoomAccessModal: false,
            showRoomAttributeEditModal: false,
            showFileUploadModal: false,
            showFileDeleteModal: false,
            roomFileToDelete: null,
            showEditRoomModal: false,
            roomToSoftDelete: null,
            showSuccessModal: false,
            showSoftDeleteRoomModal: false,
            showRoomHistory: false,
            successHeading: "",
            successDescription: "",
            // Kategorien, Eigenschaften, Nebenräume und Raumzugriff haben eigene Karten-Modals; das Modal schickt
            // sie bewusst nicht mit, sonst überschrieben veraltete Werte vom Seitenaufruf dortige Änderungen
            editRoomInheritColor: true,
            editRoomAreaColor: '#000000',
            editRoomForm: useForm({
                id: null,
                name: '',
                color: null,
                description: '',
                temporary: false,
                start_date: null,
                start_date_dt_local: null,
                end_date: null,
                end_date_dt_local: null,
                everyone_can_book: false,
                relevant_for_disposition: false,
                capacity: null,
            }),
        }
    },
    methods: {
        afterSoftDeleteRoomConfirm(confirmed) {
            if (confirmed) {
                this.softDeleteRoom()
            } else {
                this.closeSoftDeleteRoomModal()
            }
        },

        downloadFile(room_file) {
            let link = document.createElement('a');
            link.href = route('download_room_file', {room_file: room_file});
            link.target = '_blank';
            link.click();
        },
        closeSuccessModal() {
            this.showSuccessModal = false;
            this.successHeading = "";
            this.successDescription = "";
        },
        openFileDeleteModal(roomFile) {
            this.roomFileToDelete = roomFile;
            this.showFileDeleteModal = true;
        },
        duplicateRoom(room) {
            this.$inertia.post(`/rooms/${room.id}/duplicate`);
        },
        openEditRoomModal(room) {
            // bei jedem Öffnen aus dem aktuellen Raum befüllen, damit verworfene Eingaben nicht hängen bleiben
            this.editRoomForm.id = room.id;
            this.editRoomForm.name = room.name;
            this.editRoomForm.description = room.description ?? '';
            this.editRoomForm.color = room.color ?? null;
            this.editRoomInheritColor = !room.color;
            this.editRoomAreaColor = room.area?.color ?? '#000000';
            this.editRoomForm.temporary = room.temporary === true;
            this.editRoomForm.start_date = room.start_date;
            this.editRoomForm.end_date = room.end_date;
            this.editRoomForm.start_date_dt_local = room.start_date_dt_local;
            this.editRoomForm.end_date_dt_local = room.end_date_dt_local;
            this.editRoomForm.everyone_can_book = room.everyone_can_book === true;
            this.editRoomForm.relevant_for_disposition = room.relevant_for_disposition === true;
            this.editRoomForm.capacity = room.capacity ?? null;
            this.editRoomForm.clearErrors();
            this.showEditRoomModal = true;
        },
        closeEditRoomModal() {
            this.showEditRoomModal = false;
            this.editRoomForm.reset();
            this.editRoomForm.clearErrors();
        },
        openSoftDeleteRoomModal(room) {
            this.roomToSoftDelete = room;
            this.showSoftDeleteRoomModal = true;
        },
        closeSoftDeleteRoomModal() {
            this.showSoftDeleteRoomModal = false;
            this.roomToSoftDelete = null;
        },
        softDeleteRoom() {
            this.$inertia.delete(`/rooms/${this.roomToSoftDelete.id}`);
            this.closeSoftDeleteRoomModal();
            this.successHeading = this.$t('Room in the recycle bin')
            this.successDescription = this.$t('The room has been successfully moved to the trash.')
            this.showSuccessModal = true;
            setTimeout(() => this.closeSuccessModal(), 2000);
        },
        editRoom() {
            // Zeitraum nur für temporäre Räume mitschicken
            this.editRoomForm.start_date = this.editRoomForm.temporary ? (this.editRoomForm.start_date_dt_local || null) : null;
            this.editRoomForm.end_date = this.editRoomForm.temporary ? (this.editRoomForm.end_date_dt_local || null) : null;
            if (this.editRoomForm.capacity === '') {
                this.editRoomForm.capacity = null;
            }
            if (this.editRoomInheritColor) {
                // null = Farbe des Areals erben
                this.editRoomForm.color = null;
            } else if (!this.editRoomForm.color) {
                // Erben abgewählt, aber keine Farbe gewählt → aktuelle Areal-Farbe übernehmen
                this.editRoomForm.color = this.editRoomAreaColor;
            }
            // erst nach Erfolg schließen: bei Validierungsfehlern (z. B. temporär ohne Enddatum)
            // gingen sonst alle Änderungen still verloren
            this.editRoomForm.patch(route('rooms.update', {room: this.editRoomForm.id}), {
                preserveScroll: true,
                onSuccess: () => this.closeEditRoomModal(),
            });
        },
        openRoomHistoryModal() {
            this.showRoomHistory = true;
        },
        closeRoomHistoryModal() {
            this.showRoomHistory = false;
        },
    },
}
</script>

<style scoped>

</style>
