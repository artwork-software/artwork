<template>
    <form @submit.prevent="submit" @keydown.enter="preventEnterSubmit" class="mx-auto w-full px-4 md:px-6">
        <!-- Page Title -->
        <header v-if="!props.issueOfMaterial?.id" class="mb-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-info-surface to-accent-100 px-3 py-1 ring-1 ring-inset ring-info-border">
                        <span class="inline-block size-1.5 rounded-full bg-info"></span>
                        <span class="text-[11px] font-semibold text-info tracking-wide">{{ $t('Intern Material Issue') }}</span>
                    </div>
                    <h1 class="mt-2 text-xl md:text-2xl font-bold tracking-tight text-text">
                        {{ $t('Create material issue') }}
                    </h1>
                    <p class="text-sm text-text-subtle">
                        {{ $t('Here you can capture the basic information for the material issue. Fields marked with * are required.') }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                  <span class="inline-flex items-center rounded-full bg-accent-50 px-2.5 py-1 text-xs font-medium text-accent-700 ring-1 ring-inset ring-accent-200">
                    {{ internMaterialIssue.articles?.length || 0 }} {{ $t('articles') }}
                  </span>
                  <span class="inline-flex items-center rounded-full bg-success-surface px-2.5 py-1 text-xs font-medium text-success ring-1 ring-inset ring-success-border">
                    {{ internMaterialIssue.files?.length || 0 }} {{ $t('files') }}
                  </span>
                </div>
            </div>
        </header>
        <!-- Konflikt-Leiste: Zeigt Überbuchungen im Zeitraum an -->
        <section v-if="hasConflicts" class="mb-6 rounded-2xl border border-danger-border bg-danger-surface/70 p-4 ring-1 ring-inset ring-danger-border">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="grid size-8 place-items-center rounded-full bg-danger text-white text-xs font-bold">!</div>
                    <div>
                        <h3 class="text-sm font-semibold text-danger/90">{{ $t('Conflicts regarding availability') }}</h3>
                        <p class="text-xs text-danger">
                            {{ $t('There are') }} <strong>{{ conflicts.length }}</strong> {{ $t('Items with a quantity higher than available in the selected period.') }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="inline-flex items-center rounded-lg bg-danger px-3 py-1.5 text-xs font-semibold text-white hover:bg-danger" @click="fixAllConflicts">
                        {{ $t('Automatically adjust quantities') }}
                    </button>
                </div>
            </div>


            <!-- Konfliktliste (kompakt) -->
            <div class="mt-3 grid gap-2 md:grid-cols-2">
                <div v-for="c in conflicts" :key="c.id" class="flex items-center justify-between rounded-lg bg-white px-3 py-2 ring-1 ring-border-subtle">
                    <div class="min-w-0 pr-2">
                        <p class="truncate text-xs font-medium text-text">{{ c.name }}</p>
                        <p class="text-[11px] text-text-subtle">{{ $t('Requested') }}: {{ c.wanted }} • {{ $t('Available') }}: {{ c.available }}</p>
                    </div>
                    <button v-if="internMaterialIssue.articles[c.index]?.images?.length > 0" type="button" class="text-xs font-medium text-accent-700 underline shrink-0" @click="openLightbox(0, internMaterialIssue.articles[c.index]?.images || [])">{{ $t('Show images') }}</button>
                </div>
            </div>
        </section>

        <div class="space-y-8">
            <!-- Stammdaten -->
            <section class="rounded-2xl border border-border-subtle bg-white shadow-sm">
                <div class="border-b border-border-subtle bg-gradient-to-r from-info-surface via-info-surface to-transparent px-6 py-4 rounded-t-2xl">
                    <h2 class="text-base font-semibold text-text flex items-center gap-2">
                        <span class="inline-block size-2 rounded-full bg-info"></span>
                        {{ $t('Base data') }}
                    </h2>
                    <p class="text-xs text-text-subtle">{{ $t('Capture name, time period and responsibilities.') }}</p>
                </div>
                <!-- Project -->
                <div class="px-6 pt-2">
                    <template v-if="!selectedProject">
                        <ProjectSearch @project-selected="addProject" :get-first-last-event="true" show-recent-projects :label="$t('Project assignment (optional)')" />
                        <LastedProjects :limit="10" @select="addProjectFromRecent" />
                    </template>
                    <div v-else class="mt-1">
                        <span class="text-xs font-medium text-text-subtle">{{ $t('Selected project') }}</span>
                        <div class="mt-1 flex items-center justify-between rounded-xl border border-accent-200 bg-accent-50 px-3 py-1">
                            <a
                                :href="route('projects.tab', {project: selectedProject.id, projectTab: props.projectTabId})"
                                class="text-sm font-semibold text-accent-700 hover:underline"
                            >{{ selectedProject.name }}</a>
                            <button type="button" class="text-xs font-medium text-accent-700 underline" @click="removeProject">
                                {{ $t('Remove assignment') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="p-6 grid grid-cols-1 gap-6 md:grid-cols-3">
                    <!-- Name -->
                    <BaseInput id="name" v-model="internMaterialIssue.name" :label="$t('Material issue name') + ' *'" class="md:col-span-1" />



                    <!-- Room -->
                    <div class="md:col-span-1">
                        <RoomSearch v-if="!selectedRoom" @room-selected="addRoom" :label="$t('Room assignment (optional)')" />
                        <div v-else class="">
                            <span class="text-xs font-medium text-text-subtle">{{ $t('Selected room') }}</span>
                            <div class="mt-1 flex items-center justify-between rounded-xl border border-accent-200 bg-accent-50 px-3 py-1">
                                <div class="text-sm font-semibold text-accent-700">{{ selectedRoom?.name }}</div>
                                <button type="button" class="text-xs font-medium text-accent-700 underline" @click="selectedRoom = null">
                                    {{ $t('Remove assignment') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Projekträume: Schnellauswahl (aus Events/Schichten des Projekts) -->
                    <div v-if="selectedProject && (projectRooms.length || projectRoomsLoading)" class="md:col-span-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium text-text-subtle">{{ $t('Project rooms') }}</span>
                            <component :is="IconLoader" v-if="projectRoomsLoading" class="h-3.5 w-3.5 animate-spin text-text-subtle" stroke-width="1.5" />
                        </div>
                        <p v-if="projectRooms.length" class="text-[11px] text-text-subtle">
                            {{ $t('Click a room to set the period to that room’s span in the project.') }}
                        </p>
                        <div v-if="projectRooms.length" class="mt-1.5 flex flex-wrap gap-2">
                            <button
                                v-for="room in projectRooms"
                                :key="room.id"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition"
                                :class="selectedRoom?.id === room.id ? 'border-accent-200 bg-accent-50/50 text-accent-700 ring-1 ring-inset ring-accent-200'
                                    : 'border-border-subtle bg-white text-text-muted hover:border-accent-200 hover:bg-accent-50'"
                                @click="assignRoomFromProject(room, { force: true })"
                            >
                                <component :is="IconHome" class="size-3.5 shrink-0" />
                                <span class="truncate max-w-[160px]">{{ room.name }}</span>
                                <span class="text-[10px] tabular-nums text-text-subtle">{{ formatRoomPeriod(room) }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Zeitraum -->
                    <div class="md:col-span-3">
                        <div class="rounded-xl border border-border-subtle/80 bg-surface-sunken p-4">
                            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                                <BaseInput id="start_date" v-model="internMaterialIssue.start_date" :label="$t('Start date') + ' *'" type="date" />
                                <BaseInput id="start_time" v-model="internMaterialIssue.start_time" :label="$t('Start time')" type="time" />
                                <BaseInput id="end_date" v-model="internMaterialIssue.end_date" :label="$t('End date') + ' *'" type="date" />
                                <BaseInput id="end_time" v-model="internMaterialIssue.end_time" :label="$t('End time')" type="time" />
                            </div>
                            <p v-if="isEndDateBeforeStartDate" class="mt-2 text-xs font-medium text-danger flex items-center gap-1">
                                <span class="inline-block size-1.5 rounded-full bg-danger"></span>
                                {{ $t('The end date cannot be before the start date') }}
                            </p>
                        </div>
                    </div>

                    <!-- Notes -->
                    <BaseTextarea id="notes" v-model="internMaterialIssue.notes" :label="$t('Notes')" class="md:col-span-3" />

                    <!-- Verantwortliche -->
                    <div class="md:col-span-3">
                        <UserSearch @user-selected="addResponsibleUser" :label="$t('Responsible Users')" />

                        <div v-if="selectedResponsibleUsers.length" class="mt-3 flex flex-wrap gap-3">
                            <div v-for="(user, index) in selectedResponsibleUsers" :key="index" class="group flex items-center gap-2 rounded-full border border-border-subtle bg-white pr-2 shadow-sm">
                                <img class="size-8 rounded-full object-cover" :src="user.profile_photo_url" alt="" />
                                <span class="text-sm font-medium text-text">{{ user?.full_name ?? user.name }}</span>
                                <button type="button" class="text-text-subtle hover:text-danger" @click="removeUserFromIssue(index)">
                                    <IconX class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Artikel: Suche & Auswahl -->
            <section class="space-y-6">
                <!-- Artikelsuche (links) + Auswahl (rechts) 50/50 nebeneinander -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 items-start">
                <!-- Gefundene Artikel (linke Spalte) -->
                <div class="rounded-2xl border border-border-subtle bg-white shadow-sm flex flex-col lg:sticky lg:top-0 lg:max-h-[calc(100vh-11rem)]">
                    <div class="sticky top-0 z-10 border-b border-border-subtle bg-white/90 backdrop-blur px-5 py-3 rounded-t-2xl space-y-3">
                        <div class="flex items-center w-full gap-x-3">
                            <BaseInput
                                id="articleSearchFilter"
                                v-model="articleSearchFilter"
                                class="w-full"
                                :label="$t('Search article, (sub)category...')"
                                :placeholder="$t('Search article, (sub)category...')"
                            />
                            <ToolTipComponent @click="showSelectMaterialSetModal = true" :icon="IconPackages" :tooltip-text="$t('Select material set')" icon-size="size-7" tooltip-width="w-fit whitespace-nowrap" position="top" />
                            <ToolTipComponent @click="showCopyIssueModal = true" :icon="IconCopy" :tooltip-text="$t('Copy material from another material issue')" icon-size="size-7" tooltip-width="w-fit whitespace-nowrap" position="top" />
                            <InventoryFunctionBarFilter @close="reloadArticlesWithNewFilter" />
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="font-semibold flex items-center gap-2">
                                <span class="inline-block size-2 rounded-full bg-accent-600"></span>
                                {{ $t('Found Articles') }}
                                <span v-if="filteredArticles && filteredArticles.length" class="text-sm font-normal text-text-subtle">
                                    · {{ filteredArticles.length }} {{ filteredArticles.length === 1 ? $t('article found') : $t('articles found') }}
                                </span>
                            </h3>
                            <span class="inline-flex items-center rounded-full bg-accent-50 px-2.5 py-1 text-xs font-medium text-accent-700 ring-1 ring-inset ring-accent-200 shrink-0">
                                {{ internMaterialIssue.articles?.length || 0 }} {{ $t('selected') }}
                            </span>
                        </div>
                    </div>

                    <div ref="scrollContainer" class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                        <div class="grid grid-cols-1 gap-3 2xl:grid-cols-2">
                            <div v-for="article in filteredArticles" :key="article.id" class="rounded-xl border p-3 shadow-sm transition" :class="selectedQuantityById[article.id] ? 'border-success-border bg-success-surface/50 ring-1 ring-success-border' : 'border-border-subtle bg-surface-sunken/60 hover:bg-surface-sunken hover:border-accent-200'">
                                <button type="button" class="w-full text-left" @click="addArticleToIssue(article)">
                                    <div class="flex items-start gap-3">
                                        <img v-if="article?.images?.[0]?.image" :src="'/storage/' + article.images[0].image" :alt="article.images[0].alt || ''" class="h-12 w-12 rounded-lg border border-border-subtle object-cover" @error="(e) => e.target.src = usePage().props.big_logo" />
                                        <div class="min-w-0 w-full">
                                            <div class="font-medium truncate flex items-center gap-1.5">
                                                <component :is="IconCircleCheck" v-if="selectedQuantityById[article.id]" class="h-4 w-4 shrink-0 text-success" />
                                                <span class="truncate min-w-0">{{ article.name }}</span>
                                                <span v-if="selectedQuantityById[article.id]" class="ml-auto shrink-0 rounded-full bg-success-surface px-1.5 py-0.5 text-[10px] font-semibold text-success" :title="$t('selected')">×{{ selectedQuantityById[article.id] }}</span>
                                            </div>
                                            <div class="text-xs text-text-subtle line-clamp-2" v-if="article.description">{{ article.description }}</div>
                                            <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px]">
                                                <template v-for="(status, i) in article.status_values" :key="i">
                                                    <div v-if="status.name === 'Ready for use' || status.name === 'Einsatzbereit'" class="inline-flex items-center gap-1 rounded-md border px-1.5 py-0.5" :style="{ borderColor: status.color, backgroundColor: status.color + '15' }" :title="status.name">
                                                        <span class="inline-block size-1.5 rounded-full" :style="{ backgroundColor: status.color }"></span>
                                                        <span class="tabular-nums">{{ status.name }}</span>
                                                        <span class="tabular-nums">{{ readyForUseCount(article) }}</span>
                                                    </div>
                                                </template>

                                                <!-- Period availability bubble -->
                                                <div v-if="internMaterialIssue.start_date && internMaterialIssue.end_date" class="inline-flex items-center gap-1 rounded-md border px-1.5 py-0.5 border-accent-200 bg-accent-50" :title="$t('Available in period')">
                                                    <span class="inline-block size-1.5 rounded-full bg-accent-600"></span>
                                                    <span class="text-accent-700 font-medium">{{ $t('in period') }}</span>
                                                    <span v-if="!article.periodAvailabilityLoading" class="tabular-nums text-accent-700" :class="{ 'text-success': (article.periodAvailability?.available ?? 0) > 0,
                                                        'text-danger': (article.periodAvailability?.available ?? 0) === 0
                                                    }">{{ article.periodAvailability?.available ?? 0 }}</span>
                                                    <span v-else class="inline-block h-3 w-3 animate-spin rounded-full border border-accent-600 border-t-transparent"></span>
                                                </div>

                                                <span class="ml-auto text-text-subtle">{{ $t('Category') }}: {{ article.category.name }}<span v-if="article.sub_category"> • {{ $t('Subcategory') }}: {{ article.sub_category.name }}</span></span>
                                            </div>
                                        </div>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- Empty state -->
                        <div v-if="!filteredArticles.length && !loadingMore" class="py-10 text-center text-sm text-text-subtle">
                            {{ $t('No articles found') }}
                        </div>

                        <!-- Infinite-scroll sentinel + status -->
                        <div ref="loadMoreSentinel" class="h-px"></div>
                        <div v-if="loadingMore" class="flex justify-center py-4">
                            <span class="inline-block h-5 w-5 animate-spin rounded-full border-2 border-accent-600 border-t-transparent"></span>
                        </div>
                        <div v-else-if="!hasMoreArticles && filteredArticles.length" class="pt-3 text-center text-xs text-text-subtle">
                            {{ $t('All articles loaded') }}
                        </div>
                    </div>
                </div>

                    <!-- Ausgewählte Artikel (rechte Spalte) -->
                    <div class="rounded-2xl border border-border-subtle bg-white shadow-sm flex flex-col lg:sticky lg:top-0 lg:max-h-[calc(100vh-11rem)]">
                        <div class="border-b border-border-subtle px-6 py-4 rounded-t-2xl flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="text-base font-semibold text-text flex items-center gap-2">
                                    <span class="inline-block size-2 rounded-full bg-accent-600"></span>
                                    {{ $t('Selected Articles') }}
                                </h3>
                                <p class="text-xs text-text-subtle">{{ $t('Here you can see the items you have selected for the material issue. Adjust the quantity or remove items.') }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <div class="text-lg font-bold tabular-nums leading-none text-text">{{ internMaterialIssue.articles?.length || 0 }}</div>
                                <div class="text-[11px] text-text-subtle">{{ $t('selected') }}</div>
                            </div>
                        </div>

                        <div class="min-h-0 flex-1 overflow-y-auto p-5">
                            <div v-if="usageError" class="mb-4 flex items-start justify-between gap-3 rounded-lg border border-warning-border bg-warning-surface px-3 py-2 text-xs text-warning">
                                <span>{{ $t(usageError) }}</span>
                                <button type="button" class="shrink-0 font-medium text-warning hover:text-warning" @click="usageError = null">
                                    <IconX class="h-4 w-4" />
                                </button>
                            </div>
                            <div v-if="internMaterialIssue.articles.length" class="space-y-6">
                                <!-- Loop through categories -->
                                <div v-for="(subcategories, categoryName) in groupedSelectedArticles" :key="categoryName" class="space-y-4">
                                    <!-- Category heading -->
                                    <div class="border-b border-border-subtle/60 pb-2">
                                        <h4 class="text-sm font-semibold text-text flex items-center gap-2">
                                            <span class="inline-block size-2 rounded-full bg-accent-600"></span>
                                            {{ categoryName }}
                                        </h4>
                                    </div>

                                    <!-- Loop through subcategories within this category -->
                                    <div v-for="(articles, subcategoryName) in subcategories" :key="`${categoryName}-${subcategoryName}`" class="space-y-3">
                                        <!-- Subcategory heading -->
                                        <div class="ml-4">
                                            <h5 class="text-xs font-medium text-text-muted flex items-center gap-1.5">
                                                <span class="inline-block size-1.5 rounded-full bg-border-strong"></span>
                                                {{ subcategoryName }}
                                            </h5>
                                        </div>

                                        <!-- Articles in this subcategory -->
                                        <div class="ml-8 divide-y divide-border-subtle/80">
                                            <div v-for="article in articles" :key="article.originalIndex" :data-article-row="article.originalIndex" class="flex flex-col gap-3 py-3 2xl:flex-row 2xl:items-center 2xl:justify-between">
                                                <div class="flex w-full items-start gap-4">
                                                    <!-- Single preview with zoom overlay -->
                                                    <div v-if="article?.images?.length" class="shrink-0">
                                                        <div class="group relative h-16 w-16 cursor-zoom-in overflow-hidden rounded-lg border border-border-subtle shadow-sm" @click="openLightbox(0, article.images)">
                                                            <img :src="'/storage/' + article.images[0].image" :alt="article.images[0].alt || ''" class="block h-full w-full object-cover" @error="(e) => e.target.src = usePage().props.big_logo" />
                                                            <div class="pointer-events-none absolute inset-0 grid place-items-center bg-black/0 transition group-hover:bg-black/30">
                                                                <component :is="IconWindowMaximize" class="h-4 w-4 text-white opacity-0 transition group-hover:opacity-100" />
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="min-w-0">
                                                        <h4 class="text-sm font-semibold text-text flex items-center gap-1">
                                                            {{ article.name }}
                                                            <component :is="IconListDetails" class="h-4 w-4 text-text-subtle hover:text-text-muted" @click="openArticleDetailModal(article)" />
                                                        </h4>
                                                        <div class="mt-0.5 text-xs text-text-muted flex items-center gap-1">
                                                            {{ props.planningDate ? $t('Available stock on date') : $t('Available stock in period') }}:
                                                            <span v-if="!article.availableStockRequestIsLoading" class="tabular-nums inline-flex items-center gap-1" :class="{ 'text-success': (article.availableStock?.available ?? 0) > 0,
                              'text-danger': (article.availableStock?.available ?? 0) === 0
                            }">{{ article.availableStock?.available ?? 0 }}
                              <button type="button" class="underline" @click="getArticleDataForUsage(article)">
                                <component :is="IconInfoCircle" class="h-3.5 w-3.5" stroke-width="1.5" />
                              </button>
                            </span>
                                                            <span v-else class="inline-flex items-center gap-1 text-text-subtle">
                              {{ $t('Fetching') }}
                              <component :is="IconLoader" class="h-3.5 w-3.5 animate-spin text-text-subtle" stroke-width="1.5" />
                            </span>
                                                        </div>
                                                        <div v-if="article.quantity > (article.availableStock?.available ?? 0) && (props.planningDate || (internMaterialIssue.start_date && internMaterialIssue.end_date))" class="mt-1 inline-flex items-center gap-1.5 rounded-md bg-danger-surface px-2 py-1 text-[11px] font-medium text-danger ring-1 ring-inset ring-danger-border">
                                                            <span>{{ $t('Overbooking') }}</span>
                                                            <button type="button" class="underline" @click="getArticleDataForUsage(article)">{{ $t('Details') }}</button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-4 md:gap-6">
                                                    <div class="w-28">
                                                        <BaseInput :id="'article-quantity-' + article.originalIndex" type="number" v-model="internMaterialIssue.articles[article.originalIndex].quantity" :label="$t('Menge')" :input-classes="article.quantity > (article.availableStock?.available ?? 0) && internMaterialIssue.start_date && internMaterialIssue.end_date ? '!border-danger !bg-danger-surface' : ''" />
                                                    </div>
                                                    <button type="button" class="rounded-md p-2 text-text-subtle hover:bg-surface-sunken hover:text-danger" @click="removeArticle(article.originalIndex)">
                                                        <component :is="IconTrash" class="h-5 w-5" stroke-width="1.5" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else>
                                <BaseAlertComponent :message="$t('No items selected')" type="info" class="text-center" />
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /50-50 Raster -->

                <!-- Sekundär: Sonderartikel + Dateien (volle Breite) -->
                <div class="space-y-6">
                    <!-- Sonderartikel -->
                    <div class="rounded-2xl border border-border-subtle bg-white shadow-sm">
                        <div class="flex items-center justify-between gap-3 border-b border-border-subtle px-6 py-4 rounded-t-2xl">
                            <div>
                                <h3 class="text-base font-semibold text-text flex items-center gap-2">
                                    <span class="inline-block size-2 rounded-full bg-special-violet"></span>
                                    {{ $t('Sonderartikel') }}
                                </h3>
                                <p class="text-xs text-text-subtle">{{ $t('Hier können Sie Artikel erfassen, die nicht im System gelistet sind.') }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="flex items-center gap-2 text-xs text-text-muted">
                                    <input type="checkbox" v-model="internMaterialIssue.special_items_done" class="h-4 w-4 rounded border-border text-special-violet focus:ring-accent-600" />
                                    <span>{{ $t('Special items done') }}</span>
                                </label>
                                <button type="button" class="inline-flex items-center gap-1 rounded-lg bg-special-violet px-3 py-1.5 text-xs font-semibold text-white hover:bg-special-violet" @click="addSpecialItem">
                                    <component :is="IconCirclePlus" class="h-3.5 w-3.5" />
                                    {{ $t('Sonderartikel hinzufügen') }}
                                </button>
                            </div>
                        </div>

                        <div class="max-h-[26rem] overflow-y-auto p-6">
                            <div class="divide-y divide-dashed divide-border-subtle">
                                <div v-for="(article, index) in internMaterialIssue.special_items" :key="index" class="py-3">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-9">
                                        <BaseInput :id="'special-article-name-' + index" type="text" v-model="article.name" :label="$t('Artikelname')" class="md:col-span-6" />
                                        <BaseInput :id="'special-article-quantity' + index" type="number" v-model="article.quantity" :label="$t('Menge')" class="md:col-span-2" />
                                        <div class="flex items-center justify-center">
                                            <button type="button" class="rounded-md p-2 text-text-subtle hover:bg-surface-sunken hover:text-danger" @click="removeSpecialArticle(index)">
                                                <component :is="IconTrash" class="h-5 w-5" stroke-width="1.5" />
                                            </button>
                                        </div>
                                        <BaseTextarea :id="'special-article-description-' + index" v-model="article.description" rows="1" :label="$t('Beschreibung')" class="md:col-span-9" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dateien -->
                    <div class="rounded-2xl border border-border-subtle bg-white shadow-sm">
                        <div class="border-b border-border-subtle px-6 py-4 rounded-t-2xl">
                            <h3 class="text-base font-semibold text-text flex items-center gap-2">
                                <span class="inline-block size-2 rounded-full bg-success"></span>
                                {{ $t('Dateien zur Materialausgabe') }}
                            </h3>
                            <p class="text-xs text-text-subtle">{{ $t('Hier können Sie Dateien zur Materialausgabe hochladen und verwalten.') }}</p>
                        </div>

                        <div class="p-6 grid grid-cols-1 gap-6 md:grid-cols-2 items-stretch">
                            <!-- Dropzone -->
                            <div>
                                <button @click="$refs.internMaterialIssueFiles.click()" type="button" class="relative block w-full max-h-56 min-h-56 rounded-2xl border-2 border-dashed border-success-border/70 p-10 text-center hover:border-success-border focus:outline-hidden focus:ring-2 focus:ring-success focus:ring-offset-2">
                                    <component :is="IconFile" class="mx-auto size-12 text-success" stroke-width="1" />
                                    <span class="mt-2 block text-sm font-semibold text-success">{{ $t('Datei auswählen') }}</span>
                                    <input ref="internMaterialIssueFiles" id="file" type="file" class="hidden" multiple @change="upload" />
                                </button>
                            </div>

                            <!-- File Lists -->
                            <div class="rounded-xl border border-border-subtle bg-surface-sunken p-4 max-h-56 min-h-56 overflow-y-auto">
                                <!-- Bestehende Dateien -->
                                <div v-if="props.issueOfMaterial?.files?.length" class="space-y-2">
                                    <div v-for="(file, index) in props.issueOfMaterial.files" :key="'existing-' + index" class="flex items-center gap-3 rounded-lg border border-border-subtle bg-white px-3 py-2">
                                        <!-- Vorschau für Bilder UND PDFs, Klick öffnet den Viewer
                                             (Abnahme MAT-03 Ref. 1.16, gleiches Muster wie Projekt-Dokumente) -->
                                        <FilePreview
                                            v-if="isImageFile(file.original_name) || isPdfFileName(file.original_name)"
                                            :src="'/storage/' + file.file_path"
                                            :name="file.original_name"
                                            :type="isPdfFileName(file.original_name) ? 'pdf' : 'image'"
                                            size="sm"
                                            class="shrink-0"
                                            @open="openAttachmentPreview(file)"
                                        />
                                        <div class="min-w-0 flex-1">
                                            <a :href="'/storage/' + file.file_path" target="_blank" download class="truncate text-sm font-medium text-accent-700 hover:underline">
                                                {{ file.original_name }}
                                            </a>
                                        </div>
                                        <button type="button" class="rounded-md p-1.5 text-text-subtle hover:bg-surface-sunken hover:text-danger" @click="removeFile(file.id)">
                                            <component :is="IconTrash" class="h-4 w-4" stroke-width="1.5" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Neu ausgewählte Dateien -->
                                <div v-if="internMaterialIssue.files?.length" class="mt-3 space-y-2">
                                    <div v-for="(file, index) in internMaterialIssue.files" :key="'new-' + index" class="flex items-center gap-3 rounded-lg border border-border-subtle bg-white px-3 py-2">
                                        <!-- Thumbnail für neue Bilddateien -->
                                        <div v-if="isImageFile(file.name || file.original_name) && filePreviewUrl(file)" class="shrink-0">
                                            <div class="overflow-hidden rounded border border-border-subtle shadow-sm" style="width: 40px; height: 40px;">
                                                <img :src="filePreviewUrl(file)" :alt="file.name || file.original_name" class="block h-full w-full object-cover" @error="(e) => e.target.src = usePage().props.big_logo" />
                                            </div>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <h4 class="truncate text-sm font-medium">{{ file.name ?? file.original_name }}</h4>
                                            <p v-if="file.size" class="text-[11px] text-text-subtle">{{ (file.size / 1024 / 1024).toFixed(2) }} MB</p>
                                        </div>
                                        <button type="button" class="rounded-md p-1.5 text-text-subtle hover:bg-surface-sunken hover:text-danger" @click="internMaterialIssue.files.splice(index, 1)">
                                            <component :is="IconTrash" class="h-4 w-4" stroke-width="1.5" />
                                        </button>
                                    </div>
                                </div>

                                <div v-if="!props.issueOfMaterial?.files?.length && !internMaterialIssue.files?.length" class="grid h-full place-items-center text-xs text-text-subtle">
                                    {{ $t('Keine Dateien ausgewählt') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Sticky Action Bar -->
        <div class="sticky bottom-0 z-40 mt-8 -mx-4 md:-mx-6 bg-gradient-to-t from-white via-white/80 to-transparent pt-4">
            <div class="mx-auto w-full px-4 md:px-6">
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-border-subtle bg-white/90 p-3 backdrop-blur shadow-sm">
                    <div class="text-xs text-text-muted">
                        {{ $t('Selected') }}: <span class="font-medium">{{ internMaterialIssue.articles?.length || 0 }}</span> {{ $t('articles') }} • {{ $t('Files') }}: <span class="font-medium">{{ internMaterialIssue.files?.length || 0 }}</span>
                    </div>
                    <FormButton :text="issueOfMaterial?.id ? $t('Aktualisieren') : $t('Speichern')" :disabled="internMaterialIssue.processing || !internMaterialIssue.start_date || !internMaterialIssue.end_date || !internMaterialIssue.name || isEndDateBeforeStartDate" type="submit" />
                </div>
                <div v-if="Object.keys(internMaterialIssue.errors).length > 0" class="mt-2 rounded-lg border border-danger-border bg-danger-surface px-3 py-2">
                    <p v-for="(error, field) in internMaterialIssue.errors" :key="field" class="text-xs text-danger">
                        {{ error }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Single global Galleria for lightbox -->
        <Galleria
            v-if="displayCustom"
            v-model:activeIndex="activeIndex"
            v-model:visible="displayCustom"
            :value="lightboxImages"
            :responsiveOptions="responsiveOptions"
            :numVisible="7"
            :pt="{ mask: { onClick: onMaskClick } }"
            containerStyle="max-width: 850px"
            :circular="true"
            :fullScreen="true"
            :showItemNavigators="true"
            :showThumbnails="true"
        >
            <template #item="slotProps">
                <img :src="'/storage/' + slotProps.item.image" :alt="slotProps.item.alt || ''" style="width: 100%; max-height: 75vh; object-fit: contain; display: block" @error="(e) => e.target.src = usePage().props.big_logo" />
            </template>
            <template #thumbnail="slotProps">
                <img :src="'/storage/' + slotProps.item.image" :alt="slotProps.item.alt || ''" class="w-20 max-w-20" style="display: block" @error="(e) => e.target.src = usePage().props.big_logo" />
            </template>
        </Galleria>
    </form>

    <ArticleSearchFilterModal v-if="showArticleFilterModal" @close="showArticleFilterModal = false"
        @add-article="addArticleToIssue" />

    <SelectMaterialSetModal v-if="showSelectMaterialSetModal" @close="showSelectMaterialSetModal = false"
        @add-material-set="addMaterialSetToIssue" />

    <ApplyMaterialSetConfirmModal
        v-if="materialSetOverlaps.length"
        :overlaps="materialSetOverlaps"
        @close="closeApplyMaterialSetConfirm"
        @apply="confirmApplyMaterialSet"
    />

    <CopyFromMaterialIssueModal v-if="showCopyIssueModal" :exclude-issue-id="internMaterialIssue.id"
        @close="showCopyIssueModal = false" @copy-issue="addArticlesFromCopiedIssue" />

    <ArticleDetailModal :article="articleForDetailModal" v-if="articleForDetailModal" @close="articleForDetailModal = null" :show-button-for-edit-and-delete="false" />

    <ArticleUsageModal :details-for-modal="articleForUsageModal" v-if="articleForUsageModal" @close="articleForUsageModal = null; editingArticleQuantity = null" :editing-issue-id="internMaterialIssue.id" :editing-article-quantity="editingArticleQuantity" />

    <!-- Bild-/PDF-Viewer für Dateianhänge (Abnahme MAT-03 Ref. 1.16) -->
    <FileViewerModal
        v-if="attachmentPreview"
        :src="attachmentPreview.src"
        :name="attachmentPreview.name"
        :type="attachmentPreview.type"
        @close="attachmentPreview = null"
    />
</template>

<script setup lang="ts">
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import RoomSearch from "@/Components/SearchBars/RoomSearch.vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import ArticleSearchFilterModal from "@/Pages/IssueOfMaterial/Components/ArticleSearchFilterModal.vue";
import ApplyMaterialSetConfirmModal from "@/Pages/IssueOfMaterial/Components/ApplyMaterialSetConfirmModal.vue";
import ProjectSearch from "@/Components/SearchBars/ProjectSearch.vue";
import LastedProjects from "@/Artwork/LastedProjects.vue";
import FilePreview from "@/Artwork/Files/FilePreview.vue";
import FileViewerModal from "@/Artwork/Files/FileViewerModal.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import {router, useForm, usePage} from "@inertiajs/vue3";
import {computed, inject, nextTick, onBeforeUnmount, onMounted, provide, ref, watch} from "vue";
import debounce from "lodash.debounce";
import axios from "axios";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import SelectMaterialSetModal from "@/Pages/IssueOfMaterial/Components/SelectMaterialSetModal.vue";
import CopyFromMaterialIssueModal from "@/Pages/IssueOfMaterial/Components/CopyFromMaterialIssueModal.vue";
import InventoryFunctionBarFilter from "@/Artwork/Filter/InventoryFunctionBarFilter.vue";
import ArticleDetailModal from "@/Pages/Inventory/Components/Article/Modals/ArticleDetailModal.vue";
import ArticleUsageModal from "@/Pages/Inventory/Components/Planning/ArticleUsageModal.vue";
import Galleria from "primevue/galleria";
import {IconCircleCheck, IconCirclePlus, IconCopy, IconFile, IconHome, IconInfoCircle, IconListDetails, IconLoader, IconPackages, IconTrash, IconWindowMaximize, IconX} from "@tabler/icons-vue";
import dayjs from "dayjs";

// Ensure time values are always in HH:mm format (strip seconds if present)
function normalizeTime(value) {
    if (!value) return value;
    if (typeof value !== 'string') return value;
    let t = value.trim();
    // If a datetime string like "YYYY-MM-DD HH:mm:ss" is provided, extract time
    if (t.includes(' ')) {
        const parts = t.split(' ');
        t = parts[parts.length - 1];
    }
    // If matches HH:mm:ss -> return HH:mm
    if (/^\d{2}:\d{2}:\d{2}$/.test(t)) {
        return t.slice(0, 5);
    }
    // If matches H:mm or HH:mm -> pad hour
    if (/^\d{1,2}:\d{2}$/.test(t)) {
        const [h, m] = t.split(':');
        return `${h.padStart(2, '0')}:${m}`;
    }
    // Fallback: try to coerce by taking first 5 chars if they look like time
    if (t.length >= 5 && /^\d{2}:\d{2}/.test(t)) {
        return t.slice(0, 5);
    }
    return t;
}

const props = defineProps({
    issueOfMaterial: {
        type: Object,
        required: false,
        default: () => ({
            id: null,
            name: "",
            project_id: null,
            start_date: "",
            start_time: "00:00",
            end_date: "",
            end_time: "23:59",
            room_id: null,
            notes: "",
            responsible_user_ids: [],
            special_items_done: false,
            files: [],
            articles: [],
            special_items: [],
            responsible_users: [],
        }),
    },
    project: {
        type: Object,
        required: false,
        default: null,
    },
    isInProjectComponent: {
        type: Boolean,
        required: false,
        default: false,
    },
    loadArticleFormBasket: {
        type: Boolean,
        required: false,
        default: false,
    },
    firstEvent: {
        type: Object,
        required: false,
        default: null,
    },
    lastEvent: {
        type: Object,
        required: false,
        default: null,
    },
    planningDate: {
        type: String,
        required: false,
        default: null,
    },
    projectTabId: {
        type: Number,
        required: false,
        default: 1,
    },
});

// Inject materialSets from parent and provide to children
const materialSets = inject('materialSets', []);
provide('materialSets', materialSets);

const internMaterialIssue = useForm({
    id: props.issueOfMaterial?.id || null,
    name: props.issueOfMaterial?.name || "",
    project_id: props.issueOfMaterial?.project_id || null,
    project: props.issueOfMaterial?.project || null,
    start_date: props.issueOfMaterial?.start_date || props.firstEvent?.formatted_dates?.start_without_time || "",
    start_time: normalizeTime(props.issueOfMaterial?.start_time) || props.firstEvent?.formatted_dates?.startTime || "00:00",
    end_date: props.issueOfMaterial?.end_date || props.lastEvent?.formatted_dates?.end_without_time || "",
    end_time: normalizeTime(props.issueOfMaterial?.end_time) || props.lastEvent?.formatted_dates?.endTime || "23:59",
    room_id: props.issueOfMaterial?.room_id || null,
    notes: props.issueOfMaterial?.notes || "",
    responsible_user_ids: props.issueOfMaterial?.responsible_user_ids || [],
    special_items_done: props.issueOfMaterial?.special_items_done || false,
    files: [], // New files to upload
    existing_files: props.issueOfMaterial?.files || [], // Keep track of existing files
    articles: (props.issueOfMaterial?.articles || []).map((article) => ({
        id: article.id,
        name: article.name,
        description: article.description,
        quantity: article.pivot?.quantity || article.quantity || 1, // Usage quantity for material issue
        total_quantity: article.quantity, // Original total stock quantity for detail modal
        is_detailed_quantity: article.is_detailed_quantity,
        availableStock: 0,
        availableStockRequestIsLoading: true,
        detailed_article_quantities: article.detailed_article_quantities || [],
        category: article.category || null,
        subCategory: article.sub_category || null,
        // Ensure consistent property naming - map sub_category to subCategory for consistency
        sub_category: article.sub_category || null,
        images: article.images || [],
        properties: article.properties || [],
        room: article.room || null,
        manufacturer: article.manufacturer || null,
        status_values: article.status_values || [],
    })), // [{ id, quantity }]
    special_items: props.issueOfMaterial?.special_items || [], // [{...}]
    isInProjectComponent: props.isInProjectComponent || false
});

// Keep start_time and end_time normalized if user agent or prefill introduces seconds
watch(() => internMaterialIssue.start_time, (val) => {
    const nt = normalizeTime(val);
    if (nt !== val) internMaterialIssue.start_time = nt || "";
});
watch(() => internMaterialIssue.end_time, (val) => {
    const nt = normalizeTime(val);
    if (nt !== val) internMaterialIssue.end_time = nt || "";
});

const selectedProject = ref((props.project?.id ? props.project : null) || props.issueOfMaterial?.project || null);
const selectedRoom = ref(props.issueOfMaterial?.room || null);
// Rooms the linked project actually occupies (via its events/shifts), each with a
// room-specific period. Offered as quick-select chips below the room field.
const projectRooms = ref([]);
const projectRoomsLoading = ref(false);
const selectedResponsibleUsers = ref(
    props.issueOfMaterial?.responsible_users || []
);
const showArticleFilterModal = ref(false);
const showSelectMaterialSetModal = ref(false);
const showCopyIssueModal = ref(false);
// Artikel Pagination State
const articles = ref([]);
const loadingMore = ref(false);
const isFetchingArticles = ref(false);
const scrollContainer = ref(null);
const loadMoreSentinel = ref(null);
let articleObserver = null;
const articleSearchFilter = ref("");
const articleForDetailModal = ref(null);
const articleForUsageModal = ref(null);
const editingArticleQuantity = ref(null);
// Translation key of the last usage-lookup error (shown inline in the selection panel).
const usageError = ref(null);
const hasMoreArticles = ref(true);
const paginationPage = ref(1);
const baskets = ref([]);
const isLoadingBaskets = ref(true);
const currentBasket = ref(1);
const isEndDateBeforeStartDate = computed(() => {
    if (!internMaterialIssue.start_date || !internMaterialIssue.end_date) {
        return false;
    }

    const startDate = new Date(internMaterialIssue.start_date);
    const endDate = new Date(internMaterialIssue.end_date);

    return endDate < startDate;
});

const conflicts = computed(() => {
    const arts = (internMaterialIssue?.articles ?? [])
    const hasPeriod = !!(internMaterialIssue?.start_date && internMaterialIssue?.end_date)
    if (!hasPeriod) return []
    return arts
        .map((a, index) => {
            const available = a?.availableStock?.available ?? 0
            const wanted = Number(a?.quantity ?? 0)
            return wanted > available
                ? { index, id: a?.id ?? index, name: a?.name ?? ('#' + index), wanted, available }
                : null
        })
        .filter(Boolean)
})

const hasConflicts = computed(() => conflicts.value.length > 0)

// Filtered articles based on search input - now returns all articles since filtering is done server-side
const filteredArticles = computed(() => {
    return articles.value;
})

// Lookup of already-selected article id -> chosen quantity, so the "Found Articles"
// cards can show a "selected · ×N" marker without scanning the array per card.
const selectedQuantityById = computed(() => {
    const map = {};
    for (const a of internMaterialIssue.articles) {
        if (a?.id != null) {
            map[a.id] = Number(a.quantity ?? 0);
        }
    }
    return map;
})

// Server-side search: just re-run the unified loader from page 1. The current
// search term is always read inside fetchArticles(), so search and pagination
// can never drift apart.
const searchArticlesFromServer = debounce(() => {
    fetchArticles({ reset: true });
}, 300);

// Watch for search input changes
watch(articleSearchFilter, () => {
    searchArticlesFromServer();
})

// Group selected articles by category and subcategory
const groupedSelectedArticles = computed(() => {
    if (!internMaterialIssue.articles?.length) {
        return {};
    }

    const grouped = {};

    internMaterialIssue.articles.forEach((article, index) => {
        // Handle both camelCase (subCategory) and snake_case (sub_category) property names
        const categoryName = article.category?.name || 'Ohne Kategorie';
        const subcategoryName = (article.subCategory?.name || article.sub_category?.name) || 'Ohne Unterkategorie';

        // Initialize category if it doesn't exist
        if (!grouped[categoryName]) {
            grouped[categoryName] = {};
        }

        // Initialize subcategory if it doesn't exist
        if (!grouped[categoryName][subcategoryName]) {
            grouped[categoryName][subcategoryName] = [];
        }

        // Add article with its original index for operations like remove
        grouped[categoryName][subcategoryName].push({
            ...article,
            originalIndex: index
        });
    });

    return grouped;
})

// Setzt alle Konflikt-Mengen auf „max. verfügbar“
function fixAllConflicts () {
    conflicts.value.forEach(c => {
        const art = internMaterialIssue.articles[c.index]
        if (!art) return
        const avail = Number(c.available ?? 0)
        art.quantity = Math.max(0, avail)
    })
}

const activeIndex = ref(0);
const responsiveOptions = ref([
    {
        breakpoint: '1024px',
        numVisible: 5
    },
    {
        breakpoint: '768px',
        numVisible: 3
    },
    {
        breakpoint: '560px',
        numVisible: 1
    }
]);
const displayCustom = ref(false);

const imageClick = (index) => {
    activeIndex.value = index;
    displayCustom.value = true;
};

const lightboxImages = ref([])

function openLightbox(startIndex, images) {
    lightboxImages.value = images || []
    // @ts-ignore
    activeIndex.value = startIndex || 0
    // @ts-ignore
    displayCustom.value = true
}



const onMaskClick = (e) => {
    if (e.target === e.currentTarget) {
        displayCustom.value = false
    }
}


// Optional: Typ grob skizzieren (anpassbar)
type ProjectLike = {
    id: number;
    name?: string;
    is_group?: boolean
    first_event?: { formatted_dates?: { start_without_time?: string; startTime?: string } };
    last_event?: { formatted_dates?: { end_without_time?: string; endTime?: string } };
    firstEventInProject?: { start_time?: string; [key: string]: any };
    lastEventInProject?: { end_time?: string; [key: string]: any };
    firstEventStart?: string;
    lastEventEnd?: string;
};

const DEFAULT_START = '00:00';
const DEFAULT_END   = '23:59';

const isEmpty = (v: unknown) => v === '' || v === null || v === undefined;

// Merkt sich, welche Werte automatisch aus der Projekt-/Raumauswahl übernommen wurden.
// Beim Wechsel der Projektzuordnung werden NUR diese wieder freigegeben, damit der
// Zeitraum dem neuen Projekt folgt (Abnahme Ref. 3.36) — manuell geänderte Werte
// bleiben stehen.
const autoFilled = ref<{ name: string | null; startDate: string | null; endDate: string | null }>({
    name: null,
    startDate: null,
    endDate: null,
});
// true, wenn der aktuell gewählte Raum über die Projektraum-Chips gesetzt wurde
// (dann gehört er zum alten Projekt und wird beim Wechsel mit entfernt)
const roomFromProjectChip = ref(false);

const releaseAutoFilledValues = () => {
    if (autoFilled.value.name !== null && internMaterialIssue.name === autoFilled.value.name) {
        internMaterialIssue.name = '';
    }
    if (autoFilled.value.startDate !== null && internMaterialIssue.start_date === autoFilled.value.startDate) {
        internMaterialIssue.start_date = '';
    }
    if (autoFilled.value.endDate !== null && internMaterialIssue.end_date === autoFilled.value.endDate) {
        internMaterialIssue.end_date = '';
    }
    autoFilled.value = { name: null, startDate: null, endDate: null };
};

// Auswahl aus der "Zuletzt geöffnete Projekte"-Kachelliste: die localStorage-Einträge
// tragen keine first_event/last_event-Daten — für die Zeitraum-Vorbelegung wie in der
// ProjectSearch-Schnellauswahl über die reguläre Projektsuche nachladen.
const addProjectFromRecent = async (project: ProjectLike) => {
    let toAdd = project;
    if (project?.name) {
        try {
            const {data} = await axios.post(route('project.scoutSearch'), {
                project_search: project.name,
                get_first_last_event: true,
                wantsJson: true,
            });
            const match = Array.isArray(data)
                ? data.find((p: any) => Number(p.id) === Number(project.id))
                : null;
            if (match) toAdd = match;
        } catch {
            /* Fallback: Rohdaten aus der Schnellauswahl übernehmen */
        }
    }
    addProject(toAdd);
};

const addProject = (project?: ProjectLike) => {
    // Auswahl setzen (oder nullen)
    selectedProject.value = project ?? null;
    if (!project) return;

    // Wechsel der Projektzuordnung: automatisch übernommene Werte des vorherigen
    // Projekts freigeben, damit die Vorbelegung unten neu greift
    releaseAutoFilledValues();
    if (roomFromProjectChip.value) {
        selectedRoom.value = null;
        roomFromProjectChip.value = false;
    }

    // Name nur setzen, wenn leer
    if (isEmpty(internMaterialIssue.name) && project.name) {
        internMaterialIssue.name = project.name;
        autoFilled.value.name = project.name;
    }

    // Extract start date/time from either format
    let startDate: string | null = null;
    let startTime: string | null = null;

    // Try first_event format (from project search)
    const fdStart = project.first_event?.formatted_dates ;
    if (fdStart?.start_without_time) {
        startDate = fdStart.start_without_time;
        startTime = fdStart.startTime ?? DEFAULT_START;
    }
    // Try firstEventInProject format (from project context)
    else if (project.firstEventInProject?.start_time) {
        const eventStartTime = project.firstEventInProject.start_time;
        // Extract date and time from datetime string (e.g., "2024-01-15 09:00:00")
        if (typeof eventStartTime === 'string' && eventStartTime.includes(' ')) {
            const [date, time] = eventStartTime.split(' ');
            startDate = date;
            startTime = time.substring(0, 5); // Extract HH:mm from HH:mm:ss
        }
    }

    // Set start date/time only if empty
    if (isEmpty(internMaterialIssue.start_date) && startDate) {
        internMaterialIssue.start_date = startDate;
        internMaterialIssue.start_time = startTime ?? DEFAULT_START;
        autoFilled.value.startDate = startDate;
    }

    // Extract end date/time from either format
    let endDate: string | null = null;
    let endTime: string | null = null;

    // Try last_event format (from project search)
    const fdEnd = project.last_event?.formatted_dates;
    if (fdEnd?.end_without_time) {
        endDate = fdEnd.end_without_time;
        endTime = fdEnd.endTime ?? DEFAULT_END;
    }
    // Try lastEventInProject format (from project context)
    else if (project.lastEventInProject?.end_time) {
        const eventEndTime = project.lastEventInProject.end_time;
        // Extract date and time from datetime string (e.g., "2024-01-15 18:00:00")
        if (typeof eventEndTime === 'string' && eventEndTime.includes(' ')) {
            const [date, time] = eventEndTime.split(' ');
            endDate = date;
            endTime = time.substring(0, 5); // Extract HH:mm from HH:mm:ss
        }
    }




    // Set end date/time only if empty
    if (isEmpty(internMaterialIssue.end_date) && endDate) {
        internMaterialIssue.end_date = endDate;
        internMaterialIssue.end_time = endTime ?? DEFAULT_END;
        autoFilled.value.endDate = endDate;
    }

    // if start empty check if project has project.firstEventStart ("14.11.2025") format it and set it
    if (isEmpty(internMaterialIssue.start_date) && project.firstEventStart) {
        const iso = dotDateToIso(project.firstEventStart);
        if (iso) {
            internMaterialIssue.start_date = iso;
            internMaterialIssue.start_time = DEFAULT_START;
            autoFilled.value.startDate = iso;
        }
    }

// if end empty check if project has project.lastEventEnd ("20.11.2025")  format it and set it
    if (isEmpty(internMaterialIssue.end_date) && project.lastEventEnd) {
        const iso = dotDateToIso(project.lastEventEnd);
        if (iso) {
            internMaterialIssue.end_date = iso;
            internMaterialIssue.end_time = DEFAULT_END;
            autoFilled.value.endDate = iso;
        }
    }

    // Load the project's rooms for the quick-select chips. Auto-select a single
    // room only when creating a new issue (never when editing an existing one).
    if (project.id) {
        fetchProjectRooms(project.id, { allowAutoSelect: !props.issueOfMaterial?.id });
    }
};

const dotDateToIso = (value: string | null | undefined): string | null => {
    if (!value) return null;
    const trimmed = value.trim();
    const match = /^(\d{2})\.(\d{2})\.(\d{4})$/.exec(trimmed);
    if (!match) return null;

    const [, dd, mm, yyyy] = match;
    return `${yyyy}-${mm}-${dd}`;
};


const addRoom = (room) => {
    selectedRoom.value = room;
    roomFromProjectChip.value = false;
};

const isPeriodEmpty = () => !internMaterialIssue.start_date && !internMaterialIssue.end_date;

// Assign a project room and (optionally) narrow the period to that room's span.
// - force = true  → explicit chip click: always set the period to the room span.
// - force = false → automatic single-room selection: only set when no period yet
//   (never clobbers a period the user/project already provided).
const assignRoomFromProject = (room, { force = false } = {}) => {
    if (!room) return;
    selectedRoom.value = { id: room.id, name: room.name };
    roomFromProjectChip.value = true;

    if (force || isPeriodEmpty()) {
        internMaterialIssue.start_date = room.start_date || '';
        internMaterialIssue.start_time = normalizeTime(room.start_time) || '00:00';
        internMaterialIssue.end_date = room.end_date || '';
        internMaterialIssue.end_time = normalizeTime(room.end_time) || '23:59';
        // Auch dieser Zeitraum stammt aus der Projektzuordnung — beim Projektwechsel freigeben
        autoFilled.value.startDate = internMaterialIssue.start_date || null;
        autoFilled.value.endDate = internMaterialIssue.end_date || null;
    }
};

// Short "DD.MM.–DD.MM." period label for a room chip.
const formatRoomPeriod = (room) => {
    const short = (d) => {
        if (!d) return '';
        const [, m, day] = d.split('-');
        return `${day}.${m}.`;
    };
    const s = short(room.start_date);
    const e = short(room.end_date);
    if (!s && !e) return '';
    return s === e ? s : `${s}–${e}`;
};

const fetchProjectRooms = async (projectId, { allowAutoSelect = false } = {}) => {
    if (!projectId) {
        projectRooms.value = [];
        return;
    }
    projectRoomsLoading.value = true;
    try {
        const { data } = await axios.get(route('projects.rooms-with-event-periods', { project: projectId }));
        projectRooms.value = Array.isArray(data) ? data : [];

        // Exactly one project room → auto-select it (still removable/changeable).
        if (allowAutoSelect && projectRooms.value.length === 1 && !selectedRoom.value) {
            assignRoomFromProject(projectRooms.value[0]);
        }
    } catch (e) {
        console.error('Fehler beim Laden der Projekträume:', e);
        projectRooms.value = [];
    } finally {
        projectRoomsLoading.value = false;
    }
};

const removeProject = () => {
    selectedProject.value = null;
    projectRooms.value = [];
};

const addResponsibleUser = (user) => {
    // Check if the user is already in the array
    const userExists = selectedResponsibleUsers.value.find(
        (u) => u.id === user.id
    );
    if (!userExists) {
        selectedResponsibleUsers.value.push(user);
    }
};

const removeUserFromIssue = (index) => {
    selectedResponsibleUsers.value.splice(index, 1);
};

const addSpecialItem = () => {
    internMaterialIssue.special_items.push({
        id: null,
        name: "",
        quantity: 1,
    });
};

const getArticleDataForUsage = async (article) => {
    const startDate = props.planningDate || internMaterialIssue.start_date;
    const endDate = props.planningDate || internMaterialIssue.end_date;
    if (!article?.id || !startDate || !endDate) {
        return;
    }

    usageError.value = null;

    // The usage detail endpoint iterates day by day and caps the range at one
    // year. Catch this client-side so the user gets a clear hint instead of a
    // silent 422.
    const dayDiff = Math.abs((new Date(endDate).getTime() - new Date(startDate).getTime()) / 86400000);
    if (dayDiff > 366) {
        usageError.value = 'The period is too large for the usage details (max. 1 year). Please narrow the period.';
        return;
    }

    article.availableStockRequestIsLoading = true;
    try {
        const response = await axios.get(route('inventory.articles.usage'), {
            params: {
                article_id: article.id,
                start_date: startDate,
                end_date: endDate,
                // exclude the issue currently being edited from the availability math
                issue_id: internMaterialIssue?.id || null,
                type: 'intern',
            }
        });
        // Die Nutzungsdaten werden im Modal angezeigt
        articleForUsageModal.value = response.data.data;
        editingArticleQuantity.value = article.quantity;
    } catch (error) {
        usageError.value = 'Usage details could not be loaded.';
        console.error('Fehler beim Abrufen der Artikel-Nutzungsdaten:', error);
    } finally {
        article.availableStockRequestIsLoading = false;
    }
};

// Set-Anwendung mit Rückfrage: Überschneiden sich Set-Artikel mit bereits gewählten,
// entscheidet der Nutzer im Modal, ob die Set-Mengen addiert werden.
const pendingMaterialSet = ref(null);
const materialSetOverlaps = ref([]);

const addMaterialSetToIssue = (materialSet) => {
    const overlaps = (materialSet.items ?? [])
        .filter((item) => item.article?.id)
        .filter((item) => internMaterialIssue.articles.some((a) => a.id === item.article.id))
        .map((item) => ({
            id: item.article.id,
            name: item.article.name,
            existingQuantity: Number(internMaterialIssue.articles.find((a) => a.id === item.article.id)?.quantity ?? 0),
            setQuantity: item.quantity || 1,
        }));

    if (overlaps.length > 0) {
        pendingMaterialSet.value = materialSet;
        materialSetOverlaps.value = overlaps;
        return;
    }

    applyMaterialSetToIssue(materialSet, false);
};

const applyMaterialSetToIssue = (materialSet, additive) => {
    // Process each item in the material set
    for (const item of materialSet.items) {
        const article = item.article;
        // Items gelöschter Artikel haben keine article-Relation mehr
        if (!article?.id) continue;
        const existingArticleIndex = internMaterialIssue.articles.findIndex(
            (a) => a.id === article.id
        );

        if (existingArticleIndex === -1) {
            // Article doesn't exist, add it with the quantity from the material set
            internMaterialIssue.articles.push({
                id: article.id,
                name: article.name,
                description: article.description,
                quantity: item.quantity || 1, // Default quantity to 1 if not specified
                availableStock: 0,
                availableStockRequestIsLoading: true,
            });
        } else {
            const existingArticle =
                internMaterialIssue.articles[existingArticleIndex];
            if (additive) {
                existingArticle.quantity =
                    Number(existingArticle.quantity ?? 0) + (item.quantity || 1);
            }
            if (!existingArticle.availableStock) {
                existingArticle.availableStock = 0;
                existingArticle.availableStockRequestIsLoading = true;
            }
        }
    }

    // after add check the available stock
    checkAvailableStock();
};

const confirmApplyMaterialSet = (additive) => {
    if (pendingMaterialSet.value) {
        applyMaterialSetToIssue(pendingMaterialSet.value, additive);
    }
    closeApplyMaterialSetConfirm();
};

const closeApplyMaterialSetConfirm = () => {
    pendingMaterialSet.value = null;
    materialSetOverlaps.value = [];
};

// Übernimmt alle Artikel einer anderen Materialausgabe mit deren Mengen
// (pivot.quantity). Bereits ausgewählte Artikel werden aufsummiert.
const addArticlesFromCopiedIssue = (issue) => {
    for (const article of issue?.articles ?? []) {
        if (!article?.id) continue;
        const copiedQuantity = Number(article.pivot?.quantity ?? 1);
        const existingArticleIndex = internMaterialIssue.articles.findIndex(
            (a) => a.id === article.id
        );

        if (existingArticleIndex === -1) {
            internMaterialIssue.articles.push({
                id: article.id,
                name: article.name,
                description: article.description,
                quantity: copiedQuantity,
                total_quantity: article.quantity,
                is_detailed_quantity: article.is_detailed_quantity,
                availableStock: 0,
                availableStockRequestIsLoading: true,
                detailed_article_quantities: article.detailed_article_quantities || [],
                category: article.category || null,
                subCategory: article.subCategory || article.sub_category || null,
                sub_category: article.sub_category || article.subCategory || null,
                images: article.images || [],
                properties: article.properties || [],
                room: article.room || null,
                manufacturer: article.manufacturer || null,
                status_values: article.status_values || [],
            });
        } else {
            const existingArticle = internMaterialIssue.articles[existingArticleIndex];
            existingArticle.quantity = Number(existingArticle.quantity ?? 0) + copiedQuantity;
        }
    }

    checkAvailableStock();
};

const removeSpecialArticle = (index) => {
    internMaterialIssue.special_items.splice(index, 1);
};

const addArticleToIssue = (article) => {
    // Check if the article is already in the array
    const existingArticleIndex = internMaterialIssue.articles.findIndex(
        (a) => a.id === article.id
    );
    if (existingArticleIndex === -1) {
        // Article doesn't exist, add it
        internMaterialIssue.articles.push({
            id: article.id,
            name: article.name,
            description: article.description,
            quantity: 1, // Usage quantity for material issue
            total_quantity: article.quantity, // Original total stock quantity for detail modal
            is_detailed_quantity: article.is_detailed_quantity,
            availableStock: 0,
            availableStockRequestIsLoading: true,
            detailed_article_quantities:
                article.detailed_article_quantities || [],
            category: article.category || null,
            subCategory: article.sub_category || null,
            // Ensure consistent property naming - maintain both for compatibility
            sub_category: article.sub_category || null,
            images: article.images || [],
            properties: article.properties || [],
            room: article.room || null,
            manufacturer: article.manufacturer || null,
            status_values: article.status_values || [],
        });
    } else {
        // Article already selected → clicking it again increases the quantity by 1.
        const existingArticle =
            internMaterialIssue.articles[existingArticleIndex];
        existingArticle.quantity = Number(existingArticle.quantity ?? 0) + 1;
        if (!existingArticle.availableStock) {
            existingArticle.availableStock = 0;
            existingArticle.availableStockRequestIsLoading = true;
        }
    }

    // after add check the available stock
    checkAvailableStock();
};

// Single source of truth for loading the "Found Articles" list. ALWAYS sends the
// current search term + date window, so paging (reset:false) stays filtered just
// like the initial search (reset:true). This fixes the old bug where "load more"
// dropped the search term and returned unrelated articles.
const fetchArticles = async ({ reset = false } = {}) => {
    if (isFetchingArticles.value) return;
    if (!reset && !hasMoreArticles.value) return;

    isFetchingArticles.value = true;
    loadingMore.value = true;

    if (reset) {
        paginationPage.value = 1;
        hasMoreArticles.value = true;
    }

    const search = (articleSearchFilter.value || '').trim();

    try {
        const response = await axios.get(route('inventory.articles.api', {
            page: paginationPage.value,
            search: search || undefined,
            start_date: internMaterialIssue.start_date || undefined,
            end_date: internMaterialIssue.end_date || undefined,
        }));

        const paginator = response.data.articles;
        const incoming = paginator.data || [];

        if (reset) {
            articles.value = incoming;
        } else {
            for (const article of incoming) {
                if (!articles.value.some((a) => a.id === article.id)) {
                    articles.value.push(article);
                }
            }
        }

        hasMoreArticles.value = !!paginator.next_page_url;
        paginationPage.value = (paginator.current_page ?? paginationPage.value) + 1;

        // Check availability for the currently loaded articles
        await checkFoundArticlesAvailability();
    } catch (e) {
        console.error('Fehler beim Laden der Artikel:', e);
    }
    loadingMore.value = false;
    isFetchingArticles.value = false;
};

// Kept as thin wrappers so existing callers (date watcher, filter bar, onMounted)
// don't need to change.
const loadMoreArticles = () => fetchArticles({ reset: false });
const reloadArticlesWithNewFilter = () => fetchArticles({ reset: true });

const removeArticle = (index) => {
    internMaterialIssue.articles.splice(index, 1);
};
const emits = defineEmits(["close", "saved"]);

// Verhindert, dass Enter in einem Eingabefeld das Formular (und damit Speichern) auslöst.
// Textareas bleiben ausgenommen, damit Zeilenumbrüche weiterhin möglich sind.
const preventEnterSubmit = (event) => {
    if (event.target?.tagName !== 'TEXTAREA') {
        event.preventDefault();
    }
};

// Nutzdaten-Vergleich für die Verwerfen-Rückfrage im Wrapper-Modal (Abnahme Ref. 3.7):
// bewusst OHNE availableStock/availableStockRequestIsLoading — die schreiben die
// asynchronen Verfügbarkeits-Requests in die Form-Artikel, form.isDirty wäre damit
// immer true, ohne dass der User etwas geändert hat
const dirtyComparableState = () => JSON.stringify({
    name: internMaterialIssue.name,
    project: selectedProject.value?.id ?? null,
    start: [internMaterialIssue.start_date, internMaterialIssue.start_time],
    end: [internMaterialIssue.end_date, internMaterialIssue.end_time],
    room: selectedRoom.value?.id ?? null,
    notes: internMaterialIssue.notes,
    responsible: selectedResponsibleUsers.value.map((u) => u.id),
    specialItems: (internMaterialIssue.special_items ?? []).map((s) => [s.name, s.quantity]),
    specialItemsDone: internMaterialIssue.special_items_done,
    articles: (internMaterialIssue.articles ?? []).map((a) => [a.id, Number(a.quantity)]),
    newFiles: internMaterialIssue.files.length,
    existingFiles: (internMaterialIssue.existing_files ?? []).length,
});

let initialDirtySnapshot = '';
onMounted(() => {
    initialDirtySnapshot = dirtyComparableState();
});

defineExpose({
    submit: () => submit(),
    isDirty: () => dirtyComparableState() !== initialDirtySnapshot,
});

const submit = () => {
    // Ensure times are in HH:mm before submitting
    internMaterialIssue.start_time = normalizeTime(internMaterialIssue.start_time) || "";
    internMaterialIssue.end_time = normalizeTime(internMaterialIssue.end_time) || "";

    if (selectedProject.value) {
        internMaterialIssue.project_id = selectedProject.value.id;
    } else {
        internMaterialIssue.project_id = null;
    }

    if (selectedRoom.value) {
        internMaterialIssue.room_id = selectedRoom.value.id;
    } else {
        internMaterialIssue.room_id = null;
    }

    if (selectedResponsibleUsers.value.length > 0) {
        internMaterialIssue.responsible_user_ids =
            selectedResponsibleUsers.value.map((user) => user.id);
    } else {
        internMaterialIssue.responsible_user_ids = [];
    }

    // Create a list of existing file IDs to preserve them during update
    if (props.issueOfMaterial?.files) {
        internMaterialIssue.existing_file_ids = props.issueOfMaterial.files.map(
            (file) => file.id
        );
    }
    if (props.issueOfMaterial?.id) {
        // Use post instead of patch for better file upload handling
        internMaterialIssue._method = "PATCH";
        internMaterialIssue.post(
            route("issue-of-material.update", props.issueOfMaterial.id),
            {
                onSuccess: () => {
                    clearConsumedBasket();
                    emits("saved", {
                        issueId: props.issueOfMaterial.id,
                        updatedArticles: internMaterialIssue.articles.map(article => ({
                            id: article.id,
                            quantity: article.quantity
                        }))
                    });
                    emits("close");
                },
            }
        );
    } else {
        internMaterialIssue.post(route("issue-of-material.store"), {
            onSuccess: (response) => {
                clearConsumedBasket();
                emits("saved", {
                    issueId: response.props?.issueOfMaterial?.id || null,
                    updatedArticles: internMaterialIssue.articles.map(article => ({
                        id: article.id,
                        quantity: article.quantity
                    }))
                });
                emits("close");
            },
        });
    }
};

const checkAvailableStock = async () => {
    // Prefer the issue's own window so availability is computed over the booking's
    // real time span (e.g. 00:00–11:00), not the whole day. planningDate is only a
    // fallback for brand-new issues that have no dates yet.
    const startDate = internMaterialIssue.start_date || props.planningDate;
    const endDate = internMaterialIssue.end_date || props.planningDate;

    if (
        !startDate ||
        !endDate ||
        internMaterialIssue.articles.length === 0
    ) {
        console.log('Missing dates or no articles to check availability for.');
        return;
    }

    const ids = internMaterialIssue.articles.map((a) => a.id).filter(Boolean);

    // Ladezustand setzen
    for (const article of internMaterialIssue.articles) {
        article.availableStockRequestIsLoading = true;
        article.availableStock = null;
        article.overbooked = false;
    }

    // Uhrzeiten mitsenden, außer die Buchung umfasst den ganzen Tag (00:00–23:59).
    // So begrenzt z.B. eine Ausgabe von 00:00–11:00 die Verfügbarkeit nur in diesem
    // Fenster (Buchungen ab 12:00 zählen dann nicht dagegen).
    const startTime = internMaterialIssue.start_time;
    const endTime = internMaterialIssue.end_time;
    const hasExplicitTimes =
        !!startTime &&
        !!endTime &&
        !(startTime === "00:00" && endTime === "23:59");

    try {
        const payload = {
            article_ids: ids,
            type: 'intern',
            issue_id: internMaterialIssue?.id || null,
            start_date: startDate,
            end_date: endDate,
        };

        if (hasExplicitTimes) {
            payload.start_time = internMaterialIssue.start_time;
            payload.end_time = internMaterialIssue.end_time;
        }

        const response = await axios.post(
            route("inventory.articles.available-stock.batch"),
            payload
        );

        const resultMap = response.data.data;

        for (const article of internMaterialIssue.articles) {
            const stock = resultMap[article.id];

            article.availableStockRequestIsLoading = false;
            article.availableStock = stock;

            if (
                article.quantity &&
                stock &&
                stock.available < article.quantity
            ) {
                article.overbooked = true;
            }
        }
    } catch (error) {
        for (const article of internMaterialIssue.articles) {
            article.availableStockRequestIsLoading = false;
            article.availableStock = null;
            article.overbooked = false;
        }
    }
};


const checkFoundArticlesAvailability = async () => {
    if (
        !internMaterialIssue.start_date ||
        !internMaterialIssue.end_date ||
        !articles.value.length
    ) {
        return;
    }

    const ids = articles.value.map((a) => a.id).filter(Boolean);

    // Set loading for all found articles
    for (const article of articles.value) {
        article.periodAvailabilityLoading = true;
        article.periodAvailability = null;
    }

    // Times count unless the booking spans the whole day (00:00–23:59).
    const hasExplicitTimes =
        !!internMaterialIssue.start_time &&
        !!internMaterialIssue.end_time &&
        !(internMaterialIssue.start_time === "00:00" && internMaterialIssue.end_time === "23:59");

    try {
        const payload = {
            article_ids: ids,
            type: 'intern',
            issue_id: internMaterialIssue?.id || null,
            start_date: internMaterialIssue.start_date,
            end_date: internMaterialIssue.end_date,
        };

        if (hasExplicitTimes) {
            payload.start_time = internMaterialIssue.start_time;
            payload.end_time = internMaterialIssue.end_time;
        }

        const response = await axios.post(
            route("inventory.articles.available-stock.batch"),
            payload
        );

        const resultMap = response.data.data;

        for (const article of articles.value) {
            const stock = resultMap[article.id];
            article.periodAvailabilityLoading = false;
            article.periodAvailability = stock;
        }
    } catch (error) {
        for (const article of articles.value) {
            article.periodAvailabilityLoading = false;
            article.periodAvailability = null;
        }
    }
};

const upload = (event) => {
    const files = event.target.files;
    if (files.length > 0) {
        for (let i = 0; i < files.length; i++) {
            internMaterialIssue.files.push(files[i]);
        }
    }
};

const removeFile = (id) => {
    router.delete(route("issue-of-material.file.delete", id), {
        onSuccess: () => {
            // Update both the form files and the original files array
            if (props.issueOfMaterial && props.issueOfMaterial.files) {
                props.issueOfMaterial.files =
                    props.issueOfMaterial.files.filter(
                        (file) => file.id !== id
                    );
            }
        },
    });
};

watch(
    () => [internMaterialIssue.start_date, internMaterialIssue.end_date, internMaterialIssue.start_time, internMaterialIssue.end_time],
    debounce(() => {
        checkAvailableStock();
        checkFoundArticlesAvailability(); // Check availability for found articles
        reloadArticlesWithNewFilter(); // Artikelliste neu laden bei Datumswechsel
    }, 300)
);

onMounted(() => {

    if (props.issueOfMaterial?.articles?.length > 0) {
        checkAvailableStock();
    }

    // Pre-select project and auto-populate dates when editing existing issue
    if(props.issueOfMaterial?.project) {
        addProject(props.issueOfMaterial?.project);
    }
    // Pre-select project and auto-populate dates when creating from project context
    else if (props.isInProjectComponent && props.project) {
        addProject(props.project);
    }

    fetchArticles({ reset: true });
    if (props.loadArticleFormBasket){
        loadBaskets();
    }

    // Auto-load the next page as the user scrolls the found-articles list to its
    // end. Replaces the manual "load more" button. rootMargin pre-fetches a bit
    // early; because the observer also fires when the sentinel is already visible,
    // short (non-scrollable) result sets fill up automatically too.
    nextTick(() => {
        if (!loadMoreSentinel.value || !scrollContainer.value) return;
        articleObserver = new IntersectionObserver((entries) => {
            if (entries[0]?.isIntersecting && hasMoreArticles.value && !isFetchingArticles.value) {
                fetchArticles({ reset: false });
            }
        }, { root: scrollContainer.value, rootMargin: '250px' });
        articleObserver.observe(loadMoreSentinel.value);
    });
});

onBeforeUnmount(() => {
    if (articleObserver) {
        articleObserver.disconnect();
        articleObserver = null;
    }
});

// Anpassung der Artikelsuche
const searchArticles = async (searchTerm) => {
    try {
        const response = await axios.get(route('inventory.articles.api'), {
            params: {
                article_search: searchTerm,
                start_date: internMaterialIssue.start_date,
                end_date: internMaterialIssue.end_date,
            }
        });
        articles.value = response.data.articles.data || [];
    } catch (e) {
        console.error('Fehler bei der Artikelsuche:', e);
    }
};

// Helper function to check if file is an image based on extension
const isImageFile = (filename) => {
    if (!filename) return false;
    const extension = filename.split('.').pop()?.toLowerCase();
    const imageExtensions = ['png', 'jpe', 'jpeg', 'jpg', 'gif', 'bmp', 'ico', 'tiff', 'tif', 'svg', 'svgz'];
    return imageExtensions.includes(extension);
};

const isPdfFileName = (filename) => (filename || '').split('.').pop()?.toLowerCase() === 'pdf';

// Bild-/PDF-Vorschau für gespeicherte Anhänge (Abnahme MAT-03 Ref. 1.16)
const attachmentPreview = ref(null);
const openAttachmentPreview = (file) => {
    attachmentPreview.value = {
        src: '/storage/' + file.file_path,
        name: file.original_name,
        type: isPdfFileName(file.original_name) ? 'pdf' : 'image',
    };
};

// Helper function to create preview URL for file objects
const filePreviewUrl = (file) => {
    if (!file) return null;
    try {
        return URL.createObjectURL(file);
    } catch (error) {
        console.error('Error creating file preview URL:', error);
        return null;
    }
};

// Helper: Compute "Ready for use / Einsatzbereit" count for Found Articles list
// - For detailed-quantity articles: sum quantities of detailed items with status name in ['Einsatzbereit', 'Ready for use']
// - Otherwise: use the article's status_values pivot value for that status
const READY_STATUS_NAMES = ['Einsatzbereit', 'Ready for use'];
function readyForUseCount(article: any): number {
    if (!article) return 0;
    const isReadyName = (name?: string) => !!name && READY_STATUS_NAMES.includes(name);
    try {
        if (article.is_detailed_quantity && Array.isArray(article.detailed_article_quantities) && article.detailed_article_quantities.length) {
            return article.detailed_article_quantities.reduce((sum: number, dq: any) => {
                const name = dq?.status?.name as string | undefined;
                const qty = Number(dq?.quantity ?? 0);
                return sum + (isReadyName(name) && !Number.isNaN(qty) ? qty : 0);
            }, 0);
        }
        const readyStatus = (article.status_values || []).find((s: any) => isReadyName(s?.name));
        const val = Number(readyStatus?.pivot?.value ?? 0);
        return Number.isNaN(val) ? 0 : val;
    } catch (e) {
        return 0;
    }
}




const openArticleDetailModal = async (article) => {
    try {
        // If the article already has detailed_article_quantities, use it directly
        if (article.detailed_article_quantities && article.detailed_article_quantities.length > 0) {
            articleForDetailModal.value = article;
            return;
        }

        // Otherwise, fetch complete article data from the API
        const response = await axios.get(route('inventory.articles.api'), {
            params: {
                article_id: article.id,
                include_detailed_quantities: true
            }
        });

        // Find the article in the response
        const completeArticle = response.data.articles.data?.find(a => a.id === article.id);
        if (completeArticle) {
            // Use the complete article data directly to ensure all nested properties (like status) are preserved
            articleForDetailModal.value = {
                ...completeArticle,
                // Preserve any material issue specific properties from the original article
                quantity: article.quantity, // Usage quantity for material issue
                total_quantity: completeArticle.quantity, // Original total stock quantity
            };
        } else {
            // Fallback: use the article as-is if we can't fetch complete data
            articleForDetailModal.value = article;
        }
    } catch (error) {
        console.error('Error fetching complete article data:', error);
        // Fallback: use the article as-is if there's an error
        articleForDetailModal.value = article;
    }
};

const loadBaskets = async () => {
    try {
        const response = await axios
            .get(route("inventory.product_basket.get_baskets"))
            .then((res) => res.data);

        baskets.value = response?.baskets ?? [];

        // Fallback für currentBasket, falls nötig
        if (!baskets.value.find(b => b.id === currentBasket.value) && baskets.value.length) {
            currentBasket.value = baskets.value[0].id;
        }

        // Automatisch den eigenen Warenkorb übernehmen — die API liefert nur
        // die Baskets des eingeloggten Users (eine feste ID gibt es nicht).
        const ownBasket = baskets.value.find(b => b.id === currentBasket.value) ?? baskets.value[0];
        if (ownBasket) {
            addBasketArticlesToIssue(ownBasket);
        }
    } catch (e) {
        console.error(e);
        baskets.value = [];
    } finally {
        isLoadingBaskets.value = false;
    }
};

function mapBasketArticleToIssueArticle(ba) {
    const art = ba?.article ?? {};
    return {
        id: art.id,
        name: art.name,
        description: art.description,
        quantity: Number(ba?.quantity ?? 1),                 // Menge aus dem Basket
        total_quantity: art.quantity,                        // Gesamtbestand für Detailmodal
        is_detailed_quantity: art.is_detailed_quantity,
        availableStock: 0,
        availableStockRequestIsLoading: true,
        detailed_article_quantities: art.detailed_article_quantities ?? [],
        category: art.category ?? null,
        subCategory: art.sub_category ?? null,               // camelCase
        sub_category: art.sub_category ?? null,              // snake_case Kompatibilität
        images: art.images ?? [],
        properties: art.properties ?? [],
        room: art.room ?? null,
        manufacturer: art.manufacturer ?? null,
        status_values: art.status_values ?? [],
    };
}

function addBasketArticlesToIssue(basket) {
    if (!basket?.basket_articles?.length) return;

    for (const ba of basket.basket_articles) {
        const art = ba?.article;
        if (!art?.id) continue;

        const idx = internMaterialIssue.articles.findIndex(a => a.id === art.id);

        if (idx === -1) {
            // Neu aufnehmen mit der in Basket hinterlegten Menge
            internMaterialIssue.articles.push(mapBasketArticleToIssueArticle(ba));
        } else {
            // Bereits vorhanden → Menge aufsummieren
            const addQty = Number(ba?.quantity ?? 1);
            internMaterialIssue.articles[idx].quantity = Number(internMaterialIssue.articles[idx].quantity ?? 0) + addQty;

            // Falls Felder bisher minimal waren, fehlende Felder nachziehen
            const enriched = mapBasketArticleToIssueArticle(ba);
            internMaterialIssue.articles[idx] = {
                ...enriched,
                // eigene Menge behalten (bereits gemerged)
                quantity: internMaterialIssue.articles[idx].quantity,
                // bereits ggf. geladene availableStock-Flags respektieren
                availableStock: internMaterialIssue.articles[idx].availableStock ?? enriched.availableStock,
                availableStockRequestIsLoading: true,
            };
        }
    }

    // Verfügbarkeiten nachziehen
    checkAvailableStock();

    // Der Korb wird erst nach ERFOLGREICHEM Speichern der Ausgabe geleert —
    // beim Abbrechen bleiben die Artikel im Warenkorb erhalten.
    pendingBasketClearId.value = basket.id;
}

const pendingBasketClearId = ref(null);

function clearConsumedBasket() {
    if (!pendingBasketClearId.value) return;
    router.post(route("inventory.product_basket.remove_articles", {productBasket: pendingBasketClearId.value}), {
        basket_id: pendingBasketClearId.value
    });
    pendingBasketClearId.value = null;
}

</script>

<style scoped></style>
