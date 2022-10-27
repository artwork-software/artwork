<template>
    <app-layout>
        <div class="py-4">
            <div class="max-w-screen-lg mb-40 my-12 flex flex-row ml-14 mr-40">
                <div class="flex flex-1 flex-wrap">
                    <div class="w-full flex my-auto justify-between">
                        <div class="flex">
                            <Listbox as="div" class="flex" v-model="projectFilter">
                                <ListboxButton
                                    class="bg-white w-full relative py-2 cursor-pointer focus:outline-none sm:text-sm">
                                    <div class="flex items-center my-auto">
                                        <p class="items-center flex mr-2 headline1">
                                            {{ projectFilter.name }}</p>
                                        <span
                                            class="inset-y-0 flex items-center pr-2 pointer-events-none">
                                            <ChevronDownIcon class="h-5 w-5" aria-hidden="true"/>
                                         </span>
                                    </div>
                                </ListboxButton>
                                <transition leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions
                                        class="absolute w-56 z-10 mt-12 bg-primary shadow-lg max-h-64 p-3 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                                        <ListboxOption as="template" class="max-h-8"
                                                       v-for="filter in projectFilters"
                                                       :key="filter.name"
                                                       :value="filter"
                                                       v-slot="{ active, selected }">
                                            <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 px-3 text-sm subpixel-antialiased']">
                                                    <span
                                                        :class="[selected ? 'xsWhiteBold' : 'xsLight', 'block truncate']">
                                                        {{ filter.name }}
                                                    </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </Listbox>
                            <div class="flex"
                                 v-if="this.$page.props.can.create_and_edit_projects || this.$page.props.is_admin">
                                <AddButton @click="openAddProjectModal" text="Neues Projekt" mode="page"/>
                                <div v-if="$page.props.can.show_hints" class="flex mt-1">
                                    <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                                    <span
                                        class="hind ml-1 my-auto">Lege neue Projekte an</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <div v-if="!showSearchbar" @click="this.showSearchbar = !this.showSearchbar"
                                 class="cursor-pointer inset-y-0 mr-3">
                                <SearchIcon class="h-5 w-5" aria-hidden="true"/>
                            </div>
                            <div v-else class="flex items-center w-full w-64 mr-2">
                                <inputComponent v-model="this.project_query" placeholder="Suche nach Projekten" />
                                <XIcon class="ml-2 cursor-pointer h-5 w-5" @click="closeSearchbar()"/>
                            </div>
                        </div>
                    </div>
                    <div v-if="projects.length > 0 && project_query < 1" v-for="(project,index) in this.currentProjects"
                         :key="project.id"
                         class="mt-5 border-b-2 border-gray-200 w-full">
                        <div
                            class="py-4 flex">
                            <div class="flex w-full">
                                <div class="mr-6">
                                    <Link v-if="this.$page.props.can.view_projects" :href="getEditHref(project)"
                                          class="flex w-full my-auto">
                                        <p class="headline2">
                                            {{ project.name }}</p>
                                    </Link>
                                    <div v-else class="flex w-full my-auto">
                                        <p class="headline2">
                                            {{ project.name }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex w-full justify-end">
                                <div class="my-auto -mr-3" v-for="department in project.departments.slice(0,3)">
                                    <TeamIconCollection :data-tooltip-target="department.name"
                                                        class="h-9 w-9 rounded-full ring-2 ring-white object-cover"
                                                        :iconName="department.svg_name"
                                                        alt=""/>
                                    <TeamTooltip :team="department"/>
                                </div>
                                <div v-if="project.departments.length >= 4" class="my-auto">
                                    <Menu as="div" class="relative">
                                        <div>
                                            <MenuButton class="flex items-center rounded-full focus:outline-none">
                                                <ChevronDownIcon
                                                    class="ml-1 flex-shrink-0 h-9 w-9 flex my-auto items-center ring-2 ring-white font-semibold rounded-full shadow-sm text-white bg-black"></ChevronDownIcon>
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
                                                <MenuItem v-for="department in project.departments" v-slot="{ active }">
                                                    <div
                                                        :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                        <TeamIconCollection
                                                            class="h-9 w-9 rounded-full"
                                                            :iconName="department.svg_name"
                                                            alt=""/>
                                                        <span class="ml-4">
                                                                {{ department.name }}
                                                            </span>
                                                    </div>
                                                </MenuItem>
                                            </MenuItems>
                                        </transition>
                                    </Menu>
                                </div>
                            </div>
                            <div class="flex w-full justify-end">
                                <div class="flex mr-6">
                                    <div class="my-auto -mr-3" v-for="user in project.users.slice(0,3)">
                                        <img :data-tooltip-target="user.id"
                                             class="h-9 w-9 rounded-full ring-2 ring-white object-cover"
                                             :src="user.profile_photo_url"
                                             alt=""/>
                                        <UserTooltip :user="user"/>
                                    </div>
                                    <div v-if="project.users.length >= 4" class="my-auto">
                                        <Menu as="div" class="relative">
                                            <div>
                                                <MenuButton class="flex items-center rounded-full focus:outline-none">
                                                    <ChevronDownIcon
                                                        class="ml-1 flex-shrink-0 h-9 w-9 flex my-auto items-center ring-2 ring-white font-semibold rounded-full shadow-sm text-white bg-black"></ChevronDownIcon>
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
                                                    <MenuItem v-for="user in project.users" v-slot="{ active }">
                                                        <div
                                                            :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                            <img class="h-9 w-9 rounded-full object-cover"
                                                                 :src="user.profile_photo_url"
                                                                 alt=""/>
                                                            <span class="ml-4">
                                                                {{ user.first_name }} {{ user.last_name }}
                                                            </span>
                                                        </div>
                                                    </MenuItem>
                                                </MenuItems>
                                            </transition>
                                        </Menu>
                                    </div>
                                </div>
                                <Menu
                                    v-if="$page.props.permissions.includes('edit projects') || $page.props.is_admin || project.user_can_view_project"
                                    as="div" class="my-auto relative">
                                    <div class="flex">
                                        <MenuButton
                                            class="flex">
                                            <DotsVerticalIcon class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                                              aria-hidden="true"/>
                                        </MenuButton>
                                        <div v-if="$page.props.can.show_hints && index === 0"
                                             class="absolute flex w-40 ml-6">
                                            <div>
                                                <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                                            </div>
                                            <div class="flex">
                                                <span
                                                    class="ml-2 hind mt-1">Bearbeite die Projekte</span>
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
                                            class="origin-top-right absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                            <div class="py-1">
                                                <MenuItem v-slot="{ active }">
                                                    <a :href="getEditHref(project)"
                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                        <PencilAltIcon
                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                            aria-hidden="true"/>
                                                        Bearbeiten
                                                    </a>
                                                </MenuItem>
                                                <MenuItem v-slot="{ active }">
                                                    <a href="#" @click="duplicateProject(project)"
                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                        <DuplicateIcon
                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                            aria-hidden="true"/>
                                                        Duplizieren
                                                    </a>
                                                </MenuItem>
                                                <MenuItem v-slot="{ active }">
                                                    <a href="#" @click="openDeleteProjectModal(project)"
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
                        </div>
                        <div
                            v-if="this.$page.props.can.view_projects || this.$page.props.can.admin_projects || this.$page.props.is_admin"
                            class="mb-12 -mt-2 text-secondary flex items-center">
                            <span class=" xsLight">
                                  zuletzt geändert:
                            </span>
                            <div class="flex items-center" v-if="project.project_history.length !== 0">

                                <img
                                    :data-tooltip-target="project.project_history[project.project_history.length -1].user.id"
                                    :src="project.project_history[project.project_history.length -1].user.profile_photo_url"
                                    :alt="project.project_history[project.project_history.length -1].user.name"
                                    class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                                <UserTooltip
                                    :user="project.project_history[project.project_history.length -1].user"/>
                                <span class="ml-2 xsLight">
                                    {{ project.project_history[project.project_history.length - 1].created_at }}
                                </span>
                                <button class="ml-4 flex items-center cursor-pointer xsLight"
                                        @click="openProjectHistoryModal(project.project_history)">
                                    <ChevronRightIcon
                                        class="-mr-0.5 h-4 w-4  text-primaryText group-hover:text-white"
                                        aria-hidden="true"/>
                                    Verlauf ansehen
                                </button>
                            </div>
                            <div v-else class="ml-2 text-secondary subpixel-antialiased">
                                Noch kein Verlauf verfügbar
                            </div>

                        </div>
                    </div>
                    <div v-else v-for="(project,index) in project_search_results" :key="project.id"
                         class="mt-5 border-b-2 border-gray-200 w-full">
                        <div
                            class="py-5 flex">
                            <div class="flex w-full">
                                <div class="mr-6">
                                    <Link v-if="this.$page.props.can.view_projects" :href="getEditHref(project)"
                                          class="flex w-full my-auto">
                                        <p class="text-2xl font-black font-lexend subpixel-antialiased text-gray-900">
                                            {{ project.name }}</p>
                                    </Link>
                                    <div v-else class="flex w-full my-auto">
                                        <p class="text-2xl font-black font-lexend subpixel-antialiased text-gray-900">
                                            {{ project.name }}</p>
                                    </div>

                                </div>
                            </div>
                            <div class="flex w-full justify-end">
                                <div class="my-auto -mr-3" v-for="department in project.departments.slice(0,3)">
                                    <TeamIconCollection :data-tooltip-target="department.name"
                                                        class="h-9 w-9 rounded-full ring-2 ring-white"
                                                        :iconName="department.svg_name"
                                                        alt=""/>
                                    <TeamTooltip :team="department"/>
                                </div>
                                <div v-if="project.departments.length >= 4" class="my-auto">
                                    <Menu as="div" class="relative">
                                        <div>
                                            <MenuButton class="flex items-center rounded-full focus:outline-none">
                                                <ChevronDownIcon
                                                    class="ml-1 flex-shrink-0 h-9 w-9 flex my-auto items-center ring-2 ring-white font-semibold rounded-full shadow-sm text-white bg-black"></ChevronDownIcon>
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
                                                <MenuItem v-for="department in project.departments" v-slot="{ active }">
                                                    <div
                                                        :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                        <TeamIconCollection
                                                            class="h-9 w-9 rounded-full"
                                                            :iconName="department.svg_name"
                                                            alt=""/>
                                                        <span class="ml-4">
                                                                {{ department.name }}
                                                            </span>
                                                    </div>
                                                </MenuItem>
                                            </MenuItems>
                                        </transition>
                                    </Menu>
                                </div>
                            </div>
                            <div class="flex w-full justify-end">
                                <div class="flex mr-6">
                                    <div class="my-auto -mr-3" v-for="user in project.users.slice(0,3)">
                                        <img :data-tooltip-target="user.id"
                                             class="h-9 w-9 rounded-full ring-2 ring-white"
                                             :src="user.profile_photo_url"
                                             alt=""/>
                                        <UserTooltip :user="user"/>
                                    </div>
                                    <div v-if="project.users.length >= 4" class="my-auto">
                                        <Menu as="div" class="relative">
                                            <div>
                                                <MenuButton class="flex items-center rounded-full focus:outline-none">
                                                    <ChevronDownIcon
                                                        class="ml-1 flex-shrink-0 h-9 w-9 flex my-auto items-center ring-2 ring-white font-semibold rounded-full shadow-sm text-white bg-black"></ChevronDownIcon>
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
                                                    <MenuItem v-for="user in project.users" v-slot="{ active }">
                                                        <div
                                                            :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                            <img class="h-9 w-9 rounded-full"
                                                                 :src="user.profile_photo_url"
                                                                 alt=""/>
                                                            <span class="ml-4">
                                                                {{ user.first_name }} {{ user.last_name }}
                                                            </span>
                                                        </div>
                                                    </MenuItem>
                                                </MenuItems>
                                            </transition>
                                        </Menu>
                                    </div>
                                </div>
                                <Menu
                                    v-if="$page.props.permissions.includes('edit projects') || $page.props.is_admin || project.user_can_view_project"
                                    as="div" class="my-auto relative">
                                    <div class="flex">
                                        <MenuButton
                                            class="flex">
                                            <DotsVerticalIcon class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                                              aria-hidden="true"/>
                                        </MenuButton>
                                        <div v-if="$page.props.can.show_hints && index === 0"
                                             class="absolute flex w-40 ml-6">
                                            <div>
                                                <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                                            </div>
                                            <div class="flex">
                                                <span
                                                    class="font-nanum ml-2 text-secondary tracking-tight tracking-tight text-lg">Bearbeite die Projekte</span>
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
                                            class="origin-top-right absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                            <div class="py-1">
                                                <MenuItem v-slot="{ active }">
                                                    <a :href="getEditHref(project)"
                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                        <PencilAltIcon
                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                            aria-hidden="true"/>
                                                        Bearbeiten
                                                    </a>
                                                </MenuItem>
                                                <MenuItem v-slot="{ active }">
                                                    <a href="#" @click="duplicateProject(project)"
                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                        <DuplicateIcon
                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                            aria-hidden="true"/>
                                                        Duplizieren
                                                    </a>
                                                </MenuItem>
                                                <MenuItem v-slot="{ active }">
                                                    <a href="#" @click="openDeleteProjectModal(project)"
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
                        </div>
                        <div
                            v-if="this.$page.props.can.view_projects || this.$page.props.can.admin_projects || this.$page.props.is_admin"
                            class="mb-12 -mt-2 text-secondary flex items-center">
                            <span class=" text-xs subpixel-antialiased">
                                  zuletzt geändert:
                            </span>
                            <div class="flex items-center" v-if="project.project_history.length !== 0">

                                <img
                                    :data-tooltip-target="project.project_history[project.project_history.length -1].user.id"
                                    :src="project.project_history[project.project_history.length -1].user.profile_photo_url"
                                    :alt="project.project_history[project.project_history.length -1].user.name"
                                    class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                                <UserTooltip
                                    :user="project.project_history[project.project_history.length -1].user"/>
                                <span class="ml-2 text-xs subpixel-antialiased">
                                    {{ project.project_history[project.project_history.length - 1].created_at }}
                                </span>
                                <button class="ml-4 text-xs subpixel-antialiased flex items-center cursor-pointer"
                                        @click="openProjectHistoryModal(project.project_history)">
                                    <ChevronRightIcon
                                        class="-mr-0.5 h-4 w-4  text-primaryText group-hover:text-white"
                                        aria-hidden="true"/>
                                    Verlauf ansehen
                                </button>
                            </div>
                            <div v-else class="ml-2 text-secondary subpixel-antialiased">
                                Noch kein Verlauf verfügbar
                            </div>

                        </div>
                    </div>
                </div>


            </div>
        </div>
        <!-- Projekt erstellen Modal-->
        <jet-dialog-modal :show="addingProject" @close="closeAddProjectModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_project_new.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                        Neues Projekt
                    </div>
                    <XIcon @click="closeAddProjectModal" class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="mt-12">
                        <div class="flex">
                            <div class="relative flex w-full mr-4">
                                <input id="projectName" v-model="form.name" type="text"
                                       class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-xl font-bold text-primary placeholder-secondary placeholder-transparent"
                                       placeholder="placeholder"/>
                                <label for="projectName"
                                       class="absolute left-0 text-base -top-4 text-gray-600 -top-6 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Projektname*</label>
                            </div>
                        </div>
                        <div class="mt-8 mr-4">
                                            <textarea
                                                placeholder="Kurzbeschreibung"
                                                v-model="form.description" rows="4"
                                                class="resize-none placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 border-2 block w-full "/>
                        </div>
                        <div class="mt-6 grid grid-cols-1 gap-y-2 gap-x-2 sm:grid-cols-6">
                            <div class="sm:col-span-3">
                                <div class="">
                                    <input type="text" v-model="form.cost_center" placeholder="Kostenträger eintragen"
                                           class="text-primary h-10 w-full text-sm placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 border-2 block w-full  "/>
                                </div>
                            </div>
                        </div>
                        <div class="w-full items-center text-center">
                            <AddButton
                                :class="[this.form.name === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                                class="mt-8 inline-flex items-center px-20 py-3 border border-transparent text-base font-bold
                             text-xl shadow-sm text-secondaryHover"
                                @click="addProject"
                                :disabled="this.form.name === ''" text="Anlegen" mode="modal"/>
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
        <!-- Success Modal - Delete project -->
        <jet-dialog-modal :show="showSuccessModal" @close="closeSuccessModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_success.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="font-black text-primary font-lexend text-3xl my-2">
                        Projekt gelöscht
                    </div>
                    <XIcon @click="closeSuccessModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-success subpixel-antialiased">
                        Das Projekt {{ nameOfDeletedProject }} wurde gelöscht.
                    </div>
                    <div class="mt-6">
                        <button class="bg-success focus:outline-none my-auto inline-flex items-center px-24 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="closeSuccessModal">
                            <CheckIcon class="h-6 w-12 text-secondaryHover"/>
                        </button>
                    </div>
                </div>

            </template>
        </jet-dialog-modal>
        <!-- Success Modal -->
        <jet-dialog-modal :show="showSuccessModal2" @close="closeSuccessModal2">
            <template #content>
                <img src="/Svgs/Overlays/illu_success.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="font-black text-primary font-lexend text-3xl my-2">
                        Projekt erstellt
                    </div>
                    <XIcon @click="closeSuccessModal2"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-success subpixel-antialiased">
                        Das Projekt wurde erfolgreich angelegt.
                    </div>
                    <div class="mt-6">
                        <button class="bg-success focus:outline-none my-auto inline-flex items-center px-24 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="closeSuccessModal2">
                            <CheckIcon class="h-6 w-12 text-secondaryHover"/>
                        </button>
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
                    <div class="flex w-full flex-wrap mt-4">
                        <div class="flex w-full my-1" v-for="historyItem in projectHistoryToDisplay">
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
    </app-layout>
</template>

<script>

import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    DotsVerticalIcon,
    ChevronDownIcon,
    InformationCircleIcon,
    XIcon,
    PencilAltIcon,
    TrashIcon,
    DuplicateIcon
} from '@heroicons/vue/outline'
import {ChevronUpIcon, PlusSmIcon, CheckIcon, SelectorIcon, XCircleIcon, ChevronRightIcon} from '@heroicons/vue/solid'
import {SearchIcon} from "@heroicons/vue/outline";
import {
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem, MenuItems
} from '@headlessui/vue'
import Button from "@/Jetstream/Button";
import JetButton from "@/Jetstream/Button";
import JetDialogModal from "@/Jetstream/DialogModal";
import JetInput from "@/Jetstream/Input";
import JetInputError from "@/Jetstream/InputError";
import JetSecondaryButton from "@/Jetstream/SecondaryButton";
import Checkbox from "@/Layouts/Components/Checkbox";
import {useForm} from "@inertiajs/inertia-vue3";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import CategoryIconCollection from "@/Layouts/Components/EventTypeIconCollection";
import {Inertia} from "@inertiajs/inertia";
import {Link} from "@inertiajs/inertia-vue3";
import UserTooltip from "@/Layouts/Components/UserTooltip";
import TeamTooltip from "@/Layouts/Components/TeamTooltip";
import AddButton from "@/Layouts/Components/AddButton";
import projects from "@/Pages/Trash/Projects";
import InputComponent from "@/Layouts/Components/InputComponent";

