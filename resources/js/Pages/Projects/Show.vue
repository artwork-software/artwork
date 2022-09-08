<template>
    <app-layout>
        <div class="max-w-screen-2xl my-12 ml-20 mr-10 flex flex-row">
            <div class="flex w-8/12 flex-col">
                <div class="flex ">
                    <h2 class="flex font-black font-lexend text-primary tracking-wide text-3xl">
                        {{ project.name }}</h2>
                    <Menu as="div" class="my-auto mt-3 relative"
                          v-if="this.$page.props.can.create_and_edit_projects || this.$page.props.is_admin || this.$page.props.can.admin_projects || projectAdminIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id)">
                        <div class="flex items-center -mt-1">
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
                                    <span class="font-nanum ml-2 text-secondary tracking-tight tracking-tight text-lg">Bearbeite die Basisdaten</span>
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
                                class="origin-top-left absolute left-0 mr-4 mt-2 w-72 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                <div class="py-1">
                                    <MenuItem
                                        v-if="this.$page.props.is_admin || projectAdminIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id)"
                                        v-slot="{ active }">
                                        <a @click="openEditProjectModal"
                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <PencilAltIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            Basisdaten bearbeiten
                                        </a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <a href="#" @click="duplicateProject(this.project)"
                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <DuplicateIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            Duplizieren
                                        </a>
                                    </MenuItem>
                                    <MenuItem
                                        v-if="this.$page.props.can.create_and_edit_projects || this.$page.props.is_admin || this.$page.props.can.admin_projects || projectAdminIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id)"
                                        v-slot="{ active }">
                                        <a @click="openDeleteProjectModal(this.project)"
                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <TrashIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            In den Papierkorb legen
                                        </a>
                                    </MenuItem>
                                </div>
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>
                <div class="mt-2 subpixel-antialiased text-secondary text-xs flex items-center">
                    <div>
                        zuletzt geändert:
                    </div>
                    <img :data-tooltip-target="project.project_history[0].user.id"
                         :src="project.project_history[0].user.profile_photo_url"
                         :alt="project.project_history[0].user.name"
                         class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                    <UserTooltip :user="project.project_history[0].user"/>
                    <span class="ml-2 subpixel-antialiased">
                        {{ project.project_history[0].created_at }}
                    </span>
                    <button class="ml-4 subpixel-antialiased flex items-center cursor-pointer"
                            @click="openProjectHistoryModal()">
                        <ChevronRightIcon
                            class="-mr-0.5 h-4 w-4 text-primaryText group-hover:text-white"
                            aria-hidden="true"/>
                        Verlauf ansehen
                    </button>
                </div>
                <div class="mt-2 mr-14 subpixel-antialiased text-secondary">
                    {{ project.description }}
                </div>
                <div class="mt-4 text-xs text-secondary">
                    <span class="subpixel-antialiased">Kostenträger: </span><span class="text-primary font-bold">{{
                        project.cost_center ? project.cost_center : 'noch nicht definiert'
                    }} </span><span class="subpixel-antialiased"> | Anzahl
                    Teilnehmer*innen: </span>
                    <span class="text-primary font-bold">{{
                            project.number_of_participants ? project.number_of_participants : 'noch nicht definiert'
                        }} </span>
                </div>
                <div class="mt-3 flex text-secondary text-xs">
                    <span class="mr-2 subpixel-antialiased">Kategorie: </span>
                    <span class="ml-1 mr-1 text-primary font-bold">{{
                            project.category ? project.category.name : 'noch nicht definiert'
                        }} </span> <span class="subpixel-antialiased"> | Genre: </span><span
                    class="text-primary font-bold ml-1 mr-1 ">
                    {{ project.genre ? project.genre.name : 'noch nicht definiert' }} </span> <span
                    class="subpixel-antialiased"> | Bereich:  </span><span
                    class="text-primary font-bold ml-1">
                    {{ project.sector ? project.sector.name : 'noch nicht definiert' }} </span>
                </div>
            </div>
            <div class="flex flex-wrap">
                <div class="flex mr-2 mt-8 flex-1 flex-wrap">
                    <h2 class="text-xl leading-6 font-bold font-lexend text-primary mb-3">Projektteam</h2>
                    <div class="flex"
                         v-if="this.$page.props.can.create_and_edit_projects || this.$page.props.is_admin || this.$page.props.can.admin_projects || projectAdminIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id)">
                        <div class="cursor-pointer" @click="openEditProjectTeamModal">
                            <DotsVerticalIcon class="ml-2 mr-1 flex-shrink-0 h-6 w-6 text-gray-600"
                                              aria-hidden="true"/>
                        </div>
                        <div>
                            <div v-if="$page.props.can.show_hints" class="absolute flex w-48">
                                <div>
                                    <SvgCollection svgName="arrowLeft" class="mt-1"/>
                                </div>
                                <div class="flex">
                                    <span class="font-nanum ml-2 text-secondary tracking-tight tracking-tight text-lg">Stelle dein Team zusammen</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap w-full">
                    <span class="flex font-black text-xs text-secondary w-full subpixel-antialiased tracking-widest">PROJEKTLEITUNG</span>
                    <div class="flex mt-2 -mr-3" v-for="user in this.project.project_managers">
                        <img :data-tooltip-target="user.id" :src="user.profile_photo_url" :alt="user.name"
                             class="ring-white ring-2 rounded-full h-11 w-11 object-cover"/>
                        <UserTooltip :user="user"/>
                    </div>

                </div>
                <div class="flex w-full mt-2 flex-wrap">
                    <span class="flex font-black text-xs text-secondary w-full subpixel-antialiased tracking-widest">TEAM</span>
                    <div class="flex w-full">
                        <div class="flex" v-if="this.project.departments !== []">
                            <div class="flex mt-2 -mr-3" v-for="department in this.project.departments.slice(0,5)">
                                <TeamIconCollection :data-tooltip-target="department.name"
                                                    :iconName="department.svg_name"
                                                    :alt="department.name"
                                                    class="ring-white ring-2 rounded-full h-11 w-11 object-cover"/>
                                <TeamTooltip :team="department"/>
                            </div>
                            <div v-if="this.project.departments.length >= 5" class="my-auto">
                                <Menu as="div" class="relative">
                                    <div>
                                        <MenuButton class="flex items-center rounded-full focus:outline-none">
                                            <ChevronDownIcon
                                                class="ml-1 flex-shrink-0 h-11 w-11 flex my-auto items-center ring-2 ring-white font-semibold rounded-full shadow-sm text-white bg-primary"></ChevronDownIcon>
                                        </MenuButton>
                                    </div>
                                    <transition enter-active-class="transition ease-out duration-100"
                                                enter-from-class="transform opacity-0 scale-95"
                                                enter-to-class="transform opacity-100 scale-100"
                                                leave-active-class="transition ease-in duration-75"
                                                leave-from-class="transform opacity-100 scale-100"
                                                leave-to-class="transform opacity-0 scale-95">
                                        <MenuItems
                                            class="z-40 absolute overflow-y-auto max-h-48 mt-2 w-72 mr-12 origin-top-right shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none">
                                            <MenuItem v-for="department in this.project.departments"
                                                      v-slot="{ active }">
                                                <Link href="#"
                                                      :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <TeamIconCollection class="h-10 w-10 rounded-full"
                                                                        :iconName="department.svg_name"/>
                                                    <span class="ml-4">
                                                                {{ department.name }}
                                                            </span>
                                                </Link>
                                            </MenuItem>
                                        </MenuItems>
                                    </transition>
                                </Menu>
                            </div>
                        </div>
                        <div class="flex -mr-3 mt-2" v-for="user in projectMembers.slice(0,5)">
                            <img :data-tooltip-target="user.id" :src="user.profile_photo_url" :alt="user.name"
                                 class="rounded-full ring-white ring-2 h-11 w-11 object-cover"/>
                            <UserTooltip :user="user"/>
                        </div>
                        <div v-if="projectMembers.length >= 5" class="my-auto">
                            <Menu as="div" class="relative">
                                <div>
                                    <MenuButton class="flex items-center rounded-full focus:outline-none">
                                        <ChevronDownIcon
                                            class="ml-1 flex-shrink-0 h-9 w-9 flex my-auto items-center ring-2 ring-white font-semibold rounded-full shadow-sm text-white bg-primary"></ChevronDownIcon>
                                    </MenuButton>
                                </div>
                                <transition enter-active-class="transition ease-out duration-100"
                                            enter-from-class="transform opacity-0 scale-95"
                                            enter-to-class="transform opacity-100 scale-100"
                                            leave-active-class="transition ease-in duration-75"
                                            leave-from-class="transform opacity-100 scale-100"
                                            leave-to-class="transform opacity-0 scale-95">
                                    <MenuItems
                                        class="z-40 absolute overflow-y-auto max-h-48 mt-2 w-72 mr-12 origin-top-right shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none">
                                        <MenuItem v-for="user in projectMembers" v-slot="{ active }">
                                            <Link href="#"
                                                  :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <img class="h-9 w-9 rounded-full"
                                                     :src="user.profile_photo_url"
                                                     alt=""/>
                                                <span class="ml-4">
                                                                {{ user.first_name }} {{ user.last_name }}
                                                            </span>
                                            </Link>
                                        </MenuItem>
                                    </MenuItems>
                                </transition>
                            </Menu>
                        </div>
                    </div>

                </div>

            </div>
        </div>
        <!-- Div with Bg-Color -->
        <div class="bg-backgroundGray w-full h-full">
            <div class="ml-20">
                <div class="hidden sm:block">
                    <div class="border-gray-200">
                        <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8" aria-label="Tabs">
                            <a @click="changeTab(tab)" v-for="tab in tabs" href="#" :key="tab.name"
                               :class="[tab.current ? 'border-primary text-primary' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium font-semibold']"
                               :aria-current="tab.current ? 'page' : undefined">
                                {{ tab.name }}
                            </a>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="max-w-screen-2xl">
                <!-- Calendar Tab -->
                <div v-if="isScheduleTab && project.rooms">
                    <vue-cal
                        style="height: 500px"
                        today-button
                        events-on-month-view="short"
                        locale="de"
                        :disable-views="['years', 'year']"
                        :events="events"
                        :editable-events="{ title: false, drag: true, resize: true, delete: false, create: true }"
                        :snap-to-time="15"

                        @event-drop="updateEvent($event)"

                        @ready="fetchEvents"
                        @view-change="fetchEvents"
                    />
                </div>
                <!-- Checklist Tab -->
                <div v-if="isChecklistTab" class="grid grid-cols-3 ml-20 mt-14">
                    <div class="col-span-2">
                        <div class="flex w-full items-center mb-8 ">
                            <h2 class="text-xl leading-6 font-bold font-lexend text-primary"> Checklisten </h2>
                            <div class="flex items-center"
                                 v-if="this.$page.props.can.create_and_edit_projects || this.$page.props.is_admin || this.$page.props.can.admin_projects || projectAdminIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id)">
                                <button @click="openAddChecklistModal" type="button"
                                        class="flex cursor-pointer ml-4 border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none">
                                    <PlusSmIcon class="h-5 w-5" aria-hidden="true"/>
                                </button>
                                <div v-if="$page.props.can.show_hints" class="flex">
                                    <SvgCollection svgName="arrowLeft" class="ml-2"/>
                                    <span
                                        class="font-nanum text-secondary tracking-tight ml-1 my-auto tracking-tight text-xl">Lege neue Checklisten an</span>
                                </div>
                            </div>
                        </div>
                        <div class="w-full">
                        <span v-if="project.public_checklists.length === 0 && project.private_checklists.length === 0"
                              class="text-secondary subpixel-antialiased text-xs mb-4">Noch keine Checklisten hinzugefügt. Erstelle Checklisten mit Aufgaben. Die Checklisten kannst du Teams zuordnen. Nutze Vorlagen und spare Zeit.</span>
                            <div v-else>
                                <div class="flex w-full flex-wrap">
                                    <!-- Div einer Checkliste -->
                                    <div v-for="checklist in project.public_checklists"
                                         class="flex w-full bg-white my-2">
                                        <button class="bg-primary flex"
                                                @click="changeChecklistStatus(checklist)">
                                            <ChevronUpIcon v-if="this.opened_checklists.includes(checklist.id)"
                                                           class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
                                            <ChevronDownIcon v-else
                                                             class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
                                        </button>
                                        <div class="flex w-full ml-4 flex-wrap p-4">
                                            <div class="flex justify-between w-full my-auto">
                                                <div class="">
                                        <span class="text-xl leading-6 font-bold font-lexend text-primary">
                                        {{ checklist.name }}
                                        </span>
                                                </div>
                                                <div class="flex">
                                                    <div class="flex -mr-3"
                                                         v-for="department in checklist.departments.slice(0,9)">
                                                        <TeamIconCollection :data-tooltip-target="department.name"
                                                                            :iconName="department.svg_name"
                                                                            :alt="department.name"
                                                                            class="ring-white ring-2 rounded-full h-9 w-9 object-cover"/>
                                                        <TeamTooltip :team="department"/>
                                                    </div>
                                                    <div v-if="checklist.departments.length >= 10" class="my-auto">
                                                        <Menu as="div" class="relative">
                                                            <div>
                                                                <MenuButton
                                                                    class="flex items-center rounded-full focus:outline-none">
                                                                    <ChevronDownIcon
                                                                        class="ml-1 flex-shrink-0 h-9 w-9 flex my-auto items-center ring-2 ring-white font-semibold rounded-full shadow-sm text-white bg-primary"></ChevronDownIcon>
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
                                                                    class="z-40 absolute overflow-y-auto max-h-48 mt-2 w-72 mr-12 origin-top-right shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none">
                                                                    <MenuItem
                                                                        v-for="department in checklist.departments"
                                                                        v-slot="{ active }">
                                                                        <div
                                                                            :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                            <TeamIconCollection
                                                                                :iconName="department.svg_name"
                                                                                :alt="department.name"
                                                                                class="ring-white ring-2 rounded-full h-9 w-9 object-cover"/>
                                                                            <span class="ml-4">
                                                                {{ department.name }}
                                                            </span>
                                                                        </div>
                                                                    </MenuItem>
                                                                </MenuItems>
                                                            </transition>
                                                        </Menu>
                                                    </div>
                                                    <Menu
                                                        v-if="this.$page.props.can.create_and_edit_projects || this.$page.props.is_admin || this.$page.props.can.admin_projects || projectAdminIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id)"
                                                        as="div" class="my-auto relative">
                                                        <div class="flex">
                                                            <MenuButton
                                                                class="flex ml-9">
                                                                <DotsVerticalIcon
                                                                    class="z-2 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
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
                                                                class="z-40 origin-top-right absolute right-0 w-56 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                                                <div class="py-1">
                                                                    <MenuItem v-slot="{ active }">
                                                                        <a @click="openEditChecklistTeamsModal(checklist)"
                                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                            <PencilAltIcon
                                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                aria-hidden="true"/>
                                                                            Teams zuweisen
                                                                        </a>
                                                                    </MenuItem>
                                                                    <MenuItem v-slot="{ active }">
                                                                        <a @click="openEditChecklistModal(checklist)"
                                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                            <PencilAltIcon
                                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                aria-hidden="true"/>
                                                                            Bearbeiten
                                                                        </a>
                                                                    </MenuItem>
                                                                    <MenuItem v-slot="{ active }"
                                                                              v-if="allTasksChecked(checklist) === false && checklist.tasks.length > 0">
                                                                        <a @click="checkAllTasks(checklist)"
                                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                            <PencilAltIcon
                                                                                class="mr-3 h-5 w-5 shrink-0 text-primaryText group-hover:text-white"
                                                                                aria-hidden="true"/>
                                                                            Alle Aufgaben als erledigt markieren
                                                                        </a>
                                                                    </MenuItem>
                                                                    <MenuItem v-slot="{ active }"
                                                                              v-if="allTasksChecked(checklist) === true && checklist.tasks.length > 0">
                                                                        <a @click="uncheckAllTasks(checklist)"
                                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                            <PencilAltIcon
                                                                                class="mr-3 h-5 w-5 shrink-0 text-primaryText group-hover:text-white"
                                                                                aria-hidden="true"/>
                                                                            Alle Aufgaben als unerledigt markieren
                                                                        </a>
                                                                    </MenuItem>
                                                                    <MenuItem
                                                                        v-if="this.$page.props.is_admin || this.$page.props.admin_checklistTemplates"
                                                                        v-slot="{ active }">
                                                                        <a @click="createTemplateFromChecklist(checklist)"
                                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                            <PencilAltIcon
                                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                aria-hidden="true"/>
                                                                            Als Vorlage speichern
                                                                        </a>
                                                                    </MenuItem>
                                                                    <MenuItem v-slot="{ active }">
                                                                        <a href="#"
                                                                           @click="duplicateChecklist(checklist)"
                                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                            <DuplicateIcon
                                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                aria-hidden="true"/>
                                                                            Duplizieren
                                                                        </a>
                                                                    </MenuItem>
                                                                    <MenuItem v-slot="{ active }">
                                                                        <a @click="openDeleteChecklistModal(checklist)"
                                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                            <TrashIcon
                                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                aria-hidden="true"/>
                                                                            Löschen
                                                                        </a>
                                                                    </MenuItem>
                                                                </div>
                                                            </MenuItems>
                                                        </transition>
                                                    </Menu>
                                                </div>
                                            </div>
                                            <div class="flex w-full mt-6"
                                                 v-if="this.opened_checklists.includes(checklist.id)">
                                                <div class="flex"
                                                     v-if="this.$page.props.can.create_and_edit_projects || this.$page.props.is_admin || this.$page.props.can.admin_projects || projectAdminIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id)">
                                                    <div>
                                                        <button @click="openAddTaskModal(checklist)" type="button"
                                                                class="flex border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none">
                                                            <PlusSmIcon class="h-5 w-5" aria-hidden="true"/>
                                                        </button>
                                                    </div>
                                                    <div v-if="$page.props.can.show_hints" class="flex">
                                                        <SvgCollection svgName="arrowLeft" class="ml-2"/>
                                                        <span
                                                            class="font-nanum text-secondary tracking-tight ml-1 my-auto tracking-tight text-xl">Lege neue Aufgaben an</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-6 mb-12"
                                                 v-if="this.opened_checklists.includes(checklist.id)">
                                                <draggable ghost-class="opacity-50"
                                                           key="draggableKey"
                                                           item-key="draggableID" :list="checklist.tasks"
                                                           @start="dragging=true" @end="dragging=false"
                                                           @change="updateTaskOrder(checklist.tasks)">
                                                    <template #item="{element}" :key="element.id">
                                                        <div class="flex" @mouseover="showMenu = element.id"
                                                             :key="element.id"
                                                             @mouseout="showMenu = null">
                                                            <div class="flex mt-6 flex-wrap w-full"
                                                                 :class="dragging? 'cursor-grabbing' : 'cursor-grab'">
                                                                <div class="flex w-full">
                                                                    <div v-if="showMenu === element.id"
                                                                         class="flex -mt-1 items-center">
                                                                        <DotsVerticalIcon
                                                                            class="h-5 w-5 -mr-3.5 text-secondary"></DotsVerticalIcon>
                                                                        <DotsVerticalIcon
                                                                            class="h-5 w-5 text-secondary"></DotsVerticalIcon>
                                                                    </div>
                                                                    <div v-else class="h-5 w-6 flex">

                                                                    </div>
                                                                    <input @change="updateTaskStatus(element)"
                                                                           v-model="element.done"
                                                                           type="checkbox"
                                                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                                                    <p class="ml-4 my-auto text-lg font-black"
                                                                       :class="element.done ? 'text-secondary line-through' : 'text-primary'">
                                                                        {{ element.name }}</p>
                                                                    <span v-if="!element.done && element.deadline"
                                                                          class="ml-2 my-auto text-sm subpixel-antialiased"
                                                                          :class="Date.parse(element.deadline_dt_local) < new Date().getTime()? 'text-error subpixel-antialiased' : ''">bis {{
                                                                            element.deadline
                                                                        }}</span>
                                                                    <span v-if="element.done && element.done_by_user"
                                                                          class="ml-2 flex my-auto items-center text-sm text-secondary">
                                                                        <img
                                                                            :data-tooltip-target="element.done_by_user.id"
                                                                            v-if="element.done_by_user"
                                                                            :src="element.done_by_user.profile_photo_url"
                                                                            :alt="element.done_by_user.name"
                                                                            class="rounded-full mr-2 my-auto h-7 w-7 object-cover"/>
                                                                        <UserTooltip :user="element.done_by_user"/>
                                                                        {{ element.done_at }}
                                                                    </span>
                                                                    <Menu
                                                                        v-if="this.$page.props.can.create_and_edit_projects || this.$page.props.is_admin || this.$page.props.can.admin_projects || projectAdminIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id)"
                                                                        as="div" class="my-auto relative"
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
                                                                                class="origin-top-right absolute right-0 w-56 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                                                                <div class="py-1">
                                                                                    <MenuItem v-slot="{ active }">
                                                                                        <a @click="openEditTaskModal(element)"
                                                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                            <PencilAltIcon
                                                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                                aria-hidden="true"/>
                                                                                            Bearbeiten
                                                                                        </a>
                                                                                    </MenuItem>
                                                                                    <MenuItem v-slot="{ active }">
                                                                                        <a @click="deleteTask(element)"
                                                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                            <TrashIcon
                                                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                                aria-hidden="true"/>
                                                                                            Löschen
                                                                                        </a>
                                                                                    </MenuItem>
                                                                                </div>
                                                                            </MenuItems>
                                                                        </transition>
                                                                    </Menu>
                                                                </div>
                                                                <div v-if="!element.done"
                                                                     class="ml-16 text-sm text-secondary subpixel-antialiased">
                                                                    {{ element.description }}
                                                                </div>
                                                            </div>

                                                        </div>

                                                    </template>
                                                </draggable>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-for="checklist in project.private_checklists"
                                         class="flex w-full bg-white my-2">
                                        <button class="bg-primary flex"
                                                @click="changeChecklistStatus(checklist)">
                                            <ChevronUpIcon v-if="this.opened_checklists.includes(checklist.id)"
                                                           class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
                                            <ChevronDownIcon v-else
                                                             class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
                                        </button>
                                        <div class="flex w-full ml-4 flex-wrap p-4">
                                            <div class="flex justify-between w-full">
                                                <div class="my-auto">
                                        <span class="text-xl leading-6 flex font-bold font-lexend text-primary">
                                        {{ checklist.name }} <EyeIcon class="h-6 w-6 ml-3 text-primary"></EyeIcon> <p
                                            class="text-primary text-sm my-auto ml-1">Privat</p>
                                        </span>
                                                </div>
                                                <div class="flex items-center -mr-3">
                                                    <img class="h-9 w-9 rounded-full"
                                                         :src="$page.props.user.profile_photo_url"
                                                         alt=""/>
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
                                                                class="origin-top-right absolute right-0 w-56 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                                                <div class="py-1">
                                                                    <MenuItem v-slot="{ active }">
                                                                        <a @click="openEditChecklistModal(checklist)"
                                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                            <PencilAltIcon
                                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                aria-hidden="true"/>
                                                                            Bearbeiten
                                                                        </a>
                                                                    </MenuItem>
                                                                    <MenuItem v-slot="{ active }">
                                                                        <a @click="checkAllTasks(checklist.tasks)"
                                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                            <PencilAltIcon
                                                                                class="mr-3 h-5 w-5 shrink-0 text-primaryText group-hover:text-white"
                                                                                aria-hidden="true"/>
                                                                            Alle Aufgaben als erledigt markieren
                                                                        </a>
                                                                    </MenuItem>
                                                                    <MenuItem v-slot="{ active }">
                                                                        <a @click=""
                                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                            <PencilAltIcon
                                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                aria-hidden="true"/>
                                                                            Als Vorlage speichern
                                                                        </a>
                                                                    </MenuItem>
                                                                    <MenuItem v-slot="{ active }">
                                                                        <a href="#"
                                                                           @click="duplicateChecklist(checklist)"
                                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                            <DuplicateIcon
                                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                aria-hidden="true"/>
                                                                            Duplizieren
                                                                        </a>
                                                                    </MenuItem>
                                                                    <MenuItem v-slot="{ active }">
                                                                        <a @click="openDeleteChecklistModal(checklist)"
                                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                            <TrashIcon
                                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                aria-hidden="true"/>
                                                                            Löschen
                                                                        </a>
                                                                    </MenuItem>
                                                                </div>
                                                            </MenuItems>
                                                        </transition>
                                                    </Menu>
                                                </div>
                                            </div>
                                            <div class="flex w-full mt-6"
                                                 v-if="this.opened_checklists.includes(checklist.id)">
                                                <div class="">
                                                    <button @click="openAddTaskModal(checklist)" type="button"
                                                            class="flex border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none">
                                                        <PlusSmIcon class="h-5 w-5" aria-hidden="true"/>
                                                    </button>
                                                </div>
                                                <div v-if="$page.props.can.show_hints" class="flex">
                                                    <SvgCollection svgName="arrowLeft" class="ml-2"/>
                                                    <span
                                                        class="font-nanum text-secondary tracking-tight ml-1 tracking-tight text-xl">Lege neue Aufgaben an</span>
                                                </div>
                                            </div>
                                            <div class="mt-6 mb-12"
                                                 v-if="this.opened_checklists.includes(checklist.id)">
                                                <draggable ghost-class="opacity-50"
                                                           key="draggableKey"
                                                           item-key="id" :list="checklist.tasks"
                                                           @start="dragging=true" @end="dragging=false"
                                                           @change="updateTaskOrder(checklist.tasks)">
                                                    <template #item="{element}" :key="element.id">
                                                        <div class="flex items-center"
                                                             @mouseover="showMenu = element.id"
                                                             :key="element.id"
                                                             @mouseout="showMenu = null">
                                                            <div class="flex mt-6 flex-wrap w-full" :key="element.id"
                                                                 :class="dragging? 'cursor-grabbing' : 'cursor-grab'">
                                                                <div class="flex w-full" :key="element.id">
                                                                    <div v-if="showMenu === element.id"
                                                                         class="flex -mt-1 items-center">
                                                                        <DotsVerticalIcon
                                                                            class="h-5 w-5 -mr-3.5 text-secondary"></DotsVerticalIcon>
                                                                        <DotsVerticalIcon
                                                                            class="h-5 w-5 text-secondary"></DotsVerticalIcon>
                                                                    </div>
                                                                    <div v-else class="h-5 w-6 flex">

                                                                    </div>
                                                                    <input @change="updateTaskStatus(element)"
                                                                           v-model="element.done"
                                                                           type="checkbox"
                                                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                                                    <p class="ml-4 my-auto text-lg font-black text-sm"
                                                                       :class="element.done ? 'text-secondary line-through' : 'text-primary'">
                                                                        {{ element.name }}</p>
                                                                    <span v-if="!element.done && element.deadline"
                                                                          class="ml-2 my-auto text-sm subpixel-antialiased"
                                                                          :class="Date.parse(element.deadline_dt_local) < new Date().getTime()? 'text-error subpixel-antialiased' : ''">bis {{
                                                                            element.deadline
                                                                        }}</span>
                                                                    <span v-if="element.done && element.done_by_user"
                                                                          class="ml-2 flex my-auto items-center text-sm text-secondary">
                                                                        <img
                                                                            :data-tooltip-target="element.done_by_user.id"
                                                                            v-if="element.done_by_user"
                                                                            :src="element.done_by_user.profile_photo_url"
                                                                            :alt="element.done_by_user.name"
                                                                            class="rounded-full mr-2 my-auto h-7 w-7 object-cover"/>
                                                                        <UserTooltip :user="element.done_by_user"/>
                                                                        {{ element.done_at }}
                                                                    </span>
                                                                    <Menu as="div" class="my-auto relative"
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
                                                                                class="origin-top-right absolute right-0 w-56 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                                                                <div class="py-1">
                                                                                    <MenuItem v-slot="{ active }">
                                                                                <span
                                                                                    @click="openEditTaskModal(element)"
                                                                                    :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                    <PencilAltIcon
                                                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                        aria-hidden="true"/>
                                                                                    Bearbeiten
                                                                                </span>
                                                                                    </MenuItem>
                                                                                    <MenuItem v-slot="{ active }">
                                                                                        <a @click="deleteTask(element)"
                                                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                            <TrashIcon
                                                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                                aria-hidden="true"/>
                                                                                            Löschen
                                                                                        </a>
                                                                                    </MenuItem>
                                                                                </div>
                                                                            </MenuItems>
                                                                        </transition>
                                                                    </Menu>
                                                                </div>
                                                                <div v-if="!element.done"
                                                                     class="ml-16 text-sm text-secondary subpixel-antialiased">
                                                                    {{ element.description }}
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
                <div v-if="isInfoTab" class="grid grid-cols-3 mx-20 mt-14">
                    <div class="col-span-2 mr-8">
                        <div class="flex w-full items-center mb-8">
                            <h3 class="text-2xl leading-6 font-bold font-lexend text-gray-900"> Wichtige
                                Informationen </h3>
                        </div>
                        <div
                            v-if="this.$page.props.can.create_and_edit_projects || this.$page.props.is_admin || this.$page.props.can.admin_projects || projectAdminIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id) || isMemberOfADepartment"
                            class="relative border-2 hover:border-gray-400 w-full border border-gray-300">
                        <textarea
                            placeholder="Was sollten die anderen Projektmitglieder über das Projekt wissen?"
                            v-model="commentForm.text" rows="4"
                            class="resize-none focus:outline-none focus:ring-0  pt-3 mb-8 placeholder-secondary bg-backgroundGray border-0  w-full"/>
                            <div class="absolute bottom-0 right-0 flex">
                                <div v-if="$page.props.can.show_hints" class="flex mt-1">
                                <span
                                    class="font-nanum text-secondary tracking-tight ml-1 my-auto tracking-tight text-xl">Information veröffentlichen</span>
                                    <SvgCollection svgName="smallArrowRight" class="ml-2 mt-1"/>
                                </div>
                                <button
                                    :class="[commentForm.text === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none', ' mr-1 mb-1 rounded-full mt-2 ml-1 text-sm p-1 border border-transparent uppercase shadow-sm text-secondaryHover']"
                                    @click="addCommentToProject" :disabled="commentForm.text === ''">
                                    <CheckIcon class="h-4 w-4"></CheckIcon>
                                </button>
                            </div>
                        </div>
                        <div>
                            <div class="my-6" v-for="comment in sortedComments"
                                 @mouseover="commentHovered = comment.id"
                                 @mouseout="commentHovered = null">
                                <div class="flex justify-between">
                                    <div class="flex items-center">
                                        <img :data-tooltip-target="comment.user"
                                             :src="comment.user.profile_photo_url" :alt="comment.user.name"
                                             class="rounded-full h-7 w-7 object-cover"/>
                                        <UserTooltip :user="comment.user"/>
                                        <div class="ml-2 text-secondary"
                                             :class="commentHovered === comment.id ? 'text-primary':'text-secondary'">
                                            {{ comment.created_at }}
                                        </div>
                                    </div>
                                    <button v-show="commentHovered === comment.id" type="button"
                                            @click="deleteCommentFromProject(comment)">
                                        <span class="sr-only">Kommentar von Projekt entfernen</span>
                                        <XCircleIcon class="ml-2 h-7 w-7 hover:text-error"/>
                                    </button>
                                </div>
                                <div class="mt-2 mr-14 subpixel-antialiased text-primary font-semibold">
                                    {{ comment.text }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-span-1">
                        <div class="flex w-full items-center mb-8">
                            <h3 class="text-2xl leading-6 font-bold font-lexend text-gray-900"> Dokumente </h3>
                        </div>
                        <div
                            v-if="this.$page.props.can.create_and_edit_projects || this.$page.props.is_admin || this.$page.props.can.admin_projects || projectAdminIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id)">
                            <input
                                @change="uploadChosenDocuments"
                                class="hidden"
                                ref="project_files"
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

                        <!-- Confirm File Delete Modal -->
                        <jet-dialog-modal :show="deletingFile" @close="closeConfirmDeleteModal">
                            <template #content>
                                <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4" />
                                <div class="mx-4">
                                    <div class="font-black font-lexend text-primary text-3xl my-2">
                                        Datei löschen
                                    </div>
                                    <XIcon @click="closeConfirmDeleteModal" class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer" aria-hidden="true" />
                                    <div class="text-error subpixel-antialiased">
                                        Bist du sicher, dass du "{{project_file.name}}" aus dem System löschen möchtest?
                                    </div>
                                    <div class="flex justify-between mt-6">
                                        <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                                @click="removeFile(project_file)">
                                            Löschen
                                        </button>
                                        <div class="flex my-auto">
                                            <span @click="closeConfirmDeleteModal" class="text-secondary subpixel-antialiased cursor-pointer">Nein, doch nicht</span>
                                        </div>
                                    </div>
                                </div>

                                <!--
                                <p class="my-auto text-md">Datei "{{ this.project_file.name }}" wirklich
                                    löschen?</p>
                                <button class="mt-4 inline-flex items-center px-12 py-3 border
                            text-base font-bold uppercase shadow-sm font-black font-lexend"
                                        @click="closeConfirmDeleteModal">
                                    Abbrechen
                                </button>
                                <button class="ml-4 bg-error focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                        @click="removeFile(project_file)">
                                    Löschen
                                </button>
                                -->
                            </template>
                        </jet-dialog-modal>

                        <div class="space-y-1"
                             v-if="this.$page.props.is_admin || this.$page.props.can.admin_rooms || this.is_room_admin || this.$page.props.can.view_projects">
                            <div v-for="project_file in project.project_files"
                                 class="cursor-pointer group flex items-center">
                                <div :data-tooltip-target="project_file.name" class="flex truncate">
                                    <DocumentTextIcon class="h-5 w-5 flex-shrink-0" aria-hidden="true"/>
                                    <p @click="downloadFile(project_file)" class="ml-2 truncate">
                                        {{ project_file.name }}</p>

                                    <XCircleIcon
                                        v-if="this.$page.props.can.create_and_edit_projects || this.$page.props.is_admin || this.$page.props.can.admin_projects || projectAdminIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id)"
                                        @click="openConfirmDeleteModal(project_file)"
                                        class="ml-2 my-auto hidden group-hover:block h-5 w-5 flex-shrink-0 text-error"
                                        aria-hidden="true"/>
                                </div>
                                <div :id="project_file.name" role="tooltip"
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
        </div>
        <!-- Projekt bearbeiten Modal-->
        <jet-dialog-modal :show="editingProject" @close="closeEditProjectModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_project_edit.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                        Basisdaten bearbeiten
                    </div>
                    <XIcon @click="closeEditProjectModal"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="mt-12">
                        <div class="flex">
                            <div class="relative flex w-full mr-4">
                                <input id="first_name" v-model="form.name" type="text"
                                       class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-xl font-bold text-primary placeholder-secondary placeholder-transparent"
                                       placeholder="placeholder"/>
                                <label for="first_name"
                                       class="absolute left-0 -top-4 text-gray-600 -top-6 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Projektname</label>
                            </div>
                        </div>
                        <div class="mt-8 mr-4">
                                            <textarea
                                                placeholder="Kurzbeschreibung"
                                                v-model="form.description" rows="4"
                                                class="focus:border-primary placeholder-secondary border-2 w-full font-semibold border border-gray-300 "/>
                        </div>
                        <div v-on:click="showDetails = !showDetails">
                            <h2 class="text-sm flex text-primary font-semibold cursor-pointer mt-4 ">
                                Weitere Angaben
                                <ChevronUpIcon v-if="showDetails"
                                               class=" ml-1 mr-3 flex-shrink-0 mt-1 h-4 w-4"></ChevronUpIcon>
                                <ChevronDownIcon v-else class=" ml-1 mr-3 flex-shrink-0 mt-1 h-4 w-4"></ChevronDownIcon>
                            </h2>
                        </div>
                        <div v-if="showDetails" class="mt-6 grid grid-cols-1 gap-y-2 gap-x-2 sm:grid-cols-6">
                            <div class="sm:col-span-3">
                                <div>
                                    <input type="text" v-model="form.cost_center" placeholder="Kostenträger eintragen"
                                           class="text-primary h-10 focus:outline-none focus:ring-0 focus:border-secondary focus:border-1  border-gray-300 w-full text-sm "/>
                                </div>
                            </div>
                            <Listbox as="div" class="sm:col-span-3" v-model="selectedParticipantNumber">
                                <div class="relative">
                                    <ListboxButton
                                        class="bg-white relative  focus:outline-none focus:ring-0 focus:border-secondary focus:border-1  w-full border font-semibold shadow-sm pl-3 pr-10 py-2 text-left cursor-default focus:ring-primary focus:border-primary sm:text-sm">
                                        <span class="block truncate">{{ selectedParticipantNumber }}</span>
                                        <span v-if="selectedParticipantNumber === ''" class="block truncate">Anzahl Teilnehmer*innen</span>
                                        <span
                                            class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                     <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                                    </span>
                                    </ListboxButton>

                                    <transition leave-active-class="transition ease-in duration-100"
                                                leave-from-class="opacity-100" leave-to-class="opacity-0">
                                        <ListboxOptions
                                            class="absolute z-10 mt-1 w-full bg-primary shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                                            <ListboxOption as="template"
                                                           v-for="participantNumber in number_of_participants"
                                                           :key="participantNumber.number"
                                                           :value="participantNumber.number"
                                                           v-slot="{ active, selected }">
                                                <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                            <span
                                                :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                {{ participantNumber.number }}
                                            </span>
                                                    <span v-if="selected"
                                                          :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                      <CheckIcon class="h-5 w-5 flex text-success" aria-hidden="true"/>
                                                </span>
                                                </li>
                                            </ListboxOption>
                                        </ListboxOptions>
                                    </transition>
                                </div>
                            </Listbox>
                            <Listbox as="div" class="sm:col-span-3" v-model="selectedGenre">
                                <div class="relative">
                                    <ListboxButton
                                        class="bg-white relative  focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border border-gray-300 font-semibold shadow-sm pl-3 pr-10 py-2 text-left cursor-default focus:ring-primary focus:border-primary sm:text-sm">
                                        <span class="block truncate items-center">
                                            <span>{{ selectedGenre.name }}</span>
                                        </span>
                                        <span v-if="selectedGenre.name === ''"
                                              class="block truncate">Genre wählen</span>
                                        <span
                                            class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                     <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                                    </span>
                                    </ListboxButton>

                                    <transition leave-active-class="transition ease-in duration-100"
                                                leave-from-class="opacity-100" leave-to-class="opacity-0">
                                        <ListboxOptions
                                            class="absolute z-10 mt-1 w-full bg-primary shadow-lg max-h-32 rounded-md text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                            <ListboxOption as="template" class="max-h-8"
                                                           v-for="genre in genres"
                                                           :key="genre.name"
                                                           :value="genre"
                                                           v-slot="{ active, selected }">
                                                <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                    <span
                                                        :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                        {{ genre.name }}
                                                    </span>
                                                    <span
                                                        :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                      <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                                </li>
                                            </ListboxOption>
                                        </ListboxOptions>
                                    </transition>
                                </div>
                            </Listbox>
                            <Listbox as="div" class="sm:col-span-3" v-model="selectedSector">
                                <div class="relative">
                                    <ListboxButton
                                        class="bg-white relative  focus:outline-none focus:ring-0 focus:border-secondary focus:border-1  border-gray-300 w-full border font-semibold shadow-sm pl-3 pr-10 py-2 text-left cursor-default focus:ring-primary focus:border-primary sm:text-sm">
                                        <span class="block truncate items-center">
                                            <span>{{ selectedSector.name }}</span>
                                        </span>
                                        <span v-if="selectedSector.name === ''"
                                              class="block truncate items-center">
                                            <span>Bereich wählen</span>
                                        </span>
                                        <span
                                            class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                            <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                                        </span>
                                    </ListboxButton>

                                    <transition leave-active-class="transition ease-in duration-100"
                                                leave-from-class="opacity-100" leave-to-class="opacity-0">
                                        <ListboxOptions
                                            class="absolute z-10 mt-1 w-full bg-primary shadow-lg max-h-32 rounded-md text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                            <ListboxOption as="template" class="max-h-8"
                                                           v-for="sector in sectors"
                                                           :key="sector.name"
                                                           :value="sector"
                                                           v-slot="{ active, selected }">
                                                <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                    <span
                                                        :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                        {{ sector.name }}
                                                    </span>
                                                    <span
                                                        :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                      <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                                </li>
                                            </ListboxOption>
                                        </ListboxOptions>
                                    </transition>
                                </div>
                            </Listbox>
                            <Listbox as="div" class="sm:col-span-3" v-model="selectedCategory">
                                <div class="relative">
                                    <ListboxButton
                                        class="bg-white relative focus:outline-none focus:ring-0 focus:border-secondary focus:border-1  border-gray-300 w-full border font-semibold shadow-sm pl-3 pr-10 py-2 text-left cursor-default focus:ring-primary focus:border-primary sm:text-sm">
                                        <span class="block truncate items-center flex">
                                            <span>{{ selectedCategory.name }}</span>
                                        </span>
                                        <span v-if="selectedCategory.name === ''"
                                              class="block truncate">Kategorie wählen</span>
                                        <span
                                            class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                     <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                                    </span>
                                    </ListboxButton>

                                    <transition leave-active-class="transition ease-in duration-100"
                                                leave-from-class="opacity-100" leave-to-class="opacity-0">
                                        <ListboxOptions
                                            class="absolute z-10 mt-1 w-full bg-primary shadow-lg max-h-32 rounded-md text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                            <ListboxOption as="template" class="max-h-8"
                                                           v-for="category in categories"
                                                           :key="category.name"
                                                           :value="category"
                                                           v-slot="{ active, selected }">
                                                <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                    <span
                                                        :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                        {{ category.name }}
                                                    </span>
                                                    <span
                                                        :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                      <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                                </li>
                                            </ListboxOption>
                                        </ListboxOptions>
                                    </transition>
                                </div>
                            </Listbox>
                        </div>
                        <button
                            :class="[this.form.name === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                            class="mt-8 inline-flex items-center px-20 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                            @click="editProject"
                            :disabled="this.form.name === ''">
                            Speichern
                        </button>
                    </div>

                </div>

            </template>
        </jet-dialog-modal>
        <!-- Checkliste Hinzufügen-->
        <jet-dialog-modal :show="addingChecklist" @close="closeAddChecklistModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_checklist_new.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-3">
                    <div class="font-bold font-lexend text-primary text-3xl my-2">
                        Neue Checkliste
                    </div>
                    <XIcon @click="closeAddChecklistModal"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-secondary tracking-tight leading-6 sub">
                        Lege eine neue Checkliste an. Um Zeit zu sparen kannst du eine Vorlage wählen und diese
                        anschließend anpassen.
                    </div>
                    <div class="flex my-6">
                        <Listbox class="sm:col-span-3" v-model="selectedTemplate">
                            <div class="relative">
                                <ListboxButton
                                    class="bg-white relative  border-0 w-full border border-gray-300 font-semibold mr-40 py-2 text-left cursor-default focus:outline-none focus:ring-0 focus:ring-primary focus:border-primary sm:text-sm">
                                        <span class="block truncate items-center">
                                            <span>{{ selectedTemplate.name }}</span>
                                        </span>
                                    <span v-if="selectedTemplate.name === ''"
                                          class="block truncate">Keine Vorlage</span>
                                    <span
                                        class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                     <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                                    </span>
                                </ListboxButton>

                                <transition leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions
                                        class="absolute z-10 mt-1 w-full bg-primary shadow-lg max-h-32 rounded-md text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                        <ListboxOption as="template" class="max-h-8"
                                                       :key="'keineVorlage'"
                                                       :value="{name:'',id:null}"
                                                       v-slot="{ active, selected }">
                                            <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                    <span
                                                        :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                        Keine Vorlage
                                                    </span>
                                                <span
                                                    :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                      <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                            </li>
                                        </ListboxOption>
                                        <ListboxOption as="template" class="max-h-8"
                                                       v-for="template in checklist_templates"
                                                       :key="template.id"
                                                       :value="template"
                                                       v-slot="{ active, selected }">
                                            <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                    <span
                                                        :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                        {{ template.name }}
                                                    </span>
                                                <span
                                                    :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                      <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </div>
                        </Listbox>
                    </div>
                    <div class="mt-4">
                        <div class="flex mt-8">
                            <div class="relative w-full mr-4" v-if="selectedTemplate.name === ''">
                                <input id="checklistName" v-model="checklistForm.name" type="text"
                                       class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                                       placeholder="placeholder"/>
                                <label for="checklistName"
                                       class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name
                                    der Checkliste</label>
                            </div>
                            <jet-input-error :message="form.error" class="mt-2"/>
                        </div>
                        <div class="flex items-center my-6" v-if="selectedTemplate.name === ''">
                            <Switch @click="checklistForm.private = !checklistForm.private"
                                    :class="[checklistForm.private ?
                                        'bg-success' :
                                        'bg-gray-300',
                                        'relative inline-flex flex-shrink-0 h-3 w-6 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none']">
                                <span aria-hidden="true"
                                      :class="[checklistForm.private ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200']"/>
                            </Switch>
                            <span class="ml-2 text-sm"
                                  :class="checklistForm.private ? 'text-primary' : 'text-secondary'">Privat</span>
                            <div v-if="$page.props.can.show_hints" class="flex mt-1">
                                <SvgCollection svgName="arrowLeft" class="ml-2 mr-1 mt-1"/>
                                <span
                                    class="font-nanum text-secondary tracking-tight ml-1 my-auto tracking-tight text-xl">Private Liste - nur du kannst sie sehen</span>
                            </div>
                        </div>

                        <button :class="[checklistForm.name.length === 0 && !selectedTemplate.id ?
                    'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                                class="mt-4 flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="addChecklist"
                                :disabled="checklistForm.name.length === 0 && !selectedTemplate.id">
                            Anlegen
                        </button>
                    </div>
                </div>
            </template>

        </jet-dialog-modal>
        <!-- Change Project Team Modal -->
        <jet-dialog-modal :show="editingTeam" @close="closeEditProjectTeamModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_project_team.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-3">
                    <div class="font-black font-lexend text-primary text-3xl my-2">
                        Projektteam zuweisen
                    </div>
                    <XIcon @click="closeEditProjectTeamModal"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-secondary tracking-tight leading-6 sub">
                        Tippe den Namen der Nutzer*innen ein, die du zum Team hinzufügen möchtest ein. Einzelne
                        Mitglieder kannst du zum Projektadmin oder zur Projektleitung ernennen.
                    </div>
                    <div class="mt-6 relative">
                        <div class="my-auto w-full">
                            <input id="departmentSearch" v-model="department_and_user_query" type="text"
                                   autocomplete="off"
                                   class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                                   placeholder="placeholder"/>
                            <label for="departmentSearch"
                                   class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name</label>
                        </div>

                        <transition leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0">
                            <div
                                v-if="(department_and_user_search_results.users
                                || department_and_user_search_results.departments)
                                 && department_and_user_query.length > 0"
                                class="absolute z-10 mt-1 w-full max-h-60 bg-primary shadow-lg
                                         text-base ring-1 ring-black ring-opacity-5
                                         overflow-auto focus:outline-none sm:text-sm">
                                <div class="border-gray-200">
                                    <div v-for="(user, index) in department_and_user_search_results.users" :key="index"
                                         class="flex items-center cursor-pointer">
                                        <div class="flex-1 text-sm py-4">
                                            <p @click="addUserToProjectTeamArray(user)"
                                               class="font-bold px-4 flex text-white items-center hover:border-l-4 hover:border-l-success">
                                                <img :src="user.profile_photo_url" :alt="user.name"
                                                     class="rounded-full h-8 w-8 object-cover"/>
                                                <span class="ml-2 truncate">
                                                    {{ user.first_name }} {{ user.last_name }}
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                    <div v-for="(department, index) in department_and_user_search_results.departments"
                                         :key="index"
                                         class="flex items-center cursor-pointer">
                                        <div class="flex-1 text-sm py-4">
                                            <p @click="addDepartmentToProjectTeamArray(department)"
                                               class="font-bold flex items-center px-4 text-white hover:border-l-4 hover:border-l-success">
                                                <TeamIconCollection :iconName="department.svg_name"
                                                                    :alt="department.name"
                                                                    class="rounded-full h-8 w-8 object-cover"/>
                                                <span class="ml-2">
                                                    {{ department.name }}
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </transition>
                    </div>
                    <div class="mt-4">
                        <span v-for="user in assignedUsers"
                              class="flex mt-4 mr-1 rounded-full items-center font-bold text-primary">
                            <div class="flex items-center">
                                <img class="flex h-11 w-11 rounded-full"
                                     :src="user.profile_photo_url"
                                     alt=""/>
                                <span class="flex ml-4">
                                {{ user.first_name }} {{ user.last_name }}
                                    </span>
                            </div>
                            <button type="button" @click="deleteUserFromProjectTeam(user)">
                                <span class="sr-only">User aus Team entfernen</span>
                                <XCircleIcon class="ml-2 h-5 w-5 hover:text-error "/>
                            </button>
                            <div class="flex justify-between items-center ml-16 my-1.5 h-5">
                                <div class="flex items-center justify-start">
                                    <input v-model="user.is_admin"
                                           type="checkbox"
                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                    <p :class="[user.is_admin ? 'text-primary font-black' : 'text-secondary']"
                                       class="ml-4 my-auto text-sm">Projektadmin</p>
                                    <input v-model="user.is_manager"
                                           type="checkbox"
                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none ml-4 h-6 w-6 text-success border-2 border-gray-300"/>
                                    <p :class="[user.is_manager ? 'text-primary font-black' : 'text-secondary']"
                                       class="ml-4 my-auto text-sm">Projektleitung</p>
                                </div>
                            </div>

                        </span>
                        <span v-for="department in assignedDepartments"
                              class="flex mt-4 mr-1 rounded-full items-center font-bold text-primary">
                            <div class="flex items-center">
                                <TeamIconCollection :iconName="department.svg_name" :alt="department.name"
                                                    class="rounded-full h-11 w-11 object-cover"/>
                                                <span class="flex ml-4">
                                                    {{ department.name }}
                                                </span>
                            </div>
                            <button type="button" @click="deleteDepartmentFromProjectTeam(department)">
                                <span class="sr-only">Team aus dem Projekt entfernen</span>
                                <XCircleIcon class="ml-2 h-5 w-5 hover:text-error "/>
                            </button>

                        </span>
                    </div>
                    <button @click="editProjectTeam"
                            class=" inline-flex mt-8 items-center px-12 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold tracking-wider text-lg  uppercase shadow-sm text-secondaryHover"
                    >Speichern
                    </button>

                </div>

            </template>

        </jet-dialog-modal>
        <!-- Change Checklist Teams Modal -->
        <jet-dialog-modal :show="editingChecklistTeams" @close="closeEditChecklistTeamsModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_checklist_team_assign.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-3">
                    <div class="font-bold font-lexend text-primary text-2xl my-2">
                        Teams zuweisen
                    </div>
                    <XIcon @click="closeEditChecklistTeamsModal"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-secondary tracking-tight leading-6 sub">
                        Tippe den Namen des Teams ein, dem du die Checkliste zuweisen möchtest.
                    </div>
                    <div class="mt-10 relative">
                        <div class="my-auto w-full">
                            <input id="userSearch" v-model="department_query" type="text" autocomplete="off"
                                   class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                                   placeholder="placeholder"/>
                            <label for="userSearch"
                                   class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name</label>
                        </div>

                        <transition leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0">
                            <div v-if="department_search_results.length > 0 && department_query.length > 0"
                                 class="absolute z-10 mt-1 w-full max-h-60 bg-primary shadow-lg
                                         text-base ring-1 ring-black ring-opacity-5
                                         overflow-auto focus:outline-none sm:text-sm">
                                <div class="border-gray-200">
                                    <div v-for="(department, index) in department_search_results" :key="index"
                                         class="flex items-center cursor-pointer">
                                        <div class="flex-1 text-sm py-4">
                                            <p @click="addDepartmentToChecklistTeamArray(department)"
                                               class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                {{ department.name }}
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
                        <span v-for="(team,index) in checklist_assigned_departments"
                              class="flex mt-4 mr-1 rounded-full items-center font-bold text-primary">
                            <div class="flex items-center">
                                <TeamIconCollection :iconName="team.svg_name"
                                                    class="rounded-full h-11 w-11 object-cover"/>
                                <span class="flex ml-4">
                                {{ team.name }}
                                    </span>
                            </div>
                            <button type="button" @click="deleteTeamFromChecklist(team)">
                                <span class="sr-only">Team aus Checkliste entfernen</span>
                                <XCircleIcon class="ml-2 mt-1 h-5 w-5 hover:text-error "/>
                            </button>
                        </span>
                    </div>
                    <button @click="saveChecklistTeams"
                            class=" inline-flex mt-8 items-center px-12 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-lg tracking-wider uppercase shadow-sm text-secondaryHover"
                    >Zuweisen
                    </button>

                </div>

            </template>

        </jet-dialog-modal>
        <!-- Add Task Modal-->
        <jet-dialog-modal :show="addingTask" @close="closeAddTaskModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_task_new.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="font-black font-lexend text-primary tracking-wide text-3xl my-2">
                        Neue Aufgabe
                    </div>
                    <XIcon @click="closeAddTaskModal"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-secondary tracking-tight leading-6 sub">
                        Lege eine neue Aufgabe an. Du kannst sie zudem mit einer Deadline
                        und einem Kommentar versehen.
                    </div>
                    <div class="mt-6">
                        <div class="flex">
                            <div class="mt-1 w-full mr-4">
                                <input type="text" v-model="taskForm.name" placeholder="Aufgabe"
                                       class="placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-2 block w-full border-gray-300"/>
                            </div>
                        </div>
                        <div class="mt-4 mr-4">
                            <div class="mb-1">
                                <label for="datePicker" class="text-secondary subpixel-antialiased">Zu erledigen
                                    bis?</label>
                            </div>
                            <input
                                v-model="taskForm.deadline" id="datePicker"
                                placeholder="Zu erledigen bis?" type="datetime-local"
                                class="placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-2 block w-full border-gray-300 w-full"/>
                        </div>
                        <div class="mt-4 mr-4">
                                            <textarea
                                                placeholder="Kommentar"
                                                v-model="taskForm.description" rows="3"
                                                class="placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-2 block w-full border-gray-300"/>
                        </div>
                        <button
                            :class="[this.taskForm.name === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                            class="mt-8 inline-flex items-center px-20 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-lg tracking-wider uppercase shadow-sm text-secondaryHover"
                            @click="addTask"
                            :disabled="this.taskForm.name === ''">
                            Hinzufügen
                        </button>
                    </div>

                </div>

            </template>
        </jet-dialog-modal>
        <!-- Edit Task Modal-->
        <jet-dialog-modal :show="editingTask" @close="closeEditTaskModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_task_edit.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                        Aufgabe bearbeiten
                    </div>
                    <XIcon @click="closeEditTaskModal"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="mt-12">
                        <div class="flex">
                            <div class="relative flex w-full mr-4">
                                <input id="edit_task_name" v-model="taskToEditForm.name" type="text"
                                       class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-xl font-bold text-primary placeholder-secondary placeholder-transparent"
                                       placeholder="placeholder"/>
                                <label for="edit_task_name"
                                       class="absolute left-0 text-base -top-4 text-gray-600 -top-6 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Aufgabe</label>
                            </div>
                        </div>
                        <div class="mt-4 mr-4">
                            <div class="mb-1">
                                <label for="datePickerEdit" class="text-secondary subpixel-antialiased">Zu erledigen
                                    bis?</label>
                            </div>
                            <input
                                v-model="taskToEditForm.deadline" id="datePickerEdit"
                                placeholder="Zu erledigen bis?" type="datetime-local"
                                class="placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 w-full"/>
                        </div>
                        <div class="mt-4 mr-4">
                                            <textarea
                                                placeholder="Kommentar"
                                                v-model="taskToEditForm.description" rows="3"
                                                class="placeholder-secondary resize-none focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 w-full font-semibold border "/>
                        </div>
                        <button
                            :class="[taskToEditForm.name === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                            class="mt-8 inline-flex items-center px-20 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                            @click="editTask"
                            :disabled="taskToEditForm.name === ''">
                            Hinzufügen
                        </button>
                    </div>

                </div>

            </template>
        </jet-dialog-modal>
        <!-- Delete Checklist Modal -->
        <jet-dialog-modal :show="deletingChecklist" @close="closeDeleteChecklistModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="font-black font-lexend text-primary text-3xl my-2">
                        Checkliste löschen
                    </div>
                    <XIcon @click="closeDeleteChecklistModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-error subpixel-antialiased">
                        Bist du sicher, dass du die Checkliste {{ checklistToDelete.name }} löschen willst?
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="deleteChecklistFromProject()">
                            Löschen
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeDeleteChecklistModal()"
                                  class="text-secondary subpixel-antialiased cursor-pointer">Nein, doch nicht</span>
                        </div>
                    </div>
                </div>

            </template>

        </jet-dialog-modal>
        <!-- Delete Project Modal -->
        <jet-dialog-modal :show="deletingProject" @close="closeDeleteProjectModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="font-black font-lexend text-primary text-3xl my-2">
                        Projekt löschen
                    </div>
                    <XIcon @click="closeDeleteProjectModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-error subpixel-antialiased">
                        Bist du sicher, dass du das Projekt {{ projectToDelete.name }} löschen willst?
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="deleteProject">
                            Löschen
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeDeleteProjectModal()"
                                  class="text-secondary subpixel-antialiased cursor-pointer">Nein, doch nicht</span>
                        </div>
                    </div>
                </div>

            </template>

        </jet-dialog-modal>
        <!-- Project History Modal-->
        <jet-dialog-modal :show="showProjectHistory" @close="closeProjectHistoryModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_project_history.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                        Projektverlauf
                    </div>
                    <XIcon @click="closeProjectHistoryModal"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-secondary subpixel-antialiased">
                        Hier kannst du nachvollziehen, was von wem wann geändert wurde.
                    </div>
                    <div class="flex w-full flex-wrap mt-4 overflow-y-auto max-h-96">
                        <div class="flex w-full my-1" v-for="historyItem in project.project_history">
                            <span class="w-40 text-secondary my-auto text-sm subpixel-antialiased">
                                {{ historyItem.created_at }}:
                            </span>
                            <div class="flex w-full">
                                <img :data-tooltip-target="historyItem.user.id"
                                     :src="historyItem.user.profile_photo_url"
                                     :alt="historyItem.user.name"
                                     class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                                <UserTooltip :user="historyItem.user"/>
                                <div class="text-secondary subpixel-antialiased ml-2 text-sm my-auto">
                                    {{ historyItem.description }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </template>
        </jet-dialog-modal>
        <!-- Checkliste Bearbeiten-->
        <jet-dialog-modal :show="editingChecklist" @close="closeEditChecklistModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_checklist_edit.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-3">
                    <div class="font-bold font-lexend text-primary text-3xl my-2">
                        Checkliste bearbeiten
                    </div>
                    <XIcon @click="closeEditChecklistModal"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-secondary tracking-tight leading-6 sub">
                        Bearbeite deine Checkliste.
                    </div>
                    <div class="mt-4">
                        <div class="flex mt-8">
                            <div class="relative w-full mr-4">
                                <input id="editChecklistName" v-model="editChecklistForm.name" type="text"
                                       class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                                       placeholder="placeholder"/>
                                <label for="editChecklistName"
                                       class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name
                                    der Checkliste</label>
                            </div>
                            <jet-input-error :message="form.error" class="mt-2"/>
                        </div>
                        <div class="flex items-center my-6">
                            <Switch @click="editChecklistForm.private = !editChecklistForm.private"
                                    :class="[editChecklistForm.private ?
                                        'bg-success' :
                                        'bg-gray-300',
                                        'relative inline-flex flex-shrink-0 h-3 w-6 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none']">
                                <span aria-hidden="true"
                                      :class="[editChecklistForm.private ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200']"/>
                            </Switch>
                            <span class="ml-2 text-sm"
                                  :class="editChecklistForm.private ? 'text-primary' : 'text-secondary'">Privat</span>
                            <div class="flex ml-2">
                                <ExclamationIcon class="my-auto h-5 w-5 text-error"></ExclamationIcon>
                                <span
                                    class="text-error subpixel-antialiased text-sm my-auto ml-1">Dies ändert die Sichtbarkeit der Checkliste</span>
                            </div>
                        </div>

                        <button :class="[editChecklistForm.name.length === 0 ?
                    'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                                class="mt-4 flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="editChecklist" :disabled="editChecklistForm.name.length === 0">
                            Speichern
                        </button>
                    </div>
                </div>
            </template>

        </jet-dialog-modal>
    </app-layout>
