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
            <span v-if="project.public_checklists.length === 0 && project.private_checklists.length === 0" class="text-secondary subpixel-antialiased text-xs mb-4">
                Noch keine Checklisten hinzugefügt. Erstelle Checklisten mit Aufgaben. Die Checklisten kannst du Teams zuordnen. Nutze Vorlagen und spare Zeit.
            </span>
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
                                    <!-- hier User ansicht -->
                                    <div class="flex -mr-3"
                                         v-for="(user, index) in checklist.users">
                                        <img class="h-10 w-10 mr-2 object-cover rounded-full border border-2 border-white"
                                             :class="index !== 0 ? '-ml-2' : ''"
                                             :src="user.profile_photo_url"
                                             alt=""/>
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
                                                            Nutzer*innen zuweisen
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
                                                <div class="flex w-full items-center">
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
                                                    <p class="ml-4 my-auto text-lg font-black xsDark font-semibold"
                                                       :class="element.done ? 'text-secondary line-through' : 'text-primary'">
                                                        {{ element.name }}</p>
                                                    <span v-if="!element.done && element.deadline" class="ml-2 my-auto text-sm subpixel-antialiased" :class="Date.parse(element.deadline_dt_local) < new Date().getTime()? 'text-error subpixel-antialiased' : ''">
                                                        bis {{element.deadline }}
                                                    </span>

                                                    <span v-if="element.done && element.done_by_user" class="ml-2 flex my-auto items-center text-sm text-secondary">
                                                        {{ element.done_at }}
                                                        <img
                                                            :data-tooltip-target="element.done_by_user.id"
                                                            v-if="element.done_by_user"
                                                            :src="element.done_by_user.profile_photo_url"
                                                            :alt="element.done_by_user.name"
                                                            class="rounded-full ml-2 my-auto h-7 w-7 object-cover"/>
                                                        <UserTooltip :user="element.done_by_user"/>
                                                    </span>
                                                    <span class="ml-3 flex">
                                                        <span class="flex -mr-3" v-for="user in element.users">
                                                        <NewUserToolTip :id="user.id" :user="user" height="5" width="5"/>
                                                    </span>
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
                                                <div class="ml-16 text-sm text-secondary subpixel-antialiased" :class="element.done ? 'text-secondary line-through' : 'text-primary'">
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
                                                    <p class="ml-4 my-auto text-lg font-black text-sm xsDark"
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

    <!-- Change Checklist Teams Modal -->
    <AddChecklistUserModal
        :checklistId="checklistToEdit?.id"
        :users="checklistToEdit?.users"
        :editingChecklistTeams="editingChecklistTeams"
        @closed="closeEditChecklistTeamsModal"
    />

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
                            <input type="text" v-model="taskForm.name" placeholder="Aufgabe*"
                                   class="placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-2 block w-full border-gray-300"/>
                        </div>
                    </div>
                    <div class="sm:w-1/2 my-2">
                        <label for="deadlineDate" class="xxsLight">Zu erledigen bis?</label>
                        <div class="w-full flex">
                            <input v-model="taskForm.deadlineDate"
                                   id="deadlineDate"
                                   type="date"
                                   class="border-gray-300 inputMain xsDark placeholder-secondary disabled:border-none flex-grow"/>
                            <input v-model="taskForm.deadlineTime"
                                   id="deadlineTime"
                                   type="time"
                                   class="border-gray-300 inputMain xsDark placeholder-secondary  disabled:border-none"/>
                        </div>
                        <p class="text-xs text-red-800">{{ error?.start?.join('. ') }}</p>
                    </div>
                    <div class="mt-4 mr-4">
                                            <textarea
                                                placeholder="Kommentar"
                                                v-model="taskForm.description" rows="3"
                                                class="placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-2 block w-full border-gray-300"/>
                    </div>
                    <div class="w-full items-center text-center">
                        <AddButton
                            :class="[this.taskForm.name === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                            class="mt-8 inline-flex items-center px-20 py-3 border bg-primary hover:bg-primaryHover
                            focus:outline-none border-transparent text-base font-bold text-lg tracking-wider shadow-sm
                            text-secondaryHover"
                            @click="addTask"
                            :disabled="this.taskForm.name === ''" text="Hinzufügen" mode="modal"/>
                    </div>
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
                    <div class="mb-2">
                        <div class="relative flex w-full">
                            <input id="edit_task_name" v-model="taskToEditForm.name" type="text"
                                   class="mt-4 p-4 inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
                                   placeholder="Aufgabe"/>
                        </div>
                    </div>
                    <div class="mb-2">
                        <input
                            v-model="taskToEditForm.deadline" id="datePickerEdit"
                            placeholder="Zu erledigen bis?" type="datetime-local"
                            class="p-4 inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                    </div>
                    <div class="mb-2">
                        <div class="relative w-full">
                            <div class="w-full">
                                <input id="userSearch" v-model="user_query" type="text" autocomplete="off"
                                       placeholder="Wer ist zuständig für die Aufgabe?"
                                       class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                            </div>
                            <transition leave-active-class="transition ease-in duration-100"
                                        leave-from-class="opacity-100"
                                        leave-to-class="opacity-0">
                                <div v-if="user_search_results.length > 0 && user_query.length > 0"
                                     class="absolute z-10 mt-1 w-full max-h-60 bg-primary shadow-lg
                                                        text-base ring-1 ring-black ring-opacity-5
                                                        overflow-auto focus:outline-none sm:text-sm">
                                    <div class="border-gray-200">
                                        <div v-for="(user, index) in user_search_results" :key="index"
                                             class="flex items-center cursor-pointer">
                                            <div class="flex-1 text-sm py-4">
                                                <p @click="addUserToTask(user)"
                                                   class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                    {{ user.first_name }} {{ user.last_name }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </transition>
                        </div>
                        <div v-if="usersToAdd.length > 0" class="mt-2 mb-4 flex items-center">
                                        <span v-for="(user,index) in usersToAdd"
                                              class="flex mr-5 rounded-full items-center font-bold text-primary">
                                        <div class="flex items-center">
                                            <img class="flex h-11 w-11 rounded-full object-cover"
                                                 :src="user.profile_photo_url"
                                                 alt=""/>
                                            <span class="flex ml-4 sDark">
                                            {{ user.first_name }} {{ user.last_name }}
                                            </span>
                                            <button type="button" @click="deleteUserFromTask(index)">
                                                <span class="sr-only">User aus der Aufgabe entfernen</span>
                                                <XIcon
                                                    class="ml-2 h-4 w-4 p-0.5 hover:text-error rounded-full bg-buttonBlue text-white border-0 "/>
                                            </button>
                                        </div>
                                        </span>
                        </div>
                    </div>
                    <div class="mb-4">
                                            <textarea
                                                placeholder="Kommentar"
                                                v-model="taskToEditForm.description" rows="3"
                                                class="placeholder-secondary resize-none focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 w-full font-semibold border "/>
                    </div>
                    <div class="flex justify-center">
                        <AddButton text="Speichern" @click="editTask" :disabled="taskToEditForm.name === ''"></AddButton>
                    </div>
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
                                  class="xsLight cursor-pointer">Nein, doch nicht</span>
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

                    <div class="w-full items-center text-center">

                        <AddButton :class="[editChecklistForm.name.length === 0 ?
                    'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                                   class="mt-4 inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold shadow-sm text-secondaryHover"
                                   @click="editChecklist" :disabled="editChecklistForm.name.length === 0"
                                   text="Speichern" mode="modal"
                        />
                    </div>
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

                            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
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
                                der Checkliste*</label>
                        </div>
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

                    <div class="w-full items-center text-center">
                        <AddButton :class="[checklistForm.name.length === 0 && !selectedTemplate.id ?
                                       'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                                   class="mt-4 items-center px-20 py-3 border border-transparent
                            text-base font-bold shadow-sm text-secondaryHover"
                                   @click="addChecklist"
                                   :disabled="checklistForm.name.length === 0 && !selectedTemplate.id"
                                   text="Anlegen" mode="modal"/>
                    </div>
                </div>
            </div>
        </template>

    </jet-dialog-modal>
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
import {Link, useForm} from "@inertiajs/inertia-vue3";
import CalendarComponent from "@/Layouts/Components/CalendarComponent.vue";
import ChecklistTeamComponent from "@/Layouts/Components/ChecklistTeamComponent.vue";
import AddChecklistUserModal from "@/Pages/Projects/Components/AddChecklistUserModal.vue";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";

export default {
    name: "ChecklistComponent",
    props: ['project', 'opened_checklists', 'projectCanWriteIds', 'projectManagerIds', 'checklist_templates'],
    components: {
        NewUserToolTip,
        AddChecklistUserModal,
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
    data(){
        return {
            error: [],
            allDoneTasks: [],
            checklist_assigned_users: [],
            selectedTemplate: {name: '', id: null},
            checklistToEdit: null,
            editingChecklist: false,
            editingChecklistTeams: false,
            addingTask: false,
            addingChecklist: false,
            dragging: false,
            deletingChecklist: false,
            checklistToDelete: {},
            editingTask: false,
            showMenu: null,
            user_search_results: [],
            user_query: '',
            usersToAdd: [],
            checklistForm: useForm({
                name: "",
                project_id: this.project.id,
                tasks: [],
                assigned_user_ids: [],
                private: false,
                template_id: null,
                user_id: null
            }),
            editChecklistForm: useForm({
                id: null,
                name: "",
                private: false,
                user_id: null,
                assigned_user_ids: [],
            }),
            taskForm: useForm({
                name: "",
                description: "",
                deadline: null,
                deadlineDate: null,
                deadlineTime: null,
                checklist_id: null,
            }),
            taskToEditForm: useForm({
                id: '',
                name: "",
                description: "",
                deadline: null,
                users: []
            }),
            duplicateForm: useForm({
                name: "",
                project_id: this.project.id,
                tasks: [],
                assigned_user_ids: [],
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
        }
    },
    methods: {
        addUserToTask(user) {
            if(!this.usersToAdd.find(userToAdd => userToAdd.id === user.id)){
                this.usersToAdd.push(user);
            }
            this.user_query = '';
        },
        deleteUserFromTask(index) {
            this.usersToAdd.splice(index, 1);
        },
        closeDeleteChecklistModal() {
            this.deletingChecklist = false;
            this.checklistToDelete = {};
        },
        formatDate(date, time) {
            if (date === null || time === null) return null;
            return new Date((new Date(date + ' ' + time)).getTime() - ((new Date(date + ' ' + time)).getTimezoneOffset() * 60000)).toISOString();
        },
        deleteChecklistFromProject() {
            if (this.project.public_checklists.findIndex((publicChecklist) => publicChecklist.id === this.checklistToDelete.id) !== -1) {
                this.project.public_checklists.splice(this.project.public_checklists.indexOf(this.checklistToDelete), 1);
                this.$inertia.delete(`/checklists/${this.checklistToDelete.id}`, {
                    preserveState: true,
                    preserveScroll: true
                });
                this.closeDeleteChecklistModal();
                return;
            }
            if (this.project.private_checklists.findIndex((privateChecklist) => privateChecklist.id === this.checklistToDelete.id) !== -1) {
                this.project.private_checklists.splice(this.project.private_checklists.indexOf(this.checklistToDelete), 1);
                this.$inertia.delete(`/checklists/${this.checklistToDelete.id}`, {
                    preserveState: true,
                    preserveScroll: true
                });
                this.closeDeleteChecklistModal();
            }
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
            this.$inertia.delete(`/tasks/${task.id}`, {preserveState: true, preserveScroll: true});
        },
        duplicateChecklist(checklist) {
            let userIds = [];
            this.duplicateForm.name = checklist.name + " (Kopie)";
            this.duplicateForm.tasks = checklist.tasks;
            if (this.project.private_checklists.findIndex((privateChecklist) => privateChecklist.id === checklist.id) !== -1) {
                this.duplicateForm.user_id = this.$page.props.user.id;
            } else {
                checklist.users.forEach((user) => {
                    userIds.push(user.id);
                })
                this.duplicateForm.assigned_user_ids = userIds
            }
            this.duplicateForm.post(route('checklists.store'), {preserveState: true, preserveScroll: true})
            this.duplicateForm.name = "";
            this.duplicateForm.tasks = [];
            this.duplicateForm.users = [];
            this.duplicateForm.user_id = null;
        },
        openDeleteChecklistModal(checklistToDelete) {
            this.checklistToDelete = checklistToDelete;
            this.deletingChecklist = true;
        },
        openEditChecklistModal(checklist) {
            this.editChecklistForm.id = checklist.id;
            this.editChecklistForm.name = checklist.name;
            this.editChecklistForm.private = !checklist.users;
            if (checklist.users) {
                this.editChecklistForm.assigned_user_ids = [];
                checklist.users.forEach((user) => {
                    this.editChecklistForm.assigned_user_ids.push(user.id);
                })
            }
            this.editingChecklist = true;
        },
        createTemplateFromChecklist(checklist) {
            console.log('HEHE');
            this.templateForm.checklist_id = checklist.id;
            this.templateForm.post(route('checklist_templates.store'));
        },
        openEditTaskModal(task) {
            this.taskToEditForm.id = task.id;
            this.taskToEditForm.name = task.name;
            this.taskToEditForm.deadline = task.deadline_dt_local;
            this.taskToEditForm.description = task.description;
            this.usersToAdd = task.users;
            this.editingTask = true;
        },
        closeEditTaskModal() {
            this.editingTask = false;
            this.taskToEditForm.id = null;
            this.taskToEditForm.name = "";
            this.taskToEditForm.deadline = null;
            this.taskToEditForm.description = "";

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
            this.taskForm.deadlineDate = null;
            this.taskForm.deadlineTime = null;
            this.addingTask = false;
        },
        addTask() {
            if (this.taskForm.deadlineDate) {
                if (this.taskForm.deadlineTime === null) {
                    this.taskForm.deadlineTime = '00:00';
                }
                this.taskForm.deadline = this.formatDate(this.taskForm.deadlineDate, this.taskForm.deadlineTime);
            }
            this.taskForm.post(route('tasks.store'), {preserveState: true, preserveScroll: true});
            this.closeAddTaskModal();
        },
        editTask() {
            this.usersToAdd.forEach((user) => {
                this.taskToEditForm.users.push(user.id);
            })
            this.taskToEditForm.patch(route('tasks.update', {task: this.taskToEditForm.id},), {
                preserveState: true,
                preserveScroll: true
            });
            this.closeEditTaskModal();
        },
        closeEditChecklistModal() {
            this.editingChecklist = false;
            this.editChecklistForm.id = null;
            this.editChecklistForm.name = "";
            this.editChecklistForm.private = false;
            this.editChecklistForm.assigned_user_ids = null;
            this.editChecklistForm.user_id = null;
        },
        editChecklist() {
            if (this.editChecklistForm.private) {
                this.editChecklistForm.user_id = this.$page.props.user.id;
                this.editChecklistForm.assigned_user_ids = [];
            } else {
                this.editChecklistForm.user_id = null;

            }
            this.editChecklistForm.patch(route('checklists.update', {checklist: this.editChecklistForm.id}, {
                preserveState: true,
                preserveScroll: true
            }));
            this.closeEditChecklistModal();
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
        openEditChecklistTeamsModal(checklist) {
            this.checklistToEdit = checklist;
            this.checklist_assigned_users = checklist.users;
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
            this.checklist_assigned_users = [];
            this.checklistForm.assigned_user_ids = [];
        },
        addChecklist() {

            if (this.selectedTemplate.id !== null) {
                this.checklistForm.template_id = this.selectedTemplate.id;
                this.checklistForm.post(route('checklists.store'), {preserveState: true, preserveScroll: true});
                this.checklistForm.template_id = null;
                this.closeAddChecklistModal();
            } else {
                if (this.checklistForm.private === true) {
                    this.checklistForm.user_id = this.$page.props.user.id;
                }
                this.checklistForm.post(route('checklists.store'), {preserveState: true, preserveScroll: true});
                this.closeAddChecklistModal();
            }

        },
        changeChecklistStatus(checklist) {

            if (!this.opened_checklists.includes(checklist.id)) {
                const openedChecklists = this.opened_checklists;

                openedChecklists.push(checklist.id)

                this.$inertia.patch(`/users/${this.$page.props.user.id}/checklists`, {"opened_checklists": openedChecklists}, {
                    preserveState: true,
                    preserveScroll: true
                });
            } else {
                const filteredList = this.opened_checklists.filter(function (value) {
                    return value !== checklist.id;
                })
                this.$inertia.patch(`/users/${this.$page.props.user.id}/checklists`, {"opened_checklists": filteredList}, {
                    preserveState: true,
                    preserveScroll: true
                });
            }
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
    },
    watch: {
        user_query: {
            handler() {
                if (this.user_query.length > 0) {
                    axios.get('/project/user/search', {
                        params: {query: this.user_query, projectId: this.project.id}
                    }).then(response => {
                        this.user_search_results = response.data
                    })
                }
            },
            deep: true
        },
    }
}
</script>

<style scoped>

</style>
