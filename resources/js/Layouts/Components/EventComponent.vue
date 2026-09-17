<template>
    <ArtworkBaseModal
        :title="modalTitle"
        :description="modalDescription"
        modal-size="max-w-4xl"
        @close="handleCloseAttempt"
    >
        <div v-if="requestSubmitted" class="space-y-5 rounded-lg border border-success-border bg-success-surface p-5">
            <div>
                <h3 class="text-base font-semibold text-success">{{ $t('Room request submitted') }}</h3>
                <p class="mt-1 text-sm text-success">
                    {{ $t('The event was created as a room request and is not firmly booked yet. You will be notified after it has been reviewed.') }}
                </p>
            </div>
            <div class="flex justify-end">
                <FormButton @click="closeModal(true)" :text="$t('Close')" />
            </div>
        </div>

        <div v-else class="space-y-4">

            <!-- Top Meta + Tip -->
            <div class="flex flex-col gap-2">
                <!-- Created by -->
                <div
                    v-if="(isRoomAdmin || hasAdminRole()) && event?.id"
                    class="flex items-center gap-2 text-[12px] text-text-subtle"
                >
                    {{ $t('Created by') }}
                    <UserPopoverTooltip
                        :user="event?.created_by"
                        :id="event?.created_by?.id ?? 'deletedUserTooltip'"
                        height="5"
                        width="5"
                    />
                </div>
            </div>
            <!-- EDIT MODE -->
            <div v-if="canEdit">

                <!-- Serie: Tabs (Termin / Serie bzw. Vorschau) + Reichweite der Änderung -->
                <div v-if="showSeriesHeader" class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-[10px] border border-border-subtle bg-surface-sunken px-3 py-2">
                    <div class="inline-flex gap-[2px] rounded-[8px] bg-border-subtle p-[3px]" role="tablist">
                        <button
                            type="button"
                            role="tab"
                            class="inline-flex items-center justify-center h-[26px] px-3 rounded-[6px] text-[12.5px] font-semibold transition"
                            :class="activeTab === 'event' ? 'bg-surface shadow-raised text-text' : 'text-text hover:bg-white/60'"
                            :aria-selected="activeTab === 'event'"
                            @click="activeTab = 'event'"
                        >
                            {{ $t('Event') }}
                        </button>
                        <button
                            type="button"
                            role="tab"
                            class="inline-flex items-center justify-center gap-1.5 h-[26px] px-3 rounded-[6px] text-[12.5px] font-semibold transition"
                            :class="activeTab === 'series' ? 'bg-surface shadow-raised text-text' : 'text-text hover:bg-white/60'"
                            :aria-selected="activeTab === 'series'"
                            @click="activeTab = 'series'"
                        >
                            <IconRepeat class="size-3.5" />
                            {{ isSeriesEvent ? $t('Series') : $t('Preview') }}
                            <span v-if="seriesTabCount !== null" class="inline-flex min-w-[18px] items-center justify-center rounded-full bg-accent-100 px-1.5 text-[11px] text-accent-700">{{ seriesTabCount }}</span>
                        </button>
                    </div>

                    <div v-if="isSeriesEvent" class="flex flex-wrap items-center gap-2">
                        <span class="text-[12px] text-text-muted">{{ $t('Changes apply to') }}:</span>
                        <div class="inline-flex gap-[2px] rounded-[8px] bg-border-subtle p-[3px]" role="radiogroup" :aria-label="$t('Changes apply to')">
                            <button
                                v-for="opt in scopeOptions"
                                :key="opt.id"
                                type="button"
                                role="radio"
                                class="inline-flex items-center justify-center h-[26px] px-3 rounded-[6px] text-[12.5px] font-semibold transition"
                                :class="seriesScope === opt.id ? 'bg-surface shadow-raised text-text' : 'text-text hover:bg-white/60'"
                                :aria-checked="seriesScope === opt.id"
                                @click="seriesScope = opt.id"
                            >
                                {{ opt.label }}
                            </button>
                        </div>
                    </div>
                </div>
                <p v-if="isSeriesEvent" class="ui-hint mb-3 -mt-1">{{ scopeHint }}</p>

                <div v-show="activeTab === 'event'">

                <!-- Basics -->
                <section class="pb-4">
                    <h3 class="mb-3 font-lexend font-semibold text-[11px] uppercase tracking-[0.08em] text-accent-600">{{ $t('Basics') }}</h3>

                    <div class="ui-grid-2">
                        <ArtworkBaseListbox
                            v-model="selectedEventType"
                            :items="eventTypes"
                            by="id"
                            option-label="name"
                            option-key="id"
                            label="Event type"
                            :use-translations="false"
                            :show-color-indicator="true"
                            color-property="hex_code"
                        />
                        <ArtworkBaseListbox
                            v-if="statusModule"
                            v-model="selectedEventStatus"
                            :items="eventStatuses"
                            by="id"
                            option-label="name"
                            option-key="id"
                            label="Event Status"
                            :use-translations="false"
                            :show-color-indicator="true"
                            color-property="color"
                        >
                        </ArtworkBaseListbox>
                        <BaseInput
                            v-model="eventName"
                            id="eventTitle"
                            :label="selectedEventType?.individual_name ? $t('Event name') + '*' : $t('Event name')"
                            class="ui-input"
                        />
                    </div>

                    <div class="ui-grid-2 mt-0.5">
                        <p class="ui-error" v-if="errorMsg('eventType')" v-html="errorMsg('eventType')" />
                        <span v-if="statusModule" />
                        <p class="ui-error" v-if="selectedEventType?.individual_name && errorMsg('eventName')" v-html="errorMsg('eventName')" />
                    </div>
                </section>

                <!-- Date & Time -->
                <section class="border-t border-border-hairline py-4">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <h3 class="font-lexend font-semibold text-[11px] uppercase tracking-[0.08em] text-accent-600">{{ $t('Date & Time') }}</h3>
                        <SwitchIconTooltip
                            v-model="shiftPeriodOnStartDateChange"
                            :tooltip-text="$t('Move the end date along when the start date is changed')"
                            :icon="IconArrowsMoveHorizontal"
                            size="sm"
                            @change="onToggleShiftPeriodOnStartDateChange"
                        />
                    </div>

                    <label class="inline-flex items-center gap-2">
                        <input
                            type="checkbox"
                            v-model="allDayEvent"
                            class="ui-checkbox"
                            @change="checkChanges"
                        />
                        <span class="text-[13px] text-text-muted">{{ $t('Full day') }}</span>
                    </label>

                    <div class="ui-grid-2 mt-2">
                        <div class="flex gap-2 items-end">
                            <BaseInput type="date" id="startDate" v-model="startDate" :label="$t('Start')" @change="() => { shiftEndByStartDelta('date'); checkChanges() }" class="ui-input" />
                            <BaseInput
                                v-if="!allDayEvent"
                                type="time"
                                id="startTime"
                                v-model="startTime"
                                :label="$t('Start time')"
                                @change="() => { shiftEndByStartDelta('time'); endAutoFilled = !endTime; checkChanges() }"
                                class="ui-input"
                            />
                        </div>
                        <div class="flex gap-2 items-end">
                            <BaseInput type="date" id="endDate" v-model="endDate" :label="$t('End')" @change="() => { endAutoFilled = false; checkChanges() }" class="ui-input" />
                            <BaseInput
                                v-if="!allDayEvent"
                                type="time"
                                id="endTime"
                                v-model="endTime"
                                :label="$t('End time')"
                                @change="() => { endAutoFilled = false; checkChanges() }"
                                class="ui-input"
                            />
                        </div>
                    </div>

                    <!-- Einlass: optionale Uhrzeit, bezogen auf den Starttag (Instanz-Setting) -->
                    <div class="ui-grid-2 mt-2" v-if="admissionModule">
                        <div class="flex gap-2 items-end">
                            <BaseInput
                                type="time"
                                id="admissionTime"
                                v-model="admissionTime"
                                :label="$t('Admission')"
                                class="ui-input"
                            />
                        </div>
                        <div class="flex items-end pb-2 text-[12px] text-text-muted" v-if="startDate && endDate && startDate !== endDate">
                            {{ $t('Refers to the start day') }}
                        </div>
                    </div>

                    <!-- Quick duration pills -->
                    <div class="mt-4 flex flex-wrap items-center gap-2" v-if="startDate && endDate && startTime">
                        <div class="text-[12px] text-text-muted">{{ $t('Duration Shortcuts:')}}</div>
                        <button
                            v-for="m in quickDurations"
                            :key="m"
                            type="button"
                            class="inline-flex items-center rounded-full h-[26px] px-3 text-xs border border-border bg-surface hover:bg-surface-hover disabled:text-text-subtle disabled:cursor-not-allowed"
                            :disabled="!startDate || allDayEvent || !startTime"
                            @click="applyQuickDuration(m)"
                            :aria-label="$t('Set end to {0} minutes after start', [m])"
                            :title="$t('Set end to {0} minutes after start', [m])"
                        >
                            {{ m }} {{ $t('min') }}
                        </button>

                        <!-- optional: Pill mit Standarddauer aus PageProps -->
                        <button
                            v-if="defaultDurationMin > 0 && ![...quickDurations].includes(defaultDurationMin)"
                            type="button"
                            class="inline-flex items-center rounded-full h-[26px] px-3 text-xs border border-accent-200 bg-accent-50 text-accent-700 hover:bg-accent-100 disabled:text-text-subtle disabled:cursor-not-allowed"
                            :disabled="!startDate || allDayEvent || !startTime"
                            @click="applyQuickDuration(defaultDurationMin)"
                            :aria-label="$t('Set end to {0} minutes after start', [defaultDurationMin])"
                            :title="$t('Set end to {0} minutes after start', [defaultDurationMin])"
                        >
                            Standard: {{ defaultDurationMin }} {{ $t('min') }}
                        </button>
                    </div>

                    <div class="ui-grid-2">
                        <p class="ui-error" v-if="errorMsg('start')" v-html="errorMsg('start')" />
                        <p class="ui-error" v-if="errorMsg('end')" v-html="errorMsg('end')" />
                    </div>
                    <p v-if="helpTextLengthRoom" class="ui-error mt-1">{{ helpTextLengthRoom }}</p>
                </section>

                <!-- Repeat -->
                <section class="border-t border-border-hairline py-4">
                    <h3 class="mb-3 font-lexend font-semibold text-[11px] uppercase tracking-[0.08em] text-accent-600">{{ $t('Repeat') }}</h3>

                    <div class="flex flex-wrap items-center gap-2">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" v-model="series" class="ui-checkbox" :disabled="seriesLoading" @change="onSeriesToggle" />
                            <span class="text-[13px] text-text-muted">{{ $t('Repeat event') }}</span>
                        </label>
                        <span v-if="!isSeriesEvent" class="ui-hint">{{ $t('Enable if this event should repeat automatically.') }}</span>
                    </div>

                    <!-- Bestehende Serie: Zusammenfassung; Turnus/Ende nur mit Reichweite über den Termin hinaus -->
                    <p v-if="isSeriesEvent && series" class="mt-2 text-[13px] text-text">
                        <IconRepeat class="mr-1 inline size-3.5 align-[-2px] text-accent-600" />
                        {{ seriesSummaryText }}
                    </p>
                    <p v-if="isSeriesEvent && series && seriesFieldsLocked" class="ui-hint mt-1">
                        {{ $t('To change the frequency or the end of the series, choose "This and following" or "Whole series" above.') }}
                    </p>

                    <div v-show="series" class="mt-2 space-y-3" :class="seriesFieldsLocked ? 'pointer-events-none opacity-60' : ''">
                        <div class="ui-grid-2">
                            <ArtworkBaseListbox
                                v-model="selectedFrequency"
                                :items="frequencies"
                                by="id"
                                option-label="name"
                                option-key="id"
                                label="Frequency"
                                :use-translations="false"
                            />
                            <div>
                                <div class="mb-1 inline-flex gap-[2px] rounded-[8px] bg-border-subtle p-[3px]" role="radiogroup" :aria-label="$t('Series ends')">
                                    <button
                                        type="button"
                                        role="radio"
                                        class="inline-flex items-center justify-center h-[24px] px-2.5 rounded-[6px] text-[12px] font-semibold transition"
                                        :class="seriesEndMode === 'date' ? 'bg-surface shadow-raised text-text' : 'text-text hover:bg-white/60'"
                                        :aria-checked="seriesEndMode === 'date'"
                                        @click="seriesEndMode = 'date'"
                                    >
                                        {{ $t('Ends on date') }}
                                    </button>
                                    <button
                                        type="button"
                                        role="radio"
                                        class="inline-flex items-center justify-center h-[24px] px-2.5 rounded-[6px] text-[12px] font-semibold transition"
                                        :class="seriesEndMode === 'count' ? 'bg-surface shadow-raised text-text' : 'text-text hover:bg-white/60'"
                                        :aria-checked="seriesEndMode === 'count'"
                                        @click="seriesEndMode = 'count'"
                                    >
                                        {{ $t('Ends after') }}
                                    </button>
                                </div>
                                <BaseInput
                                    v-if="seriesEndMode === 'date'"
                                    id="seriesEndDate"
                                    type="date"
                                    v-model="seriesEndDate"
                                    :label="$t('End date Repeat event')"
                                    :disabled="seriesFieldsLocked"
                                    class="ui-input"
                                />
                                <BaseInput
                                    v-else
                                    id="seriesOccurrenceCount"
                                    type="number"
                                    min="1"
                                    max="500"
                                    v-model="seriesOccurrenceCount"
                                    :label="$t('Number of events')"
                                    :disabled="seriesFieldsLocked"
                                    class="ui-input"
                                />
                            </div>
                        </div>

                        <!-- Wochentage (nur wöchentlich / alle 2 Wochen) -->
                        <div v-if="showWeekdayPicker">
                            <div class="mb-1 ui-hint">{{ $t('On these weekdays') }}</div>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="wd in weekdayOptions"
                                    :key="wd.id"
                                    type="button"
                                    class="inline-flex h-[28px] min-w-[38px] items-center justify-center rounded-full border px-2.5 text-xs font-medium transition"
                                    :class="[
                                        seriesWeekdays.includes(wd.id) ? 'bg-accent-50 text-accent-700 border-accent-200' : 'bg-surface border-border text-text-muted hover:bg-surface-hover',
                                        wd.id === anchorWeekday ? 'ring-1 ring-accent-300' : '',
                                    ]"
                                    :aria-pressed="seriesWeekdays.includes(wd.id)"
                                    :title="wd.id === anchorWeekday ? $t('Weekday of the first event, always included') : ''"
                                    @click="toggleWeekday(wd.id)"
                                >
                                    {{ wd.label }}
                                </button>
                            </div>
                        </div>

                        <p class="ui-error" v-if="seriesValidationMessage">{{ seriesValidationMessage }}</p>
                    </div>
                </section>

                <!-- Room -->
                <section class="border-t border-border-hairline py-4">
                    <h3 class="mb-3 font-lexend font-semibold text-[11px] uppercase tracking-[0.08em] text-accent-600">{{ $t('Room') }}</h3>

                    <div v-if="declinedRoomId" class="flex items-center gap-2 text-[12px] text-danger mb-1">
                        <span>{{ $t('Previously declined from') }}:</span>
                        <span class="font-medium line-through">{{ declinedRoomName }}</span>
                    </div>

                    <div class="mb-1">
                        <span class="ui-hint">{{ $t('Pick a room for this event.') }}</span>
                    </div>

                    <div class="grid grid-cols-1 gap-2 md:grid-cols-[1fr_auto]">
                        <RoomSearch v-if="!selectedRoom" :label="$t('Search for Rooms')" @room-selected="onRoomSelected" />
                        <div v-if="selectedRoom"
                             class="flex items-center gap-1.5 rounded-md border border-border-subtle bg-surface-sunken px-2.5 py-4 text-sm/5 font-semibold text-text"
                        >
                            <span class="truncate">{{ selectedRoom.name }}</span>
                            <button class="ml-0.5 text-text-subtle transition hover:text-danger" @click="selectedRoom = null" type="button">
                                <IconCircleX class="size-4" />
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="selectedRoom && roomCollisionArray?.[selectedRoom.id] > 0"
                        class="mt-2 flex items-center gap-2 rounded-[8px] border border-warning-border bg-warning-surface px-3 py-2 text-[13px] text-warning"
                    >
                        <IconAlertTriangle class="size-4 shrink-0" />
                        <span>{{ $t('{0} potential conflicts detected', [roomCollisionArray[selectedRoom.id]]) }}</span>
                    </div>

                    <p class="ui-error mt-1" v-if="errorMsg('roomId')" v-html="errorMsg('roomId')" />
                </section>

                <!-- Project -->
                <section class="border-t border-border-hairline py-4">
                    <h3 class="mb-3 font-lexend font-semibold text-[11px] uppercase tracking-[0.08em] text-accent-600">{{ $t('Project') }}</h3>

                    <!-- Fehlerhinweis immer oben beim Abschnittstitel anzeigen -->
                    <div class="mt-1">
                        <p class="ui-error" v-if="errorMsg('projectId')" v-html="errorMsg('projectId')" />
                    </div>

                    <!-- Aktivierung -->
                    <label for="showProjectInfo" class="flex items-center gap-2 mt-1 cursor-pointer">
                        <input id="showProjectInfo" type="checkbox" v-model="showProjectInfo" class="ui-checkbox" />
                        <span class="text-[13px] text-text-muted">{{ $t('Enable project assignment') }}</span>
                    </label>

                    <div v-if="showProjectInfo" class="mt-2 space-y-2">

                        <!-- Chip-Ansicht: nur anzeigen, wenn ein bestehendes Projekt ausgewählt ist -->
                        <template v-if="!creatingProject && selectedProject?.id">
                            <div class="ui-project-chip">
                                <div class="min-w-0">
                                    <a
                                        v-if="canAccessProject()"
                                        :href="route('projects.tab', { project: selectedProject.id, projectTab: first_project_calendar_tab_id })"
                                        class="ui-project-link"
                                        :title="selectedProject?.name"
                                    >
                                        {{ selectedProject?.name }}
                                    </a>
                                    <span v-else class="truncate text-sm/5 font-semibold text-text text-text">{{ selectedProject?.name }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <button type="button" class="ui-icon-btn" @click="removeProject" :aria-label="$t('Remove project')">
                                        <IconCircleX class="size-4" />
                                    </button>
                                </div>
                            </div>
                        </template>

                        <!-- Segment: Bestehend / Neu -->
                        <div class="inline-flex gap-[2px] rounded-[8px] bg-border-subtle p-[3px]" role="tablist" aria-label="Project source">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center h-[26px] px-3 rounded-[6px] text-[12.5px] font-semibold transition"
                                :class="!creatingProject ? 'bg-surface shadow-raised text-text' : 'text-text hover:bg-white/60'"
                                role="tab"
                                :aria-selected="!creatingProject"
                                @click="switchToExisting()"
                            >
                                {{ $t('Existing project') }}
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center justify-center h-[26px] px-3 rounded-[6px] text-[12.5px] font-semibold transition"
                                :class="creatingProject ? 'bg-surface shadow-raised text-text' : 'text-text hover:bg-white/60'"
                                role="tab"
                                :aria-selected="creatingProject"
                                @click="switchToNew()"
                            >
                                {{ $t('New project') }}
                            </button>
                        </div>

                        <!-- Inhalt: Bestehend = Suche / Neu = freies Input -->
                        <div>
                            <!-- Bestehendes Projekt: Suche. Hinweis: Chip oben blendet die Suche aus, bis "Ändern" -->
                            <ProjectSearch
                                v-if="!creatingProject && !selectedProject?.id"
                                ref="projectSearchRef"
                                :label="$t('Search for projects')"
                                @project-selected="chooseProjectFromPicker"
                            />

                            <!-- Neues Projekt: freies Feld, keine Bestätigung, NICHT auto-umschalten -->
                            <BaseInput
                                v-if="creatingProject"
                                id="projectName"
                                ref="projectNameRef"
                                :label="$t('New project name')"
                                v-model="projectName"
                                class="ui-input"
                                :placeholder="$t('e.g. Kitchen Miller – Renovation')"
                            />

                            <LastedProjects
                                v-if="!creatingProject && !selectedProject?.id"
                                :limit="10"
                                @select="chooseProjectFromPicker"
                            />
                        </div>

                        <!-- Hinweise/Fehler -->
                        <p class="ui-hint" v-if="creatingProject">
                            {{ $t('The project will be created when saving the event.') }}
                        </p>
                        <p class="ui-error" v-if="errorMsg('projectName')" v-html="errorMsg('projectName')" />
                    </div>
                </section>


                <!-- Notes / Booking -->
                <section class="border-t border-border-hairline py-4">
                    <header class="mb-3 flex items-center gap-2">
                        <h3 class="font-lexend font-semibold text-[11px] uppercase tracking-[0.08em] text-accent-600">{{ $t('Description') }}</h3>
                        <ToolTipComponent
                            :tooltip-text="$t('Other users can see this description in the project\'s event list and in the calendar.')"
                            direction="right"
                            icon="IconInfoCircle"
                            icon-size="h-4 w-4"
                        />
                    </header>

                    <div class="space-y-3">
                        <BaseTextarea
                            :label="$t('What do I need to bear in mind for the event?')"
                            id="description"
                            v-model="description"
                            rows="4"
                            @update:model-value="descriptionTouched = true"
                        />
                        <p v-if="descriptionLoadFailed && !descriptionTouched" class="text-xs text-red-600">
                            {{ $t('The description could not be loaded. Please reopen the event before saving.') }}
                        </p>

                        <div v-if="event?.occupancy_option" class="space-y-2">
                            <BaseTextarea
                                :label="$t('Comment on the booking (inquirer will be notified)')"
                                id="adminComment"
                                v-model="adminComment"
                                rows="3"
                            />

                            <div class="flex flex-wrap items-center gap-x-6 gap-y-1.5">
                                <label class="inline-flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        v-model="accept"
                                        class="ui-checkbox ui-checkbox-emerald"
                                        @change="toggleAccept('accept')"
                                    />
                                    <span class="text-[13px]" :class="accept ? 'text-text' : 'text-text-muted'">{{ $t('Commitments') }}</span>
                                </label>

                                <label class="inline-flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        v-model="optionAccept"
                                        class="ui-checkbox ui-checkbox-amber"
                                        @change="toggleAccept('option')"
                                    />
                                    <span class="text-[13px]" :class="optionAccept ? 'text-text' : 'text-text-muted'">{{ $t('Optional commitment') }}</span>
                                </label>
                            </div>

                            <div v-if="optionAccept" class="max-w-sm">
                                <ArtworkBaseListbox
                                    v-model="optionString"
                                    :items="bookingOptions"
                                    option-label="name"
                                    option-key="name"
                                    by="name"
                                    label="Option"
                                    :use-translations="false"
                                />
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Properties -->
                <section v-if="(event_properties?.length || 0) > 0" class="border-t border-border-hairline py-4">
                    <h3 class="mb-3 font-lexend font-semibold text-[11px] uppercase tracking-[0.08em] text-accent-600">{{ $t('Properties') }}</h3>

                    <div class="flex flex-wrap gap-2">
                        <label
                            v-for="ep in event_properties"
                            :key="ep.id"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-full h-[26px] px-3 text-xs border transition has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-accent-600"
                            :class="ep.checked ? 'bg-accent-50 text-accent-700 border-accent-200' : 'bg-surface border-border text-text-muted hover:bg-surface-hover'"
                        >
                            <input type="checkbox" v-model="ep.checked" class="sr-only" />
                            <IconCheck v-if="ep.checked" class="size-3.5" />
                            <PropertyIcon :name="ep.icon" class="size-3.5" />
                            <span>{{ ep.name }}</span>
                        </label>
                    </div>
                </section>

                </div>

                <!-- Serien-Tab: Liste aller Termine der Serie (Bearbeiten) bzw. Vorschau (Anlegen) -->
                <div v-if="activeTab === 'series'" class="pb-4">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <h3 class="font-lexend font-semibold text-[11px] uppercase tracking-[0.08em] text-accent-600">
                            {{ isSeriesEvent ? $t('Events of this series') : $t('Preview of the series') }}
                        </h3>
                        <span class="ui-hint">{{ seriesSummaryText }}</span>
                    </div>

                    <div v-if="seriesLoading || seriesPreviewLoading" class="py-6 text-center text-[13px] text-text-muted">
                        {{ $t('Loading…') }}
                    </div>
                    <div v-else-if="seriesLoadFailed" class="ui-error py-4">
                        {{ $t('Data could not be loaded.') }}
                    </div>
                    <div v-else-if="!isSeriesEvent && !seriesDefinitionValid" class="py-6 text-center text-[13px] text-text-muted">
                        {{ $t('Choose a frequency and an end for the series to see the preview.') }}
                    </div>
                    <div v-else-if="seriesRows.length === 0" class="py-6 text-center text-[13px] text-text-muted">
                        {{ $t('No events') }}
                    </div>
                    <ul v-else class="max-h-[46vh] overflow-y-auto divide-y divide-border-hairline rounded-[10px] border border-border-subtle">
                        <li
                            v-for="row in seriesRows"
                            :key="row.key"
                            class="flex flex-wrap items-center gap-x-3 gap-y-1 px-3 py-2 text-[13px]"
                            :class="[
                                row.isCurrent ? 'bg-accent-50' : '',
                                row.isTrashed || row.isPast ? 'text-text-subtle' : 'text-text',
                                row.isTrashed ? 'line-through decoration-border' : '',
                            ]"
                        >
                            <span class="w-6 shrink-0 text-right text-[11px] tabular-nums text-text-subtle">{{ row.index }}</span>
                            <span class="w-[132px] shrink-0 font-medium">{{ row.dateLabel }}</span>
                            <span class="w-[104px] shrink-0 tabular-nums">{{ row.timeLabel }}</span>
                            <span class="min-w-0 flex-1 truncate text-text-muted">{{ row.roomName ?? '' }}</span>
                            <span class="flex shrink-0 flex-wrap items-center gap-1">
                                <span v-if="row.isCurrent" class="rounded-full bg-accent-600 px-2 py-0.5 text-[11px] font-semibold text-white">{{ $t('This event') }}</span>
                                <span v-if="row.isException" class="rounded-full border border-warning-border bg-warning-surface px-2 py-0.5 text-[11px] text-warning">{{ $t('Deviating') }}</span>
                                <span v-if="row.isTrashed" class="rounded-full border border-border bg-surface px-2 py-0.5 text-[11px] text-text-subtle">{{ $t('In the trash') }}</span>
                                <span v-if="row.shiftsCount > 0" class="rounded-full border border-border bg-surface px-2 py-0.5 text-[11px] text-text-muted" :title="$t('Shifts')">{{ row.shiftsCount }} {{ $t('Shifts') }}</span>
                                <span v-if="row.collisions > 0" class="inline-flex items-center gap-1 rounded-full border border-warning-border bg-warning-surface px-2 py-0.5 text-[11px] text-warning">
                                    <IconAlertTriangle class="size-3" />
                                    {{ $t('{0} potential conflicts detected', [row.collisions]) }}
                                </span>
                            </span>
                            <a
                                v-if="row.id && !row.isCurrent && !row.isTrashed"
                                :href="route('dashboard.redirect-to-calendar', { event: row.id })"
                                class="ml-1 shrink-0 text-[12px] text-accent-600 hover:underline"
                                :title="$t('Show in calendar')"
                            >
                                {{ $t('Show in calendar') }}
                            </a>
                        </li>
                    </ul>
                    <p v-if="seriesPreview?.capped" class="ui-hint mt-2">{{ $t('A series is limited to {0} events.', [500]) }}</p>
                </div>

                <!-- Sticky Action Bar -->
                <div class="ui-footer">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <BaseUIButton
                                v-if="event?.id && event?.canDelete"
                                type="button"
                                variant="danger"
                                hide-icon
                                @click="onDeleteClick"
                            >
                                <IconTrash class="size-4" />
                                {{ $t('Put in the trash') }}
                            </BaseUIButton>
                        </div>

                        <div class="flex items-center gap-2">
                            <BaseUIButton type="button" hide-icon @click="closeModal">
                                {{ $t('Cancel') }}
                            </BaseUIButton>

                            <BaseUIButton
                                v-if="canCreateDirect"
                                type="button"
                                variant="primary"
                                hide-icon
                                :disabled="isPrimaryDisabled"
                                @click="updateOrCreateEvent()"
                            >
                                {{ primaryButtonText }}
                            </BaseUIButton>
                            <BaseUIButton
                                v-else
                                type="button"
                                variant="primary"
                                hide-icon
                                :disabled="requestDisabled"
                                @click="updateOrCreateEvent(true)"
                            >
                                {{ $t('Request occupancy') }}
                            </BaseUIButton>
                        </div>
                    </div>
                </div>
            </div>

            <!-- READONLY MODE -->
            <div v-else class="py-4">
                <h3 class="mb-3 font-lexend font-semibold text-[11px] uppercase tracking-[0.08em] text-accent-600">{{ $t('Event overview') }}</h3>

                <div class="flex items-center gap-3">
                    <div class="size-9 rounded-full ring-1 ring-border-subtle" :style="{ backgroundColor: selectedEventType?.hex_code }" />
                    <div class="min-w-0">
                        <h2 class="truncate text-[15px] font-semibold text-text">{{ eventName }}</h2>
                        <div class="mt-0.5 flex items-center gap-3 text-[12.5px] text-text-muted">
                            <span>{{ selectedEventType?.name }}</span>
                            <span v-if="selectedEventStatus" class="inline-flex items-center gap-1">
                            <span class="inline-block size-2 rounded-full" :style="{ backgroundColor: selectedEventStatus?.color }"></span>
                                {{ selectedEventStatus?.name }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-3 ui-grid-2">
                    <div class="space-y-1.5">
                        <div class="ui-hint">{{ $t('Date & Time') }}</div>
                        <div class="text-[13px] text-text">
                            <span v-if="startDate === endDate">{{ formatDateGerman(startDate) }} • {{ startTime }} – {{ endTime }}</span>
                            <span v-else>{{ formatDateGerman(startDate) }} {{ startTime }} — {{ formatDateGerman(endDate) }} {{ endTime }}</span>
                        </div>
                        <div class="text-[13px] text-text" v-if="admissionModule && admissionTime">
                            {{ $t('Admission') }}: {{ admissionTime }}
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <div class="ui-hint">{{ $t('Room') }}</div>
                        <div class="text-[13px] text-text">{{ selectedRoom?.name }}</div>
                    </div>
                    <div v-if="selectedProject?.id" class="space-y-1.5">
                        <div class="ui-hint">{{ $t('Project') }}</div>
                        <div class="text-[13px]">
                            <a
                                v-if="canAccessProject()"
                                :href="route('projects.tab', { project: selectedProject.id, projectTab: first_project_calendar_tab_id })"
                                class="text-accent-600 hover:underline"
                            >
                                {{ selectedProject?.name }}
                            </a>
                            <span v-else class="text-text">{{ selectedProject?.name }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="description" class="mt-3 border-t border-border-subtle pt-3 text-[13px] text-text">
                    {{ description }}
                </div>

                <div v-if="checkedEventProperties.length" class="mt-3 border-t border-border-subtle pt-3">
                    <div class="mb-1 ui-hint">{{ $t('Properties') }}</div>
                    <div class="flex flex-wrap gap-1.5">
            <span
                v-for="(ep, i) in checkedEventProperties"
                :key="ep.id ?? i"
                class="inline-flex items-center gap-1.5 rounded-full border border-border-subtle bg-surface-sunken px-2.5 py-1 text-[12.5px] text-text"
            >
              <component :is="ep.icon" class="size-3.5" />
              <span>{{ ep.name }}</span>
            </span>
                    </div>
                </div>

                <div class="mt-3 flex w-full justify-end">
                    <BaseUIButton type="button" hide-icon @click="closeModal">{{ $t('Close') }}</BaseUIButton>
                </div>
            </div>
        </div>

        <!-- Confirmations / Series -->
        <ConfirmationComponent
            v-if="deleteComponentVisible"
            :confirm="$t('Delete')"
            :titel="$t('Delete event?')"
            :description="$t('Are you sure you want to put the event {0} in the trash? You can restore it within 30 days.', [event?.title ?? ''])"
            @closed="afterConfirm"
        />
    </ArtworkBaseModal>

    <!-- Serientermin löschen: Reichweite wählen -->
    <ArtworkBaseModal
        v-if="showSeriesDeleteModal"
        :title="$t('Put in the trash')"
        :description="$t('This event is part of a series. Which events should be put in the trash?')"
        modal-size="sm:max-w-lg"
        @close="showSeriesDeleteModal = false"
    >
        <div class="mt-4 grid gap-2">
            <button
                v-for="opt in scopeOptions"
                :key="opt.id"
                type="button"
                class="flex w-full flex-col items-start rounded-[10px] border border-border-subtle px-4 py-3 text-left transition hover:border-accent-300 hover:bg-accent-50"
                :disabled="isLoading"
                @click="deleteSeriesScoped(opt.id)"
            >
                <span class="text-[13px] font-semibold text-text">{{ opt.label }}</span>
                <span class="text-[12px] text-text-muted">{{ deleteScopeDescription(opt.id) }}</span>
            </button>
        </div>
        <div class="mt-4 flex justify-end">
            <BaseUIButton type="button" hide-icon @click="showSeriesDeleteModal = false">{{ $t('Cancel') }}</BaseUIButton>
        </div>
    </ArtworkBaseModal>

    <!-- Serie abwählen: Termin lösen oder Serie beenden -->
    <ArtworkBaseModal
        v-if="showSeriesDetachModal"
        :title="$t('Remove from series?')"
        :description="$t('This event is part of a series. What should happen?')"
        modal-size="sm:max-w-lg"
        @close="cancelDetach"
    >
        <div class="mt-4 grid gap-2">
            <button
                type="button"
                class="flex w-full flex-col items-start rounded-[10px] border border-border-subtle px-4 py-3 text-left transition hover:border-accent-300 hover:bg-accent-50"
                :disabled="isLoading"
                @click="confirmDetach('single')"
            >
                <span class="text-[13px] font-semibold text-text">{{ $t('Detach only this event') }}</span>
                <span class="text-[12px] text-text-muted">{{ $t('This event becomes a single event. The other events of the series stay as they are.') }}</span>
            </button>
            <button
                type="button"
                class="flex w-full flex-col items-start rounded-[10px] border border-border-subtle px-4 py-3 text-left transition hover:border-danger hover:bg-danger/5"
                :disabled="isLoading"
                @click="confirmDetach('end')"
            >
                <span class="text-[13px] font-semibold text-text">{{ $t('End the series') }}</span>
                <span class="text-[12px] text-text-muted">{{ $t('All other events of the series ({0}) are put in the trash. This event becomes a single event.', [seriesOtherActiveCount]) }}</span>
            </button>
        </div>
        <div class="mt-4 flex justify-end">
            <BaseUIButton type="button" hide-icon @click="cancelDetach">{{ $t('Cancel') }}</BaseUIButton>
        </div>
    </ArtworkBaseModal>

    <!-- Warnung vor Turnus-/Ende-Änderung -->
    <ArtworkBaseModal
        v-if="showSeriesImpactModal"
        :title="$t('Change series?')"
        :description="seriesImpact?.rebuild
            ? $t('Changing the frequency re-creates the future events of the series from this event on.')
            : $t('Changing the end of the series adds or removes events at the end of the series.')"
        modal-size="sm:max-w-lg"
        @close="showSeriesImpactModal = false"
    >
        <ul class="mt-4 space-y-1.5 text-[13px] text-text">
            <li v-if="seriesImpact?.trash > 0" class="flex items-start gap-2">
                <IconAlertTriangle class="mt-0.5 size-4 shrink-0 text-warning" />
                <span>{{ $t('{0} events are put in the trash', [seriesImpact.trash]) }}<span v-if="seriesImpact?.shifts > 0">, {{ $t('including {0} shifts', [seriesImpact.shifts]) }}</span>.</span>
            </li>
            <li v-if="seriesImpact?.create > 0" class="flex items-start gap-2">
                <IconRepeat class="mt-0.5 size-4 shrink-0 text-accent-600" />
                <span>{{ $t('{0} events are created', [seriesImpact.create]) }}.</span>
            </li>
            <li v-if="seriesImpact?.exceptions > 0" class="flex items-start gap-2">
                <IconCheck class="mt-0.5 size-4 shrink-0 text-success" />
                <span>{{ $t('{0} individually adjusted events are kept', [seriesImpact.exceptions]) }}.</span>
            </li>
            <li class="flex items-start gap-2 text-text-muted">
                <IconCheck class="mt-0.5 size-4 shrink-0" />
                <span>{{ $t('Events in the trash can be restored. No date is created twice.') }}</span>
            </li>
        </ul>
        <div class="mt-6 flex justify-end gap-2">
            <BaseUIButton type="button" variant="secondary" hide-icon @click="showSeriesImpactModal = false">{{ $t('Cancel') }}</BaseUIButton>
            <BaseUIButton type="button" variant="primary" hide-icon :disabled="isLoading" @click="confirmSeriesImpactAndSave">{{ $t('Apply changes') }}</BaseUIButton>
        </div>
    </ArtworkBaseModal>

    <!-- Confirm: Verschiebung löst Einzeltag-Projektzuordnungen auf -->
    <ArtworkBaseModal
        v-if="showAssignmentImpactModal"
        :title="$t('Dissolve project assignments?')"
        :description="$t('If you move this event, the following project assignments will be dissolved because they fall outside the new project period.')"
        modal-size="sm:max-w-lg"
        @close="showAssignmentImpactModal = false"
    >
        <div class="mt-4 space-y-2">
            <div
                v-for="(affected, index) in assignmentImpactList"
                :key="index"
                class="flex items-start gap-2 rounded-lg border border-border-subtle bg-surface-sunken/70 px-3 py-2 text-xs text-text-muted"
            >
                <span class="mt-1 inline-flex h-1.5 w-1.5 shrink-0 rounded-full" :class="affected.type === 'wish' ? 'bg-success' : 'bg-danger'"></span>
                <span>
                    <span class="font-medium" :class="affected.type === 'wish' ? 'italic' : ''">{{ affected.worker_name }}</span>
                    <span v-if="affected.type === 'wish'" class="italic"> ({{ $t('Wish') }})</span>
                    <span class="text-text-subtle"> &middot; {{ affected.dates.join(', ') }}</span>
                </span>
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-6">
            <BaseUIButton type="button" variant="secondary" hide-icon @click="showAssignmentImpactModal = false">
                {{ $t('Cancel') }}
            </BaseUIButton>
            <BaseUIButton type="button" variant="primary" hide-icon @click="confirmAssignmentImpactAndSave">
                {{ $t('Move anyway') }}
            </BaseUIButton>
        </div>
    </ArtworkBaseModal>

    <!-- Bestätigungsdialog beim Schließen -->
    <ArtworkBaseModal
        v-if="showDiscardConfirmation"
        @close="showDiscardConfirmation = false"
        modal-size="sm:max-w-md"
        :title="$t('Discard data')"
        :description="$t('Should the entered data be discarded?')"
    >
        <div class="flex justify-end gap-3 mt-4">
            <button
                type="button"
                class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium text-text-muted hover:bg-surface-sunken transition"
                @click="showDiscardConfirmation = false"
            >
                {{ $t('No, continue editing') }}
            </button>
            <button
                type="button"
                class="inline-flex items-center rounded-lg bg-danger px-4 py-2 text-sm font-semibold text-white hover:bg-danger transition"
                @click="confirmDiscard"
            >
                {{ $t('Discard') }}
            </button>
        </div>
    </ArtworkBaseModal>
</template>


<script setup>
import {computed, inject, nextTick, onMounted, ref, watch} from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import dayjs from 'dayjs'
import { can } from 'laravel-permission-to-vuejs'

import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import FormButton from '@/Layouts/Components/General/Buttons/FormButton.vue'
import UserPopoverTooltip from '@/Layouts/Components/UserPopoverTooltip.vue'
import NewUserToolTip from '@/Layouts/Components/NewUserToolTip.vue'
import ConfirmationComponent from '@/Layouts/Components/ConfirmationComponent.vue'
import ProjectSearch from '@/Components/SearchBars/ProjectSearch.vue'
import RoomSearch from '@/Components/SearchBars/RoomSearch.vue'

import { IconAlertTriangle, IconArrowsMoveHorizontal, IconCheck, IconChevronUp, IconCircleX, IconRepeat, IconTrash } from '@tabler/icons-vue'
import SwitchIconTooltip from '@/Artwork/Toggles/SwitchIconTooltip.vue'
import { useEvent } from '@/Composeables/Event.js'
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import {useI18n} from "vue-i18n";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";
import LastedProjects from "@/Artwork/LastedProjects.vue";
const { t } = useI18n(), $t = t;
const props = defineProps({
    showHints: { type: Boolean, default: false },
    eventTypes: { type: Array, required: true },
    // Rooms can arrive a bit later depending on the caller. Provide a safe default.
    rooms: { type: Array, required: false, default: () => [] },
    isAdmin: { type: Boolean, default: false },
    event: { type: [Object, Boolean], default: null, required: false },
    project: { type: Object, default: null },
    wantedRoomId: { type: [Number, String], default: null },
    roomCollisions: { type: Object, default: () => ({}) },
    showComments: { type: Boolean, default: false },
    first_project_calendar_tab_id: { type: [Number, String], required: true },
    usedInBulkComponent: { type: Boolean, default: false },
    requiresAxiosRequests: { type: Boolean, default: false },
    calendarProjectPeriod: { type: Boolean, default: false },
    eventStatuses: { type: Array, default: () => [] },
    isPlanning: { type: Boolean, default: false },
    wantedDate: { type: String, default: null },
    declinedRoomId: { type: [Number, String], default: null },
})
const emit = defineEmits(['closed'])

const page = usePage()
const statusModule = computed(() => page.props.event_status_module)
const admissionModule = computed(() => page.props.event_admission_module)
const { getDaysOfEvent } = useEvent()
const event_properties = inject('event_properties', [])

// --- State
const submit = ref(true)
const startDate = ref(null)
const startTime = ref(null)
const endDate = ref(null)
const endTime = ref(null)
const admissionTime = ref(null)
const oldStartDate = ref(null)
const oldStartTime = ref(null)
const oldEndDate = ref(null)
const oldEndTime = ref(null)

// Bearbeitungsverhalten beim Ändern des Startdatums: Zeitraum mitverschieben (true)
// oder Enddatum fixiert lassen (false, Standard). Persistiertes User-Setting;
// im Bulk-Kontext wird die von BulkBody bereitgestellte Ref geteilt, damit beide
// Umschalter synchron bleiben.
const injectedShiftPeriod = inject('shiftPeriodOnStartDateChange', null)
const shiftPeriodOnStartDateChange = injectedShiftPeriod
    ?? ref(usePage().props.auth.user?.shift_period_on_start_date_change ?? false)

const onToggleShiftPeriodOnStartDateChange = () => {
    axios.patch(
        route('user.update.shift_period_on_start_date_change', {user: usePage().props.auth.user.id}),
        {shift_period_on_start_date_change: shiftPeriodOnStartDateChange.value}
    ).catch((e) => console.error('shift-period-setting:patch-failed', e))
}

// --- Wiederholungstermine (KONZEPT_Wiederholungstermine.md)
const series = ref(false)
const seriesEndDate = ref(null)
const frequencies = computed(() => [
    { id: 1, name: $t('Daily') },
    { id: 2, name: $t('Weekly') },
    { id: 3, name: $t('Every 2 weeks') },
    { id: 4, name: $t('Monthly') },
])
const selectedFrequency = ref(frequencies.value[1])
// Ende: Datum ODER Anzahl Termine
const seriesEndMode = ref('date')
const seriesOccurrenceCount = ref(10)
// ISO-Wochentage 1..7, nur bei wöchentlich / alle 2 Wochen
const seriesWeekdays = ref([])
// Reichweite beim Bearbeiten eines Serientermins
const seriesScope = ref('single')
const activeTab = ref('event')
// Definition + alle Termine der Serie (beim Öffnen nachgeladen, hält das Kalender-Paket schlank)
const seriesInfo = ref(null)
const seriesLoading = ref(false)
const seriesLoadFailed = ref(false)
let initialSeriesDefinition = null
// Vorschau beim Anlegen
const seriesPreview = ref(null)
const seriesPreviewLoading = ref(false)
let seriesPreviewTimer = null
// Dialoge
const showSeriesDeleteModal = ref(false)
const showSeriesDetachModal = ref(false)
const showSeriesImpactModal = ref(false)
const seriesImpact = ref(null)
let seriesImpactConfirmed = false

const WEEKDAY_KEYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
const weekdayOptions = computed(() => WEEKDAY_KEYS.map((key, i) => ({ id: i + 1, label: $t(key) })))
const isSeriesEvent = computed(() => !!props.event?.id && !!props.event?.is_series)
const showSeriesHeader = computed(() => isSeriesEvent.value || (!props.event?.id && series.value))
const seriesFieldsLocked = computed(() => isSeriesEvent.value && (seriesScope.value === 'single' || seriesLoading.value || seriesLoadFailed.value))
const showWeekdayPicker = computed(() => [2, 3].includes(selectedFrequency.value?.id))
const anchorWeekday = computed(() => {
    const d = dayjs(startDate.value)
    return d.isValid() ? ((d.day() + 6) % 7) + 1 : null
})
const scopeOptions = computed(() => [
    { id: 'single', label: $t('Only this event') },
    { id: 'following', label: $t('This and following') },
    { id: 'all', label: $t('Whole series') },
])
const scopeHint = computed(() => {
    if (seriesScope.value === 'following') return $t('Changes apply to this and all following events of the series. Changed times are applied as a shift.')
    if (seriesScope.value === 'all') return $t('Changes apply to all events of the series, including past ones. Changed times are applied as a shift.')
    return $t('Changes apply only to this event. Changing date, time or room makes it a deviating event of the series.')
})
const seriesDefinitionValid = computed(() => {
    if (!series.value || !selectedFrequency.value) return false
    if (seriesEndMode.value === 'count') {
        const n = Number(seriesOccurrenceCount.value)
        return Number.isInteger(n) && n >= 1 && n <= 500
    }
    if (!seriesEndDate.value) return false
    return !startDate.value || seriesEndDate.value >= startDate.value
})
const seriesValidationMessage = computed(() => {
    if (!series.value || seriesFieldsLocked.value) return ''
    if (seriesEndMode.value === 'count') {
        const n = Number(seriesOccurrenceCount.value)
        return Number.isInteger(n) && n >= 1 && n <= 500 ? '' : $t('Please enter a number between 1 and 500.')
    }
    if (!seriesEndDate.value) return $t('Please choose an end date for the series.')
    if (startDate.value && seriesEndDate.value < startDate.value) return $t('The end of the series must not be before the start of the event.')
    return ''
})
function normalizedWeekdays() {
    if (!showWeekdayPicker.value) return null
    const set = new Set(seriesWeekdays.value.map(Number))
    if (anchorWeekday.value) set.add(anchorWeekday.value)
    const list = [...set].sort((a, b) => a - b)
    return list.length === 1 && list[0] === anchorWeekday.value ? null : list
}
function currentSeriesDefinition() {
    return {
        frequency: selectedFrequency.value?.id ?? null,
        end_date: seriesEndMode.value === 'date' ? (seriesEndDate.value || null) : null,
        weekdays: normalizedWeekdays(),
        occurrence_count: seriesEndMode.value === 'count' ? Number(seriesOccurrenceCount.value) : null,
    }
}
function seriesDefinitionChanged() {
    return initialSeriesDefinition !== null && JSON.stringify(currentSeriesDefinition()) !== initialSeriesDefinition
}
const activeSeriesOccurrences = computed(() => (seriesInfo.value?.occurrences ?? []).filter(o => !o.isTrashed))
const seriesOtherActiveCount = computed(() => activeSeriesOccurrences.value.filter(o => !o.isCurrent).length)
const seriesTabCount = computed(() => {
    if (isSeriesEvent.value) return seriesInfo.value ? activeSeriesOccurrences.value.length : null
    return seriesPreview.value?.total ?? null
})
const seriesSummaryText = computed(() => {
    if (!series.value) return ''
    let text = selectedFrequency.value?.name ?? ''
    const weekdays = normalizedWeekdays()
    if (weekdays) text += ' (' + weekdays.map(d => $t(WEEKDAY_KEYS[d - 1])).join(', ') + ')'
    if (seriesEndMode.value === 'count') text += ' · ' + $t('{0} events', [Number(seriesOccurrenceCount.value) || 0])
    else if (seriesEndDate.value) text += ' · ' + $t('until {0}', [convertDateFormat(seriesEndDate.value)])
    const count = isSeriesEvent.value ? activeSeriesOccurrences.value.length : seriesPreview.value?.total
    if (seriesEndMode.value !== 'count' && count) text += ' · ' + $t('{0} events', [count])
    return text
})
function weekdayLabelOf(dateString) {
    const d = dayjs(dateString)
    return d.isValid() ? $t(WEEKDAY_KEYS[(d.day() + 6) % 7]) : ''
}
function occurrenceRow(o, index, extra = {}) {
    const start = dayjs(o.start)
    const end = dayjs(o.end)
    const sameDay = start.format('YYYY-MM-DD') === end.format('YYYY-MM-DD')
    return {
        key: o.id ?? o.start,
        id: o.id ?? null,
        index,
        dateLabel: `${weekdayLabelOf(o.start)}, ${start.format('DD.MM.YYYY')}`,
        timeLabel: o.allDay ? $t('Full day') : (sameDay ? `${start.format('HH:mm')}–${end.format('HH:mm')}` : `${start.format('HH:mm')} – ${end.format('DD.MM. HH:mm')}`),
        roomName: o.roomName ?? null,
        isCurrent: !!o.isCurrent,
        isException: !!o.isException,
        isTrashed: !!o.isTrashed,
        isPast: !!o.isPast,
        shiftsCount: o.shiftsCount ?? 0,
        collisions: o.collisions ?? 0,
        ...extra,
    }
}
const seriesRows = computed(() => {
    if (isSeriesEvent.value) {
        return (seriesInfo.value?.occurrences ?? []).map((o, i) => occurrenceRow(o, i + 1))
    }
    return (seriesPreview.value?.occurrences ?? []).map((o, i) => occurrenceRow(
        { ...o, allDay: allDayEvent.value, roomName: selectedRoom.value?.name ?? null },
        i + 1,
        { isCurrent: i === 0 }
    ))
})
function toggleWeekday(id) {
    if (id === anchorWeekday.value) return
    const idx = seriesWeekdays.value.indexOf(id)
    if (idx >= 0) seriesWeekdays.value.splice(idx, 1)
    else seriesWeekdays.value.push(id)
}
function applySeriesDefinition(def) {
    if (!def) return
    const found = frequencies.value.find(f => f.id === def.frequency_id)
    if (found) selectedFrequency.value = found
    seriesWeekdays.value = Array.isArray(def.weekdays) ? [...def.weekdays] : []
    if (def.occurrence_count) {
        seriesEndMode.value = 'count'
        seriesOccurrenceCount.value = def.occurrence_count
        seriesEndDate.value = def.end_date ?? null
    } else {
        seriesEndMode.value = 'date'
        seriesEndDate.value = def.end_date ?? null
        seriesOccurrenceCount.value = seriesInfo.value?.counts?.active ?? 10
    }
    initialSeriesDefinition = JSON.stringify(currentSeriesDefinition())
}
async function loadSeriesInfo() {
    seriesInfo.value = null
    seriesLoadFailed.value = false
    initialSeriesDefinition = null
    if (!props.event?.id || !props.event?.is_series) return
    seriesLoading.value = true
    try {
        const { data } = await axios.get(route('events.series.show', { event: props.event.id }))
        seriesInfo.value = data
        applySeriesDefinition(data.series)
    } catch {
        seriesLoadFailed.value = true
    } finally {
        seriesLoading.value = false
    }
}
function scheduleSeriesPreview() {
    clearTimeout(seriesPreviewTimer)
    if (isSeriesEvent.value || !series.value || !seriesDefinitionValid.value) {
        seriesPreview.value = null
        return
    }
    seriesPreviewTimer = setTimeout(fetchSeriesPreview, 350)
}
async function fetchSeriesPreview() {
    const start = formatDate(startDate.value, allDayEvent.value ? '00:00' : startTime.value)
    const end = formatDate(endDate.value, allDayEvent.value ? '23:59' : endTime.value)
    if (!start || !end) return
    seriesPreviewLoading.value = true
    try {
        const { data } = await axios.post(route('events.series.preview'), {
            start,
            end,
            roomId: selectedRoom.value?.id ?? null,
            ...seriesDefinitionPayload(),
        })
        seriesPreview.value = data
    } catch {
        seriesPreview.value = null
    } finally {
        seriesPreviewLoading.value = false
    }
}
function seriesDefinitionPayload() {
    return {
        seriesFrequency: selectedFrequency.value?.id ?? null,
        seriesEndDate: seriesEndMode.value === 'date' ? (seriesEndDate.value || null) : null,
        seriesWeekdays: showWeekdayPicker.value ? seriesWeekdays.value.map(Number) : null,
        seriesOccurrenceCount: seriesEndMode.value === 'count' ? Number(seriesOccurrenceCount.value) : null,
    }
}
// Serie abwählen: nie still löschen – Rückfrage „lösen“ oder „beenden“
function onSeriesToggle() {
    if (isSeriesEvent.value && !series.value) {
        series.value = true
        showSeriesDetachModal.value = true
    }
}
function cancelDetach() {
    showSeriesDetachModal.value = false
}
async function confirmDetach(mode) {
    isLoading.value = true
    try {
        await axios.post(route('events.series.detach', { event: props.event.id }), { mode })
        showSeriesDetachModal.value = false
        isLoading.value = false
        closeModal(true)
    } catch (e) {
        isLoading.value = false
        error.value = e?.response?.data?.errors ?? e
    }
}
function onDeleteClick() {
    if (isSeriesEvent.value) showSeriesDeleteModal.value = true
    else deleteComponentVisible.value = true
}
function deleteScopeDescription(scope) {
    if (scope === 'single') return $t('Only this event is put in the trash.')
    const currentStart = props.event?.start ?? ''
    const following = activeSeriesOccurrences.value.filter(o => o.isCurrent || o.start >= currentStart).length
    if (scope === 'following') return $t('This and all following events of the series ({0}).', [following])
    return $t('All events of the series ({0}).', [activeSeriesOccurrences.value.length])
}
async function deleteSeriesScoped(scope) {
    isLoading.value = true
    try {
        if (scope === 'single') await axios.delete(`/events/${props.event.id}`)
        else await axios.delete(route('events.series.delete', { event: props.event.id }), { data: { scope } })
        showSeriesDeleteModal.value = false
        isLoading.value = false
        closeModal(true)
    } catch (e) {
        isLoading.value = false
        error.value = e?.response?.data?.errors ?? e
    }
}
// Warnung vor Turnus-/Ende-Änderung (Dry-Run im Backend)
async function checkSeriesImpact() {
    if (!isSeriesEvent.value || seriesScope.value === 'single' || !series.value || seriesImpactConfirmed) return true
    if (!seriesDefinitionValid.value || !seriesDefinitionChanged()) return true
    try {
        const { data } = await axios.post(route('events.series.impact', { event: props.event.id }), seriesDefinitionPayload())
        if (data?.changed && (data.trash > 0 || data.create > 0 || data.rebuild)) {
            seriesImpact.value = data
            showSeriesImpactModal.value = true
            return false
        }
    } catch {
        // Der Precheck darf das Speichern nie blockieren
    }
    return true
}
async function confirmSeriesImpactAndSave() {
    seriesImpactConfirmed = true
    showSeriesImpactModal.value = false
    await doSaveEvent()
}
// Zeitraum, den der Kalender nach dem Schließen neu laden soll
function affectedDayRange() {
    let from = startDate.value
    let to = endDate.value
    const push = (d) => {
        if (!d) return
        const day = String(d).slice(0, 10)
        if (!from || day < from) from = day
        if (!to || day > to) to = day
    }
    push(oldStartDate.value)
    push(oldEndDate.value)
    if (series.value && seriesEndMode.value === 'date') push(seriesEndDate.value)
    if (isSeriesEvent.value) {
        push(seriesInfo.value?.counts?.first)
        push(seriesInfo.value?.counts?.last)
    } else if (series.value) {
        const last = seriesPreview.value?.occurrences?.at(-1)?.start
        push(last)
    }
    return [from, to]
}

const projectName = ref(null)
const title = ref(null)
const isOption = ref(null)
const eventName = ref(null)
const selectedEventType = ref(props.eventTypes?.[0] ?? null)
const selectedEventStatus = ref(props.eventStatuses?.find(s => s.default) ?? props.eventStatuses?.[0] ?? null)

const showProjectInfo = ref(Boolean(props.project) || (props.calendarProjectPeriod && page.props.auth.user.calendar_settings.time_period_project_id))
const allDayEvent = ref(!!usePage().props.event_all_day_default)
const selectedProject = ref(null)
const selectedRoom = ref(null)
const error = ref(null)
const creatingProject = ref(false)
const description = ref(null)
// Der Kalender liefert den Beschreibungs-Volltext nur mit, wenn die Anzeige-
// einstellung ihn in der Kachel zeigt. Sonst wird er beim Oeffnen nachgeladen —
// ohne das wuerde Speichern einen vorhandenen Text ueberschreiben.
const descriptionTouched = ref(false)
const descriptionLoadFailed = ref(false)
let descriptionRequest = null

// Zeit-/Projekt-Stand beim Oeffnen (Snapshot passiert in openModal). Muss VOR
// loadFullDescription deklariert sein: der immediate-Watcher auf props.event
// ruft openModal -> loadFullDescription bereits waehrend des setup() auf —
// eine spaetere const-Deklaration crasht dort mit einem TDZ-ReferenceError
// und das Termin-Modal oeffnet gar nicht.
const initialTiming = ref(null)

function loadFullDescription() {
    descriptionTouched.value = false
    descriptionLoadFailed.value = false
    descriptionRequest = null
    initialTiming.value = null
    if (!props.event?.id) return

    const prefilled = description.value
    descriptionRequest = axios.get(route('events.description', { event: props.event.id }))
        .then(({ data }) => {
            // Nur uebernehmen, wenn zwischenzeitlich niemand getippt hat
            if (!descriptionTouched.value && description.value === prefilled) {
                description.value = data?.description ?? ''
            }
        })
        .catch(() => { descriptionLoadFailed.value = true })
        .finally(() => { descriptionRequest = null })
}

// Vor dem Speichern muss der Volltext stehen, sonst geht er verloren.
async function ensureDescriptionLoaded() {
    if (descriptionTouched.value) return true
    if (descriptionRequest) await descriptionRequest
    if (!descriptionLoadFailed.value) return true

    loadFullDescription()
    if (descriptionRequest) await descriptionRequest
    return !descriptionLoadFailed.value
}
const canEdit = ref(false)
const declinedRoomId = ref(null)
const deleteComponentVisible = ref(false)
const adminComment = ref('')
const optionString = ref(null)
const accept = ref(true)
const optionAccept = ref(false)
const roomCollisionArray = ref(props.roomCollisions ?? {})
const helpTextLengthRoom = ref('')
const initialRoomId = ref(null)
const showRejections = ref(false)
const isLoading = ref(false)
const requestSubmitted = ref(false)
const quickDurations = [30, 60, 90]

const bookingOptions = [{ name: 'Option 1' }, { name: 'Option 2' }, { name: 'Option 3' }, { name: 'Option 4' }]

const answerRequestForm = useForm({ accepted: false })
const endAutoFilled = ref(false) // darf anfänglich auto-füllen
const defaultDurationMin = computed(() => {
    const raw = Number(page.props.event_time_length_minutes)
    return Number.isFinite(raw) && raw > 0 ? raw : 60
})

function setEndFromDuration() {
    if (!startDate.value || !startTime.value || allDayEvent.value) return
    const startDT = dayjs(`${startDate.value}T${startTime.value}`)
    const endDT = startDT.add(defaultDurationMin.value, 'minute')
    endDate.value = endDT.format('YYYY-MM-DD')
    endTime.value  = endDT.format('HH:mm')
    endAutoFilled.value = true
}
// --- Computeds
// Manche Aufrufer liefern rooms als Objekt-Map statt als Array – hier vereinheitlichen
const roomsList = computed(() => Array.isArray(props.rooms) ? props.rooms : Object.values(props.rooms || {}))
const isRoomAdmin = computed(() => {
    return roomsList.value.find(r => r.id === props.event?.roomId)?.admins?.includes(page.props.auth.user.id) || false
})
const isCreator = computed(() => (props.event ? props.event.created_by?.id === page.props.auth.user.id : false))
const hasAdminRole = () => props.isAdmin || page.props.auth.user?.roles?.some?.(r => r.name?.toLowerCase?.().includes('admin'))

const roomAdminIds = computed(() => selectedRoom.value?.admins ?? [])
const declinedRoomName = computed(() => {
    if (!declinedRoomId.value) return null
    return roomsList.value.find(r => r.id === Number(declinedRoomId.value))?.name ?? null
})

const modalTitle = computed(() => {
    if (props.event?.id) {
        if (props.event?.occupancy_option) return $t('Change & confirm occupancy')
        if (props.event?.isPlanning) return $t('Planned Event')
        return $t('Event')
    }
    return props.isPlanning ? $t('Create planned Event') : $t('New room allocation')
})

const modalDescription = computed(() => {
    if (props.event?.id) {
        if (props.event?.occupancy_option) return $t('Please review the event details and confirm the booking by selecting "Commitments" or "Optional commitment". You can also send a message to the inquirer if needed.')
        if (props.event?.isPlanning) return $t('This event is marked as planned. You can edit the details, but it will not be visible to regular users until it is confirmed.')
        return $t('Here you can view and edit the details of the event. Make sure to save any changes you make.')
    }
    return props.isPlanning
        ? $t('You are about to create a planned event. Please fill in the necessary details and save it. The event will not be visible to regular users until it is confirmed.')
        : !canCreateDirect.value
            ? $t('This booking requires approval. Saving creates a room request; the event is not firmly booked until it has been accepted.')
        : $t('Fill in the details for the new room allocation. Once you save, the event will be created and visible to users with access to the selected room.')
})

const checkedEventProperties = computed(() => (event_properties ?? []).filter(p => p.checked))

const canCreateDirect = computed(
    () => hasAdminRole() || selectedRoom.value?.everyone_can_book || roomAdminIds.value.includes(page.props.auth.user.id) || (props.isPlanning ? can('can plan fixed in planning calendar') : can('create events without request'))
)

const isPrimaryDisabled = computed(() => {
    const invalidSeries = series.value && !seriesFieldsLocked.value && !seriesDefinitionValid.value
    const missingRoom = !selectedRoom.value
    const missingSubmit = !submit.value
    const needDecision = props.event?.occupancy_option && accept.value === false && optionAccept.value === false && adminComment.value === ''
    return missingRoom || missingSubmit || invalidSeries || needDecision || isLoading.value
})
const primaryButtonText = computed(() => {
    if (!props.event?.occupancy_option) return $t('Save')
    if (accept.value) return $t('Commitments')
    if (optionAccept.value) return $t('Optional commitment')
    if (adminComment.value) return $t('Send message')
    return $t('Save')
})
const isRequestableForRoom = computed(() => {
    return selectedRoom.value?.requestable_by?.includes(page.props.auth.user.id) || false
})

const requestDisabled = computed(() => {
    const invalidSeries = series.value && !seriesFieldsLocked.value && !seriesDefinitionValid.value
    if (!selectedRoom.value || !submit.value || invalidSeries || isLoading.value) return true
    const canRequestGlobal = can('request room occupancy')
    const canRequestRoom = isRequestableForRoom.value
    if (!canRequestGlobal && !canRequestRoom && !canCreateDirect.value) return true
    if (!can('can see planning calendar') && props.isPlanning) return true
    return false
})

// --- Watches / Mount
watch(selectedRoom, () => checkChanges(), { deep: true })
watch(() => props.event, () => openModal(), { deep: true, immediate: true })
// Falls Räume asynchron/als Map eintreffen: gewünschten Raum nachtragen
watch(
    () => props.rooms,
    (roomsNow) => {
        if (selectedRoom.value || !props.wantedRoomId) return
        const list = Array.isArray(roomsNow) ? roomsNow : Object.values(roomsNow || {})
        const rid = Number(props.wantedRoomId)
        const found = list.find(r => Number(r?.id) === rid || Number(r?.roomId) === rid) || null
        if (found) selectedRoom.value = found
    },
    { deep: false }
)

onMounted(() => {
    if (props.wantedDate) {
        startDate.value = props.wantedDate
        startTime.value = page.props.event_start_time || '09:00'
        setEndFromDuration()
    }
    if (props.wantedRoomId) {
        const rid = Number(props.wantedRoomId)
        selectedRoom.value = findRoomById(rid)
    } else if (props.event) {
        const rid = Number(props.event.roomId)
        selectedRoom.value = findRoomById(rid)
    }
})

// --- Methods
// Anzeige im Read-only-Modus: ISO (Input-Format) → DD.MM.YYYY
function formatDateGerman(isoDate) {
    const parts = (isoDate ?? '').split('-')
    if (parts.length !== 3) return isoDate ?? ''
    return `${parts[2]}.${parts[1]}.${parts[0]}`
}
function canAccessProject() {
    if (can('view projects') || can('write projects')) return true
    if (selectedProject.value?.creator_id === page.props.auth.user.id) return true
    if (selectedProject.value?.team_members?.some?.(m => m.id === page.props.auth.user.id)) return true
    return false
}
function convertDateFormat(dateString) {
    const parts = (dateString ?? '').split('-')
    if (parts.length !== 3) return dateString
    return `${parts[2]}.${parts[1]}.${parts[0]}`
}
// Hilfsfunktion: robustes Finden eines Raums anhand id/roomId, inkl. Typ-Coercion
function findRoomById(rawId) {
    const rid = Number(rawId)
    if (!Number.isFinite(rid)) return null
    const list = roomsList.value || []
    return list.find(r => Number(r?.id) === rid || Number(r?.roomId) === rid) || null
}
function openModal() {
    canEdit.value = (!props.event?.id) || isCreator.value || isRoomAdmin.value || hasAdminRole() || can('create events without request')
    if (!props.event) {
        selectedEventType.value = props.eventTypes?.[0] ?? null
        selectedEventStatus.value = props.eventStatuses?.find(s => s.default) ?? props.eventStatuses?.[0] ?? null
        // Direkt vorbelegen: Datum/Zeit und Raum, falls gewünscht
        if (props.wantedDate) {
            startDate.value = props.wantedDate
            startTime.value = page.props.event_start_time || '09:00'
            setEndFromDuration()
        }
        if (props.wantedRoomId && !selectedRoom.value) {
            const rid = Number(props.wantedRoomId)
            selectedRoom.value = findRoomById(rid)
        }
        // NEU: Wenn ein Projekt-Prop gesetzt ist (z. B. im Projekt-Schichttab), Projekt vorauswählen
        if (props.project && !selectedProject.value) {
            selectedProject.value = { id: props.project.id, name: props.project.name }
            showProjectInfo.value = true
        } else if (props.calendarProjectPeriod && page.props.auth.user.calendar_settings.time_period_project_id) {
            selectedProject.value = { id: page.props.auth.user.calendar_settings.time_period_project_id, name: page.props.projectNameOfCalendarProject }
        }
        return
    }

    if (props.event?.project) {
        selectedProject.value = { id: props.event.project.id, name: props.event.project.name }
    } else if (props.calendarProjectPeriod && page.props.auth.user.calendar_settings.time_period_project_id) {
        selectedProject.value = { id: page.props.auth.user.calendar_settings.time_period_project_id, name: page.props.projectNameOfCalendarProject }
    }

    const start = dayjs(props.event.start)
    const end = dayjs(props.event.end)
    startDate.value = start.format('YYYY-MM-DD')
    startTime.value = start.format('HH:mm')
    endDate.value = end.format('YYYY-MM-DD')
    endTime.value = end.format('HH:mm')
    // TIME-Spalte kommt als "HH:mm:ss" — Inputs erwarten "HH:mm"
    admissionTime.value = (props.event.admission_time ?? props.event.admissionTime ?? null)?.slice(0, 5) ?? null
    oldStartDate.value = startDate.value
    oldStartTime.value = startTime.value
    oldEndDate.value = endDate.value
    oldEndTime.value = endTime.value

    title.value = props.event.title
    eventName.value = props.event.eventName

    const eventStatusId = props.event?.eventStatus?.id ?? props.event?.eventStatusId ?? props.event?.event_status_id
    selectedEventStatus.value = props.eventStatuses?.find(s => s.id === eventStatusId) ?? selectedEventStatus.value
    allDayEvent.value = !!props.event.allDay

    selectedEventType.value = props.event.eventType?.id
        ? props.eventTypes?.find(t => t.id === props.event.eventType.id) ?? props.eventTypes?.[0]
        : props.eventTypes?.[0]
    if (props.event?.eventTypeId) {
        selectedEventType.value = props.eventTypes?.find(t => t.id === props.event.eventTypeId) ?? selectedEventType.value
    }

    series.value = !!props.event.is_series
    seriesScope.value = 'single'
    activeTab.value = 'event'
    // Turnus, Wochentage, Ende und die Terminliste kommen aus events.series.show (nicht aus dem Kalender-Paket)
    loadSeriesInfo()

    if (selectedProject.value?.id) showProjectInfo.value = true

    if (props.wantedRoomId) {
        const rid = Number(props.wantedRoomId)
        selectedRoom.value = findRoomById(rid)
    } else {
        const rid = Number(props.event.roomId)
        selectedRoom.value = findRoomById(rid)
    }

    if (props.wantedDate) {
        startDate.value = props.wantedDate
        startTime.value = '09:00'
        endDate.value = props.wantedDate
        endTime.value = '10:00'
    }

    initialRoomId.value = selectedRoom.value?.id ?? null
    declinedRoomId.value = props.declinedRoomId ?? props.event.declinedRoomId ?? null
    description.value = props.event.description ?? ''
    loadFullDescription()
    initialTiming.value = timingSnapshot()

    ;(event_properties ?? []).forEach(ep => {
        ep.checked = props.event?.eventProperties?.some(eep => eep.id === ep.id) || false
    })

    checkCollisions()
}
function closeModal(closedOnPurpose = false) {
    if (closedOnPurpose) {
        emit(
            'closed',
            closedOnPurpose,
            Array.from(new Set([initialRoomId.value, selectedRoom.value?.id].filter(Boolean))),
            getDaysOfEvent(...affectedDayRange()),
            getDaysOfEvent(oldStartDate.value, oldEndDate.value),
        )
    } else {
        emit('closed', closedOnPurpose)
    }

    // minimal reset
    startDate.value = startTime.value = endDate.value = endTime.value = admissionTime.value = null
    oldStartDate.value = oldStartTime.value = oldEndDate.value = oldEndTime.value = null
    eventName.value = description.value = null
    descriptionTouched.value = false
    descriptionLoadFailed.value = false
    descriptionRequest = null
    selectedProject.value = selectedRoom.value = null
    selectedEventType.value = props.eventTypes?.[0] ?? null
    selectedEventStatus.value = props.eventStatuses?.find(s => s.default) ?? props.eventStatuses?.[0] ?? null
    allDayEvent.value = !!page.props.event_all_day_default
    series.value = false
    seriesEndDate.value = null
    seriesEndMode.value = 'date'
    seriesOccurrenceCount.value = 10
    seriesWeekdays.value = []
    selectedFrequency.value = frequencies.value[1]
    seriesScope.value = 'single'
    activeTab.value = 'event'
    seriesInfo.value = null
    seriesPreview.value = null
    initialSeriesDefinition = null
    seriesImpactConfirmed = false
    showSeriesDeleteModal.value = showSeriesDetachModal.value = showSeriesImpactModal.value = false
    requestSubmitted.value = false
    showProjectInfo.value = Boolean(props.project) || (props.calendarProjectPeriod && page.props.auth.user.calendar_settings.time_period_project_id)
    creatingProject.value = false
    projectName.value = null
    adminComment.value = ''
    accept.value = true
    optionAccept.value = false
    optionString.value = null
    error.value = null
    ;(event_properties ?? []).forEach(p => (p.checked = false))
    initialRoomId.value = null
}

const showDiscardConfirmation = ref(false)

function handleCloseAttempt() {
    // Headless-UI-Falle: Schließt ein innerer Dialog (Warnung, Lösch-/Lösen-Rückfrage) per Klick,
    // wertet der äußere Dialog denselben Klick als „außerhalb“ und würde das Termin-Modal mitschließen.
    if (innerDialogOpen.value || Date.now() < suppressOuterCloseUntil) return
    if (!props.event?.id) {
        showDiscardConfirmation.value = true
        return
    }
    closeModal()
}

function confirmDiscard() {
    showDiscardConfirmation.value = false
    closeModal()
}

function formatDate(date, time, toUTC = true) {
    // fehlende Werte abfangen
    if (!date || !time) return null

    // ISO-8601-kompatibel: "YYYY-MM-DDTHH:mm"
    const isoLocal = `${date}T${time}`

    // sicher parsen (alle Browser)
    const d = new Date(isoLocal)
    if (Number.isNaN(d.getTime())) return null

    // Wenn dein Backend UTC erwartet -> toUTC=true
    // Wenn lokales ISO ohne Z erwartet wird -> return isoLocal
    return toUTC ? d.toISOString() : isoLocal
}

function normalizeDateInput(raw) {
    if (!raw) return null
    // BaseInput (type=date) liefert normalerweise "YYYY-MM-DD".
    // Falls doch mal ein Date-Objekt oder ISO-String reinkommt: robust normalisieren.
    if (raw instanceof Date) {
        const d = dayjs(raw)
        return d.isValid() ? d.format('YYYY-MM-DD') : null
    }
    const s = String(raw).trim()
    // Wenn bereits korrekt: direkt zurück
    if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return s

    const d = dayjs(s)
    return d.isValid() ? d.format('YYYY-MM-DD') : null
}
async function checkCollisions() {
    if ((startTime.value && startDate.value && endTime.value && endDate.value) || (allDayEvent.value && startDate.value && endDate.value)) {
        const startFull = formatDate(startDate.value, startTime.value ?? '00:00')
        const endFull = formatDate(endDate.value, endTime.value ?? '23:59')
        try {
            const { data } = await axios.post('/collision/room', { params: { start: startFull, end: endFull, currentEventId: props.event?.id ?? null } })
            roomCollisionArray.value = data
        } catch { /* ignore */ }
    }
}
function checkYear(date) {
    return parseInt(String(date).split('-')[0]) > 1900
}
function getNextHourString(timeString) {
    const h = Number(timeString.slice(0, 2)) + 1
    const m = timeString.slice(3, 5)
    return `${h < 10 ? '0' + h : h}:${m}`
}
function validateStartBeforeEndTime() {
    error.value = null
    if (startDate.value && endDate.value && startTime.value && endTime.value) {
        // optional server-side validation hook
    }
}

// Liste der Schnell-Intervalle (anpassbar)


// Endzeit/Datum aus Start + Minuten setzen
function applyQuickDuration(minutes) {
    if (!startDate.value || allDayEvent.value) return
    if (!startTime.value) return

    const startDT = dayjs(`${startDate.value}T${startTime.value}`)
    const endDT = startDT.add(minutes, 'minute')

    endDate.value = endDT.format('YYYY-MM-DD')
    endTime.value  = endDT.format('HH:mm')

    // Kennzeichnen: automatisch gesetzt (manuelle Änderungen sollen danach Vorrang haben)
    endAutoFilled.value = true

    validateStartBeforeEndTime()
    checkCollisions()
}

function shiftEndByStartDelta(type) {
    // Nur bei bestehenden Terminen (oldStart-Werte vorhanden)
    if (!oldStartDate.value || !endDate.value) return

    if (type === 'date') {
        const oldStart = dayjs(oldStartDate.value)
        const newStart = dayjs(startDate.value)
        if (!oldStart.isValid() || !newStart.isValid()) return

        const diffDays = newStart.diff(oldStart, 'day')
        if (diffDays === 0) return

        if (shiftPeriodOnStartDateChange.value) {
            // Zeitraum mitverschieben: Dauer beibehalten
            endDate.value = dayjs(endDate.value).add(diffDays, 'day').format('YYYY-MM-DD')
        } else if (dayjs(endDate.value).isBefore(newStart, 'day')) {
            // Standard: Enddatum bleibt stehen. Läge es vor dem neuen Start, auf den Start clampen.
            endDate.value = newStart.format('YYYY-MM-DD')
        }
        // oldStartDate immer nachführen — auch ohne Verschiebung, sonst rechnet
        // die Uhrzeit-Verschiebung ('time') später mit tagesgroßen Deltas.
        oldStartDate.value = startDate.value
    } else if (type === 'time') {
        if (!oldStartTime.value || !startTime.value || !endTime.value || !endDate.value) return

        const oldDT = dayjs(`${oldStartDate.value}T${oldStartTime.value}`)
        const newDT = dayjs(`${startDate.value}T${startTime.value}`)
        if (!oldDT.isValid() || !newDT.isValid()) return

        const diffMinutes = newDT.diff(oldDT, 'minute')
        if (diffMinutes === 0) return

        const newEnd = dayjs(`${endDate.value}T${endTime.value}`).add(diffMinutes, 'minute')
        endDate.value = newEnd.format('YYYY-MM-DD')
        endTime.value = newEnd.format('HH:mm')
        oldStartTime.value = startTime.value
        oldStartDate.value = startDate.value
    }
}

function updateTimes() {
    // Start-/Enddatum immer in das gleiche Format bringen wie bei manueller Eingabe
    if (startDate.value) startDate.value = normalizeDateInput(startDate.value)
    if (endDate.value) endDate.value = normalizeDateInput(endDate.value)

    // Enddatum soll beim Anlegen automatisch dem Startdatum folgen,
    // solange das Ende nicht manuell geändert wurde.
    if (startDate.value && (!endDate.value || endAutoFilled.value)) {
        endDate.value = startDate.value
        endAutoFilled.value = true
    }

    if (startDate.value && startTime.value) {
        // Endzeit nur setzen, wenn leer ODER zuletzt auto-gefüllt
        if (!endTime.value || endAutoFilled.value) {
            setEndFromDuration()
        }
    }

    validateStartBeforeEndTime()
    checkCollisions()
}

function checkChanges() {
    if (selectedRoom.value?.temporary) {
        const startFull = formatDate(startDate.value, startTime.value)
        const endFull = formatDate(endDate.value, endTime.value)
        const start = dayjs(startFull)
        const end = dayjs(endFull)
        const roomStart = dayjs(selectedRoom.value.start_date)
        const roomEnd = dayjs(selectedRoom.value.end_date)

        if (start.isBefore(roomStart)) {
            helpTextLengthRoom.value = $t('The start time is before the availability of this temporary room.')
            submit.value = false
        } else if (end.isAfter(roomEnd)) {
            helpTextLengthRoom.value = $t('The end time is after the availability of this temporary room.')
            submit.value = false
        } else {
            helpTextLengthRoom.value = ''
            submit.value = true
        }
    }
    updateTimes()
}

function toggleAccept(type) {
    if (type === 'option') {
        if (optionAccept.value) {
            accept.value = false
            optionString.value = bookingOptions[0].name
        }
    } else {
        if (accept.value) {
            optionAccept.value = false
            optionString.value = null
        }
    }
}
function chooseProject(project) {
    selectedProject.value = project
    projectName.value = ''
}
function onRoomSelected(room) {
    selectedRoom.value = room
    checkChanges()
}
function errorMsg(field) {
    const e = error.value
    if (!e) return ''
    const raw = Array.isArray(e?.[field]) ? e[field].join('.<br> ') : e?.[field]
    return raw || ''
}
function payload() {
    return {
        title: title.value,
        eventName: eventName.value,
        eventStatusId: selectedEventStatus.value?.id,
        start: formatDate(startDate.value, allDayEvent.value ? '00:00' : startTime.value),
        end: formatDate(endDate.value, allDayEvent.value ? '23:59' : endTime.value),
        admissionTime: admissionTime.value || null,
        roomId: selectedRoom.value?.id,
        description: description.value,
        isOption: isOption.value,
        eventNameMandatory: !!selectedEventType.value?.individual_name,
        projectId: showProjectInfo.value ? selectedProject.value?.id : null,
        projectName: showProjectInfo.value ? (creatingProject.value ? projectName.value : '') : '',
        eventTypeId: selectedEventType.value?.id,
        projectIdMandatory: !!(selectedEventType.value?.project_mandatory && !creatingProject.value),
        creatingProject: showProjectInfo.value ? creatingProject.value : false,
        declinedRoomId: declinedRoomId.value,
        is_series: !!series.value,
        seriesScope: isSeriesEvent.value ? seriesScope.value : 'single',
        // Turnus/Ende nur mitschicken, wenn sie gelten: beim Anlegen, beim Umwandeln in eine Serie
        // oder bei Reichweite über den Termin hinaus. Sonst bleibt die Serie unangetastet.
        ...((series.value && (!isSeriesEvent.value || seriesScope.value !== 'single'))
            ? seriesDefinitionPayload()
            : { seriesFrequency: null, seriesEndDate: null, seriesWeekdays: null, seriesOccurrenceCount: null }),
        adminComment: adminComment.value,
        optionString: optionAccept.value ? optionString.value : null,
        accept: accept.value,
        optionAccept: optionAccept.value,
        allDay: allDayEvent.value,
        usedInBulkComponent: props.usedInBulkComponent,
        showProjectPeriodInCalendar: props.calendarProjectPeriod,
        event_properties: (event_properties ?? []).filter(p => p.checked).map(p => p.id),
        isPlanning: props.event ? props.event.isPlanning : props.isPlanning,
    }
}
async function updateOrCreateEvent(isOptionParam = false) {
    isOption.value = isOptionParam

    if (allDayEvent.value) {
        startTime.value = '00:00'
        endTime.value = '23:59'
    }
    if (accept.value === false && optionAccept.value === false) {
        isOption.value = true
    }

    await doSaveEvent()
}
// --- Confirm-Dialog: Terminverschiebung löst Einzeltag-Projektzuordnungen auf ---
const showAssignmentImpactModal = ref(false)
const assignmentImpactList = ref([])
let assignmentImpactConfirmed = false
// Der Precheck fragt nur nach, wenn sich am initialTiming-Snapshot (Deklaration
// weiter oben bei loadFullDescription) wirklich etwas geaendert hat — sonst kam
// der Dialog auch beim reinen Bearbeiten der Beschreibung.
function timingSnapshot() {
    return {
        start: formatDate(startDate.value, allDayEvent.value ? '00:00' : startTime.value),
        end: formatDate(endDate.value, allDayEvent.value ? '23:59' : endTime.value),
        projectId: showProjectInfo.value ? (selectedProject.value?.id ?? null) : null,
    }
}

async function checkProjectAssignmentImpact(data) {
    if (!props.event?.id || assignmentImpactConfirmed) return true

    // Zuordnungen fallen nur durch verschobene Zeiten oder einen Projektwechsel
    // heraus. Bleibt beides gleich, gibt es nichts zu bestaetigen.
    const before = initialTiming.value
    if (
        before &&
        before.start === data.start &&
        before.end === data.end &&
        before.projectId === (data.projectId ?? null)
    ) {
        return true
    }

    try {
        const { data: response } = await axios.get(
            route('events.project-assignment-impact', { event: props.event.id }),
            { params: { start_time: data.start, end_time: data.end, project_id: data.projectId } },
        )

        if ((response.affected ?? []).length) {
            assignmentImpactList.value = response.affected
            showAssignmentImpactModal.value = true
            return false
        }
    } catch (e) {
        // Der Precheck darf das Speichern nie blockieren
    }

    return true
}

async function confirmAssignmentImpactAndSave() {
    assignmentImpactConfirmed = true
    showAssignmentImpactModal.value = false
    await doSaveEvent()
}

async function doSaveEvent() {
    // Alle Speicherwege laufen hier durch: ohne geladenen Volltext wuerde
    // payload() eine leere Beschreibung schicken und den Text loeschen.
    if (!await ensureDescriptionLoaded()) {
        error.value = { description: $t('The description could not be loaded. Please reopen the event before saving.') }
        return
    }

    isLoading.value = true
    const data = payload()

    if (!(await checkSeriesImpact())) {
        isLoading.value = false
        return
    }
    if (!(await checkProjectAssignmentImpact(data))) {
        isLoading.value = false
        return
    }
    seriesImpactConfirmed = false
    // Bestätigung gilt nur für genau diesen Speichervorgang — sonst überspringt
    // ein späteres erneutes Verschieben im selben Modal den Precheck stumm
    assignmentImpactConfirmed = false

    if (!props.requiresAxiosRequests &&
        (props.usedInBulkComponent ||
            (page.props.auth.user.calendar_settings.time_period_project_id === selectedProject.value?.id &&
                props.calendarProjectPeriod))) {
        if (!props.event?.id) {
            router.post(route('events.store'), data, {
                preserveScroll: true,
                preserveState: (pg) => typeof pg?.component === 'undefined',
                onSuccess: () => {
                    handleSuccessfulSave()
                },
                onError: (resp) => {
                    isLoading.value = false
                    error.value = resp
                },
            })
        } else {
            router.put(route('events.update', { event: props.event.id }), data, {
                preserveScroll: true,
                preserveState: (pg) => typeof pg?.component === 'undefined',
                onSuccess: () => {
                    handleSuccessfulSave()
                },
                onError: (resp) => {
                    isLoading.value = false
                    error.value = resp
                },
            })
        }
        return
    }

    try {
        if (!props.event?.id) await axios.post('/events', data)
        else await axios.put(`/events/${props.event.id}`, data)
        handleSuccessfulSave()
    } catch (e) {
        isLoading.value = false
        error.value = e?.response?.data?.errors ?? e
    }
}

function handleSuccessfulSave() {
    isLoading.value = false

    if (isOption.value && !canCreateDirect.value) {
        requestSubmitted.value = true
        return
    }

    closeModal(true)
}
async function afterConfirm(confirmed) {
    if (!confirmed) {
        deleteComponentVisible.value = false
        return
    }
    isLoading.value = true
    try {
        await axios.delete(`/events/${props.event.id}`)
        isLoading.value = false
        closeModal(true)
    } catch (e) {
        isLoading.value = false
        error.value = e?.response?.data?.errors ?? e
    }
}

// neue lokale UI-States/Methoden
const showProjectPicker = ref(false)

// beim Öffnen initial steuern (falls Projekt schon gesetzt, Picker ausblenden)
const _origOpenModal = openModal
openModal = function () {
    _origOpenModal()
    showProjectPicker.value = !selectedProject.value?.id
}

const projectSearchRef = ref(null)
const projectNameRef = ref(null)

// Umschalten: Bestehend
function switchToExisting() {
    creatingProject.value = false
    nextTick(() => projectSearchRef.value?.focus?.())
}

// Umschalten: Neu
function switchToNew() {
    // NICHTS automatisch bestätigen – einfach editierbares Feld lassen
    creatingProject.value = true
    selectedProject.value = null // wichtig: kein bestehendes Projekt aktiv
    nextTick(() => projectNameRef.value?.focus?.())
}

// Entfernen
function removeProject() {
    selectedProject.value = null
    // bleib in bestehendem Modus? Dann zeig Suche; oder wechsel optional zu "Neu":
    // creatingProject.value = true
}

watch([startDate, startTime, allDayEvent], () => {
    // Endzeit ggf. automatisch anpassen, wenn Start sich ändert
    if (!endTime.value || endAutoFilled.value) updateTimes()
})

// Auswahl aus Suche
function chooseProjectFromPicker(project) {
    chooseProject(project)      // deine vorhandene Methode (setzt selectedProject)
    // nach Auswahl → Chip anzeigen (Suche verschwindet automatisch)
}

// Innere Dialoge: solange einer offen ist (und kurz danach) darf der äußere Dialog nicht auf „close“ reagieren
const innerDialogOpen = computed(() =>
    showSeriesDeleteModal.value || showSeriesDetachModal.value || showSeriesImpactModal.value
    || showAssignmentImpactModal.value || showDiscardConfirmation.value
)
let suppressOuterCloseUntil = 0
watch(innerDialogOpen, (open) => {
    if (!open) suppressOuterCloseUntil = Date.now() + 500
})

// Vorschau beim Anlegen nachziehen (steht am Ende, weil die Quellen erst weiter oben deklariert werden)
watch(
    [series, startDate, startTime, endDate, endTime, allDayEvent, selectedFrequency, seriesEndDate, seriesEndMode, seriesOccurrenceCount, seriesWeekdays, () => selectedRoom.value?.id],
    () => scheduleSeriesPreview(),
    { deep: true }
)
</script>