const number_of_participants = [
    {number: '1-10'},
    {number: '10-50'},
    {number: '50-100'},
    {number: '100-500'},
    {number: '>500'}
]

export default defineComponent({
    components: {
        AddButton,
        CategoryIconCollection,
        TeamIconCollection,
        SvgCollection,
        Button,
        AppLayout,
        DotsVerticalIcon,
        PlusSmIcon,
        SearchIcon,
        Listbox,
        ListboxButton,
        ListboxLabel,
        ListboxOption,
        ListboxOptions,
        CheckIcon,
        SelectorIcon,
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
        Checkbox,
        XIcon,
        PencilAltIcon,
        TrashIcon,
        XCircleIcon,
        DuplicateIcon,
        ChevronRightIcon,
        Link,
        UserTooltip,
        TeamTooltip,
        InputComponent
    },
    props: ['projects', 'users', 'categories', 'genres', 'sectors', 'can'],
    computed: {
        currentProjects: function () {
            if (this.projectFilter.name === 'Alle Projekte') {
                return this.projects
            } else {
                const newProjects = this.projects.filter(project => project.curr_user_is_related === true)
                console.log(newProjects);
                return newProjects;
            }
        }
    },
    methods: {
        closeSearchbar() {
            this.showSearchbar = !this.showSearchbar;
            this.project_query = ''
        },
        openAddProjectModal() {
            this.addingProject = true;
        },
        closeAddProjectModal() {
            this.addingProject = false;
            this.form.name = "";
            this.form.description = "";
            this.form.cost_center = "";
            this.form.number_of_participants = "";
            this.selectedParticipantNumber = "";
        },
        addProject() {
            this.form.number_of_participants = this.selectedParticipantNumber;
            this.form.post(route('projects.store'), {})
            this.closeAddProjectModal();
            this.openSuccessModal2();
        },
        getEditHref(project) {
            return route('projects.show', {project: project.id, openTab:'calendar'});
        },
        duplicateProject(project) {
            this.$inertia.post(`/projects/${project.id}/duplicate`);
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
            this.openSuccessModal();

        },
        openSuccessModal() {
            this.showSuccessModal = true;
            setTimeout(() => this.closeSuccessModal(), 2000)
        },
        closeSuccessModal() {
            this.showSuccessModal = false;
            this.nameOfDeletedProject = "";
            this.closeSearchbar()
        },
        openSuccessModal2() {
            this.showSuccessModal2 = true;
            setTimeout(() => this.closeSuccessModal2(), 2000)
        },
        closeSuccessModal2() {
            this.showSuccessModal2 = false;
            this.closeSearchbar()
        },
        openProjectHistoryModal(projectHistory) {
            this.projectHistoryToDisplay = projectHistory;
            this.showProjectHistory = true;
        },
        closeProjectHistoryModal() {
            this.showProjectHistory = false;
            this.projectHistoryToDisplay = [];
        },
        isTeamMember(departments) {
            departments.forEach((department) => {
                department.users.forEach((user) => {
                    if (user.id === this.$page.props.user.id) {
                        console.log("moin");
                        return true;
                    }
                })
            })
            return false;
        },

    },
    watch: {
        project_query: {
            handler() {
                if (this.project_query.length > 0) {
                    axios.get('/projects/search', {
                        params: {query: this.project_query}
                    }).then(response => {
                        if (this.projectFilter.name === 'Alle Projekte') {
                            this.project_search_results = response.data
                        } else {
                            console.log(response.data)
                            this.project_search_results = response.data.filter(project => project.curr_user_is_related === true)
                        }
                    })
                }
            },
            deep: true
        }
    },
    data() {
        return {
            projectFilters: [{'name': 'Alle Projekte'}, {'name': 'Meine Projekte'}],
            projectFilter: {'name': 'Alle Projekte'},
            showSearchbar: false,
            project_query: '',
            project_search_results: [],
            addingProject: false,
            deletingProject: false,
            projectToDelete: null,
            showSuccessModal: false,
            showSuccessModal2: false,
            selectedParticipantNumber: "",
            nameOfDeletedProject: "",
            showProjectHistory: false,
            projectHistoryToDisplay: [],
            form: useForm({
                name: "",
                description: "",
                cost_center: "",
                number_of_participants: "",
                sector_id: null,
                category_id: null,
                genre_id: null,
            }),
        }
    },
    setup() {

        return {
            number_of_participants
        }
    }
})
</script>
