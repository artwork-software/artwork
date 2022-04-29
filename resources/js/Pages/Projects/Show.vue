<template>
    <app-layout title="Projects">
        <div class="max-w-screen-2xl my-12 ml-20 mr-10 flex flex-row">
            <div class="flex w-8/12 flex-col">
                <div class="flex ">
                    <h2 class="flex font-bold font-lexend text-3xl">{{ project.name }}</h2>
                    <Menu as="div" class="my-auto relative">
                        <div class="flex">
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
                                class="origin-top-left absolute left-0 mr-4 mt-2 w-72 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                <div class="py-1">
                                    <MenuItem v-slot="{ active }">
                                        <a @click="openEditProjectModal"
                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <PencilAltIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            Basisdaten bearbeiten
                                        </a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <a href="#" @click=""
                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <DuplicateIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            Duplizieren
                                        </a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <a @click=""
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
                <div class="mt-2 text-secondary text-xs">
                    zuletzt geändert:
                </div>
                <div class="mt-2 mr-14 subpixel-antialiased text-secondary">
                    {{ project.description }}
                </div>
                <div class="mt-4 text-xs text-secondary">
                    Kostenträger: {{ project.cost_center ? project.cost_center : 'noch nicht definiert' }} | Anzahl
                    Teilnehmer*innen:
                    {{ project.number_of_participants ? project.number_of_participants : 'noch nicht definiert' }}
                </div>
                <div class="mt-3 flex text-secondary text-xs">
                    <span class="mr-2">Kategorie: </span>
                    <CategoryIconCollection v-if="project.category" height="16" width="16"
                                            :iconName="project.category.svg_name"/>
                    <span class="ml-1 mr-1">{{
                            project.category ? project.category.name : 'noch nicht definiert'
                        }} </span> | Genre:
                    {{ project.genre ? project.genre.name : 'noch nicht definiert' }} | Bereich:
                    {{ project.sector ? project.sector.name : 'noch nicht definiert' }}
                </div>
                <h3 class="text-xl mt-12 mb-4 leading-6 font-bold font-lexend text-gray-900">Wann und wo?</h3>
                <span class="text-secondary text-sm">Termin & Raum noch nicht definiert</span>
            </div>
            <div class="flex flex-wrap">
                <div class="flex mr-2 mt-10 flex-1 flex-wrap">
                    <h2 class="font-bold font-lexend text-2xl">Projektteam</h2>
                    <div class="cursor-pointer" @click="openEditProjectTeamModal">
                        <DotsVerticalIcon class="ml-2 mr-1 mt-2 flex-shrink-0 h-6 w-6 text-gray-600"
                                          aria-hidden="true"/>
                    </div>
                    <div>
                        <div v-if="$page.props.can.show_hints" class="absolute flex w-48 mt-2">
                            <div>
                                <SvgCollection svgName="arrowLeft" class="mt-1"/>
                            </div>
                            <div class="flex">
                                <span class="font-nanum ml-2 text-secondary tracking-tight tracking-tight text-lg">Stelle dein Team zusammen</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap w-full">
                    <span class="flex text-secondary w-full subpixel-antialiased tracking-widest">Projektleitung</span>
                    <div class="flex" v-for="user in this.project.users">
                        <img :src="user.profile_photo_url" :alt="user.name"
                             class="rounded-full h-11 w-11 object-cover"/>
                    </div>

                </div>
                <div class="flex w-full flex-wrap">
                    <span class="flex text-secondary w-full subpixel-antialiased tracking-widest">Team</span>
                    <div class="flex " v-for="department in this.project.departments">
                        <TeamIconCollection :iconName="department.svg_name" :alt="department.name"
                                            class="rounded-full h-11 w-11 object-cover"/>
                    </div>
                </div>

            </div>


        </div>
        <!-- Div with Bg-Color -->
        <div class="bg-stone-50 w-full h-full">
            <div class="ml-20">
                <div class="sm:hidden">
                    <label for="tabs" class="sr-only">Select a tab</label>
                    <select id="tabs" name="tabs"
                            class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option v-for="tab in tabs" :key="tab.name" :selected="tab.current">{{ tab.name }}</option>
                    </select>
                </div>
                <div class="hidden sm:block">
                    <div class="border-gray-200">
                        <nav class="-mb-px pt-4 flex space-x-8" aria-label="Tabs">
                            <a @click="changeTab(tab)" v-for="tab in tabs" href="#" :key="tab.name"
                               :class="[tab.current ? 'border-primary text-primary' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg font-semibold']"
                               :aria-current="tab.current ? 'page' : undefined">
                                {{ tab.name }}
                            </a>
                        </nav>
                    </div>
                </div>
            </div>
            <div>
                <!-- Checklist Tab -->
                <div v-if="isChecklistTab" class="grid grid-cols-3 ml-20 mt-14">
                    <div class="col-span-2">
                        <div class="flex w-full items-center mb-8 ">
                            <h3 class="text-2xl leading-6 font-bold font-lexend text-gray-900"> Checklisten </h3>
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
                        <div class="w-full">
                        <span v-if="project.public_checklists.length === 0"
                              class="text-secondary subpixel-antialiased font-semibold text-sm mb-4">Noch keine Checklisten hinzugefügt. Erstelle Checklisten mit Aufgaben. Die Checklisten kannst du Teams zuordnen. Nutze Vorlagen und spare Zeit.</span>
                            <div v-else>
                                <div class="flex w-full flex-wrap">
                                    <!-- Div einer Checkliste -->
                                    <div v-for="checklist in project.public_checklists"
                                         class="flex w-full bg-white my-2">
                                        <div class="flex w-full ml-4 flex-wrap p-4">
                                            <div class="flex mt-4 justify-between w-full">
                                                <div class="">
                                        <span class="text-2xl leading-6 font-bold font-lexend text-gray-900">
                                        {{ checklist.name }}
                                        </span>
                                                </div>
                                                <div class="flex">
                                                    <div class="flex " v-for="department in checklist.departments">
                                                        <TeamIconCollection :iconName="department.svg_name"
                                                                            :alt="department.name"
                                                                            class="rounded-full h-9 w-9 object-cover"/>
                                                    </div>
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
                                                                        <a @click="openEditChecklistTeamsModal(checklist)"
                                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                            <PencilAltIcon
                                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                aria-hidden="true"/>
                                                                            Teams zuweisen
                                                                        </a>
                                                                    </MenuItem>
                                                                    <MenuItem v-slot="{ active }">
                                                                        <a @click=""
                                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                            <PencilAltIcon
                                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                aria-hidden="true"/>
                                                                            Bearbeiten
                                                                        </a>
                                                                    </MenuItem>
                                                                    <MenuItem v-slot="{ active }">
                                                                        <a @click=""
                                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                            <PencilAltIcon
                                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                aria-hidden="true"/>
                                                                            Alle Aufgaben als erledigt markieren
                                                                        </a>
                                                                    </MenuItem>
                                                                    <MenuItem v-slot="{ active }">
                                                                        <a @click=""
                                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                            <PencilAltIcon
                                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                aria-hidden="true"/>
                                                                            Als Vorlage speichern
                                                                        </a>
                                                                    </MenuItem>
                                                                    <MenuItem v-slot="{ active }">
                                                                        <a href="#" @click=""
                                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                            <DuplicateIcon
                                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                aria-hidden="true"/>
                                                                            Duplizieren
                                                                        </a>
                                                                    </MenuItem>
                                                                    <MenuItem v-slot="{ active }">
                                                                        <a @click=""
                                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
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
                                            <div class="flex w-full mt-6">
                                                <div class="">
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
                                            <div class="mt-6 mb-12">
                                                <draggable ghost-class="opacity-50" tag="transition-group"
                                                           item-key="draggableID" v-model="checklist.tasks"
                                                           @start="dragging=true" @end="dragging=false">
                                                    <template #item="{element}">
                                                        <div class="flex mt-6 flex-wrap w-full"
                                                             :class="dragging? 'cursor-grabbing' : 'cursor-grab'">
                                                            <div class="flex w-full">
                                                                <input v-model="element.done"
                                                                       type="checkbox"
                                                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                                                <p class="ml-4 my-auto text-lg font-black text-sm"
                                                                   :class="element.done ? 'text-secondary' : 'text-primary'">
                                                                    {{ element.name }}</p>
                                                            </div>
                                                            <div class="ml-10 text-secondary">
                                                                {{ element.description }}
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
                    <div class="col-span-2">
                        <div class="flex w-full items-center mb-8">
                            <h3 class="text-2xl leading-6 font-bold font-lexend text-gray-900"> Informationen </h3>
                        </div>
                    </div>
                    <div class="col-span-1">
                        <div class="flex w-full items-center mb-8">
                            <h3 class="text-2xl leading-6 font-bold font-lexend text-gray-900"> Dokumente </h3>
                        </div>
                        <div @dragover.prevent @drop.stop.prevent="uploadDocument($event)" class="mb-8 w-full flex justify-center items-center
                        border-secondary border-dotted border-4 h-40 bg-stone-100 p-2">
                            <p class="text-secondary">Zugelassene Formate:
                                <br>.jpg, .pdf, .docx, .xls
                            </p>
                        </div>
                        <jet-input-error :message="uploadDocumentFeedback"/>
                        <div class="space-y-1">
                            <div v-for="project_file in project.project_files" class="cursor-pointer group flex items-center">
                                <DocumentTextIcon class="h-5 w-5" aria-hidden="true"/>
                                <p @click="downloadFile(project_file)" class="ml-2">{{project_file.name}}</p>
                                <XCircleIcon @click="removeFile(project_file)" class="ml-2 hidden group-hover:block h-5 w-5 text-error" aria-hidden="true"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Projekt bearbeiten Modal-->
        <jet-dialog-modal :show="editingProject" @close="closeEditProjectModal">
            <template #content>
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
                                           class="text-primary h-10 focus:border-black border-2 w-full text-sm border-gray-300 "/>
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
                                            <CategoryIconCollection v-if="selectedCategory.svg_name !== ''" :height="16"
                                                                    :width="16" :iconName="selectedCategory.svg_name"/> <span
                                            class="ml-4">{{ selectedCategory.name }}</span>
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
                                                    <CategoryIconCollection :width="16" :height="16"
                                                                            :iconName="category.svg_name"/>
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
                        <div class="text-primary flex tracking-tight leading-6 sub">
                            Keine Vorlage
                            <ChevronDownIcon class="h-6 w-6"/>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex mt-8">
                            <div class="relative w-full mr-4">
                                <input id="checklistName" v-model="checklistForm.name" type="text"
                                       class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                                       placeholder="placeholder"/>
                                <label for="checklistName"
                                       class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name
                                    der Checkliste</label>
                            </div>
                            <jet-input-error :message="form.error" class="mt-2"/>
                        </div>
                        <div class="flex items-center my-6">
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


                        <button :class="[checklistForm.name.length === 0 ?
                    'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                                class="mt-4 flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="addChecklist" :disabled="checklistForm.name.length === 0">
                            Anlegen
                        </button>
                    </div>
                </div>
            </template>

        </jet-dialog-modal>
        <!-- Change Project Team Modal -->
        <jet-dialog-modal :show="editingTeam" @close="closeEditProjectTeamModal">
            <template #content>
                <div class="mx-3">
                    <div class="font-bold font-lexend text-primary text-2xl my-2">
                        Team bearbeiten
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
                        <pre>
                            {{assignedUsers}}
                        </pre>
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
                            class=" inline-flex mt-8 items-center px-12 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                    >Speichern
                    </button>

                </div>

            </template>

        </jet-dialog-modal>
        <!-- Change Checklist Teams Modal -->
        <jet-dialog-modal :show="editingChecklistTeams" @close="closeEditChecklistTeamsModal">
            <template #content>
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
                    <div class="mt-6 relative">
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
                            class=" inline-flex mt-8 items-center px-12 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                    >Zuweisen
                    </button>

                </div>

            </template>

        </jet-dialog-modal>
        <!-- Add Task Modal-->
        <jet-dialog-modal :show="addingTask" @close="closeAddTaskModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                        Neue Aufgabe
                    </div>
                    <XIcon @click="closeAddTaskModal"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="mt-12">
                        <div class="flex">
                            <div class="relative flex w-full mr-4">
                                <input id="task_name" v-model="taskForm.name" type="text"
                                       class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-xl font-bold text-primary placeholder-secondary placeholder-transparent"
                                       placeholder="placeholder"/>
                                <label for="task_name"
                                       class="absolute left-0 text-base -top-4 text-gray-600 -top-6 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Aufgabe</label>
                            </div>
                        </div>
                        <!-- TODO: ZU ERLEDIGEN BIS bei TASK -->
                        <span> Zu erledigen bis? (Kommt mit Raum/Zeit-Sprint)</span>
                        <div class="mt-8 mr-4">
                                            <textarea
                                                placeholder="Kommentar"
                                                v-model="taskForm.description" rows="3"
                                                class="focus:border-primary placeholder-secondary border-2 w-full font-semibold border border-gray-300 "/>
                        </div>
                        <button
                            :class="[this.taskForm.name === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                            class="mt-8 inline-flex items-center px-20 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                            @click="addTask"
                            :disabled="this.taskForm.name === ''">
                            Hinzufügen
                        </button>
                    </div>

                </div>

            </template>
        </jet-dialog-modal>
    </app-layout>
