<template>
    <app-layout>
        <div class="py-4">
            <div class="max-w-screen-2xl mb-40 my-12 flex flex-row ml-20 mr-40">
                <div class="flex flex-1 flex-wrap">
                    <div class="w-full flex my-auto justify-between">
                        <div class="flex flex-wrap w-full">
                            <div class="flex flex-wrap w-full">
                                <h2 class="text-3xl font-lexend font-black text-primary flex">Räume & Areale</h2>
                                <div class="flex w-full justify-between mt-6">
                                    <div class="flex">
                                        <div>
                                            <AddButton @click="openAddAreaModal" text="Areal hinzufügen"/>
                                        </div>
                                        <div v-if="$page.props.can.show_hints" class="flex">
                                            <SvgCollection svgName="arrowLeft" class="ml-2"/>
                                            <span
                                                class="font-nanum text-secondary tracking-tight ml-1 tracking-tight text-xl">Lege neue Areale an</span>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <div class="flex w-full flex-wrap mt-10">
                                <div v-for="area in areas"
                                     class="flex w-full bg-white my-2 border border-gray-200">
                                    <button class="bg-black flex" @click="changeAreaStatus(area)">
                                        <ChevronUpIcon v-if="this.opened_areas.includes(area.id)"
                                                       class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
                                        <ChevronDownIcon v-else
                                                         class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
                                    </button>
                                    <div class="flex items-center w-full ml-4 flex-wrap p-4">
                                        <div class="flex justify-between w-full">
                                            <div class="my-auto flex items-center">
                                                <span class="text-2xl leading-6 font-bold font-lexend text-gray-900">
                                                    {{ area.name }}
                                                </span>
                                            </div>
                                            <div class="flex items-center">
                                                <Menu as="div" class="my-auto relative">
                                                    <div class="flex">
                                                        <MenuButton
                                                            class="flex ml-6">
                                                            <DotsVerticalIcon
                                                                class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                                                aria-hidden="true"/>
                                                        </MenuButton>
                                                    </div>
                                                    <transition
                                                        enter-active-class="transition ease-out duration-100"
                                                        enter-from-class="transform opacity-0 scale-95"
                                                        enter-to-class="transform opacity-100 scale-100"
                                                        leave-active-class="transition ease-in duration-75"
                                                        leave-from-class="transform opacity-100 scale-100"
                                                        leave-to-class="transform opacity-0 scale-95">
                                                        <MenuItems
                                                            class="origin-top-right absolute right-0 w-56 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                                            <div class="py-1">
                                                                <MenuItem v-slot="{ active }">
                                                                    <a @click="openEditAreaModal(area)"
                                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                        <PencilAltIcon
                                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                            aria-hidden="true"/>
                                                                        Bearbeiten
                                                                    </a>
                                                                </MenuItem>
                                                                <MenuItem v-slot="{ active }">
                                                                    <a href="#"
                                                                       @click="duplicateArea(area)"
                                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                        <DuplicateIcon
                                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                            aria-hidden="true"/>
                                                                        Duplizieren
                                                                    </a>
                                                                </MenuItem>
                                                                <MenuItem v-slot="{ active }">
                                                                    <a @click="openSoftDeleteAreaModal(area)"
                                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                        <TrashIcon
                                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                            aria-hidden="true"/>
                                                                        In den Papierkorb
                                                                    </a>
                                                                </MenuItem>
                                                                <MenuItem v-slot="{ active }">
                                                                    <a @click="openDeleteAllRoomsModal(area)"
                                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                        <PencilAltIcon
                                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                            aria-hidden="true"/>
                                                                        Alle Räume entfernen
                                                                    </a>
                                                                </MenuItem>
                                                            </div>
                                                        </MenuItems>
                                                    </transition>
                                                </Menu>
                                            </div>
                                        </div>
                                        <div class="flex w-full mt-6" v-if="this.opened_areas.includes(area.id)">
                                            <div class="">
                                                <AddButton @click="openAddRoomModal(area)" text="Raum hinzufügen"/>
                                            </div>
                                            <div v-if="$page.props.can.show_hints" class="flex">
                                                <SvgCollection svgName="arrowLeft" class="ml-2"/>
                                                <span
                                                    class="font-nanum text-secondary tracking-tight ml-1 tracking-tight text-xl">Lege neue Räume an</span>
                                            </div>
                                        </div>
                                        <div class="mt-6 mb-12" v-if="this.opened_areas.includes(area.id)">
                                            <draggable ghost-class="opacity-50"
                                                       key="draggableKey"
                                                       item-key="id" :list="area.rooms"
                                                       @start="dragging=true" @end="dragging=false"
                                                       @change="updateRoomOrder(area.rooms)">
                                                <template #item="{element}" :key="element.id">
                                                    <div v-show="!element.temporary" class="flex"
                                                         @mouseover="showMenu = element.id"
                                                         :key="element.id"
                                                         @mouseout="showMenu = null">
                                                        <div class="flex mt-6 flex-wrap w-full" :key="element.id"
                                                             :class="dragging? 'cursor-grabbing' : 'cursor-grab'">
                                                            <div class="flex w-full">
                                                                <div class="flex">
                                                                    <Link :href="route('rooms.show',{room: element.id,month_start: new Date((new Date).getFullYear(),(new Date).getMonth(),1,0,120),month_end:new Date((new Date).getFullYear(),(new Date).getMonth() + 1,2), calendarType: 'monthly'})"
                                                                          class="ml-4 my-auto text-lg font-black text-sm"
                                                                    >
                                                                        {{ element.name }}
                                                                    </Link>
                                                                    <div
                                                                        class="ml-6 flex items-center text-secondary text-sm my-auto">
                                                                        angelegt am {{ element.created_at }} von
                                                                        <img
                                                                            :data-tooltip-target="element.created_by.id"
                                                                            :src="element.created_by.profile_photo_url"
                                                                            :alt="element.created_by.first_name"
                                                                            class="rounded-full ml-2 h-6 w-6 object-cover cursor-pointer">
                                                                        <UserTooltip
                                                                            :user="element.created_by"></UserTooltip>
                                                                    </div>
                                                                </div>
                                                                <Menu as="div" class="my-auto relative"
                                                                      :key="element.id"
                                                                      v-show="showMenu === element.id">
                                                                    <div class="flex">
                                                                        <MenuButton
                                                                            class="flex ml-6">
                                                                            <DotsVerticalIcon
                                                                                class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                                                                aria-hidden="true"/>
                                                                        </MenuButton>
                                                                    </div>
                                                                    <transition
                                                                        enter-active-class="transition ease-out duration-100"
                                                                        enter-from-class="transform opacity-0 scale-95"
                                                                        enter-to-class="transform opacity-100 scale-100"
                                                                        leave-active-class="transition ease-in duration-75"
                                                                        leave-from-class="transform opacity-100 scale-100"
                                                                        leave-to-class="transform opacity-0 scale-95">
                                                                        <MenuItems
                                                                            class="origin-top-right absolute right-0 w-56 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                                                            <div class="py-1">
                                                                                <MenuItem v-slot="{ active }">
                                                                                    <a @click="openEditRoomModal(element)"
                                                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                        <PencilAltIcon
                                                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                            aria-hidden="true"/>
                                                                                        Bearbeiten
                                                                                    </a>
                                                                                </MenuItem>
                                                                                <MenuItem v-slot="{ active }">
                                                                                    <a href="#"
                                                                                       @click="duplicateRoom(element)"
                                                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                        <DuplicateIcon
                                                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                            aria-hidden="true"/>
                                                                                        Duplizieren
                                                                                    </a>
                                                                                </MenuItem>
                                                                                <MenuItem v-slot="{ active }">
                                                                                    <a @click="openSoftDeleteRoomModal(element)"
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

                                                        </div>

                                                    </div>
                                                </template>
                                            </draggable>
                                            <div v-show="area.rooms.filter(room => room.temporary === 1).length > 0"
                                                 class="mt-12">
                                                <h2 v-on:click="switchVisibility(area.id)"
                                                    class="text-sm pb-2 flex font-bold text-primary cursor-pointer">
                                                    Temporäre
                                                    Räume
                                                    <ChevronUpIcon v-if="showTemporaryRooms.includes(area.id)"
                                                                   class=" ml-1 mr-3 flex-shrink-0 mt-1 h-4 w-4"></ChevronUpIcon>
                                                    <ChevronDownIcon v-else
                                                                     class=" ml-1 mr-3 flex-shrink-0 mt-1 h-4 w-4"></ChevronDownIcon>
                                                </h2>
                                                <draggable v-show="showTemporaryRooms.includes(area.id)"
                                                           ghost-class="opacity-50"
                                                           key="draggableKey"
                                                           item-key="id" :list="area.rooms"
                                                           @start="dragging=true" @end="dragging=false"
                                                           @change="updateRoomOrder(area.rooms)">
                                                    <template #item="{element}" :key="element.id">
                                                        <div v-show="element.temporary" class="flex"
                                                             @mouseover="showMenu = element.id"
                                                             :key="element.id"
                                                             @mouseout="showMenu = null">
                                                            <div class="flex mt-6 flex-wrap w-full" :key="element.id"
                                                                 :class="dragging? 'cursor-grabbing' : 'cursor-grab'">
                                                                <div class="flex w-full">
                                                                    <div class="flex">
                                                                        <Link
                                                                            :href="route('rooms.show',{room: element.id,month_start: new Date((new Date).getFullYear(),(new Date).getMonth(),1,0,120),month_end:new Date((new Date).getFullYear(),(new Date).getMonth() + 1,2), calendarType: 'monthly'})"
                                                                            class="ml-4 my-auto text-lg font-black text-sm"
                                                                        >
                                                                            {{ element.name }} ({{ element.start_date }}
                                                                            - {{ element.end_date }})
                                                                        </Link>
                                                                        <div
                                                                            class="ml-6 flex items-center text-secondary text-sm my-auto">
                                                                            angelegt am {{ element.created_at }} von
                                                                            <img
                                                                                :data-tooltip-target="element.created_by.id"
                                                                                :src="element.created_by.profile_photo_url"
                                                                                :alt="element.created_by.first_name"
                                                                                class="rounded-full ml-2 h-6 w-6 object-cover cursor-pointer">
                                                                            <UserTooltip
                                                                                :user="element.created_by"></UserTooltip>
                                                                        </div>
                                                                    </div>
                                                                    <Menu as="div" class="my-auto relative"
                                                                          :key="element.id"
                                                                          v-show="showMenu === element.id">
                                                                        <div class="flex">
                                                                            <MenuButton
                                                                                class="flex ml-6">
                                                                                <DotsVerticalIcon
                                                                                    class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                                                                    aria-hidden="true"/>
                                                                            </MenuButton>
                                                                        </div>
                                                                        <transition
                                                                            enter-active-class="transition ease-out duration-100"
                                                                            enter-from-class="transform opacity-0 scale-95"
                                                                            enter-to-class="transform opacity-100 scale-100"
                                                                            leave-active-class="transition ease-in duration-75"
                                                                            leave-from-class="transform opacity-100 scale-100"
                                                                            leave-to-class="transform opacity-0 scale-95">
                                                                            <MenuItems
                                                                                class="origin-top-right absolute right-0 w-56 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                                                                <div class="py-1">
                                                                                    <MenuItem v-slot="{ active }">
                                                                                        <a @click="openEditRoomModal(element)"
                                                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                            <PencilAltIcon
                                                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                                aria-hidden="true"/>
                                                                                            Bearbeiten
                                                                                        </a>
                                                                                    </MenuItem>
                                                                                    <MenuItem v-slot="{ active }">
                                                                                        <a href="#"
                                                                                           @click="duplicateRoom(element)"
                                                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                            <DuplicateIcon
                                                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                                aria-hidden="true"/>
                                                                                            Duplizieren
                                                                                        </a>
                                                                                    </MenuItem>
                                                                                    <MenuItem v-slot="{ active }">
                                                                                        <a @click="openSoftDeleteRoomModal(element)"
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

                                                            </div>

                                                        </div>
                                                    </template>
                                                </draggable>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </app-layout>
    <!-- Areal Hinzufügen-->
    <jet-dialog-modal :show="showAddAreaModal" @close="closeAddAreaModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_area_new.svg" class="-ml-6 -mt-8 mb-4" />
            <div class="mx-3">
                <div class="font-bold font-lexend text-primary text-3xl my-2">
                    Neues Areal
                </div>
                <XIcon @click="closeAddAreaModal"
                       class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                       aria-hidden="true"/>
                <div class="mt-4">
                    <div class="flex mt-10 relative">
                        <input id="areaName" v-model="newAreaForm.name" type="text"
                               class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                               placeholder="placeholder"/>
                        <label for="areaName"
                               class="absolute left-0 text-base -top-4 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name
                            des Areals*
                        </label>
                        <jet-input-error :message="newAreaForm.error" class="mt-2"/>
                    </div>

                    <button :class="[newAreaForm.name.length === 0 ?
                    'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                            class="mt-4 flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                            @click="addArea"
                            :disabled="newAreaForm.name.length === 0">
                        Anlegen
                    </button>
                </div>
            </div>
        </template>
    </jet-dialog-modal>
    <!-- Areal Bearbeiten-->
    <jet-dialog-modal :show="showEditAreaModal" @close="closeEditAreaModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_area_edit.svg" class="-ml-6 -mt-8 mb-4" />
            <div class="mx-3">
                <div class="font-bold font-lexend text-primary text-3xl my-2">
                    Areal bearbeiten
                </div>
                <XIcon @click="closeEditAreaModal"
                       class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                       aria-hidden="true"/>
                <div class="mt-4">
                    <div class="flex mt-10 relative">
                        <input id="areaEditName" v-model="editAreaForm.name" type="text"
                               class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                               placeholder="placeholder"/>
                        <label for="areaEditName"
                               class="absolute left-0 text-base -top-4 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name
                            des Areals
                        </label>
                        <jet-input-error :message="editAreaForm.error" class="mt-2"/>
                    </div>

                    <button :class="[editAreaForm.name.length === 0 ?
                    'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                            class="mt-4 flex items-center px-20 py-3 border border-transparent
                            text-base tracking-wider font-bold uppercase shadow-sm text-secondaryHover"
                            @click="editArea"
                            :disabled="editAreaForm.name.length === 0">
                        Speichern
                    </button>
                </div>
            </div>
        </template>
    </jet-dialog-modal>
    <!-- Raum Hinzufügen-->
    <jet-dialog-modal :show="showAddRoomModal" @close="closeAddRoomModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_room_new.svg" class="-ml-6 -mt-8 mb-4" />
            <div class="mx-3">
                <div class="font-bold font-lexend text-primary text-3xl my-2">
                    Neuer Raum
                </div>
                <XIcon @click="closeAddRoomModal"
                       class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                       aria-hidden="true"/>
                <div class="mt-4">
                    <div class="flex mt-10 relative">
                        <input id="roomName" v-model="newRoomForm.name" type="text"
                               class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                               placeholder="placeholder"/>
                        <label for="roomName"
                               class="absolute left-0 text-base -top-4 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name des Raums*
                        </label>
                        <jet-input-error :message="newRoomForm.error" class="mt-2"/>
                    </div>
                    <div class="mt-8 mr-4">
                                            <textarea
                                                placeholder="Kurzbeschreibung"
                                                v-model="newRoomForm.description" rows="4"
                                                class="placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full font-semibold border border-gray-300 "/>
                    </div>
                    <div class="flex items-center my-6">
                        <input v-model="newRoomForm.temporary"
                               type="checkbox"
                               class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                        <p :class="[newRoomForm.temporary ? 'text-primary font-black' : 'text-secondary']"
                           class="ml-4 my-auto text-sm">Temporärer Raum</p>
                        <div v-if="$page.props.can.show_hints" class="flex mt-1">
                            <SvgCollection svgName="arrowLeft" class="h-6 w-6 ml-2 mr-2 mt-4"/>
                            <span
                                class="font-nanum text-secondary tracking-tight ml-1 my-auto tracking-tight text-xl">Richte einen temporären Raum ein - z.B wenn ein Teil eines Raumes abgetrennt wird. Dieser wird nur in diesem Zeitraum im Kalender angezeigt.</span>
                        </div>
                    </div>
                    <div class="flex" v-if="newRoomForm.temporary">
                        <input
                            v-model="newRoomForm.start_date" id="startDate"
                            placeholder="Zu erledigen bis?" type="date"
                            class="border-gray-300 placeholder-secondary mr-2 w-full"/>
                        <input
                            v-model="newRoomForm.end_date" id="endDate"
                            placeholder="Zu erledigen bis?" type="date"
                            class="border-gray-300 placeholder-secondary w-full"/>
                    </div>

                    <button :class="[newRoomForm.name.length === 0 ?
                    'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                            class="mt-4 flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                            @click="addRoom"
                            :disabled="newRoomForm.name.length === 0">
                        Anlegen
                    </button>
                </div>
            </div>
        </template>
    </jet-dialog-modal>
    <!-- Raum Bearbeiten-->
    <jet-dialog-modal :show="showEditRoomModal" @close="closeEditRoomModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_room_edit.svg" class="-ml-6 -mt-8 mb-4" />
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
                                                class="focus:border-black placeholder-secondary border-2 w-full font-semibold border border-gray-300 "/>
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
    <!-- Delete Area Modal -->
    <jet-dialog-modal :show="showSoftDeleteAreaModal" @close="closeSoftDeleteAreaModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4" />
            <div class="mx-4">

                <div class="font-bold text-primary text-2xl my-2">
                    Areal in den Papierkorb
                </div>
                <XIcon @click="closeSoftDeleteAreaModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="text-error subpixel-antialiased">
                    Bist du sicher,dass du das Areal {{ areaToSoftDelete.name }} mit allen Räumen in den Papierkorb
                    legen möchtest?
                </div>
                <div class="flex justify-between mt-6">
                    <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                            @click="softDeleteArea()">
                        In den Papierkorb
                    </button>
                    <div class="flex my-auto">
                            <span @click="closeSoftDeleteAreaModal()"
                                  class="text-secondary subpixel-antialiased cursor-pointer">Nein, doch nicht</span>
                    </div>
                </div>
            </div>
        </template>
    </jet-dialog-modal>
    <!-- Delete Room Modal -->
    <jet-dialog-modal :show="showSoftDeleteRoomModal" @close="closeSoftDeleteRoomModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4" />
            <div class="mx-4">

                <div class="font-bold text-primary text-3xl my-2">
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
    <!-- Delete All Rooms from Area Modal -->
    <jet-dialog-modal :show="showDeleteAllRoomsModal" @close="closeDeleteAllRoomsModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4" />
            <div class="mx-4">

                <div class="font-black text-primary text-3xl my-2">
                    Alle Räume entfernen
                </div>
                <XIcon @click="closeDeleteAllRoomsModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="text-error subpixel-antialiased">
                    Bist du sicher, dass du alle Räume aus diesem Areal in den Papierkorb legen möchtest?
                </div>
                <div class="flex justify-between mt-6">
                    <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                            @click="softDeleteAllRooms()">
                        In den Papierkorb
                    </button>
                    <div class="flex my-auto">
                            <span @click="closeDeleteAllRoomsModal()"
                                  class="text-secondary subpixel-antialiased cursor-pointer">Nein, doch nicht</span>
                    </div>
                </div>
            </div>
        </template>
    </jet-dialog-modal>
    <!-- Success Modal -->
    <jet-dialog-modal :show="showSuccessModal" @close="closeSuccessModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_success.svg" class="-ml-6 -mt-8 mb-4" />
            <div class="mx-4">

                <div class="font-black text-primary font-lexend text-3xl my-2">
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
</template>

