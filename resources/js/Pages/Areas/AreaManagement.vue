<template>
    <RoomSettingsHeader
        :title="$t('Rooms & areas')"
        :description="$t('Create areas and rooms and assign side rooms to individual rooms. Also define global properties for rooms.')"
    >
                        <SettingsGuideBanner
                            class="mb-6"
                            storage-key="settings-guide.rooms.areas"
                            title="How does this area work?"
                            :paragraphs="[
                                'Areas group your rooms in the calendar. Rooms inherit the color of their area unless they have their own color.',
                                'Room categories and room properties are global filter dimensions: they let you filter the room rows in the calendars.',
                            ]"
                        />
                        <h2 class="font-lexend font-semibold text-[clamp(18px,2.5vw,20px)]/[25px] text-text w-full">{{ $t('Room properties') }}</h2>
                        <div class="text-sm/5 font-bold text-text-subtle flex mt-4 w-full">
                            {{
                                $t('Define room categories and properties. These can then be filtered in the calendars.')
                            }}
                        </div>
                        <SettingsGuideBanner
                            variant="inline"
                            class="w-full mt-4"
                            storage-key="settings-guide.rooms.areas.properties"
                            title="Categories or properties?"
                            :paragraphs="[
                                'Categories describe what a room is (e.g. stage, rehearsal stage, storage). Properties describe what a room offers (e.g. air conditioning, accessible).',
                                'Both are used purely for filtering in the calendars and have no effect on permissions.',
                                'Deleting a category or property removes its assignments from all rooms.',
                            ]"
                        />
                        <div class="w-full grid grid-cols-1 md:grid-cols-2 gap-6 my-10">

                            <!-- Raumkategorien -->
                            <div class="space-y-3">
                                <!-- Kopf: Titel + Counter -->
                                <div class="flex items-center justify-between">
      <span class="text-sm font-semibold text-text">
        {{ $t('Room categories') }}
      </span>
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] leading-none font-medium border border-text-inverse/30
               bg-gradient-to-br from-text-inverse/10 to-transparent
               text-accent-700 shadow-[inset_0_1px_0_rgba(255,255,255,0.45)] ring-1 ring-inset ring-white/40"
                                        :title="$t('Total')"
                                    >
                                    <span class="tabular-nums">{{ room_categories?.length || 0 }}</span>
                                    <span class="opacity-80">{{ $t('Total') }}</span>
                                  </span>
                                </div>

                                <!-- Eingabe + Add -->
                                <div class="flex items-center">
                                    <div class="relative w-full md:w-80">
                                        <BaseInput
                                            id="roomCategory"
                                            v-model="roomCategoryInput"
                                            :label="$t('Enter room category')"
                                            :error="roomCategoryError"
                                            @update:model-value="roomCategoryError = ''"
                                            v-on:keyup.enter="addRoomCategory"
                                        />
                                    </div>
                                    <div class="flex items-center h-full ml-2">
                                        <button
                                            :class="[!roomCategoryInput.trim() ? 'bg-text-subtle cursor-not-allowed' : 'bg-accent-600 hover:bg-accent-700 focus:outline-none', 'rounded-full ml-1 inline-flex items-center p-2 border border-transparent shadow-sm text-white transition']"
                                            @click="addRoomCategory"
                                            :disabled="!roomCategoryInput.trim()"
                                            :aria-disabled="!roomCategoryInput.trim()"
                                            :title="$t('Add category')"
                                        >
                                            <PropertyIcon name="IconCheck" stroke-width="1.5" class="h-4 w-4"/>
                                        </button>
                                    </div>
                                </div>

                                <!-- Chips -->
                                <TransitionGroup name="chip-fade" tag="div" class="mt-1 flex flex-wrap gap-2">
      <span
          v-for="(category, index) in room_categories"
          :key="category.id ?? category.name ?? index"
          class="inline-flex items-center gap-1.5 h-8 rounded-full px-3 text-sm font-medium border border-text-inverse/25
               bg-gradient-to-br from-white to-text-inverse/5
               text-text ring-1 ring-inset ring-white/40 shadow-sm"
      >
        <!-- kleiner Farbdot -->
        <span class="inline-block h-2 w-2 rounded-full bg-accent-700/80"></span>
        <span class="truncate max-w-[14rem]">{{ category.name }}</span>
        <span class="rounded-full bg-surface-sunken px-1.5 py-0.5 text-[11px] leading-none tabular-nums text-text-subtle"
              :title="$t('Used by {count} rooms', {count: categoryUsage[category.id] ?? 0})">{{ categoryUsage[category.id] ?? 0 }}</span>
        <button
            type="button"
            class="ml-1 inline-flex h-6 w-6 items-center justify-center rounded-full text-text-subtle hover:text-text hover:bg-surface-sunken/70 transition"
            @click="openRenameModal('category', category)"
            :title="$t('Rename')"
            :aria-label="$t('Rename')"
        >
          <PropertyIcon name="IconPencil" stroke-width="1.5" class="h-4 w-4"/>
        </button>
        <button
            type="button"
            class="inline-flex h-6 w-6 items-center justify-center rounded-full text-text-subtle hover:text-danger hover:bg-surface-sunken/70 transition"
            @click="this.showRoomCategoryDeleteModal(category)"
            :title="$t('Remove')"
            :aria-label="$t('Remove')"
        >
          <PropertyIcon name="IconX" stroke-width="1.5" class="h-4 w-4"/>
        </button>
      </span>
                                </TransitionGroup>
                            </div>

                            <!-- Raumattribute -->
                            <div class="space-y-3">
                                <!-- Kopf: Titel + Counter -->
                                <div class="flex items-center justify-between">
      <span class="text-sm font-semibold text-text">
        {{ $t('Room properties') }}
      </span>
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] leading-none font-medium border border-accent-200/60
               bg-gradient-to-br from-accent-100/80 to-white
               text-accent-700 shadow-[inset_0_1px_0_rgba(255,255,255,0.55)] ring-1 ring-inset ring-white/50"
                                        :title="$t('Total')"
                                    >
        <span class="tabular-nums">{{ room_attributes?.length || 0 }}</span>
        <span class="opacity-80">{{ $t('Total') }}</span>
      </span>
                                </div>

                                <!-- Eingabe + Add -->
                                <div class="flex items-center">
                                    <div class="relative w-full md:w-80">
                                        <BaseInput
                                            id="roomAttribute"
                                            v-model="roomAttributeInput"
                                            :label="$t('Enter room property')"
                                            :error="roomAttributeError"
                                            @update:model-value="roomAttributeError = ''"
                                            v-on:keyup.enter="addRoomAttribute"
                                        />
                                    </div>
                                    <div class="flex items-center ml-2 h-full">
                                        <button
                                            :class="[!roomAttributeInput.trim() ? 'bg-text-subtle cursor-not-allowed' : 'bg-accent-600 hover:bg-accent-700 focus:outline-none', 'rounded-full ml-1 inline-flex items-center p-2 border border-transparent shadow-sm text-white transition']"
                                            @click="addRoomAttribute"
                                            :disabled="!roomAttributeInput.trim()"
                                            :aria-disabled="!roomAttributeInput.trim()"
                                            :title="$t('Add property')"
                                        >
                                            <PropertyIcon name="IconCheck" stroke-width="1.5" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Chips -->
                                <TransitionGroup name="chip-fade" tag="div" class="mt-1 flex flex-wrap gap-2">
      <span
          v-for="(attribute, index) in room_attributes"
          :key="attribute.id ?? attribute.name ?? index"
          class="inline-flex items-center gap-1.5 h-8 rounded-full px-3 text-sm font-medium border border-text-inverse/25
               bg-gradient-to-br from-white to-text-inverse/5
               text-text ring-1 ring-inset ring-white/40 shadow-sm"
      >
        <span class="inline-block h-2 w-2 rounded-full bg-accent-500/80"></span>
        <span class="truncate max-w-[14rem]">{{ attribute.name }}</span>
        <span class="rounded-full bg-surface-sunken px-1.5 py-0.5 text-[11px] leading-none tabular-nums text-text-subtle"
              :title="$t('Used by {count} rooms', {count: attributeUsage[attribute.id] ?? 0})">{{ attributeUsage[attribute.id] ?? 0 }}</span>
        <button
            type="button"
            class="ml-1 inline-flex h-6 w-6 items-center justify-center rounded-full text-text-subtle hover:text-text hover:bg-surface-sunken/70 transition"
            @click="openRenameModal('attribute', attribute)"
            :title="$t('Rename')"
            :aria-label="$t('Rename')"
        >
          <PropertyIcon name="IconPencil" stroke-width="1.5" class="h-4 w-4"/>
        </button>
        <button
            type="button"
            class="inline-flex h-6 w-6 items-center justify-center rounded-full text-text-subtle hover:text-danger hover:bg-surface-sunken/70 transition"
            @click="this.showRoomAttributeDeleteModal(attribute)"
            :title="$t('Remove')"
            :aria-label="$t('Remove')"
        >
          <PropertyIcon name="IconX" stroke-width="1.5" class="h-4 w-4"/>
        </button>
      </span>
                                </TransitionGroup>
                            </div>

                        </div>

                        <div class="flex w-full justify-between">
                            <h2 class="font-lexend font-semibold text-[clamp(18px,2.5vw,20px)]/[25px] text-text">{{ $t('Areas ') }}</h2>
                        </div>
                        <div class="flex w-full justify-between mt-6">
                            <div class="flex flex-wrap items-center gap-3">
                                <BaseUIButton @click="openAddAreaModal()" :label="$t('Add area')" is-add-button />
                                <BaseUIButton
                                    v-if="areas.length > 1"
                                    variant="secondary"
                                    hide-icon
                                    :label="allAreasAreOpen ? $t('Collapse all') : $t('Expand all')"
                                    :disabled="hasActiveRoomFilters"
                                    @click="toggleAllAreas"
                                />
                                <BaseUIButton
                                    v-if="areas.some((area) => area.rooms?.length)"
                                    variant="secondary"
                                    icon="IconFileSpreadsheet"
                                    :label="$t('Export')"
                                    :title="hasActiveRoomFilters ? $t('Exports the rooms currently shown.') : $t('Exports all rooms.')"
                                    @click="exportRooms(hasActiveRoomFilters ? visibleRoomIds : [])"
                                />
                            </div>
                            <div class="flex items-end gap-3">
                                <div class="w-64">
                                    <BaseInput
                                        id="roomSearch"
                                        v-model="roomSearch"
                                        :label="$t('Search rooms')"
                                        is-small
                                    />
                                </div>
                            <BaseFilter only-icon white-background :has-active-filters="hasActivePropertyFilters">
                                <div class="p-3 space-y-5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold text-text">{{ $t('Filter rooms') }}</span>
                                        <button type="button"
                                                v-if="hasActivePropertyFilters"
                                                class="text-xs text-accent-600 hover:text-accent-700"
                                                @click="resetRoomFilter">
                                            {{ $t('Reset filters') }}
                                        </button>
                                    </div>
                                    <RoomPropertyCheckboxGroup
                                        :label="$t('Room categories')"
                                        :items="room_categories"
                                        v-model="roomFilterCategoryIds"
                                        :empty-text="$t('No room categories created yet')"
                                        single-column
                                    />
                                    <RoomPropertyCheckboxGroup
                                        :label="$t('Room properties')"
                                        :items="room_attributes"
                                        v-model="roomFilterAttributeIds"
                                        :empty-text="$t('No room properties created yet')"
                                        single-column
                                    />
                                </div>
                            </BaseFilter>
                            </div>
                        </div>
                    <div class="flex w-full flex-wrap mt-8">
                        <!-- Modernisierte Liste: Bereiche & Räume -->
                        <div v-if="hasActiveRoomFilters && filteredAreas.length === 0"
                             class="w-full my-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-dashed border-border-subtle bg-surface-sunken/40 p-4 text-sm text-text-subtle">
                            <span>{{ $t('No rooms found') }}</span>
                            <button type="button" class="text-accent-600 hover:text-accent-700" @click="resetRoomFilter">
                                {{ $t('Reset filters') }}
                            </button>
                        </div>
                        <div v-else-if="areas.length === 0"
                             class="w-full my-4 rounded-xl border border-dashed border-border-subtle bg-surface-sunken/40 p-4 text-sm text-text-subtle">
                            {{ $t('No areas yet. Create an area first, then add rooms to it.') }}
                        </div>
                        <div
                            v-for="area in filteredAreas"
                            :key="area.id"
                            class="group relative my-4 w-full rounded-2xl border border-border-subtle bg-white shadow-sm transition-shadow hover:shadow-md"
                        >
                            <div class="flex items-center justify-between gap-3 pl-5 pr-4 py-4">
                                <BaseUIButton
                                    variant="primary"
                                    hide-icon
                                    @click="changeAreaStatus(area)"
                                    :aria-expanded="isAreaOpen(area)"
                                    :aria-controls="`area-panel-${area.id}`"
                                >
                                    <PropertyIcon name="IconChevronUp"
                                        v-if="isAreaOpen(area)"
                                        class="h-5 w-5"
                                        stroke-width="1.5"
                                    />
                                    <PropertyIcon name="IconChevronDown"
                                        v-else
                                        class="h-5 w-5"
                                        stroke-width="1.5"
                                    />
                                    <span class="sr-only">
                                        {{ isAreaOpen(area) ? $t('Collapse') : $t('Expand') }}
                                    </span>
                                </BaseUIButton>

                                <div class="flex min-w-0 items-center gap-3">
                                    <SelectionCheckbox
                                        v-if="area.rooms?.length"
                                        :checked="areaSelectionState(area) === 'all'"
                                        :indeterminate="areaSelectionState(area) === 'some'"
                                        :label="$t('Select all rooms of this area')"
                                        @toggle="toggleAreaSelection(area)"
                                    />
                                    <span class="inline-block h-3.5 w-3.5 shrink-0 rounded-full border border-border"
                                          :style="{ backgroundColor: area.color ?? '#000000' }"
                                          :title="$t('Color')"></span>
                                    <span class="font-lexend font-semibold text-[clamp(18px,2.5vw,20px)]/[25px] text-text truncate">{{ area.name }}</span>

                                    <!-- Zähler-Chips (schöner) -->
                                    <div class="flex items-center gap-2">
                                        <!-- Rooms -->
                                        <span
                                            :title="$t('Rooms')"
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] leading-none font-medium border border-text-inverse/30
           bg-gradient-to-br from-text-inverse/10 to-transparent
           text-accent-700
           shadow-[inset_0_1px_0_rgba(255,255,255,0.45)]
           ring-1 ring-inset ring-white/40
           transition hover:ring-text-inverse/30"
                                            role="status"
                                            aria-live="polite"
                                        >
                                        <component :is="IconLayoutGrid" class="h-3.5 w-3.5 opacity-75"
                                                   stroke-width="1.75" aria-hidden="true"/>
                                        <span class="tabular-nums">
                                          {{ area.rooms?.filter(r => !r.temporary).length || 0 }}
                                        </span>
                                        <span class="opacity-80">{{ (area.rooms?.filter(r => !r.temporary).length || 0) === 1 ? $t('Room') : $t('Rooms') }}</span>
                                      </span>

                                        <!-- Temporary -->
                                        <span
                                            v-if="area.rooms?.some(r => r.temporary)"
                                            :title="$t('Temporary rooms')"
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] leading-none font-medium border border-warning-border/60
                                               bg-gradient-to-br from-warning-surface/90 to-warning-surface
                                               text-warning
                                               shadow-[inset_0_1px_0_rgba(255,255,255,0.6)]
                                               ring-1 ring-inset ring-white/50
                                               transition hover:ring-warning-border"
                                            role="status"
                                            aria-live="polite"
                                        >
                                        <!-- Pulsierender Status-Dot -->
                                        <span class="relative flex h-2 w-2">
                                          <span
                                              class="absolute inline-flex h-full w-full animate-ping rounded-full bg-warning/40"></span>
                                          <span class="relative inline-flex h-2 w-2 rounded-full bg-warning"></span>
                                        </span>
                                        <span class="tabular-nums">
                                          {{ area.rooms.filter(r => r.temporary).length }}
                                        </span>
                                        <span class="opacity-80">{{ $t('Temporary') }}</span>
                                      </span>
                                    </div>
                                </div>

                                <!-- Aktionen Bereich -->
                                <div class="ml-auto">
                                    <BaseMenu white-menu-background has-no-offset>
                                        <BaseMenuItem :icon="IconEdit" title="Edit" white-menu-background
                                                      @click="openEditAreaModal(area)"/>
                                        <BaseMenuItem :icon="IconCopy" title="Duplicate" white-menu-background
                                                      @click="duplicateArea(area)"/>
                                        <BaseMenuItem v-if="area.rooms?.length" :icon="IconRecycle" title="Remove all rooms" white-menu-background
                                                      @click="openDeleteAllRoomsModal(area)"/>
                                        <BaseMenuItem :icon="IconTrash" title="In the recycle bin" white-menu-background
                                                      @click="openSoftDeleteAreaModal(area)"/>
                                    </BaseMenu>
                                </div>
                            </div>

                            <!-- Inhalt (ein-/ausklappbar) -->
                            <Transition name="accordion">
                                <div
                                    v-if="isAreaOpen(area)"
                                    :id="`area-panel-${area.id}`"
                                    class="px-5 pb-5"
                                >
                                    <!-- Add Room + Hint -->
                                    <div class="flex items-center gap-3">
                                        <BaseUIButton @click="openAddRoomModal(area)" :label="$t('Add room')" is-add-button />
                                        <div v-if="this.$page.props.show_hints"
                                             class="flex items-center text-text-subtle">
                                            <SvgCollection svgName="arrowLeft" class="ml-1 h-4 w-4 opacity-70"/>
                                            <span class="ml-1 text-sm">{{ $t('Create new rooms') }}</span>
                                        </div>
                                    </div>

                                    <!-- Aktive Räume -->
                                    <div class="mt-6">
                                        <div
                                            v-if="(area.rooms?.filter(r => !r.temporary).length || 0) === 0"
                                            class="rounded-xl border border-dashed border-border-subtle bg-surface-sunken/40 p-4 text-sm text-text-subtle"
                                        >
                                            {{ $t('No rooms yet') }}.
                                        </div>

                                        <div v-else v-for="element in area.rooms" :key="element.id">
                                                <div v-show="!element.temporary" class="relative group mt-3">
                                                    <div
                                                        class="relative rounded-2xl border border-border-subtle bg-white/90 p-4 pl-5 shadow-sm ring-1 ring-transparent transition hover:border-text-inverse/30 hover:shadow-md focus-within:ring-2 focus-within:ring-text-inverse/30"
                                                    >
                                                        <div class="flex items-start justify-between gap-4">
                                                            <!-- Titel & Meta -->
                                                            <div class="min-w-0">
                                                                <div class="flex items-center gap-2">
                                                                    <SelectionCheckbox
                                                                        :checked="selectedRoomIds.includes(element.id)"
                                                                        :label="$t('Select room')"
                                                                        @toggle="toggleRoomSelection(element.id)"
                                                                    />
                                                                    <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full border border-border"
                                                                          :style="{ backgroundColor: element.color ?? area.color ?? '#000000' }"
                                                                          :title="element.color ? $t('Color') : $t('Inherit color from area')"></span>
                                                                    <Link
                                                                        :href="route('rooms.show', { room: element.id })"
                                                                        class="text-sm/5 font-semibold text-text block truncate hover:underline decoration-accent-700/50"
                                                                    >
                                                                        {{ element.name }}
                                                                    </Link>
                                                                </div>

                                                                <div
                                                                    class="mt-1 flex flex-wrap items-center gap-2 text-[11px] leading-tight text-text-subtle">
                                                                    <span class="inline-flex items-center gap-1">
                                                                        <PropertyIcon name="IconCalendar" class="h-3.5 w-3.5"
                                                                                      stroke-width="1.5"/>
                                                                        {{
                                                                            element.created_by ? $t('created on { created_at } by', {'created_at': element.created_at}) : element.created_at
                                                                        }}
                                                                    </span>
                                                                    <UserPopoverTooltip
                                                                        v-if="element.created_by"
                                                                        :user="element.created_by"
                                                                        lazy-load
                                                                        :id="element.created_by.id + '-room-' + element.id"
                                                                        height="5"
                                                                        width="5"
                                                                    />
                                                                    <span v-if="roomRowFacts(element).capacity" class="inline-flex items-center gap-1" :title="$t('Capacity')">
                                                                        <PropertyIcon name="IconArmchair" class="h-3.5 w-3.5" stroke-width="1.5"/>
                                                                        {{ roomRowFacts(element).capacity }}
                                                                    </span>
                                                                    <span v-for="categoryName in roomRowFacts(element).categories" :key="categoryName"
                                                                          class="rounded-full border border-border-subtle bg-surface-sunken px-2 py-0.5">
                                                                        {{ categoryName }}
                                                                    </span>
                                                                    <span v-if="roomRowFacts(element).hiddenFromCalendars"
                                                                          class="inline-flex items-center gap-1 rounded-full border border-warning-border bg-warning-surface px-2 py-0.5 text-warning"
                                                                          :title="$t('If \'Relevant for disposition\' is off, the room does not appear in any calendar, shift plan or export.')">
                                                                        <PropertyIcon name="IconCalendarOff" class="h-3.5 w-3.5" stroke-width="1.5"/>
                                                                        {{ $t('Not relevant for disposition') }}
                                                                    </span>
                                                                </div>
                                                            </div>

                                                            <!-- Actions -->
                                                            <div class="flex items-center gap-1">
                                                                <!-- Quick Actions (edit / duplicate) -->
                                                                <button
                                                                    type="button"
                                                                    class="inline-flex items-center rounded-lg p-1.5 text-text-subtle hover:bg-surface-sunken hover:text-text focus-visible:visible focus:outline-none focus:ring-2 focus:ring-border"
                                                                    @click="openEditRoomModal(element, area)"
                                                                    :title="$t('Edit')"
                                                                    :aria-label="$t('Edit')"
                                                                >
                                                                    <PropertyIcon name="IconEdit" class="h-4.5 w-4.5" stroke-width="1.75"/>
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    class="inline-flex items-center rounded-lg p-1.5 text-text-subtle hover:bg-surface-sunken hover:text-text focus-visible:visible focus:outline-none focus:ring-2 focus:ring-border"
                                                                    @click="duplicateRoom(element)"
                                                                    :title="$t('Duplicate')"
                                                                    :aria-label="$t('Duplicate')"
                                                                >
                                                                    <PropertyIcon name="IconCopy" class="h-4.5 w-4.5" stroke-width="1.75"/>
                                                                </button>

                                                                <!-- Mehr (Kontextmenü) -->
                                                                <BaseMenu white-menu-background has-no-offset>
                                                                    <BaseMenuItem :icon="IconEdit" title="Edit"
                                                                                  white-menu-background
                                                                                  @click="openEditRoomModal(element, area)"/>
                                                                    <BaseMenuItem :icon="IconCopy" title="Duplicate"
                                                                                  white-menu-background
                                                                                  @click="duplicateRoom(element)"/>
                                                                    <BaseMenuItem :icon="IconTrash"
                                                                                  title="In the recycle bin"
                                                                                  white-menu-background
                                                                                  @click="openSoftDeleteRoomModal(element)"/>
                                                                </BaseMenu>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                        </div>
                                    </div>

                                    <!-- Temporäre Räume (einklappbar) -->
                                    <div
                                        v-show="area.rooms?.some(r => r.temporary)"
                                        class="mt-10"
                                    >
                                        <button
                                            class="flex w-full items-center gap-2 rounded-lg px-2 py-1 text-left text-xs/[15px] font-semibold text-text text-warning hover:bg-warning-surface"
                                            @click="switchVisibility(area.id)"
                                        >
                                            {{ $t('Temporary rooms') }}
                                            <PropertyIcon name="IconChevronUp"
                                                v-if="showTemporaryRooms.includes(area.id)"
                                                class="h-4 w-4"
                                                stroke-width="1.5"
                                            />
                                            <PropertyIcon name="IconChevronDown"
                                                v-else
                                                class="h-4 w-4"
                                                stroke-width="1.5"
                                            />
                                        </button>

                                        <Transition name="fade">
                                            <div v-show="showTemporaryRooms.includes(area.id)">
                                                <div v-for="element in area.rooms.filter(r => r.temporary)"
                                                    :key="element.id"
                                                    class="mt-2"
                                                >
                                                        <div class="relative group mt-3">
                                                            <div
                                                                class="relative rounded-2xl border border-warning-border/80 bg-warning-surface/70 p-4 pl-5 shadow-sm ring-1 ring-transparent transition hover:border-warning-border hover:shadow-md focus-within:ring-2 focus-within:ring-warning-border/50"
                                                                @mouseover="showMenu = element.id"
                                                                @mouseout="showMenu = null"
                                                            >

                                                                <div class="flex items-start justify-between gap-4">
                                                                    <!-- Titel & Meta -->
                                                                    <div class="min-w-0">
                                                                        <div class="flex items-center gap-2">
                                                                            <SelectionCheckbox
                                                                                :checked="selectedRoomIds.includes(element.id)"
                                                                                :label="$t('Select room')"
                                                                                @toggle="toggleRoomSelection(element.id)"
                                                                            />
                                                                            <Link
                                                                                :href="route('rooms.show', { room: element.id })"
                                                                                class="text-sm/5 font-semibold text-text block truncate hover:underline decoration-warning"
                                                                            >
                                                                                {{ element.name }}
                                                                            </Link>
                                                                            <!-- Temporary-Status -->
                                                                            <span
                                                                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] leading-none font-medium border border-warning-border/60/60 bg-warning-surface/80 text-warning ring-1 ring-inset ring-white/50"
                                                                                :title="$t('Temporary rooms')"
                                                                            >
                                                                            <span class="relative flex h-2 w-2">
                                                                              <span
                                                                                  class="absolute inline-flex h-full w-full animate-ping rounded-full bg-warning/40"></span>
                                                                              <span
                                                                                  class="relative inline-flex h-2 w-2 rounded-full bg-warning"></span>
                                                                            </span>
                                                                            {{ $t('Temporary') }}
                                                                          </span>
                                                                        </div>

                                                                        <!-- Chips: Zeitraum + Temporary -->
                                                                        <div
                                                                            class="mt-2 flex flex-wrap items-center gap-1.5">
                                                                            <!-- Zeitraum -->
                                                                            <span
                                                                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] leading-none font-medium border border-warning-border
                       bg-gradient-to-br from-warning-surface/90 to-warning-surface
                       text-warning shadow-[inset_0_1px_0_rgba(255,255,255,0.6)] ring-1 ring-inset ring-white/40"
                                                                                :title="$t('Date range')"
                                                                            >
                                                                            <PropertyIcon name="IconCalendar" class="h-3.5 w-3.5"
                                                                                          stroke-width="1.5"/>
                                                                            <span class="tabular-nums">{{
                                                                                    element.start_date
                                                                                }}</span>
                                                                            <span class="opacity-60">–</span>
                                                                            <span class="tabular-nums">{{
                                                                                    element.end_date
                                                                                }}</span>
                                                                          </span>
                                                                        </div>

                                                                        <!-- Meta: erstellt am / von -->
                                                                        <div
                                                                            class="mt-2 flex flex-wrap items-center gap-2 text-[11px] leading-tight text-warning/90">
                                                                          <span class="inline-flex items-center gap-1">
                                                                            <component :is="IconClock"
                                                                                       class="h-3.5 w-3.5"
                                                                                       stroke-width="1.5"/>
                                                                            {{
                                                                                  element.created_by ? $t('created on { created_at } by', {'created_at': element.created_at}) : element.created_at
                                                                              }}
                                                                          </span>
                                                                            <UserPopoverTooltip
                                                                                v-if="element.created_by"
                                                                                :user="element.created_by"
                                                                                lazy-load
                                                                                :id="element.created_by.id + '-room-' + element.id"
                                                                                height="5"
                                                                                width="5"
                                                                            />
                                                                        </div>
                                                                    </div>

                                                                    <!-- Actions -->
                                                                    <div class="flex items-center gap-1">
                                                                        <!-- Quick Actions -->
                                                                        <button
                                                                            type="button"
                                                                            class="inline-flex items-center rounded-lg p-1.5 text-warning hover:bg-warning-surface hover:text-warning focus-visible:visible focus:outline-none focus:ring-2 focus:ring-warning-border"
                                                                            @click="openEditRoomModal(element, area)"
                                                                            :title="$t('Edit')"
                                                                            :aria-label="$t('Edit')"
                                                                        >
                                                                            <PropertyIcon name="IconEdit" class="h-4.5 w-4.5"
                                                                                      stroke-width="1.75"/>
                                                                        </button>
                                                                        <button
                                                                            type="button"
                                                                            class="inline-flex items-center rounded-lg p-1.5 text-warning hover:bg-warning-surface hover:text-warning focus-visible:visible focus:outline-none focus:ring-2 focus:ring-warning-border"
                                                                            @click="duplicateRoom(element)"
                                                                            :title="$t('Duplicate')"
                                                                            :aria-label="$t('Duplicate')"
                                                                        >
                                                                            <PropertyIcon name="IconCopy" class="h-4.5 w-4.5"
                                                                                      stroke-width="1.75"/>
                                                                        </button>

                                                                        <!-- Kontextmenü -->
                                                                        <BaseMenu white-menu-background has-no-offset>
                                                                            <BaseMenuItem :icon="IconEdit" title="Edit"
                                                                                          white-menu-background
                                                                                          @click="openEditRoomModal(element, area)"/>
                                                                            <BaseMenuItem :icon="IconCopy"
                                                                                          title="Duplicate"
                                                                                          white-menu-background
                                                                                          @click="duplicateRoom(element)"/>
                                                                            <BaseMenuItem :icon="IconTrash"
                                                                                          title="In the recycle bin"
                                                                                          white-menu-background
                                                                                          @click="openSoftDeleteRoomModal(element)"/>
                                                                        </BaseMenu>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                </div>

                                            </div>
                                        </Transition>
                                    </div>
                                </div>
                            </Transition>
                        </div>

                    </div>
    </RoomSettingsHeader>
    <!-- Areal Hinzufügen-->
    <ArtworkBaseModal
        v-if="showAddAreaModal"
        title="New area"
        modal-size="sm:max-w-lg"
        @close="closeAddAreaModal"
    >
        <form id="addAreaForm" @submit.prevent="addArea">
            <BaseInput
                id="newAreaName"
                v-model="newAreaForm.name"
                label="Name of the area"
                required
                autofocus
                :error="newAreaForm.errors.name"
            />
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="newAreaForm.processing"
                @click="closeAddAreaModal"
            />
            <BaseUIButton
                type="submit"
                form="addAreaForm"
                label="Create"
                variant="primary"
                :processing="newAreaForm.processing"
                :disabled="newAreaForm.processing || newAreaForm.name.trim() === ''"
            />
        </template>
    </ArtworkBaseModal>
    <!-- Areal Bearbeiten-->
    <ArtworkBaseModal
        v-if="showEditAreaModal"
        title="Edit area"
        modal-size="sm:max-w-lg"
        @close="closeEditAreaModal"
    >
        <form id="editAreaForm" class="space-y-4" @submit.prevent="editArea">
            <BaseInput
                id="areaEditName"
                v-model="editAreaForm.name"
                label="Name of the area"
                required
                :error="editAreaForm.errors.name"
            />
            <div>
                <div class="mb-1 font-lexend text-xs font-medium text-text">{{ $t('Color') }}</div>
                <p class="mb-2 text-xs text-text-muted">{{ $t('The color visually separates the rooms of this area in the calendar. Rooms inherit this color unless they have their own.') }}</p>
                <ColorPickerComponent @updateColor="(color) => editAreaForm.color = color" :color="editAreaForm.color"/>
            </div>
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="editAreaForm.processing"
                @click="closeEditAreaModal"
            />
            <BaseUIButton
                type="submit"
                form="editAreaForm"
                label="Save"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="editAreaForm.processing"
                :disabled="editAreaForm.processing || editAreaForm.name.trim() === ''"
            />
        </template>
    </ArtworkBaseModal>
    <!-- Raum Hinzufügen-->
    <ArtworkBaseModal
        v-if="showAddRoomModal"
        title="New room"
        description="Create a new room."
        modal-size="sm:max-w-2xl"
        @close="closeAddRoomModal"
    >
        <form id="addRoomForm" class="grid grid-cols-1 gap-4" @submit.prevent="addRoom">
            <BaseInput
                id="newRoomName"
                v-model="newRoomForm.name"
                label="Room name"
                required
                autofocus
                :error="newRoomForm.errors.name"
            />
            <BaseTextarea
                id="newRoomDescription"
                v-model="newRoomForm.description"
                label="Short description"
                :rows="4"
            />
            <div class="space-y-6 rounded-lg border border-border-subtle bg-surface-sunken p-4">
                <RoomPropertyCheckboxGroup
                    :label="$t('Room categories')"
                    :items="room_categories"
                    v-model="newRoomForm.room_categories"
                    :empty-text="$t('No room categories created yet')"
                />
                <RoomPropertyCheckboxGroup
                    :label="$t('Room properties')"
                    :items="room_attributes"
                    v-model="newRoomForm.room_attributes"
                    :empty-text="$t('No room properties created yet')"
                />
                <RoomPropertyCheckboxGroup
                    :label="$t('Adjoining rooms')"
                    :items="adjoiningRoomItems"
                    v-model="newRoomForm.adjoining_rooms"
                    :empty-text="$t('No rooms created yet')"
                />
            </div>
            <SettingsGuideBanner
                variant="static"
                title="What do these switches do?"
                :paragraphs="[
                    'Adjoining rooms record which rooms are next to each other. This is information about the room only: the conflict check still looks at the room itself, and events in an adjoining room neither block it nor trigger a warning.',
                    'Temporary rooms appear in the calendar only during the set period.',
                    'If \'Can be booked by anyone\' is off, other users can only send booking requests for this room.',
                    'If \'Relevant for disposition\' is off, the room does not appear in any calendar, shift plan or export.',
                ]"
            />
            <BaseCheckbox
                id="newRoomTemporary"
                v-model="newRoomForm.temporary"
                :label="$t('Temporary room')"
                :description="$page.props.show_hints ? $t('Set up a temporary room - e.g. if part of a room is partitioned off. This is only displayed in the calendar during this period.') : undefined"
            />
            <div v-if="newRoomForm.temporary" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <BaseInput
                    id="newRoomStartDate"
                    type="date"
                    v-model="newRoomForm.start_date"
                    label="Start date"
                    :error="newRoomForm.errors.start_date"
                />
                <BaseInput
                    id="newRoomEndDate"
                    type="date"
                    v-model="newRoomForm.end_date"
                    label="End date"
                    :error="newRoomForm.errors.end_date"
                />
            </div>
            <BaseCheckbox
                id="newRoomEveryoneCanBook"
                v-model="newRoomForm.everyone_can_book"
                :description="$page.props.show_hints ? $t('Decides whether everyone who may create events books this room directly or only the room admins. If it is off, the others can only request the room.') : undefined"
            >
                <template #label>
                    {{ $t('Can be booked by anyone') }}<span v-if="$page.props.event_direct_booking_only" class="ml-1 font-normal text-text-muted">({{ $t('no effect') }})</span>
                </template>
            </BaseCheckbox>
            <BaseCheckbox
                id="newRoomRelevantForDisposition"
                v-model="newRoomForm.relevant_for_disposition"
                :label="$t('Relevant for disposition')"
                :description="$t('Activate this field if the room is to be included in the calendars.')"
            />
            <div>
                <BaseInput
                    type="number"
                    id="newRoomCapacity"
                    v-model.number="newRoomForm.capacity"
                    label="Capacity"
                    :min="0"
                    :step="1"
                    :error="newRoomForm.errors.capacity"
                />
                <p class="mt-1 text-xs text-text-muted">{{ $t('Used by the BI module as the seat capacity per performance (occupancy rate); projects can override it.') }}</p>
            </div>
            <RoomAddressFields :form="newRoomForm" id-prefix="newRoom" />
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="newRoomForm.processing"
                @click="closeAddRoomModal"
            />
            <BaseUIButton
                type="submit"
                form="addRoomForm"
                label="Create"
                variant="primary"
                :processing="newRoomForm.processing"
                :disabled="newRoomForm.processing || newRoomForm.name.trim() === ''"
            />
        </template>
    </ArtworkBaseModal>

    <!-- Raum Bearbeiten-->
    <ArtworkBaseModal
        v-if="showEditRoomModal"
        title="Edit room"
        modal-size="sm:max-w-2xl"
        @close="closeEditRoomModal"
    >
        <form id="editRoomForm" class="grid grid-cols-1 gap-4" @submit.prevent="editRoom">
            <BaseInput
                id="editRoomName"
                v-model="editRoomForm.name"
                label="Room name"
                required
                :error="editRoomForm.errors.name"
            />
            <div v-if="areas.length > 1">
                <ArtworkBaseListbox
                    v-model="editRoomArea"
                    :items="areaOptions"
                    by="id"
                    option-label="name"
                    label="Room area"
                    show-color-indicator
                />
                <p v-if="editRoomForm.errors.area_id" class="mt-1 text-xs text-danger" role="alert">{{ editRoomForm.errors.area_id }}</p>
                <p v-else-if="editRoomArea?.id !== editRoomOriginalAreaId" class="mt-1 text-xs text-text-muted">
                    {{ $t('The room moves to this area when you save. Events, access rights and its place in the calendar order stay as they are.') }}
                </p>
            </div>
            <BaseTextarea
                id="editRoomDescription"
                v-model="editRoomForm.description"
                label="Short description"
                :rows="4"
            />
            <div class="space-y-3 rounded-lg border border-border-subtle bg-surface-sunken p-4">
                <div class="font-lexend text-xs font-medium text-text">{{ $t('Color') }}</div>
                <BaseCheckbox
                    id="editRoomInheritColor"
                    v-model="editRoomInheritColor"
                    :label="$t('Inherit color from area')"
                />
                <div v-if="!editRoomInheritColor">
                    <ColorPickerComponent @updateColor="(color) => editRoomForm.color = color" :color="editRoomForm.color ?? editRoomAreaColor"/>
                </div>
            </div>
            <div class="space-y-6 rounded-lg border border-border-subtle bg-surface-sunken p-4">
                <RoomPropertyCheckboxGroup
                    :label="$t('Room categories')"
                    :items="room_categories"
                    v-model="editRoomForm.room_categories"
                    :empty-text="$t('No room categories created yet')"
                />
                <RoomPropertyCheckboxGroup
                    :label="$t('Room properties')"
                    :items="room_attributes"
                    v-model="editRoomForm.room_attributes"
                    :empty-text="$t('No room properties created yet')"
                />
                <RoomPropertyCheckboxGroup
                    :label="$t('Adjoining rooms')"
                    :items="editAdjoiningRoomItems"
                    v-model="editRoomForm.adjoining_rooms"
                    :empty-text="$t('No rooms created yet')"
                />
            </div>
            <SettingsGuideBanner
                variant="static"
                title="What do these switches do?"
                :paragraphs="[
                    'Adjoining rooms record which rooms are next to each other. This is information about the room only: the conflict check still looks at the room itself, and events in an adjoining room neither block it nor trigger a warning.',
                    'Temporary rooms appear in the calendar only during the set period.',
                    'If \'Can be booked by anyone\' is off, other users can only send booking requests for this room.',
                    'If \'Relevant for disposition\' is off, the room does not appear in any calendar, shift plan or export.',
                ]"
            />
            <BaseCheckbox
                id="editRoomTemporary"
                v-model="editRoomForm.temporary"
                :label="$t('Temporary room')"
                :description="$page.props.show_hints ? $t('Set up a temporary room - e.g. if part of a room is partitioned off. This is only displayed in the calendar during this period.') : undefined"
            />
            <div v-if="editRoomForm.temporary" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <BaseInput
                    id="editRoomStartDate"
                    type="date"
                    v-model="editRoomForm.start_date_dt_local"
                    label="Start date"
                    :error="editRoomForm.errors.start_date"
                />
                <BaseInput
                    id="editRoomEndDate"
                    type="date"
                    v-model="editRoomForm.end_date_dt_local"
                    label="End date"
                    :error="editRoomForm.errors.end_date"
                />
            </div>
            <BaseCheckbox
                id="editRoomEveryoneCanBook"
                v-model="editRoomForm.everyone_can_book"
                :description="$page.props.show_hints ? $t('Decides whether everyone who may create events books this room directly or only the room admins. If it is off, the others can only request the room.') : undefined"
            >
                <template #label>
                    {{ $t('Can be booked by anyone') }}<span v-if="$page.props.event_direct_booking_only" class="ml-1 font-normal text-text-muted">({{ $t('no effect') }})</span>
                </template>
            </BaseCheckbox>
            <BaseCheckbox
                id="editRoomRelevantForDisposition"
                v-model="editRoomForm.relevant_for_disposition"
                :label="$t('Relevant for disposition')"
                :description="$t('Activate this field if the room is to be included in the calendars.')"
            />
            <div>
                <BaseInput
                    type="number"
                    id="editRoomCapacity"
                    v-model.number="editRoomForm.capacity"
                    label="Capacity"
                    :min="0"
                    :step="1"
                    :error="editRoomForm.errors.capacity"
                />
                <p class="mt-1 text-xs text-text-muted">{{ $t('Used by the BI module as the seat capacity per performance (occupancy rate); projects can override it.') }}</p>
            </div>
            <RoomAddressFields :form="editRoomForm" id-prefix="editRoom" />
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="editRoomForm.processing"
                @click="closeEditRoomModal"
            />
            <BaseUIButton
                type="submit"
                form="editRoomForm"
                label="Save"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="editRoomForm.processing"
                :disabled="editRoomForm.processing || editRoomForm.name.trim() === ''"
            />
        </template>
    </ArtworkBaseModal>
    <!-- Success Modal -->
    <SuccessModal
        :show="showSuccessModal"
        :title="successHeading"
        :description="successDescription"
        @closed="closeSuccessModal"
    />
    <!-- Delete Area Modal -->
    <ConfirmationComponent v-if="showSoftDeleteAreaModal"
                           :confirm="$t('In the recycle bin')"
                           :titel="$t('Area in the trash')"
                           :description="areaDeleteDescriptionText"
                           @closed="afterSoftDeleteAreaConfirm"/>
    <!-- Delete All Rooms from Area Modal -->
    <ConfirmationComponent v-if="showDeleteAllRoomsModal"
                           :confirm="$t('In the recycle bin')"
                           :titel="$t('Remove all rooms')"
                           :description="$t('Are you sure you want to put all the rooms in this area in the recycle bin?')"
                           @closed="afterSoftDeleteAllRoomsConfirm"/>
    <!-- Delete Room Modal -->
    <ConfirmationComponent v-if="showSoftDeleteRoomModal"
                           :confirm="$t('Delete room')"
                           :titel="$t('Room in the recycle bin')"
                           :description="roomDeleteDescriptionText"
                           @closed="afterSoftDeleteRoomConfirm"/>
    <!-- Delete Room Category Modal -->
    <ConfirmationComponent v-if="roomCategoryDeleteModalVisible"
                           :confirm="$t('Delete room category')"
                           :titel="$t('Delete room category')"
                           :description="$t('Are you sure you want to delete the room category? This irrevocably deletes all room assignments to this room category.') + ' ' + $t('Used by {count} rooms', {count: categoryUsage[roomCategoryToDelete?.id] ?? 0}) + '.'"
                           @closed="afterDeleteRoomCategoryConfirm"/>
    <!-- Delete Room Attribute Modal -->
    <ConfirmationComponent v-if="roomAttributeDeleteModalVisible"
                           :confirm="$t('Delete room property')"
                           :titel="$t('Delete room property')"
                           :description="$t('Are you sure you want to delete the room property? This irrevocably deletes all room assignments for this room property.') + ' ' + $t('Used by {count} rooms', {count: attributeUsage[roomAttributeToDelete?.id] ?? 0}) + '.'"
                           @closed="afterDeleteRoomAttributeConfirm"/>
    <!-- Kategorie oder Eigenschaft umbenennen -->
    <ArtworkBaseModal
        v-if="renameTarget"
        :title="renameTarget.kind === 'category' ? $t('Rename room category') : $t('Rename room property')"
        :description="$t('The assignments to rooms are kept.')"
        modal-size="sm:max-w-lg"
        @close="closeRenameModal"
    >
        <form id="renamePropertyForm" @submit.prevent="submitRename">
            <BaseInput
                id="renamePropertyName"
                v-model="renameForm.name"
                label="Name"
                required
                autofocus
                :error="renameForm.errors.name"
                @update:model-value="renameForm.clearErrors()"
            />
        </form>
        <template #footer>
            <BaseUIButton label="Cancel" is-cancel-button hide-icon :disabled="renameForm.processing" @click="closeRenameModal"/>
            <BaseUIButton
                type="submit"
                form="renamePropertyForm"
                label="Save"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="renameForm.processing"
                :disabled="renameForm.processing || renameForm.name.trim() === ''"
            />
        </template>
    </ArtworkBaseModal>
    <!-- Mehrfachauswahl -->
    <RoomBulkBar
        :count="selectedRoomIds.length"
        :processing="bulkDeleting"
        @edit="bulkEditMode = $event"
        @export="exportRooms(selectedRoomIds)"
        @delete="showBulkDeleteModal = true"
        @clear="selectedRoomIds = []"
    />
    <RoomBulkEditModal
        v-if="bulkEditMode"
        :mode="bulkEditMode"
        :room-ids="selectedRoomIds"
        :categories="room_categories"
        :attributes="room_attributes"
        :areas="areaOptions"
        @close="bulkEditMode = null"
        @done="onBulkEditDone"
    />
    <ConfirmationComponent v-if="showBulkDeleteModal"
                           :confirm="$t('In the recycle bin')"
                           :titel="$t('Rooms in the recycle bin')"
                           :description="$t('Are you sure you want to put the {count} selected rooms in the recycle bin?', {count: selectedRoomIds.length})"
                           @closed="afterBulkDeleteConfirm"/>