</template>

<script>

import {useForm} from "@inertiajs/inertia-vue3";
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
import {PencilAltIcon, TrashIcon, XIcon, DuplicateIcon, DocumentTextIcon} from "@heroicons/vue/outline";
import {
    CheckIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    DotsVerticalIcon,
    XCircleIcon,
    PlusSmIcon
} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import JetButton from "@/Jetstream/Button";
import JetDialogModal from "@/Jetstream/DialogModal";
import JetInput from "@/Jetstream/Input";
import JetInputError from "@/Jetstream/InputError";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import Checkbox from "@/Jetstream/Checkbox";
import CategoryIconCollection from "@/Layouts/Components/CategoryIconCollection";
import draggable from "vuedraggable";

const number_of_participants = [
    {number: '100-1000'},
    {number: '1000-10000'},
]


export default {
    name: "ProjectShow",
    props: ['project', 'users', 'categories', 'genres', 'sectors'],
    components: {
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
        DocumentTextIcon
    },
    computed: {
        tabs() {
            return [
                {name: 'Ablaufplan', href: '#', current: this.isScheduleTab},
                {name: 'Checklisten', href: '#', current: this.isChecklistTab},
                {name: 'Informationen & Dokumente', href: '#', current: this.isInfoTab},
            ]
        }
    },
    data() {
        return {
            uploadDocumentFeedback: "",
            editingProject: false,
            addingTask: false,
            dragging: false,
            selectedParticipantNumber: this.project.number_of_participants ? this.project.number_of_participants : '',
            addingChecklist: false,
            isScheduleTab: false,
            isChecklistTab: true,
            isInfoTab: false,
            editingTeam: false,
            editingChecklistTeams: false,
            department_query: "",
            department_and_user_query: "",
            department_search_results: [],
            department_and_user_search_results: [],
            checklist_assigned_departments: [],
            selectedCategory: this.project.category ? this.project.category : {name: '', svg_name: ''},
            selectedSector: this.project.sector ? this.project.sector : {name: ''},
            selectedGenre: this.project.genre ? this.project.genre : {name: ''},
            showDetails: false,
            checklistToEdit: null,
            assignedUsers: this.project.users,
            assignedDepartments: this.project.departments,
            form: useForm({
                name: this.project.name,
                description: this.project.description,
                cost_center: this.project.cost_center,
                number_of_participants: this.project.number_of_participants,
                assigned_user_ids: {},
                assigned_departments: [],
                sector_id: null,
                category_id: null,
                genre_id: null,

            }),
            checklistForm: useForm({
                name: "",
                project_id: this.project.id,
                tasks: [],
                assigned_department_ids: [],
                private: false,
            }),
            taskForm: useForm({
                name: "",
                description: "",
                deadline: "2012-12-12",
                checklist_id: null,
            }),
            documentForm: useForm({
                file: null
            })
        }
    },
    methods: {
        removeFile(project_file) {
          this.$inertia.delete(`/project_files/${project_file.id}`, {
              preserveState: true
          })
        },
        downloadFile(project_file) {
            let link = document.createElement('a');
            link.href = route('download_file', {project_file: project_file});
            link.target = '_blank';
            link.click();
        },
        uploadDocument(event) {
            this.uploadDocumentFeedback = "";
            const allowedTypes = [
                "image/jpeg",
                "application/pdf",
                "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                "application/vnd.ms-excel",
                "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
            ]

            for (let file of [...event.dataTransfer.files]) {
                if (allowedTypes.includes(file.type)) {

                    this.uploadDocumentToProject(file)

                } else {
                    this.uploadDocumentFeedback = "Dieses Dateiformat wird nicht unterstützt"

                }
            }
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
        openEditProjectModal() {
            this.editingProject = true;
        },
        closeEditProjectModal() {
            this.editingProject = false;
            this.form.name = "";
            this.form.description = "";
            this.form.cost_center = "";
            this.form.number_of_participants = "";
            this.selectedParticipantNumber = "";
            this.selectedCategory = {name: '', svg_name: ''};
            this.selectedGenre = {name: ''};
            this.selectedSector = {name: ''};
            this.form.sector_id = 0;
            this.form.category_id = 0;
            this.form.genre_id = 0;
        },
        openAddChecklistModal() {
            this.addingChecklist = true;
        },
        closeAddChecklistModal() {
            this.addingChecklist = false;
        },
        addChecklist() {
            this.checklistForm.post(route('checklists.store'), {})
            this.closeAddChecklistModal();
        },
        editProject() {
            this.form.number_of_participants = this.selectedParticipantNumber;
            this.form.category_id = this.selectedCategory.id;
            this.form.sector_id = this.selectedSector.id;
            this.form.genre_id = this.selectedGenre.id;
            this.assignedUsers.forEach(user => {
                this.form.assigned_user_ids.push(user.id);
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
        deleteUserFromProjectTeam(user) {
            this.assignedUsers.splice(this.assignedUsers.indexOf(user), 1);
        },
        deleteDepartmentFromProjectTeam(department) {
            this.assignedDepartments.splice(this.assignedDepartments.indexOf(department), 1);
        },
        addDepartmentToChecklistTeamArray(department) {
            for (let assigned_department of this.checklist_assigned_departments) {
                if (department === assigned_department) {
                    this.department_query = ""
                    return;
                }
            }
            this.checklist_assigned_departments.push(department);
            this.department_query = ""
        },
        saveChecklistTeams() {
            this.checklist_assigned_departments.forEach((department) => {
                this.checklistForm.assigned_department_ids.push(department.id);
            })
            this.checklistForm.name = this.checklistToEdit.name;

            this.checklistForm.patch((route('checklists.update', {checklist: this.checklistToEdit.id}))
            )
            this.closeEditChecklistTeamsModal();
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
            for (let assignedDepartment of this.assignedDepartments) {
                if (departmentToAdd.id === assignedDepartment.id) {
                    this.department_and_user_query = ""
                    return;
                }
            }

            this.assignedDepartments.push(departmentToAdd);
            this.department_and_user_query = ""
        },
        editProjectTeam() {
            // TODO: HIER NOCH IS_ADMIN IS_MANAGER VERARBEITEN
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
            this.addingTask = false;
        },
        addTask() {
            console.log(this.taskForm);
            this.taskForm.post(route('tasks.store'), {});
            this.closeAddTaskModal();
        }
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

<style scoped>

</style>
