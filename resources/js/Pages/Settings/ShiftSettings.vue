<template>
    <ShiftSettingsHeader :title="$t('Shift Settings')">
        <SettingsGuideBanner
            storage-key="settings-guide.shift.general"
            title="How the shift settings work"
            :paragraphs="[
                'This tab bundles the master data for shift planning: crafts, craft functions, global qualifications and time presets. Crafts and their functions are the basis of every shift — each shift belongs to a craft and its staffing demand is defined per craft function.',
                'You also configure global behaviour switches here, such as the permission model, the release workflow, overbooking and the shift plan subscription. All changes are saved immediately.'
            ]"
        />
        <div class="my-10 grid gap-5 xl:grid-cols-2">
            <div v-if="hasAdminRole()" class="rounded-2xl border border-accent-200 bg-accent-50 p-5">
                <div class="flex items-start justify-between gap-5">
                    <BasePageTitle
                        :title="$t('Permission model')"
                        :description="$t('In simple mode, the existing general shift-settings permission continues to grant access to all areas. In granular mode, separate read and edit permissions are evaluated for each settings area.')"
                    />
                    <SwitchIconTooltip
                        v-model="shiftSettings.granular_permissions_enabled"
                        :tooltip-text="$t('Enable granular shift-setting permissions')"
                        size="md"
                        icon="IconKey"
                        @change="updateGranularPermissions"
                    />
                </div>
                <div class="mt-4 rounded-xl border border-accent-200 bg-white/80 px-4 py-3 text-xs leading-5 text-accent-700">
                    <strong>{{ shiftSettings.granular_permissions_enabled ? $t('Granular mode active') : $t('Simple mode active') }}</strong>
                    <p class="mt-1">
                        {{ shiftSettings.granular_permissions_enabled
                            ? $t('The general permission remains the module access. The subordinate permissions now determine which areas may be read or edited. Edit permission also includes read access.')
                            : $t('Existing houses retain the current behavior. Subordinate permissions are stored but are not evaluated.') }}
                    </p>
                    <p class="mt-1">{{ $t('When granular mode is enabled for the first time, all subordinate permissions are initially granted to existing holders of the general permission and can then be removed individually.') }}</p>
                </div>
            </div>

            <div class="rounded-2xl border border-violet-200 bg-violet-50/60 p-5">
                <div class="flex items-start justify-between gap-5">
                    <BasePageTitle
                        :title="$t('Uncommitted shifts in own roster')"
                        :description="$t('Controls whether people can see shifts in their own roster before those shifts have been committed.')"
                    />
                    <SwitchIconTooltip
                        v-model="shiftSettings.hide_uncommitted_shifts_from_own_roster"
                        :tooltip-text="canEditGeneralSettings
                            ? $t('Hide uncommitted shifts from own roster')
                            : $t('You have read access only')"
                        :disabled="!canEditGeneralSettings"
                        size="md"
                        icon="IconEyeOff"
                        @change="updateOwnRosterUncommittedShiftVisibility"
                    />
                </div>
                <div class="mt-4 rounded-xl border border-violet-200 bg-white/80 px-4 py-3 text-xs leading-5 text-violet-900">
                    <p v-if="shiftSettings.hide_uncommitted_shifts_from_own_roster">
                        {{ $t('Active: uncommitted shifts are hidden from a person in their own roster. The individual permission “View own uncommitted shifts” can be granted as an exception.') }}
                    </p>
                    <p v-else>
                        {{ $t('Inactive: current production behavior is preserved and people continue to see their own uncommitted shifts.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="my-10">
                <div class="rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5">

                    <div class="flex items-center justify-between">
                        <span class="flex grow flex-col">
                            <label class="font-lexend font-bold mb-1 text-text" id="availability-label">
                                {{ $t('Duty roster release workflow') }}
                            </label>
                            <span class="text-sm text-text-subtle w-1/2" id="availability-description">
                                {{ $t('Activates a two-stage approval process for schedules.If activated, authorized persons can send time periods (e.g. entire calendar weeks) to selected users for approval. They receive a notification and can approve or reject the schedule. If deactivated, authorized persons can approve schedules directly, without an additional approval process.') }}
                            </span>
                        </span>
                        <SwitchIconTooltip
                            v-model="shiftCommitWorkflow"
                            :tooltip-text="$t('Duty roster release workflow')"
                            size="md"
                            @change="changeShiftCommitWorkflow"
                            icon="IconCheck"
                        />
                    </div>



                    <div v-if="shiftCommitWorkflow">
                        <div class="mt-5 w-1/2">
                            <UserSearch
                                :label="$t('Select users who can confirm the shift commit requests')"
                                @user-selected="addUserToWorkflow"
                            />
                        </div>


                        <div>
                            <div v-if="shiftCommitWorkflowUsers?.length > 0" class="flex flex-wrap items-center gap-4 mt-3">
                                <div v-for="(object, index) in shiftCommitWorkflowUsers" class="group block shrink-0 bg-white w-fit pr-3 rounded-full border border-border-subtle">
                                    <div class="flex items-center">
                                        <div>
                                            <img class="inline-block size-9 rounded-full object-cover" :src="object.user.profile_photo_url" alt="" />
                                        </div>
                                        <div class="mx-2">
                                            <p class="text-sm/5 font-semibold text-text group-hover:text-text">{{ object.user.full_name }}</p>
                                        </div>
                                        <div class="flex items-center">
                                            <button type="button" @click="removeUserFormShiftWorkFlow(object.id)">
                                                <PropertyIcon name="IconX" class="h-4 w-4 text-text-subtle hover:text-danger" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>


            <div class="rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5">
                <div class="flex items-center justify-between gap-x-3">
                    <div class="w-1/2">
                        <BasePageTitle
                            :title="$t('Crafts')"
                            :description="$t('Define crafts to which you can later assign employees and shifts. Additionally, you can specify which users are allowed to assign what type of employee shifts.')"
                        />
                    </div>
                    <div class="flex items-center justify-end">
                        <BaseUIButton @click="openAddCraftsModal = true" label="New Craft" use-translation is-add-button />
                    </div>
                </div>
                <draggable
                    ghost-class="opacity-50"
                    key="draggableKey"
                    item-key="id"
                    :list="crafts"
                    @start="dragging = true"
                    @end="dragging = false"
                    @change="reorderCrafts(crafts)"
                    class="space-y-3 mt-5"
                >
                    <template #item="{ element }">
                        <div
                            class="group relative w-full rounded-2xl border border-border-subtle bg-white transition hover:border-border"
                            :class="dragging ? 'cursor-grabbing' : 'cursor-grab'"
                        >

                            <!-- Inhalt -->
                            <div class="pl-4 pr-3 sm:pl-6 sm:pr-4 py-4">
                                <div class="flex items-start gap-4">
                                    <!-- Drag-Handle -->
                                    <div class="mt-1 shrink-0 opacity-60 group-hover:opacity-100 transition">
                                        <PropertyIcon name="IconGripVertical" class="size-5" aria-hidden="true" />
                                    </div>

                                    <!-- Header: Name + Kürzel + Badges -->
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center flex-wrap gap-2">
                                            <div class="flex items-center gap-2">
                                              <span
                                                  class="inline-block size-5 rounded-full ring-2"
                                                  :style="{
                                                  backgroundColor: element.color + '33',
                                                  boxShadow: `inset 0 0 0 1px ${element.color}`
                                                }"
                                                  aria-hidden="true"
                                              />
                                                                            <h3 class="text-sm font-semibold text-text leading-6">
                                                                                {{ element.name }}
                                                                                <span class="text-text-subtle">({{ element.abbreviation }})</span>
                                                                            </h3>
                                                                        </div>

                                                                        <span
                                                                            v-if="element.universally_applicable"
                                                                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs ring-1 ring-inset
                                                     ring-border-subtle bg-surface-sunken text-text-muted"
                                                                        >
                                              <PropertyIcon name="IconShieldCheck" class="size-3.5" />
                                              {{ $t('Universally applicable') }}
                                            </span>

                                                                        <span
                                                                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs ring-1 ring-inset
                                                     ring-border-subtle bg-surface-sunken text-text-muted"
                                                                        >
                                              <PropertyIcon name="IconUsersGroup" class="size-3.5" />
                                              <span v-if="element.assignable_by_all">
                                                {{ $t('Assignable by all schedulers') }}
                                              </span>
                                              <span v-else>
                                                {{ $t('Restricted assignment') }}
                                              </span>
                                            </span>
                                        </div>

                                        <!-- Subtext-Zeilen -->
                                        <div class="mt-2 space-y-1.5 text-xs text-text-muted">
                                            <p v-if="element.assignable_by_all" class="leading-5">
                                                {{ $t('Assignable by all schedulers') }}
                                            </p>
                                            <p v-else class="leading-5">
                                                {{ $t('Can only be assigned by:') }}
                                                <span class="text-text">
                                            {{ (element.craft_shift_planer || []).map(u => u.full_name).join(', ') || '—' }}
                                          </span>
                                            </p>

                                            <p v-if="element.inventory_planned_by_all" class="leading-5">
                                                {{ $t('Inventory can be planned by all planners') }}
                                            </p>
                                            <p v-else class="leading-5">
                                                {{ $t('Inventory can only be planned by:') }}
                                                <span class="text-text">
                                                {{ (element.craft_inventory_planer || []).map(u => u.full_name).join(', ') || '—' }}
                                              </span>
                                            </p>

                                            <p class="leading-5 flex items-center gap-1.5">
                                                <PropertyIcon name="IconBell" class="size-3.5 shrink-0" />
                                                <span v-if="element.notify_days > 0">
                                                    {{ $t('Notification of shifts with open demand is sent {0} day(s) before the start of the shift', [element.notify_days]) }}
                                                </span>
                                                <span v-else>
                                                    {{ $t('Notification of shifts that are not fully staffed takes place on the same day as the shift starts') }}
                                                </span>
                                            </p>
                                        </div>

                                        <!-- Qualifications -->
                                        <div v-if="(element.qualifications || []).length" class="mt-3 flex flex-wrap gap-2">
                                            <span
                                                v-for="q in element.qualifications"
                                                :key="q.id"
                                                class="inline-flex items-center gap-1 rounded-full border border-border-subtle bg-white px-2 py-1 text-xs text-text-muted"
                                            >
                                              <PropertyIcon :name="q.icon" class="size-3.5" />
                                              {{ q.name }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="mt-1 flex items-start gap-2">
                                        <BaseMenu white-menu-background has-no-offset>
                                            <BaseMenuItem
                                                white-menu-background
                                                @click="updateCraft(element)"
                                                :title="$t('Edit')"
                                                icon="IconEdit"
                                            />
                                            <BaseMenuItem
                                                white-menu-background
                                                @click="openDeleteCraftModal(element)"
                                                :title="$t('Delete')"
                                                icon="IconTrash"
                                            />
                                        </BaseMenu>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </draggable>
            </div>
            <!--<div class="mt-10 rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5">
                <BasePageTitle
                    :title="$t('Shift-relevant Event Types')"
                    :description="$t('Determine which types of events are displayed as shift-relevant by default. These will then automatically appear in the \'shifts\' tab of the project. You can also define additional events as shift-relevant for each project.')"
                />
                <div class="mt-3">
                    <Listbox as="div">
                        <div class="relative mt-2 w-1/2">
                            <ListboxButton class="menu-button">
                                <span class="block truncate text-left pl-3">{{$t('Select Event Types')}}</span>
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                    <IconChevronDown  stroke-width="1.5" class="h-5 w-5 text-text" aria-hidden="true"/>
                                </span>
                            </ListboxButton>

                            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions class="absolute z-50 mt-1 max-h-28 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                                    <ListboxOption as="template" v-for="type in notRelevantEventTypes" :key="type.id" :value="type" v-slot="{ active, selected }">
                                        <li @click="addRelevantEventType(type)" :class="[active ? 'bg-accent-600 text-white' : 'text-text', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                            <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ type.name }}</span>
                                            <span v-if="selected" :class="[active ? 'text-white' : 'text-accent-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                <IconCheck stroke-width="1.5" class="h-5 w-5" aria-hidden="true" />
                                            </span>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </transition>
                        </div>
                    </Listbox>
                </div>
                <div class="mt-3 flex flex-wrap">
                    <TagComponent v-for="type in relevantEventTypes" :method="removeRelevantEventType" :displayed-text="type.name" :property="type" />
                </div>
            </div>-->

            <GlobalQualificationsSettingsCard :global-qualifications="globalQualifications" />

        <section class="mt-10">
            <!-- Card -->
            <div class="rounded-2xl border border-border-subtle bg-white/95 shadow-sm backdrop-blur">
                <!-- Header -->
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between px-5 py-4">
                    <BasePageTitle
                        :title="$t('Craft functions')"
                        :description="$t('Create or edit craft functions.')"
                    />
                    <div class="flex items-center gap-2">
                        <BaseUIButton
                            @click="openShiftQualificationModal('create')"
                            label="Neue Qualifikation"
                            use-translation
                            is-add-button
                        />
                    </div>
                </div>

                <!-- Content -->
                <div class="px-5 py-4">
                    <SettingsGuideBanner
                        variant="inline"
                        storage-key="settings-guide.shift.general.craft-functions"
                        title="Craft functions vs. global qualifications"
                        class="mb-4"
                        :paragraphs="[
                            'Craft functions define the staffing rows of a shift: for each function you specify how many people are needed (e.g. 2 technicians, 1 stage manager).',
                            'Global qualifications, on the other hand, are cross-craft attributes of people (e.g. driving licence, first aider) and do not create any demand of their own.'
                        ]"
                    />
                    <!-- Empty state -->
                    <div v-if="shiftQualifications.length === 0" class="flex items-center justify-between rounded-xl border border-dashed border-border bg-surface-sunken px-5 py-8">
                        <div class="flex items-start gap-3">
                            <div class="rounded-xl bg-white p-3 shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-width="1.5" d="M12 6v6l4 2" />
                                    <circle cx="12" cy="12" r="9" stroke-width="1.5" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-text">
                                    {{ $t('No qualifications have been created yet.') }}
                                </p>
                                <p class="mt-1 text-xs text-text-muted">
                                    {{ $t('Create your first qualification to use it in shifts and staffing rules.') }}
                                </p>
                            </div>
                        </div>
                        <BaseUIButton
                            @click="openShiftQualificationModal('create')"
                            size="sm"
                            label="Neue Qualifikation"
                            use-translation
                            is-add-button
                        />
                    </div>

                    <!-- List -->
                    <ul v-else role="list" class="space-y-2">
                        <transition-group
                            name="list-fade"
                            tag="div"
                            class="space-y-2 divide-y divide-border-subtle divide-dashed">
                            <li v-for="shiftQualification in shiftQualifications"
                                :key="shiftQualification.id"
                                class="group bg-white px-4 py-3 transition">
                                <div class="flex items-center justify-between gap-4 pb-2">
                                    <!-- Left: Icon + name + meta -->
                                    <div class="min-w-0 flex items-center gap-3">
                                        <div class="mt-0.5 rounded-lg bg-surface-sunken p-2 ring-1 ring-inset ring-border-subtle">
                                            <PropertyIcon
                                                stroke-width="1.5"
                                                class="text-text size-7"
                                                :name="shiftQualification.icon"
                                            />
                                        </div>
                                        <div class="min-w-0">
                                            <div class="">
                                                <h3 class="truncate text-sm font-medium text-text">
                                                    {{ shiftQualification.name }}
                                                </h3>

                                                <!-- Availability badge -->
                                                <span v-if="shiftQualification.available" class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium bg-success-surface text-success ring-1 ring-inset ring-success-border">
                                                    {{ $t('Considered for new shifts') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right: actions -->
                                    <div class="shrink-0">
                                        <BaseMenu white-menu-background has-no-offset>
                                            <BaseMenuItem
                                                white-menu-background
                                                @click="openShiftQualificationModal('edit', shiftQualification)"
                                                title="Edit"
                                                icon="IconEdit"
                                            />
                                            <BaseMenuItem
                                                v-if="shiftQualification.id > 1"
                                                white-menu-background
                                                @click="openDeleteQualificationModal(shiftQualification)"
                                                title="Delete"
                                                icon="IconTrash"
                                            />
                                        </BaseMenu>
                                    </div>
                                </div>
                            </li>
                        </transition-group>
                    </ul>
                </div>
            </div>
        </section>
            <div class="rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5 mt-10">
                <div class="flex items-center justify-between">
                    <BasePageTitle
                        class=""
                        :title="$t('Time presets for shifts')"
                        :description="$t('Create time presets for layers to be able to assign them quickly and easily later.')"
                    />

                    <BaseUIButton @click="showAddShiftPresetModal = true" label="New time preset" use-translation is-add-button />
                </div>
                <SettingsGuideBanner
                    variant="inline"
                    storage-key="settings-guide.shift.general.time-presets"
                    title="Time presets vs. shift templates"
                    class="mt-4"
                    :paragraphs="[
                        'A time preset only stores times (start, end, break) as a quick selection when creating shifts.',
                        'A shift template goes further: it combines times, craft and qualification demand and creates fully configured shifts — you manage templates in the \'Shift Templates\' tab.'
                    ]"
                />
                <div class="mt-5">
                    <AlertComponent
                        type="info"
                        show-icon
                        icon-size="h-6 w-6"
                        v-if="shiftTimePresets.length === 0"
                        :text="$t('No time presets for shifts have been created yet.')"
                        text-size="text-sm/5 font-bold text-text-subtle"
                    />
                    <ul v-else role="list" class="w-full">
                        <li v-for="(shiftTimePreset) in shiftTimePresets" :key="shiftTimePreset.id" class="py-4 pr-4 flex justify-between items-center border-b border-border-subtle">
                            <div class="text-base/5 font-semibold text-text">
                                <div>
                                    {{ shiftTimePreset.name }}
                                </div>
                                <div class="flex items-center gap-x-2 text-text-subtle text-xs">
                                    <div>{{ shiftTimePreset.start_time }} - {{ shiftTimePreset.end_time}} </div>
                                    <div v-if="shiftTimePreset.break_time !== 0">{{ $t('Break time')}}: {{ shiftTimePreset.break_time }}
                                        {{ $t('Minutes') }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-x-3">
                                <PropertyIcon name="IconEdit" stroke-width="1.5" class="h-5 w-5 cursor-pointer" aria-hidden="true" @click="openAddEditShiftPresetModal(shiftTimePreset)"/>

                                <PropertyIcon name="IconTrash" stroke-width="1.5" class="h-5 w-5 text-danger cursor-pointer" aria-hidden="true" @click="openDeleteShiftTimePresetModal(shiftTimePreset)"/>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="flex flex-col my-10 gap-2 rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5">
                <BasePageTitle :title="$t('Sort settings')"
                                  :description="$t('Configure the behaviour of shift plans sort opportunity.')"/>
                <SwitchGroup as="div" class="flex flex-row items-center gap-x-2 cursor-pointer mt-4">
                    <SwitchLabel as="span" class='text-sm'>
                        <span :class="[!shiftSettings.use_first_name_for_sort ? 'font-bold' : 'font-medium', 'text-text']">{{ $t('Sort by first name')}}</span>
                    </SwitchLabel>
                    <Switch v-model="shiftSettings.use_first_name_for_sort"
                            @update:model-value="this.updateShiftSettingUseFirstNameSort"
                            :class="[
                                shiftSettings.use_first_name_for_sort ?
                                    'bg-accent-600' :
                                    'bg-border-subtle',
                                'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-accent-600 focus:ring-offset-2'
                            ]">
                        <span aria-hidden="true" :class="[shiftSettings.use_first_name_for_sort ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                    </Switch>
                    <SwitchLabel as="span" class="text-sm">
                        <span :class="[shiftSettings.use_first_name_for_sort ? 'font-bold' : 'font-medium', 'text-text']">{{ $t('Sort by last name')}}</span>
                    </SwitchLabel>
                </SwitchGroup>
            </div>

            <div class="flex flex-col gap-2 rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5 mb-10">
                <BasePageTitle
                    :title="$t('Shifts in shift plan subscription')"
                    :description="$t('When a user subscribes to their own shift plan, the following shifts should appear:')"
                />
                <SwitchGroup as="div" class="flex flex-row items-center gap-x-2 cursor-pointer mt-4">
                    <SwitchLabel as="span" class="text-sm">
                        <span class="flex items-center gap-x-1">
                            <span :class="[!shiftSettings.calendar_abo_show_all_shifts ? 'font-bold' : 'font-medium', 'text-text']">
                                {{ $t('Only committed shifts') }}
                            </span>
                            <ToolTipComponent
                                direction="right"
                                :tooltip-text="$t('A committed shift is a shift that has been declared as finished in the duty roster by a shift planner using the button Commit all shifts and, if active, has been approved by the duty roster release workflow.')"
                                icon="IconInfoCircle"
                                icon-size="h-4 w-4"
                                classes-button=""
                                no-relative
                            />
                        </span>
                    </SwitchLabel>
                    <Switch v-model="shiftSettings.calendar_abo_show_all_shifts"
                            @update:model-value="updateCalendarAboShowAllShifts"
                            :class="[
                                shiftSettings.calendar_abo_show_all_shifts ?
                                    'bg-accent-600' :
                                    'bg-border-subtle',
                                'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-accent-600 focus:ring-offset-2'
                            ]">
                        <span aria-hidden="true" :class="[shiftSettings.calendar_abo_show_all_shifts ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                    </Switch>
                    <SwitchLabel as="span" class="text-sm">
                        <span :class="[shiftSettings.calendar_abo_show_all_shifts ? 'font-bold' : 'font-medium', 'text-text']">
                            {{ $t('All shifts') }}
                        </span>
                    </SwitchLabel>
                </SwitchGroup>
            </div>

            <div class="flex flex-col gap-2 rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5 mb-10">
                <BasePageTitle
                    :title="$t('Overbooking of shifts')"
                    :description="$t('If activated, planners can assign more people to a shift than the defined demand. Overbooked positions are marked separately and the demand remains unchanged.')"
                />
                <SwitchGroup as="div" class="flex flex-row items-center gap-x-2 cursor-pointer mt-4">
                    <SwitchLabel as="span" class="text-sm">
                        <span :class="[!shiftSettings.allow_shift_overbooking ? 'font-bold' : 'font-medium', 'text-text']">
                            {{ $t('Deactivated') }}
                        </span>
                    </SwitchLabel>
                    <Switch v-model="shiftSettings.allow_shift_overbooking"
                            @update:model-value="updateAllowShiftOverbooking"
                            :class="[
                                shiftSettings.allow_shift_overbooking ?
                                    'bg-accent-600' :
                                    'bg-border-subtle',
                                'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-accent-600 focus:ring-offset-2'
                            ]">
                        <span aria-hidden="true" :class="[shiftSettings.allow_shift_overbooking ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                    </Switch>
                    <SwitchLabel as="span" class="text-sm">
                        <span :class="[shiftSettings.allow_shift_overbooking ? 'font-bold' : 'font-medium', 'text-text']">
                            {{ $t('Allow overbooking') }}
                        </span>
                    </SwitchLabel>
                </SwitchGroup>
            </div>
        <ShiftQualificationModal
            v-if="this.showShiftQualificationModal"
            :show="this.showShiftQualificationModal"
            :mode="this.shiftQualificationModalMode"
            :shift-qualification="this.shiftQualificationModalShiftQualification"
            @close="this.closeShiftQualificationModal"
        />
        <success-modal
            v-if="this.$page.props.flash.success?.shift_qualification"
            :title="$t('Qualification')"
            :description="this.$page.props.flash.success?.shift_qualification"
            :button="$t('Close message')"
            @closed="this.$page.props.flash.success.shift_qualification = null"
        />
        <error-component
            v-if="this.$page.props.flash.error?.shift_qualification"
            :titel="$t('Qualification')"
            :description="this.$page.props.flash.error?.shift_qualification"
            @closed="this.$page.props.flash.error.shift_qualification = null"
            :confirm="$t('Close message')"
        />
        <AddEditShiftTimePreset :time-preset="presetToEdit" @closed="closeShiftPresetModal" v-if="showAddShiftPresetModal" />
        <AddCraftsModal @closed="closeAddCraftModal" v-if="openAddCraftsModal" :craft-to-edit="craftToEdit" :users-with-permission="usersWithPermission" :users-with-inventory-permission="usersWithInventoryPermission" :prop-qualifications="shiftQualifications" />
        <ConfirmDeleteModal :title="confirmDeleteTitle" :description="confirmDeleteDescription" @closed="closedDeleteCraftModal" @delete="submitDelete" v-if="openConfirmDeleteModal" />
    </ShiftSettingsHeader>
</template>
<script>
import {defineComponent} from 'vue'
import ShiftSettingsHeader from "@/Pages/Settings/Components/ShiftSettingsHeader.vue";
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
    Switch,
    SwitchGroup,
    SwitchLabel
} from "@headlessui/vue";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import AddCraftsModal from "@/Layouts/Components/AddCraftsModal.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import ShiftQualificationModal from "@/Layouts/Components/ShiftQualificationModal.vue";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import ErrorComponent from "@/Layouts/Components/ErrorComponent.vue";
import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";
import IconLib from "@/Mixins/IconLib.vue";
import TabComponent from "@/Components/Tabs/TabComponent.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import AddEditShiftTimePreset from "@/Pages/Settings/Components/AddEditShiftTimePreset.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import draggable from "vuedraggable";
import {router, useForm, usePage} from "@inertiajs/vue3";
import ShiftQualificationIconCollection from "@/Layouts/Components/ShiftQualificationIconCollection.vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import Button from "@/Jetstream/Button.vue";
import {IconCheck, IconChevronDown, IconCirclePlus, IconCopy, IconDotsVertical, IconEdit, IconGripVertical, IconTrash, IconX} from "@tabler/icons-vue";
import BaseTabs from "@/Artwork/Tabs/BaseTabs.vue";
import ShiftTabs from "@/Pages/Shifts/Components/ShiftTabs.vue";
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import SwitchIconTooltip from "@/Artwork/Toggles/SwitchIconTooltip.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import GlobalQualificationsSettingsCard from "@/Pages/Settings/ShiftSettingsComponents/GlobalQualificationsSettingsCard.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import {can, is} from 'laravel-permission-to-vuejs';

