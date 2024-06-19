<template>
    <app-layout :title="$t('Rooms & areas')">
        <div class="">

            <div class="max-w-screen-2xl mb-40 flex flex-row ml-14 mr-40">
                <div class="flex flex-1 flex-wrap">
                    <div class="w-full flex my-auto justify-between">
                        <div class="flex flex-wrap w-full">
                            <div class="flex flex-wrap w-full">
                                <h2 class="headline1 flex w-full">{{ $t('Rooms & areas')}}</h2>
                                <div class="xsLight flex mt-4 w-full">
                                    {{ $t('Create areas and rooms and assign side rooms to individual rooms. Also define global properties for rooms.')}}
                                </div>
                                <Tabs />
                                <h2 class="headline2 w-full">{{ $t('Room properties')}}</h2>
                                <div class="xsLight flex mt-4 w-full">
                                    {{ $t('Define room categories and properties. These can then be filtered in the calendars.')}}
                                </div>
                                <div v-if="showInvalidNameErrorText" class="text-red-600 text-sm mt-4">
                                    {{$t('You have entered an invalid name. No spaces are allowed at the beginning or end. It is also not permitted to enter only spaces.')}}
                                </div>
                                <div class=" w-full grid grid-cols-2 grid-flow-col grid-rows-2">
                                    <!-- Raumkategorien -->
                                    <div class="mt-8 mr-10 flex">
                                        <div class="relative w-72">
                                            <input v-on:keyup.enter=addRoomCategory id="roomCategory"
                                                   v-model="roomCategoryInput"
                                                   type="text"
                                                   class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-artwork-navigation-background placeholder-secondary placeholder-transparent"
                                                   placeholder="placeholder"/>
                                            <label for="roomCategory"
                                                   class="absolute left-0 text-sm -top-5 text-gray-600 text-sm -top-3.5 transition-all
                                                subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base
                                                 peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5
                                                  peer-focus:text-sm ">
                                                {{ $t('Enter room category')}}
                                            </label>
                                        </div>

                                        <div class="m-2">
                                            <button
                                                :class="[roomCategoryInput === '' ? 'bg-secondary': 'bg-artwork-buttons-create hover:bg-artwork-buttons-hover focus:outline-none', 'rounded-full mt-2 ml-1 items-center text-sm p-1 border border-transparent uppercase shadow-sm text-white']"
                                                @click="addRoomCategory" :disabled="!roomCategoryInput">
                                                <IconCheck stroke-width="1.5" class="h-5 w-5"></IconCheck>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mt-2 mr-10 flex flex-wrap">
                                        <span v-for="(category,index) in room_categories"
                                              class="rounded-full items-center font-medium text-tagText
                                            border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
                                            {{ category.name }}
                                            <button type="button" @click="this.showRoomCategoryDeleteModal(category)">
                                                <!--<span class="sr-only">Email aus Einladung entfernen</span>-->
                                                <IconX stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error "/>
                                            </button>
                                        </span>
                                    </div>

                                    <!-- Raumattribute -->
                                    <div class="mt-8 flex">
                                        <div class="relative w-72">
                                            <input v-on:keyup.enter=addRoomAttribute id="roomAttribute"
                                                   v-model="roomAttributeInput"
                                                   type="text"
                                                   class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-artwork-navigation-background placeholder-secondary placeholder-transparent"
                                                   placeholder="placeholder"/>
                                            <label for="roomAttribute"
                                                   class="absolute left-0 text-sm -top-5 text-gray-600 text-sm -top-3.5 transition-all
                                                subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base
                                                 peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5
                                                  peer-focus:text-sm ">
                                                {{ $t('Enter room property')}}
                                            </label>
                                        </div>

                                        <div class="m-2">
                                            <button
                                                :class="[roomAttributeInput === '' ? 'bg-secondary': 'bg-artwork-buttons-create hover:bg-artwork-buttons-hover focus:outline-none', 'rounded-full mt-2 ml-1 items-center text-sm p-1 border border-transparent uppercase shadow-sm text-white']"
                                                @click="addRoomAttribute" :disabled="!roomAttributeInput">
                                                <IconCheck stroke-width="1.5" class="h-5 w-5"></IconCheck>
                                            </button>
                                        </div>

                                    </div>

                                    <div class="mt-2 flex flex-wrap">
                                        <span v-for="(attribute,index) in room_attributes"
                                              class="mr-1 rounded-full items-center font-medium text-tagText
                                         border bg-tagBg border-tag px-3 text-sm mb-1 h-8 inline-flex">
                                            {{ attribute.name }}
                                            <button type="button" @click="this.showRoomAttributeDeleteModal(attribute)">
                                                <!--<span class="sr-only">Email aus Einladung entfernen</span>-->
                                                <IconX stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error"/>
                                            </button>
                                        </span>
                                    </div>
                                </div>

                                <div class="flex w-full justify-between mt-6">
                                    <h2 class=" mt-10 headline2">{{$t('Areas ')}}</h2>
                                </div>
                                <div class="flex w-full justify-between mt-6">
                                    <div class="flex">
                                        <div>
                                            <AddButtonBig @click="openAddAreaModal" :text="$t('Add area')"/>
                                        </div>
                                        <div v-if="this.$page.props.show_hints" class="flex">
                                            <SvgCollection svgName="arrowLeft" class="ml-2 mt-4"/>
                                            <span
                                                class="ml-1 mt-4 hind">{{ $t('Create new areas')}}</span>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <div class="flex w-full flex-wrap mt-10">
                                <div v-for="area in areas"
                                     class="flex w-full bg-white my-2 border border-gray-200">
                                    <button class="bg-artwork-buttons-hover flex" @click="changeAreaStatus(area)">
                                        <IconChevronUp stroke-width="1.5" v-if="this.opened_areas.includes(area.id)"
                                                       class="h-6 w-6 text-white my-auto"></IconChevronUp>
                                        <IconChevronDown stroke-width="1.5" v-else
                                                         class="h-6 w-6 text-white my-auto"></IconChevronDown>
                                    </button>
                                    <div class="flex items-center w-full ml-4 flex-wrap p-4">
                                        <div class="flex items-center justify-between w-full">
                                            <div class="my-auto flex items-center">
                                                <span class="headline2">
                                                    {{ area.name }}
                                                </span>
                                            </div>
                                            <div class="flex items-center">
                                                <BaseMenu>
                                                    <MenuItem v-slot="{ active }">
                                                        <a @click="openEditAreaModal(area)"
                                                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased capitalize']">
                                                            <IconEdit stroke-width="1.5"
                                                                      class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                      aria-hidden="true"/>
                                                            {{ $t('edit')}}
                                                        </a>
                                                    </MenuItem>
                                                    <MenuItem v-slot="{ active }">
                                                        <a href="#"
                                                           @click="duplicateArea(area)"
                                                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                            <IconCopy stroke-width="1.5"
                                                                      class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                      aria-hidden="true"/>
                                                            {{ $t('Duplicate')}}
                                                        </a>
                                                    </MenuItem>
                                                    <MenuItem v-slot="{ active }">
                                                        <a @click="openSoftDeleteAreaModal(area)"
                                                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                            <IconTrash stroke-width="1.5"
                                                                       class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                       aria-hidden="true"/>
                                                            {{ $t('In the recycle bin')}}
                                                        </a>
                                                    </MenuItem>
                                                    <MenuItem v-slot="{ active }">
                                                        <a @click="openDeleteAllRoomsModal(area)"
                                                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                            <IconRecycle stroke-width="1.5"
                                                                         class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                         aria-hidden="true"/>
                                                            {{ $t('Remove all rooms')}}
                                                        </a>
                                                    </MenuItem>
                                                </BaseMenu>
                                            </div>
                                        </div>
                                        <div class="flex w-full mt-6" v-if="this.opened_areas.includes(area.id)">
                                            <div class="">
                                                <AddButtonBig @click="openAddRoomModal(area)" :text="$t('Add room')" />
                                            </div>
                                            <div v-if="this.$page.props.show_hints" class="flex">
                                                <SvgCollection svgName="arrowLeft" class="ml-2"/>
                                                <span
                                                    class="hind text-secondary tracking-tight ml-1 text-xl">{{ $t('Create new rooms')}}</span>
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
                                                            <div class="flex w-full items-center">
                                                                <div class="flex items-center">
                                                                    <Link :href="route('rooms.show',{room: element.id})"
                                                                          class="ml-4 my-auto xsDark"
                                                                    >
                                                                        {{ element.name }}
                                                                    </Link>
                                                                    <div
                                                                        class="ml-6 mt-1 flex items-center xsLight my-auto">
                                                                        {{$t('created on { created_at } by', {'created_at': element.created_at })}}
                                                                        <UserPopoverTooltip :user="element.created_by"
                                                                                            :id="element.created_by.id"
                                                                                            :height="6" :width="6"
                                                                                            class="ml-2"/>
                                                                    </div>
                                                                </div>
                                                                <BaseMenu :key="element.id" v-show="showMenu === element.id">
                                                                    <MenuItem v-slot="{ active }">
                                                                        <a @click="openEditRoomModal(element)"
                                                                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased capitalize']">
                                                                            <IconEdit stroke-width="1.5"
                                                                                      class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                      aria-hidden="true"/>
                                                                            {{ $t('edit')}}
                                                                        </a>
                                                                    </MenuItem>
                                                                    <MenuItem v-slot="{ active }">
                                                                        <a href="#"
                                                                           @click="duplicateRoom(element)"
                                                                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                            <IconCopy stroke-width="1.5"
                                                                                      class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                      aria-hidden="true"/>
                                                                            {{ $t('Duplicate')}}
                                                                        </a>
                                                                    </MenuItem>
                                                                    <MenuItem v-slot="{ active }">
                                                                        <a @click="openSoftDeleteRoomModal(element)"
                                                                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                            <IconTrash stroke-width="1.5"
                                                                                       class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                       aria-hidden="true"/>
                                                                            {{ $t('In the recycle bin')}}
                                                                        </a>
                                                                    </MenuItem>
                                                                </BaseMenu>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </draggable>
                                            <div v-show="area.rooms.filter(room => room.temporary === true).length > 0"
                                                 class="mt-12">
                                                <h2 v-on:click="switchVisibility(area.id)"
                                                    class="pb-2 flex xxsDarkBold cursor-pointer">
                                                    {{ $t('Temporary rooms')}}
                                                    <IconChevronUp stroke-width="1.5" v-if="showTemporaryRooms.includes(area.id)"
                                                                   class=" ml-1 mr-3 flex-shrink-0 mt-1 h-4 w-4"></IconChevronUp>
                                                    <IconChevronDown stroke-width="1.5" v-else
                                                                     class=" ml-1 mr-3 flex-shrink-0 mt-1 h-4 w-4"></IconChevronDown>
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
                                                                            :href="route('rooms.show',{room: element.id})"
                                                                            class="ml-4 my-auto xsDark"
                                                                        >
                                                                            {{ element.name }} ({{ element.start_date }}
                                                                            - {{ element.end_date }})
                                                                        </Link>
                                                                        <div
                                                                            class="ml-6 flex items-center xsLight my-auto">
                                                                            {{$t('created on { created_at } by', {'crated_at': element.created_at })}}
                                                                            <UserPopoverTooltip
                                                                                :user="element.created_by"
                                                                                :id="element.created_by.id"
                                                                                height="6"
                                                                                width="6"
                                                                                class="ml-2"></UserPopoverTooltip>
                                                                        </div>
                                                                    </div>
                                                                    <BaseMenu :key="element.id" v-show="showMenu === element.id">
                                                                        <MenuItem v-slot="{ active }">
                                                                            <a @click="openEditRoomModal(element)"
                                                                               :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased capitalize']">
                                                                                <IconEdit stroke-width="1.5"
                                                                                          class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                          aria-hidden="true"/>
                                                                                {{ $t('edit')}}
                                                                            </a>
                                                                        </MenuItem>
                                                                        <MenuItem v-slot="{ active }">
                                                                            <a href="#"
                                                                               @click="duplicateRoom(element)"
                                                                               :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                <IconCopy  stroke-width="1.5"
                                                                                           class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                           aria-hidden="true"/>
                                                                                {{ $t('Duplicate')}}
                                                                            </a>
                                                                        </MenuItem>
                                                                        <MenuItem v-slot="{ active }">
                                                                            <a @click="openSoftDeleteRoomModal(element)"
                                                                               :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                <IconTrash stroke-width="1.5"
                                                                                           class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                           aria-hidden="true"/>
                                                                                {{ $t('In the recycle bin')}}
                                                                            </a>
                                                                        </MenuItem>
                                                                    </BaseMenu>
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

    <BaseModal modal-image="/Svgs/Overlays/illu_area_new.svg" @closed="closeAddAreaModal" v-if="showAddAreaModal">
        <div class="mx-3">
            <div class="headline1 my-2">
                {{ $t('New area')}}
            </div>
            <div class="mt-4">
                <div class="flex mt-10 relative">
                    <input id="roomNameEdit" v-model="newAreaForm.name" type="text"
                           class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary sDark focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 placeholder-secondary placeholder-transparent"
                           placeholder="placeholder"/>
                    <label for="roomNameEdit"
                           class="absolute left-0 text-base -top-4 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">
                        {{$t('Name of the area')}}*
                    </label>
                    <jet-input-error :message="newAreaForm.error" class="mt-2"/>
                </div>

                <div class="w-full items-center text-center mt-4">
                    <FormButton
                        :disabled="newAreaForm.name.length === 0"
                        :text="$t('Create')"
                        class="mt-8"
                        @click="addArea"
                    />
                </div>
            </div>
        </div>
    </BaseModal>
    <!-- Areal Bearbeiten-->
    <BaseModal modal-image="/Svgs/Overlays/illu_area_edit.svg" @closed="closeEditAreaModal" v-if="showEditAreaModal">
        <div class="mx-3">
            <div class="headline1 my-2">
                {{$t('Edit area')}}
            </div>
            <div class="mt-4">
                <div class="flex mt-10 relative">
                    <input id="areaEditName" v-model="editAreaForm.name" type="text"
                           class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                           placeholder="placeholder"/>
                    <label for="areaEditName"
                           class="absolute left-0 text-base -top-4 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">
                        {{$t('Name of the area')}}*
                    </label>
                    <jet-input-error :message="editAreaForm.error" class="mt-2"/>
                </div>

                <div class="w-full items-center text-center">
                    <FormButton
                        :disabled="editAreaForm.name.length === 0"
                        :text="$t('Save')"
                        @click="editArea"
                        class="mt-8 inline-flex items-center"
                    />
                </div>
            </div>
        </div>
    </BaseModal>
    <!-- Raum Hinzufügen-->
    <BaseModal modal-image="/Svgs/Overlays/illu_room_new.svg" @closed="closeAddRoomModal" v-if="showAddRoomModal">
        <div class="mx-3 overflow-y-auto">
            <div class="headline1 my-2">
                {{ $t('New room')}}
            </div>
            <div class="mt-4">
                <div class="text-secondary">{{$t('Create a new room.')}}</div>
                <div class="flex mt-6 relative">
                    <input id="roomName" v-model="newRoomForm.name" type="text"
                           class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                           placeholder="placeholder"/>
                    <label for="roomName"
                           class="absolute left-0 text-base -top-4 text-gray-600 text-sm -top-3.5 transition-all
                                subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base
                                 peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5
                                  peer-focus:text-sm "
                    >
                        {{ $t('Room name')}}*
                    </label>
                    <jet-input-error :message="newRoomForm.error" class="mt-2"/>
                </div>
                <div class="mt-8">
                                            <textarea
                                                :placeholder="$t('Short description')"
                                                v-model="newRoomForm.description" rows="4"
                                                class="placeholder-secondary border-2 resize-none focus:outline-none focus:ring-0 focus:border-secondary focus:border-2 w-full font-semibold border border-gray-300 "/>
                </div>

                <Menu as="span" class="relative inline-block w-full text-left">
                    <div>
                        <MenuButton
                            class="mt-1 border border-gray-300 w-full bg-white px-4 py-2 text-sm font-medium text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                        >
                            <span class="font-semibold float-left text-secondary">{{$t('Select room properties')}}</span>
                            <ChevronDownIcon
                                class="ml-2 -mr-1 h-5 w-5 text-primary float-right"
                                aria-hidden="true"
                            />
                        </MenuButton>
                    </div>

                    <transition
                        enter-active-class="transition duration-50 ease-out"
                        enter-from-class="transform scale-100 opacity-100"
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-75 ease-in"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0"
                    >

                        <MenuItems
                            class="absolute right-0 px-4 py-2  mt-2 w-full origin-top-right divide-y divide-gray-200 rounded-sm bg-artwork-navigation-background ring-1 ring-black text-white opacity-100 z-50">
                            <div class="mx-auto w-full rounded-2xl bg-artwork-navigation-background border-none">
                                <!-- Room Categories Section -->
                                <Disclosure v-slot="{ open }">
                                    <DisclosureButton
                                        class="flex w-full py-2 justify-between rounded-lg bg-artwork-navigation-background text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                    >
                                        <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{ $t('Room categories')}}</span>
                                        <ChevronDownIcon
                                            :class="open ? 'rotate-180 transform' : ''"
                                            class="h-4 w-4 mt-0.5 text-white"
                                        />
                                    </DisclosureButton>

                                    <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                                        <div v-if="room_categories.length > 0"
                                             v-for="category in room_categories"
                                             :key="category"
                                             class="flex w-full items-center mb-2">
                                            <input type="checkbox"
                                                   v-model="newRoomForm.room_categoriesToDisplay"
                                                   :value="{id:category.id,name: category.name}"
                                                   class="cursor-pointer h-6 w-6 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                            <p :class="[newRoomForm.room_categoriesToDisplay.includes(category)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                               class="ml-1.5 text-md subpixel-antialiased align-text-middle">
                                                {{ category.name }}
                                            </p>
                                        </div>
                                        <div v-else class="text-secondary">{{$t('No room categories created yet')}}</div>
                                    </DisclosurePanel>
                                </Disclosure>

                                <Disclosure v-slot="{ open }">
                                    <DisclosureButton
                                        class="flex w-full py-2 justify-between rounded-lg bg-artwork-navigation-background text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                    >
                                        <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{ $t('Adjoining rooms')}}</span>
                                        <ChevronDownIcon
                                            :class="open ? 'rotate-180 transform' : ''"
                                            class="h-4 w-4 mt-0.5 text-white"
                                        />
                                    </DisclosureButton>

                                    <DisclosurePanel class="pt-2 pb-2 text-sm text-white">

                                        <div v-for="area in areas">
                                            <div v-if="area.rooms.length > 0"
                                                 v-for="room in area.rooms"
                                                 :key="room"
                                                 class="flex items-center w-full mb-2">
                                                <input type="checkbox"
                                                       v-model="newRoomForm.adjoining_roomsToDisplay"
                                                       :value="{id:room.id,name: room.name}"
                                                       class="cursor-pointer h-6 w-6 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                                <p :class="[newRoomForm.adjoining_roomsToDisplay.includes(room)
                                                                                            ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                   class="ml-1.5 text-md subpixel-antialiased align-text-middle">
                                                    {{ room.name }}
                                                </p>
                                            </div>
                                            <div v-else class="text-secondary">{{ $t('No rooms created yet')}}</div>
                                        </div>

                                    </DisclosurePanel>
                                </Disclosure>
                                <!--                                    -->
                                <!-- Room Attributes Section -->
                                <Disclosure v-slot="{ open }">
                                    <DisclosureButton
                                        class="flex w-full py-2 justify-between rounded-lg bg-artwork-navigation-background text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                    >
                                        <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{ $t('Room properties')}}</span>
                                        <ChevronDownIcon
                                            :class="open ? 'rotate-180 transform' : ''"
                                            class="h-4 w-4 mt-0.5 text-white"
                                        />
                                    </DisclosureButton>

                                    <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                                        <div v-if="room_attributes.length > 0"
                                             v-for="attribute in room_attributes"
                                             :key="attribute"
                                             class="flex w-full items-center mb-2">
                                            <input type="checkbox"
                                                   v-model="newRoomForm.room_attributesToDisplay"
                                                   :value="{id:attribute.id,name: attribute.name}"
                                                   class="cursor-pointer h-6 w-6 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                            <p :class="[newRoomForm.room_attributesToDisplay.includes(attribute)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                               class="ml-1.5 text-md subpixel-antialiased align-text-middle">
                                                {{ attribute.name }}
                                            </p>
                                        </div>
                                        <div v-else class="text-secondary">
                                            {{ $t('No room properties created yet')}}
                                        </div>
                                    </DisclosurePanel>
                                </Disclosure>
                            </div>
                        </MenuItems>
                    </transition>

                </Menu>
                <div class="mt-2 flex flex-wrap">
                        <span v-for="(category, index) in newRoomForm.room_categoriesToDisplay"
                              class="flex rounded-full items-center font-medium text-tagText
                             border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
                            {{ category.name }}
                            <button @click="newRoomForm.room_categoriesToDisplay.splice(index,1)" type="button">
                                <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
                            </button>
                        </span>
                    <span v-for="(attribute, index) in newRoomForm.room_attributesToDisplay"
                          class="flex rounded-full items-center font-medium text-tagText
                             border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
                            {{ attribute.name }}
                            <button @click="newRoomForm.room_attributesToDisplay.splice(index,1)" type="button">
                                <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
                            </button>
                        </span>
                    <span v-for="(room, index) in newRoomForm.adjoining_roomsToDisplay"
                          class="flex rounded-full items-center font-medium text-tagText
                                         border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
                            {{ $t('adjoining room from')}} {{ room.name }}
                            <button @click="newRoomForm.adjoining_roomsToDisplay.splice(index,1)" type="button">
                                <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
                            </button>
                        </span>


                </div>
                <div class="flex items-center my-4">
                    <input v-model="newRoomForm.temporary"
                           type="checkbox"
                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                    <p :class="[newRoomForm.temporary ? 'text-primary font-black' : 'text-secondary']"
                       class="ml-4 my-auto text-sm">Temporärer Raum</p>
                    <div v-if="this.$page.props.show_hints" class="flex mt-1">
                        <SvgCollection svgName="arrowLeft" class="h-6 w-6 ml-2 mr-2"/>
                        <span
                            class="ml-1 my-auto hind">{{ $t('Set up a temporary room - e.g. if part of a room is partitioned off. This is only displayed in the calendar during this period.')}}</span>
                    </div>
                </div>
                <div class="flex" v-if="newRoomForm.temporary">
                    <input
                        v-model="newRoomForm.start_date" id="startDate"
                        :placeholder="$t('To be completed by?')" type="date"
                        class="border-gray-300 placeholder-secondary mr-2 w-full"/>
                    <input
                        v-model="newRoomForm.end_date" id="endDate"
                        :placeholder="$t('To be completed by?')" type="date"
                        class="border-gray-300 placeholder-secondary w-full"/>
                </div>
                <div class="flex items-center my-6">
                    <input v-model="newRoomForm.everyone_can_book"
                           type="checkbox"
                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                    <p :class="[newRoomForm.everyone_can_book ? 'text-primary font-black' : 'text-secondary']"
                       class="ml-4 my-auto text-sm">{{ $t('Can be booked by anyone')}}</p>
                    <div v-if="this.$page.props.show_hints" class="flex mt-1">
                        <SvgCollection svgName="arrowLeft" class="h-6 w-6 ml-2 mr-2"/>
                        <span
                            class="ml-1 my-auto hind">{{ $t('Decides whether this room can be booked by everyone or only by the room admins.')}}</span>
                    </div>
                </div>
                <div class="w-full items-center text-center">
                    <FormButton
                        @click="addRoom"
                        :disabled="newRoomForm.name.length === 0"
                        :text="$t('Create')"
                        class="inline-flex items-center mt-4"
                    />
                </div>
            </div>
        </div>
    </BaseModal>

    <!-- Raum Bearbeiten-->
    <BaseModal modal-image="/Svgs/Overlays/illu_room_edit.svg" @closed="closeEditRoomModal" v-if="showEditRoomModal">
        <div class="mx-3">
            <div class="headline1 my-2">
                {{$t('Edit room')}}
            </div>
            <div class="mt-4">
                <div class="flex mt-10 relative">
                    <input id="roomNameEdit" v-model="editRoomForm.name" type="text"
                           class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary sDark focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 placeholder-secondary placeholder-transparent"
                           placeholder="placeholder"/>
                    <label for="roomNameEdit"
                           class="absolute left-0 text-base -top-4 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">
                        {{ $t('Room name')}}*
                    </label>
                    <jet-input-error :message="editRoomForm.error" class="mt-2"/>
                </div>
                <div class="mt-8">
                                            <textarea
                                                :placeholder="$t('Short description')"
                                                v-model="editRoomForm.description" rows="4"
                                                class="focus:border-black placeholder-secondary border-2 w-full font-semibold border border-gray-300 "/>
                </div>
                <Menu as="span" class="relative inline-block w-full text-left">
                    <div>
                        <MenuButton
                            class="mt-1 border border-gray-300 w-full bg-white px-4 py-2 text-sm font-medium text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                        >
                            <span class="font-semibold float-left text-secondary">{{ $t('Select room properties')}}</span>
                            <ChevronDownIcon
                                class="ml-2 -mr-1 h-5 w-5 text-primary float-right"
                                aria-hidden="true"
                            />
                        </MenuButton>
                    </div>

                    <transition
                        enter-active-class="transition duration-50 ease-out"
                        enter-from-class="transform scale-100 opacity-100"
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-75 ease-in"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0"
                    >

                        <MenuItems
                            class="absolute right-0 px-4 py-2  mt-2 w-full origin-top-right divide-y divide-gray-200 rounded-sm bg-artwork-navigation-background ring-1 ring-black text-white opacity-100 z-50">
                            <div class="mx-auto w-full rounded-2xl bg-artwork-navigation-background border-none">
                                <!-- Room Categories Section -->
                                <Disclosure v-slot="{ open }">
                                    <DisclosureButton
                                        class="flex w-full py-2 justify-between rounded-lg bg-artwork-navigation-background text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                    >
                                        <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{$t('Room categories')}}</span>
                                        <ChevronDownIcon
                                            :class="open ? 'rotate-180 transform' : ''"
                                            class="h-4 w-4 mt-0.5 text-white"
                                        />
                                    </DisclosureButton>

                                    <DisclosurePanel class="pt-2 pb-2 text-sm text-white">

                                        <div v-if="room_categories.length > 0"
                                             v-for="category in room_categories"
                                             :key="category"
                                             class="flex w-full items-center mb-2">
                                            <input type="checkbox"
                                                   v-model="editRoomForm.room_categoriesToDisplay"
                                                   :value="{id:category.id,name: category.name}"
                                                   class="cursor-pointer h-6 w-6 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                            <p :class="[editRoomForm.room_categoriesToDisplay.includes(category)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                               class="ml-1.5 text-md subpixel-antialiased align-text-middle">
                                                {{ category.name }}
                                            </p>
                                        </div>
                                        <div v-else class="text-secondary">{{$t('No room categories created yet')}}</div>
                                    </DisclosurePanel>
                                </Disclosure>

                                <Disclosure v-slot="{ open }">
                                    <DisclosureButton
                                        class="flex w-full py-2 justify-between rounded-lg bg-artwork-navigation-background text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                    >
                                        <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{ $t('Adjoining rooms')}}</span>
                                        <ChevronDownIcon
                                            :class="open ? 'rotate-180 transform' : ''"
                                            class="h-4 w-4 mt-0.5 text-white"
                                        />
                                    </DisclosureButton>

                                    <DisclosurePanel class="pt-2 pb-2 text-sm text-white">

                                        <div v-for="area in areas">
                                            <div v-if="area.rooms.length > 0"
                                                 v-for="room in area.rooms"
                                                 :key="room"
                                                 class="flex items-center w-full mb-2">
                                                <input type="checkbox"
                                                       v-model="editRoomForm.adjoining_roomsToDisplay"
                                                       :value="{id:room.id,name: room.name}"
                                                       class="cursor-pointer h-6 w-6 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                                <p :class="[editRoomForm.adjoining_roomsToDisplay.includes(room)
                                                                                            ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                   class="ml-1.5 text-md subpixel-antialiased align-text-middle">
                                                    {{ room.name }}
                                                </p>
                                            </div>
                                            <div v-else class="text-secondary">{{$t('No rooms created yet')}}</div>
                                        </div>

                                    </DisclosurePanel>
                                </Disclosure>
                                <!--                                    -->
                                <!-- Room Attributes Section -->
                                <Disclosure v-slot="{ open }">
                                    <DisclosureButton
                                        class="flex w-full py-2 justify-between rounded-lg bg-artwork-navigation-background text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                    >
                                        <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{$t('Room properties')}}</span>
                                        <ChevronDownIcon
                                            :class="open ? 'rotate-180 transform' : ''"
                                            class="h-4 w-4 mt-0.5 text-white"
                                        />
                                    </DisclosureButton>

                                    <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                                        <div v-if="room_attributes.length > 0"
                                             v-for="attribute in room_attributes"
                                             :key="attribute"
                                             class="flex w-full items-center mb-2">
                                            <input type="checkbox"
                                                   v-model="editRoomForm.room_attributesToDisplay"
                                                   :value="{id:attribute.id,name: attribute.name}"
                                                   class="cursor-pointer h-6 w-6 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                            <p :class="[editRoomForm.room_attributesToDisplay.includes(attribute)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                               class="ml-1.5 text-md subpixel-antialiased align-text-middle">
                                                {{ attribute.name }}
                                            </p>
                                        </div>
                                        <div v-else class="text-secondary">
                                            {{ $t('No room properties created yet')}}
                                        </div>
                                    </DisclosurePanel>
                                </Disclosure>
                            </div>
                        </MenuItems>
                    </transition>

                </Menu>
                <div class="mt-2 flex flex-wrap">
                                    <span v-for="(category, index) in editRoomForm.room_categoriesToDisplay"
                                          class="flex rounded-full items-center font-medium text-tagText
                                         border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
                                        {{ category.name }}
                                        <button @click="editRoomForm.room_categoriesToDisplay.splice(index,1)"
                                                type="button">
                                            <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
                                        </button>
                                    </span>
                    <span v-for="(attribute, index) in editRoomForm.room_attributesToDisplay"
                          class="flex rounded-full items-center font-medium text-tagText
                                         border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
                                        {{ attribute.name }}
                                        <button @click="editRoomForm.room_attributesToDisplay.splice(index,1)"
                                                type="button">
                                            <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
                                        </button>
                                    </span>
                    <span v-for="(room, index) in editRoomForm.adjoining_roomsToDisplay"
                          class="flex rounded-full items-center font-medium text-tagText
                                         border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
                                        {{ $t('adjoining room from')}} {{ room.name }}
                                        <button @click="editRoomForm.adjoining_roomsToDisplay.splice(index,1)"
                                                type="button">
                                            <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
                                        </button>
                                    </span>


                </div>
                <div class="flex items-center my-6">
                    <input v-model="editRoomForm.temporary"
                           type="checkbox"
                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                    <p :class="[editRoomForm.temporary ? 'text-primary font-black' : 'text-secondary']"
                       class="ml-4 my-auto text-sm">{{$t('Temporary room')}}</p>
                    <div v-if="this.$page.props.show_hints" class="flex mt-1">
                        <SvgCollection svgName="arrowLeft" class="h-6 w-6 ml-2 mr-2"/>
                        <span
                            class="ml-1 my-auto hind">{{ $t('Set up a temporary room - e.g. if part of a room is partitioned off. This is only displayed in the calendar during this period.')}}</span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-x-3" v-if="editRoomForm.temporary">
                    <input
                        v-model="editRoomForm.start_date_dt_local" id="startDateEdit"
                        :placeholder="$t('To be completed by?')" type="date"
                        class="border-gray-300 col-span-1 placeholder-secondary mr-2 w-full"/>
                    <input
                        v-model="editRoomForm.end_date_dt_local" id="endDateEdit"
                        :placeholder="$t('To be completed by?')" type="date"
                        class="border-gray-300 col-span-1 placeholder-secondary w-full"/>
                </div>

                <div class="flex items-center my-6">
                    <input v-model="editRoomForm.everyone_can_book"
                           type="checkbox"
                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                    <p :class="[editRoomForm.everyone_can_book ? 'text-primary font-black' : 'text-secondary']"
                       class="ml-4 my-auto text-sm">{{ $t('Can be booked by anyone')}}</p>
                    <div v-if="this.$page.props.show_hints" class="flex mt-1">
                        <SvgCollection svgName="arrowLeft" class="h-6 w-6 ml-2 mr-2"/>
                        <span
                            class="ml-1 my-auto hind">{{ $t('Decides whether this room can be booked by everyone or only by the room admins.')}}</span>
                    </div>
                </div>

                <div class="w-full items-center text-center">
                    <FormButton
                        @click="editRoom"
                        :disabled="editRoomForm.name.length === 0"
                        :text="$t('Save')"
                        class="inline-flex items-center mt-8"
                    />
                </div>

            </div>
        </div>
    </BaseModal>
    <!-- Success Modal -->
    <SuccessModal
        :show="showSuccessModal"
        :title="successHeading"
        :description="successDescription"
        @closed="closeSuccessModal"
    />
    <!-- Delete Area Modal -->
    <ConfirmationComponent v-if="showSoftDeleteAreaModal"
                           :confirm="$t('In the recycle bin')"
                           :titel="$t('Area in the trash')"
                           :description="areaDeleteDescriptionText"
                           @closed="afterSoftDeleteAreaConfirm"/>
    <!-- Delete All Rooms from Area Modal -->
    <ConfirmationComponent v-if="showDeleteAllRoomsModal"
                           :confirm="$t('In the recycle bin')"
                           :titel="$t('Remove all rooms')"
                           :description="$t('Are you sure you want to put all the rooms in this area in the recycle bin?')"
                           @closed="afterSoftDeleteAllRoomsConfirm"/>
    <!-- Delete Room Modal -->
    <ConfirmationComponent v-if="showSoftDeleteRoomModal"
                           :confirm="$t('Delete room')"
                           :titel="$t('Room in the recycle bin')"
                           :description="roomDeleteDescriptionText"
                           @closed="afterSoftDeleteRoomConfirm"/>
    <!-- Delete Room Category Modal -->
    <ConfirmationComponent v-if="roomCategoryDeleteModalVisible"
                           :confirm="$t('Delete room category')"
                           :titel="$t('Delete room category')"
                           :description="$t('Are you sure you want to delete the room category? This irrevocably deletes all room assignments to this room category.')"
                           @closed="afterDeleteRoomCategoryConfirm"/>
    <!-- Delete Room Attribute Modal -->
    <ConfirmationComponent v-if="roomAttributeDeleteModalVisible"
                           :confirm="$t('Delete room property')"
                           :titel="$t('Delete room property')"
                           :description="$t('Are you sure you want to delete the room property? This irrevocably deletes all room assignments for this room property.')"
                           @closed="afterDeleteRoomAttributeConfirm"/>
</template>

<script>

import AppLayout from '@/Layouts/AppLayout.vue'
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import Button from "@/Jetstream/Button.vue";
import {
    DotsVerticalIcon,
    InformationCircleIcon,
    PencilAltIcon,
    SearchIcon, TrashIcon,
    XIcon, DuplicateIcon, DocumentTextIcon
} from "@heroicons/vue/outline";
import {CheckIcon, ChevronUpIcon, ChevronDownIcon, PlusSmIcon, XCircleIcon} from "@heroicons/vue/solid";
import {Menu, MenuButton, MenuItem, MenuItems, Disclosure, DisclosureButton, DisclosurePanel} from "@headlessui/vue";
import JetButton from "@/Jetstream/Button.vue";
import {defineComponent} from 'vue'
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import JetInput from "@/Jetstream/Input.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import JetSecondaryButton from "@/Jetstream/SecondaryButton.vue";
import {Link, useForm} from "@inertiajs/vue3";
import draggable from "vuedraggable";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import {router} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import AddButtonBig from "@/Layouts/Components/General/Buttons/AddButtonBig.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import Tabs from "@/Pages/Areas/Components/Tabs.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default defineComponent({
    mixins: [Permissions, IconLib],
    components: {
        BaseModal,
        BaseMenu,
        Tabs,
        FormButton,
        AddButtonBig,
        SuccessModal,
        ConfirmationComponent,
        UserPopoverTooltip,
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
        DocumentTextIcon,
        Disclosure, DisclosureButton, DisclosurePanel
    },
    name: 'Area Management',
    props: ['areas', 'opened_areas', 'room_categories', 'room_attributes'],
    data() {
        return {
            roomCategoryInput: '',
            roomAttributeInput: '',
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
                everyone_can_book: false,
                room_categories: [],
                room_attributes: [],
                adjoining_rooms: [],
                room_categoriesToDisplay: [],
                room_attributesToDisplay: [],
                adjoining_roomsToDisplay: []
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
                everyone_can_book: false,
                room_categories: [],
                room_attributes: [],
                adjoining_rooms: [],
                room_categoriesToDisplay: [],
                room_attributesToDisplay: [],
                adjoining_roomsToDisplay: []
            }),
            editAreaForm: useForm({
                id: null,
                name: '',
                rooms: [],
            }),
            showInvalidNameErrorText: false,
            roomCategoryDeleteModalVisible: false,
            roomCategoryToDelete: null,
            roomAttributeDeleteModalVisible: false,
            roomAttributeToDelete: null,
        }
    },
    computed: {
        roomDeleteDescriptionText() {
            return this.$t('Are you sure you want to put the room {0} in the trash?', [this.roomToSoftDelete.name]);
        },
        areaDeleteDescriptionText() {
            return this.$t('Are you sure you want to put the area {0} with all rooms in the recycle bin?', [this.areaToSoftDelete.name]);
        }
    },
    methods: {
        afterSoftDeleteAllRoomsConfirm(confirmed) {
            if (confirmed) {
                this.softDeleteAllRooms()
            } else {
                this.closeDeleteAllRoomsModal()
            }
        },
        afterSoftDeleteRoomConfirm(confirmed) {
            if (confirmed) {
                this.softDeleteRoom()
            } else {
                this.closeSoftDeleteRoomModal()
            }
        },
        afterSoftDeleteAreaConfirm(confirmed) {
            if (confirmed) {
                this.softDeleteArea()
            } else {
                this.closeSoftDeleteAreaModal()
            }
        },
        showRoomCategoryDeleteModal(roomCategory) {
            this.roomCategoryToDelete = roomCategory;
            this.roomCategoryDeleteModalVisible = true;
        },
        afterDeleteRoomCategoryConfirm(confirmed) {
            if (confirmed) {
                router.delete(route('room_categories.destroy', {roomCategory: this.roomCategoryToDelete.id}));
                this.roomCategoryToDelete = null;
            }
            this.roomCategoryDeleteModalVisible = false;
        },
        showRoomAttributeDeleteModal(roomAttribute) {
            this.roomAttributeToDelete = roomAttribute;
            this.roomAttributeDeleteModalVisible = true;
        },
        afterDeleteRoomAttributeConfirm(confirmed) {
            if (confirmed) {
                router.delete(route('room_attribute.destroy', {roomAttribute: this.roomAttributeToDelete.id}));
                this.roomAttributeToDelete = null;
            }
            this.roomAttributeDeleteModalVisible = false;
        },
        checkNameRegex(name) {
            //Leerzeichen am Anfang und am Ende des Strings sind nicht erlaubt, aber innerhalb des Strings
            const regex = /^(?!\s)(?:(?!\s+$)\s|\S+\s*\S*)*(?<!\s)$/;
            return regex.test(name);
        },
        addRoomCategory() {
            if (this.checkNameRegex(this.roomCategoryInput)) {
                this.showInvalidNameErrorText = false;
                router.post(
                    route('room_categories.store'),
                    {
                        name: this.roomCategoryInput
                    },
                    {
                        onSuccess: () => this.roomCategoryInput = ''
                    }
                );
            } else {
                this.showInvalidNameErrorText = true;
            }
        },
        deleteRoomAttribute(attribute) {
            router.delete(route('room_attribute.destroy', {roomAttribute: attribute.id}));
        },
        addRoomAttribute() {
            if (this.checkNameRegex(this.roomAttributeInput)) {
                this.showInvalidNameErrorText = false;
                router.post(
                    route('room_attribute.store'),
                    {
                        name: this.roomAttributeInput
                    },
                    {
                        onSuccess: () => this.roomAttributeInput = ''
                    }
                );
            } else {
                this.showInvalidNameErrorText = true;
            }
        },
        changeAreaStatus(area) {
            if (!this.opened_areas.includes(area.id)) {
                const openedAreas = this.opened_areas;

                openedAreas.push(area.id)
                router.patch(`/users/${this.$page.props.user.id}/areas`, {"opened_areas": openedAreas}, {
                    preserveScroll: true,
                    preserveState: true
                });
            } else {
                const filteredList = this.opened_areas.filter(function (value) {
                    return value !== area.id;
                })
                router.patch(`/users/${this.$page.props.user.id}/areas`, {"opened_areas": filteredList},{
                    preserveScroll: true,
                    preserveState: true
                });
            }
        },
        updateRoomOrder(rooms) {
            rooms.map((room, index) => {
                room.order = index + 1
            })

            router.put('/rooms/order', {
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
            this.newRoomForm.everyone_can_book = false;
            this.newRoomForm.room_categories = [];
            this.newRoomForm.room_attributes = [];
            this.newRoomForm.adjoining_rooms = [];
            this.newRoomForm.room_categoriesToDisplay = [];
            this.newRoomForm.room_attributesToDisplay = [];
            this.newRoomForm.adjoining_roomsToDisplay = [];
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
            router.post(`/areas/${area.id}/duplicate`);
        },
        duplicateRoom(room) {
            router.post(`/rooms/${room.id}/duplicate`);
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
            router.delete(`/areas/${this.areaToSoftDelete.id}`);
            this.closeSoftDeleteAreaModal()
            this.successHeading = this.$t('Area in the recycle bin')
            this.successDescription = this.$t('The area and all associated rooms have been successfully trashed.')
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
                router.delete(`/rooms/${room.id}`);
            })
            this.closeDeleteAllRoomsModal();
            this.successHeading = this.$t('Room in the recycle bin')
            this.successDescription = this.$t('The rooms have been successfully moved to the trash.')
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
            room.adjoining_rooms.forEach((adjoining_room) => {
                this.editRoomForm.adjoining_roomsToDisplay.push({id: adjoining_room.id, name: adjoining_room.name})
            });
            room.room_categories.forEach((room_category) => {
                this.editRoomForm.room_categoriesToDisplay.push({id: room_category.id, name: room_category.name})
            });
            room.room_attributes.forEach((room_attribute) => {
                this.editRoomForm.room_attributesToDisplay.push({id: room_attribute.id, name: room_attribute.name})
            });

            if (room.temporary === true) {
                this.editRoomForm.temporary = true;
            }
            this.showEditRoomModal = true;
            this.editRoomForm.everyone_can_book = room.everyone_can_book
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
            this.editRoomForm.adjoining_roomsToDisplay = [];
            this.editRoomForm.room_categoriesToDisplay = [];
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
            router.delete(`/rooms/${this.roomToSoftDelete.id}`);
            this.closeSoftDeleteRoomModal();
            this.successHeading = this.$t('Room in the recycle bin')
            this.successDescription = this.$t('The rooms have been successfully moved to the trash.')
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

            this.editRoomForm.adjoining_roomsToDisplay.forEach((adjoining_room) => {
                this.editRoomForm.adjoining_rooms.push(adjoining_room.id);
            })
            this.editRoomForm.room_categoriesToDisplay.forEach((room_category) => {
                this.editRoomForm.room_categories.push(room_category.id);
            })
            this.editRoomForm.room_attributesToDisplay.forEach((room_attributes) => {
                this.editRoomForm.room_attributes.push(room_attributes.id);
            })

            this.editRoomForm.patch(route('rooms.update', {room: this.editRoomForm.id}));
            this.closeEditRoomModal();
        }
    },
})
</script>

<style scoped>

</style>