</template>

<script>

import {Link, useForm} from "@inertiajs/inertia-vue3";
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
    Switch
} from "@headlessui/vue";
import {
    PencilAltIcon,
    TrashIcon,
    XIcon,
    DuplicateIcon,
    DocumentTextIcon,
    EyeIcon,
    ExclamationIcon
} from "@heroicons/vue/outline";
import {
    CheckIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    DotsVerticalIcon,
    XCircleIcon,
    PlusSmIcon, ChevronRightIcon
} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import JetButton from "@/Jetstream/Button";
import JetDialogModal from "@/Jetstream/DialogModal";
import JetInput from "@/Jetstream/Input";
import JetInputError from "@/Jetstream/InputError";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import Checkbox from "@/Jetstream/Checkbox";
import CategoryIconCollection from "@/Layouts/Components/EventTypeIconCollection";
import draggable from "vuedraggable";
import UserTooltip from "@/Layouts/Components/UserTooltip";
import {Inertia} from "@inertiajs/inertia";
import TeamTooltip from "@/Layouts/Components/TeamTooltip";
import MonthlyCalendar from "@/Layouts/Components/MonthlyCalendar";
import DailyCalendar from "@/Layouts/Components/DailyCalendar";
import VueCal from 'vue-cal'
import 'vue-cal/dist/vuecal.css'

