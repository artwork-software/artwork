<template>
    <div class="col-span-2">
        <div class="flex w-full items-center mb-8 ">
            <h2 class="text-xl leading-6 font-bold font-lexend text-primary"> Checklisten </h2>
            <div class="flex items-center"
                 v-if="this.$page.props.can.edit_projects || this.$page.props.is_admin || projectCanWriteIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id)">
                <AddButton @click="openAddChecklistModal" text="Neue Checkliste" mode="page"/>
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
                         class="flex w-full bg-white my-2 inputMain">
                        {{ checklist.user_id }}
                        <button class="bg-buttonBlue flex"
                                @click="changeChecklistStatus(checklist)">
                            <ChevronUpIcon v-if="this.opened_checklists.includes(checklist.id)"
                                           class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
                            <ChevronDownIcon v-else
                                             class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
                        </button>
                        <div :class="this.opened_checklists.includes(checklist.id) ? 'mt-4' : ''"
                             class="flex w-full ml-4 flex-wrap p-4">
                            <div class="flex justify-between w-full my-auto items-center">
                                <div>
                                                    <span
                                                        class="text-xl ml-6 my-auto leading-6 font-bold font-lexend text-primary">
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
                                        v-if="this.$page.props.can.create_and_edit_projects || this.$page.props.is_admin || this.$page.props.can.admin_projects || projectCanWriteIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id)"
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
                                     v-if="this.$page.props.can.edit_projects || this.$page.props.is_admin || projectCanWriteIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id)">
                                    <div>
                                        <AddButton @click="openAddTaskModal(checklist)"
                                                   text="Neue Aufgabe" mode="page"/>
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
                                                        v-if="this.$page.props.can.edit_projects || this.$page.props.is_admin  || projectCanWriteIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id)"
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
                         class="flex w-full bg-white my-2 inputMain">
                        <button class="bg-buttonBlue flex"
                                @click="changeChecklistStatus(checklist)">
                            <ChevronUpIcon v-if="this.opened_checklists.includes(checklist.id)"
                                           class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
                            <ChevronDownIcon v-else
                                             class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
                        </button>
                        <div class="flex w-full ml-4 flex-wrap p-4">
                            <div class="flex justify-between w-full">
                                <div class="my-auto">
                                        <span class="ml-6 text-xl leading-6 flex font-bold font-lexend text-primary">
                                        {{ checklist.name }} <EyeIcon class="h-6 w-6 ml-3 text-primary"></EyeIcon> <p
                                            class="text-primary text-sm my-auto ml-1">Privat</p>
                                        </span>
                                </div>
                                <div class="flex items-center -mr-3">
                                    <img class="h-9 w-9 rounded-full object-cover"
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
                                <div class="">
                                    <AddButton @click="openAddTaskModal(checklist)" text="Neue Aufgabe"
                                               mode="page"/>
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
</template>

<script>
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import AddButton from "@/Layouts/Components/AddButton.vue";
import TeamTooltip from "@/Layouts/Components/TeamTooltip.vue";
import CategoryIconCollection from "@/Layouts/Components/EventTypeIconCollection.vue";
import Checkbox from "@/Jetstream/Checkbox.vue";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    Disclosure, DisclosureButton, DisclosurePanel,
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems, Switch
} from "@headlessui/vue";
import {
    DocumentTextIcon,
    DuplicateIcon,
    ExclamationIcon,
    EyeIcon,
    PencilAltIcon,
    TrashIcon,
    XIcon
} from "@heroicons/vue/outline";
import {
    CheckIcon,
    ChevronDownIcon, ChevronRightIcon,
    ChevronUpIcon,
    DotsVerticalIcon,
    PlusSmIcon,
    XCircleIcon
} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import JetButton from "@/Jetstream/Button.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import JetInput from "@/Jetstream/Input.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import draggable from "vuedraggable";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import {Link} from "@inertiajs/inertia-vue3";
import CalendarComponent from "@/Layouts/Components/CalendarComponent.vue";
import ChecklistTeamComponent from "@/Layouts/Components/ChecklistTeamComponent.vue";

export default {
    name: "ChecklistComponent",
    components: {
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
    }
}
</script>

<style scoped>

</style>
