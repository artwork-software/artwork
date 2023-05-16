<template>
    <app-layout>
        <div class="py-4">
            <div class="max-w-screen mb-40 my-12 flex flex-row ml-14 mr-14">
                <div class="flex flex-1 flex-wrap">
                    <div class="w-full flex items-center justify-between">
                        <div>
                            <p class="items-center flex mr-2 headline1 mb-3">Projekte</p>
                            <div class="w-48">
                                <Listbox as="div" class="flex">
                                    <ListboxButton @click="openCloseMenu"
                                                   class="bg-white w-full relative py-2 cursor-pointer focus:outline-none sm:text-sm border border-2">
                                        <div class="flex justify-between items-center my-auto w-44 h-6 ml-3">
                                            Filter
                                            <span
                                                class="inset-y-0 flex items-center pr-2 pointer-events-none">
                                                <ChevronDownIcon class="h-5 w-5" aria-hidden="true"/>
                                             </span>
                                        </div>
                                    </ListboxButton>
                                    <transition leave-active-class="transition ease-in duration-100"
                                                leave-from-class="opacity-100" leave-to-class="opacity-0">
                                        <ListboxOptions static v-show="openedMenu"
                                                        class="absolute w-80 z-10 mt-12 bg-primary shadow-lg p-3 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">

                                            <ListboxOption as="div" class="max-h-8 flex justify-end mb-3">
                                                <span class="xxsLight cursor-pointer"
                                                      @click="removeFilter">Zurücksetzen</span>
                                            </ListboxOption>
                                            <ListboxOption>
                                                <SwitchGroup as="div" class="flex items-center">
                                                    <Switch v-model="enabled"
                                                            :class="[enabled ? 'bg-green-400' : 'bg-gray-200', 'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2']">
                                                        <span class="sr-only">Use setting</span>
                                                        <span aria-hidden="true"
                                                              :class="[enabled ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"/>
                                                    </Switch>
                                                    <SwitchLabel as="span" class="ml-3 xxsLight">
                                                        Nur meine Projekte anzeigen
                                                    </SwitchLabel>
                                                </SwitchGroup>
                                            </ListboxOption>
                                            <ListboxOption as="div" class="max-h-8 mb-3 mt-3">
                                                <div class="flex">
                                                    <input v-model="showProjectGroups"
                                                           type="checkbox"
                                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                                    <p class=" ml-4 my-auto text-sm text-secondary">Projektgruppen</p>
                                                </div>
                                            </ListboxOption>
                                            <ListboxOption as="div" class="max-h-8 mb-3 mt-3">
                                                <div class="flex">
                                                    <input v-model="showProjects"
                                                           type="checkbox"
                                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                                    <p class=" ml-4 my-auto text-sm text-secondary">Projekte</p>
                                                </div>
                                            </ListboxOption>
                                            <ListboxOption as="div" class="mb-3 mt-3">
                                                <div class="flex justify-between xsLight mb-3"
                                                     @click="showProjectStateFilter = !showProjectStateFilter">
                                                    Projektstatus
                                                    <ChevronDownIcon class="h-5 w-5" v-if="!showProjectStateFilter"
                                                                     aria-hidden="true"/>
                                                    <ChevronUpIcon class="h-5 w-5" v-if="showProjectStateFilter"
                                                                   aria-hidden="true"/>
                                                </div>
                                                <div v-if="showProjectStateFilter">
                                                    <div class="flex mb-3" v-for="state in states">
                                                        <input v-model="state.clicked" @change="addStateToFilter(state)"
                                                               type="checkbox"
                                                               class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                                        <p class=" ml-4 my-auto text-sm text-secondary">{{
                                                                state.name
                                                            }}</p>
                                                    </div>
                                                </div>

                                            </ListboxOption>

                                            <ListboxOption as="div" class="mt-6">
                                                <div class="xxsLight cursor-pointer" @click="openedMenu = !openedMenu">
                                                    Filter schließen
                                                </div>
                                            </ListboxOption>
                                        </ListboxOptions>
                                    </transition>
                                </Listbox>
                            </div>

                            <div id="selectedFilter" class="mt-3">
                                <span v-if="enabled"
                                      class="rounded-full items-center font-medium text-tagText border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
                                    Meine Projekte
                                    <button type="button" @click="enabled = !enabled">
                                        <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
                                    </button>
                                </span>
                                <span v-if="showProjectGroups"
                                      class="rounded-full items-center font-medium text-tagText border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
                                    Projektgruppen
                                    <button type="button" @click="showProjectGroups = !showProjectGroups">
                                        <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
                                    </button>
                                </span>
                                <span v-if="showProjects"
                                      class="rounded-full items-center font-medium text-tagText border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
                                    Projekte
                                    <button type="button" @click="showProjects = !showProjects">
                                        <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
                                    </button>
                                </span>
                                <span v-for="state in states">
                                    <span v-if="state.clicked"
                                          class="rounded-full items-center font-medium text-tagText border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
                                    {{ state.name }}
                                    <button type="button"
                                            @click="this.projectStateFilter.splice(this.projectStateFilter.indexOf(state),1); state.clicked = false">
                                        <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
                                    </button>
                                </span>
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <div v-if="!showSearchbar" @click="this.showSearchbar = !this.showSearchbar"
                                 class="cursor-pointer inset-y-0 mr-3">
                                <SearchIcon class="h-5 w-5" aria-hidden="true"/>
                            </div>
                            <div v-else class="flex items-center w-full w-64 mr-2">
                                <inputComponent v-model="project_search" placeholder="Suche nach Projekten"/>
                                <XIcon class="ml-2 cursor-pointer h-5 w-5" @click="closeSearchbar()"/>
                            </div>
                        </div>
                        <!-- PERMISSION: Create Project -->
                        <div class="flex relative" v-if="$can('create and edit own project') || $role('artwork admin')">
                            <div v-if="$page.props.can.show_hints" class="flex mt-1 absolute w-40 right-20">
                                <span class="hind ml-1 my-auto">Lege neue Projekte an</span>
                                <SvgCollection svgName="smallArrowRight" class="mt-1 ml-2"/>
                            </div>
                            <AddButton @click="openAddProjectModal" text="Neu" mode="page"/>
                        </div>
                    </div>
                    <div v-for="(project,index) in filteredProjects" :key="project.id"
                         class="mt-5 border-b-2 border-gray-200 w-full">
                        <div class="flex mb-3">
                            <div class="w-48 flex justify-center">
                                <div
                                    class="flex justify-center items-center relative bg-gray-200 rounded-full h-12 w-12">
                                    <img :src="'/storage/keyVisual/' + project.key_visual" alt=""
                                         class="rounded-full h-12 w-12" v-if="project.key_visual !== null">
                                    <img src="/Svgs/IconSvgs/placeholder.svg" alt="" class="rounded-full h-5 w-5"
                                         v-else>
                                    <div class="absolute flex items-center justify-center w-7 h-7"
                                         v-if="project.is_group">
                                        <img src="Svgs/IconSvgs/icon_project_group.svg" alt="">
                                    </div>
                                </div>
                            </div>
                            <div class="w-full mr-3">
                                <div class="flex items-center mb-2">
                                    <div class="mr-6">
                                        <Link
                                            v-if="
                                                $can('view projects') ||
                                                $can('management projects') ||
                                                $can('write projects') ||
                                                $role('artwork admin') ||
                                                $role('budget admin') ||
                                                checkPermission(project, 'edit')"
                                            :href="getEditHref(project)"
                                            class="flex w-full my-auto">
                                            <p class="headline2 flex items-center">
                                                {{ truncate(project.name, 30, '...') }}
                                            </p>
                                        </Link>
                                        <div v-else class="flex w-full my-auto">
                                            <p class="headline2 flex items-center">
                                            <span v-if="project.is_group">
                                                <img src="/Svgs/IconSvgs/icon_group_black.svg" class="h-5 w-5 mr-2"
                                                     aria-hidden="true"/>
                                            </span>
                                                {{ truncate(project.name, 80, '...') }}
                                            </p>
                                        </div>
                                    </div>
                                    <div
                                        v-if="$role('artwork admin') || $can('write projects') || checkPermission(project, 'edit') || $can('view projects')"
                                        class="text-secondary flex flex-nowrap items-center ">
                                        <div v-if="project.project_history.length" class="flex items-center">
                                        <span class=" xxsLight">
                                              zuletzt geändert:
                                        </span>
                                            <img v-if="project.project_history[0].changes[0].changed_by"
                                                 :data-tooltip-target="project.project_history[0].changes[0].changed_by?.id"
                                                 :src="project.project_history[0].changes[0].changed_by?.profile_photo_url"
                                                 :alt="project.project_history[0].changes[0].changed_by?.first_name"
                                                 class="ml-2 ring-white ring-2 rounded-full h-6 w-6 object-cover"/>
                                            <UserTooltip v-if="project.project_history[0].changes[0].changed_by"
                                                         :user="project.project_history[0].changes[0].changed_by"/>
                                            <span class="ml-2 xxsLight subpixel-antialiased">
                                                {{ project.project_history[0].created_at }}
                                            </span>
                                            <button
                                                class="ml-4 xxsLight subpixel-antialiased text-buttonBlue flex items-center cursor-pointer"
                                                @click="openProjectHistoryModal(project)">
                                                <ChevronRightIcon
                                                    class="-mr-0.5 h-4 w-4 group-hover:text-white"
                                                    aria-hidden="true"/>
                                                Verlauf ansehen
                                            </button>
                                        </div>
                                        <div v-else class="xxsLight">
                                            Noch kein Verlauf verfügbar
                                        </div>
                                    </div>
                                </div>
                                <div class="xsLight w-11/12">
                                    {{ truncate(project.description, 300, '...') }}
                                </div>
                            </div>
                            <div class="flex w-4/12 justify-between mr-10">
                                <div class="mr-3 w-36">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium break-keep"
                                        :class="project.state?.color">{{ project.state?.name }}</span>
                                </div>
                                <div class="flex items-top mx-4">
                                    <div class="-mr-3 " v-for="(user) in project.project_managers">
                                        <NewUserToolTip :user="user" :id="user.id" height="8" width="8"/>
                                    </div>
                                </div>
                                <div class="flex w-1/12 ml-4">
                                    <Menu
                                        v-if="this.checkPermission(project, 'edit') || checkPermission(project, 'delete') || $role('artwork admin')"
                                        as="div" class="relative">
                                        <div class="flex bg-tagBg p-0.5 rounded-full">
                                            <div v-if="$page.props.can.show_hints && index === 0"
                                                 class="absolute flex w-40 right-5 bottom-5">
                                                <div class="flex">
                                                    <span class="mr-2 hind mt-1">Bearbeite die Projekte</span>
                                                </div>
                                                <div>
                                                    <SvgCollection svgName="arrowUpRight" class="ml-2 rotate-45"/>
                                                </div>
                                            </div>
                                            <MenuButton
                                                class="flex">
                                                <DotsVerticalIcon
                                                    class=" flex-shrink-0 h-6 w-6 text-menuButtonBlue my-auto"
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
                                                class="origin-top-right z-50 absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                                <div class="py-1">
                                                    <MenuItem v-slot="{ active }"
                                                              v-if="$role('artwork admin') || $can('write projects') || this.checkPermission(project, 'edit')">
                                                        <a :href="getEditHref(project)"
                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                            <PencilAltIcon
                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                aria-hidden="true"/>
                                                            Bearbeiten
                                                        </a>
                                                    </MenuItem>
                                                    <MenuItem v-slot="{ active }"
                                                              v-if="$role('artwork admin') || $can('write projects') || $can('management projects') || this.checkPermission(project, 'edit')">
                                                        <a href="#" @click="duplicateProject(project)"
                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                            <DuplicateIcon
                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                aria-hidden="true"/>
                                                            Duplizieren
                                                        </a>
                                                    </MenuItem>
                                                    <MenuItem v-slot="{ active }"
                                                              v-if="$role('artwork admin') || $can('delete projects') || this.checkPermission(project, 'delete')">
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
                        </div>
                        <div class="py-4 flex hidden">
                            <div class="flex w-1/2">
                                <div class="mr-6">
                                    <Link
                                        v-if="$role('artwork admin') || $can('write projects') || checkPermission(project, 'edit') || $can('view projects')"
                                        :href="getEditHref(project)"
                                        class="flex w-full my-auto">
                                        <p class="headline2 flex items-center">
                                            <span v-if="project.is_group">
                                                <img src="/Svgs/IconSvgs/icon_group_black.svg" class="h-5 w-5 mr-2"
                                                     aria-hidden="true"/>
                                            </span>
                                            {{ project.name }}
                                        </p>
                                    </Link>
                                    <div v-else class="flex w-full my-auto">
                                        <p class="headline2 flex items-center">
                                            <span v-if="project.is_group">
                                                <img src="/Svgs/IconSvgs/icon_group_black.svg" class="h-5 w-5 mr-2"
                                                     aria-hidden="true"/>
                                            </span>
                                            {{ project.name }}
                                        </p>
                                    </div>
                                </div>

                            </div>
                            <div class="w-32">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium break-keep"
                                    :class="project.state?.color">{{ project.state?.name }}</span>
                            </div>
                            <div class="flex w-96">
                                <div class="my-auto -mr-3 flex" v-for="user in project.users.slice(0,3)">
                                    <img :data-tooltip-target="user.id"
                                         class="h-9 w-9 rounded-full ring-2 ring-white object-cover"
                                         :src="user.profile_photo_url"
                                         alt=""/>
                                    <UserTooltip :user="user"/>
                                </div>
                            </div>
                            <div class="flex w-12 justify-end">
                                <div class="flex mr-6">
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
                    <div class="mb-4">
                        <div class="hidden sm:block">
                            <div class="border-gray-200">
                                <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8"
                                     aria-label="Tabs">
                                    <a @click="changeTab(tab)" v-for="tab in tabs" href="#" :key="tab.name"
                                       :class="[tab.current ? 'border-buttonBlue text-buttonBlue' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium font-semibold']"
                                       :aria-current="tab.current ? 'page' : undefined">
                                        {{ tab.name }}
                                    </a>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <XIcon @click="closeAddProjectModal" class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div v-if="isSingleTab">
                        <div class="mt-2">
                            <div class="mb-2">
                                <div class="relative flex w-full">
                                    <input id="projectName" v-model="form.name" type="text" placeholder="Projektname*"
                                           class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                </div>
                            </div>
                            <div class="mb-2">
                                <Menu as="div" class="inline-block text-left w-full">
                                    <div>
                                        <MenuButton
                                            class="h-12 border border-2 sDark placeholder:xsLight border-gray-300 w-full bg-white px-3 py-2 text-sm font-medium text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                        >
                                            <span class="float-left xsLight">Eigenschaften wählen</span>
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
                                            <div
                                                class="mx-auto w-full max-w-md rounded-2xl bg-primary border-none mt-2">
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
                                                                   v-model="form.assignedCategoryIds"
                                                                   :value="category.id"
                                                                   class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                                            <p :class="[form.assignedCategoryIds.includes(category.id)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                               class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                                {{ category.name }}
                                                            </p>
                                                        </div>
                                                        <div v-else class="text-secondary">Noch keine Kategorien
                                                            angelegt
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
                                                                   v-model="form.assignedGenreIds"
                                                                   :value="genre.id"
                                                                   class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                                            <p :class="[form.assignedGenreIds.includes(genre.id)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                               class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                                {{ genre.name }}
                                                            </p>
                                                        </div>
                                                        <div v-else class="text-secondary">Noch keine Genres angelegt
                                                        </div>
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
                                                                   v-model="form.assignedSectorIds"
                                                                   :value="sector.id"
                                                                   class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                                            <p :class="[form.assignedSectorIds.includes(sector.id)
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
                            <div class="flex mb-2">
                                <div v-for="categoryId in form.assignedCategoryIds">
                                    <TagComponent hide-x="true"
                                                  :displayed-text="this.categories.find(category => category.id === categoryId).name"
                                                  :property="this.categories.find(category => category.id === categoryId)"></TagComponent>
                                </div>
                                <div v-for="genreId in form.assignedGenreIds">
                                    <TagComponent hide-x="true"
                                                  :displayed-text="this.genres.find(genre => genre.id === genreId).name"
                                                  :property="this.genres.find(genre => genre.id === genreId)"></TagComponent>
                                </div>
                                <div v-for="sectorId in form.assignedSectorIds">
                                    <TagComponent hide-x="true"
                                                  :displayed-text="this.sectors.find(sector => sector.id === sectorId).name"
                                                  :property="this.sectors.find(sector => sector.id === sectorId)"></TagComponent>
                                </div>
                            </div>
                            <div class="mb-3">
                                            <textarea
                                                placeholder="Kurzbeschreibung"
                                                v-model="form.description" rows="4"
                                                class="resize-none placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 border-2 block w-full "/>
                            </div>
                            <div class="flex items-center mb-2">
                                <input id="hasGroup" type="checkbox" v-model="this.hasGroup"
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
                                                this.selectedGroup ? this.selectedGroup.name : 'Projektgruppe suchen'
                                            }}
                                        </div>
                                        <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                    </ListboxButton>
                                    <ListboxOptions class="w-5/6 bg-primary max-h-32 overflow-y-auto text-sm absolute">
                                        <ListboxOption v-for="projectGroup in this.projectGroups"
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
                    <div v-if="isGroupTab">
                        <div class="">
                            <div class="mb-2 w-full">
                                <input type="text"
                                       v-model="form.name"
                                       id="sourceName"
                                       placeholder="Titel*"
                                       class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                            </div>
                            <div class="mb-2">
                                <Menu as="div" class="inline-block text-left w-full">
                                    <div>
                                        <MenuButton
                                            class="h-12 border border-2 sDark placeholder:xsLight border-gray-300 w-full bg-white px-3 py-2 text-sm font-medium text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                        >
                                            <span
                                                class="float-left subpixel-antialiased xsLight">Eigenschaften wählen</span>
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
                                            <div
                                                class="mx-auto w-full max-w-md rounded-2xl bg-primary border-none mt-2">
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
                                                                   v-model="form.assignedCategoryIds"
                                                                   :value="category.id"
                                                                   class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                                            <p :class="[form.assignedCategoryIds.includes(category.id)
                                                            ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                               class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                                {{ category.name }}
                                                            </p>
                                                        </div>
                                                        <div v-else class="text-secondary">Noch keine Kategorien
                                                            angelegt
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
                                                                   v-model="form.assignedGenreIds"
                                                                   :value="genre.id"
                                                                   class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                                            <p :class="[form.assignedGenreIds.includes(genre.id)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                               class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                                {{ genre.name }}
                                                            </p>
                                                        </div>
                                                        <div v-else class="text-secondary">Noch keine Genres angelegt
                                                        </div>
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
                                                                   v-model="form.assignedSectorIds"
                                                                   :value="sector.id"
                                                                   class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                                            <p :class="[form.assignedSectorIds.includes(sector.id)
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
                            <div class="flex mb-2">
                                <div v-for="categoryId in form.assignedCategoryIds">
                                    <TagComponent hide-x="true"
                                                  :displayed-text="this.categories.find(category => category.id === categoryId).name"
                                                  :property="this.categories.find(category => category.id === categoryId)"></TagComponent>
                                </div>
                                <div v-for="genreId in form.assignedGenreIds">
                                    <TagComponent hide-x="true"
                                                  :displayed-text="this.genres.find(genre => genre.id === genreId).name"
                                                  :property="this.genres.find(genre => genre.id === genreId)"></TagComponent>
                                </div>
                                <div v-for="sectorId in form.assignedSectorIds">
                                    <TagComponent hide-x="true"
                                                  :displayed-text="this.sectors.find(sector => sector.id === sectorId).name"
                                                  :property="this.sectors.find(sector => sector.id === sectorId)"></TagComponent>
                                </div>
                            </div>
                            <div class="mb-2">
                                            <textarea
                                                placeholder="Kurzbeschreibung"
                                                v-model="form.description" rows="4"
                                                class="resize-none placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 border-2 block w-full "/>
                            </div>
                            <div class="mb-2">
                                <div class="relative w-full">
                                    <div class="w-full">
                                        <input id="userSearch" v-model="projectGroup_query" type="text"
                                               autocomplete="off"
                                               placeholder="Welche Projekte gehören zu dieser Gruppe?"
                                               class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                    </div>
                                    <transition leave-active-class="transition ease-in duration-100"
                                                leave-from-class="opacity-100"
                                                leave-to-class="opacity-0">
                                        <div
                                            v-if="projectGroup_search_results.length > 0 && projectGroup_query.length > 0"
                                            class="absolute z-10 mt-1 w-full max-h-60 bg-primary shadow-lg
                                                        text-base ring-1 ring-black ring-opacity-5
                                                        overflow-auto focus:outline-none sm:text-sm">
                                            <div class="border-gray-200">
                                                <div v-for="(projectGroup, index) in projectGroup_search_results"
                                                     :key="index"
                                                     class="flex items-center cursor-pointer">
                                                    <div class="flex-1 text-sm py-4">
                                                        <p @click="addProjectToGroup(projectGroup)"
                                                           class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                            {{ projectGroup.name }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </transition>
                                </div>
                                <div v-if="subProjects.length > 0" class="mt-2 mb-4 flex items-center">
                                        <span v-for="(subProjects,index) in subProjects"
                                              class="flex rounded-full items-center font-bold text-primary">
                                            <span
                                                class="rounded-full items-center font-medium text-tagText border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
                                                {{ subProjects.name }}
                                                <button type="button"
                                                        @click="this.deleteSubProjectFromGroup(index)">
                                                    <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
                                                </button>
                                            </span>
                                        </span>
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
                                  class="xsLight cursor-pointer">Nein, doch nicht</span>
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
        <!-- Project History Modal -->
        <project-history-component
            @closed="closeProjectHistoryModal"
            v-if="showProjectHistory"
            :project_history="projectHistoryToDisplay"
            :access_budget="projectBudgetAccess"
        ></project-history-component>
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
    Disclosure, DisclosureButton, DisclosurePanel,
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem, MenuItems, Switch, SwitchGroup, SwitchLabel
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
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import ProjectHistoryComponent from "@/Layouts/Components/ProjectHistoryComponent.vue";
import Dropdown from "@/Jetstream/Dropdown.vue";

const number_of_participants = [
    {number: '1-10'},
    {number: '10-50'},
    {number: '50-100'},
    {number: '100-500'},
    {number: '>500'}
]

export default defineComponent({
    components: {
        Dropdown,
        Switch,
        ProjectHistoryComponent,
        NewUserToolTip,
        TagComponent,
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
        InputComponent,
        Disclosure,
        DisclosurePanel,
        DisclosureButton,
        SwitchLabel,
        SwitchGroup,
    },
    props: ['projects', 'states', 'users', 'categories', 'genres', 'sectors', 'can', 'projectGroups'],
    computed: {
        tabs() {
            return [
                {name: 'Projekt', href: '#', current: this.isSingleTab},
                {name: 'Projektgruppe', href: '#', current: this.isGroupTab},
            ]
        },
        historyTabs() {
            return [
                {name: 'Projekt', href: '#', current: this.showProjectHistoryTab},
                {name: 'Budget', href: '#', current: this.showBudgetHistoryTab},
            ]
        },
        filteredProjects() {
            return this.projects.filter(project => {
                if (!this.enabled) {
                    if (this.showProjectGroups) {
                        if (project.is_group) {
                            if (this.projectStateFilter.length > 0) {
                                if (this.projectStateFilter.includes(project?.state?.id)) {
                                    return project.name.toLowerCase().includes(this.project_search.toLowerCase());
                                }
                            } else {
                                return project.name.toLowerCase().includes(this.project_search.toLowerCase());
                            }
                        }
                    } else {
                        if (this.projectStateFilter.length > 0) {
                            if (this.projectStateFilter.includes(project?.state?.id)) {
                                return project.name.toLowerCase().includes(this.project_search.toLowerCase());
                            }
                        } else {
                            return project.name.toLowerCase().includes(this.project_search.toLowerCase());
                        }
                    }
                    if (this.showProjects) {
                        if (this.projectStateFilter.length > 0) {
                            if (this.projectStateFilter.includes(project?.state?.id)) {
                                return project.name.toLowerCase().includes(this.project_search.toLowerCase());
                            }
                        } else {
                            return project.name.toLowerCase().includes(this.project_search.toLowerCase());
                        }
                    }
                } else {
                    if (project.curr_user_is_related === true) {
                        if (this.showProjectGroups) {
                            if (project.is_group) {
                                if (this.projectStateFilter.length > 0) {
                                    if (this.projectStateFilter.includes(project?.state?.id)) {
                                        return project.name.toLowerCase().includes(this.project_search.toLowerCase());
                                    }
                                } else {
                                    return project.name.toLowerCase().includes(this.project_search.toLowerCase());
                                }
                            }
                        } else {
                            if (this.projectStateFilter.length > 0) {
                                if (this.projectStateFilter.includes(project?.state?.id)) {
                                    return project.name.toLowerCase().includes(this.project_search.toLowerCase());
                                }
                            } else {
                                return project.name.toLowerCase().includes(this.project_search.toLowerCase());
                            }
                        }
                        if (this.showProjects) {
                            if (this.projectStateFilter.length > 0) {
                                if (this.projectStateFilter.includes(project?.state?.id)) {
                                    return project.name.toLowerCase().includes(this.project_search.toLowerCase());
                                }
                            } else {
                                return project.name.toLowerCase().includes(this.project_search.toLowerCase());
                            }
                        }
                    }
                }

            });
        }


    },
    data() {
        return {
            project_search: '',
            showProjectHistoryTab: true,
            showBudgetHistoryTab: false,
            projectBudgetAccess: {},
            projectGroup_search_results: [],
            projectGroup_query: '',
            subProjects: [],
            projectFilters: [{'name': 'Alle Projekte'}, {'name': 'Meine Projekte'}],
            projectFilter: {'name': 'Alle Projekte'},
            isSingleTab: true,
            isGroupTab: false,
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
            hasGroup: false,
            selectedGroup: null,
            enabled: false,
            showProjectGroups: false,
            showProjects: false,
            showProjectStateFilter: false,
            projectStateFilter: [],
            openedMenu: false,
            form: useForm({
                name: "",
                description: "",
                cost_center: null,
                number_of_participants: "",
                assignedSectorIds: [],
                assignedCategoryIds: [],
                assignedGenreIds: [],
                isGroup: false,
                projects: [],
                selectedGroup: null
            }),
        }
    },
    methods: {
        openCloseMenu() {
            if (this.openedMenu) {
                this.openedMenu = false
            } else {
                this.openedMenu = true
            }
        },
        addStateToFilter(state) {
            if (!state.clicked) {
                this.projectStateFilter.splice(this.projectStateFilter.indexOf(state), 1);
            } else {
                this.projectStateFilter.push(state.id)

            }
        },
        removeFilter() {
            this.enabled = false;
            this.showProjectGroups = false;
            this.showProjects = false;
            this.projectStateFilter = []
            this.states.forEach((state) => {
                state.clicked = false
            })
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
        changeTab(selectedTab) {
            this.usersToAdd = [];
            this.isSingleTab = false;
            this.isGroupTab = false;
            if (selectedTab.name === 'Projekt') {
                this.isSingleTab = true;
                this.form.isGroup = false;
            } else {
                this.isGroupTab = true;
                this.form.isGroup = true;
            }
        },
        closeSearchbar() {
            this.showSearchbar = !this.showSearchbar;
            this.project_search = '';
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
            this.form.projects = [];
            this.form.isGroup = false;
            this.form.selectedGroup = null;
            this.form.assignedSectorIds = [];
            this.form.assignedCategoryIds = [];
            this.form.assignedGenreIds = [];
            this.hasGroup = false;
            this.selectedGroup = null;
        },
        addProject() {
            this.subProjects.forEach((projectToAdd) => {
                this.form.projects.push(projectToAdd.id);
            });
            this.form.selectedGroup = this.selectedGroup;
            this.form.number_of_participants = this.selectedParticipantNumber;
            this.form.post(route('projects.store'), {})
            this.closeAddProjectModal();
            this.openSuccessModal2();
        },
        getEditHref(project) {
            return route('projects.show', {project: project.id, openTab: 'info'});
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
        openProjectHistoryModal(project) {
            this.projectHistoryToDisplay = project.project_history;
            this.projectBudgetAccess = project.access_budget;
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
                        return true;
                    }
                })
            })
            return false;
        },
        addProjectToGroup(project) {
            this.subProjects.push(project);
            this.projectGroup_query = '';
        },
        deleteSubProjectFromGroup(index) {
            this.subProjects.splice(index, 1);
        },
        checkPermission(project, type) {
            const writeAuth = [];
            const managerAuth = [];
            const deleteAuth = [];

            project.project_managers.forEach((user) => {
                managerAuth.push(user.id);
            })

            project.write_auth.forEach((user) => {
                writeAuth.push(user.id);
            });

            project.delete_permission_users.forEach((user) => {
                deleteAuth.push(user.id);
            });

            if (writeAuth.includes(this.$page.props.user.id) && type === 'edit') {
                return true;
            }
            if (managerAuth.includes(this.$page.props.user.id) || deleteAuth.includes(this.$page.props.user.id) && type === 'delete') {
                return true;
            }
            return false;
        },
        truncate(text, length, clamp) {
            clamp = clamp || '...';
            const node = document.createElement('div');
            node.innerHTML = text;
            const content = node.textContent;
            return content.length > length ? content.slice(0, length) + clamp : content;
        },
    },
    watch: {
        projectGroup_query: {
            handler() {
                if (this.projectGroup_query.length > 0) {
                    axios.get('/projects/search/single', {
                        params: {query: this.projectGroup_query, type: this.searchType}
                    }).then(response => {
                        this.projectGroup_search_results = response.data
                    })
                }
            },
            deep: true
        },
    },

    setup() {
        return {
            number_of_participants
        }
    }
})
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
