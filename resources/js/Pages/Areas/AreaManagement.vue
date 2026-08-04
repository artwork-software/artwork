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
                        <div v-if="showInvalidNameErrorText" class="text-danger text-sm mt-4">
                            {{
                                $t('You have entered an invalid name. No spaces are allowed at the beginning or end. It is also not permitted to enter only spaces.')
                            }}
                        </div>
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
                                            v-on:keyup.enter="addRoomCategory"
                                        />
                                    </div>
                                    <div class="flex items-center h-full ml-2">
                                        <button
                                            :class="[roomCategoryInput === '' ? 'bg-text-subtle cursor-not-allowed' : 'bg-accent-600 hover:bg-accent-700 focus:outline-none', 'rounded-full ml-1 inline-flex items-center p-2 border border-transparent shadow-sm text-white transition']"
                                            @click="addRoomCategory"
                                            :disabled="!roomCategoryInput"
                                            :aria-disabled="!roomCategoryInput"
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
        <button
            type="button"
            class="ml-1 inline-flex h-6 w-6 items-center justify-center rounded-full text-text-subtle hover:text-danger hover:bg-surface-sunken transition"
            @click="this.showRoomCategoryDeleteModal(category)"
            :title="$t('Remove')"
            aria-label="Remove category"
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
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] leading-none font-medium border border-accent-200
               bg-gradient-to-br from-accent-100 to-white
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
                                            v-on:keyup.enter="addRoomAttribute"
                                        />
                                    </div>
                                    <div class="flex items-center ml-2 h-full">
                                        <button
                                            :class="[roomAttributeInput === '' ? 'bg-text-subtle cursor-not-allowed' : 'bg-accent-600 hover:bg-accent-700 focus:outline-none', 'rounded-full ml-1 inline-flex items-center p-2 border border-transparent shadow-sm text-white transition']"
                                            @click="addRoomAttribute"
                                            :disabled="!roomAttributeInput"
                                            :aria-disabled="!roomAttributeInput"
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
        <span class="inline-block h-2 w-2 rounded-full bg-accent-500"></span>
        <span class="truncate max-w-[14rem]">{{ attribute.name }}</span>
        <button
            type="button"
            class="ml-1 inline-flex h-6 w-6 items-center justify-center rounded-full text-text-subtle hover:text-danger hover:bg-surface-sunken transition"
            @click="this.showRoomAttributeDeleteModal(attribute)"
            :title="$t('Remove')"
            aria-label="Remove attribute"
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
                            <div class="flex">
                                <div>
                                    <BaseUIButton @click="openAddAreaModal()" :label="$t('Add area')" is-add-button />
                                </div>
                            </div>
                            <BaseFilter only-icon white-background :has-active-filters="hasActiveRoomFilters">
                                <div class="p-3 space-y-5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold text-text">{{ $t('Filter rooms') }}</span>
                                        <button type="button"
                                                v-if="hasActiveRoomFilters"
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
                    <div class="flex w-full flex-wrap mt-8">
                        <!-- Modernisierte Liste: Bereiche & Räume -->
                        <div v-if="hasActiveRoomFilters && filteredAreas.every(area => area.rooms.length === 0)"
                             class="w-full my-4 rounded-xl border border-dashed border-border-subtle bg-surface-sunken p-4 text-sm text-text-subtle">
                            {{ $t('No rooms found') }}
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
                                    :aria-expanded="this.opened_areas.includes(area.id)"
                                    :aria-controls="`area-panel-${area.id}`"
                                >
                                    <PropertyIcon name="IconChevronUp"
                                        v-if="this.opened_areas.includes(area.id)"
                                        class="h-5 w-5"
                                        stroke-width="1.5"
                                    />
                                    <PropertyIcon name="IconChevronDown"
                                        v-else
                                        class="h-5 w-5"
                                        stroke-width="1.5"
                                    />
                                    <span class="sr-only">
                                        {{ this.opened_areas.includes(area.id) ? $t('Collapse') : $t('Expand') }}
                                    </span>
                                </BaseUIButton>

                                <div class="flex min-w-0 items-center gap-3">
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
                                        <span class="opacity-80">{{ $t('Rooms') }}</span>
                                      </span>

                                        <!-- Temporary -->
                                        <span
                                            v-if="area.rooms?.some(r => r.temporary)"
                                            :title="$t('Temporary rooms')"
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] leading-none font-medium border border-warning-border
                                               bg-gradient-to-br from-warning-surface to-warning-surface
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
                                              class="absolute inline-flex h-full w-full animate-ping rounded-full bg-warning"></span>
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
                                        <BaseMenuItem :icon="IconRecycle" title="Remove all rooms" white-menu-background
                                                      @click="openDeleteAllRoomsModal(area)"/>
                                        <BaseMenuItem :icon="IconTrash" title="In the recycle bin" white-menu-background
                                                      @click="openSoftDeleteAreaModal(area)"/>
                                    </BaseMenu>
                                </div>
                            </div>

                            <!-- Inhalt (ein-/ausklappbar) -->
                            <Transition name="accordion">
                                <div
                                    v-if="this.opened_areas.includes(area.id)"
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
                                            class="rounded-xl border border-dashed border-border-subtle bg-surface-sunken p-4 text-sm text-text-subtle"
                                        >
                                            {{ $t('No rooms yet') }}.
                                        </div>

                                        <div v-else v-for="element in area.rooms">
                                                <div v-show="!element.temporary" class="relative group mt-3">
                                                    <div
                                                        class="relative rounded-2xl border border-border-subtle bg-white/90 p-4 pl-5 shadow-sm ring-1 ring-transparent transition hover:border-text-inverse/30 hover:shadow-md focus-within:ring-2 focus-within:ring-text-inverse/30"
                                                    >
                                                        <div class="flex items-start justify-between gap-4">
                                                            <!-- Titel & Meta -->
                                                            <div class="min-w-0">
                                                                <div class="flex items-center gap-2">
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
                                                                            $t('created on { created_at } by', {'created_at': element.created_at})
                                                                        }}
                                                                    </span>
                                                                    <UserPopoverTooltip
                                                                        :user="element.created_by"
                                                                        :id="element.created_by.id + '-room-' + element.id"
                                                                        height="5"
                                                                        width="5"
                                                                    />
                                                                </div>
                                                            </div>

                                                            <!-- Actions -->
                                                            <div class="flex items-center gap-1">
                                                                <!-- Quick Actions (edit / duplicate) -->
                                                                <button
                                                                    type="button"
                                                                    class="inline-flex items-center rounded-lg p-1.5 text-text-subtle hover:bg-surface-sunken hover:text-text focus-visible:visible focus:outline-none focus:ring-2 focus:ring-border"
                                                                    @click="openEditRoomModal(element, area)"
                                                                    aria-label="Edit"
                                                                >
                                                                    <PropertyIcon name="IconEdit" class="h-4.5 w-4.5" stroke-width="1.75"/>
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    class="inline-flex items-center rounded-lg p-1.5 text-text-subtle hover:bg-surface-sunken hover:text-text focus-visible:visible focus:outline-none focus:ring-2 focus:ring-border"
                                                                    @click="duplicateRoom(element)"
                                                                    aria-label="Duplicate"
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
                                                    class="mt-2"
                                                >
                                                        <div class="relative group mt-3">
                                                            <div
                                                                class="relative rounded-2xl border border-warning-border bg-warning-surface p-4 pl-5 shadow-sm ring-1 ring-transparent transition hover:border-warning-border hover:shadow-md focus-within:ring-2 focus-within:ring-warning-border"
                                                                @mouseover="showMenu = element.id"
                                                                @mouseout="showMenu = null"
                                                            >

                                                                <div class="flex items-start justify-between gap-4">
                                                                    <!-- Titel & Meta -->
                                                                    <div class="min-w-0">
                                                                        <div class="flex items-center gap-2">

                                                                            <Link
                                                                                :href="route('rooms.show', { room: element.id })"
                                                                                class="text-sm/5 font-semibold text-text block truncate hover:underline decoration-warning"
                                                                            >
                                                                                {{ element.name }}
                                                                            </Link>
                                                                            <!-- Temporary-Status -->
                                                                            <span
                                                                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] leading-none font-medium border border-warning-border bg-warning-surface text-warning ring-1 ring-inset ring-white/50"
                                                                                :title="$t('Temporary rooms')"
                                                                            >
                                                                            <span class="relative flex h-2 w-2">
                                                                              <span
                                                                                  class="absolute inline-flex h-full w-full animate-ping rounded-full bg-warning"></span>
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
                       bg-gradient-to-br from-warning-surface to-warning-surface
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
                                                                            class="mt-2 flex flex-wrap items-center gap-2 text-[11px] leading-tight text-warning">
                                                                          <span class="inline-flex items-center gap-1">
                                                                            <component :is="IconClock"
                                                                                       class="h-3.5 w-3.5"
                                                                                       stroke-width="1.5"/>
                                                                            {{
                                                                                  $t('created on { created_at } by', {'created_at': element.created_at})
                                                                              }}
                                                                          </span>
                                                                            <UserPopoverTooltip
                                                                                :user="element.created_by"
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
                                                                            aria-label="Edit"
                                                                        >
                                                                            <PropertyIcon name="IconEdit" class="h-4.5 w-4.5"
                                                                                      stroke-width="1.75"/>
                                                                        </button>
                                                                        <button
                                                                            type="button"
                                                                            class="inline-flex items-center rounded-lg p-1.5 text-warning hover:bg-warning-surface hover:text-warning focus-visible:visible focus:outline-none focus:ring-2 focus:ring-warning-border"
                                                                            @click="duplicateRoom(element)"
                                                                            aria-label="Duplicate"
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

    <ArtworkBaseModal @close="closeAddAreaModal" v-if="showAddAreaModal" :title="$t('New area')" description="">
        <div class="mx-3">
            <form @submit.prevent="addArea" class="">
                <div>
                    <BaseInput
                        id="roomNameEdit"
                        v-model="newAreaForm.name"
                        label="Name of the area"
                        required
                    />
                    <jet-input-error :message="newAreaForm.error" class="mt-2"/>
                </div>

                <div class="w-full items-center flex justify-center text-center mt-4">
                    <BaseUIButton
                        is-add-button
                        :disabled="newAreaForm.processing || newAreaForm.name === ''"
                        :label="$t('Create')"
                        class="mt-8"
                        type="submit"
                    />
                </div>
            </form>
        </div>
    </ArtworkBaseModal>
    <!-- Areal Bearbeiten-->
    <ArtworkBaseModal @close="closeEditAreaModal" v-if="showEditAreaModal" :title="$t('Edit area')" description="">
        <div class="mx-3">
            <form @submit.prevent="editArea" class="">
                <div>
                    <BaseInput
                        id="areaEditName"
                        v-model="editAreaForm.name"
                        label="Name of the area"
                    />
                    <jet-input-error :message="editAreaForm.error" class="mt-2"/>
                </div>

                <div class="mt-4">
                    <div class="text-sm text-text mb-1">{{ $t('Color') }}</div>
                    <div class="text-xs text-text-subtle mb-2">{{ $t('The color visually separates the rooms of this area in the calendar. Rooms inherit this color unless they have their own.') }}</div>
                    <ColorPickerComponent @updateColor="(color) => editAreaForm.color = color" :color="editAreaForm.color"/>
                </div>

                <div class="w-full items-center flex justify-center text-center">
                    <BaseUIButton
                        :disabled="editAreaForm.processing || editAreaForm.name === ''"
                        :label="$t('Save')"
                        type="submit"
                        is-add-button
                    />
                </div>
            </form>
        </div>
    </ArtworkBaseModal>
    <!-- Raum Hinzufügen-->
    <ArtworkBaseModal @close="closeAddRoomModal" v-if="showAddRoomModal"  :title="$t('New room')" :description="$t('Create a new room.')">
        <div class="mx-3">
            <form @submit.prevent="addRoom" class="grid grid-cols-1 gap-4">
                <div class="">
                    <BaseInput
                        id="roomName"
                        v-model="newRoomForm.name"
                        label="Room name*"
                        required
                    />
                    <jet-input-error :message="newRoomForm.error" class="mt-2"/>
                </div>
                <div class="">
                    <BaseTextarea
                        label="Short description"
                        v-model="newRoomForm.description"
                        :rows="4"
                        id="description"
                    />
                </div>
                <div class="space-y-6 rounded-xl border border-border-subtle bg-surface-sunken p-4">
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
                        'Adjoining rooms link rooms for the conflict check: a room counts as occupied if an adjoining room hosts a loud event or an event with audience in the same period.',
                        'Temporary rooms appear in the calendar only during the set period.',
                        'If \'Can be booked by anyone\' is off, other users can only send booking requests for this room.',
                        'If \'Relevant for disposition\' is off, the room does not appear in any calendar, shift plan or export.',
                    ]"
                />
                <div class="flex items-center">
                    <input v-model="newRoomForm.temporary" type="checkbox" class="input-checklist"/>
                    <p :class="[newRoomForm.temporary ? 'text-text font-black' : 'text-text-subtle']"
                       class="ml-4 text-sm">{{ $t('Temporary room') }}</p>
                    <div v-if="this.$page.props.show_hints" class="flex mt-1">
                        <SvgCollection svgName="arrowLeft" class="h-6 w-6 ml-2 mr-2"/>
                        <span
                            class="ml-1 my-auto ">{{
                                $t('Set up a temporary room - e.g. if part of a room is partitioned off. This is only displayed in the calendar during this period.')
                            }}</span>
                    </div>
                </div>
                <div class="" v-if="newRoomForm.temporary">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <BaseInput
                            type="date"
                            v-model="newRoomForm.start_date"
                            id="startDate"
                            label="Start date"/>
                        <BaseInput
                            type="date"
                            v-model="newRoomForm.end_date"
                            id="endDate"
                            label="End date"
                        />
                    </div>
                </div>
                <div class="flex items-center">
                    <input v-model="newRoomForm.everyone_can_book" type="checkbox" class="input-checklist"/>
                    <p :class="[newRoomForm.everyone_can_book ? 'text-text font-black' : 'text-text-subtle']"
                       class="ml-4 my-auto text-sm">{{ $t('Can be booked by anyone') }}</p>
                    <div v-if="this.$page.props.show_hints" class="flex mt-1">
                        <SvgCollection svgName="arrowLeft" class="h-6 w-6 ml-2 mr-2"/>
                        <span
                            class="ml-1 my-auto ">{{
                                $t('Decides whether this room can be booked by everyone or only by the room admins.')
                            }}</span>
                    </div>
                </div>

                <div class="flex items-start gap-x-4">
                    <input v-model="newRoomForm.relevant_for_disposition"
                           type="checkbox"
                           class="input-checklist"/>
                    <div>
                        <p :class="[newRoomForm.relevant_for_disposition ? 'text-text font-black' : 'text-text-subtle']"
                           class="my-auto text-sm">{{ $t('Relevant for disposition') }}</p>
                        <span class="text-xs"
                              :class="[newRoomForm.relevant_for_disposition ? 'text-text font-black' : 'text-text-subtle']">{{
                                $t('Activate this field if the room is to be included in the calendars.')
                            }}</span>
                    </div>
                </div>
                <BaseInput
                    type="number"
                    id="new_room_capacity"
                    v-model.number="newRoomForm.capacity"
                    :label="$t('Capacity')"
                    :min="0"
                    :step="1"
                />
                <div class="w-full items-center text-center">
                    <BaseUIButton
                        type="submit"
                        :disabled="newRoomForm.processing || newRoomForm.name === ''"
                        :label="$t('Create')"
                        is-add-button
                    />
                </div>
            </form>
        </div>
    </ArtworkBaseModal>

    <!-- Raum Bearbeiten-->
    <ArtworkBaseModal @close="closeEditRoomModal" v-if="showEditRoomModal" :title="$t('Edit room')" description="">
        <div class="mx-3">
            <form @submit.prevent="editRoom" class="grid grid-cols-1 gap-4">
                <div class="">
                    <BaseInput
                        id="roomNameEdit"
                        v-model="editRoomForm.name"
                        label="Room name*"
                    />
                    <jet-input-error :message="editRoomForm.error" class="mt-2"/>
                </div>
                <div class="">
                    <BaseTextarea
                        v-model="editRoomForm.description"
                        id="description"
                        label="Short description"
                        :rows="4"
                    />
                </div>
                <div class="rounded-xl border border-border-subtle bg-surface-sunken p-4">
                    <div class="text-sm text-text mb-1">{{ $t('Color') }}</div>
                    <div class="flex items-center mb-3">
                        <input v-model="editRoomInheritColor" id="inheritRoomColor" type="checkbox" class="input-checklist"/>
                        <label for="inheritRoomColor"
                               :class="[editRoomInheritColor ? 'text-text font-black' : 'text-text-subtle']"
                               class="ml-4 my-auto text-sm cursor-pointer">
                            {{ $t('Inherit color from area') }}
                        </label>
                    </div>
                    <div v-if="!editRoomInheritColor">
                        <ColorPickerComponent @updateColor="(color) => editRoomForm.color = color" :color="editRoomForm.color ?? editRoomAreaColor"/>
                    </div>
                </div>
                <div class="space-y-6 rounded-xl border border-border-subtle bg-surface-sunken p-4">
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
                        :items="adjoiningRoomItems"
                        v-model="editRoomForm.adjoining_rooms"
                        :empty-text="$t('No rooms created yet')"
                    />
                </div>
                <SettingsGuideBanner
                    variant="static"
                    title="What do these switches do?"
                    :paragraphs="[
                        'Adjoining rooms link rooms for the conflict check: a room counts as occupied if an adjoining room hosts a loud event or an event with audience in the same period.',
                        'Temporary rooms appear in the calendar only during the set period.',
                        'If \'Can be booked by anyone\' is off, other users can only send booking requests for this room.',
                        'If \'Relevant for disposition\' is off, the room does not appear in any calendar, shift plan or export.',
                    ]"
                />
                <div class="flex items-center">
                    <input v-model="editRoomForm.temporary"
                           type="checkbox"
                           class="input-checklist"/>
                    <p :class="[editRoomForm.temporary ? 'text-text font-black' : 'text-text-subtle']"
                       class="ml-4 my-auto text-sm">{{ $t('Temporary room') }}</p>
                    <div v-if="this.$page.props.show_hints" class="flex mt-1">
                        <SvgCollection svgName="arrowLeft" class="h-6 w-6 ml-2 mr-2"/>
                        <span
                            class="ml-1 my-auto ">{{
                                $t('Set up a temporary room - e.g. if part of a room is partitioned off. This is only displayed in the calendar during this period.')
                            }}</span>
                    </div>
                </div>
                <div v-if="editRoomForm.temporary">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <BaseInput
                            type="date"
                            v-model="editRoomForm.start_date_dt_local"
                            id="startDate"
                            label="Start date"/>
                        <BaseInput
                            type="date"
                            v-model="editRoomForm.end_date_dt_local"
                            id="endDate"
                            label="End date"
                        />
                    </div>
                </div>

                <div class="flex items-center">
                    <input v-model="editRoomForm.everyone_can_book"
                           type="checkbox"
                           class="input-checklist"/>
                    <p :class="[editRoomForm.everyone_can_book ? 'text-text font-black' : 'text-text-subtle']"
                       class="ml-4 my-auto text-sm">{{ $t('Can be booked by anyone') }}</p>
                    <div v-if="this.$page.props.show_hints" class="flex mt-1">
                        <SvgCollection svgName="arrowLeft" class="h-6 w-6 ml-2 mr-2"/>
                        <span
                            class="ml-1 my-auto ">{{
                                $t('Decides whether this room can be booked by everyone or only by the room admins.')
                            }}</span>
                    </div>
                </div>
                <div class="flex items-start gap-x-4">
                    <input v-model="editRoomForm.relevant_for_disposition"
                           type="checkbox"
                           class="input-checklist"/>
                    <div>
                        <p :class="[editRoomForm.relevant_for_disposition ? 'text-text font-black' : 'text-text-subtle']"
                           class="my-auto text-sm">{{ $t('Relevant for disposition') }}</p>
                        <span class="text-xs"
                              :class="[editRoomForm.relevant_for_disposition ? 'text-text font-black' : 'text-text-subtle']">{{
                                $t('Activate this field if the room is to be included in the calendars.')
                            }}</span>
                    </div>
                </div>
                <BaseInput
                    type="number"
                    id="edit_room_capacity"
                    v-model.number="editRoomForm.capacity"
                    :label="$t('Capacity')"
                    :min="0"
                    :step="1"
                />

                <div class="w-full items-center text-center">
                    <BaseUIButton
                        type="submit"
                        :disabled="editRoomForm.name.length === 0 || editRoomForm.processing"
                        :label="$t('Save')"
                        is-add-button
                    />
                </div>

            </form>
        </div>
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
                           :description="$t('Are you sure you want to delete the room category? This irrevocably deletes all room assignments to this room category.')"
                           @closed="afterDeleteRoomCategoryConfirm"/>
    <!-- Delete Room Attribute Modal -->
    <ConfirmationComponent v-if="roomAttributeDeleteModalVisible"
                           :confirm="$t('Delete room property')"
                           :titel="$t('Delete room property')"
                           :description="$t('Are you sure you want to delete the room property? This irrevocably deletes all room assignments for this room property.')"
                           @closed="afterDeleteRoomAttributeConfirm"/>
