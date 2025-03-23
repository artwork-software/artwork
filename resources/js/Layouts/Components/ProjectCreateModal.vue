<template>
    <BaseModal @closed="this.$emit('closeCreateProjectModal')" full-modal modal-size="max-w-3xl">
            <div class="">

                <div class="px-6 mt-5 modal-header"  v-if="!project">
                    <SwitchGroup as="div" class="flex items-center">
                        <SwitchLabel as="span" class="mr-3 model-title cursor-pointer" :class="!createProjectGroup ? '' : '!text-gray-300'">
                            {{ $t('Project') }}
                        </SwitchLabel>
                        <Switch v-model="createProjectGroup" :class="[createProjectGroup ? 'bg-artwork-buttons-create' : 'bg-artwork-buttons-create', 'relative inline-flex h-5 w-12 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none']">
                            <span aria-hidden="true" :class="[createProjectGroup  ? 'translate-x-7' : 'translate-x-0', 'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                        </Switch>
                        <SwitchLabel as="span" class="ml-3 model-title cursor-pointer" :class="createProjectGroup ? '' : '!text-gray-300'">
                            {{ $t('Project group') }}
                        </SwitchLabel>
                    </SwitchGroup>
                </div>
                <div v-else>
                    <ModalHeader
                        :title="project ? $t('Edit basic data') : isCreateProjectTab ? $t('New project') : $t('New project group')"
                        :description="$t('Please fill in the following fields to create a new project.')"
                        full-modal
                    />
                </div>
                <div v-if="!createProjectGroup">
                    <div>
                       <div v-if="project" class="px-6 py-2">
                           <KeyVisual :project="project"  />
                       </div>
                        <div>
                            <div class="px-6 py-2">
                                <div class="relative flex w-full">
                                    <TextInputComponent
                                        id="projectName"
                                        v-model="createProjectForm.name"
                                        :label="$t('Project name*')"
                                        />
                                </div>
                                <div class="pt-1" v-if="createSettings.show_artists">
                                    <TextInputComponent
                                        id="create-settings.show-artists"
                                        v-model="createProjectForm.artists"
                                        :label="$t('Artists')"
                                    />
                                </div>
                                <div v-if="showInvalidProjectNameHelpText" class="text-error text-xs mt-1">
                                    {{ $t('Project name is a required field.')}}
                                </div>
                            </div>
                            <div class="px-6 pt-4" v-if="createSettings.attributes">
                                <Menu as="div" class="inline-block text-left w-full relative">
                                    <div>
                                        <MenuButton class="menu-button">
                                            <span>{{ $t('Select properties') }}</span>
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
                                                                   class="input-checklist-dark"/>
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
                                                                   class="input-checklist-dark"/>
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
                                                                   class="input-checklist-dark"/>
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
                                <div :class="createProjectForm.assignedCategoryIds || createProjectForm.assignedGenreIds || createProjectForm.assignedSectorIds ? 'mt-2' : ''">
                                    <TagComponent v-for="categoryId in createProjectForm.assignedCategoryIds" hide-x="true"
                                                  :displayed-text="this.categories.find(category => category.id === categoryId).name"
                                                  :property="this.categories.find(category => category.id === categoryId)"
                                    />
                                    <TagComponent v-for="genreId in createProjectForm.assignedGenreIds" hide-x="true"
                                                  :displayed-text="this.genres.find(genre => genre.id === genreId).name"
                                                  :property="this.genres.find(genre => genre.id === genreId)"
                                    />
                                    <TagComponent hide-x="true" v-for="sectorId in createProjectForm.assignedSectorIds"
                                                      :displayed-text="this.sectors.find(sector => sector.id === sectorId).name"
                                                      :property="this.sectors.find(sector => sector.id === sectorId)"
                                    />
                                </div>
                            </div>

                            <div class="flex px-6 pb-5 pt-4 w-full" v-if="createSettings.state">
                                <ProjectStateListbox :projectStates="states"
                                                     :selectedProjectState="selectedState"
                                                     @update:selectedProjectState="selectedState = $event"/>
                            </div>

                            <div class="px-6 py-6 bg-lightBackgroundGray" v-if="createSettings.managers">
                                <div class="font-semibold text-sm pb-2">{{ $t('Project management')}}</div>
                                <UserSearch @user-selected="addUserToProject" only-manager />

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
                                <TextInputComponent
                                    id="costCenter"
                                    v-model="createProjectForm.cost_center"
                                    :label="$t('Name of the cost unit')"
                                />
                            </div>

                            <div class="px-6 py-2" v-if="!project?.is_group || !project">

                                <div class="flex items-center ">
                                    <input id="addToProjectGroup" type="checkbox" v-model="this.addToProjectGroup"
                                           class="input-checklist"/>
                                    <label for="addToProjectGroup"
                                           :class="this.addToProjectGroup ? 'xsDark' : 'xsLight subpixel-antialiased'"
                                           class="ml-2">
                                        {{ $t('Belongs to project group') }}
                                    </label>
                                </div>
                                <div v-if="this.addToProjectGroup" class="pt-5">
                                    <ProjectSearch @project-selected="createProjectForm.selectedGroup = $event" only-project-groups label="Search project group" v-if="!createProjectForm.selectedGroup"/>

                                    <div v-else class="flex items-center gap-x-2 justify-between">
                                        <div>
                                            <h3 class="font-semibold text-base">{{ createProjectForm.selectedGroup.name }}</h3>
                                        </div>
                                        <div class="text-blue-500 text-xs underline cursor-pointer" @click="createProjectForm.selectedGroup = null">
                                            Projektgruppenauswahl aufheben
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="createSettings.budget_deadline" class="px-6 py-2">
                                <DateInputComponent
                                    id="budgetDeadline"
                                    v-model="createProjectForm.budget_deadline"
                                    :label="$t('Budget deadline')" />
                            </div>
                        </div>
                        <div class="w-full flex items-center justify-end gap-x-4 pb-6 px-6">
                            <BaseButton
                                v-if="!project && $can('can create events when creating a project')"
                                @click="addProject(true)"
                                :text="$t('Set up events')"
                                class="mt-8 inline-flex items-center"
                                classes="!w-fit gap-x-2 h-12 bg-artwork-buttons-create">
                                <IconCalendarMonth class="w-5 h-5" />
                            </BaseButton>
                            <BaseButton
                                type="submit"
                                @click="addProject(false)"
                                :text="project ? $t('Save') : $t('Create')"
                                class="mt-8 inline-flex items-center "
                                classes="!w-fit gap-x-2 h-12"
                            >
                                <IconCirclePlus class="w-5 h-5" />
                            </BaseButton>
                        </div>
                    </div>
                </div>
                <div v-if="createProjectGroup && !project">
                    <div class="px-6 pb-6">
                        <div class="flex justify-between">
                            <div class="flex items-center gap-x-2">
                                <IconSelector @update:modelValue="addIconToForm" :current-icon="createProjectForm.icon" />
                                <TinyPageHeadline
                                    title="Icon"
                                    description="Wähle ein Icon für die Projektgruppe aus."
                                />

                            </div>
                            <div class="flex items-center gap-x-2">
                                <div class="">
                                    <ColorPickerComponent @updateColor="addColorToProject" color="#ccc" />
                                </div>
                                <TinyPageHeadline
                                    title="Farbe"
                                    description="Wähle eine Farbe für die Projektgruppe aus."
                                    />

                            </div>
                        </div>
                        <div class="w-full col-span-4">
                            <TextInputComponent
                                id="sourceName"
                                v-model="createProjectForm.name"
                                :label="$t('Title*')"
                            />
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-x-4">
                            <div class="flex flex-col items-center justify-center w-full h-full">

                            </div>


                        </div>
                        <div class="mb-2 mt-5" v-if="createSettings.attributes">
                            <Menu as="div" class="inline-block text-left w-full">
                                <div>
                                    <MenuButton class="menu-button">
                                            <span class="float-left subpixel-antialiased xsLight">
                                                {{ $t('Select properties') }}
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
                                    <MenuItems class="absolute overflow-y-auto h-48 mt-2 w-80 origin-top-left divide-y divide-gray-200 rounded-sm bg-primary ring-1 ring-black p-2 text-white opacity-100 z-50">
                                        <div class="mx-auto w-full max-w-md rounded-2xl bg-primary border-none mt-2">
                                            <Disclosure v-slot="{ open }">
                                                <DisclosureButton class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500">
                                                    <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{ $t('Category') }}</span>
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
                        <div class="w-full flex items-center justify-end gap-x-4 pb-6">
                            <BaseButton
                                v-if="!project"
                                @click="addProject(true)"
                                :text="$t('Set up events')"
                                class="mt-8 inline-flex items-center"
                                classes="!w-fit gap-x-2 h-12 bg-artwork-buttons-create">
                                <IconCalendarMonth class="w-5 h-5" />
                            </BaseButton>
                            <BaseButton
                                type="submit"
                                @click="addProject(false)"
                                :text="project ? $t('Save') : $t('Create')"
                                class="mt-8 inline-flex items-center "
                                classes="!w-fit gap-x-2 h-12"
                            >
                                <IconCirclePlus class="w-5 h-5" />
                            </BaseButton>
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
    MenuItems,
    Switch,
    SwitchGroup,
    SwitchLabel
} from "@headlessui/vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import IconLib from "@/Mixins/IconLib.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import ColorHelper from "@/Mixins/ColorHelper.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import ProjectStateListbox from "@/Components/Listboxes/ProjectStateListbox.vue";
import ProjectGroupListbox from "@/Components/Listboxes/ProjectGroupListbox.vue";
import KeyVisual from "@/Components/Uploads/KeyVisual.vue";
import DateInputComponent from "@/Components/Inputs/DateInputComponent.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import BaseTabs from "@/Components/Tabs/BaseTabs.vue";
import ProjectSearch from "@/Components/SearchBars/ProjectSearch.vue";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import Permissions from "@/Mixins/Permissions.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import IconSelector from "@/Components/Icon/IconSelector.vue";
import ColorPickerComponent from "@/Components/Globale/ColorPickerComponent.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";