export default defineComponent({
    name: "ShiftSettings",
    mixins: [IconLib],
    components: {
        GlobalQualificationsSettingsCard,
        SettingsGuideBanner,
        ToolTipComponent,
        PropertyIcon,
        SwitchIconTooltip,
        BaseMenuItem,
        BaseUIButton,
        BaseButton,
        BasePageTitle,
        ShiftSettingsHeader,
        ShiftTabs,
        BaseTabs,
        Button, IconX,
        UserSearch,
        ShiftQualificationIconCollection,
        SwitchLabel,
        Switch,
        SwitchGroup,
        draggable,
        AlertComponent,
        AddEditShiftTimePreset,
        TinyPageHeadline,
        BaseMenu,
        TabComponent,
        AddButtonSmall,
        ErrorComponent,
        SuccessModal,
        ShiftQualificationModal,
        ConfirmDeleteModal,
        TagComponent,
        AddCraftsModal,
        IconChevronDown,
        IconCheck,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        Listbox,
        IconEdit,
        MenuItem,
        Menu,
        MenuButton,
        SvgCollection,
        MenuItems,
        IconCopy,
        IconTrash,
        IconDotsVertical
    },
    props: [
        'crafts',
        'eventTypes',
        'usersWithPermission',
        'shiftQualifications',
        'shiftTimePresets',
        'usersWithInventoryPermission',
        'shiftSettings',
        'shiftCommitWorkflowUsers',
        'globalQualifications'
    ],
    data(){
        return {
            shiftCommitWorkflow: usePage().props.shiftCommitWorkflow,
            selectedEventType: null,
            openAddCraftsModal: false,
            craftToEdit: null,
            openConfirmDeleteModal: false,
            craftToDelete: null,
            shiftQualificationToDelete: null,
            shiftTimePresetToDelete: null,
            showShiftQualificationModal: false,
            shiftQualificationModalMode: null,
            shiftQualificationModalShiftQualification: null,
            showAddShiftPresetModal: false,
            presetToEdit: null,
            dragging: false,
            confirmDeleteTitle: '',
            confirmDeleteDescription: '',
            userForWorkflowForm: useForm({
                users: []
            }),
            deleteType: ''
        }
    },
    computed: {
        canEditGeneralSettings() {
            return !usePage().props.shift_settings_access?.granular_permissions_enabled
                || is('artwork admin')
                || can('shift.settings.general.edit');
        },
        relevantEventTypes(){
            const types = [];
            this.eventTypes.forEach((type) => {
                if(type.relevant_for_shift){
                    types.push(type)
                }
            })
            return types;
        },
        notRelevantEventTypes(){
            const types = [];
            this.eventTypes.forEach((type) => {
                if(!type.relevant_for_shift && type.id !== 1){
                    types.push(type)
                }
            })
            return types;
        }
    },
    methods: {
        hasAdminRole() {
            return is('artwork admin');
        },
        IconCheck,
        IconEdit,
        IconTrash,
        IconCirclePlus,
        IconGripVertical,
        addUserToWorkflow(user) {
            const userAlreadyExistsOnServer =
                this.shiftCommitWorkflowUsers.some(wu => wu.user?.id === user.id);

            const userAlreadyQueuedLocally =
                this.userForWorkflowForm.users.includes(user.id);

            if (this.userForWorkflowForm.processing || userAlreadyExistsOnServer || userAlreadyQueuedLocally) {
                console.warn('User already exists / queued or form is processing.');
                return;
            }

            // WICHTIG: Payload immer nur "dieser eine User"
            this.userForWorkflowForm.users = [user.id];

            this.userForWorkflowForm.patch(route('shift.settings.update.shift-commit-workflow-users'), {
                preserveScroll: true,
                preserveState: false,
                onSuccess: () => {
                    // Array leeren, damit der nächste Klick keinen "Altbestand" mitsendet
                    this.userForWorkflowForm.users = [];
                },
                onError: () => {
                    this.userForWorkflowForm.users = [];
                },
                onFinish: () => {
                    // zur Sicherheit auch hier leeren (falls onSuccess/onError nicht greift)
                    this.userForWorkflowForm.users = [];
                },
            });
        },

        removeUserFormShiftWorkFlow(objectId){
            this.$inertia.delete(route('shift.settings.remove.shift-commit-workflow-user', objectId), {
                preserveScroll: true,
                preserveState: false,
                onSuccess: () => {
                }
            });
        },
        changeShiftCommitWorkflow(){
            console.log(this.shiftCommitWorkflow)
            this.$inertia.patch(route('shift.settings.update.shift-commit-workflow'), {
                shift_commit_workflow: this.shiftCommitWorkflow
            }, {
                preserveScroll: true,
                preserveState: false
            });
        },
        openAddEditShiftPresetModal(shiftTimePreset){
            this.presetToEdit = shiftTimePreset;
            this.showAddShiftPresetModal = true;
        },
        closeShiftPresetModal(){
            this.presetToEdit = null;
            this.showAddShiftPresetModal = false;
        },
        deleteShiftTimePreset(preset){
            this.$inertia.delete(route('shift-time-preset.destroy', preset.id), {
                preserveScroll: true,
                preserveState: true
            })
        },
        openShiftQualificationModal(mode, shiftQualification = null) {
            this.shiftQualificationModalMode = mode;
            this.shiftQualificationModalShiftQualification = shiftQualification;
            this.showShiftQualificationModal = true;
        },
        closeShiftQualificationModal() {
            this.showShiftQualificationModal = false;
            this.shiftQualificationModalShiftQualification = null;
            this.shiftQualificationModalMode = null;
        },
        closeAddCraftModal(){
            this.openAddCraftsModal = false;
            this.craftToEdit = null;
        },
        addRelevantEventType(type){
            this.$inertia.patch(route('event-type.update.relevant', type), {
                relevant_for_shift: true
            });
        },
        removeRelevantEventType(type){
            this.$inertia.patch(route('event-type.update.relevant', type), {
                relevant_for_shift: false
            });

            return true;
        },
        updateCraft(craft){
            this.craftToEdit = craft;
            this.openAddCraftsModal = true;
        },
        openDeleteCraftModal(craft){
            this.craftToDelete = craft;
            this.confirmDeleteTitle = this.$t('Delete craft');
            this.confirmDeleteDescription = this.$t('Are you sure you want to delete the selected craft?');
            this.deleteType = 'craft';
            this.openConfirmDeleteModal = true;
        },
        closedDeleteCraftModal(){
            this.openConfirmDeleteModal = false;
            this.craftToDelete = null;
        },
        openDeleteQualificationModal(shiftQualificationToDelete){
            this.shiftQualificationToDelete = shiftQualificationToDelete;
            this.confirmDeleteTitle = this.$t('Delete qualification');
            this.confirmDeleteDescription = this.$t('Do you really want to delete the selected qualification?');
            this.deleteType = 'qualification';
            this.openConfirmDeleteModal = true;
        },
        closedDeleteQualificationModal(){
            this.openConfirmDeleteModal = false;
            this.deleteType = '';
            this.shiftQualificationToDelete = null;
        },
        openDeleteShiftTimePresetModal(preset){
            this.confirmDeleteTitle = this.$t('Delete time preset');
            this.confirmDeleteDescription = this.$t('Do you really want to delete the selected time preset?');
            this.deleteType = 'preset';
            this.shiftTimePresetToDelete = preset;
            this.openConfirmDeleteModal = true;
        },
        closeDeleteShiftTimePresetModal(){
            this.openConfirmDeleteModal = false;
            this.deleteType = '';
            this.shiftTimePresetToDelete = null;
        },
        submitDelete(){
            if (this.deleteType === 'craft') {
                this.$inertia.delete(route('craft.delete', this.craftToDelete.id), {
                    preserveScroll: true,
                    preserveState: true,
                    onFinish: () => {
                        this.closedDeleteCraftModal();
                    }
                });
            }
            if (this.deleteType === 'qualification'){
                this.$inertia.delete(
                    route(
                        'shift-qualifications.destroy',
                        {
                            shift_qualification: this.shiftQualificationToDelete.id
                        }
                    ),
                    {
                        preserveScroll: true,
                        onSuccess: this.closedDeleteQualificationModal
                    }
                );
            }
            if (this.deleteType === 'preset') {
                this.deleteShiftTimePreset(this.shiftTimePresetToDelete);
                this.closeDeleteShiftTimePresetModal();
            }
        },
        reorderCrafts(crafts) {
            crafts.map((craft, index) => {
                craft.position = index + 1
            })

            router.post(route('craft.reorder'), {
                crafts: crafts
            });
        },
        updateShiftSettingUseFirstNameSort(useFirstNameForSort) {
            router.patch(
                route('shift.settings.update.shift-settings.use-first-name-for-sort'),
                {
                    use_first_name_for_sort: useFirstNameForSort
                },
                {
                    preserveScroll: true
                }
            )
        },
        updateCalendarAboShowAllShifts(calendarAboShowAllShifts) {
            router.patch(
                route('shift.settings.update.calendar-abo-show-all-shifts'),
                {
                    calendar_abo_show_all_shifts: calendarAboShowAllShifts
                },
                {
                    preserveScroll: true
                }
            )
        },
        updateAllowShiftOverbooking(allowShiftOverbooking) {
            router.patch(
                route('shift.settings.update.allow-shift-overbooking'),
                {
                    allow_shift_overbooking: allowShiftOverbooking
                },
                {
                    preserveScroll: true
                }
            )
        },
        updateGranularPermissions(granularPermissionsEnabled) {
            router.patch(route('shift.settings.update.granular-permissions'), {
                granular_permissions_enabled: granularPermissionsEnabled
            }, { preserveScroll: true })
        },
        updateOwnRosterUncommittedShiftVisibility(hideUncommittedShifts) {
            router.patch(route('shift.settings.update.own-roster-uncommitted-visibility'), {
                hide_uncommitted_shifts_from_own_roster: hideUncommittedShifts
            }, { preserveScroll: true })
        }
    }
})
</script>
