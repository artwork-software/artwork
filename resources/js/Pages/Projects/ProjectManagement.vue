<template>
    <app-layout title="Meine Projekte">
        <div class="py-4">
            <div class="max-w-screen-lg mb-40 my-12 flex flex-row ml-20 mr-40">
                <div class="flex flex-1 flex-wrap">
                    <div class="w-full flex my-auto justify-between">
                        <div class="flex">
                            <h2 class="text-2xl flex">Meine Projekte</h2>
                            <!-- TODO: PERMISSION CHECK -->
                            <button v-if="this.$page.props.user.can.create_projects" @click="openAddProjectModal" type="button"
                                    class="flex my-auto ml-6 items-center border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none">
                                <PlusSmIcon class="h-5 w-5" aria-hidden="true"/>
                            </button>
                            <div v-if="$page.props.can.show_hints" class="flex mt-1">
                                <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                                <span
                                    class="font-nanum text-secondary tracking-tight ml-1 my-auto tracking-tight text-lg">Lege neue Projekte an</span>
                            </div>
                        </div>
                        <div class="flex items-center">

                            <div class="inset-y-0 mr-3 pointer-events-none">
                                <SearchIcon class="h-5 w-5" aria-hidden="true"/>
                            </div>
                        </div>
                    </div>
                    <div v-if="projects.data.length > 0" v-for="(project,index) in projects.data" :key="project.id"
                         class="mt-5 border-b-2 border-gray-200 w-full">
                        <div
                            class="py-5 flex">
                            <div class="flex w-full">
                                <div class="mr-6">
                                    <div class="flex w-full my-auto">
                                        <p class="text-2xl subpixel-antialiased text-gray-900">{{ project.name }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex w-full justify-end">
                                <div class="my-auto -mr-3" v-for="department in project.departments.slice(0,3)">
                                    <TeamIconCollection :data-tooltip-target="department.name" class="h-9 w-9 rounded-full ring-2 ring-white"
                                                        :iconName="department.svg_name"
                                                        alt=""/>
                                    <div :id="department.name" role="tooltip" class="inline-block absolute invisible z-10 py-2 px-3 text-sm font-medium text-secondary bg-primary rounded-lg shadow-sm opacity-0 transition-opacity duration-300 tooltip">
                                        {{department.name}}
                                        <div class="tooltip-arrow" data-popper-arrow></div>
                                    </div>
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

                                <Menu v-if="$page.props.permissions.includes('edit projects') || $page.props.is_admin || isTeamMember(project.departments)" as="div" class="my-auto relative">
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
                        <div class="mb-12 text-secondary flex items-center">
                            <span class="subpixel-antialiased">
                                  zuletzt geändert:
                            </span>
                            <div class="flex items-center" v-if="project.project_history.length !== 0">
                                <img
                                    :data-tooltip-target="project.project_history[project.project_history.length -1].user.id"
                                    :src="project.project_history[project.project_history.length -1].user.profile_photo_url"
                                    :alt="project.project_history[project.project_history.length -1].user.name"
                                    class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                                <UserTooltip :user="project.project_history[project.project_history.length -1].user"/>
                                <span class="ml-2 subpixel-antialiased">
                                    {{ project.project_history[project.project_history.length - 1].created_at }}
                                </span>
                                <button class="ml-4 subpixel-antialiased flex items-center cursor-pointer"
                                        @click="openProjectHistoryModal(project.project_history)">
                                    <ChevronRightIcon
                                        class="-mr-0.5 h-4 w-4 text-primaryText group-hover:text-white"
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
                <img src="/Svgs/Overlays/illu_project_new.svg" class="-ml-6 -mt-8 mb-4" />
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
                                       class="absolute left-0 text-base -top-4 text-gray-600 -top-6 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Projektname</label>
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
                                <div class="">
                                    <input type="text" v-model="form.cost_center" placeholder="Kostenträger eintragen"
                                           class="text-primary h-10 focus:border-primary border-2 w-full text-sm border-gray-300 "/>
                                </div>
                            </div>
                            <Listbox as="div" class="sm:col-span-3" v-model="selectedParticipantNumber">
                                <div class="relative">
                                    <ListboxButton
                                        class="bg-white relative  border-2 w-full border border-gray-300 font-semibold shadow-sm pl-3 pr-10 py-2 text-left cursor-default focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary sm:text-sm">
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
                                        class="bg-white relative  border-2 w-full border border-gray-300 font-semibold shadow-sm pl-3 pr-10 py-2 text-left cursor-default focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary sm:text-sm">
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
                                                           v-for="genre in genres.data"
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
                                        class="bg-white relative  border-2 w-full border border-gray-300 font-semibold shadow-sm pl-3 pr-10 py-2 text-left cursor-default focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary sm:text-sm">
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
                                                           v-for="sector in sectors.data"
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
                                        class="bg-white relative  border-2 w-full border border-gray-300 font-semibold shadow-sm pl-3 pr-10 py-2 text-left cursor-default focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary sm:text-sm">
                                        <span class="block truncate items-center flex">
                                            <span class="">{{ selectedCategory.name }}</span>
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
                                                           v-for="category in categories.data"
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
                            @click="addProject"
                            :disabled="this.form.name === ''">
                            Anlegen
                        </button>
                    </div>

                </div>

            </template>
        </jet-dialog-modal>
        <!-- Delete Project Modal -->
        <jet-dialog-modal :show="deletingProject" @close="closeDeleteProjectModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4" />
                <div class="mx-4">
                    <div class="font-bold text-primary text-2xl my-2">
                        Projekt löschen
                    </div>
                    <XIcon @click="closeDeleteProjectModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-error">
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
                <img src="/Svgs/Overlays/illu_success.svg" class="-ml-6 -mt-8 mb-4" />
                <div class="mx-4">
                    <div class="font-bold text-primary font-lexend text-2xl my-2">
                        Projekt gelöscht
                    </div>
                    <XIcon @click="closeSuccessModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-success">
                        Das Projekt {{ nameOfDeletedProject }} wurde gelöscht.
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
        <!-- Project History Modal-->
        <jet-dialog-modal :show="showProjectHistory" @close="closeProjectHistoryModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_project_history.svg" class="-ml-6 -mt-8 mb-4" />
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
                            <span class="text-secondary my-auto text-sm subpixel-antialiased">
                        {{ historyItem.created_at }}:
                    </span>
                            <img :data-tooltip-target="historyItem.user.id" :src="historyItem.user.profile_photo_url"
                                 :alt="historyItem.user.name"
                                 class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                            <UserTooltip :user="historyItem.user"/>
                            <div class="text-secondary subpixel-antialiased ml-2 text-sm my-auto">
                                {{ historyItem.description }}
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

const number_of_participants = [
    {number: '100-1000'},
    {number: '1000-10000'},
]

export default defineComponent({
    components: {
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
        UserTooltip
    },
    props: ['projects', 'users', 'categories', 'genres', 'sectors'],
    methods: {
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
            this.selectedCategory = {name: ''};
            this.selectedGenre = {name: ''};
            this.selectedSector = {name: ''};
            this.form.sector_id = 0;
            this.form.category_id = 0;
            this.form.genre_id = 0;
        },
        addProject() {
            this.form.number_of_participants = this.selectedParticipantNumber;
            this.form.category_id = this.selectedCategory.id;
            this.form.sector_id = this.selectedSector.id;
            this.form.genre_id = this.selectedGenre.id;
            this.form.post(route('projects.store'), {})
            this.closeAddProjectModal();
        },
        getEditHref(project) {
            return route('projects.show', {project: project.id,month_start: new Date((new Date).getFullYear(),(new Date).getMonth(),1,0,120),month_end:new Date((new Date).getFullYear(),(new Date).getMonth() + 1,2), calendarType: 'monthly' });
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

        },
        closeSuccessModal() {
            this.showSuccessModal = false;
            this.nameOfDeletedProject = "";
        },
        openProjectHistoryModal(projectHistory) {
            this.projectHistoryToDisplay = projectHistory;
            this.showProjectHistory = true;
        },
        closeProjectHistoryModal() {
            this.showProjectHistory = false;
            this.projectHistoryToDisplay = [];
        },
        isTeamMember(departments){
            departments.forEach((department) => {
                department.users.forEach((user) => {
                    if(user.id === this.$page.props.user.id){
                        console.log("moin");
                        return true;
                    }
                })
            })
            return false;
        }
    },
    data() {
        return {
            addingProject: false,
            deletingProject: false,
            showDetails: false,
            projectToDelete: null,
            showSuccessModal: false,
            selectedParticipantNumber: "",
            nameOfDeletedProject: "",
            selectedCategory: {name: ''},
            selectedSector: {name: ''},
            selectedGenre: {name: ''},
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
