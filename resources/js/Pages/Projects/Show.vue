<template>
    <app-layout>
        <div class="max-w-screen-2xl my-12 pl-10 pr-10 flex flex-row">
            <div class="flex flex-col">
                <div class="w-full flex" v-if="this.project.key_visual_path">
                    <img :src="this.project.key_visual_path" alt="Aktuelles Key-Visual"
                         class="rounded-md h-40">
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


                        <span class="rounded-full items-center font-medium px-3 mt-2 text-sm ml-2 mb-1 h-8 inline-flex"
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
                                class="origin-top-left absolute left-0 mr-4 mt-2 w-72 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
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
                <div v-if="currentGroup">
                    <div class="flex mt-2 items-center">
                        <span v-if="!project.is_group">
                            <img src="/Svgs/IconSvgs/icon_group_black.svg" class="h-4 w-4 mr-2" aria-hidden="true"/>
                        </span>
                        Gehört zu <a :href="'/projects/' + currentGroup.id" class="text-buttonBlue ml-1">
                        {{ currentGroup?.name }}</a>
                    </div>
                </div>

                <div class="mt-3" v-if="projectGroups.length > 0">
                    <TagComponent v-for="projectGroup in projectGroups" :method="deleteProjectFromGroup"
                                  :displayed-text="projectGroup.name" :property="projectGroup"></TagComponent>
                </div>

                <div class="w-full mt-6 text-secondary subpixel-antialiased">
                    <div v-if="firstEventInProject && lastEventInProject">
                        Zeitraum/Öffnungszeiten: {{ firstEventInProject?.start_time }} <span v-if="firstEventInProject?.start_time">Uhr -</span>  {{ lastEventInProject?.end_time }} <span v-if="lastEventInProject?.end_time">Uhr</span>
                    </div>
                    <div v-if="RoomsWithAudience">
                        Termine mit Publikum in: <span v-for="(RoomWithAudience, index) in RoomsWithAudience">{{ RoomWithAudience }}, </span>
                    </div>
                    <div v-if="!RoomsWithAudience && !(firstEventInProject && lastEventInProject)">
                        Noch keine Termine innerhalb dieses Projektes
                    </div>
                </div>
                <div class="flex mt-5">
                    <div>
                        <TagComponent v-for="category in projectCategories" :method="deleteCategoryFromProject"
                                      :displayed-text="category.name" :property="category"
                                      :hide-x="true"></TagComponent>
                    </div>
                    <div>
                        <TagComponent v-for="genre in projectGenres" :method="deleteGenreFromProject"
                                      :displayed-text="genre.name" :property="genre" :hide-x="true"></TagComponent>
                    </div>
                    <div>
                        <TagComponent v-for="sector in projectSectors" :method="deleteSectorFromProject"
                                      :displayed-text="sector.name" :property="sector" :hide-x="true"></TagComponent>
                    </div>
                </div>
            </div>
            <!-- TODO: DAS HIER NOCH IN SIDEBAR -->
            <!--
            <div class="flex flex-wrap">
                <div class="flex mr-2 mt-8 flex-1 flex-wrap">
                    <h2 class="text-xl leading-6 font-bold font-lexend text-primary mb-3">Projektteam</h2>
                    <div class="flex"
                         v-if="this.$page.props.can.edit_projects || this.$page.props.is_admin || this.$page.props.can.project_management || projectCanWriteIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id)">
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
                        <img :data-tooltip-target="user?.id" :src="user?.profile_photo_url" :alt="user?.name"
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
                            <img :data-tooltip-target="user?.id" :src="user?.profile_photo_url" :alt="user?.name"
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
                                                     :src="user?.profile_photo_url"
                                                     alt=""/>
                                                <span class="ml-4">
                                                                {{ user?.first_name }} {{ user?.last_name }}
                                                            </span>
                                            </Link>
                                        </MenuItem>
                                    </MenuItems>
                                </transition>
                            </Menu>
                        </div>
                    </div>

                </div>

            </div>-->
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
                                    {{ project.description ? project.description : 'Hier klicken um Text hinzuzufügen' }}
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
                            <div class="ml-10">
                                <label class="block my-4 sDark">
                                    Key Visual </label>
                                <div
                                    class="flex col-span-2 w-full justify-center border-2 bg-stone-50 border-gray-300 cursor-pointer border-dashed rounded-md p-2"
                                    @click="selectNewKeyVisual"
                                    @dragover.prevent
                                    @drop.stop.prevent="uploadDraggedKeyVisual($event)">
                                    <div class="space-y-1 text-center">
                                        <div class="xsLight flex my-auto h-40 items-center"
                                             v-if="this.project.key_visual_path === null">
                                            Ziehe hier dein <br/> Key Visual hin
                                            <input id="keyVisual-upload" ref="keyVisual"
                                                   name="file-upload" type="file" class="sr-only"
                                                   @change="updateKeyVisual"/>
                                        </div>
                                        <div class="cursor-pointer" v-else-if="this.project.key_visual_path">
                                            <img :src="this.project.key_visual_path" alt="Aktuelles Key-Visual"
                                                 class="rounded-md h-40 w-80">
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
                            <div class="relative flex w-full">
                                <input id="projectName" v-model="form.name" type="text" placeholder="Projektname*"
                                       class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

                            </div>
                        </div>
                        <div class="flex mt-2">
                            <Menu as="div" class="inline-block text-left w-full">
                                <div>
                                    <MenuButton
                                        class=" h-12 border border-2 border-gray-300 w-full bg-white px-4 py-2 text-sm font-medium text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                    >
                                        <span class="float-left">Eigenschaften wählen</span>
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
                                        class="absolute overflow-y-auto h-48 mt-2 w-80 origin-top-left divide-y divide-gray-200 rounded-sm bg-primary ring-1 ring-black p-2 text-white opacity-100 z-50">
                                        <div class="mx-auto w-full max-w-md rounded-2xl bg-primary border-none mt-2">
                                            <Disclosure v-slot="{ open }">
                                                <DisclosureButton
                                                    class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                                >
                                                    <span
                                                        :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Kategorie</span>
                                                    <ChevronDownIcon
                                                        :class="open ? 'rotate-180 transform' : ''"
                                                        class="h-4 w-4 mt-0.5 text-white"
                                                    />
                                                </DisclosureButton>
                                                <DisclosurePanel class="pt-2 pb-2 text-sm text-white">

                                                    <div v-if="categories.length > 0"
                                                         v-for="category in categories"
                                                         :key="category.id"
                                                         class="flex w-full mb-2">
                                                        <input type="checkbox"
                                                               v-model="form.projectCategoryIds"
                                                               :value="category.id"
                                                               class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                                        <p :class="[form.projectCategoryIds.includes(category.id)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                           class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                            {{ category.name }}
                                                        </p>
                                                    </div>
                                                    <div v-else class="text-secondary">Noch keine Kategorien angelegt
                                                    </div>
                                                </DisclosurePanel>
                                            </Disclosure>
                                            <hr class="border-gray-500 mt-2 mb-2">
                                            <Disclosure v-slot="{ open }">
                                                <DisclosureButton
                                                    class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                                >
                                                    <span
                                                        :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Genre</span>
                                                    <ChevronDownIcon
                                                        :class="open ? 'rotate-180 transform' : ''"
                                                        class="h-4 w-4 mt-0.5 text-white"
                                                    />
                                                </DisclosureButton>
                                                <DisclosurePanel class="pt-2 pb-2 text-sm text-white">

                                                    <div v-if="genres.length > 0"
                                                         v-for="genre in genres"
                                                         :key="genre.id"
                                                         class="flex w-full mb-2">
                                                        <input type="checkbox"
                                                               v-model="form.projectGenreIds"
                                                               :value="genre.id"
                                                               class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                                        <p :class="[form.projectGenreIds.includes(genre.id)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                           class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                            {{ genre.name }}
                                                        </p>
                                                    </div>
                                                    <div v-else class="text-secondary">Noch keine Genres angelegt</div>
                                                </DisclosurePanel>
                                            </Disclosure>
                                            <hr class="border-gray-500 mt-2 mb-2">
                                            <Disclosure v-slot="{ open }">
                                                <DisclosureButton
                                                    class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                                >
                                                    <span
                                                        :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Bereich</span>
                                                    <ChevronDownIcon
                                                        :class="open ? 'rotate-180 transform' : ''"
                                                        class="h-4 w-4 mt-0.5 text-white"
                                                    />
                                                </DisclosureButton>
                                                <DisclosurePanel class="pt-2 pb-2 text-sm text-white">

                                                    <div v-if="sectors.length > 0"
                                                         v-for="sector in sectors"
                                                         :key="sector.id"
                                                         class="flex w-full mb-2">
                                                        <input type="checkbox"
                                                               v-model="form.projectSectorIds"
                                                               :value="sector.id"
                                                               class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                                        <p :class="[form.projectSectorIds.includes(sector.id)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                           class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                            {{ sector.name }}
                                                        </p>
                                                    </div>
                                                    <div v-else class="text-secondary">Noch keine Bereiche angelegt
                                                    </div>
                                                </DisclosurePanel>
                                            </Disclosure>
                                        </div>

                                    </MenuItems>
                                </transition>
                            </Menu>
                        </div>
                        <div class="flex mt-2"
                             v-if="projectCategories.length > 0 || projectGenres.length > 0 || projectSectors.length > 0">
                            <div>
                                <TagComponent v-for="category in projectCategories" :method="deleteCategoryFromProject"
                                              :displayed-text="category.name" :property="category"></TagComponent>
                            </div>
                            <div>
                                <TagComponent v-for="genre in projectGenres" :method="deleteGenreFromProject"
                                              :displayed-text="genre.name" :property="genre"></TagComponent>
                            </div>
                            <div>
                                <TagComponent v-for="sector in projectSectors" :method="deleteSectorFromProject"
                                              :displayed-text="sector.name" :property="sector"></TagComponent>
                            </div>
                        </div>
                        <div class="flex mt-2 w-full">
                            <Listbox as="div" class="flex w-full" v-model="selectedState">
                                <ListboxButton class="w-full text-left">
                                    <button
                                        class="w-full h-12 text-left border border-2 border-gray-300bg-white px-4 py-2 text-sm font-medium text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                        :class="selectedState"
                                        @click="openColor = !openColor">
                                        <span class="w-full" v-if="!selectedState">
                                            Wähle Projekt Status
                                        </span>
                                        <span v-else>
                                            {{ selectedState?.name }}
                                        </span>
                                    </button>
                                </ListboxButton>

                                <transition leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions
                                        class="absolute w-52 z-10 mt-12 bg-primary shadow-lg max-h-64 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                        <ListboxOption as="template" class=""
                                                       v-for="state in states"
                                                       :key="state"
                                                       :value="state" v-slot="{ active, selected }">
                                            <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 text-sm subpixel-antialiased']"
                                                @click="updateProjectState(state)">
                                                <div class="flex">
                                                    <span
                                                        class="rounded-full items-center font-medium px-3 mt-2 text-sm ml-3 mr-1 mb-1 h-8 inline-flex"
                                                        :class="state.color">
                                                        {{ state.name }}
                                                    </span>
                                                </div>
                                                <span
                                                    :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                                                    <CheckIcon v-if="selected"
                                                                               class="h-5 w-5 flex text-success"
                                                                               aria-hidden="true"/>
                                                                </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </Listbox>
                        </div>
                        <div class="mt-2">
                            <textarea placeholder="Kurzbeschreibung" v-model="form.description" rows="8"
                                      class="focus:border-primary placeholder-secondary border-2 w-full font-semibold border border-gray-300 "/>
                        </div>
                        <div>
                            <div class="flex items-center mb-2" v-if="!project.is_group">
                                <input id="hasGroup" type="checkbox" v-model="this.hasGroup"
                                       @change="removeSelectedGroup"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <label for="hasGroup" :class="this.hasGroup ? 'xsDark' : 'xsLight subpixel-antialiased'"
                                       class="ml-2">
                                    Gehört zu Projektgruppe
                                </label>
                            </div>
                            <div v-if="this.hasGroup" class="mb-2">
                                <Listbox as="div" v-model="this.selectedGroup" id="room">
                                    <ListboxButton class="inputMain w-full h-10 cursor-pointer truncate flex p-2">
                                        <div class="flex-grow flex text-left xsDark">
                                            {{
                                                this.selectedGroup?.name ? this.selectedGroup.name : 'Projektgruppe suchen'
                                            }}
                                        </div>
                                        <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                    </ListboxButton>
                                    <ListboxOptions class="w-5/6 bg-primary max-h-32 overflow-y-auto text-sm absolute">
                                        <ListboxOption v-for="projectGroup in groupProjects"
                                                       class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between "
                                                       :key="projectGroup.id"
                                                       :value="projectGroup"
                                                       v-slot="{ active, selected }">
                                            <div :class="[selected ? 'xsWhiteBold' : 'xsLight', 'flex']">
                                                {{ projectGroup.name }}
                                            </div>
                                            <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </Listbox>
                            </div>
                        </div>

                        <div class="w-full items-center text-center mt-2">
                            <AddButton
                                :class="[this.form.name === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                                class="mt-8 inline-flex items-center px-20 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-xl shadow-sm text-secondaryHover"
                                @click="editProject"
                                :disabled="this.form.name === ''" text="Speichern" mode="modal"/>
                        </div>
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
                    <div class="xsLight">
                        Tippe den Namen der Nutzer*innen, die du zum Team hinzufügen möchtest.
                        Die Nutzer*innen erhalten Lesezugriff auf dieses Projekt.
                        Weiterreichende Rechte kann nur die Projektleitung vergeben.
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
                              class="flex justify-between mt-4 mr-1 items-center font-bold text-primary border-1 border-b pb-3">
                            <div class="flex items-center w-64">
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
                                    <XCircleIcon class="ml-3 text-buttonBlue h-5 w-5 hover:text-error "/>
                                </button>
                            </div>
                            <div class="flex justify-between items-center my-1.5 h-5 w-80">
                                <div class="flex items-center justify-between" v-if="checkUserAuth(user)">

                                   <div class="flex">
                                        <input v-model="user.can_write"
                                               type="checkbox"
                                               class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                    <p :class="[user.can_write ? 'text-primary font-black' : 'text-secondary']"
                                       class="ml-4 my-auto text-sm">Schreibrecht</p>
                                   </div>
                                    <Dropdown :open="user.openedMenu" align="right" width="60" class="text-right">
                                        <template #trigger>
                                            <span class="inline-flex">
                                                <button @click="user.openedMenu = !user.openedMenu" type="button"
                                                        class="text-sm flex items-center ml-14 my-auto text-primary font-semibold focus:outline-none transition">
                                                    Weitere Rechte
                                                </button>
                                            </span>
                                        </template>

                                        <template #content>
                                            <div class="w-44 p-4">
                                                <div class="flex">
                                                    <input v-model="user.access_budget"
                                                           type="checkbox"
                                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                                <p
                                                    class=" ml-4 my-auto text-sm text-secondary">Budgetzugriff</p>
                                                </div>
                                                <div class="flex mt-4" v-if="user.project_management">
                                                    <input v-model="user.is_manager"
                                                           type="checkbox"
                                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                                <p
                                                    class="ml-4 my-auto text-sm text-secondary">Projektleitung</p>
                                                </div>
                                            </div>
                                        </template>
                                    </Dropdown>
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
                    <div class="w-full items-center text-center">
                        <AddButton @click="editProjectTeam" text="Speichern" mode="modal"
                                   class=" inline-flex mt-8 items-center px-12 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold tracking-wider text-lg shadow-sm text-secondaryHover"
                        />
                    </div>
                </div>
            </template>

        </jet-dialog-modal>

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

