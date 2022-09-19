<template>
    <app-layout>
        <div class="max-w-screen-xl my-12 ml-20 mr-10">
            <div class="flex-wrap">
                <div class="flex">
                    <h2 class="font-bold font-lexend text-3xl">{{ room.name }}</h2>
                    <Menu as="div" class="my-auto relative">
                        <div class="flex"
                            v-if="this.$page.props.is_admin || this.$page.props.can.admin_rooms || this.is_room_admin">
                            <MenuButton
                                class="flex ml-6">
                                <DotsVerticalIcon class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                    aria-hidden="true"/>
                            </MenuButton>
                            <div v-if="$page.props.can.show_hints" class="absolute flex w-48 ml-12">
                                <div>
                                    <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                                </div>
                                <div class="flex">
                                    <span class="font-nanum ml-2 text-secondary tracking-tight tracking-tight text-lg">Bearbeite den Raum</span>
                                </div>
                            </div>
                        </div>
                        <transition enter-active-class="transition ease-out duration-100"
                            enter-from-class="transform opacity-0 scale-95"
                            enter-to-class="transform opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-75"
                            leave-from-class="transform opacity-100 scale-100"
                            leave-to-class="transform opacity-0 scale-95">
                            <MenuItems
                                class="origin-top-left absolute left-0 mr-4 mt-2 w-72 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                <div class="py-1">
                                    <MenuItem v-slot="{ active }">
                                        <a @click="openEditRoomModal(room)"
                                            :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <PencilAltIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            Bearbeiten
                                        </a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <a href="#" @click="duplicateRoom(room)"
                                            :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <DuplicateIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            Duplizieren
                                        </a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <a @click="openSoftDeleteRoomModal(room)"
                                            :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <TrashIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            In den Papierkorb
                                        </a>
                                    </MenuItem>
                                </div>
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>
                <div v-if="room.temporary === 1" class="font-lexend my-4 font-semibold">
                    {{ room.start_date }} - {{ room.end_date }}
                </div>
                <div class="grid grid-cols-7 mt-6">
                    <div class="col-span-5 mr-14">
                        <span class="text-secondary subpixel-antialiased">
                            {{ room.area.name }}
                        </span>
                        <p class="text-secondary subpixel-antialiased mt-4">
                            Kann von jedem gebucht werden: <label v-if="room.everyone_can_book">Ja</label>
                            <label v-else>Nein</label>
                        </p>
                        <span class="flex mt-6 text-secondary text-sm subpixel-antialiased">
                            {{ room.description }}
                        </span>
                    </div>
                    <div class="col-span-2">
                        <div class="flex w-full mt-6 items-center mb-8">
                            <h3 class="text-xl leading-6 font-bold font-lexend text-primary"> Dokumente </h3>
                        </div>
                        <div v-if="this.$page.props.is_admin || this.$page.props.can.admin_rooms || this.is_room_admin">
                            <input
                                @change="uploadChosenDocuments"
                                class="hidden"
                                ref="room_files"
                                id="file"
                                type="file"
                                multiple
                            />
                            <div @click="selectNewFiles" @dragover.prevent
                                @drop.stop.prevent="uploadDraggedDocuments($event)" class="mb-8 w-full flex justify-center items-center
                        border-secondary border-dotted border-2 h-40 bg-stone-100 p-2 cursor-pointer">
                                <p class="text-secondary text-center">Ziehe Dokumente hier her
                                    <br>oder klicke ins Feld
                                </p>
                            </div>
                            <jet-input-error :message="uploadDocumentFeedback"/>
                        </div>
                        <div class="space-y-1">
                            <div v-for="room_file in room.room_files"
                                class="cursor-pointer group flex items-center">
                                <DocumentTextIcon class="h-5 w-5 flex-shrink-0" aria-hidden="true"/>
                                <p :data-tooltip-target="room_file.name" @click="downloadFile(room_file)" class="ml-2 truncate hover:font-bold">
                                    {{
                                        room_file.name
                                    }}</p>
                                <XCircleIcon v-if="this.$page.props.is_admin || this.$page.props.can.admin_rooms || this.is_room_admin" @click="removeFile(room_file)"
                                    class="ml-2 hidden group-hover:block h-5 w-5 text-error flex-shrink-0"
                                    aria-hidden="true"/>
                                <div>
                                    <div :id="room_file.name" role="tooltip"
                                        class="max-w-md inline-block flex flex-wrap absolute invisible z-10 py-3 px-3 text-sm font-medium text-secondary bg-primary shadow-sm opacity-0 transition-opacity duration-300 tooltip">
                                        <div class="flex flex-wrap">
                                            Um die Datei herunterzuladen, klicke auf den Dateinamen
                                        </div>
                                        <div class="tooltip-arrow" data-popper-arrow></div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center mt-16 mr-8">
                <span class="font-bold font-lexend text-2xl w-full">
                    Raumadmin
                </span>
                    <div class="mt-4" v-if="roomForm.room_admins.length === 0">
                        <span class="text-secondary subpixel-antialiased cursor-pointer">Noch keine Raumadmins festgelegt</span>
                    </div>
                    <div v-else class="mt-4 -mr-3" v-for="user in room.room_admins">
                        <img :data-tooltip-target="user.id" class="h-9 w-9 rounded-full"
                            :src="user.profile_photo_url"
                            alt=""/>
                        <UserTooltip :user="user"/>
                    </div>
                    <button @click="openChangeRoomAdminsModal()" v-if="this.$page.props.is_admin || this.$page.props.can.admin_rooms || this.is_room_admin">
                        <PencilAltIcon class="mt-4 ml-6 h-6 w-6"/>
                    </button>
                </div>

                <div class="flex flex-wrap">
                    <span class="font-bold mt-12 font-lexend text-2xl w-full" v-if="room.event_requests.length !== 0">
                    Offene Belegungsanfragen
                    </span>
                    <div v-for="eventRequest in room.event_requests" class="flex flex-wrap w-full items-center">
                        <div class="flex w-full items-center flex-wrap">
                            <div class="flex items-center w-full mt-8">
                                <div class="flex items-center w-full">
                                    <EventTypeIconCollection :height="26" :width="26"
                                        :iconName="eventRequest.event_type.svg_name"/>
                                    <div
                                        class="whitespace-nowrap ml-2 text-lg flex leading-6 font-bold font-lexend text-gray-900">
                                        {{ eventRequest.event_type.name }}
                                        <img src="/Svgs/IconSvgs/icon_public.svg" v-if="eventRequest.audience"
                                            class="h-5 w-5 ml-2 my-auto"/>
                                        <img src="/Svgs/IconSvgs/icon_loud.svg" v-if="eventRequest.is_loud"
                                            class="h-5 w-5 ml-2 my-auto"/>
                                    </div>

                                    <div class="flex w-full whitespace-nowrap ml-3"
                                        v-if="eventRequest.start_time.split(',')[0] === eventRequest.end_time.split(',')[0]">
                                        {{ getGermanWeekdayAbbreviation(eventRequest.start_time_weekday) }}, {{
                                            eventRequest.start_time.split(',')[0]
                                        }},{{ eventRequest.start_time.split(',')[1] }}
                                        - {{ eventRequest.end_time.split(',')[1] }}
                                    </div>
                                    <div class="flex w-full whitespace-nowrap ml-3" v-else>
                                        {{ getGermanWeekdayAbbreviation(eventRequest.start_time_weekday) }},
                                        {{ eventRequest.start_time }} -
                                        {{ getGermanWeekdayAbbreviation(eventRequest.end_time_weekday) }},
                                        {{ eventRequest.end_time }}
                                    </div>
                                    <button @click="openApproveRequestModal(eventRequest)" type="button"
                                        class="flex my-auto ml-6 p-0.5 items-center border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none hover:bg-success">
                                        <CheckIcon class="h-4 w-4 flex flex-shrink" aria-hidden="true"/>
                                    </button>
                                    <button @click="openDeclineRequestModal(eventRequest)" type="button"
                                        class="flex my-auto ml-6 p-0.5 items-center border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none hover:bg-error">
                                        <XIcon class="h-4 w-4 flex flex-shrink" aria-hidden="true"/>
                                    </button>
                                </div>
                            </div>
                            <div class="flex items-center w-full ml-24 ">
                                <div v-if="eventRequest.project" class="w-64">
                                    <div class="text-secondary text-sm flex items-center">
                                        Zugeordnet zu
                                        <Link :href="route('projects.show',{project: eventRequest.project.id})"
                                            class="text-secondary font-black leading-3 subpixel-antialiased ml-2">
                                            {{ eventRequest.project.name }}
                                        </Link>
                                    </div>
                                    <!--
                                                                        <div v-for="projectLeader in eventRequest.project.project_managers">
                                                                            <img :data-tooltip-target="projectLeader.id"
                                                                                 :src="projectLeader.profile_photo_url"
                                                                                 :alt="projectLeader.name"
                                                                                 class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                                                                            <UserTooltip :user="projectLeader"/>
                                                                        </div>
                                    -->
                                </div>
                                <div class="text-secondary text-sm w-64" v-else>
                                    Keinem Projekt zugeordnet
                                </div>

                                <div class="flex text-sm text-secondary items-center">
                                    angefragt:<img :data-tooltip-target="eventRequest.created_by.id"
                                    :src="eventRequest.created_by.profile_photo_url"
                                    :alt="eventRequest.created_by.name"
                                    class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                                    <UserTooltip :user="eventRequest.created_by"/>
                                    <span class="ml-2"> {{ eventRequest.created_at }}</span>
                                </div>
                                <div>

                                </div>
                            </div>

                            <div class="flex ml-40 mt-2 text-sm text-secondary items-center w-full"
                                v-if="eventRequest.description">
                                {{ eventRequest.description }}
                            </div>


                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="this.$page.props.is_admin || this.$page.props.can.admin_rooms || this.is_room_admin">
            <CalendarComponent :eventTypes="this.event_types" :room="room"/>
        </div>

        <!-- Change RoomAdmins Modal -->
        <jet-dialog-modal :show="showChangeRoomAdminsModal" @close="closeChangeRoomAdminsModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_room_admin_edit.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-3">
                    <div class="font-bold font-lexend text-primary text-2xl my-2">
                        Raumadmin bearbeiten
                    </div>
                    <XIcon @click="closeChangeRoomAdminsModal"
                        class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                        aria-hidden="true"/>
                    <div class="text-secondary tracking-tight leading-6 sub">
                        Tippe den Namen der Nutzer*innen ein, welche den Raum bearbeiten und direkt belegen dürfen.
                    </div>
                    <div class="mt-6 relative">
                        <div class="my-auto w-full">
                            <input id="userSearch" v-model="user_query" type="text" autocomplete="off"
                                class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                                placeholder="placeholder"/>
                            <label for="userSearch"
                                class="absolute left-0 -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name</label>
                        </div>

                        <transition leave-active-class="transition ease-in duration-100"
                            leave-from-class="opacity-100"
                            leave-to-class="opacity-0">
                            <div v-if="user_search_results.length > 0 && user_query.length > 0"
                                class="absolute z-10 mt-1 w-full max-h-60 bg-primary shadow-lg
                                         text-base ring-1 ring-black ring-opacity-5
                                         overflow-auto focus:outline-none sm:text-sm">
                                <div class="border-gray-200">
                                    <div v-for="(user, index) in user_search_results" :key="index"
                                        class="flex items-center cursor-pointer">
                                        <div class="flex-1 text-sm py-4">
                                            <p @click="addUserToRoomAdminsArray(user)"
                                                class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                {{ user.first_name }} {{ user.last_name }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </transition>
                    </div>
                    <div class="mt-4">
                        <div class="flex">
                        </div>
                        <span v-for="(user,index) in roomForm.room_admins"
                            class="flex mt-4 mr-1 rounded-full items-center font-bold text-primary">
                            <div class="flex items-center">
                                <img class="flex h-11 w-11 rounded-full"
                                    :src="user.profile_photo_url"
                                    alt=""/>
                                <span class="flex ml-4">
                                {{ user.first_name }} {{ user.last_name }}
                                    </span>
                            </div>
                            <button type="button" @click="deleteUserFromRoomAdminArray(user)">
                                <span class="sr-only">User als Raumadmin entfernen</span>
                                <XCircleIcon class="ml-2 mt-1 h-5 w-5 hover:text-error "/>
                            </button>
                        </span>
                    </div>
                    <button @click="editRoomAdmins"
                        class=" inline-flex mt-8 items-center px-12 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                    >Speichern
                    </button>

                </div>

            </template>

        </jet-dialog-modal>
        <!-- Raum Bearbeiten-->
        <jet-dialog-modal :show="showEditRoomModal" @close="closeEditRoomModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_room_edit.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-3">
                    <div class="font-bold font-lexend text-primary text-3xl my-2">
                        Raum bearbeiten
                    </div>
                    <XIcon @click="closeEditRoomModal"
                        class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                        aria-hidden="true"/>
                    <div class="mt-4">
                        <div class="flex mt-10 relative">
                            <input id="roomNameEdit" v-model="editRoomForm.name" type="text"
                                class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                                placeholder="placeholder"/>
                            <label for="roomNameEdit"
                                class="absolute left-0 text-base -top-4 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Raumname
                            </label>
                            <jet-input-error :message="editRoomForm.error" class="mt-2"/>
                        </div>
                        <div class="mt-8 mr-4">
                                            <textarea
                                                placeholder="Kurzbeschreibung"
                                                v-model="editRoomForm.description" rows="4"
                                                class="shadow-sm placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 border-2 block w-full "/>
                        </div>
                        <div class="flex items-center my-6">
                            <input v-model="editRoomForm.temporary"
                                type="checkbox"
                                class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <p :class="[editRoomForm.temporary ? 'text-primary font-black' : 'text-secondary']"
                                class="ml-4 my-auto text-sm">Temporärer Raum</p>
                            <div v-if="$page.props.can.show_hints" class="flex mt-1">
                                <SvgCollection svgName="arrowLeft" class="h-6 w-6 ml-2 mr-2 mt-4"/>
                                <span
                                    class="font-nanum text-secondary tracking-tight ml-1 my-auto tracking-tight text-xl">Richte einen temporären Raum ein - z.B wenn ein Teil eines Raumes abgetrennt wird. Dieser wird nur in diesem Zeitraum im Kalender angezeigt.</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-x-3" v-if="editRoomForm.temporary">
                            <input
                                v-model="editRoomForm.start_date_dt_local" id="startDateEdit"
                                placeholder="Zu erledigen bis?" type="date"
                                class="border-gray-300 col-span-1 placeholder-secondary mr-2 w-full"/>
                            <input
                                v-model="editRoomForm.end_date_dt_local" id="endDateEdit"
                                placeholder="Zu erledigen bis?" type="date"
                                class="border-gray-300 col-span-1 placeholder-secondary w-full"/>
                        </div>

                        <div class="flex items-center my-6">
                            <input v-model="editRoomForm.everyone_can_book"
                                type="checkbox"
                                class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <p :class="[editRoomForm.everyone_can_book ? 'text-primary font-black' : 'text-secondary']"
                                class="ml-4 my-auto text-sm">Kann von jedem fest gebucht werden</p>
                            <div v-if="$page.props.can.show_hints" class="flex mt-1">
                                <SvgCollection svgName="arrowLeft" class="h-6 w-6 ml-2 mr-2 mt-4"/>
                                <span
                                    class="font-nanum text-secondary tracking-tight ml-1 my-auto tracking-tight text-xl">Entscheidet, ob dieser Raum von jedem, oder nur von den Raum Admins fest gebucht werden kann.</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2">
                            <button :class="[editRoomForm.name.length === 0 ?
                    'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                                class="mt-4 col-span-1 mr-1.5 flex items-center px-24 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="editRoom"
                                :disabled="editRoomForm.name.length === 0">
                                Speichern
                            </button>
                        </div>

                    </div>
                </div>
            </template>
        </jet-dialog-modal>
        <!-- Delete Room Modal -->
        <jet-dialog-modal :show="showSoftDeleteRoomModal" @close="closeSoftDeleteRoomModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="font-black font-lexend text-primary text-3xl my-2">
                        Raum in den Papierkorb
                    </div>
                    <XIcon @click="closeSoftDeleteRoomModal"
                        class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                        aria-hidden="true"/>
                    <div class="text-error subpixel-antialiased">
                        Bist du sicher, dass du den Raum {{ roomToSoftDelete.name }} in den Papierkorb legen möchtest?
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                            @click="softDeleteRoom()">
                            Entfernen
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeSoftDeleteRoomModal()"
                                class="text-secondary subpixel-antialiased cursor-pointer">Nein, doch nicht</span>
                        </div>
                    </div>
                </div>
            </template>
        </jet-dialog-modal>
        <!-- Success Modal -->
        <jet-dialog-modal :show="showSuccessModal" @close="closeSuccessModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_success.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="font-bold text-primary font-lexend text-2xl my-2">
                        {{ successHeading }}
                    </div>
                    <XIcon @click="closeSuccessModal"
                        class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                        aria-hidden="true"/>
                    <div class="text-success subpixel-antialiased">
                        {{ successDescription }}
                    </div>
                    <div class="mt-6">
                        <button class="bg-success focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                            @click="closeSuccessModal">
                            <CheckIcon class="h-6 w-6 text-secondaryHover"/>
                        </button>
                    </div>
                </div>

            </template>
        </jet-dialog-modal>
        <!-- Approve Request Modal -->
        <jet-dialog-modal :show="showApproveRequestModal" @close="closeApproveRequestModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_appointment_edit.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="font-bold text-primary font-lexend text-2xl my-2">
                        Raumbelegung zusagen
                    </div>
                    <XIcon @click="closeApproveRequestModal"
                        class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                        aria-hidden="true"/>
                    <div class="text-success subpixel-antialiased">
                        Bist du sicher, dass du die Raumbelegung zusagen möchtest?
                    </div>
                    <div class="flex flex-wrap w-full items-center">
                        <div class="flex w-full items-center flex-wrap">

                            <div class="flex items-center w-full mt-4">
                                <div class="flex items-center ml-12 w-full">
                                    <EventTypeIconCollection :height="26" :width="26"
                                        :iconName="requestToApprove.event_type.svg_name"/>
                                    <div
                                        class="whitespace-nowrap ml-2 text-lg flex leading-6 font-bold font-lexend text-gray-900">
                                        {{ requestToApprove.event_type.name }}
                                        <AdjustmentsIcon v-if="requestToApprove.occupancy_option"
                                            class="h-5 w-5 ml-2 my-auto"/>
                                        <img src="/Svgs/IconSvgs/icon_public.svg" v-if="requestToApprove.audience"
                                            class="h-5 w-5 ml-2 my-auto"/>
                                        <img src="/Svgs/IconSvgs/icon_loud.svg" v-if="requestToApprove.is_loud"
                                            class="h-5 w-5 ml-2 my-auto"/>
                                    </div>

                                    <div class="flex w-full whitespace-nowrap ml-3"
                                        v-if="requestToApprove.start_time.split(',')[0] === requestToApprove.end_time.split(',')[0]">
                                        {{ getGermanWeekdayAbbreviation(requestToApprove.start_time_weekday) }}, {{
                                            requestToApprove.start_time.split(',')[0]
                                        }},{{ requestToApprove.start_time.split(',')[1] }}
                                        - {{ requestToApprove.end_time.split(',')[1] }}
                                    </div>
                                    <div class="flex w-full whitespace-nowrap ml-3" v-else>
                                        {{ getGermanWeekdayAbbreviation(requestToApprove.start_time_weekday) }},
                                        {{ requestToApprove.start_time }} -
                                        {{ getGermanWeekdayAbbreviation(requestToApprove.end_time_weekday) }},
                                        {{ requestToApprove.end_time }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center w-full ml-2 justify-between">
                                <div v-if="requestToApprove.project" class="w-80">
                                    <div class="ml-16 text-secondary text-sm flex items-center">
                                        Zugeordnet zu
                                        <div class="text-secondary font-black leading-3 subpixel-antialiased ml-2">
                                            {{ requestToApprove.project.name }}
                                        </div>
                                    </div>
                                    <!--
                                    <div v-for="projectLeader in requestToApprove.project.project_managers">
                                        <img :data-tooltip-target="projectLeader.id"
                                             :src="projectLeader.profile_photo_url"
                                             :alt="projectLeader.name"
                                             class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                                        <UserTooltip :user="projectLeader"/>
                                    </div>
                                    -->
                                </div>
                                <div class="text-secondary text-sm ml-10" v-else>
                                    Keinem Projekt zugeordnet
                                </div>
                                <div class="flex text-sm text-secondary items-center">
                                    angefragt:<img :data-tooltip-target="requestToApprove.created_by.id"
                                    :src="requestToApprove.created_by.profile_photo_url"
                                    :alt="requestToApprove.created_by.name"
                                    class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                                    <UserTooltip :user="requestToApprove.created_by"/>
                                    <span class="ml-2"> {{ requestToApprove.created_at }}</span>
                                </div>
                                <div>

                                </div>
                            </div>
                            <div class="flex ml-12 mt-2 text-sm text-secondary items-center w-full"
                                v-if="requestToApprove.description">
                                {{ requestToApprove.description }}
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                            @click="approveRequest">
                            Zusagen
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeApproveRequestModal"
                                class="text-secondary subpixel-antialiased cursor-pointer">Nein, doch nicht</span>
                        </div>
                    </div>
                </div>
            </template>
        </jet-dialog-modal>
        <!-- Decline Request Modal -->
        <jet-dialog-modal :show="showDeclineRequestModal" @close="closeDeclineRequestModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_appointment_warning.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="font-black font-lexend text-primary text-3xl my-2">
                        Raumbelegung absagen
                    </div>
                    <XIcon @click="closeDeclineRequestModal"
                        class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                        aria-hidden="true"/>
                    <div class="text-error subpixel-antialiased">
                        Bist du sicher, dass du die Raumbelegung absagen möchtest?
                    </div>
                    <div class="flex flex-wrap w-full items-center">
                        <div class="flex w-full items-center flex-wrap">

                            <div class="flex items-center w-full mt-4">
                                <div class="flex items-center ml-12 w-full">
                                    <EventTypeIconCollection :height="26" :width="26"
                                        :iconName="requestToDecline.event_type.svg_name"/>
                                    <div
                                        class="whitespace-nowrap ml-2 text-lg flex leading-6 font-bold font-lexend text-gray-900">
                                        {{ requestToDecline.event_type.name }}
                                        <AdjustmentsIcon v-if="requestToDecline.occupancy_option"
                                            class="h-5 w-5 ml-2 my-auto"/>
                                        <img src="/Svgs/IconSvgs/icon_public.svg" v-if="requestToDecline.audience"
                                            class="h-5 w-5 ml-2 my-auto"/>
                                        <img src="/Svgs/IconSvgs/icon_loud.svg" v-if="requestToDecline.is_loud"
                                            class="h-5 w-5 ml-2 my-auto"/>
                                    </div>

                                    <div class="flex w-full whitespace-nowrap ml-3"
                                        v-if="requestToDecline.start_time.split(',')[0] === requestToDecline.end_time.split(',')[0]">
                                        {{ getGermanWeekdayAbbreviation(requestToDecline.start_time_weekday) }}, {{
                                            requestToDecline.start_time.split(',')[0]
                                        }},{{ requestToDecline.start_time.split(',')[1] }}
                                        - {{ requestToDecline.end_time.split(',')[1] }}
                                    </div>
                                    <div class="flex w-full whitespace-nowrap ml-3" v-else>
                                        {{ getGermanWeekdayAbbreviation(requestToDecline.start_time_weekday) }},
                                        {{ requestToDecline.start_time }} -
                                        {{ getGermanWeekdayAbbreviation(requestToDecline.end_time_weekday) }},
                                        {{ requestToDecline.end_time }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center w-full ml-2 justify-between">
                                <div v-if="requestToDecline.project" class="w-80">
                                    <div class="ml-16 text-secondary text-sm flex items-center">
                                        Zugeordnet zu
                                        <div class="text-secondary font-black leading-3 subpixel-antialiased ml-2">
                                            {{ requestToDecline.project.name }}
                                        </div>
                                    </div>
                                    <!--
                                    <div v-for="projectLeader in requestToApprove.project.project_managers">
                                        <img :data-tooltip-target="projectLeader.id"
                                             :src="projectLeader.profile_photo_url"
                                             :alt="projectLeader.name"
                                             class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                                        <UserTooltip :user="projectLeader"/>
                                    </div>
                                    -->
                                </div>
                                <div class="text-secondary text-sm ml-10" v-else>
                                    Keinem Projekt zugeordnet
                                </div>
                                <div class="flex text-sm text-secondary items-center">
                                    angefragt:<img :data-tooltip-target="requestToDecline.created_by.id"
                                    :src="requestToDecline.created_by.profile_photo_url"
                                    :alt="requestToDecline.created_by.name"
                                    class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                                    <UserTooltip :user="requestToDecline.created_by"/>
                                    <span class="ml-2"> {{ requestToDecline.created_at }}</span>
                                </div>
                                <div>

                                </div>
                            </div>
                            <div class="flex ml-12 mt-2 text-sm text-secondary items-center w-full"
                                v-if="requestToDecline.description">
                                {{ requestToDecline.description }}
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                            @click="declineRequest">
                            Absagen
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeDeclineRequestModal"
                                class="text-secondary subpixel-antialiased cursor-pointer">Nein, doch nicht</span>
                        </div>
                    </div>
                </div>
            </template>
        </jet-dialog-modal>
    </app-layout>
</template>

<script>

import AppLayout from '@/Layouts/AppLayout.vue'
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems
} from "@headlessui/vue";
import {DocumentTextIcon, DuplicateIcon, PencilAltIcon, TrashIcon, XIcon} from "@heroicons/vue/outline";
import {CheckIcon, ChevronDownIcon, DotsVerticalIcon, PlusSmIcon, XCircleIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import JetButton from "@/Jetstream/Button";
import JetDialogModal from "@/Jetstream/DialogModal";
import JetInput from "@/Jetstream/Input";
import JetInputError from "@/Jetstream/InputError";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import {Link, useForm} from "@inertiajs/inertia-vue3";
import UserTooltip from "@/Layouts/Components/UserTooltip";
import EventTypeIconCollection from "@/Layouts/Components/EventTypeIconCollection";
import CalendarComponent from "@/Layouts/Components/CalendarComponent";

const attributeFilters = [
    {name: 'Nur Anfragen', id: 1},
    {name: 'Nur laute Termine', id: 2},
    {name: 'Nur Termine mit Publikum', id: 3}
]

export default {
    name: "Show",
    props: ['room', 'event_types', 'projects', 'is_room_admin'],
    components: {
        TeamIconCollection,
        AppLayout,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        XIcon,
        PencilAltIcon,
        TrashIcon,
        DotsVerticalIcon,
        SvgCollection,
        XCircleIcon,
        JetButton,
        JetDialogModal,
        JetInput,
        JetInputError,
        CheckIcon,
        ChevronDownIcon,
        DocumentTextIcon,
        DuplicateIcon,
        UserTooltip,
        EventTypeIconCollection,
        PlusSmIcon,
        Link,
        Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        CalendarComponent,
    },
    computed: {
        eventTypeFilters: function () {
            let filters = [];
            this.event_types.forEach((eventType) => {
                filters.push({eventTypeId: eventType.id, name: eventType.name});
            })
            return filters;
        },
    },
    data() {
        return {
            showChangeRoomAdminsModal: false,
            showSuccess: false,
            user_query: "",
            user_search_results: [],
            uploadDocumentFeedback: "",
            showEditRoomModal: false,
            roomToSoftDelete: null,
            showSuccessModal: false,
            showSoftDeleteRoomModal: false,
            requestToDecline: null,
            requestToApprove: null,
            showApproveRequestModal: false,
            showDeclineRequestModal: false,
            successHeading: "",
            successDescription: "",
            roomForm: this.$inertia.form({
                _method: 'PUT',
                room_admins: this.room.room_admins,
            }),
            editRoomForm: useForm({
                id: null,
                name: '',
                description: '',
                temporary: false,
                start_date: null,
                start_date_dt_local: null,
                end_date: null,
                end_date_dt_local: null,
                area_id: null,
                user_id: null,
                everyone_can_book: this.room.everyone_can_book
            }),
            documentForm: useForm({
                file: null
            }),
            approveRequestForm: useForm({
                name: '',
                start_time: null,
                end_time: null,
                description: '',
                occupancy_option: false,
                is_loud: false,
                audience: false,
                room_id: null,
                project_id: null,
                event_type_id: null,
                user_id: this.$page.props.user.id,
            }),
            declineRequestForm: useForm({
                name: '',
                start_time: null,
                end_time: null,
                description: '',
                occupancy_option: false,
                is_loud: false,
                audience: false,
                room_id: null,
                project_id: null,
                event_type_id: null,
                user_id: this.$page.props.user.id,
            }),
        }
    },
    methods: {
        getGermanWeekdayAbbreviation(englishWeekday) {
            switch (englishWeekday) {
                case 'Monday':
                    return 'Mo';
                case 'Tuesday':
                    return 'Di';
                case 'Wednesday':
                    return 'Mi';
                case 'Thursday':
                    return 'Do';
                case 'Friday':
                    return 'Fr';
                case 'Saturday':
                    return 'Sa';
                case 'Sunday':
                    return 'So';
            }
        },

        openApproveRequestModal(eventRequest) {
            this.requestToApprove = eventRequest;
            this.showApproveRequestModal = true;
        },
        closeApproveRequestModal() {
            this.showApproveRequestModal = false;
            this.requestToApprove = null;
        },
        openDeclineRequestModal(eventRequest) {
            this.requestToDecline = eventRequest;
            this.showDeclineRequestModal = true;
        },
        closeDeclineRequestModal() {
            this.showDeclineRequestModal = false;
            this.requestToDecline = null;
        },
        approveRequest() {
            this.approveRequestForm.name = this.requestToApprove.name;
            this.approveRequestForm.start_time = this.requestToApprove.start_time_dt_local;
            this.approveRequestForm.end_time = this.requestToApprove.end_time_dt_local;
            this.approveRequestForm.description = this.requestToApprove.description;
            this.approveRequestForm.occupancy_option = false;
            this.approveRequestForm.is_loud = this.requestToApprove.is_loud;
            this.approveRequestForm.audience = this.requestToApprove.audience;
            if (this.requestToApprove.room) {
                this.approveRequestForm.room_id = this.requestToApprove.room.id;
            }
            if (this.requestToApprove.project) {
                this.approveRequestForm.project_id = this.requestToApprove.project.id;
            }
            this.approveRequestForm.event_type_id = this.requestToApprove.event_type.id;
            this.approveRequestForm.patch(route('events.update', {event: this.requestToApprove.id}));
            this.closeApproveRequestModal();
        },
        declineRequest() {
            this.approveRequestForm.name = this.requestToDecline.name;
            this.approveRequestForm.start_time = this.requestToDecline.start_time_dt_local;
            this.approveRequestForm.end_time = this.requestToDecline.end_time_dt_local;
            this.approveRequestForm.description = this.requestToDecline.description;
            this.approveRequestForm.occupancy_option = false;
            this.approveRequestForm.is_loud = this.requestToDecline.is_loud;
            this.approveRequestForm.audience = this.requestToDecline.audience;
            this.approveRequestForm.room_id = null;
            if (this.requestToDecline.project) {
                this.approveRequestForm.project_id = this.requestToDecline.project.id;
            }
            this.approveRequestForm.event_type_id = this.requestToDecline.event_type.id;
            this.approveRequestForm.patch(route('events.update', {event: this.requestToDecline.id}));
            this.closeDeclineRequestModal();
        },
        selectNewFiles() {
            this.$refs.room_files.click();
        },
        removeFile(room_file) {
            this.$inertia.delete(`/room_files/${room_file.id}`, {
                preserveState: true,
            })
        },
        downloadFile(room_file) {
            let link = document.createElement('a');
            link.href = route('download_room_file', {room_file: room_file});
            link.target = '_blank';
            link.click();
        },
        validateTypeAndUpload(files) {
            this.uploadDocumentFeedback = "";
            const forbiddenTypes = [
                "application/vnd.microsoft.portable-executable",
                "application/x-apple-diskimage",
            ]
            for (let file of files) {

                if (forbiddenTypes.includes(file.type) || file.type.match('video.*') || file.type === "") {
                    this.uploadDocumentFeedback = "Videos, .exe und .dmg Dateien werden nicht unterstützt"
                } else {
                    this.uploadDocumentToRoom(file)
                }
            }
        },
        uploadChosenDocuments(event) {
            this.validateTypeAndUpload([...event.target.files])
        },
        uploadDraggedDocuments(event) {
            this.validateTypeAndUpload([...event.dataTransfer.files])
        },
        uploadDocumentToRoom(file) {
            this.documentForm.file = file

            this.documentForm.post(`/rooms/${this.room.id}/files`, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    this.documentForm.file = null
                }
            })
        },
        openChangeRoomAdminsModal() {
            this.showChangeRoomAdminsModal = true;
        },
        closeChangeRoomAdminsModal() {
            this.showChangeRoomAdminsModal = false;
        },
        deleteUserFromRoomAdminArray(user) {
            this.roomForm.room_admins.splice(this.roomForm.room_admins.indexOf(user), 1);
        },
        closeSuccessModal() {
            this.showSuccessModal = false;
            this.successHeading = "";
            this.successDescription = "";
        },
        editRoomAdmins() {
            this.roomForm.patch(route('rooms.update', {room: this.room.id}));
            this.closeChangeRoomAdminsModal();
        },
        addUserToRoomAdminsArray(user) {
            for (let adminUser of this.roomForm.room_admins) {
                //if User is already Room Admin, do nothing
                if (user.id === adminUser.id) {
                    this.user_query = ""
                    return;
                }
            }
            this.roomForm.room_admins.push(user);
            this.user_query = "";
            this.user_search_results = []
        },
        duplicateRoom(room) {
            this.$inertia.post(`/rooms/${room.id}/duplicate`);
        },
        openEditRoomModal(room) {
            this.editRoomForm.id = room.id;
            this.editRoomForm.name = room.name;
            this.editRoomForm.description = room.description;
            this.editRoomForm.start_date = room.start_date;
            this.editRoomForm.end_date = room.end_date;
            this.editRoomForm.start_date_dt_local = room.start_date_dt_local;
            this.editRoomForm.end_date_dt_local = room.end_date_dt_local;
            if (room.temporary === 1) {
                this.editRoomForm.temporary = true;
            }
            this.showEditRoomModal = true;
        },
        closeEditRoomModal() {
            this.showEditRoomModal = false;
            this.editRoomForm.id = null;
            this.editRoomForm.name = null;
            this.editRoomForm.description = null;
            this.editRoomForm.start_date = null;
            this.editRoomForm.end_date = null;
            this.editRoomForm.start_date_dt_local = null;
            this.editRoomForm.end_date_dt_local = null;
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
            this.successHeading = "Raum im Papierkorb"
            this.successDescription = "Der Raum wurde erfolgreich in den Papierkorb gelegt."
            this.showSuccessModal = true;
            setTimeout(() => this.closeSuccessModal(), 2000);
        },
        editRoom() {
            this.editRoomForm.start_date = this.editRoomForm.start_date_dt_local;
            this.editRoomForm.end_date = this.editRoomForm.end_date_dt_local;
            this.editRoomForm.patch(route('rooms.update', {room: this.editRoomForm.id}));
            this.closeEditRoomModal();
        }
    },
    watch: {
        user_query: {
            handler() {
                if (this.user_query.length > 0) {
                    axios.get('/users/search', {
                        params: {query: this.user_query}
                    }).then(response => {
                        this.user_search_results = response.data
                    })
                }
            },
            deep: true
        }
    },
    setup() {
        return {
            attributeFilters,
        }
    }
}
</script>

<style scoped>

</style>