<script>

import AppLayout from '@/Layouts/AppLayout.vue'
import SvgCollection from "@/Layouts/Components/SvgCollection";
import Button from "@/Jetstream/Button";
import AddButton from "@/Layouts/Components/AddButton";
import {
    DotsVerticalIcon,
    InformationCircleIcon,
    PencilAltIcon,
    SearchIcon, TrashIcon,
    XIcon, DuplicateIcon
} from "@heroicons/vue/outline";
import {CheckIcon, ChevronUpIcon,ChevronDownIcon, PlusSmIcon, XCircleIcon} from "@heroicons/vue/solid";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import JetButton from "@/Jetstream/Button";
import {defineComponent} from 'vue'
import JetDialogModal from "@/Jetstream/DialogModal";
import JetInput from "@/Jetstream/Input";
import JetInputError from "@/Jetstream/InputError";
import JetSecondaryButton from "@/Jetstream/SecondaryButton";
import {Link, useForm} from "@inertiajs/inertia-vue3";
import draggable from "vuedraggable";
import UserTooltip from "@/Layouts/Components/UserTooltip";

export default defineComponent({
    components: {
        AddButton,
        UserTooltip,
        SvgCollection,
        Button,
        AppLayout,
        DotsVerticalIcon,
        PlusSmIcon,
        SearchIcon,
        CheckIcon,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        JetButton,
        JetDialogModal,
        JetInput,
        JetInputError,
        JetSecondaryButton,
        InformationCircleIcon,
        ChevronDownIcon,
        ChevronUpIcon,
        XIcon,
        PencilAltIcon,
        TrashIcon,
        XCircleIcon,
        Link,
        DuplicateIcon,
        draggable,
    },
    name: 'Area Management',
    props: ['areas', 'opened_areas'],
    data() {
        return {
            dragging: false,
            showMenu: null,
            showAddAreaModal: false,
            showAddRoomModal: false,
            showEditAreaModal: false,
            showSoftDeleteAreaModal: false,
            showDeleteAllRoomsModal: false,
            areaToDeleteRoomsFrom: null,
            areaToSoftDelete: null,
            roomToSoftDelete: null,
            showSuccessModal: false,
            showSoftDeleteRoomModal: false,
            successHeading: "",
            successDescription: "",
            showTemporaryRooms: [],
            showEditRoomModal: false,
            newAreaForm: useForm({
                name: ''
            }),
            newRoomForm: useForm({
                name: '',
                description: '',
                temporary: false,
                start_date: null,
                end_date: null,
                area_id: null,
                user_id: this.$page.props.user.id,
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
            }),
            editAreaForm: useForm({
                id: null,
                name: '',
                rooms: [],
            }),
        }
    },
    methods: {
        changeAreaStatus(area) {
            if(!this.opened_areas.includes(area.id)) {
                const openedAreas = this.opened_areas;

                openedAreas.push(area.id)
                this.$inertia.patch(`/users/${this.$page.props.user.id}/areas`, {"opened_areas": openedAreas});
            }
            else {
                const filteredList = this.opened_areas.filter(function(value) {
                    return value !== area.id;
                })
                this.$inertia.patch(`/users/${this.$page.props.user.id}/areas`, {"opened_areas": filteredList});
            }
        },
        updateRoomOrder(rooms) {

            rooms.map((room, index) => {
                room.order = index + 1
            })

            this.$inertia.put('/rooms/order', {
                rooms
            }, {
                preserveState: true,
                preserveScroll: true
            })

        },
        openAddAreaModal() {
            this.showAddAreaModal = true;
        },
        closeAddAreaModal() {
            this.showAddAreaModal = false;
            this.newAreaForm.name = "";
        },
        addArea() {
            this.newAreaForm.post(route('areas.store'), {});
            this.closeAddAreaModal();
        },
        addRoom() {
            this.newRoomForm.post(route('rooms.store'), {});
            this.closeAddRoomModal();
        },
        openAddRoomModal(area) {
            this.newRoomForm.area_id = area.id;
            this.showAddRoomModal = true;
        },
        closeAddRoomModal() {
            this.showAddRoomModal = false;
            this.newRoomForm.area_id = null;
            this.newRoomForm.name = "";
            this.newRoomForm.description = "";
            this.newRoomForm.start_date = null;
            this.newRoomForm.end_date = null;
            this.newRoomForm.temporary = false;
        },
        openEditAreaModal(area) {
            this.editAreaForm.id = area.id;
            this.editAreaForm.name = area.name;
            this.editAreaForm.rooms = area.rooms;
            this.showEditAreaModal = true;
        },
        closeEditAreaModal() {
            this.showEditAreaModal = false;
            this.editAreaForm.id = null;
            this.editAreaForm.name = "";
            this.editAreaForm.rooms = [];
        },
        editArea() {
            this.editAreaForm.patch(route('areas.update', {area: this.editAreaForm.id}));
            this.closeEditAreaModal();
        },
        duplicateArea(area) {
            this.$inertia.post(`/areas/${area.id}/duplicate`);
        },
        duplicateRoom(room) {
            this.$inertia.post(`/rooms/${room.id}/duplicate`);
        },
        openSoftDeleteAreaModal(area) {
            this.areaToSoftDelete = area;
            this.showSoftDeleteAreaModal = true;
        },
        closeSoftDeleteAreaModal() {
            this.showSoftDeleteAreaModal = false;
            this.areaToSoftDelete = null;
        },
        softDeleteArea() {
            this.$inertia.delete(`/areas/${this.areaToSoftDelete.id}`);
            this.closeSoftDeleteAreaModal()
            this.successHeading = "Areal im Papierkorb"
            this.successDescription = "Das Areal und alle zugehörigen Räume wurden erfolgreich in den Papierkorb gelegt."
            this.showSuccessModal = true;
            setTimeout(() => this.closeSuccessModal(), 2000)
        },
        openDeleteAllRoomsModal(area) {
            this.areaToDeleteRoomsFrom = area;
            this.showDeleteAllRoomsModal = true;
        },
        closeDeleteAllRoomsModal() {
            this.showDeleteAllRoomsModal = false;
            this.areaToDeleteRoomsFrom = null;

        },
        closeSuccessModal() {
            this.showSuccessModal = false;
            this.successHeading = "";
            this.successDescription = "";
        },
        softDeleteAllRooms() {
            this.areaToDeleteRoomsFrom.rooms.forEach((room) => {
                this.$inertia.delete(`/rooms/${room.id}`);
            })
            this.closeDeleteAllRoomsModal();
            this.successHeading = "Raum im Papierkorb"
            this.successDescription = "Die Räume wurden erfolgreich in den Papierkorb gelegt."
            this.showSuccessModal = true;
            setTimeout(() => this.closeSuccessModal(), 2000)
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
        switchVisibility(areaId) {
            if (this.showTemporaryRooms.includes(areaId)) {
                this.showTemporaryRooms.splice(this.showTemporaryRooms.indexOf(areaId), 1);
            } else {
                this.showTemporaryRooms.push(areaId);
            }
        },
        editRoom() {
            this.editRoomForm.start_date = this.editRoomForm.start_date_dt_local;
            this.editRoomForm.end_date = this.editRoomForm.end_date_dt_local;
            this.editRoomForm.patch(route('rooms.update', {room: this.editRoomForm.id}));
            this.closeEditRoomModal();
        }
    },
})
</script>

<style scoped>

</style>