const number_of_participants = [
    {number: '1-10'},
    {number: '10-50'},
    {number: '50-100'},
    {number: '100-500'},
    {number: '>500'}
]


export default {
    name: "ProjectShow",
    props: ['first_start', 'last_end', 'opened_checklists', 'project_users', 'project', 'openTab', 'users', 'categories', 'genres', 'sectors', 'checklist_templates', 'calendarType', 'event_types', 'days_this_month', 'areas', 'month_events', 'events_without_room', 'hours_of_day', 'shown_day_formatted', 'shown_day_local', 'isMemberOfADepartment', 'requested_start_time', 'requested_end_time'],
    components: {
        TeamTooltip,
        CategoryIconCollection,
        Checkbox,
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
        DuplicateIcon,
        Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        PlusSmIcon,
        Switch,
        ChevronUpIcon,
        draggable,
        DocumentTextIcon,
        ChevronRightIcon,
        UserTooltip,
        EyeIcon,
        ExclamationIcon,
        Link,
        MonthlyCalendar,
        DailyCalendar,
        VueCal,
    },
    computed: {
        tabs() {
            return [
                {name: 'Ablaufplan', href: '#', current: this.isScheduleTab},
                {name: 'Checklisten', href: '#', current: this.isChecklistTab},
                {name: 'Informationen & Dokumente', href: '#', current: this.isInfoTab},
            ]
        },
        projectMembers() {
            let projectMembers = [];
            this.project.users.forEach((user) => {
                if (this.project.project_managers.findIndex((projectManager) => projectManager.id === user.id) !== -1) {
                    user.is_manager = true;
                } else {
                    projectMembers.push(user);
                }
                if (this.project.project_admins.findIndex((projectAdmin) => projectAdmin.id === user.id) !== -1) {
                    user.is_admin = true;
                }
            })
            return projectMembers;
        },
        sortedComments: function () {
            let commentCopy = this.project.comments.slice();

            function compare(a, b) {
                if (b.created_at === null) {
                    return -1;
                }
                if (a.created_at === null) {
                    return 1;
                }
                if (a.created_at < b.created_at)
                    return 1;
                if (a.created_at > b.created_at)
                    return -1;
                return 0;
            }

            return commentCopy.sort(compare);
        },
        projectAdminIds: function () {
            let adminIdArray = [];
            this.project.project_admins.forEach(admin => {
                    adminIdArray.push(admin.id)
                }
            )
            return adminIdArray;
        },
        projectManagerIds: function () {
            let managerIdArray = [];
            this.project.project_managers.forEach(manager => {
                    managerIdArray.push(manager.id)
                }
            )
            return managerIdArray;
        }
    },
    data() {
        return {
            deletingFile: false,
            project_file: null,
            uploadDocumentFeedback: "",
            editingProject: false,
            addingTask: false,
            dragging: false,
            selectedParticipantNumber: this.project.number_of_participants ? this.project.number_of_participants : '',
            addingChecklist: false,
            isScheduleTab: this.openTab ? this.openTab === 'calendar' : false,
            isChecklistTab: this.openTab ? this.openTab === 'checklist' : false,
            isInfoTab: this.openTab ? this.openTab === 'info' : false,
            editingTeam: false,
            editingChecklistTeams: false,
            department_query: "",
            department_and_user_query: "",
            department_search_results: [],
            department_and_user_search_results: [],
            checklist_assigned_departments: [],
            selectedCategory: this.project.category ? this.project.category : {name: ''},
            selectedSector: this.project.sector ? this.project.sector : {name: ''},
            selectedGenre: this.project.genre ? this.project.genre : {name: ''},
            selectedTemplate: {name: '', id: null},
            showDetails: false,
            checklistToEdit: null,
            editingChecklist: false,
            assignedUsers: this.project.users ? this.project.users : [],
            assignedDepartments: this.project.departments ? this.project.departments : [],
            deletingChecklist: false,
            checklistToDelete: {},
            editingTask: false,
            showMenu: null,
            showProjectHistory: false,
            commentHovered: null,
            allDoneTasks: [],
            projectToDelete: {},
            deletingProject: false,
            form: useForm({
                name: this.project.name,
                description: this.project.description,
                cost_center: this.project.cost_center,
                number_of_participants: this.project.number_of_participants,
                assigned_user_ids: {},
                assigned_departments: [],
                sector_id: this.project.sector_id,
                category_id: this.project.category_id,
                genre_id: this.project.genre_id,

            }),
            checklistForm: useForm({
                name: "",
                project_id: this.project.id,
                tasks: [],
                assigned_department_ids: [],
                private: false,
                template_id: null,
                user_id: null
            }),
            editChecklistForm: useForm({
                id: null,
                name: "",
                private: false,
                user_id: null,
                assigned_department_ids: null,
            }),
            taskForm: useForm({
                name: "",
                description: "",
                deadline: null,
                checklist_id: null,
            }),
            taskToEditForm: useForm({
                id: '',
                name: "",
                description: "",
                deadline: null,
            }),
            commentForm: useForm({
                text: "",
                user_id: this.$page.props.user.id,
                project_id: this.project.id
            }),
            documentForm: useForm({
                file: null
            }),
            duplicateForm: useForm({
                name: "",
                project_id: this.project.id,
                tasks: [],
                assigned_department_ids: [],
                user_id: null
            }),
            templateForm: useForm({
                checklist_id: null,
                user_id: this.$page.props.user.id,
            }),
            doneTaskForm: useForm({
                done: false,
                user_id: this.$page.props.user.id
            }),
            events: [],
        }
    },
    methods: {
        changeChecklistStatus(checklist) {

            if (!this.opened_checklists.includes(checklist.id)) {
                const openedChecklists = this.opened_checklists;

                openedChecklists.push(checklist.id)

                this.$inertia.patch(`/users/${this.$page.props.user.id}/checklists`, {"opened_checklists": openedChecklists});
            } else {
                const filteredList = this.opened_checklists.filter(function (value) {
                    return value !== checklist.id;
                })
                this.$inertia.patch(`/users/${this.$page.props.user.id}/checklists`, {"opened_checklists": filteredList});
            }
        },
        selectNewFiles() {
            this.$refs.project_files.click();
        },
        updateTaskOrder(tasks) {

            tasks.map((task, index) => {
                task.order = index + 1
            })

            this.$inertia.put('/tasks/order', {
                tasks
            }, {
                preserveState: true,
                preserveScroll: true
            })

        },
        openConfirmDeleteModal(project_file) {
            this.deletingFile = true;
            this.project_file = project_file
        },
        closeConfirmDeleteModal() {
            this.deletingFile = false;
            this.project_file = null
        },
        removeFile(project_file) {
            this.$inertia.delete(`/project_files/${project_file.id}`, {
                preserveState: true,
            })
            this.closeConfirmDeleteModal();
        },
        downloadFile(project_file) {
            let link = document.createElement('a');
            link.href = route('download_file', {project_file: project_file});
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
                    this.uploadDocumentToProject(file)
                }
            }
        },
        uploadChosenDocuments(event) {
            this.validateTypeAndUpload([...event.target.files])
        },
        uploadDraggedDocuments(event) {
            this.validateTypeAndUpload([...event.dataTransfer.files])
        },
        uploadDocumentToProject(file) {
            this.documentForm.file = file

            this.documentForm.post(`/projects/${this.project.id}/files`, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    this.documentForm.file = null
                }
            })
        },
        setName(newName) {
            this.checklistForm.name = newName;
        },
        openEditProjectTeamModal() {
            this.editingTeam = true;
        },
        closeEditProjectTeamModal() {
            this.editingTeam = false;
        },
        openEditChecklistTeamsModal(checklist) {
            this.checklistToEdit = checklist;
            this.checklist_assigned_departments = checklist.departments;
            this.editingChecklistTeams = true;
        },
        closeEditChecklistTeamsModal() {
            this.editingChecklistTeams = false;
        },
        openAddChecklistModal() {
            this.addingChecklist = true;
        },
        closeAddChecklistModal() {
            this.addingChecklist = false;
            this.checklistForm.name = '';
            this.checklistForm.tasks = [];
            this.checklistForm.private = false;
            this.checklistForm.template_id = null;
            this.checklistForm.user_id = null;
            this.selectedTemplate = {name: '', id: null};
            this.checklist_assigned_departments = [];
            this.checklistForm.assigned_department_ids = [];
        },
        addChecklist() {

            if (this.selectedTemplate.id !== null) {
                this.checklistForm.template_id = this.selectedTemplate.id;
                this.checklistForm.post(route('checklists.store'), {});
                this.checklistForm.template_id = null;
                this.closeAddChecklistModal();
            } else {
                if (this.checklistForm.private === true) {
                    this.checklistForm.user_id = this.$page.props.user.id;
                }
                this.checklistForm.post(route('checklists.store'), {});
                this.closeAddChecklistModal();
            }

        },
        openDeleteProjectModal(project) {
            this.projectToDelete = project;
            this.deletingProject = true;
        },
        closeDeleteProjectModal() {
            this.deletingProject = false;
            this.projectToDelete = null;
        },
        deleteProject() {
            this.nameOfDeletedProject = this.projectToDelete.name;
            Inertia.delete(`/projects/${this.projectToDelete.id}`);
            this.closeDeleteProjectModal();
        },
        openEditProjectModal() {
            this.editingProject = true;
        },
        closeEditProjectModal() {
            this.editingProject = false;
            this.form.assigned_departments = [];
            this.form.assigned_user_ids = {};
        },
        editProject() {
            this.form.number_of_participants = this.selectedParticipantNumber;
            this.form.category_id = this.selectedCategory.id;
            this.form.sector_id = this.selectedSector.id;
            this.form.genre_id = this.selectedGenre.id;
            this.assignedUsers.forEach(user => {
                this.form.assigned_user_ids[user.id] = {is_admin: user.is_admin, is_manager: user.is_manager};
            })
            this.assignedDepartments.forEach(department => {
                this.form.assigned_departments.push(department);
            })
            this.form.patch(route('projects.update', {project: this.project.id}));
            this.closeEditProjectModal();
        },
        changeTab(selectedTab) {
            this.isScheduleTab = false;
            this.isChecklistTab = false;
            this.isInfoTab = false;
            if (selectedTab.name === 'Ablaufplan') {
                this.isScheduleTab = true;
            } else if (selectedTab.name === 'Checklisten') {
                this.isChecklistTab = true;
            } else {
                this.isInfoTab = true;
            }
        },
        duplicateProject(project) {
            this.$inertia.post(`/projects/${project.id}/duplicate`);
        },
        deleteUserFromProjectTeam(user) {
            if (this.assignedUsers.includes(user)) {
                this.assignedUsers.splice(this.assignedUsers.indexOf(user), 1);
            }
        },
        deleteDepartmentFromProjectTeam(department) {
            this.assignedDepartments.splice(this.assignedDepartments.indexOf(department), 1);
        },
        addDepartmentToChecklistTeamArray(department) {
            let assignedIDs = [];
            this.checklist_assigned_departments.forEach((assignedDepartment) => {
                if (!assignedIDs.includes(assignedDepartment.id)) {
                    assignedIDs.push(assignedDepartment.id);
                }
            })
            if (!assignedIDs.includes(department.id)) {
                this.checklist_assigned_departments.push(department);
                this.department_query = ""
            } else {
                this.department_query = "";
            }
        },
        saveChecklistTeams() {
            let assignedIDs = [];
            this.assignedDepartments.forEach((assignedDepartment) => {
                assignedIDs.push(assignedDepartment.id);
            })
            this.checklist_assigned_departments.forEach((department) => {
                this.checklistForm.assigned_department_ids.push(department.id);

                if (!assignedIDs.includes(department.id)) {
                    this.assignedDepartments.push(department);
                }
            })
            this.checklistForm.name = this.checklistToEdit.name;

            this.checklistForm.patch((route('checklists.update', {checklist: this.checklistToEdit.id})));
            this.editProject();
            this.closeEditChecklistTeamsModal();
            this.closeAddChecklistModal();
        },
        addUserToProjectTeamArray(userToAdd) {
            for (let assignedUser of this.assignedUsers) {
                if (userToAdd.id === assignedUser.id) {
                    this.department_and_user_query = ""
                    return;
                }
            }

            this.assignedUsers.push(userToAdd);
            this.department_and_user_query = ""
        },
        addDepartmentToProjectTeamArray(departmentToAdd) {
            if (this.assignedDepartments !== []) {
                for (let assignedDepartment of this.assignedDepartments) {
                    if (departmentToAdd.id === assignedDepartment.id) {
                        this.department_and_user_query = ""
                        return;
                    }
                }
            } else {
                this.assignedDepartments = [departmentToAdd];
            }
            this.department_and_user_query = ""
            this.assignedDepartments.push(departmentToAdd);

        },
        editProjectTeam() {
            this.form.assigned_user_ids = {};
            this.assignedUsers.forEach(user => {
                this.form.assigned_user_ids[user.id] = {is_admin: user.is_admin, is_manager: user.is_manager};
            })

            this.assignedDepartments.forEach(department => {
                this.form.assigned_departments.push(department);
            })
            this.form.patch(route('projects.update', {project: this.project.id}));
            this.closeEditProjectTeamModal();
        },
        deleteTeamFromChecklist(team) {
            this.checklist_assigned_departments.splice(this.checklist_assigned_departments.indexOf(team), 1)
        },
        openAddTaskModal(checklist) {
            this.taskForm.checklist_id = checklist.id;
            this.addingTask = true;
        },
        closeAddTaskModal() {
            this.taskForm.checklist_id = null;
            this.taskForm.name = "";
            this.taskForm.description = "";
            this.taskForm.deadline = null;
            this.addingTask = false;
        },
        addTask() {
            this.taskForm.post(route('tasks.store'), {preserveState: true, preserveScroll: true});
            this.closeAddTaskModal();
        },
        editTask() {
            this.taskToEditForm.patch(route('tasks.update', {task: this.taskToEditForm.id}));
            this.closeEditTaskModal();
        },
        openEditTaskModal(task) {
            this.taskToEditForm.id = task.id;
            this.taskToEditForm.name = task.name;
            this.taskToEditForm.deadline = task.deadline_dt_local;
            this.taskToEditForm.description = task.description;
            this.editingTask = true;
        },
        closeEditTaskModal() {
            this.editingTask = false;
            this.taskToEditForm.id = null;
            this.taskToEditForm.name = "";
            this.taskToEditForm.deadline = null;
            this.taskToEditForm.description = "";

        },
        addCommentToProject() {
            this.commentForm.post(route('comments.store'), {});
            this.commentForm.text = "";
        },
        checkAllTasks(checklist) {
            checklist.tasks.forEach((task) => {
                task.done = true;
                this.updateTaskStatus(task)
            })
        },
        uncheckAllTasks(checklist) {
            checklist.tasks.forEach((task) => {
                task.done = false;
                this.updateTaskStatus(task)
            })
        },
        allTasksChecked(checklist) {
            let checked = true;
            checklist.tasks.forEach((task) => {
                if (task.done === false) {
                    checked = false
                }
            })
            return checked
        },
        deleteTask(task) {
            this.$inertia.delete(`/tasks/${task.id}`);
        },
        deleteCommentFromProject(comment) {
            this.$inertia.delete(`/comments/${comment.id}`);
        },
        duplicateChecklist(checklist) {
            let departmentIds = [];
            this.duplicateForm.name = checklist.name + " (Kopie)";
            this.duplicateForm.tasks = checklist.tasks;
            if (this.project.private_checklists.findIndex((privateChecklist) => privateChecklist.id === checklist.id) !== -1) {
                this.duplicateForm.user_id = this.$page.props.user.id;
            } else {
                checklist.departments.forEach((department) => {
                    departmentIds.push(department.id);
                })
                this.duplicateForm.assigned_department_ids = departmentIds
            }
            this.duplicateForm.post(route('checklists.store'), {})
            this.duplicateForm.name = "";
            this.duplicateForm.tasks = [];
            this.duplicateForm.departments = [];
            this.duplicateForm.user_id = null;
        },
        openDeleteChecklistModal(checklistToDelete) {
            this.checklistToDelete = checklistToDelete;
            this.deletingChecklist = true;
        },
        closeDeleteChecklistModal() {
            this.deletingChecklist = false;
            this.checklistToDelete = {};
        },
        deleteChecklistFromProject() {
            if (this.project.public_checklists.findIndex((publicChecklist) => publicChecklist.id === this.checklistToDelete.id) !== -1) {
                this.project.public_checklists.splice(this.project.public_checklists.indexOf(this.checklistToDelete), 1);
                this.$inertia.delete(`/checklists/${this.checklistToDelete.id}`);
                this.closeDeleteChecklistModal();
                return;
            }
            if (this.project.private_checklists.findIndex((privateChecklist) => privateChecklist.id === this.checklistToDelete.id) !== -1) {
                this.project.private_checklists.splice(this.project.private_checklists.indexOf(this.checklistToDelete), 1);
                this.$inertia.delete(`/checklists/${this.checklistToDelete.id}`);
                this.closeDeleteChecklistModal();
            }
        },
        openProjectHistoryModal() {
            this.showProjectHistory = true;
        },
        closeProjectHistoryModal() {
            this.showProjectHistory = false;
        },
        openEditChecklistModal(checklist) {
            this.editChecklistForm.id = checklist.id;
            this.editChecklistForm.name = checklist.name;
            this.editChecklistForm.private = !checklist.departments;
            if (checklist.departments) {
                this.editChecklistForm.assigned_department_ids = [];
                checklist.departments.forEach((department) => {
                    this.editChecklistForm.assigned_department_ids.push(department.id);
                })
            }
            this.editingChecklist = true;
        },
        closeEditChecklistModal() {
            this.editingChecklist = false;
            this.editChecklistForm.id = null;
            this.editChecklistForm.name = "";
            this.editChecklistForm.private = false;
            this.editChecklistForm.assigned_department_ids = null;
            this.editChecklistForm.user_id = null;
        },
        editChecklist() {
            if (this.editChecklistForm.private) {
                this.editChecklistForm.user_id = this.$page.props.user.id;
                this.editChecklistForm.assigned_department_ids = null;
            } else {
                this.editChecklistForm.user_id = null;

            }
            this.editChecklistForm.patch(route('checklists.update', {checklist: this.editChecklistForm.id}));
            this.closeEditChecklistModal();
        },
        createTemplateFromChecklist(checklist) {
            this.templateForm.checklist_id = checklist.id;
            this.templateForm.post(route('checklist_templates.store'));
        },
        updateTaskStatus(task) {
            this.doneTaskForm.done = task.done;
            if (this.doneTaskForm.done === false) {
                task.done_by_user = null;
                task.done_at = null;
                task.done_at_dt_local = null;
            }
            this.doneTaskForm.patch(route('tasks.update', {task: task.id}), {
                preserveScroll: true,
            });
        },

        async fetchEvents({view, startDate, endDate}) {
            await axios.get(`/projects/${this.project.id}/events`, {
                params: {
                    start: startDate,
                    end: endDate,
                }
            }).then(response => {
                console.log(response.data.data)
                this.events = response.data.data
            });
        },

        async updateEvent(event) {
                console.log(event)
            const data = {
            };
                console.log(data)

            await axios.put(`/events/${event.originalEvent?.id}`, {
                roomId: event.event.roomId,
                start: event.event.start,
                end: event.event.end,
                title: event.event.title,
                description: event.event.description,
            }).then(response => {
                console.log(response)
                // success message
            });
        },
    },
    watch: {
        department_query: {
            handler() {
                if (this.department_query.length > 0) {
                    axios.get('/departments/search', {
                        params: {query: this.department_query}
                    }).then(response => {
                        this.department_search_results = response.data
                    })
                }
            },
            deep: true
        },
        department_and_user_query: {
            handler() {
                if (this.department_and_user_query.length > 0) {
                    axios.get('/projects/users_departments/search', {
                        params: {query: this.department_and_user_query}
                    }).then(response => {
                        this.department_and_user_search_results = response.data
                    })
                }
            },
            deep: true
        }
    },
    setup() {
        return {
            number_of_participants,
        }
    }
}

</script>

<style>
.vuecal__event {
    font-size: 1em;
    margin-top: 3px;
    border: solid rgba(196, 42, 15, 0.9);
    border-width: 0px 0px 0px 3px;
}
.vuecal__event.leisure {
    border: solid rgba(15, 164, 196, 0.9);
    border-width: 0px 0px 0px 3px;
}
</style>