</template>

<script>

import RoomSettingsHeader from '@/Pages/Areas/Components/RoomSettingsHeader.vue'
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import Button from "@/Jetstream/Button.vue";
import JetButton from "@/Jetstream/Button.vue";
import {defineComponent} from 'vue'
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import JetInput from "@/Jetstream/Input.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import JetSecondaryButton from "@/Jetstream/SecondaryButton.vue";
import {Link, router, useForm} from "@inertiajs/vue3";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import Permissions from "@/Mixins/Permissions.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import AddButtonBig from "@/Layouts/Components/General/Buttons/AddButtonBig.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import DateInputComponent from "@/Components/Inputs/DateInputComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import {IconCheck, IconCircleX, IconClock, IconCopy, IconDotsVertical, IconEdit, IconFileText, IconInfoCircle, IconLayoutGrid, IconPlus, IconRecycle, IconSearch, IconTrash, IconX} from "@tabler/icons-vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import BaseFilter from "@/Layouts/Components/BaseFilter.vue";
import RoomPropertyCheckboxGroup from "@/Pages/Areas/Components/RoomPropertyCheckboxGroup.vue";
import ColorPickerComponent from "@/Components/Globale/ColorPickerComponent.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";

export default defineComponent({
    mixins: [Permissions, IconLib],
    components: {
        SettingsGuideBanner,
        PropertyIcon,
        ColorPickerComponent,
        BaseFilter,
        RoomPropertyCheckboxGroup,
        ArtworkBaseModal,
        BaseUIButton,
        BaseMenuItem,
        BaseTextarea,
        BaseInput,
        DateInputComponent,
        TextareaComponent,
        ModalHeader,
        TextInputComponent,
        BaseModal,
        BaseMenu,
        FormButton,
        AddButtonBig,
        SuccessModal,
        ConfirmationComponent,
        UserPopoverTooltip,
        UserTooltip,
        SvgCollection,
        Button,
        RoomSettingsHeader,
        IconDotsVertical,
        IconPlus,
        IconSearch,
        IconCheck,
        JetButton,
        JetDialogModal,
        JetInput,
        JetInputError,
        JetSecondaryButton,
        IconInfoCircle,
        IconX,
        IconEdit,
        IconTrash,
        IconCircleX,
        Link,
        IconCopy,
        IconFileText
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
            showInvalidNameErrorText: false,
            roomCategoryDeleteModalVisible: false,
            roomCategoryToDelete: null,
            roomAttributeDeleteModalVisible: false,
            roomAttributeToDelete: null,
        }
    },
    computed: {
        roomDeleteDescriptionText() {
            return this.$t('Are you sure you want to put the room {0} in the trash?', [this.roomToSoftDelete.name]);
        },
        areaDeleteDescriptionText() {
            return this.$t('Are you sure you want to put the area {0} with all rooms in the recycle bin?', [this.areaToSoftDelete.name]);
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
        hasActiveRoomFilters() {
            return this.roomFilterCategoryIds.length > 0 || this.roomFilterAttributeIds.length > 0;
        },
        filteredAreas() {
            if (!this.hasActiveRoomFilters) {
                return this.areas;
            }

            return this.areas.map((area) => ({
                ...area,
                rooms: area.rooms.filter((room) => this.roomMatchesFilter(room))
            }));
        }
    },
    methods: {
        IconTrash,
        IconRecycle,
        IconCopy,
        IconEdit,
        IconClock,
        IconLayoutGrid,
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
                router.delete(route('room_categories.destroy', {roomCategory: this.roomCategoryToDelete.id}));
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
                router.delete(route('room_attribute.destroy', {roomAttribute: this.roomAttributeToDelete.id}));
                this.roomAttributeToDelete = null;
            }
            this.roomAttributeDeleteModalVisible = false;
        },
        checkNameRegex(name) {
            //Leerzeichen am Anfang und am Ende des Strings sind nicht erlaubt, aber innerhalb des Strings
            const regex = /^(?!\s)(?:(?!\s+$)\s|\S+\s*\S*)*(?<!\s)$/;
            return regex.test(name);
        },
        addRoomCategory() {
            if (this.checkNameRegex(this.roomCategoryInput)) {
                this.showInvalidNameErrorText = false;
                router.post(
                    route('room_categories.store'),
                    {
                        name: this.roomCategoryInput
                    },
                    {
                        onSuccess: () => this.roomCategoryInput = ''
                    }
                );
            } else {
                this.showInvalidNameErrorText = true;
            }
        },
        deleteRoomAttribute(attribute) {
            router.delete(route('room_attribute.destroy', {roomAttribute: attribute.id}));
        },
        addRoomAttribute() {
            if (this.checkNameRegex(this.roomAttributeInput)) {
                this.showInvalidNameErrorText = false;
                router.post(
                    route('room_attribute.store'),
                    {
                        name: this.roomAttributeInput
                    },
                    {
                        onSuccess: () => this.roomAttributeInput = ''
                    }
                );
            } else {
                this.showInvalidNameErrorText = true;
            }
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
        },
        addArea() {
            this.newAreaForm.post(route('areas.store'), {});
            this.closeAddAreaModal();
        },
        roomMatchesFilter(room) {
            const matchesCategories = this.roomFilterCategoryIds.length === 0
                || room.room_categories?.some((category) => this.roomFilterCategoryIds.includes(category.id));
            const matchesAttributes = this.roomFilterAttributeIds.length === 0
                || room.room_attributes?.some((attribute) => this.roomFilterAttributeIds.includes(attribute.id));

            return matchesCategories && matchesAttributes;
        },
        resetRoomFilter() {
            this.roomFilterCategoryIds = [];
            this.roomFilterAttributeIds = [];
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
        },
        editArea() {
            this.editAreaForm.patch(route('areas.update', {area: this.editAreaForm.id}));
            this.closeEditAreaModal();
        },
        duplicateArea(area) {
            router.post(`/areas/${area.id}/duplicate`);
        },
        duplicateRoom(room) {
            router.post(`/rooms/${room.id}/duplicate`);
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
            router.delete(`/areas/${this.areaToSoftDelete.id}`);
            this.closeSoftDeleteAreaModal()
            this.successHeading = this.$t('Area in the recycle bin')
            this.successDescription = this.$t('The area and all associated rooms have been successfully trashed.')
            this.showSuccessModal = true;
            setTimeout(() => this.closeSuccessModal(), 2000)
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
            this.areaToDeleteRoomsFrom.rooms.forEach((room) => {
                router.delete(`/rooms/${room.id}`);
            })
            this.closeDeleteAllRoomsModal();
            this.successHeading = this.$t('Room in the recycle bin')
            this.successDescription = this.$t('The rooms have been successfully moved to the trash.')
            this.showSuccessModal = true;
            setTimeout(() => this.closeSuccessModal(), 2000)
        },
        openEditRoomModal(room, area = null) {
            this.editRoomForm.id = room.id;
            this.editRoomForm.name = room.name;
            this.editRoomForm.color = room.color;
            this.editRoomInheritColor = !room.color;
            this.editRoomAreaColor = area?.color ?? '#000000';
            this.editRoomForm.description = room.description ?? '';
            this.editRoomForm.start_date = room.start_date;
            this.editRoomForm.end_date = room.end_date;
            this.editRoomForm.start_date_dt_local = room.start_date_dt_local;
            this.editRoomForm.end_date_dt_local = room.end_date_dt_local;
            this.editRoomForm.adjoining_rooms = room.adjoining_rooms.map((adjoining_room) => adjoining_room.id);
            this.editRoomForm.room_categories = room.room_categories.map((room_category) => room_category.id);
            this.editRoomForm.room_attributes = room.room_attributes.map((room_attribute) => room_attribute.id);

            if (room.temporary === true) {
                this.editRoomForm.temporary = true;
            }
            this.showEditRoomModal = true;
            this.editRoomForm.everyone_can_book = room.everyone_can_book
            this.editRoomForm.relevant_for_disposition = room.relevant_for_disposition
            this.editRoomForm.capacity = room.capacity
        },
        closeEditRoomModal() {
            this.showEditRoomModal = false;
            this.editRoomForm.reset();
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
            router.delete(`/rooms/${this.roomToSoftDelete.id}`, {preserveScroll: true});
            this.closeSoftDeleteRoomModal();
            this.successHeading = this.$t('Room in the recycle bin')
            this.successDescription = this.$t('The rooms have been successfully moved to the trash.')
            this.showSuccessModal = true;
            setTimeout(() => this.closeSuccessModal(), 2000);
        },
        switchVisibility(areaId) {
            if (this.showTemporaryRooms.includes(areaId)) {
                this.showTemporaryRooms.splice(this.showTemporaryRooms.indexOf(areaId), 1);
            } else {
                this.showTemporaryRooms.push(areaId);
            }
        },
        editRoom() {
            this.editRoomForm.start_date = this.editRoomForm.start_date_dt_local;
            this.editRoomForm.end_date = this.editRoomForm.end_date_dt_local;

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