</template>

<script>

import RoomSettingsHeader from '@/Pages/Areas/Components/RoomSettingsHeader.vue'
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import {defineComponent} from 'vue'
import {Link, router, useForm} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import RoomAddressFields from "@/Pages/Rooms/Components/RoomAddressFields.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseCheckbox from "@/Artwork/Inputs/BaseCheckbox.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import {IconClock, IconCopy, IconEdit, IconLayoutGrid, IconRecycle, IconTrash} from "@tabler/icons-vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import BaseFilter from "@/Layouts/Components/BaseFilter.vue";
import RoomPropertyCheckboxGroup from "@/Pages/Areas/Components/RoomPropertyCheckboxGroup.vue";
import ColorPickerComponent from "@/Components/Globale/ColorPickerComponent.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import RoomBulkBar from "@/Pages/Areas/Components/RoomBulkBar.vue";
import RoomBulkEditModal from "@/Pages/Areas/Components/RoomBulkEditModal.vue";
import SelectionCheckbox from "@/Pages/Areas/Components/SelectionCheckbox.vue";
import axios from "axios";
import {
    allAreasOpened,
    filterAreas,
    hasActiveRoomCriteria,
    nameAlreadyExists,
    pruneSelection,
    roomIdsOf,
    roomRowFacts,
    selectionState,
    toggleRoomIds,
    usageCounts,
} from "@/Pages/Areas/Composables/areaListTools.js";

