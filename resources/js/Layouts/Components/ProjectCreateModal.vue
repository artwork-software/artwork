<template>
    <ArtworkBaseModal @close="$emit('closeCreateProjectModal')" :title="project ? t('Edit basic data') : createProjectGroup ? t('New project group') : t('New project')" :description="t('Please fill in the following fields to create a new project.')" modal-size="max-w-3xl">
            <div class="">

                <div class="px-6 my-5 flex items-center justify-between"  v-if="!project">
                    <SwitchDualLabel
                        v-model="createProjectGroup"
                        :left-label="t('Project')"
                        :right-label="t('Project group')"
                        size="md"
                        :tooltip-text="createProjectGroup ? $t('Create a standard project.') : $t('Create a project group to manage multiple related projects together.')"
                        icon="IconGeometry"
                        :disabled="false"
                    />

                    <ToolTipComponent
                        :tooltip-text="$t('Multiple projects can be assigned to a project group. If the content in the KTO and KST columns in subprojects and the project group in the project budget is the same, the data for the subprojects is then displayed in a separate column in the project group.')"
                        direction="bottom"
                        use-translation
                        icon="IconInfoCircle"
                    />
                </div>
                <div v-if="!createProjectGroup">
                    <div>
                       <div v-if="project" class="px-6 py-2">
                           <KeyVisual :project="project"  />
                       </div>
                        <div>
                            <div class="px-6 py-2">
                                <div class="relative flex w-full mb-4">
                                    <BaseInput
                                        id="projectName"
                                        v-model="createProjectForm.name"
                                        label="Project name*"
                                        />
                                </div>
                                <div class="pt-1" v-if="createSettings.show_artists">
                                    <BaseInput
                                        id="create-settings.show-artists"
                                        v-model="createProjectForm.artists"
                                        label="Artists"
                                    />
                                </div>
                                <div v-if="showInvalidProjectNameHelpText" class="text-error text-xs mt-1">
                                    {{ t('Project name is a required field.')}}
                                </div>
                            </div>
                            <div class="px-6 pt-4" v-if="createSettings.attributes">
                                <Menu as="div" class="inline-block text-left w-full relative">
                                    <div>
                                        <MenuButton class="menu-button">
                                            <span>{{ t('Select properties') }}</span>
                                            <ChevronDownIcon class="ml-2 -mr-1 h-5 w-5 text-primary float-right" aria-hidden="true"
                                            />
                                        </MenuButton>
                                    </div>
                                    <transition
                                        enter-active-class="transition duration-50 ease-out"
                                        enter-from-class="transform scale-100 opacity-100"
                                        enter-to-class="transform scale-100 opacity-100"
                                        leave-active-class="transition duration-75 ease-in"
                                        leave-from-class="transform scale-100 opacity-100"
                                        leave-to-class="transform scale-95 opacity-0">
                                        <MenuItems class="absolute overflow-y-auto h-48 w-full origin-top-left divide-y divide-gray-200 bg-primary ring-1 ring-black text-white opacity-100 z-50 rounded-lg">
                                            <div class="mx-auto w-full p-3 bg-primary border-none mt-2">
                                                <Disclosure v-slot="{ open }">
                                                    <DisclosureButton
                                                        class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                                    >
                                                        <span
                                                            :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">
                                                            {{ t('Category') }}
                                                        </span>
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
                                                                   v-model="createProjectForm.assignedCategoryIds"
                                                                   :value="category.id"
                                                                   class="input-checklist-dark"/>
                                                            <p :class="[createProjectForm.assignedCategoryIds.includes(category.id)
                                                            ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                               class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                                {{ category.name }}
                                                            </p>
                                                        </div>
                                                        <div v-else class="text-secondary">
                                                            {{ t('No categories created yet') }}
                                                        </div>
                                                    </DisclosurePanel>
                                                </Disclosure>
                                                <hr class="border-gray-500 mt-2 mb-2">
                                                <Disclosure v-slot="{ open }">
                                                    <DisclosureButton
                                                        class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                                    >
                                                        <span
                                                            :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">
                                                            {{ t('Genre') }}
                                                        </span>
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
                                                                   v-model="createProjectForm.assignedGenreIds"
                                                                   :value="genre.id"
                                                                   class="input-checklist-dark"/>
                                                            <p :class="[createProjectForm.assignedGenreIds.includes(genre.id)
                                                            ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                               class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                                {{ genre.name }}
                                                            </p>
                                                        </div>
                                                        <div v-else class="text-secondary">
                                                            {{ t('No genres created yet') }}
                                                        </div>
                                                    </DisclosurePanel>
                                                </Disclosure>
                                                <hr class="border-gray-500 mt-2 mb-2">
                                                <Disclosure v-slot="{ open }">
                                                    <DisclosureButton
                                                        class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                                    >
                                                        <span
                                                            :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">
                                                            {{ t('Area') }}
                                                        </span>
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
                                                                   v-model="createProjectForm.assignedSectorIds"
                                                                   :value="sector.id"
                                                                   class="input-checklist-dark"/>
                                                            <p :class="[createProjectForm.assignedSectorIds.includes(sector.id)
                                                            ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                               class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                                {{ sector.name }}
                                                            </p>
                                                        </div>
                                                        <div v-else class="text-secondary">
                                                            {{ t('No areas created yet') }}
                                                        </div>
                                                    </DisclosurePanel>
                                                </Disclosure>
                                            </div>
                                        </MenuItems>
                                    </transition>
                                </Menu>
                                <div :class="createProjectForm.assignedCategoryIds || createProjectForm.assignedGenreIds || createProjectForm.assignedSectorIds ? 'mt-2' : ''">
                                    <TagComponent v-for="categoryId in createProjectForm.assignedCategoryIds" hide-x="true"
                                                  :displayed-text="categories?.find(category => category.id === categoryId)?.name ?? ''"
                                                  :property="categories?.find(category => category.id === categoryId)"
                                    />
                                    <TagComponent v-for="genreId in createProjectForm.assignedGenreIds" hide-x="true"
                                                  :displayed-text="genres?.find(genre => genre.id === genreId)?.name ?? ''"
                                                  :property="genres?.find(genre => genre.id === genreId)"
                                    />
                                    <TagComponent hide-x="true" v-for="sectorId in createProjectForm.assignedSectorIds"
                                                      :displayed-text="sectors?.find(sector => sector.id === sectorId)?.name ?? ''"
                                                      :property="sectors?.find(sector => sector.id === sectorId)"
                                    />
                                </div>
                            </div>
                            <div class="px-6 pb-5 pt-4 w-full" v-if="createSettings.state">
                                <!-- Show tag when state is selected -->
                                <div v-if="selectedState" class="w-full">
                                    <div class="text-gray-500 text-xs mb-2">
                                        {{ t('Project status') }}
                                    </div>
                                    <div class="inline-flex items-center gap-x-2 px-3 py-1.5 rounded-full border border-gray-300 bg-white">
                                        <div class="block w-3 h-3 rounded-full" :style="{'backgroundColor' : selectedState.color }"/>
                                        <span class="text-sm flex items-center gap-x-1">
                                            {{ selectedState.name }}
                                            <IconCalendarMonth v-if="selectedState.is_planning === true || selectedState.is_planning === 1" class="w-4 h-4" />
                                        </span>
                                        <button type="button" @click="selectedState = null" class="ml-1">
                                            <XIcon class="h-4 w-4 text-gray-400 hover:text-error" />
                                        </button>
                                    </div>
                                </div>
                                <!-- Show dropdown when no state is selected -->
                                <Listbox v-else as="div" class="w-full relative" v-model="selectedState" @update:model-value="handleStateChange($event)">
                                    <ListboxButton class="menu-button-no-padding relative">
                                        <div class="truncate">
                                            <div class="top-2 left-4 absolute text-gray-500 text-xs">
                                                {{ t('Project status') }}
                                            </div>
                                            <div class="pt-6 pb-2 flex items-center gap-x-2">
                                                <div class="truncate">
                                                    <span>
                                                        {{ t('Select project status') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                    </ListboxButton>
                                    <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                        <ListboxOptions class="absolute w-full z-10 bg-primary shadow-lg max-h-40 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                            <ListboxOption as="template" class="max-h-8"
                                                v-for="state in states"
                                                :key="state.id"
                                                :value="state"
                                                v-slot="{ active, selected }">
                                                <li :class="[active ? 'text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                    <div class="flex items-center">
                                                        <div>
                                                            <div class="block w-3 h-3 rounded-full"
                                                                :style="{'backgroundColor' : state?.color }"/>
                                                        </div>
                                                        <span
                                                            :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 flex items-center gap-x-1 truncate']">
                                                            {{ state.name }}
                                                            <IconCalendarMonth v-if="state.is_planning === true || state.is_planning === 1" class="w-4 h-4" />
                                                        </span>
                                                    </div>
                                                    <span
                                                        :class="[active ? 'text-white' : 'text-secondary', 'group flex justify-end items-center text-sm subpixel-antialiased']">
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

                            <div class="px-11 py-6 -mx-5 bg-lightBackgroundGray" v-if="createSettings.managers">
                                <div class="font-semibold text-sm pb-2">{{ t('Project management')}}</div>
                                <UserSearch @user-selected="addUserToProject" only-manager label="Search for users" />

                                <div v-if="assignedUsers?.length > 0" class="flex items-center gap-4 mt-3">
                                    <div v-for="(user, index) in assignedUsers" class="group block shrink-0 bg-white w-fit pr-3 rounded-full border border-gray-100">
                                        <div class="flex items-center">
                                            <div>
                                                <img class="inline-block size-9 rounded-full object-cover" :src="user.profile_photo_url" alt="" />
                                            </div>
                                            <div class="mx-2">
                                                <p class="xsDark group-hover:text-gray-900">{{ user.name}}</p>
                                            </div>
                                            <div class="flex items-center">
                                                <button type="button" @click="removeUserFromProject(index)">
                                                    <XIcon class="h-4 w-4 text-gray-400 hover:text-error" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="px-6 pb-2 pt-4" v-if="createSettings.cost_center">
                                <BaseInput
                                    id="costCenter"
                                    v-model="createProjectForm.cost_center"
                                    label="Name of the cost unit"
                                />
                            </div>



                            <div v-if="createSettings.budget_deadline" class="px-6 py-2">
                                <BaseInput
                                    type="date"
                                    id="budgetDeadline"
                                    v-model="createProjectForm.budget_deadline"
                                    label="Budget deadline" />
                            </div>

                            <div class="px-6 py-2" v-if="!project?.is_group || !project">

                                <div class="flex gap-3">
                                    <div class="flex h-6 shrink-0 items-center">
                                        <div class="group grid size-4 grid-cols-1">
                                            <input v-model="addToProjectGroup" id="addToProjectGroup" aria-describedby="addToProjectGroup-description" name="addToProjectGroup" type="checkbox" checked="" class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto" />
                                            <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                                <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="text-sm/6">
                                        <label for="addToProjectGroup" class="text-gray-900">{{ $t('Belongs to project group') }}</label>
                                        <p id="addToProjectGroup-description" class="text-gray-500 text-xs">
                                            {{ $t('Add the project to a project group') }}
                                        </p>
                                    </div>
                                </div>

                                <div v-if="addToProjectGroup" class="pt-5">
                                    <ProjectSearch id="2" @project-selected="createProjectForm.selectedGroup = $event" only-project-groups label="Search project group" v-if="!createProjectForm.selectedGroup"/>

                                    <div v-else class="flex items-center gap-x-2 justify-between">
                                        <div>
                                            <h3 class="font-semibold text-base">{{ createProjectForm.selectedGroup.name }}</h3>
                                        </div>
                                        <div class="text-blue-500 text-xs underline cursor-pointer" @click="createProjectForm.selectedGroup = null">
                                            {{ $t('Deselect project group') }}
                                        </div>
                                    </div>

                                    <LastedProjects
                                        :limit="10"
                                        @select="createProjectForm.selectedGroup = $event"
                                        v-if="!createProjectForm.selectedGroup"
                                        only-groups
                                    />
                                </div>
                            </div>

                            <div class="px-6 py-2">
                                <div class="flex gap-3">
                                    <div class="flex h-6 shrink-0 items-center">
                                        <div class="group grid size-4 grid-cols-1">
                                            <input v-model="createProjectForm.marked_as_done" id="marked_as_done" aria-describedby="marked_as_done-description" name="marked_as_done" type="checkbox" checked="" class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto" />
                                            <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                                <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="text-sm/6">
                                        <label for="marked_as_done" class="text-gray-900">{{ $t('Mark as Done') }}</label>
                                        <p id="marked_as_done-description" class="text-gray-500 text-xs">
                                            {{ $t('As soon as this option is activated, the project no longer appears in the project searches.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="w-full flex items-center justify-end gap-x-4 mt-5 px-6">
                            <BaseUIButton
                                label="Set up events"
                                is-add-button
                                use-translation
                                icon="IconCalendarMonth"
                                v-if="!project && can('can create events when creating a project')"
                                @click="addProject(true)"
                            />
                            <BaseUIButton
                                :label="project ? t('Save') : t('Create')"
                                is-add-button
                                use-translation
                                @click="addProject(false)"
                            />
                        </div>
                    </div>
                </div>
                <div v-if="createProjectGroup && !project">
                    <div class="px-6 pb-6">
                        <div class="flex justify-between">
                            <div class="flex items-center gap-x-2">
                                <IconSelector @update:modelValue="addIconToForm" :current-icon="createProjectForm.icon" />
                                <BasePageTitle
                                    title="Icon"
                                    description="Wähle ein Icon für die Projektgruppe aus."
                                />

                            </div>
                            <div class="flex items-center gap-x-2">
                                <div class="">
                                    <ColorPickerComponent @updateColor="addColorToProject" color="#ccc" />
                                </div>
                                <BasePageTitle
                                    title="Farbe"
                                    description="Wähle eine Farbe für die Projektgruppe aus."
                                    />

                            </div>
                        </div>
                        <div class="w-full col-span-4 mt-4">
                            <BaseInput
                                id="sourceName"
                                v-model="createProjectForm.name"
                                label="Title*"
                            />
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-x-4">
                            <div class="flex flex-col items-center justify-center w-full h-full">

                            </div>
                        </div>
                        <div class="mb-2 mt-5" v-if="createSettings.attributes">
                            <Menu as="div" class="inline-block text-left w-full relative">
                                <div>
                                    <MenuButton class="menu-button">
                                            <span>
                                                {{ t('Select properties') }}
                                            </span>
                                        <ChevronDownIcon class="ml-2 -mr-1 h-5 w-5 text-primary float-right" aria-hidden="true"/>
                                    </MenuButton>
                                </div>
                                <transition
                                    enter-active-class="transition duration-50 ease-out"
                                    enter-from-class="transform scale-100 opacity-100"
                                    enter-to-class="transform scale-100 opacity-100"
                                    leave-active-class="transition duration-75 ease-in"
                                    leave-from-class="transform scale-100 opacity-100"
                                    leave-to-class="transform scale-95 opacity-0">
                                    <MenuItems class="absolute overflow-y-auto h-48 mt-2 w-full origin-top-left divide-y divide-gray-200 rounded-lg bg-primary ring-1 ring-black p-2 text-white opacity-100 z-50">
                                        <div class="mx-auto w-full rounded-2xl bg-primary border-none mt-2">
                                            <Disclosure v-slot="{ open }">
                                                <DisclosureButton class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500">
                                                    <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{ t('Category') }}</span>
                                                    <ChevronDownIcon :class="open ? 'rotate-180 transform' : ''" class="h-4 w-4 mt-0.5 text-white"/>
                                                </DisclosureButton>
                                                <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                                                    <div v-if="categories.length > 0"
                                                         v-for="category in categories"
                                                         :key="category.id"
                                                         class="flex w-full mb-2">
                                                        <input type="checkbox"
                                                               v-model="createProjectForm.assignedCategoryIds"
                                                               :value="category.id"
                                                               class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                                        <p :class="[createProjectForm.assignedCategoryIds.includes(category.id)
                                                            ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                           class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                            {{ category.name }}
                                                        </p>
                                                    </div>
                                                    <div v-else class="text-secondary">
                                                        {{ t('No categories created yet') }}
                                                    </div>
                                                </DisclosurePanel>
                                            </Disclosure>
                                            <hr class="border-gray-500 mt-2 mb-2">
                                            <Disclosure v-slot="{ open }">
                                                <DisclosureButton
                                                    class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                                >
                                                    <span
                                                        :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{ t('Genre') }}</span>
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
                                                               v-model="createProjectForm.assignedGenreIds"
                                                               :value="genre.id"
                                                               class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                                        <p :class="[createProjectForm.assignedGenreIds.includes(genre.id)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                           class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                            {{ genre.name }}
                                                        </p>
                                                    </div>
                                                    <div v-else class="text-secondary">
                                                        {{ t('No genres created yet') }}
                                                    </div>
                                                </DisclosurePanel>
                                            </Disclosure>
                                            <hr class="border-gray-500 mt-2 mb-2">
                                            <Disclosure v-slot="{ open }">
                                                <DisclosureButton
                                                    class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                                >
                                                    <span
                                                        :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{ t('Area') }}</span>
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
                                                               v-model="createProjectForm.assignedSectorIds"
                                                               :value="sector.id"
                                                               class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                                        <p :class="[createProjectForm.assignedSectorIds.includes(sector.id)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                           class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                            {{ sector.name }}
                                                        </p>
                                                    </div>
                                                    <div v-else class="text-secondary">
                                                        {{ t('No areas created yet') }}
                                                    </div>
                                                </DisclosurePanel>
                                            </Disclosure>
                                        </div>
                                    </MenuItems>
                                </transition>
                            </Menu>
                        </div>
                        <div class="flex">
                            <div v-for="categoryId in createProjectForm.assignedCategoryIds">
                                <TagComponent hide-x="true"
                                              :displayed-text="categories?.find(category => category.id === categoryId)?.name ?? ''"
                                              :property="categories?.find(category => category.id === categoryId)"></TagComponent>
                            </div>
                            <div v-for="genreId in createProjectForm.assignedGenreIds">
                                <TagComponent hide-x="true"
                                              :displayed-text="genres?.find(genre => genre.id === genreId)?.name ?? ''"
                                              :property="genres?.find(genre => genre.id === genreId)"></TagComponent>
                            </div>
                            <div v-for="sectorId in createProjectForm.assignedSectorIds">
                                <TagComponent hide-x="true"
                                              :displayed-text="sectors?.find(sector => sector.id === sectorId)?.name ?? ''"
                                              :property="sectors?.find(sector => sector.id === sectorId)"></TagComponent>
                            </div>
                        </div>
                        <div class="mb-2">
                            <div class="relative w-full">
                                <ProjectSearch :noProjectGroups="createProjectGroup" @project-selected="addProjectToProjectGroup" v-model="projectGroupQuery" />
                            </div>
                            <div v-if="projectGroupProjects.length > 0" class="mt-3 mb-4 flex items-center flex-wrap gap-3">
                                <div v-for="(groupProject, index) in projectGroupProjects" class="group block shrink-0 bg-gray-50 w-fit pr-3 rounded-full border border-gray-300">
                                    <div class="flex items-center">
                                        <div>
                                            <img class="inline-block size-9 rounded-full object-cover" :src="groupProject?.key_visual_path ? '/storage/keyVisual/' + groupProject?.key_visual_path : '/storage/logo/artwork_logo_small.svg'" alt="" />
                                        </div>
                                        <div class="mx-2">
                                            <p class="xsDark group-hover:text-gray-900">{{ groupProject.name}}</p>
                                        </div>
                                        <div class="flex items-center">
                                            <button type="button" @click="deleteProjectFromProjectGroup(index)">
                                                <XIcon class="h-4 w-4 text-gray-400 hover:text-error" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <LastedProjects
                            :limit="10"
                            @select="handleOpenProject"
                            without-group
                        />

                        <div class="w-full flex items-center justify-end gap-x-4 mt-5">

                            <BaseUIButton label="Set up events" is-add-button use-translation icon="IconCalendarMonth" v-if="!project" @click="addProject(true)"/>
                            <BaseUIButton :label="project ? t('Save') : t('Create')" is-add-button use-translation @click="addProject(false)"/>
                        </div>

                    </div>
                </div>
            </div>
    </ArtworkBaseModal>
</template>

<script setup>
import Input from "@/Layouts/Components/InputComponent.vue";
import Button from "@/Jetstream/Button.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import {CheckIcon} from "@heroicons/vue/solid";
import {ChevronDownIcon, XIcon} from "@heroicons/vue/outline";
import {useForm} from "@inertiajs/vue3";
import {
    Disclosure,
    DisclosureButton,
    DisclosurePanel,
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItems,
} from "@headlessui/vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import KeyVisual from "@/Components/Uploads/KeyVisual.vue";
import ProjectSearch from "@/Components/SearchBars/ProjectSearch.vue";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import IconSelector from "@/Components/Icon/IconSelector.vue";
import ColorPickerComponent from "@/Components/Globale/ColorPickerComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import { ref, reactive, computed, defineProps, defineEmits } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { usePermission } from '@/Composeables/Permission.js';
import { useTranslation } from '@/Composeables/Translation.js';
import {IconCalendarMonth, IconCirclePlus} from "@tabler/icons-vue";
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import SwitchDualLabel from "@/Artwork/Toggles/SwitchDualLabel.vue";
import LastedProjects from "@/Artwork/LastedProjects.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";

// Define props
const props = defineProps({
    show: Boolean,
    categories: Array,
    genres: Array,
    sectors: Array,
    projectGroups: Array,
    states: Array,
    createSettings: Object,
    project: Object,
    selectedGroup: {
        type: Object,
        default: null,
        required: false,
    },
});

// Define emits
const emit = defineEmits(['closeCreateProjectModal', 'dropFeedback', 'openProjectStateChangeModal', 'openProjectPlanningStateChangeModal']);

// Setup composables
const page = usePage();
const { can } = usePermission(page.props);
const t = useTranslation();

// Color helper functions
const backgroundColorWithOpacity = (color, percent = 15) => {
    if (!color) return `rgba(255, 255, 255, ${percent / 100})`;
    return `rgba(${parseInt(color.slice(-6, -4), 16)}, ${parseInt(color.slice(-4, -2), 16)}, ${parseInt(color.slice(-2), 16)}, ${percent / 100})`;
};

const calculateLuminance = (color) => {
    let rgb;
    if (color.startsWith('rgb')) {
        // Convert "rgb(r, g, b)" or "rgba(r, g, b, a)" to hex format
        rgb = color.match(/\d+/g).slice(0, 3).map(Number);
    } else {
        rgb = [
            parseInt(color.slice(1, 3), 16),
            parseInt(color.slice(3, 5), 16),
            parseInt(color.slice(5, 7), 16),
        ];
    }
    const [r, g, b] = rgb.map(v => v / 255);
    const a = [r, g, b].map(v => v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4));
    return 0.2126 * a[0] + 0.7152 * a[1] + 0.0722 * a[2];
};

const isDarkColor = (color) => {
    return calculateLuminance(color) < 0.5;
};

const TextColorWithDarken = (color, percent = 75) => {
    if (!color) return 'rgb(180, 180, 180)';
    return `rgb(${Math.max(0, parseInt(color.slice(-6, -4), 16) - percent)}, ${Math.max(0, parseInt(color.slice(-4, -2), 16) - percent)}, ${Math.max(0, parseInt(color.slice(-2), 16) - percent)})`;
};

// Reactive state
const isCreateProjectTab = ref(true);
const isCreateProjectGroupTab = ref(false);
const addToProjectGroup = ref(props.project ? !!props.project?.groups[0] : props.selectedGroup ? !!props.selectedGroup : false);
const createProjectForm = useForm({
    name: props.project ? props.project.name : '',
    artists: props.project ? props.project.artists : '',
    assignedSectorIds: props.project ? props.project?.sectors?.map(sector => sector.id) : [],
    assignedCategoryIds: props.project ? props.project?.categories?.map(category => category.id) : [],
    assignedGenreIds: props.project ? props.project?.genres?.map(genre => genre.id) : [],
    isGroup: props.project ? props.project.is_group : false,
    projects: [],
    selectedGroup: props.project ? props.project?.groups[0] : props.selectedGroup ? props.selectedGroup : null,
    budget_deadline: props.project ? props.project.budget_deadline : '',
    state: null,
    assignedUsers: [],
    cost_center: props.project ? props.project?.cost_center?.name : '',
    icon: props.project ? props.project.icon : '',
    color: props.project ? props.project.color : null,
    marked_as_done: props.project ? props.project.marked_as_done : false,
});
const projectGroupProjects = ref([]);
const projectGroupSearchResults = ref([]);
const projectGroupQuery = ref('');
const selectedState = ref(props.project && props.project.state && props.states ? props.states.find(state => state.id === (typeof props.project.state === 'object' ? props.project.state.id : props.project.state)) : null);
const selectedStateObject = ref(props.project ? props.project?.state : null);
const initialStatePlanning = ref(props.project && props.project.state ? props.states?.find(state => state.id === (typeof props.project.state === 'object' ? props.project.state.id : props.project.state))?.is_planning : null);
const assignedUsers = ref(props.project ? props.project.manager_users ? props.project.manager_users : [] : []);
const keyVisualForm = useForm({
    keyVisual: null,
});
const uploadKeyVisualFeedback = ref("");
const createProjectGroup = ref(false);
const showInvalidProjectNameHelpText = ref(false);

// Computed properties
const tabs = computed(() => {
    return [
        {
            name: t('Projects'),
            current: isCreateProjectTab.value
        },
        {
            name: t('Project group'),
            current: isCreateProjectGroupTab.value
        },
    ];
});

// Methods
const addUserToProject = (user) => {
    // check if user is already in the list
    if (!assignedUsers.value.find(assignedUser => assignedUser.id === user.id)) {
        assignedUsers.value.push(user);
    }
};

const removeUserFromProject = (index) => {
    assignedUsers.value.splice(index, 1);
};

const changeTab = (selectedTab) => {
    if (selectedTab.name === t('Projects')) {
        isCreateProjectTab.value = true;
        isCreateProjectGroupTab.value = false;
        createProjectForm.isGroup = false;
    } else {
        isCreateProjectGroupTab.value = true;
        isCreateProjectTab.value = false;
        createProjectForm.isGroup = true;
    }
};

const addIconToForm = (icon) => {
    createProjectForm.icon = icon;
};

const addColorToProject = (color) => {
    createProjectForm.color = color;
};

const addProject = (bool) => {
    if (createProjectForm.name === '') {
        showInvalidProjectNameHelpText.value = true;
        return;
    }

    projectGroupProjects.value.forEach((projectToAdd) => {
        createProjectForm.projects.push(projectToAdd.id);
    });

    createProjectForm.assignedUsers = assignedUsers.value?.map(user => user.id);
    createProjectForm.state = selectedState.value ? selectedState.value.id : null;

    if (createProjectGroup.value) {
        createProjectForm.isGroup = true;
    }

    if (props.project) {
        createProjectForm.patch(
            route('projects.update', props.project.id), {
                onSuccess: () => {
                    if(initialStatePlanning.value === 1 && selectedState.value?.is_planning === 0) {
                        emit('openProjectStateChangeModal', props.project);
                    } else if(initialStatePlanning.value === 0 && selectedState.value?.is_planning === 1) {
                        emit('openProjectPlanningStateChangeModal', props.project);
                    }
                    emit('closeCreateProjectModal', bool);
                }
            }
        );
    } else {
        createProjectForm.post(
            route('projects.store'), {
                onSuccess: () => {
                    emit('closeCreateProjectModal', bool);
                    emit('dropFeedback');
                }
            }
        );
    }
};

function handleOpenProject(p) {
    // hier deine gewünschte Funktionalität
    // z.B. Inertia besuchen:
    // router.visit(route('projects.show', p.id))
    addProjectToProjectGroup(p)
}

const addProjectToProjectGroup = (project) => {
    if (!projectGroupProjects.value.includes(project)) {
        projectGroupProjects.value.push(project);
    }
};

const deleteProjectFromProjectGroup = (index) => {
    projectGroupProjects.value.splice(index, 1);
};

const handleStateChange = (stateId) => {
    // Find the state object by ID and update selectedStateObject
    selectedStateObject.value = props.states.find(state => state.id === stateId) || null;
};
</script>
