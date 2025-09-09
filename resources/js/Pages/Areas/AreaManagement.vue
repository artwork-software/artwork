<template>
    <app-layout :title="$t('Rooms & areas')">
        <div class="artwork-container">
            <div class="w-full flex my-auto justify-between">
                <div class="flex flex-wrap w-full">
                    <div class="flex flex-wrap w-full">
                        <h2 class="headline1 flex w-full">{{ $t('Rooms & areas') }}</h2>
                        <div class="xsLight flex mt-4 w-full">
                            {{
                                $t('Create areas and rooms and assign side rooms to individual rooms. Also define global properties for rooms.')
                            }}
                        </div>
                        <Tabs/>
                        <h2 class="headline2 w-full">{{ $t('Room properties') }}</h2>
                        <div class="xsLight flex mt-4 w-full">
                            {{
                                $t('Define room categories and properties. These can then be filtered in the calendars.')
                            }}
                        </div>
                        <div v-if="showInvalidNameErrorText" class="text-red-600 text-sm mt-4">
                            {{
                                $t('You have entered an invalid name. No spaces are allowed at the beginning or end. It is also not permitted to enter only spaces.')
                            }}
                        </div>
                        <div class="w-full grid grid-cols-1 md:grid-cols-2 gap-6 my-10">

                            <!-- Raumkategorien -->
                            <div class="space-y-3">
                                <!-- Kopf: Titel + Counter -->
                                <div class="flex items-center justify-between">
      <span class="text-sm font-semibold text-zinc-800">
        {{ $t('Room categories') }}
      </span>
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] leading-none font-medium
               border border-artwork-navigation-color/30
               bg-gradient-to-br from-artwork-navigation-color/10 to-transparent
               text-artwork-buttons-hover shadow-[inset_0_1px_0_rgba(255,255,255,0.45)] ring-1 ring-inset ring-white/40"
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
                                            :class="[roomCategoryInput === '' ? 'bg-secondary cursor-not-allowed' : 'bg-artwork-buttons-create hover:bg-artwork-buttons-hover focus:outline-none',
                   'rounded-full ml-1 inline-flex items-center p-2 border border-transparent shadow-sm text-white transition']"
                                            @click="addRoomCategory"
                                            :disabled="!roomCategoryInput"
                                            :aria-disabled="!roomCategoryInput"
                                            :title="$t('Add category')"
                                        >
                                            <IconCheck stroke-width="1.5" class="h-4 w-4"></IconCheck>
                                        </button>
                                    </div>
                                </div>

                                <!-- Chips -->
                                <TransitionGroup name="chip-fade" tag="div" class="mt-1 flex flex-wrap gap-2">
      <span
          v-for="(category, index) in room_categories"
          :key="category.id ?? category.name ?? index"
          class="inline-flex items-center gap-1.5 h-8 rounded-full px-3 text-sm font-medium
               border border-artwork-navigation-color/25
               bg-gradient-to-br from-white to-artwork-navigation-color/5
               text-zinc-800 ring-1 ring-inset ring-white/40 shadow-sm"
      >
        <!-- kleiner Farbdot -->
        <span class="inline-block h-2 w-2 rounded-full bg-artwork-buttons-hover/80"></span>
        <span class="truncate max-w-[14rem]">{{ category.name }}</span>
        <button
            type="button"
            class="ml-1 inline-flex h-6 w-6 items-center justify-center rounded-full text-zinc-500 hover:text-error hover:bg-zinc-100/70 transition"
            @click="this.showRoomCategoryDeleteModal(category)"
            :title="$t('Remove')"
            aria-label="Remove category"
        >
          <IconX stroke-width="1.5" class="h-4 w-4"/>
        </button>
      </span>
                                </TransitionGroup>
                            </div>

                            <!-- Raumattribute -->
                            <div class="space-y-3">
                                <!-- Kopf: Titel + Counter -->
                                <div class="flex items-center justify-between">
      <span class="text-sm font-semibold text-zinc-800">
        {{ $t('Room properties') }}
      </span>
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] leading-none font-medium
               border border-indigo-300/60
               bg-gradient-to-br from-indigo-100/80 to-white
               text-indigo-800 shadow-[inset_0_1px_0_rgba(255,255,255,0.55)] ring-1 ring-inset ring-white/50"
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
                                            :class="[roomAttributeInput === '' ? 'bg-secondary cursor-not-allowed' : 'bg-artwork-buttons-create hover:bg-artwork-buttons-hover focus:outline-none',
                   'rounded-full ml-1 inline-flex items-center p-2 border border-transparent shadow-sm text-white transition']"
                                            @click="addRoomAttribute"
                                            :disabled="!roomAttributeInput"
                                            :aria-disabled="!roomAttributeInput"
                                            :title="$t('Add property')"
                                        >
                                            <IconCheck stroke-width="1.5" class="h-4 w-4"></IconCheck>
                                        </button>
                                    </div>
                                </div>

                                <!-- Chips -->
                                <TransitionGroup name="chip-fade" tag="div" class="mt-1 flex flex-wrap gap-2">
      <span
          v-for="(attribute, index) in room_attributes"
          :key="attribute.id ?? attribute.name ?? index"
          class="inline-flex items-center gap-1.5 h-8 rounded-full px-3 text-sm font-medium
               border border-artwork-navigation-color/25
               bg-gradient-to-br from-white to-artwork-navigation-color/5
               text-zinc-800 ring-1 ring-inset ring-white/40 shadow-sm"
      >
        <span class="inline-block h-2 w-2 rounded-full bg-indigo-500/80"></span>
        <span class="truncate max-w-[14rem]">{{ attribute.name }}</span>
        <button
            type="button"
            class="ml-1 inline-flex h-6 w-6 items-center justify-center rounded-full text-zinc-500 hover:text-error hover:bg-zinc-100/70 transition"
            @click="this.showRoomAttributeDeleteModal(attribute)"
            :title="$t('Remove')"
            aria-label="Remove attribute"
        >
          <IconX stroke-width="1.5" class="h-4 w-4"/>
        </button>
      </span>
                                </TransitionGroup>
                            </div>

                        </div>

                        <div class="flex w-full justify-between">
                            <h2 class=" headline2">{{ $t('Areas ') }}</h2>
                        </div>
                        <div class="flex w-full justify-between mt-6">
                            <div class="flex">
                                <div>
                                    <ArtworkBaseModalButton @click="openAddAreaModal()">{{
                                            $t('Add area')
                                        }}
                                    </ArtworkBaseModalButton>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex w-full flex-wrap mt-8">
                        <!-- Modernisierte Liste: Bereiche & Räume -->
                        <div
                            v-for="area in areas"
                            :key="area.id"
                            class="group relative my-4 w-full rounded-2xl border border-zinc-200 bg-white shadow-sm transition-shadow hover:shadow-md"
                        >
                            <div class="flex items-center justify-between gap-3 pl-5 pr-4 py-4">
                                <button
                                    class="text-blue-500 bg-blue-100 border-blue-200 hover:bg-blue-200 hover:text-blue-500 disabled:bg-gray-100 hover:border-blue-300 inline-flex items-center justify-center rounded-md font-medium transition duration-200 ease-in-out font-lexend border shadow-glass backdrop-blur-md disabled:opacity-50 p-2 text-xs"
                                    @click="changeAreaStatus(area)"
                                    :aria-expanded="this.opened_areas.includes(area.id)"
                                    :aria-controls="`area-panel-${area.id}`"
                                >
                                    <IconChevronUp
                                        v-if="this.opened_areas.includes(area.id)"
                                        class="h-5 w-5"
                                        stroke-width="1.5"
                                    />
                                    <IconChevronDown
                                        v-else
                                        class="h-5 w-5"
                                        stroke-width="1.5"
                                    />
                                    <span class="sr-only">
                                        {{ this.opened_areas.includes(area.id) ? $t('Collapse') : $t('Expand') }}
                                    </span>
                                </button>

                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="headline2 truncate">{{ area.name }}</span>

                                    <!-- Zähler-Chips (schöner) -->
                                    <div class="flex items-center gap-2">
                                        <!-- Rooms -->
                                        <span
                                            :title="$t('Rooms')"
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] leading-none font-medium
           border border-artwork-navigation-color/30
           bg-gradient-to-br from-artwork-navigation-color/10 to-transparent
           text-artwork-buttons-hover
           shadow-[inset_0_1px_0_rgba(255,255,255,0.45)]
           ring-1 ring-inset ring-white/40
           transition hover:ring-artwork-navigation-color/30"
                                            role="status"
                                            aria-live="polite"
                                        >
                                        <component is="IconLayoutGrid" class="h-3.5 w-3.5 opacity-75"
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
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] leading-none font-medium
                                               border border-amber-300/60
                                               bg-gradient-to-br from-amber-100/90 to-amber-50
                                               text-amber-800
                                               shadow-[inset_0_1px_0_rgba(255,255,255,0.6)]
                                               ring-1 ring-inset ring-white/50
                                               transition hover:ring-amber-300"
                                            role="status"
                                            aria-live="polite"
                                        >
                                        <!-- Pulsierender Status-Dot -->
                                        <span class="relative flex h-2 w-2">
                                          <span
                                              class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-500/40"></span>
                                          <span class="relative inline-flex h-2 w-2 rounded-full bg-amber-500"></span>
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
                                        <BaseMenuItem icon="IconEdit" title="Edit" white-menu-background
                                                      @click="openEditAreaModal(area)"/>
                                        <BaseMenuItem icon="IconCopy" title="Duplicate" white-menu-background
                                                      @click="duplicateArea(area)"/>
                                        <BaseMenuItem icon="IconRecycle" title="Remove all rooms" white-menu-background
                                                      @click="openDeleteAllRoomsModal(area)"/>
                                        <BaseMenuItem icon="IconTrash" title="In the recycle bin" white-menu-background
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
                                        <ArtworkBaseModalButton @click="openAddRoomModal(area)">{{
                                                $t('Add room')
                                            }}
                                        </ArtworkBaseModalButton>
                                        <div v-if="this.$page.props.show_hints"
                                             class="flex items-center text-secondary">
                                            <SvgCollection svgName="arrowLeft" class="ml-1 h-4 w-4 opacity-70"/>
                                            <span class="ml-1 text-sm">{{ $t('Create new rooms') }}</span>
                                        </div>
                                    </div>

                                    <!-- Aktive Räume -->
                                    <div class="mt-6">
                                        <div
                                            v-if="(area.rooms?.filter(r => !r.temporary).length || 0) === 0"
                                            class="rounded-xl border border-dashed border-zinc-200 bg-zinc-50/40 p-4 text-sm text-zinc-500"
                                        >
                                            {{ $t('No rooms yet') }}.
                                        </div>

                                        <div v-else v-for="element in area.rooms">
                                                <div v-show="!element.temporary" class="relative group mt-3">
                                                    <div
                                                        class="relative rounded-2xl border border-zinc-200 bg-white/90 p-4 pl-5 shadow-sm ring-1 ring-transparent transition
               hover:border-artwork-navigation-color/30 hover:shadow-md focus-within:ring-2 focus-within:ring-artwork-navigation-color/30"
                                                    >
                                                        <div class="flex items-start justify-between gap-4">
                                                            <!-- Titel & Meta -->
                                                            <div class="min-w-0">
                                                                <div class="flex items-center gap-2">
                                                                    <Link
                                                                        :href="route('rooms.show', { room: element.id })"
                                                                        class="xsDark block truncate hover:underline decoration-artwork-buttons-hover/50"
                                                                    >
                                                                        {{ element.name }}
                                                                    </Link>
                                                                </div>

                                                                <div
                                                                    class="mt-1 flex flex-wrap items-center gap-2 text-[11px] leading-tight text-zinc-500">
                                                                    <span class="inline-flex items-center gap-1">
                                                                        <IconCalendar class="h-3.5 w-3.5"
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
                                                                    class="inline-flex items-center rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800 focus-visible:visible focus:outline-none focus:ring-2 focus:ring-zinc-300"
                                                                    @click="openEditRoomModal(element)"
                                                                    aria-label="Edit"
                                                                >
                                                                    <IconEdit class="h-4.5 w-4.5" stroke-width="1.75"/>
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    class="inline-flex items-center rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800 focus-visible:visible focus:outline-none focus:ring-2 focus:ring-zinc-300"
                                                                    @click="duplicateRoom(element)"
                                                                    aria-label="Duplicate"
                                                                >
                                                                    <IconCopy class="h-4.5 w-4.5" stroke-width="1.75"/>
                                                                </button>

                                                                <!-- Mehr (Kontextmenü) -->
                                                                <BaseMenu white-menu-background has-no-offset>
                                                                    <BaseMenuItem icon="IconEdit" title="Edit"
                                                                                  white-menu-background
                                                                                  @click="openEditRoomModal(element)"/>
                                                                    <BaseMenuItem icon="IconCopy" title="Duplicate"
                                                                                  white-menu-background
                                                                                  @click="duplicateRoom(element)"/>
                                                                    <BaseMenuItem icon="IconTrash"
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
                                            class="flex w-full items-center gap-2 rounded-lg px-2 py-1 text-left xxsDarkBold text-amber-700 hover:bg-amber-50"
                                            @click="switchVisibility(area.id)"
                                        >
                                            {{ $t('Temporary rooms') }}
                                            <IconChevronUp
                                                v-if="showTemporaryRooms.includes(area.id)"
                                                class="h-4 w-4"
                                                stroke-width="1.5"
                                            />
                                            <IconChevronDown
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
                                                                class="relative rounded-2xl border border-amber-200/80 bg-amber-50/70 p-4 pl-5 shadow-sm ring-1 ring-transparent transition hover:border-amber-300 hover:shadow-md focus-within:ring-2 focus-within:ring-amber-300/50"
                                                                @mouseover="showMenu = element.id"
                                                                @mouseout="showMenu = null"
                                                            >

                                                                <div class="flex items-start justify-between gap-4">
                                                                    <!-- Titel & Meta -->
                                                                    <div class="min-w-0">
                                                                        <div class="flex items-center gap-2">

                                                                            <Link
                                                                                :href="route('rooms.show', { room: element.id })"
                                                                                class="xsDark block truncate hover:underline decoration-amber-500/50"
                                                                            >
                                                                                {{ element.name }}
                                                                            </Link>
                                                                            <!-- Temporary-Status -->
                                                                            <span
                                                                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] leading-none font-medium border border-amber-400/60 bg-amber-100/80 text-amber-800 ring-1 ring-inset ring-white/50"
                                                                                :title="$t('Temporary rooms')"
                                                                            >
                                                                            <span class="relative flex h-2 w-2">
                                                                              <span
                                                                                  class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-500/40"></span>
                                                                              <span
                                                                                  class="relative inline-flex h-2 w-2 rounded-full bg-amber-500"></span>
                                                                            </span>
                                                                            {{ $t('Temporary') }}
                                                                          </span>
                                                                        </div>

                                                                        <!-- Chips: Zeitraum + Temporary -->
                                                                        <div
                                                                            class="mt-2 flex flex-wrap items-center gap-1.5">
                                                                            <!-- Zeitraum -->
                                                                            <span
                                                                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] leading-none font-medium
                       border border-amber-300/60
                       bg-gradient-to-br from-amber-100/90 to-amber-50
                       text-amber-800 shadow-[inset_0_1px_0_rgba(255,255,255,0.6)] ring-1 ring-inset ring-white/40"
                                                                                :title="$t('Date range')"
                                                                            >
                                                                            <IconCalendar class="h-3.5 w-3.5"
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
                                                                            class="mt-2 flex flex-wrap items-center gap-2 text-[11px] leading-tight text-amber-800/90">
                                                                          <span class="inline-flex items-center gap-1">
                                                                            <component is="IconClock"
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
                                                                            class="inline-flex items-center rounded-lg p-1.5 text-amber-700 hover:bg-amber-100 hover:text-amber-900 focus-visible:visible focus:outline-none focus:ring-2 focus:ring-amber-300"
                                                                            @click="openEditRoomModal(element)"
                                                                            aria-label="Edit"
                                                                        >
                                                                            <IconEdit class="h-4.5 w-4.5"
                                                                                      stroke-width="1.75"/>
                                                                        </button>
                                                                        <button
                                                                            type="button"
                                                                            class="inline-flex items-center rounded-lg p-1.5 text-amber-700 hover:bg-amber-100 hover:text-amber-900 focus-visible:visible focus:outline-none focus:ring-2 focus:ring-amber-300"
                                                                            @click="duplicateRoom(element)"
                                                                            aria-label="Duplicate"
                                                                        >
                                                                            <IconCopy class="h-4.5 w-4.5"
                                                                                      stroke-width="1.75"/>
                                                                        </button>

                                                                        <!-- Kontextmenü -->
                                                                        <BaseMenu white-menu-background has-no-offset>
                                                                            <BaseMenuItem icon="IconEdit" title="Edit"
                                                                                          white-menu-background
                                                                                          @click="openEditRoomModal(element)"/>
                                                                            <BaseMenuItem icon="IconCopy"
                                                                                          title="Duplicate"
                                                                                          white-menu-background
                                                                                          @click="duplicateRoom(element)"/>
                                                                            <BaseMenuItem icon="IconTrash"
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
                </div>
            </div>
        </div>
    </app-layout>
    <!-- Areal Hinzufügen-->

    <BaseModal @closed="closeAddAreaModal" v-if="showAddAreaModal">
        <div class="mx-3">
            <ModalHeader
                :title="$t('New area')"
            />
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
                    <FormButton
                        :disabled="newAreaForm.processing || newAreaForm.name === ''"
                        :text="$t('Create')"
                        class="mt-8"
                        type="submit"
                    />
                </div>
            </form>
        </div>
    </BaseModal>
    <!-- Areal Bearbeiten-->
    <BaseModal @closed="closeEditAreaModal" v-if="showEditAreaModal">
        <div class="mx-3">
            <ModalHeader
                :title="$t('Edit area')"
            />
            <form @submit.prevent="editArea" class="">
                <div>
                    <BaseInput
                        id="areaEditName"
                        v-model="editAreaForm.name"
                        label="Name of the area"
                    />
                    <jet-input-error :message="editAreaForm.error" class="mt-2"/>
                </div>

                <div class="w-full items-center flex justify-center text-center">
                    <FormButton
                        :disabled="editAreaForm.processing || editAreaForm.name === ''"
                        :text="$t('Save')"
                        type="submit"
                        class="mt-8 inline-flex items-center"
                    />
                </div>
            </form>
        </div>
    </BaseModal>
    <!-- Raum Hinzufügen-->
    <BaseModal @closed="closeAddRoomModal" v-if="showAddRoomModal">
        <div class="mx-3">
            <ModalHeader
                :title="$t('New room')"
                :description="$t('Create a new room.')"
            />
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
                <Menu as="span" class="relative inline-block w-full text-left">
                    <div>
                        <MenuButton class="menu-button">
                            <span>{{ $t('Select room properties') }}</span>
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
                        <MenuItems
                            class="absolute right-0 px-4 py-2 w-full origin-top-right divide-y divide-gray-200 bg-artwork-navigation-background rounded-lg ring-1 ring-black text-white opacity-100 z-50">
                            <div class="mx-auto w-full rounded-2xl bg-artwork-navigation-background border-none">
                                <!-- Room Categories Section -->
                                <Disclosure v-slot="{ open }">
                                    <DisclosureButton
                                        class="flex w-full py-2 justify-between rounded-lg bg-artwork-navigation-background text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500">
                                        <span
                                            :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{
                                                $t('Room categories')
                                            }}</span>
                                        <ChevronDownIcon :class="open ? 'rotate-180 transform' : ''"
                                                         class="h-4 w-4 mt-0.5 text-white"
                                        />
                                    </DisclosureButton>
                                    <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                                        <div v-if="room_categories.length > 0"
                                             v-for="category in room_categories"
                                             :key="category"
                                             class="flex w-full items-center mb-2">
                                            <input type="checkbox"
                                                   v-model="newRoomForm.room_categoriesToDisplay"
                                                   :value="{id:category.id,name: category.name}"
                                                   class="input-checklist-dark"/>
                                            <p :class="[newRoomForm.room_categoriesToDisplay.includes(category)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                               class="ml-1.5 text-md subpixel-antialiased align-text-middle">
                                                {{ category.name }}
                                            </p>
                                        </div>
                                        <div v-else class="text-secondary">{{ $t('No room categories created yet') }}
                                        </div>
                                    </DisclosurePanel>
                                </Disclosure>
                                <Disclosure v-slot="{ open }">
                                    <DisclosureButton
                                        class="flex w-full py-2 justify-between rounded-lg bg-artwork-navigation-background text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500">
                                        <span
                                            :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{
                                                $t('Adjoining rooms')
                                            }}</span>
                                        <ChevronDownIcon
                                            :class="open ? 'rotate-180 transform' : ''"
                                            class="h-4 w-4 mt-0.5 text-white"
                                        />
                                    </DisclosureButton>
                                    <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                                        <div v-for="area in computedAreasAndRooms">
                                            <div v-if="area.rooms.length > 0"
                                                 v-for="room in area.rooms"
                                                 :key="room"
                                                 class="flex items-center w-full mb-2">
                                                <input type="checkbox"
                                                       v-model="newRoomForm.adjoining_roomsToDisplay"
                                                       :value="{id:room.id,name: room.name}"
                                                       class="input-checklist-dark"/>
                                                <p :class="[newRoomForm.adjoining_roomsToDisplay.includes(room) ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                   class="ml-1.5 text-md subpixel-antialiased align-text-middle">
                                                    {{ room.name }}
                                                </p>
                                            </div>
                                            <div v-else class="text-secondary">{{ $t('No rooms created yet') }}</div>
                                        </div>
                                    </DisclosurePanel>
                                </Disclosure>
                                <!-- Room Attributes Section -->
                                <Disclosure v-slot="{ open }">
                                    <DisclosureButton
                                        class="flex w-full py-2 justify-between rounded-lg bg-artwork-navigation-background text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                    >
                                        <span
                                            :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{
                                                $t('Room properties')
                                            }}</span>
                                        <ChevronDownIcon
                                            :class="open ? 'rotate-180 transform' : ''"
                                            class="h-4 w-4 mt-0.5 text-white"
                                        />
                                    </DisclosureButton>

                                    <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                                        <div v-if="room_attributes.length > 0"
                                             v-for="attribute in room_attributes"
                                             :key="attribute"
                                             class="flex w-full items-center mb-2">
                                            <input type="checkbox"
                                                   v-model="newRoomForm.room_attributesToDisplay"
                                                   :value="{id:attribute.id,name: attribute.name}"
                                                   class="input-checklist-dark"/>
                                            <p :class="[newRoomForm.room_attributesToDisplay.includes(attribute)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                               class="ml-1.5 text-md subpixel-antialiased align-text-middle">
                                                {{ attribute.name }}
                                            </p>
                                        </div>
                                        <div v-else class="text-secondary">
                                            {{ $t('No room properties created yet') }}
                                        </div>
                                    </DisclosurePanel>
                                </Disclosure>
                            </div>
                        </MenuItems>
                    </transition>
                </Menu>
                <div class="flex flex-wrap">
                        <span v-for="(category, index) in newRoomForm.room_categoriesToDisplay"
                              class="flex rounded-full items-center font-medium text-tagText
                             border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
                            {{ category.name }}
                            <button @click="newRoomForm.room_categoriesToDisplay.splice(index,1)" type="button">
                                <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
                            </button>
                        </span>
                    <span v-for="(attribute, index) in newRoomForm.room_attributesToDisplay"
                          class="flex rounded-full items-center font-medium text-tagText
                             border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
                            {{ attribute.name }}
                            <button @click="newRoomForm.room_attributesToDisplay.splice(index,1)" type="button">
                                <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
                            </button>
                        </span>
                    <span v-for="(room, index) in newRoomForm.adjoining_roomsToDisplay"
                          class="flex rounded-full items-center font-medium text-tagText
                                    border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
                            {{ $t('adjoining room from') }} {{ room.name }}
                            <button @click="newRoomForm.adjoining_roomsToDisplay.splice(index,1)" type="button">
                                <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
                            </button>
                        </span>


                </div>
                <div class="flex items-center">
                    <input v-model="newRoomForm.temporary" type="checkbox" class="input-checklist"/>
                    <p :class="[newRoomForm.temporary ? 'text-primary font-black' : 'text-secondary']"
                       class="ml-4 text-sm">{{ $t('Temporary room') }}</p>
                    <div v-if="this.$page.props.show_hints" class="flex mt-1">
                        <SvgCollection svgName="arrowLeft" class="h-6 w-6 ml-2 mr-2"/>
                        <span
                            class="ml-1 my-auto hind">{{
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
                    <p :class="[newRoomForm.everyone_can_book ? 'text-primary font-black' : 'text-secondary']"
                       class="ml-4 my-auto text-sm">{{ $t('Can be booked by anyone') }}</p>
                    <div v-if="this.$page.props.show_hints" class="flex mt-1">
                        <SvgCollection svgName="arrowLeft" class="h-6 w-6 ml-2 mr-2"/>
                        <span
                            class="ml-1 my-auto hind">{{
                                $t('Decides whether this room can be booked by everyone or only by the room admins.')
                            }}</span>
                    </div>
                </div>

                <div class="flex items-start gap-x-4">
                    <input v-model="newRoomForm.relevant_for_disposition"
                           type="checkbox"
                           class="input-checklist"/>
                    <div>
                        <p :class="[newRoomForm.relevant_for_disposition ? 'text-primary font-black' : 'text-secondary']"
                           class="my-auto text-sm">{{ $t('Relevant for disposition') }}</p>
                        <span class="text-xs"
                              :class="[newRoomForm.relevant_for_disposition ? 'text-primary font-black' : 'text-secondary']">{{
                                $t('Activate this field if the room is to be included in the calendars.')
                            }}</span>
                    </div>
                </div>
                <div class="w-full items-center text-center">
                    <FormButton
                        type="submit"
                        :disabled="newRoomForm.processing || newRoomForm.name === ''"
                        :text="$t('Create')"
                        class="inline-flex items-center mt-4"
                    />
                </div>
            </form>
        </div>
    </BaseModal>

    <!-- Raum Bearbeiten-->
    <BaseModal @closed="closeEditRoomModal" v-if="showEditRoomModal">
        <div class="mx-3">
            <ModalHeader :title="$t('Edit room')"/>
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
                <Menu as="span" class="relative inline-block w-full text-left">
                    <div>
                        <MenuButton class="menu-button">
                            <span>{{ $t('Select room properties') }}</span>
                            <ChevronDownIcon class="ml-2 -mr-1 h-5 w-5 text-primary float-right" aria-hidden="true"/>
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
                            class="absolute right-0 px-4 py-2 w-full origin-top-right divide-y divide-gray-200 rounded-lg bg-artwork-navigation-background ring-1 ring-black text-white opacity-100 z-50">
                            <div class="mx-auto w-full rounded-2xl bg-artwork-navigation-background border-none">
                                <!-- Room Categories Section -->
                                <Disclosure v-slot="{ open }">
                                    <DisclosureButton
                                        class="flex w-full py-2 justify-between rounded-lg bg-artwork-navigation-background text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500">
                                        <span
                                            :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{
                                                $t('Room categories')
                                            }}</span>
                                        <ChevronDownIcon
                                            :class="open ? 'rotate-180 transform' : ''"
                                            class="h-4 w-4 mt-0.5 text-white"
                                        />
                                    </DisclosureButton>

                                    <DisclosurePanel class="pt-2 pb-2 text-sm text-white">

                                        <div v-if="room_categories.length > 0"
                                             v-for="category in room_categories"
                                             :key="category"
                                             class="flex w-full items-center mb-2">
                                            <input type="checkbox"
                                                   v-model="editRoomForm.room_categoriesToDisplay"
                                                   :value="{id:category.id,name: category.name}"
                                                   class="input-checklist-dark"/>
                                            <p :class="[editRoomForm.room_categoriesToDisplay.includes(category)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                               class="ml-1.5 text-md subpixel-antialiased align-text-middle">
                                                {{ category.name }}
                                            </p>
                                        </div>
                                        <div v-else class="text-secondary">{{ $t('No room categories created yet') }}
                                        </div>
                                    </DisclosurePanel>
                                </Disclosure>

                                <Disclosure v-slot="{ open }">
                                    <DisclosureButton
                                        class="flex w-full py-2 justify-between rounded-lg bg-artwork-navigation-background text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                    >
                                        <span
                                            :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{
                                                $t('Adjoining rooms')
                                            }}</span>
                                        <ChevronDownIcon
                                            :class="open ? 'rotate-180 transform' : ''"
                                            class="h-4 w-4 mt-0.5 text-white"
                                        />
                                    </DisclosureButton>
                                    <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                                        <div v-for="area in computedAreasAndRooms">
                                            <div v-if="area.rooms.length > 0"
                                                 v-for="room in area.rooms"
                                                 :key="room"
                                                 class="flex items-center w-full mb-2">
                                                <input type="checkbox"
                                                       v-model="editRoomForm.adjoining_roomsToDisplay"
                                                       :value="{id:room.id,name: room.name}"
                                                       class="input-checklist-dark"/>
                                                <p :class="[editRoomForm.adjoining_roomsToDisplay.includes(room)
                                                                                            ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                   class="ml-1.5 text-md subpixel-antialiased align-text-middle">
                                                    {{ room.name }}
                                                </p>
                                            </div>
                                            <div v-else class="text-secondary">{{ $t('No rooms created yet') }}</div>
                                        </div>
                                    </DisclosurePanel>
                                </Disclosure>
                                <!-- Room Attributes Section -->
                                <Disclosure v-slot="{ open }">
                                    <DisclosureButton
                                        class="flex w-full py-2 justify-between rounded-lg bg-artwork-navigation-background text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                    >
                                        <span
                                            :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{
                                                $t('Room properties')
                                            }}</span>
                                        <ChevronDownIcon
                                            :class="open ? 'rotate-180 transform' : ''"
                                            class="h-4 w-4 mt-0.5 text-white"
                                        />
                                    </DisclosureButton>
                                    <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                                        <div v-if="room_attributes.length > 0"
                                             v-for="attribute in room_attributes"
                                             :key="attribute"
                                             class="flex w-full items-center mb-2">
                                            <input type="checkbox"
                                                   v-model="editRoomForm.room_attributesToDisplay"
                                                   :value="{id:attribute.id,name: attribute.name}"
                                                   class="input-checklist-dark"/>
                                            <p :class="[editRoomForm.room_attributesToDisplay.includes(attribute)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                               class="ml-1.5 text-md subpixel-antialiased align-text-middle">
                                                {{ attribute.name }}
                                            </p>
                                        </div>
                                        <div v-else class="text-secondary">
                                            {{ $t('No room properties created yet') }}
                                        </div>
                                    </DisclosurePanel>
                                </Disclosure>
                            </div>
                        </MenuItems>
                    </transition>
                </Menu>
                <div class="mt-2 flex flex-wrap">
                                    <span v-for="(category, index) in editRoomForm.room_categoriesToDisplay"
                                          class="flex rounded-full items-center font-medium text-tagText
                                         border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
                                        {{ category.name }}
                                        <button @click="editRoomForm.room_categoriesToDisplay.splice(index,1)"
                                                type="button">
                                            <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
                                        </button>
                                    </span>
                    <span v-for="(attribute, index) in editRoomForm.room_attributesToDisplay"
                          class="flex rounded-full items-center font-medium text-tagText
                                         border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
                                        {{ attribute.name }}
                                        <button @click="editRoomForm.room_attributesToDisplay.splice(index,1)"
                                                type="button">
                                            <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
                                        </button>
                                    </span>
                    <span v-for="(room, index) in editRoomForm.adjoining_roomsToDisplay"
                          class="flex rounded-full items-center font-medium text-tagText border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
                        {{ $t('adjoining room from') }} {{ room.name }}
                        <button @click="editRoomForm.adjoining_roomsToDisplay.splice(index,1)"
                                type="button">
                            <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
                        </button>
                    </span>
                </div>
                <div class="flex items-center">
                    <input v-model="editRoomForm.temporary"
                           type="checkbox"
                           class="input-checklist"/>
                    <p :class="[editRoomForm.temporary ? 'text-primary font-black' : 'text-secondary']"
                       class="ml-4 my-auto text-sm">{{ $t('Temporary room') }}</p>
                    <div v-if="this.$page.props.show_hints" class="flex mt-1">
                        <SvgCollection svgName="arrowLeft" class="h-6 w-6 ml-2 mr-2"/>
                        <span
                            class="ml-1 my-auto hind">{{
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
                    <p :class="[editRoomForm.everyone_can_book ? 'text-primary font-black' : 'text-secondary']"
                       class="ml-4 my-auto text-sm">{{ $t('Can be booked by anyone') }}</p>
                    <div v-if="this.$page.props.show_hints" class="flex mt-1">
                        <SvgCollection svgName="arrowLeft" class="h-6 w-6 ml-2 mr-2"/>
                        <span
                            class="ml-1 my-auto hind">{{
                                $t('Decides whether this room can be booked by everyone or only by the room admins.')
                            }}</span>
                    </div>
                </div>
                <div class="flex items-start gap-x-4">
                    <input v-model="editRoomForm.relevant_for_disposition"
                           type="checkbox"
                           class="input-checklist"/>
                    <div>
                        <p :class="[editRoomForm.relevant_for_disposition ? 'text-primary font-black' : 'text-secondary']"
                           class="my-auto text-sm">{{ $t('Relevant for disposition') }}</p>
                        <span class="text-xs"
                              :class="[editRoomForm.relevant_for_disposition ? 'text-primary font-black' : 'text-secondary']">{{
                                $t('Activate this field if the room is to be included in the calendars.')
                            }}</span>
                    </div>
                </div>

                <div class="w-full items-center text-center">
                    <FormButton
                        type="submit"
                        :disabled="editRoomForm.name.length === 0 || editRoomForm.processing"
                        :text="$t('Save')"
                        class="inline-flex items-center mt-8"
                    />
                </div>

            </form>
        </div>
    </BaseModal>
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

import AppLayout from '@/Layouts/AppLayout.vue'
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import Button from "@/Jetstream/Button.vue";
import JetButton from "@/Jetstream/Button.vue";
import {
    DocumentTextIcon,
    DotsVerticalIcon,
    DuplicateIcon,
    InformationCircleIcon,
    PencilAltIcon,
    SearchIcon,
    TrashIcon,
    XIcon
} from "@heroicons/vue/outline";
import {CheckIcon, ChevronDownIcon, ChevronUpIcon, PlusSmIcon, XCircleIcon} from "@heroicons/vue/solid";
import {Disclosure, DisclosureButton, DisclosurePanel, Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
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
import Tabs from "@/Pages/Areas/Components/Tabs.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import DateInputComponent from "@/Components/Inputs/DateInputComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";

export default defineComponent({
    mixins: [Permissions, IconLib],
    components: {
        ArtworkBaseModalButton,
        BaseMenuItem,
        BaseTextarea,
        BaseInput,
        DateInputComponent,
        TextareaComponent,
        ModalHeader,
        TextInputComponent,
        BaseModal,
        BaseMenu,
        Tabs,
        FormButton,
        AddButtonBig,
        SuccessModal,
        ConfirmationComponent,
        UserPopoverTooltip,
        UserTooltip,
        SvgCollection,
        Button,
        AppLayout,
        DotsVerticalIcon,
        PlusSmIcon,
        SearchIcon,
        CheckIcon,
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
        XIcon,
        PencilAltIcon,
        TrashIcon,
        XCircleIcon,
        Link,
        DuplicateIcon,
        DocumentTextIcon,
        Disclosure,
        DisclosureButton,
        DisclosurePanel
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
                room_categories: [],
                room_attributes: [],
                adjoining_rooms: [],
                room_categoriesToDisplay: [],
                room_attributesToDisplay: [],
                adjoining_roomsToDisplay: []
            }),
            editRoomForm: useForm({
                id: null,
                name: '',
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
                room_categories: [],
                room_attributes: [],
                adjoining_rooms: [],
                room_categoriesToDisplay: [],
                room_attributesToDisplay: [],
                adjoining_roomsToDisplay: []
            }),
            editAreaForm: useForm({
                id: null,
                name: '',
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
        }
    },
    methods: {
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
        addRoom() {
            this.newRoomForm.adjoining_roomsToDisplay.forEach((adjoining_room) => {
                this.newRoomForm.adjoining_rooms.push(adjoining_room.id);
            });
            this.newRoomForm.room_categoriesToDisplay.forEach((room_category) => {
                this.newRoomForm.room_categories.push(room_category.id);
            });
            this.newRoomForm.room_attributesToDisplay.forEach((room_attributes) => {
                this.newRoomForm.room_attributes.push(room_attributes.id);
            });
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
            this.editAreaForm.rooms = area.rooms;
            this.showEditAreaModal = true;
        },
        closeEditAreaModal() {
            this.showEditAreaModal = false;
            this.editAreaForm.id = null;
            this.editAreaForm.name = "";
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
        openEditRoomModal(room) {
            this.editRoomForm.id = room.id;
            this.editRoomForm.name = room.name;
            this.editRoomForm.description = room.description ?? '';
            this.editRoomForm.start_date = room.start_date;
            this.editRoomForm.end_date = room.end_date;
            this.editRoomForm.start_date_dt_local = room.start_date_dt_local;
            this.editRoomForm.end_date_dt_local = room.end_date_dt_local;
            room.adjoining_rooms.forEach((adjoining_room) => {
                this.editRoomForm.adjoining_roomsToDisplay.push({id: adjoining_room.id, name: adjoining_room.name})
            });
            room.room_categories.forEach((room_category) => {
                this.editRoomForm.room_categoriesToDisplay.push({id: room_category.id, name: room_category.name})
            });
            room.room_attributes.forEach((room_attribute) => {
                this.editRoomForm.room_attributesToDisplay.push({id: room_attribute.id, name: room_attribute.name})
            });

            if (room.temporary === true) {
                this.editRoomForm.temporary = true;
            }
            this.showEditRoomModal = true;
            this.editRoomForm.everyone_can_book = room.everyone_can_book
            this.editRoomForm.relevant_for_disposition = room.relevant_for_disposition
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

            this.editRoomForm.adjoining_roomsToDisplay.forEach((adjoining_room) => {
                this.editRoomForm.adjoining_rooms.push(adjoining_room.id);
            });
            this.editRoomForm.room_categoriesToDisplay.forEach((room_category) => {
                this.editRoomForm.room_categories.push(room_category.id);
            });
            this.editRoomForm.room_attributesToDisplay.forEach((room_attributes) => {
                this.editRoomForm.room_attributes.push(room_attributes.id);
            });

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