export default {
    name: 'ProjectCreateModal',
    mixins: [IconLib, ColorHelper, Permissions],
    components: {
        TinyPageHeadline,
        ColorPickerComponent,
        IconSelector,
        TextareaComponent,
        BaseButton,
        Switch,
        SwitchLabel,
        SwitchGroup,
        ProjectSearch,
        BaseTabs,
        ModalHeader,
        DateInputComponent,
        KeyVisual,
        ProjectGroupListbox,
        ProjectStateListbox,
        JetInputError,
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
    emits: ['closeCreateProjectModal', 'dropFeedback'],
    props: [
        'show',
        'categories',
        'genres',
        'sectors',
        'projectGroups',
        'states',
        'createSettings',
        'project'
    ],
    data() {
        return {
            isCreateProjectTab: true,
            isCreateProjectGroupTab: false,
            addToProjectGroup: this.project ? !!this.project?.groups[0] : false,
            createProjectForm: useForm({
                name: this.project ? this.project.name : '',
                artists: this.project ? this.project.artists : '',
                assignedSectorIds: this.project ? this.project?.sectors?.map(sector => sector.id) : [],
                assignedCategoryIds: this.project ? this.project?.categories?.map(category => category.id) : [],
                assignedGenreIds: this.project ? this.project?.genres?.map(genre => genre.id) : [],
                isGroup: this.project ? this.project.is_group : false,
                projects: [],
                selectedGroup: this.project ? this.project?.groups[0] : null,
                budget_deadline: this.project ? this.project.budget_deadline : '',
                state: null,
                assignedUsers: [],
                cost_center: this.project ? this.project?.cost_center?.name : '',
                icon: this.project ? this.project.icon : 'IconPhotoCircle',
                color: this.project ? this.project.color : null,
            }),
            projectGroupProjects: [],
            projectGroupSearchResults: [],
            projectGroupQuery: '',
            selectedState: this.project ? this.project?.state?.id : null,
            assignedUsers: this.project ? this.project.manager_users ? this.project.manager_users : [] : [],
            keyVisualForm: useForm({
                keyVisual: null,
            }),
            uploadKeyVisualFeedback: "",
            createProjectGroup: false,
            showInvalidProjectNameHelpText: false,

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
            // check if user is already in the list
            if (!this.assignedUsers.find(assignedUser => assignedUser.id === user.id)) {
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
        addIconToForm(icon){
            this.createProjectForm.icon = icon;
        },
        addColorToProject(color){
            this.createProjectForm.color = color;
        },
        addProject(bool) {
            if (this.createProjectForm.name === '') {
                this.showInvalidProjectNameHelpText = true;
                return;
            }

            this.projectGroupProjects.forEach((projectToAdd) => {
                this.createProjectForm.projects.push(projectToAdd.id);
            });

            this.createProjectForm.assignedUsers = this.assignedUsers?.map(user => user.id);
            this.createProjectForm.state = this.selectedState;

            if ( this.createProjectGroup ){
                this.createProjectForm.isGroup = true;
            }

            if (this.project) {
                this.createProjectForm.patch(
                    route('projects.update', this.project.id), {
                        onSuccess: () => {
                            this.$emit('closeCreateProjectModal', bool);
                        }
                    }
                );
            } else {
                this.createProjectForm.post(
                    route('projects.store'), {
                        onSuccess: () => {
                            this.$emit('closeCreateProjectModal', bool);
                            this.$emit('dropFeedback');
                        }
                    }
                );
            }
        },
        addProjectToProjectGroup(project) {
            if(!this.projectGroupProjects.includes(project)){
                this.projectGroupProjects.push(project);
            }
        },
        deleteProjectFromProjectGroup(index) {
            this.projectGroupProjects.splice(index, 1);
        },

    }
}
</script>
