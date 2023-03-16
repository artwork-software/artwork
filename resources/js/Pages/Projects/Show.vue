<template>
    <app-layout>
        <div class="my-12 pl-10 pr-10">
            <div class="flex flex-col">
                <div v-if="currentGroup" class="bg-secondaryHover -mb-6 z-20 w-fit pr-6 pb-0.5">
                    <div class="flex items-center">
                        <span v-if="!project.is_group">
                            <img src="/Svgs/IconSvgs/icon_group_black.svg" class="h-4 w-4 mr-2" aria-hidden="true"/>
                        </span>
                        Gehört zu <a :href="'/projects/' + currentGroup.id" class="text-buttonBlue ml-1">
                        {{ currentGroup?.name }}</a>
                    </div>
                </div>
                <div class="flex z-10" v-if="this.project.key_visual_path !== null">
                    <img :src="'/storage/keyVisual/' + this.project.key_visual_path" alt="Aktuelles Key-Visual"
                         class="rounded-md w-full h-[200px]">
                </div>
                <div v-else class="w-full h-40 bg-gray-200 flex justify-center items-center">
                    <img src="/Svgs/IconSvgs/placeholder.svg" alt="Aktuelles Key-Visual"
                         class="rounded-md ">
                </div>
                <div class="mt-2 subpixel-antialiased text-secondary text-xs flex items-center"
                     v-if="project.project_history.length">
                    <div>
                        zuletzt geändert:
                    </div>
                    <img v-if="project.project_history[0]?.changes[0]?.changed_by"
                         :data-tooltip-target="project.project_history[0].changes[0].changed_by?.id"
                         :src="project.project_history[0].changes[0].changed_by?.profile_photo_url"
                         :alt="project.project_history[0].changes[0].changed_by?.first_name"
                         class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                    <UserTooltip v-if="project.project_history[0]?.changes[0]?.changed_by"
                                 :user="project.project_history[0].changes[0].changed_by"/>
                    <span class="ml-2 subpixel-antialiased">
                        {{ project.project_history[0]?.created_at }}
                    </span>
                    <button class="ml-4 subpixel-antialiased text-buttonBlue flex items-center cursor-pointer"
                            @click="openProjectHistoryModal()">
                        <ChevronRightIcon
                            class="-mr-0.5 h-4 w-4  group-hover:text-white"
                            aria-hidden="true"/>
                        Verlauf ansehen
                    </button>
                </div>
                <div class="flex justify-between items-center">
                    <h2 class="flex font-black font-lexend text-primary tracking-wide text-3xl items-center">
                        <span v-if="project.is_group">
                            <img src="/Svgs/IconSvgs/icon_group_black.svg" class="h-6 w-6 mr-2" aria-hidden="true"/>
                        </span>
                        {{ project?.name }}
                        <span class="rounded-full items-center font-medium px-3 py-1 my-2 text-sm ml-2 mb-1 inline-flex"
                              :class="selectedState?.color">
                            {{ selectedState?.name }}
                        </span>

                    </h2>
                    <Menu as="div" class="my-auto mt-3 relative"
                          v-if="this.$page.props.can.edit_projects || this.$page.props.is_admin || projectManagerIds.includes(this.$page.props.user.id) || projectCanWriteIds.includes(this.$page.props.user.id)">
                        <div class="flex items-center -mt-1">
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
                                class="origin-top-right absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                <div class="py-1">
                                    <MenuItem
                                        v-if="this.$page.props.is_admin || projectCanWriteIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id) || this.$page.props.can.edit_projects"
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
                                        v-if="this.$page.props.can.edit_projects || this.$page.props.is_admin || this.$page.props.can.delete_projects || projectManagerIds.includes(this.$page.props.user.id)"
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
                <div class="mt-3" v-if="projectGroups.length > 0">
                    <TagComponent v-for="projectGroup in projectGroups" :method="deleteProjectFromGroup"
                                  :displayed-text="projectGroup.name" :property="projectGroup"></TagComponent>
                </div>

                <div class="w-full mt-5 text-secondary subpixel-antialiased">
                    <div v-if="firstEventInProject && lastEventInProject">
                        Zeitraum/Öffnungszeiten: {{ firstEventInProject?.start_time }} <span
                        v-if="firstEventInProject?.start_time">Uhr -</span> {{ lastEventInProject?.end_time }} <span
                        v-if="lastEventInProject?.end_time">Uhr</span>
                    </div>
                    <div>
                        Termine mit Publikum in: <span
                        v-for="(RoomWithAudience, index) in RoomsWithAudience">{{ RoomWithAudience }}, </span>
                    </div>
                    <div v-if="!RoomsWithAudience && !(firstEventInProject && lastEventInProject)">
                        Noch keine Termine innerhalb dieses Projektes
                    </div>
                </div>
            </div>
        </div>
        <!-- Div with Bg-Color -->
        <div class="w-full h-full mb-48">
            <div class="ml-10">
                <div class="hidden sm:block">
                    <div class="border-gray-200">
                        <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8" aria-label="Tabs">
                            <a @click="changeTab(tab)" v-for="tab in tabs" href="#" :key="tab?.name"
                               :class="[tab.current ? 'border-buttonBlue text-buttonBlue' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium font-semibold']"
                               :aria-current="tab.current ? 'page' : undefined">
                                {{ tab?.name }}
                            </a>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="bg-lightBackgroundGray">
                <!-- Calendar Tab -->
                <div v-if="isScheduleTab" class="p-5 mt-6 max-w-screen-2xl bg-lightBackgroundGray">
                    <CalendarComponent :eventTypes=this.eventTypes :project="project"/>
                </div>
                <!-- Checklist Tab -->
                <div v-if="isChecklistTab"
                     class="grid grid-cols-3 ml-10 mt-6 p-5 max-w-screen-2xl bg-lightBackgroundGray ">
                    <ChecklistComponent
                        :project="project"
                        :opened_checklists="opened_checklists"
                        :project-can-write-ids="projectCanWriteIds"
                        :project-manager-ids="projectManagerIds"
                        :checklist_templates="checklist_templates"
                    />

                </div>
                <!-- Comment Tab -->
                <div v-if="isCommentTab"
                     class="mx-5 mt-6 p-5 max-w-screen-xl bg-lightBackgroundGray">
                    <div
                        v-if="this.$page.props.can.edit_projects || this.$page.props.is_admin || this.$page.props.can.admin_projects || projectCanWriteIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id) || isMemberOfADepartment"
                        class="relative border-2 hover:border-gray-400 w-full bg-white border border-gray-300">
                        <textarea
                            placeholder="Was sollten die anderen Projektmitglieder über das Projekt wissen?"
                            v-model="commentForm.text" rows="4"
                            class="resize-none focus:outline-none focus:ring-0  pt-3 mb-8 placeholder-secondary border-0  w-full"/>
                        <div class="absolute bottom-0 right-0 flex bg-white">
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
                        <div v-if="sortedComments?.length > 0" class="my-6" v-for="comment in sortedComments"
                             @mouseover="commentHovered = comment.id"
                             @mouseout="commentHovered = null">
                            <div class="flex justify-between">
                                <div class="flex items-center">
                                    <img v-if="comment.user" :data-tooltip-target="comment.user"
                                         :src="comment.user.profile_photo_url" :alt="comment.user.name"
                                         class="rounded-full h-7 w-7 object-cover"/>
                                    <UserTooltip v-if="comment.user" :user="comment.user"/>
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
                        <div v-else class="xsDark mt-6">
                            Noch keine Kommentare vorhanden
                        </div>
                    </div>
                </div>
                <!-- Info Tab -->
                <div v-if="isInfoTab" class="mx-5 mt-6 p-5  bg-lightBackgroundGray">
                    <div class="grid grid-cols-6 mr-8">
                        <div class="col-span-4">
                            <!-- Description -->
                            <div class="mt-4">
                                <div> Kurzbeschreibung</div>
                                <div v-if="descriptionClicked === false"
                                     class="mt-2 subpixel-antialiased text-secondary"
                                     @click="handleDescriptionClick()">
                                    {{
                                        project.description ? project.description : 'Hier klicken um Text hinzuzufügen'
                                    }}
                                </div>
                                <textarea v-else v-model="project.description" type="text"
                                          @focusout="updateDescription()"
                                          :ref="`description-${this.project.id}`"
                                          class="w-full border-gray-300 text-primary h-40"
                                          :placeholder="project.description || 'Hier klicken um Text hinzuzufügen'"/>
                            </div>
                            <!-- Individual Projectinformation -->
                            <div v-for="headline in project.project_headlines" class="mt-4">
                                <div>{{ headline.name }}</div>
                                <div v-if="!headline.clicked" class="mt-2 subpixel-antialiased text-secondary"
                                     @click="handleTextClick(headline)">
                                    {{ headline.text ? headline.text : 'Hier klicken um Text hinzuzufügen' }}
                                </div>
                                <textarea v-else v-model="headline.text" type="text" :ref="`text-${headline.id}`"
                                          @focusout="changeHeadlineText(headline)"
                                          class="w-full border-gray-300 text-primary h-40"
                                          :placeholder="headline.text || 'Hier klicken um Text hinzuzufügen'"/>
                            </div>
                        </div>
                        <div
                            v-if="this.$page.props.can.edit_projects || this.$page.props.is_admin || this.$page.props.can.admin_projects || projectCanWriteIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id) || isMemberOfADepartment"
                            class="col-span-2">
                            <div class="ml-10 group">
                                <label class="block my-4 sDark">
                                    Key Visual </label>
                                <div class="flex col-span-2 w-full justify-center border-2 bg-stone-50 border-gray-300 cursor-pointer border-dashed rounded-md p-2"
                                    @dragover.prevent
                                    @drop.stop.prevent="uploadDraggedKeyVisual($event)"
                                    @click="selectNewKeyVisual"
                                     v-if="this.project.key_visual_path === null">
                                    <div class="space-y-1 text-center">
                                        <div class="xsLight flex my-auto h-40 items-center"
                                             v-if="this.project.key_visual_path === null">
                                            Ziehe hier dein <br/> Key Visual hin
                                            <input id="keyVisual-upload" ref="keyVisual"
                                                   name="file-upload" type="file" class="sr-only"
                                                   @change="updateKeyVisual"/>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="flex items-center justify-center relative w-full">
                                    <div class="absolute !gap-4 w-full text-center flex items-center justify-center hidden group-hover:block">
                                        <button @click="downloadKeyVisual" type="button" class="mr-3 inline-flex rounded-full bg-indigo-600 p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                            <SvgCollection svg-name="ArrowDownTray" class="h-5 w-5" aria-hidden="true" />
                                        </button>
                                        <button @click="selectNewKeyVisual" type="button" class="mr-3 inline-flex rounded-full bg-indigo-600 p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                            <PencilAltIcon
                                                class="h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                        </button>
                                        <button @click="deleteKeyVisual" type="button" class="inline-flex rounded-full bg-red-600 p-1 text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                                            <XIcon class="h-5 w-5 text-primaryText group-hover:text-white" aria-hidden="true" />
                                        </button>
                                    </div>
                                    <div class="text-center">
                                        <div class="cursor-pointer">
                                            <img src="">
                                            <img :src="'/storage/keyVisual/' + this.project.key_visual_path" alt="Aktuelles Key-Visual"
                                                 class="rounded-md w-full h-48">
                                            <input id="keyVisual-upload" ref="keyVisual"
                                                   name="file-upload" type="file" class="sr-only"
                                                   @change="updateKeyVisual"/>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex w-full items-center my-4">
                                    <h3 class="sDark"> Dokumente </h3>
                                </div>
                                <div
                                    v-if="this.$page.props.is_admin || access_budget.includes(this.$page.props.user.id)">
                                    <input
                                        @change="uploadChosenDocuments"
                                        class="hidden"
                                        ref="project_files"
                                        id="file"
                                        type="file"
                                        multiple
                                    />
                                    <div @click="selectNewFiles" @dragover.prevent
                                         @drop.stop.prevent="uploadDraggedDocuments($event)" class="mb-4 w-full flex justify-center items-center
                        border-buttonBlue border-dotted border-2 h-40 bg-colorOfAction p-2 cursor-pointer">
                                        <p class="text-buttonBlue font-bold text-center">Dokument zum Upload hierher
                                            ziehen
                                            <br>oder ins Feld klicken
                                        </p>
                                    </div>
                                    <jet-input-error :message="uploadDocumentFeedback"/>
                                </div>
                                <div>
                                    <div class="space-y-1"
                                         v-if="this.$page.props.is_admin || access_budget.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id)">
                                        <div v-for="project_file in project.project_files"
                                             class="cursor-pointer group flex items-center">
                                            <div :data-tooltip-target="project_file.name" class="flex truncate">
                                                <DocumentTextIcon class="h-5 w-5 flex-shrink-0" aria-hidden="true"/>
                                                <p @click="downloadFile(project_file)" class="ml-2 truncate">
                                                    {{ project_file.name }}</p>

                                                <XCircleIcon
                                                    v-if="this.$page.props.is_admin || access_budget.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id)"
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
                                    <div v-else class="xsDark">
                                        Keine Dateien vorhanden
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-6">
                    <div v-if="isBudgetTab" class="flex bg-lightBackgroundGray w-[95%]">
                        <BudgetComponent :table="budget.table" :project="project" :selectedCell="budget.selectedCell"
                                         :selectedRow="budget.selectedRow" :templates="budget.templates"
                                         :selected-sum-detail="budget.selectedSumDetail"
                                         :money-sources="moneySources" :budget-access="access_budget"
                                         :project-manager="projectManagerIds"></BudgetComponent>
                    </div>
                </div>
            </div>
        </div>
        <!-- File Delete Modal -->
        <jet-dialog-modal :show="deletingFile" @close="closeConfirmDeleteModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="font-black font-lexend text-primary text-3xl my-2">
                        Datei löschen
                    </div>
                    <XIcon @click="closeConfirmDeleteModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-error subpixel-antialiased">
                        Bist du sicher, dass du "{{ project_file.name }}" aus dem System löschen
                        möchtest?
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="removeFile(project_file)">
                            Löschen
                        </button>
                        <div class="flex my-auto">
                                            <span @click="closeConfirmDeleteModal"
                                                  class="xsLight cursor-pointer">Nein, doch nicht</span>
                        </div>
                    </div>
                </div>
            </template>
        </jet-dialog-modal>
        <!-- Projekt bearbeiten Modal-->
        <project-data-edit-modal
            :show="editingProject"
            @closed="closeEditProjectModal"
            :project="this.project"
            :group-projects="this.groupProjects"
            :current-group="this.currentGroup"
            :states="states"
        />


        <!-- Project History Modal -->
        <project-history-component
            @closed="closeProjectHistoryModal"
            v-if="showProjectHistory"
            :project_history="project.project_history"
            :access_budget="project.access_budget"
        ></project-history-component>

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
                                  class="xsLight cursor-pointer">Nein, doch nicht</span>
                        </div>
                    </div>
                </div>

            </template>

        </jet-dialog-modal>
        <BaseSidenav :show="show" @toggle="this.show =! this.show">
            <ProjectSidenav
                v-if="isBudgetTab"
                :project="project"
                :cost-center="project.cost_center"
                :copyright="project.copyright"
                :project-files="project.project_files"
                :contracts="project.contracts"
                :money-sources="projectMoneySources"
                :traits="{'categories': categories, 'genres': genres, 'sectors': sectors}"
            />
            <ProjectSecondSidenav
                v-else
                :project="project"
                :project-members="projectMembers"
                :project-categories="projectCategories"
                :project-genres="projectGenres"
                :project-sectors="projectSectors"
                :project-category-ids="projectCategoryIds"
                :project-genre-ids="projectGenreIds"
                :project-sector-ids="projectSectorIds"
                :categories="categories"
                :sectors="sectors"
                :genres="genres"
                :project-manager-ids="projectManagerIds"
            />
        </BaseSidenav>

    </app-layout>
