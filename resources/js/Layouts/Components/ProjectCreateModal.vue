<template>
    <BaseModal @closed="this.$emit('closeCreateProjectModal')" v-if="show" modal-image="/Svgs/Overlays/illu_project_new.svg">
            <div class="mx-4">
                <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                    {{ $t('New project') }}
                </div>
                <div class="mb-4">
                    <div class="hidden sm:block">
                        <div class="border-gray-200">
                            <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8"
                                 aria-label="Tabs">
                                <a @click="changeTab(tab)" v-for="tab in tabs" href="#" :key="tab.name"
                                   :class="[tab.current ? 'border-artwork-buttons-create text-artwork-buttons-create' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium font-semibold']"
                                   :aria-current="tab.current ? 'page' : undefined">
                                    {{ tab.name }}
                                </a>
                            </nav>
                        </div>
                    </div>
                </div>
                <div v-if="isCreateProjectTab">
                    <div class="mt-2 divide-y divide-gray-300 divide-dashed">
                        <div class="py-3">
                            <div class="relative flex w-full">
                                <input id="projectName" v-model="createProjectForm.name" type="text"
                                       :placeholder="$t('Project name*')"
                                       class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                            </div>
                        </div>
                        <div class="py-3" v-if="createSettings.attributes">
                            <Menu as="div" class="inline-block text-left w-full">
                                <div>
                                    <MenuButton
                                        class="h-12 border border-2 sDark placeholder:xsLight border-gray-300 w-full bg-white px-3 py-2 text-sm font-medium text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                    >
                                        <span class="float-left xsLight">{{ $t('Select properties') }}</span>
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
                                                        :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">
                                                        {{ $t('Category') }}
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
                                                               class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                                        <p :class="[createProjectForm.assignedCategoryIds.includes(category.id)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                           class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                            {{ category.name }}
                                                        </p>
                                                    </div>
                                                    <div v-else class="text-secondary">
                                                        {{ $t('No categories created yet') }}
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
                                                        {{ $t('Genre') }}
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
                                                               class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                                        <p :class="[createProjectForm.assignedGenreIds.includes(genre.id)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                           class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                            {{ genre.name }}
                                                        </p>
                                                    </div>
                                                    <div v-else class="text-secondary">
                                                        {{ $t('No genres created yet') }}
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
                                                        {{ $t('Area') }}
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
                                                               class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                                        <p :class="[createProjectForm.assignedSectorIds.includes(sector.id)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                           class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                            {{ sector.name }}
                                                        </p>
                                                    </div>
                                                    <div v-else class="text-secondary">
                                                        {{ $t('No areas created yet') }}
                                                    </div>
                                                </DisclosurePanel>
                                            </Disclosure>
                                        </div>
                                    </MenuItems>
                                </transition>
                            </Menu>
                            <div class="flex" :class="createProjectForm.assignedCategoryIds || createProjectForm.assignedGenreIds || createProjectForm.assignedSectorIds ? 'mt-2' : ''">
                                <div v-for="categoryId in createProjectForm.assignedCategoryIds">
                                    <TagComponent hide-x="true"
                                                  :displayed-text="this.categories.find(category => category.id === categoryId).name"
                                                  :property="this.categories.find(category => category.id === categoryId)"></TagComponent>
                                </div>
                                <div v-for="genreId in createProjectForm.assignedGenreIds">
                                    <TagComponent hide-x="true"
                                                  :displayed-text="this.genres.find(genre => genre.id === genreId).name"
                                                  :property="this.genres.find(genre => genre.id === genreId)"></TagComponent>
                                </div>
                                <div v-for="sectorId in createProjectForm.assignedSectorIds">
                                    <TagComponent hide-x="true"
                                                  :displayed-text="this.sectors.find(sector => sector.id === sectorId).name"
                                                  :property="this.sectors.find(sector => sector.id === sectorId)"></TagComponent>
                                </div>
                            </div>
                        </div>

                        <div class="flex py-2 w-full" v-if="createSettings.state">
                            <Listbox as="div" class="flex w-full" v-model="selectedState">
                                <ListboxButton class="w-full text-left">
                                    <button class="w-full h-12 flex justify-between xsDark items-center text-left border-2 border-gray-300 bg-white px-4 py-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
                                        <span class="w-full" v-if="!selectedState">
                                            {{ $t('Select project status') }}
                                        </span>
                                        <span v-else  class="items-center font-medium px-2 py-1.5 inline-flex border rounded-full"
                                              :style="{
                                            backgroundColor: backgroundColorWithOpacity(states.find(state => state.id === selectedState)?.color),
                                            color: TextColorWithDarken(states.find(state => state.id === selectedState)?.color),
                                            borderColor: TextColorWithDarken(states.find(state => state.id === selectedState)?.color)
                                        }">
                                            {{ this.states.find(state => state.id === selectedState)?.name}}
                                        </span>
                                        <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                    </button>
                                </ListboxButton>
                                <transition leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100"
                                            leave-to-class="opacity-0">
                                    <ListboxOptions
                                        class="absolute w-[88%] z-10 mt-12 bg-white shadow-lg max-h-40 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                        <ListboxOption as="template" class=""
                                                       v-for="state in states"
                                                       :key="state.id"
                                                       :value="state.id" v-slot="{ active, selected }">
                                            <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-1 pl-3 text-sm subpixel-antialiased']">
                                                <div class="flex">
                                                    <span class=" items-center font-medium px-2 py-1.5 inline-flex border rounded-full" :style="{backgroundColor: backgroundColorWithOpacity(state.color), color: TextColorWithDarken(state.color), borderColor: TextColorWithDarken(state.color)}">
                                                        {{ state.name }}
                                                    </span>
                                                </div>
                                                <span :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                                    <CheckIcon v-if="selected" class="h-5 w-5 flex text-success" aria-hidden="true"/>
                                                </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </Listbox>
                        </div>

                        <div class="py-2" v-if="createSettings.managers">
                            <div class="font-semibold text-sm -mb-1">{{ $t('Project management')}}</div>
                            <UserSearch @user-selected="addUserToProject" class="mb-2" />

                            <div v-if="assignedUsers.length > 0">
                                <div v-for="(user, index) in assignedUsers">
                                    <div class="flex items-center justify-between mb-3 group">
                                        <div class="flex items-center gap-x-2">
                                            <img :src="user.profile_photo_url" alt="" class="h-12 w-12 object-cover rounded-full">
                                            <div>
                                                {{ user.full_name}}
                                            </div>
                                        </div>
                                        <div class="hidden group-hover:block">
                                            <IconCircleX class="h-6 w-6 text-gray-600 hover:text-red-600 cursor-pointer transition-all duration-150 ease-in-out" @click="removeUserFromProject(index)"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="py-3" v-if="createSettings.cost_center">
                            <TextInputComponent
                                v-model="createProjectForm.cost_center"
                                :label="$t('Name of the cost unit')"
                            />
                        </div>

                        <div class="flex items-center py-3">
                            <input id="addToProjectGroup" type="checkbox" v-model="this.addToProjectGroup"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <label for="addToProjectGroup"
                                   :class="this.addToProjectGroup ? 'xsDark' : 'xsLight subpixel-antialiased'"
                                   class="ml-2">
                                {{ $t('Belongs to project group') }}
                            </label>
                        </div>
                        <div v-if="this.addToProjectGroup" class="py-3">
                            <Listbox as="div" v-model="this.createProjectForm.selectedGroup" id="room">
                                <ListboxButton class="inputMain w-full h-10 cursor-pointer truncate flex p-2">
                                    <div class="flex-grow flex text-left xsDark">
                                        {{
                                            this.createProjectForm.selectedGroup ? this.createProjectForm.selectedGroup.name : $t('Search project group')
                                        }}
                                    </div>
                                    <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </ListboxButton>
                                <ListboxOptions class="w-5/6 bg-primary max-h-32 overflow-y-auto text-sm absolute">
                                    <ListboxOption v-if="this.projectGroups.length === 0"
                                                   class="w-full text-secondary cursor-pointer p-2 flex justify-between"
                                                   :value="null">
                                        {{ $t('No project group has been created yet') }}
                                    </ListboxOption>
                                    <ListboxOption v-for="projectGroup in this.projectGroups"
                                                   class="hover:bg-artwork-buttons-create text-secondary cursor-pointer p-2 flex justify-between "
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
                        <div class="py-3" v-if="createSettings.budget_deadline">
                            <div class="xsLight">
                                <span>{{ $t('Budget deadline') }}</span>
                            </div>
                            <div class="flex mt-1">
                                <input v-model="this.createProjectForm.budgetDeadline"
                                       id="budgetDeadline"
                                       type="date"
                                       required
                                       class="border-gray-300 inputMain xsDark placeholder-secondary placeholder:text-secondary disabled:border-none flex-grow"/>
                            </div>
                        </div>

                    </div>
                    <div class="w-full items-center text-center">
                        <FormButton
                            @click="addProject"
                            :disabled="this.createProjectForm.name === ''"
                            :text="$t('Create')"
                            class="mt-8 inline-flex items-center"
                        />
                    </div>
                </div>
                <div v-if="isCreateProjectGroupTab">
                    <div class="">
                        <div class="mb-2 w-full">
                            <input type="text"
                                   v-model="createProjectForm.name"
                                   id="sourceName"
                                   :placeholder="$t('Title*')"
                                   class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                        </div>
                        <div class="mb-2" v-if="createSettings.attributes">
                            <Menu as="div" class="inline-block text-left w-full">
                                <div>
                                    <MenuButton
                                        class="h-12 border border-2 sDark placeholder:xsLight border-gray-300 w-full bg-white px-3 py-2 text-sm font-medium text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                    >
                                            <span
                                                class="float-left subpixel-antialiased xsLight">
                                                {{ $t('Select properties') }}
                                            </span>
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
                                                        :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{ $t('Category') }}</span>
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
                                                               class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                                        <p :class="[createProjectForm.assignedCategoryIds.includes(category.id)
                                                            ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                           class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                            {{ category.name }}
                                                        </p>
                                                    </div>
                                                    <div v-else class="text-secondary">
                                                        {{ $t('No categories created yet') }}
                                                    </div>
                                                </DisclosurePanel>
                                            </Disclosure>
                                            <hr class="border-gray-500 mt-2 mb-2">
                                            <Disclosure v-slot="{ open }">
                                                <DisclosureButton
                                                    class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                                >
                                                    <span
                                                        :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{ $t('Genre') }}</span>
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
                                                        {{ $t('No genres created yet') }}
                                                    </div>
                                                </DisclosurePanel>
                                            </Disclosure>
                                            <hr class="border-gray-500 mt-2 mb-2">
                                            <Disclosure v-slot="{ open }">
                                                <DisclosureButton
                                                    class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                                >
                                                    <span
                                                        :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{ $t('Area') }}</span>
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
                                                        {{ $t('No areas created yet') }}
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
                                              :displayed-text="this.categories.find(category => category.id === categoryId).name"
                                              :property="this.categories.find(category => category.id === categoryId)"></TagComponent>
                            </div>
                            <div v-for="genreId in createProjectForm.assignedGenreIds">
                                <TagComponent hide-x="true"
                                              :displayed-text="this.genres.find(genre => genre.id === genreId).name"
                                              :property="this.genres.find(genre => genre.id === genreId)"></TagComponent>
                            </div>
                            <div v-for="sectorId in createProjectForm.assignedSectorIds">
                                <TagComponent hide-x="true"
                                              :displayed-text="this.sectors.find(sector => sector.id === sectorId).name"
                                              :property="this.sectors.find(sector => sector.id === sectorId)"></TagComponent>
                            </div>
                        </div>
                        <div class="mb-2"  v-if="createSettings.attributes">
                            <div class="relative w-full">
                                <div class="w-full">
                                    <input id="projectGroupQuery" v-model="projectGroupQuery" type="text"
                                           autocomplete="off"
                                           :placeholder="$t('Which projects belong to this group?')"
                                           class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                </div>
                                <transition leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100"
                                            leave-to-class="opacity-0">
                                    <div
                                        v-if="projectGroupSearchResults.length > 0 && projectGroupQuery.length > 0"
                                        class="absolute z-10 mt-1 w-full max-h-60 bg-primary shadow-lg
                                                        text-base ring-1 ring-black ring-opacity-5
                                                        overflow-auto focus:outline-none sm:text-sm">
                                        <div class="border-gray-200">
                                            <div v-for="(projectGroup, index) in projectGroupSearchResults"
                                                 :key="index"
                                                 class="flex items-center cursor-pointer">
                                                <div class="flex-1 text-sm py-4">
                                                    <p @click="addProjectToProjectGroup(projectGroup)"
                                                       class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                        {{ projectGroup.name }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </transition>
                            </div>
                            <div v-if="projectGroupProjects.length > 0" class="mt-2 mb-4 flex items-center">
                                <span v-for="(projectGroupProject, index) in projectGroupProjects"
                                      class="flex rounded-full items-center font-bold text-primary">
                                    <span
                                        class="rounded-full items-center font-medium text-tagText border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
                                        {{ projectGroupProject.name }}
                                        <button type="button"
                                                @click="this.deleteProjectFromProjectGroup(index)">
                                            <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
                                        </button>
                                    </span>
                                </span>
                            </div>
                        </div>
                        <div class="w-full items-center text-center">
                            <FormButton
                                @click="addProject"
                                :disabled="this.createProjectForm.name === ''" :text="$t('Create')"/>
                        </div>
                    </div>
                </div>
            </div>
    </BaseModal>
</template>

<script>
import Input from "@/Layouts/Components/InputComponent.vue";
import Button from "@/Jetstream/Button.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
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
    MenuItems
} from "@headlessui/vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import IconLib from "@/Mixins/IconLib.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import ColorHelper from "@/Mixins/ColorHelper.vue";

