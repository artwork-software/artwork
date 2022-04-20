<template>
    <app-layout title="Teamprofil">
        <div class="max-w-screen-2xl my-12 ml-20 mr-10 flex flex-row">
            <div class="flex w-8/12 flex-col">
                <div class="flex">
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
                {{ project }}
                <div class="mt-2 subpixel-antialiased text-secondary">
                    {{ project.description }}
                </div>
                <div class="mt-4 text-xs text-secondary">
                    Kostenträger: {{ project.cost_center ? project.cost_center : 'noch nicht definiert' }} | Anzahl
                    Teilnehmer*innen:
                    {{ project.number_of_participants ? project.number_of_participants : 'noch nicht definiert' }}
                </div>
                <div class="mt-3 text-secondary text-xs">
                    Kategorie: {{ project.category ? project.category : 'noch nicht definiert' }} | Genre:
                    {{ project.genre ? project.genre : 'noch nicht definiert' }} | Bereich:
                    {{ project.field ? project.field : 'noch nicht definiert' }}
                </div>
                <h3 class="text-xl mt-6 mb-4 leading-6 font-bold font-lexend text-gray-900">Wann und wo?</h3>
                <span class="text-secondary text-sm mb-4">Termin & Raum noch nicht definiert</span>
            </div>
            <div class="flex flex-wrap">
                <div class="flex">
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
                <div class="flex w-full">
                    <span class="flex text-secondary -mt-16">Projektleitung</span>
                </div>

            </div>


        </div>
        <!-- Div with Bg-Color -->
        <div class="bg-stone-50 w-full h-full bottom-0 left-0">
            <div class="ml-20">
                <div class="sm:hidden">
                    <label for="tabs" class="sr-only">Select a tab</label>
                    <!-- Use an "onChange" listener to redirect the user to the selected tab URL. -->
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
            <div class="max-w-screen-2xl mb-20 mt-14 ml-20 mr-10 flex flex-wrap flex-row">
                <!-- Checklist Tab -->
                <div v-if="isChecklistTab">
                    <div class="flex items-center mb-4">
                        <h3 class="text-2xl leading-6 font-bold font-lexend text-gray-900"> Checklisten </h3>
                        <button @click="openAddChecklistModal" type="button"
                                class="flex ml-4 border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none">
                            <PlusSmIcon class="h-5 w-5" aria-hidden="true"/>
                        </button>
                        <div v-if="$page.props.can.show_hints" class="flex">
                            <SvgCollection svgName="arrowLeft" class="ml-2"/>
                            <span
                                class="font-nanum text-secondary tracking-tight ml-1 my-auto tracking-tight text-xl">Lege neue Checklisten an</span>
                        </div>
                    </div>
                    <div class="flex w-full">
                        <span v-if="project.checklists.length === 0"
                              class="text-secondary subpixel-antialiased font-semibold text-sm mb-4">Noch keine Checklisten hinzugefügt. Erstelle Checklisten mit Aufgaben. Die Checklisten kannst du Teams zuordnen. Nutze Vorlagen und spare Zeit.</span>
                        <div v-else>
                            <div>
                            <div v-for="checklist in project.checklists" class="flex bg-white">
                                {{ checklist.name }}
                            </div>
                                <Menu as="div" class="my-auto relative">
                                    <div class="flex">
                                        <MenuButton
                                            class="flex ml-6">
                                            <DotsVerticalIcon class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                                              aria-hidden="true"/>
                                        </MenuButton>
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
                                                    <a @click="openEditChecklistTeamsModal"
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
                    </div>
                </div>
            </div>
        </div>
        <!-- Projekt erstellen Modal-->
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
                        <div class="mt-4 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <div class="sm:col-span-3">
                                <div class="mt-1">
                                    <input type="text" v-model="form.cost_center" placeholder="Kostenträger eintragen"
                                           class="text-primary focus:border-primary border-2 w-full font-semibold border-gray-300 "/>
                                </div>
                            </div>
                            <Listbox as="div" class="sm:col-span-3 mt-1" v-model="selectedParticipantNumber">
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
                                                <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                            <span
                                                :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">
                                                {{ participantNumber.number }}
                                            </span>
                                                    <span v-if="selected"
                                                          :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                      <CheckIcon class="h-5 w-5" aria-hidden="true"/>
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
                            Anlegen
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
        <!-- Change TeamMember Modal -->
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
                    <!-- TODO: Volltextsuche nach allen teams hier -->
                    <div class="mt-4">
                        <div class="flex">
                        </div>
                        <span v-for="(user,index) in department.users"
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
                                <XCircleIcon class="ml-2 mt-1 h-5 w-5 hover:text-error "/>
                            </button>
                            <div class="flex justify-between items-center my-1.5 h-5">
                                <div class="flex items-center justify-start">
                                    <input @change="changeAdminStatus(user)" v-model="user.is_admin"
                                           type="checkbox"
                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                    <p :class="[user.is_admin ? 'text-primary font-black' : 'text-secondary']"
                                       class="ml-4 my-auto text-sm">Projektadmin</p>
                                    <input @change="changeManagerStatus(user)" v-model="user.is_manager"
                                           type="checkbox"
                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                    <p :class="[user.is_manager ? 'text-primary font-black' : 'text-secondary']"
                                       class="ml-4 my-auto text-sm">Projektleitung</p>
                                </div>
                            </div>

                        </span>
                    </div>
                    <button @click="editTeam"
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
                    <XIcon @click="closeChangeTeamMembersModal"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-secondary tracking-tight leading-6 sub">
                        Tippe den Namen des Teams ein, dem du die Checkliste zuweisen möchtest.
                    </div>
                    <!-- TODO: Volltextsuche nach allen departments hier -->
                    <div class="mt-4">
                        <div class="flex">
                        </div>
                        <span v-for="(team,index) in project.departments"
                              class="flex mt-4 mr-1 rounded-full items-center font-bold text-primary">
                            <div class="flex items-center">
                                <TeamIconCollection :iconName="team.svg_name" />
                                <span class="flex ml-4">
                                {{ team.name }}
                                    </span>
                            </div>
                            <button type="button" @click="deleteTeamFromChecklist(index)">
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
import {PencilAltIcon, TrashIcon, XIcon, DuplicateIcon} from "@heroicons/vue/outline";
import {CheckIcon, ChevronDownIcon, DotsVerticalIcon, XCircleIcon, PlusSmIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import JetButton from "@/Jetstream/Button";
import JetDialogModal from "@/Jetstream/DialogModal";
import JetInput from "@/Jetstream/Input";
import JetInputError from "@/Jetstream/InputError";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import Checkbox from "@/Jetstream/Checkbox";

const number_of_participants = [
    {number: '100-1000'},
    {number: '1000-10000'},
]


export default {
    name: "ProjectShow",
    props: ['project'],
    components: {
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
        Switch
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
            editingProject: false,
            selectedParticipantNumber: this.project.number_of_participants,
            addingChecklist: false,
            isScheduleTab: false,
            isChecklistTab: true,
            isInfoTab: false,
            editingTeam: false,
            editingChecklistTeams:false,
            form: useForm({
                name: this.project.name,
                description: this.project.description,
                cost_center: this.project.cost_center,
                number_of_participants: this.project.number_of_participants,
                assigned_user_ids: this.project.users

            }),
            checklistForm: useForm({
                name: "",
                project_id: this.project.id,
                tasks: [],
                users: [],
                assigned_department_ids: [],
                private: false,
            }),
            taskForm: useForm({
                name: "",
                description: "",
                deadline: "",
                done: false
            })
        }
    },
    methods: {
        openEditProjectTeamModal() {
            this.editingTeam = true;
        },
        closeEditProjectTeamModal() {
            this.editingTeam = false;
        },
        openEditChecklistTeamsModal(){
          this.editingChecklistTeams = true;
        },
        closeEditChecklistTeamsModal(){
          this.editingChecklistTeams = false;
        },
        openEditProjectModal() {
            this.editingProject = true;
        },
        closeEditProjectModal() {
            this.editingProject = false;
        },
        openAddChecklistModal() {
            this.addingChecklist = true;
        },
        closeAddChecklistModal() {
            this.addingChecklist = false;
        },
        addChecklist() {
            this.checklistForm.post(route('checklists.store'), {})
        },
        editProject() {
            this.form.number_of_participants = this.selectedParticipantNumber;
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
        deleteUserFromProjectTeam(user){

        },
        changeAdminStatus(){

        },
        changeManagerStatus(){

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