</template>

<script>

import {Link, useForm} from "@inertiajs/inertia-vue3";
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Disclosure, DisclosureButton, DisclosurePanel,
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
    PlusSmIcon, ChevronRightIcon, PlusIcon
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
import AddButton from "@/Layouts/Components/AddButton";
import CalendarComponent from "@/Layouts/Components/CalendarComponent";
import ChecklistTeamComponent from "@/Layouts/Components/ChecklistTeamComponent";
import TagComponent from "@/Layouts/Components/TagComponent";
import BudgetComponent from "@/Layouts/Components/BudgetComponent.vue";
import BaseSidenav from "@/Layouts/Components/BaseSidenav";
import ProjectSidenav from "@/Layouts/Components/ProjectSidenav";
import Dropdown from "@/Jetstream/Dropdown.vue";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import ProjectHistoryComponent from "@/Layouts/Components/ProjectHistoryComponent.vue";
import ChecklistComponent from "@/Pages/Projects/Components/ChecklistComponent.vue";
import ProjectSecondSidenav from "@/Layouts/Components/ProjectSecondSidenav.vue";
import {nextTick} from "vue";
import ProjectDataEditModal from "@/Layouts/Components/ProjectDataEditModal.vue";

export default {
    name: "ProjectShow",
    props: ['projectMoneySources', 'RoomsWithAudience', 'firstEventInProject', 'lastEventInProject', 'eventTypes', 'opened_checklists', 'project_users', 'project', 'openTab', 'users', 'categories', 'projectCategoryIds', 'projectGenreIds', 'projectSectorIds', 'projectCategories', 'projectGenres', 'projectSectors', 'genres', 'sectors', 'checklist_templates', 'isMemberOfADepartment', 'budget', 'moneySources', 'projectGroups', 'currentGroup', 'groupProjects', 'states'],
    components: {
        ProjectSecondSidenav,
        ChecklistComponent,
        ProjectHistoryComponent,
        NewUserToolTip,
        Dropdown,
        BudgetComponent,
        ProjectSidenav,
        BaseSidenav,
        TagComponent,
        AddButton,
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
        CalendarComponent,
        ChecklistTeamComponent,
        Disclosure,
        DisclosurePanel,
        DisclosureButton,
        ProjectDataEditModal,
        PlusIcon
    },
    computed: {
        tabs() {
            if (this.$page.props.is_admin || this.access_budget.includes(this.$page.props.user.id) || this.projectManagerIds.includes(this.$page.props.user.id)) {
                return [
                    {name: 'Projektinformationen', href: '#', current: this.isInfoTab},
                    {name: 'Ablaufplan', href: '#', current: this.isScheduleTab},
                    {name: 'Checklisten', href: '#', current: this.isChecklistTab},
                    {name: 'Kommentare', href: '#', current: this.isCommentTab},
                    {name: 'Budget', href: '#', current: this.isBudgetTab}
                ]
            } else {
                return [
                    {name: 'Projektinformationen', href: '#', current: this.isInfoTab},
                    {name: 'Ablaufplan', href: '#', current: this.isScheduleTab},
                    {name: 'Checklisten', href: '#', current: this.isChecklistTab},
                    {name: 'Kommentare', href: '#', current: this.isCommentTab},
                ]
            }
        },
        historyTabs() {
            if (this.$page.props.is_admin || this.access_budget.includes(this.$page.props.user.id)) {
                return [
                    {name: 'Projekt', href: '#', current: this.showProjectHistoryTab},
                    {name: 'Budget', href: '#', current: this.showBudgetHistoryTab},
                ]
            } else {
                return [
                    {name: 'Projekt', href: '#', current: this.showProjectHistoryTab},
                ]
            }
        },
        projectMembers() {
            let projectMembers = [];
            this.project.users.forEach((user) => {
                if (this.project.project_managers.findIndex((projectManager) => projectManager.id === user.id) !== -1) {
                    user.is_manager = true;
                } else {
                    projectMembers.push(user);
                }
                if (this.project.access_budget.findIndex((access_budget) => access_budget.id === user.id) !== -1) {
                    user.access_budget = true;
                }
                if (this.project.write_auth.findIndex((writeAuth) => writeAuth.id === user.id) !== -1) {
                    user.can_write = true;
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
        access_budget: function () {
            let access_budget = [];
            this.project.access_budget.forEach(admin => {
                    access_budget.push(admin.id)
                }
            )
            return access_budget;
        },
        projectManagerIds: function () {
            let managerIdArray = [];
            this.project.project_managers.forEach(manager => {
                    managerIdArray.push(manager.id)
                }
            )
            return managerIdArray;
        },
        projectCanWriteIds: function () {
            let canWriteArray = [];
            this.project.write_auth.forEach(write => {
                    canWriteArray.push(write.id)
                }
            )
            return canWriteArray;
        },
    },
    data() {
        return {
            show: false,
            deletingFile: false,
            project_file: null,
            uploadDocumentFeedback: "",
            editingProject: false,
            isScheduleTab: this.openTab ? this.openTab === 'calendar' : false,
            isChecklistTab: this.openTab ? this.openTab === 'checklist' : false,
            isInfoTab: this.openTab ? this.openTab === 'info' : false,
            isBudgetTab: this.openTab ? this.openTab === 'budget' : false,
            isCommentTab: this.openTab ? this.openTab === 'comment' : false,
            showProjectHistory: false,
            commentHovered: null,
            projectToDelete: {},
            deletingProject: false,
            descriptionClicked: false,
            keyVisualForm: useForm({
                keyVisual: null,
            }),
            commentForm: useForm({
                text: "",
                user_id: this.$page.props.user.id,
                project_id: this.project.id
            }),
            documentForm: useForm({
                file: null
            }),
            selectedState: this.project.state ? this.project.state : null,
        }
    },
    mounted() {
        this.show = true;
        setTimeout(() => {
            this.show = false;
        }, 1000)
    },
    methods: {
        downloadKeyVisual(){
            let link = document.createElement('a');
            link.href = route('project.download.keyVisual', this.project.id);
            link.target = '_blank';
            link.click();
        },
        deleteKeyVisual(){
            this.$inertia.delete(route('project.delete.keyVisual', this.project.id))
        },
        async handleDescriptionClick() {

            this.descriptionClicked = true;

            await nextTick()

            this.$refs[`description-${this.project.id}`].select();
        },
        async handleTextClick(headline) {

            headline.clicked = !headline.clicked

            if (headline.clicked) {
                await nextTick()

                this.$refs[`text-${headline.id}`][0].select();
            }
        },
        changeHeadlineText(headline) {
            this.$inertia.patch(route('project_headlines.update.text', {
                project_headline: headline.id,
                project: this.project.id
            }), {text: headline.text})
        },
        updateDescription() {
            this.$inertia.patch(route('projects.update_description', this.project.id), {
                description: this.project.description
            }, {
                preserveScroll: true,
                preserveState: true
            });
            this.descriptionClicked = false;
        },
        selectNewKeyVisual() {
            this.$refs.keyVisual.click();
        },
        updateKeyVisual() {
            this.validateTypeAndUploadKeyVisual(this.$refs.keyVisual.files[0], 'keyVisual');
        },
        uploadDraggedKeyVisual(event) {
            this.validateTypeAndUploadKeyVisual(event.dataTransfer.files[0], 'keyVisual');
        },
        validateTypeAndUploadKeyVisual(file, type) {
            this.uploadDocumentFeedback = "";
            const allowedTypes = [
                "image/jpeg",
                "image/svg+xml",
                "image/png",
                "image/gif"
            ]

            if (allowedTypes.includes(file.type)) {
                this.keyVisualForm.keyVisual = file
                this.keyVisualForm.post(route('projects_key_visual.update', {project: this.project.id}));
            } else {
                this.uploadDocumentFeedback = "Es werden ausschließlich Logos und Illustrationen vom Typ .jpeg, .svg, .png und .gif akzeptiert."
            }
        },
        deleteProjectFromGroup(projectGroupId) {
            axios.delete(route('projects.group.delete'), {
                params: {
                    projectIdToDelete: projectGroupId.id,
                    groupId: this.project.id
                }
            }).finally(() => {
                this.projectGroups.splice(this.projectGroups.findIndex(index => index.id === projectGroupId.id), 1)
            })
        },
        selectNewFiles() {
            this.$refs.project_files.click();
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
        },
        changeTab(selectedTab) {
            this.isScheduleTab = false;
            this.isChecklistTab = false;
            this.isInfoTab = false;
            this.isBudgetTab = false;
            this.isCommentTab = false;
            if (selectedTab.name === 'Ablaufplan') {
                this.isScheduleTab = true;
            } else if (selectedTab.name === 'Checklisten') {
                this.isChecklistTab = true;
            } else if (selectedTab.name === 'Projektinformationen') {
                this.isInfoTab = true;
            } else if (selectedTab.name === 'Kommentare') {
                this.isCommentTab = true;
            } else {
                this.isBudgetTab = true;
            }
        },
        duplicateProject(project) {
            this.$inertia.post(`/projects/${project.id}/duplicate`);
        },
        addCommentToProject() {
            this.commentForm.post(route('comments.store'), {preserveState: true, preserveScroll: true});
            this.commentForm.text = "";
        },
        deleteCommentFromProject(comment) {
            this.$inertia.delete(`/comments/${comment.id}`, {preserveState: true, preserveScroll: true});
        },
        openProjectHistoryModal() {
            this.showProjectHistory = true;
        },
        closeProjectHistoryModal() {
            this.showProjectHistory = false;
        },
        checkUserAuth(user) {
            if (this.projectManagerIds.includes(this.$page.props.user.id)) {
                return true;
            }
            if (this.$page.props.user.id === user.id && user.project_management) {
                return true;
            }
            if (this.$page.props.is_admin) {
                return true;
            }
            return false;
        }
    },
}

</script>

<style scoped>
.whiteColumn {
    background-color: #FCFCFBFF;
}

.greenColumn {
    background-color: #50908E;
    border: 2px solid #1FC687;
}

.yellowColumn {
    background-color: #F0B54C;
}

.redColumn {
    background-color: #D84387;
}

.lightGreenColumn {
    background-color: #35A965;
}
</style>