export default {
    name: 'ProjectCreateModal',
    mixins: [IconLib, ColorHelper],
    components: {
        TextInputComponent,
        UserSearch,
        BaseModal,
        FormButton,
        ListboxOption,
        ListboxOptions,
        ListboxButton,
        Listbox,
        DisclosurePanel,
        DisclosureButton,
        Disclosure,
        Menu,
        MenuItems,
        MenuButton,
        XIcon,
        ChevronDownIcon,
        CheckIcon,
        JetDialogModal,
        TagComponent,
        Button,
        Input
    },
    emits: ['closeCreateProjectModal'],
    props: [
        'show',
        'categories',
        'genres',
        'sectors',
        'projectGroups',
        'states',
        'createSettings'
    ],
    data() {
        return {
            isCreateProjectTab: true,
            isCreateProjectGroupTab: false,
            addToProjectGroup: false,
            createProjectForm: useForm({
                name: "",
                assignedSectorIds: [],
                assignedCategoryIds: [],
                assignedGenreIds: [],
                isGroup: false,
                projects: [],
                selectedGroup: null,
                budgetDeadline: null,
                state: null,
                assignedUsers: [],
                cost_center: '',
            }),
            projectGroupProjects: [],
            projectGroupSearchResults: [],
            projectGroupQuery: '',
            selectedState: null,
            assignedUsers: [],
        }
    },
    computed: {
        tabs() {
            return [
                {
                    name: this.$t('Projects'),
                    current: this.isCreateProjectTab
                },
                {
                    name: this.$t('Project group'),
                    current: this.isCreateProjectGroupTab
                },
            ]
        },
    },
    methods: {
        addUserToProject(user) {
            if (!this.assignedUsers.includes(user)) {
                this.assignedUsers.push(user);
            }
        },
        removeUserFromProject(index) {
            this.assignedUsers.splice(index, 1);
        },
        changeTab(selectedTab) {
            if (selectedTab.name === this.$t('Projects')) {
                this.isCreateProjectTab = true;
                this.isCreateProjectGroupTab = false;
                this.createProjectForm.isGroup = false;
            } else {
                this.isCreateProjectGroupTab = true;
                this.isCreateProjectTab = false;
                this.createProjectForm.isGroup = true;
            }
        },
        addProject() {
            this.projectGroupProjects.forEach((projectToAdd) => {
                this.createProjectForm.projects.push(projectToAdd.id);
            });

            this.createProjectForm.assignedUsers = this.assignedUsers.map(user => user.id);
            this.createProjectForm.state = this.selectedState;

            this.createProjectForm.post(
                route('projects.store'), {
                    onSuccess: () => {
                        this.$emit('closeCreateProjectModal', true);
                    }
                }
            );
        },
        addProjectToProjectGroup(project) {
            this.projectGroupProjects.push(project);
            this.projectGroupQuery = '';
        },
        deleteProjectFromProjectGroup(index) {
            this.projectGroupProjects.splice(index, 1);
        },
    },
    watch: {
        projectGroupQuery: {
            handler() {
                if (this.projectGroupQuery.length > 0) {
                    axios.get('/projects/search/single', {
                        params: {query: this.projectGroupQuery, type: this.searchType}
                    }).then(response => {
                        this.projectGroupSearchResults = response.data
                    })
                }
            },
            deep: true
        },
    }
}
</script>