export default defineComponent({
    mixins: [Permissions],
    components: {
        ArtworkBaseListbox,
        RoomBulkBar,
        RoomBulkEditModal,
        SelectionCheckbox,
        RoomAddressFields,
        SettingsGuideBanner,
        PropertyIcon,
        ColorPickerComponent,
        BaseFilter,
        RoomPropertyCheckboxGroup,
        ArtworkBaseModal,
        BaseUIButton,
        BaseMenuItem,
        BaseTextarea,
        BaseCheckbox,
        BaseInput,
        BaseMenu,
        SuccessModal,
        ConfirmationComponent,
        UserPopoverTooltip,
        SvgCollection,
        RoomSettingsHeader,
        Link,
    },
    name: 'Area Management',
    props: ['areas', 'opened_areas', 'room_categories', 'room_attributes'],
    data() {
        return {
            roomCategoryInput: '',
            roomAttributeInput: '',
            showMenu: null,
            showAddAreaModal: false,
            showAddRoomModal: false,
            showEditAreaModal: false,
            showSoftDeleteAreaModal: false,
            showDeleteAllRoomsModal: false,
            areaToDeleteRoomsFrom: null,
            areaToSoftDelete: null,
            roomToSoftDelete: null,
            showSuccessModal: false,
            showSoftDeleteRoomModal: false,
            successHeading: "",
            successDescription: "",
            showTemporaryRooms: [],
            showEditRoomModal: false,
            roomFilterCategoryIds: [],
            roomFilterAttributeIds: [],
            roomSearch: '',
            roomCategoryError: '',
            roomAttributeError: '',
            newAreaForm: useForm({
                name: ''
            }),
            newRoomForm: useForm({
                name: '',
                description: '',
                temporary: false,
                start_date: null,
                end_date: null,
                area_id: null,
                user_id: this.$page.props.auth.user.id,
                relevant_for_disposition: false,
                everyone_can_book: false,
                capacity: null,
                street: '',
                zip_code: '',
                city: '',
                room_categories: [],
                room_attributes: [],
                adjoining_rooms: []
            }),
            editRoomInheritColor: true,
            editRoomAreaColor: '#000000',
            editRoomForm: useForm({
                id: null,
                name: '',
                color: null,
                description: '',
                temporary: false,
                start_date: null,
                start_date_dt_local: null,
                end_date: null,
                end_date_dt_local: null,
                area_id: null,
                user_id: null,
                everyone_can_book: false,
                relevant_for_disposition: false,
                capacity: null,
                street: '',
                zip_code: '',
                city: '',
                room_categories: [],
                room_attributes: [],
                adjoining_rooms: []
            }),
            editAreaForm: useForm({
                id: null,
                name: '',
                color: '#000000',
                rooms: [],
            }),
            roomCategoryDeleteModalVisible: false,
            roomCategoryToDelete: null,
            roomAttributeDeleteModalVisible: false,
            roomAttributeToDelete: null,
            editRoomArea: null,
            editRoomOriginalAreaId: null,
            renameTarget: null,
            renameForm: useForm({name: ''}),
            selectedRoomIds: [],
            bulkEditMode: null,
            showBulkDeleteModal: false,
            bulkDeleting: false,
        }
    },
    watch: {
        // Räume, die es nicht mehr gibt, fallen aus der Auswahl
        areas() {
            this.selectedRoomIds = pruneSelection(this.selectedRoomIds, this.areas);
        },
    },
    computed: {
        roomDeleteDescriptionText() {
            return this.$t('Are you sure you want to put the room {0} in the trash?', [this.roomToSoftDelete?.name ?? '']);
        },
        areaDeleteDescriptionText() {
            return this.$t('Are you sure you want to put the area {0} with all rooms in the recycle bin?', [this.areaToSoftDelete?.name ?? '']);
        },
        computedAreasAndRooms() {
            if (this.showEditRoomModal) {
                let areas = JSON.parse(JSON.stringify(this.areas));
                areas.forEach((area) => {
                    area.rooms = area.rooms.filter((room) => this.editRoomForm.id !== room.id);
                });

                return areas;
            }

            return this.areas;
        },
        adjoiningRoomItems() {
            return this.computedAreasAndRooms.flatMap((area) =>
                area.rooms.map((room) => ({id: room.id, name: room.name, hint: area.name}))
            );
        },
        editAdjoiningRoomItems() {
            // ein Raum kann nicht sein eigener Nebenraum sein
            return this.adjoiningRoomItems.filter((room) => room.id !== this.editRoomForm.id);
        },
        roomCriteria() {
            return {
                search: this.roomSearch,
                categoryIds: this.roomFilterCategoryIds,
                attributeIds: this.roomFilterAttributeIds,
            };
        },
        hasActivePropertyFilters() {
            return this.roomFilterCategoryIds.length > 0 || this.roomFilterAttributeIds.length > 0;
        },
        // Suche oder Filter aktiv: Areale ohne Treffer fallen weg, die übrigen stehen aufgeklappt
        hasActiveRoomFilters() {
            return hasActiveRoomCriteria(this.roomCriteria);
        },
        filteredAreas() {
            return filterAreas(this.areas, this.roomCriteria);
        },
        allAreasAreOpen() {
            return allAreasOpened(this.areas, this.opened_areas);
        },
        areaOptions() {
            return this.areas.map((area) => ({id: area.id, name: area.name, color: area.color}));
        },
        categoryUsage() {
            return usageCounts(this.areas, 'room_categories');
        },
        attributeUsage() {
            return usageCounts(this.areas, 'room_attributes');
        },
        visibleRoomIds() {
            return roomIdsOf(this.filteredAreas);
        },
    },
    methods: {
        IconTrash,
        IconRecycle,
        IconCopy,
        IconEdit,
        IconClock,
        IconLayoutGrid,
        roomRowFacts,
        isAreaOpen(area) {
            return this.hasActiveRoomFilters || this.opened_areas.includes(area.id);
        },
        toggleAllAreas() {
            router.patch(
                `/users/${this.$page.props.auth.user.id}/areas`,
                {opened_areas: this.allAreasAreOpen ? [] : this.areas.map((area) => area.id)},
                {preserveScroll: true, preserveState: true}
            );
        },
        toggleRoomSelection(roomId) {
            this.selectedRoomIds = toggleRoomIds(this.selectedRoomIds, [roomId]);
        },
        // bei aktiver Suche oder aktivem Filter gilt "alle" für die angezeigten Räume des Areals
        areaSelectionState(area) {
            return selectionState(this.selectedRoomIds, roomIdsOf([area]));
        },
        toggleAreaSelection(area) {
            this.selectedRoomIds = toggleRoomIds(this.selectedRoomIds, roomIdsOf([area]));
        },
        exportRooms(roomIds = []) {
            window.location.href = route('rooms.export', roomIds.length ? {ids: roomIds.join(',')} : {});
        },
        reloadRooms(onSuccess = null) {
            router.reload({only: ['areas'], preserveScroll: true, onSuccess: () => onSuccess?.()});
        },
        onBulkEditDone() {
            this.bulkEditMode = null;
            this.reloadRooms();
        },
        afterBulkDeleteConfirm(confirmed) {
            this.showBulkDeleteModal = false;
            if (confirmed) {
                this.bulkDeleteRooms(
                    [...this.selectedRoomIds],
                    this.$t('The rooms have been successfully moved to the trash.')
                );
            }
        },
        // ein Request für alle Räume; die Erfolgsmeldung kommt erst nach der Bestätigung des Servers
        bulkDeleteRooms(roomIds, successDescription) {
            if (this.bulkDeleting || roomIds.length === 0) {
                return;
            }
            this.bulkDeleting = true;
            axios.post(route('rooms.bulk-delete'), {ids: roomIds})
                .then(() => this.reloadRooms(() => this.showSuccess(this.$t('Rooms in the recycle bin'), successDescription)))
                .catch(() => this.reloadRooms())
                .finally(() => this.bulkDeleting = false);
        },
        openRenameModal(kind, item) {
            this.renameTarget = {kind, id: item.id};
            this.renameForm.name = item.name;
            this.renameForm.clearErrors();
        },
        closeRenameModal() {
            this.renameTarget = null;
            this.renameForm.reset();
            this.renameForm.clearErrors();
        },
        submitRename() {
            const isCategory = this.renameTarget.kind === 'category';
            const name = this.renameForm.name.trim();
            const others = (isCategory ? this.room_categories : this.room_attributes)
                .filter((item) => item.id !== this.renameTarget.id);
            if (nameAlreadyExists(others, name)) {
                this.renameForm.setError('name', this.$t('This name already exists.'));
                return;
            }
            this.renameForm.name = name;
            this.renameForm.patch(
                isCategory
                    ? route('room_categories.update', {roomCategory: this.renameTarget.id})
                    : route('room_attribute.update', {roomAttribute: this.renameTarget.id}),
                {preserveScroll: true, onSuccess: () => this.closeRenameModal()}
            );
        },
        showSuccess(heading, description) {
            this.successHeading = heading;
            this.successDescription = description;
            this.showSuccessModal = true;
            setTimeout(() => this.closeSuccessModal(), 2000);
        },
        afterSoftDeleteAllRoomsConfirm(confirmed) {
            if (confirmed) {
                this.softDeleteAllRooms()
            } else {
                this.closeDeleteAllRoomsModal()
            }
        },
        afterSoftDeleteRoomConfirm(confirmed) {
            if (confirmed) {
                this.softDeleteRoom()
            } else {
                this.closeSoftDeleteRoomModal()
            }
        },
        afterSoftDeleteAreaConfirm(confirmed) {
            if (confirmed) {
                this.softDeleteArea()
            } else {
                this.closeSoftDeleteAreaModal()
            }
        },
        showRoomCategoryDeleteModal(roomCategory) {
            this.roomCategoryToDelete = roomCategory;
            this.roomCategoryDeleteModalVisible = true;
        },
        afterDeleteRoomCategoryConfirm(confirmed) {
            if (confirmed) {
                router.delete(route('room_categories.destroy', {roomCategory: this.roomCategoryToDelete.id}), {preserveScroll: true});
                this.roomCategoryToDelete = null;
            }
            this.roomCategoryDeleteModalVisible = false;
        },
        showRoomAttributeDeleteModal(roomAttribute) {
            this.roomAttributeToDelete = roomAttribute;
            this.roomAttributeDeleteModalVisible = true;
        },
        afterDeleteRoomAttributeConfirm(confirmed) {
            if (confirmed) {
                router.delete(route('room_attribute.destroy', {roomAttribute: this.roomAttributeToDelete.id}), {preserveScroll: true});
                this.roomAttributeToDelete = null;
            }
            this.roomAttributeDeleteModalVisible = false;
        },
        addRoomCategory() {
            const name = this.roomCategoryInput.trim();
            if (name === '') {
                return;
            }
            if (nameAlreadyExists(this.room_categories, name)) {
                this.roomCategoryError = this.$t('This name already exists.');
                return;
            }
            router.post(route('room_categories.store'), {name}, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    this.roomCategoryInput = '';
                    this.roomCategoryError = '';
                },
                onError: (errors) => this.roomCategoryError = errors.name ?? '',
            });
        },
        addRoomAttribute() {
            const name = this.roomAttributeInput.trim();
            if (name === '') {
                return;
            }
            if (nameAlreadyExists(this.room_attributes, name)) {
                this.roomAttributeError = this.$t('This name already exists.');
                return;
            }
            router.post(route('room_attribute.store'), {name}, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    this.roomAttributeInput = '';
                    this.roomAttributeError = '';
                },
                onError: (errors) => this.roomAttributeError = errors.name ?? '',
            });
        },
        changeAreaStatus(area) {
            if (!this.opened_areas.includes(area.id)) {
                const openedAreas = this.opened_areas;

                openedAreas.push(area.id)
                router.patch(`/users/${this.$page.props.auth.user.id}/areas`, {"opened_areas": openedAreas}, {
                    preserveScroll: true,
                    preserveState: true
                });
            } else {
                const filteredList = this.opened_areas.filter(function (value) {
                    return value !== area.id;
                })
                router.patch(`/users/${this.$page.props.auth.user.id}/areas`, {"opened_areas": filteredList}, {
                    preserveScroll: true,
                    preserveState: true
                });
            }
        },
        openAddAreaModal() {
            this.showAddAreaModal = true;
        },
        closeAddAreaModal() {
            this.showAddAreaModal = false;
            this.newAreaForm.name = "";
            this.newAreaForm.clearErrors();
        },
        addArea() {
            // erst nach erfolgreichem Speichern schließen, damit Validierungsfehler sichtbar bleiben
            this.newAreaForm.post(route('areas.store'), {
                preserveScroll: true,
                onSuccess: () => this.closeAddAreaModal(),
            });
        },
        resetRoomFilter() {
            this.roomFilterCategoryIds = [];
            this.roomFilterAttributeIds = [];
            this.roomSearch = '';
        },
        addRoom() {
            this.newRoomForm.post(
                route('rooms.store'),
                {
                    preserveScroll: true,
                    onSuccess: this.closeAddRoomModal
                }
            );
        },
        openAddRoomModal(area) {
            this.newRoomForm.area_id = area.id;
            this.showAddRoomModal = true;
        },
        closeAddRoomModal() {
            this.showAddRoomModal = false;
            this.newRoomForm.reset();
            this.newRoomForm.clearErrors();
        },
        openEditAreaModal(area) {
            this.editAreaForm.id = area.id;
            this.editAreaForm.name = area.name;
            this.editAreaForm.color = area.color ?? '#000000';
            this.editAreaForm.rooms = area.rooms;
            this.showEditAreaModal = true;
        },
        closeEditAreaModal() {
            this.showEditAreaModal = false;
            this.editAreaForm.id = null;
            this.editAreaForm.name = "";
            this.editAreaForm.color = '#000000';
            this.editAreaForm.rooms = [];
            this.editAreaForm.clearErrors();
        },
        editArea() {
            this.editAreaForm.patch(route('areas.update', {area: this.editAreaForm.id}), {
                preserveScroll: true,
                onSuccess: () => this.closeEditAreaModal(),
            });
        },
        duplicateArea(area) {
            router.post(`/areas/${area.id}/duplicate`, {}, {preserveScroll: true});
        },
        duplicateRoom(room) {
            router.post(`/rooms/${room.id}/duplicate`, {}, {preserveScroll: true});
        },
        openSoftDeleteAreaModal(area) {
            this.areaToSoftDelete = area;
            this.showSoftDeleteAreaModal = true;
        },
        closeSoftDeleteAreaModal() {
            this.showSoftDeleteAreaModal = false;
            this.areaToSoftDelete = null;
        },
        softDeleteArea() {
            // Erfolgsmeldung erst, wenn der Server das Löschen bestätigt hat
            router.delete(`/areas/${this.areaToSoftDelete.id}`, {
                preserveScroll: true,
                onSuccess: () => this.showSuccess(
                    this.$t('Area in the recycle bin'),
                    this.$t('The area and all associated rooms have been successfully trashed.')
                ),
            });
            this.closeSoftDeleteAreaModal()
        },
        openDeleteAllRoomsModal(area) {
            this.areaToDeleteRoomsFrom = area;
            this.showDeleteAllRoomsModal = true;
        },
        closeDeleteAllRoomsModal() {
            this.showDeleteAllRoomsModal = false;
            this.areaToDeleteRoomsFrom = null;

        },
        closeSuccessModal() {
            this.showSuccessModal = false;
            this.successHeading = "";
            this.successDescription = "";
        },
        softDeleteAllRooms() {
            const roomIds = (this.areaToDeleteRoomsFrom?.rooms ?? []).map((room) => room.id);
            this.closeDeleteAllRoomsModal();
            this.bulkDeleteRooms(roomIds, this.$t('The rooms have been successfully moved to the trash.'));
        },
        openEditRoomModal(room, area = null) {
            this.editRoomForm.id = room.id;
            this.editRoomForm.name = room.name;
            this.editRoomForm.color = room.color;
            this.editRoomInheritColor = !room.color;
            this.editRoomAreaColor = area?.color ?? '#000000';
            this.editRoomArea = this.areaOptions.find((option) => option.id === area?.id) ?? null;
            this.editRoomOriginalAreaId = area?.id ?? null;
            this.editRoomForm.description = room.description ?? '';
            this.editRoomForm.start_date = room.start_date;
            this.editRoomForm.end_date = room.end_date;
            this.editRoomForm.start_date_dt_local = room.start_date_dt_local;
            this.editRoomForm.end_date_dt_local = room.end_date_dt_local;
            this.editRoomForm.adjoining_rooms = room.adjoining_rooms.map((adjoining_room) => adjoining_room.id);
            this.editRoomForm.room_categories = room.room_categories.map((room_category) => room_category.id);
            this.editRoomForm.room_attributes = room.room_attributes.map((room_attribute) => room_attribute.id);
            this.editRoomForm.temporary = room.temporary === true;
            this.editRoomForm.everyone_can_book = room.everyone_can_book === true;
            this.editRoomForm.relevant_for_disposition = room.relevant_for_disposition === true;
            this.editRoomForm.capacity = room.capacity ?? null;
            this.editRoomForm.street = room.street ?? '';
            this.editRoomForm.zip_code = room.zip_code ?? '';
            this.editRoomForm.city = room.city ?? '';
            this.showEditRoomModal = true;
        },
        closeEditRoomModal() {
            this.showEditRoomModal = false;
            this.editRoomForm.reset();
            this.editRoomForm.clearErrors();
        },
        openSoftDeleteRoomModal(room) {
            this.roomToSoftDelete = room;
            this.showSoftDeleteRoomModal = true;
        },
        closeSoftDeleteRoomModal() {
            this.showSoftDeleteRoomModal = false;
            this.roomToSoftDelete = null;
        },
        softDeleteRoom() {
            router.delete(`/rooms/${this.roomToSoftDelete.id}`, {
                preserveScroll: true,
                onSuccess: () => this.showSuccess(
                    this.$t('Room in the recycle bin'),
                    this.$t('The room has been successfully moved to the trash.')
                ),
            });
            this.closeSoftDeleteRoomModal();
        },
        switchVisibility(areaId) {
            if (this.showTemporaryRooms.includes(areaId)) {
                this.showTemporaryRooms.splice(this.showTemporaryRooms.indexOf(areaId), 1);
            } else {
                this.showTemporaryRooms.push(areaId);
            }
        },
        editRoom() {
            // Zeitraum nur für temporäre Räume mitschicken
            this.editRoomForm.start_date = this.editRoomForm.temporary ? (this.editRoomForm.start_date_dt_local || null) : null;
            this.editRoomForm.end_date = this.editRoomForm.temporary ? (this.editRoomForm.end_date_dt_local || null) : null;
            if (this.editRoomForm.capacity === '') {
                this.editRoomForm.capacity = null;
            }

            // Areal nur mitschicken, wenn es gewechselt wurde
            this.editRoomForm.transform((data) => {
                const {area_id: _unused, ...rest} = data;
                return this.editRoomArea?.id && this.editRoomArea.id !== this.editRoomOriginalAreaId
                    ? {...rest, area_id: this.editRoomArea.id}
                    : rest;
            });

            if (this.editRoomInheritColor) {
                // null = Farbe des Areals erben
                this.editRoomForm.color = null;
            } else if (!this.editRoomForm.color) {
                // Erben abgewählt, aber keine Farbe gewählt → aktuelle Areal-Farbe übernehmen
                this.editRoomForm.color = this.editRoomAreaColor;
            }

            this.editRoomForm.patch(
                route('rooms.update', {room: this.editRoomForm.id}),
                {
                    preserveScroll: true,
                    onSuccess: this.closeEditRoomModal
                }
            );
        }
    },
})
</script>


<style scoped>
/* Sanfte Ein-/Ausklapp-Transition */
.accordion-enter-active,
.accordion-leave-active {
    transition: height 220ms ease, opacity 220ms ease, transform 220ms ease;
}

.accordion-enter-from,
.accordion-leave-to {
    height: 0;
    opacity: 0;
    transform: translateY(-4px);
}

.accordion-enter-to,
.accordion-leave-from {
    height: auto;
    opacity: 1;
    transform: translateY(0);
}

/* Fade für temporäre Räume */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 180ms ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.drag-ghost {
    opacity: 0.5 !important;
    transform: scale(0.98);
    border-radius: 1rem;
}

.chip-fade-enter-active,
.chip-fade-leave-active {
    transition: all 160ms ease;
}

.chip-fade-enter-from {
    opacity: 0;
    transform: translateY(-4px) scale(0.98);
}

.chip-fade-leave-to {
    opacity: 0;
    transform: translateY(4px) scale(0.98);
}
</style>