const number_of_participants = [
    {number: '1-10'},
    {number: '10-50'},
    {number: '50-100'},
    {number: '100-500'},
    {number: '>500'}
]


export default {
    name: "ProjectShow",
    props: ['projectMoneySources','RoomsWithAudience', 'firstEventInProject','lastEventInProject', 'eventTypes', 'opened_checklists', 'project_users', 'project', 'openTab', 'users', 'categories', 'projectCategoryIds', 'projectGenreIds', 'projectSectorIds', 'projectCategories', 'projectGenres', 'projectSectors', 'genres', 'sectors', 'checklist_templates', 'isMemberOfADepartment', 'budget', 'moneySources', 'projectGroups', 'currentGroup', 'groupProjects', 'states'],
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
        DisclosureButton
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
            showProjectHistoryTab: true,
            showBudgetHistoryTab: false,
            show: false,
            hasGroup: !!this.currentGroup,
            deletingFile: false,
            project_file: null,
            uploadDocumentFeedback: "",
            editingProject: false,
            selectedParticipantNumber: this.project.number_of_participants ? this.project.number_of_participants : '',
            isScheduleTab: this.openTab ? this.openTab === 'calendar' : false,
            isChecklistTab: this.openTab ? this.openTab === 'checklist' : false,
            isInfoTab: this.openTab ? this.openTab === 'info' : false,
            isBudgetTab: this.openTab ? this.openTab === 'budget' : false,
            isCommentTab: this.openTab ? this.openTab === 'comment' : false,
            editingTeam: false,
            department_and_user_query: "",
            department_search_results: [],
            department_and_user_search_results: [],
            assignedUsers: this.project.users ? this.project.users : [],
            assignedDepartments: this.project.departments ? this.project.departments : [],
            showMenu: null,
            showProjectHistory: false,
            commentHovered: null,
            projectToDelete: {},
            deletingProject: false,
            selectedGroup: null,
            descriptionClicked: false,
            form: useForm({
                name: this.project.name,
                description: this.project.description,
                number_of_participants: this.project.number_of_participants,
                assigned_user_ids: {},
                assigned_departments: [],
                projectCategoryIds: this.projectCategoryIds,
                projectGenreIds: this.projectGenreIds,
                projectSectorIds: this.projectSectorIds,
                selectedGroup: null
            }),
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
            attributeForm: useForm({}),
            selectedState: this.project.state ? this.project.state : null,
            openColor: false
        }
    },
    mounted() {
        this.selectedGroup = this.currentGroup.id ? this.currentGroup.id : null
    },
    methods: {
        updateProjectState(state) {
            this.$inertia.patch(route('update.project.state', this.project.id), {
                state: state.id
            })
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
                description:this.project.description
            }, {
                preserveScroll: true,
                preserveState: true
            });
            this.descriptionClicked = false;
        },
        changeHistoryTabs(selectedTab) {
            this.showProjectHistoryTab = false;
            this.showBudgetHistoryTab = false;
            if (selectedTab.name === 'Projekt') {
                this.showProjectHistoryTab = true;
            } else {
                this.showBudgetHistoryTab = true;
            }
        },
        selectNewKeyVisual() {
            this.$refs.keyVisual.click();
        },
        updateKeyVisual() {
            this.validateTypeAndUploadKeyVisual(this.$refs.keyVisual.files[0], 'keyVisual');
        },
        uploadDraggedKeyVisual(event) {
            console.log(event)
            this.validateTypeAndUploadKeyVisual(event.dataTransfer.files[0], 'keyVisual');
        },
        validateTypeAndUploadKeyVisual(file, type) {
            this.uploadDocumentFeedback = "";
            console.log(file)
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
        removeSelectedGroup() {
            if (!this.hasGroup) {
                this.selectedGroup = null;
            }
        },
        formatDate(date, time) {
            if (date === null || time === null) return null;
            return new Date((new Date(date + ' ' + time)).getTime() - ((new Date(date + ' ' + time)).getTimezoneOffset() * 60000)).toISOString();
        },
        deleteCategoryFromProject(category) {
            this.form.projectCategoryIds.splice(this.form.projectCategoryIds.indexOf(category.id), 1)
            this.assignedUsers.forEach(user => {
                this.form.assigned_user_ids[user.id] = {
                    access_budget: user.access_budget,
                    is_manager: user.is_manager,
                    can_write: user.can_write
                };
            })
            this.assignedDepartments.forEach(department => {
                this.form.assigned_departments.push(department);
            })
            this.form.patch(route('projects.update', {project: this.project.id}));
        },
        deleteGenreFromProject(genre) {
            this.form.projectGenreIds.splice(this.form.projectGenreIds.indexOf(genre.id), 1)
            this.assignedUsers.forEach(user => {
                this.form.assigned_user_ids[user.id] = {
                    access_budget: user.access_budget,
                    is_manager: user.is_manager,
                    can_write: user.can_write
                };
            })
            this.assignedDepartments.forEach(department => {
                this.form.assigned_departments.push(department);
            })
            this.form.patch(route('projects.update', {project: this.project.id}));
        },
        deleteSectorFromProject(sector) {
            this.form.projectSectorIds.splice(this.form.projectSectorIds.indexOf(sector.id), 1)
            this.assignedUsers.forEach(user => {
                this.form.assigned_user_ids[user.id] = {
                    access_budget: user.access_budget,
                    is_manager: user.is_manager,
                    can_write: user.can_write
                };
            })
            this.assignedDepartments.forEach(department => {
                this.form.assigned_departments.push(department);
            })
            this.form.patch(route('projects.update', {project: this.project.id}));
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
        openEditProjectTeamModal() {
            this.editingTeam = true;
        },
        closeEditProjectTeamModal() {
            this.editingTeam = false;
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
            this.categories.forEach((category) => {
                category.checked = false;
            })
            this.form.assigned_departments = [];
            this.form.assigned_user_ids = {};
        },
        editProject() {

            this.form.number_of_participants = this.selectedParticipantNumber;
            this.assignedUsers.forEach(user => {
                this.form.assigned_user_ids[user.id] = {
                    access_budget: user.access_budget,
                    is_manager: user.is_manager,
                    can_write: user.can_write
                };
            })
            this.assignedDepartments.forEach(department => {
                this.form.assigned_departments.push(department);
            })
            this.form.selectedGroup = this.selectedGroup;
            this.form.patch(route('projects.update', {project: this.project.id}));
            this.closeEditProjectModal();
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
        deleteUserFromProjectTeam(user) {
            if (this.assignedUsers.includes(user)) {
                this.assignedUsers.splice(this.assignedUsers.indexOf(user), 1);
            }
        },
        deleteDepartmentFromProjectTeam(department) {
            this.assignedDepartments.splice(this.assignedDepartments.indexOf(department), 1);
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
                this.form.assigned_user_ids[user.id] = {
                    access_budget: user.access_budget,
                    is_manager: user.is_manager,
                    can_write: user.can_write
                };
            })
            this.form.assigned_departments = [];
            this.assignedDepartments.forEach(department => {
                this.form.assigned_departments.push(department);
            })
            this.form.patch(route('projects.update', {project: this.project.id}));
            this.closeEditProjectTeamModal();
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
    watch: {
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
        },
    },
    setup() {
        return {
            number_of_participants,
        }
    }
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
