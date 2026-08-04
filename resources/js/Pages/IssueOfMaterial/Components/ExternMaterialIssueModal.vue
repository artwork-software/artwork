<template>
    <form @submit.prevent="submit" @keydown.enter="preventEnterSubmit" class="mx-auto w-full px-4 md:px-6">
        <!-- Page Header -->
        <header class="mb-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-info-surface to-accent-100 px-3 py-1 ring-1 ring-inset ring-info-border">
                        <span class="inline-block size-1.5 rounded-full bg-info"></span>
                        <span class="text-[11px] font-semibold text-info tracking-wide">{{ $t('External Material Issue') }}</span>
                    </div>
                    <h1 class="mt-2 text-xl md:text-2xl font-bold tracking-tight text-text">
                        {{ externMaterialIssue?.id ? $t('Edit external material issue') : $t('Create external material issue') }}
                    </h1>
                    <p class="text-sm text-text-subtle">
                        {{ $t('Here you can capture the basic information for the external material issue. Fields marked with * are required.') }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-accent-50 px-2.5 py-1 text-xs font-medium text-accent-700 ring-1 ring-inset ring-accent-200">
                    {{ externMaterialIssueForm.articles?.length || 0 }} {{ $t('articles') }}
                    </span>
                                        <span class="inline-flex items-center rounded-full bg-success-surface px-2.5 py-1 text-xs font-medium text-success ring-1 ring-inset ring-success-border">
                    {{ externMaterialIssueForm.files?.length || 0 }} {{ $t('files') }}
                    </span>
                                        <span v-if="externMaterialIssueForm.special_items?.length" class="inline-flex items-center rounded-full bg-special-violet-surface px-2.5 py-1 text-xs font-medium text-special-violet ring-1 ring-inset ring-special-violet-border">
                    {{ externMaterialIssueForm.special_items.length }} {{ $t('Special article') }}
                    </span>
                </div>
            </div>
        </header>

        <!-- Konflikt-Leiste: Zeigt Überbuchungen im Zeitraum an -->
        <section v-if="hasConflicts" class="mb-6 rounded-2xl border border-danger-border bg-danger-surface p-4 ring-1 ring-inset ring-danger-border">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="grid size-8 place-items-center rounded-full bg-danger text-white text-xs font-bold">!</div>
                    <div>
                        <h3 class="text-sm font-semibold text-danger">{{ $t('Conflicts regarding availability') }}</h3>
                        <p class="text-xs text-danger">
                            {{ $t('There are') }} <strong>{{ conflicts.length }}</strong> {{ $t('Items with a quantity higher than available in the selected period.') }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="inline-flex items-center rounded-lg bg-white px-3 py-1.5 text-xs font-medium text-danger ring-1 ring-inset ring-danger-border hover:bg-danger-surface" @click="scrollToFirstConflict">
                        {{ $t('Show first conflict') }}
                    </button>
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
                    <button type="button" class="text-xs font-medium text-accent-700 underline shrink-0" @click="openLightbox(0, externMaterialIssueForm.articles[c.index]?.images || [])">{{ $t('details') }}</button>
                </div>
            </div>
        </section>



        <div class="space-y-8">
            <!-- Base data & External contact -->
            <section class="rounded-2xl border border-border-subtle bg-white shadow-sm">
                <div class="border-b border-border-subtle bg-gradient-to-r from-info-surface via-info-surface to-transparent px-6 py-4 rounded-t-2xl">
                    <h2 class="text-base font-semibold text-text flex items-center gap-2">
                        <span class="inline-block size-2 rounded-full bg-info"></span>
                        {{ $t('Base data') }}
                    </h2>
                    <p class="text-xs text-text-subtle">{{ $t('Capture name, value, period and contact.') }}</p>
                </div>
                <!-- Project -->
                <div class="px-6 pt-2">
                    <ProjectSearch v-if="!selectedProject" @project-selected="addProject" :get-first-last-event="true" show-recent-projects :label="$t('Project assignment (optional)')" />
                    <div v-else class="mt-1">
                        <span class="text-xs font-medium text-text-subtle">{{ $t('Selected project') }}</span>
                        <div class="mt-1 flex items-center justify-between rounded-xl border border-accent-200 bg-accent-50 px-3 py-1">
                            <div class="text-sm font-semibold text-accent-700">{{ selectedProject.name }}</div>
                            <button type="button" class="text-xs font-medium text-accent-700 underline" @click="removeProject">
                                {{ $t('Remove assignment') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="p-6 grid grid-cols-1 gap-6 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <BaseInput id="name" type="text" v-model="externMaterialIssueForm.name" :label="$t('Name') + ' *'" />
                        <p class="text-xs text-danger mt-0.5" v-if="externMaterialIssueForm.errors.name">{{ externMaterialIssueForm.errors.name }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <BaseInput id="material_value" type="number" :step="0.01" v-model="externMaterialIssueForm.material_value" :label="$t('Material Value') + ' *'" />
                        <p class="text-xs text-danger mt-0.5" v-if="externMaterialIssueForm.errors.material_value">{{ externMaterialIssueForm.errors.material_value }}</p>
                    </div>


                    <div class="md:col-span-2">
                        <BaseInput id="issue_date" v-model="externMaterialIssueForm.issue_date" :label="$t('Issue Date') + ' *'" type="date" />
                        <p class="text-xs text-danger mt-0.5" v-if="externMaterialIssueForm.errors.issue_date">{{ externMaterialIssueForm.errors.issue_date }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <BaseInput id="return_date" v-model="externMaterialIssueForm.return_date" :label="$t('Return Date') + ' *'" type="date" />
                        <p class="text-xs text-danger mt-0.5" v-if="externMaterialIssueForm.errors.return_date">{{ externMaterialIssueForm.errors.return_date }}</p>
                        <p class="text-xs font-medium text-danger mt-1" v-if="isReturnDateBeforeIssueDate">{{ $t('Return date cannot be earlier than issue date') }}</p>
                    </div>


                    <div class="md:col-span-2">
                        <BaseInput id="external_name" v-model="externMaterialIssueForm.external_name" :label="$t('External Name') + ' *'" type="text" />
                        <p class="text-xs text-danger mt-0.5" v-if="externMaterialIssueForm.errors.external_name">{{ externMaterialIssueForm.errors.external_name }}</p>
                    </div>
                    <div>
                        <BaseInput id="external_email" v-model="externMaterialIssueForm.external_email" :label="$t('External E-Mail')" type="email" />
                        <p class="text-xs text-danger mt-0.5" v-if="externMaterialIssueForm.errors.external_email">{{ externMaterialIssueForm.errors.external_email }}</p>
                    </div>
                    <div>
                        <BaseInput id="external_phone" v-model="externMaterialIssueForm.external_phone" :label="$t('External Phone')" type="text" />
                        <p class="text-xs text-danger mt-0.5" v-if="externMaterialIssueForm.errors.external_phone">{{ externMaterialIssueForm.errors.external_phone }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <BaseInput id="external_address" v-model="externMaterialIssueForm.external_address" :label="$t('External Address')" type="text" />
                        <p class="text-xs text-danger mt-0.5" v-if="externMaterialIssueForm.errors.external_address">{{ externMaterialIssueForm.errors.external_address }}</p>
                    </div>


                    <!-- Issued By -->
                    <div class="md:col-span-4">
                        <UserSearch @user-selected="selectIssueBy" :label="$t('Issued By')" v-if="!issueBy" />
                        <div v-else class="mt-1">
                            <span class="text-xs font-medium text-text-subtle">{{ $t('Issued By') }}</span>
                            <div class="mt-1 flex items-center justify-between rounded-xl border border-accent-200 bg-accent-50 px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <img class="size-8 rounded-full object-cover" :src="issueBy.profile_photo_url" alt="" />
                                    <span class="text-sm font-semibold text-accent-700">{{ issueBy.first_name }} {{ issueBy.last_name }}</span>
                                </div>
                                <button type="button" class="text-xs font-medium text-accent-700 underline" @click="issueBy = null">
                                    {{ $t('Remove assignment') }}
                                </button>
                            </div>
                        </div>
                    </div>


                    <!-- Return remarks -->
                    <div class="md:col-span-4">
                        <BaseTextarea id="return_remarks" v-model="externMaterialIssueForm.return_remarks" :label="$t('Defects after return')" />
                    </div>
                </div>
            </section>

            <!-- Search & select items -->
            <section class="space-y-6">
                <!-- Artikelsuche (links) + Auswahl (rechts) 50/50 nebeneinander -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 items-start">
                <!-- Found articles (left column) -->
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
                            <ToolTipComponent @click="showSelectMaterialSetModal = true" :icon="IconParentheses" :tooltip-text="$t('Select material set')" icon-size="size-7" tooltip-width="w-fit whitespace-nowrap" position="top" />
                            <ToolTipComponent @click="showCopyIssueModal = true" :icon="IconCopy" :tooltip-text="$t('Copy material from another material issue')" icon-size="size-7" tooltip-width="w-fit whitespace-nowrap" position="top" />
                            <InventoryFunctionBarFilter @close="reloadArticlesWithNewFilter" />
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="font-semibold flex items-center gap-2">
                                <span class="inline-block size-2 rounded-full bg-accent-600"></span>
                                {{ $t('Found Articles') }}
                                <span v-if="filteredArticles && filteredArticles.length > 0" class="text-sm font-normal text-text-subtle">
                                    · {{ filteredArticles.length }} {{ filteredArticles.length === 1 ? $t('article found') : $t('articles found') }}
                                </span>
                            </h3>
                            <span class="inline-flex items-center rounded-full bg-accent-50 px-2.5 py-1 text-xs font-medium text-accent-700 ring-1 ring-inset ring-accent-200 shrink-0">
                                {{ externMaterialIssueForm.articles?.length || 0 }} {{ $t('selected') }}
                            </span>
                        </div>
                    </div>

                    <div ref="scrollContainer" class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                        <div class="grid grid-cols-1 gap-3 2xl:grid-cols-2">
                            <div v-for="article in filteredArticles" :key="article.id" class="rounded-xl border p-3 shadow-sm transition" :class="selectedQuantityById[article.id] ? 'border-success-border bg-success-surface ring-1 ring-success-border' : 'border-border-subtle bg-surface-sunken hover:bg-surface-sunken hover:border-accent-200'">
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
                                                        <span class="tabular-nums">{{ article.availableStock?.ready ?? status.pivot.value ?? 0 }}</span>
                                                    </div>
                                                </template>
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

                    <!-- Selected items (right column) -->
                    <div class="rounded-2xl border border-border-subtle bg-white shadow-sm flex flex-col lg:sticky lg:top-0 lg:max-h-[calc(100vh-11rem)]">
                        <div class="border-b border-border-subtle px-6 py-4 rounded-t-2xl flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="text-base font-semibold text-text flex items-center gap-2">
                                    <span class="inline-block size-2 rounded-full bg-accent-600"></span>
                                    {{ $t('Selected items') }}
                                </h3>
                                <p class="text-xs text-text-subtle">{{ $t('Here you can see the items you have selected for the material issue. Adjust the quantity or remove items.') }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <div class="text-lg font-bold tabular-nums leading-none text-text">{{ externMaterialIssueForm.articles?.length || 0 }}</div>
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
                            <div v-if="externMaterialIssueForm.articles.length > 0" class="divide-y divide-border-subtle">
                                <div v-for="(article, index) in externMaterialIssueForm.articles" :key="index" :data-article-row="index" class="flex flex-col gap-3 py-3 2xl:flex-row 2xl:items-center 2xl:justify-between">
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
                                                <component :is="IconListDetails" class="h-4 w-4 text-text-subtle hover:text-text-muted" @click="articleForDetailModal = article" />
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
                                            <div v-if="article.quantity > (article.availableStock?.available ?? 0) && (props.planningDate || (externMaterialIssueForm.issue_date && externMaterialIssueForm.return_date))" class="mt-1 inline-flex items-center gap-1.5 rounded-md bg-danger-surface px-2 py-1 text-[11px] font-medium text-danger ring-1 ring-inset ring-danger-border">
                                                <span>{{ $t('Overbooking') }}</span>
                                                <button type="button" class="underline" @click="getArticleDataForUsage(article)">{{ $t('Details') }}</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-4 md:gap-6">
                                        <div class="w-28">
                                            <BaseInput :id="'article-quantity-' + index" type="number" v-model="article.quantity" :label="$t('Menge')" :input-classes="article.quantity > (article.availableStock?.available ?? 0) && externMaterialIssueForm.issue_date && externMaterialIssueForm.return_date ? '!border-danger !bg-danger-surface' : ''" />
                                        </div>
                                        <button type="button" class="rounded-md p-2 text-text-subtle hover:bg-surface-sunken hover:text-danger" @click="removeArticle(index)">
                                            <component :is="IconTrash" class="h-5 w-5" stroke-width="1.5" />
                                        </button>
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

                <!-- Sekundär: Sonderartikel (volle Breite) -->
                <div class="space-y-6">
                    <!-- Special items -->
                    <div class="rounded-2xl border border-border-subtle bg-white shadow-sm">
                        <div class="flex items-center justify-between gap-3 border-b border-border-subtle px-6 py-4 rounded-t-2xl">
                            <div>
                                <h3 class="text-base font-semibold text-text flex items-center gap-2">
                                    <span class="inline-block size-2 rounded-full bg-special-violet"></span>
                                    {{ $t('Special items') }}
                                </h3>
                                <p class="text-xs text-text-subtle">{{ $t('Add items that are not listed in the system.') }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="flex items-center gap-2 text-xs text-text-muted">
                                    <input type="checkbox" v-model="externMaterialIssueForm.special_items_done" class="h-4 w-4 rounded border-border text-special-violet focus:ring-accent-600" />
                                    <span>{{ $t('Special items done') }}</span>
                                </label>
                                <button type="button" class="inline-flex items-center gap-1 rounded-lg bg-special-violet px-3 py-1.5 text-xs font-semibold text-white hover:bg-special-violet" @click="addSpecialItem">
                                    <component :is="IconCirclePlus" class="h-3.5 w-3.5" />
                                    {{ $t('Add special article') }}
                                </button>
                            </div>
                        </div>

                        <div class="max-h-[26rem] overflow-y-auto p-6">
                            <div class="divide-y divide-dashed divide-border-subtle">
                                <div v-for="(article, index) in externMaterialIssueForm.special_items" :key="index" class="py-3">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-9">
                                        <BaseInput :id="'special-article-name-' + index" type="text" v-model="article.name" :label="$t('Article Name')" class="md:col-span-6" />
                                        <BaseInput :id="'special-article-quantity' + index" type="number" v-model="article.quantity" :label="$t('Quantity')" class="md:col-span-2" />
                                        <div class="flex items-center justify-center">
                                            <button type="button" class="rounded-md p-2 text-text-subtle hover:bg-surface-sunken hover:text-danger" @click="removeSpecialArticle(index)">
                                                <component :is="IconTrash" class="h-5 w-5" stroke-width="1.5" />
                                            </button>
                                        </div>
                                        <BaseTextarea :id="'special-article-description-' + index" v-model="article.description" rows="1" :label="$t('Description')" class="md:col-span-9" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Files -->
            <section class="rounded-2xl border border-border-subtle bg-white shadow-sm">
                <div class="border-b border-border-subtle px-6 py-4 rounded-t-2xl bg-gradient-to-r from-success-surface via-success-surface to-transparent">
                    <h2 class="text-base font-semibold text-text flex items-center gap-2">
                        <span class="inline-block size-2 rounded-full bg-success"></span>
                        {{ $t('Files for material output') }}
                    </h2>
                </div>
                <div class="p-6 grid grid-cols-1 gap-6 md:grid-cols-2 items-stretch">
                    <div>
                        <button @click="$refs.externMaterialIssueFiles.click()" type="button" class="relative block w-full max-h-56 min-h-56 rounded-2xl border-2 border-dashed border-success-border p-10 text-center hover:border-success-border focus:outline-hidden focus:ring-2 focus:ring-success focus:ring-offset-2">
                            <component :is="IconFile" class="mx-auto size-12 text-success" stroke-width="1" />
                            <span class="mt-2 block text-sm font-semibold text-success">{{ $t('Select file') }}</span>
                            <input @change="upload" class="hidden" ref="externMaterialIssueFiles" id="file" type="file" multiple />
                        </button>
                    </div>

                    <div class="rounded-xl border border-border-subtle bg-surface-sunken p-4 max-h-56 min-h-56 overflow-y-auto">
                        <div v-if="props.externMaterialIssue?.files?.length" class="space-y-2">
                            <div v-for="(file, index) in props.externMaterialIssue.files" :key="'existing-' + index" class="flex items-center gap-3 rounded-lg border border-border-subtle bg-white px-3 py-2">
                                <!-- Thumbnail für Bilddateien -->
                                <div v-if="isImageFile(file.original_name)" class="shrink-0">
                                    <div class="overflow-hidden rounded border border-border-subtle shadow-sm" style="width: 40px; height: 40px;">
                                        <img :src="'/storage/' + file.file_path" :alt="file.original_name" class="block h-full w-full object-cover" @error="(e) => e.target.src = usePage().props.big_logo" />
                                    </div>
                                </div>
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

                        <div v-if="externMaterialIssueForm.files?.length" class="mt-3 space-y-2">
                            <div v-for="(file, index) in externMaterialIssueForm.files" :key="'new-' + index" class="flex items-center gap-3 rounded-lg border border-border-subtle bg-white px-3 py-2">
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
                                <button type="button" class="rounded-md p-1.5 text-text-subtle hover:bg-surface-sunken hover:text-danger" @click="externMaterialIssueForm.files.splice(index, 1)">
                                    <component :is="IconTrash" class="h-4 w-4" stroke-width="1.5" />
                                </button>
                            </div>
                        </div>

                        <div v-if="!props.externMaterialIssue?.files?.length && !externMaterialIssueForm.files?.length" class="grid h-full place-items-center text-xs text-text-subtle">
                            {{ $t('No files selected') }}
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
                        {{ $t('Selected') }}: <span class="font-medium">{{ externMaterialIssueForm.articles?.length || 0 }}</span> {{ $t('articles') }} • {{ $t('Files') }}: <span class="font-medium">{{ externMaterialIssueForm.files?.length || 0 }}</span>
                    </div>
                    <FormButton :text="externMaterialIssue?.id ? $t('Update') : $t('Save')" :disabled="externMaterialIssueForm.processing || !externMaterialIssueForm.issue_date || !externMaterialIssueForm.return_date || !externMaterialIssueForm.material_value || isReturnDateBeforeIssueDate" type="submit" />
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
            :circular="true"
            :fullScreen="true"
            :showItemNavigators="true"
            :showThumbnails="true"
        >
            <template #item="slotProps">
                <img :src="'/storage/' + slotProps.item.image" :alt="slotProps.item.alt || ''" style="width: 100%; display: block" @error="(e) => e.target.src = usePage().props.big_logo" />
            </template>
            <template #thumbnail="slotProps">
                <img :src="'/storage/' + slotProps.item.image" :alt="slotProps.item.alt || ''" class="w-20 max-w-20" style="display: block" @error="(e) => e.target.src = usePage().props.big_logo" />
            </template>
        </Galleria>
    </form>

    <ArticleSearchFilterModal
        v-if="showArticleFilterModal"
        @close="showArticleFilterModal = false"
        @add-article="addArticleToIssue"
    />

    <SelectMaterialSetModal
        v-if="showSelectMaterialSetModal"
        @close="showSelectMaterialSetModal = false"
        @add-material-set="addMaterialSetToIssue"
    />

    <CopyFromMaterialIssueModal
        v-if="showCopyIssueModal"
        issue-type="extern"
        :exclude-issue-id="externMaterialIssueForm.id"
        @close="showCopyIssueModal = false"
        @copy-issue="addArticlesFromCopiedIssue"
    />

    <ArticleDetailModal :article="articleForDetailModal" v-if="articleForDetailModal" @close="articleForDetailModal = null" :show-button-for-edit-and-delete="false" />

    <ArticleUsageModal :details-for-modal="articleForUsageModal" v-if="articleForUsageModal" @close="articleForUsageModal = null" />
</template>

<script setup>

import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import RoomSearch from "@/Components/SearchBars/RoomSearch.vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import ArticleSearchFilterModal from "@/Pages/IssueOfMaterial/Components/ArticleSearchFilterModal.vue";
import ProjectSearch from "@/Components/SearchBars/ProjectSearch.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import {router, useForm, usePage} from "@inertiajs/vue3";
import {computed, nextTick, onBeforeUnmount, onMounted, ref, watch} from "vue";
import debounce from "lodash.debounce";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import SelectMaterialSetModal from "@/Pages/IssueOfMaterial/Components/SelectMaterialSetModal.vue";
import CopyFromMaterialIssueModal from "@/Pages/IssueOfMaterial/Components/CopyFromMaterialIssueModal.vue";
import InventoryFunctionBarFilter from "@/Artwork/Filter/InventoryFunctionBarFilter.vue";
import axios from "axios";
import ArticleUsageModal from "@/Pages/Inventory/Components/Planning/ArticleUsageModal.vue";
import ArticleDetailModal from "@/Pages/Inventory/Components/Article/Modals/ArticleDetailModal.vue";
import Galleria from "primevue/galleria";
import {IconCircleCheck, IconCirclePlus, IconCopy, IconFile, IconInfoCircle, IconListDetails, IconLoader, IconParentheses, IconTrash, IconWindowMaximize, IconX} from "@tabler/icons-vue";

const props = defineProps({
    externMaterialIssue: {
        type: Object,
        required: false,
        default: () => ({
            material_value: 0.00,
            name: '',
            issue_date: '',
            return_date: '',
            return_remarks: '',
            external_name: '',
            external_address: '',
            external_email: '',
            external_phone: '',
            files: [],
            articles: [],
            special_items: [],
            special_items_done: false,
            issued_by_id: null,
            project_id: null,
            project: null
        })
    },
    loadArticleFormBasket: {
        type: Boolean,
        required: false,
        default: false,
    },
    planningDate: {
        type: String,
        required: false,
        default: null,
    },
    project: {
        type: Object,
        required: false,
        default: null,
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
})


// Map articles and ensure pivot quantities are correctly assigned
const mapArticlesWithQuantity = (articles) => {
    if (!articles || !Array.isArray(articles)) return [];

    return articles.map(article => ({
        ...article,
        // Map pivot.quantity to quantity for form binding
        quantity: article.pivot?.quantity || article.quantity || 1,
        // Preserve pivot data for reference
        pivot: article.pivot
    }));
};

const externMaterialIssueForm = useForm({
    id: props.externMaterialIssue.id ?? null,
    name: props.externMaterialIssue.name,
    material_value: props.externMaterialIssue.material_value,
    issue_date: props.externMaterialIssue.issue_date,
    return_date: props.externMaterialIssue.return_date,
    return_remarks: props.externMaterialIssue.return_remarks,
    external_name: props.externMaterialIssue.external_name,
    external_address: props.externMaterialIssue.external_address,
    external_email: props.externMaterialIssue.external_email,
    external_phone: props.externMaterialIssue.external_phone,
    files: [], // New files to upload
    existing_files: props.externMaterialIssue?.files || [], // Keep track of existing files
    articles: mapArticlesWithQuantity(props.externMaterialIssue?.articles || []),
    special_items: props.externMaterialIssue?.special_items || [],
    special_items_done: props.externMaterialIssue?.special_items_done || false,
    issued_by_id: props.externMaterialIssue?.issued_by_id || null,
    project_id: props.externMaterialIssue?.project_id || null
})

const selectedProject = ref(
    props.externMaterialIssue?.project
    ?? (!props.externMaterialIssue?.id && props.project?.id ? props.project : null)
)

// Projektkontext (z. B. Projekt-Tab): Projekt und Zeitraum bei Neuanlage vorbelegen
if (!props.externMaterialIssue?.id && selectedProject.value) {
    externMaterialIssueForm.project_id = selectedProject.value.id ?? null
    if (!externMaterialIssueForm.name && selectedProject.value.name) {
        externMaterialIssueForm.name = selectedProject.value.name
    }
    const contextStart = props.firstEvent?.formatted_dates?.start_without_time ?? null
    const contextEnd = props.lastEvent?.formatted_dates?.end_without_time ?? null
    if (!externMaterialIssueForm.issue_date && contextStart) {
        externMaterialIssueForm.issue_date = contextStart
    }
    if (!externMaterialIssueForm.return_date && contextEnd) {
        externMaterialIssueForm.return_date = contextEnd
    }
}

const addProject = (project) => {
    selectedProject.value = project ?? null
    if (!project) return
    externMaterialIssueForm.project_id = project.id ?? null

    // Name nur setzen, wenn leer
    if (!externMaterialIssueForm.name && project.name) {
        externMaterialIssueForm.name = project.name
    }

    // Zeitraum aus erstem/letztem Termin vorbelegen — nur, wenn die Felder leer sind
    const startDate = project.first_event?.formatted_dates?.start_without_time ?? null
    const endDate = project.last_event?.formatted_dates?.end_without_time ?? null
    if (!externMaterialIssueForm.issue_date && startDate) {
        externMaterialIssueForm.issue_date = startDate
    }
    if (!externMaterialIssueForm.return_date && endDate) {
        externMaterialIssueForm.return_date = endDate
    }
}

const removeProject = () => {
    selectedProject.value = null
    externMaterialIssueForm.project_id = null
}

const showArticleFilterModal = ref(false)
const showSelectMaterialSetModal = ref(false)
const showCopyIssueModal = ref(false)
const issueBy = ref(props.externMaterialIssue?.issued_by || null)

// Artikel Pagination State (aligned with internal)
const articles = ref([])
const loadingMore = ref(false)
const isFetchingArticles = ref(false)
const scrollContainer = ref(null)
const loadMoreSentinel = ref(null)
let articleObserver = null
const hasMoreArticles = ref(true)
const paginationPage = ref(1)
const articleForDetailModal = ref(null);
const articleForUsageModal = ref(null);
// Translation key of the last usage-lookup error (shown inline in the selection panel).
const usageError = ref(null);
const articleSearchFilter = ref("");

const baskets = ref([]);
const isLoadingBaskets = ref(true);
const currentBasket = ref(1);

const isReturnDateBeforeIssueDate = computed(() => {
    if (!externMaterialIssueForm.issue_date || !externMaterialIssueForm.return_date) {
        return false
    }

    const issueDate = new Date(externMaterialIssueForm.issue_date)
    const returnDate = new Date(externMaterialIssueForm.return_date)

    return returnDate < issueDate
})

const conflicts = computed(() => {
    const arts = (externMaterialIssueForm?.articles ?? [])
    const hasPeriod = !!(externMaterialIssueForm?.issue_date && externMaterialIssueForm?.return_date)
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

// Server-side search: filtering happens in the API (name, category, subcategory),
// so the found list always mirrors what the server returns for the current term.
const filteredArticles = computed(() => articles.value)

// Lookup of already-selected article id -> chosen quantity, so the "Found Articles"
// cards can show a "selected · ×N" marker without scanning the array per card.
const selectedQuantityById = computed(() => {
    const map = {};
    for (const a of externMaterialIssueForm.articles) {
        if (a?.id != null) {
            map[a.id] = Number(a.quantity ?? 0);
        }
    }
    return map;
})

// Re-run the unified loader from page 1 on every search change. The current term
// is always read inside fetchArticles(), so search and pagination stay in sync
// (fixes the old client-side filter that only searched already-loaded pages).
const searchArticlesFromServer = debounce(() => {
    fetchArticles({ reset: true });
}, 300);

watch(articleSearchFilter, () => {
    searchArticlesFromServer();
})

// Scrollt zur ersten konfliktbehafteten Zeile und hebt sie kurz hervor
function scrollToFirstConflict () {
    if (!conflicts.value.length) return
    nextTick(() => {
        const idx = conflicts.value[0].index
        const el = document.querySelector(`[data-article-row="${idx}"]`) | null
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' })
            el.classList.add('ring-2','ring-danger-border','rounded-xl')
            setTimeout(() => el.classList.remove('ring-2','ring-danger-border','rounded-xl'), 1400)
        }
    })
}

// Setzt alle Konflikt-Mengen auf „max. verfügbar“
function fixAllConflicts () {
    conflicts.value.forEach(c => {
        const art = externMaterialIssueForm.articles[c.index]
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


const addSpecialItem = () => {
    externMaterialIssueForm.special_items.push({
        id: null,
        name: '',
        quantity: 1
    })
}

const removeSpecialArticle = (index) => {
    externMaterialIssueForm.special_items.splice(index, 1)
}

const addArticleToIssue = (article) => {
    // Check if the article is already in the array
    const existingArticleIndex = externMaterialIssueForm.articles.findIndex(
        (a) => a.id === article.id
    );
    if (existingArticleIndex === -1) {
        // Article doesn't exist, add it
        externMaterialIssueForm.articles.push({
            id: article.id,
            name: article.name,
            description: article.description,
            quantity: 1,
            availableStock: 0,
            availableStockRequestIsLoading: true,
            detailedArticleQuantities:
                article.detailed_article_quantities || [],
            category: article.category || null,
            subCategory: article.sub_category || null,
            images: article.images || [],
            properties: article.properties || [],
            room: article.room || null,
            manufacturer: article.manufacturer || null,
            status_values: article.status_values || [],
        });
    } else {
        // Article already selected → clicking it again increases the quantity by 1.
        const existingArticle =
            externMaterialIssueForm.articles[existingArticleIndex];
        existingArticle.quantity = Number(existingArticle.quantity ?? 0) + 1;
        if (!existingArticle.availableStock) {
            existingArticle.availableStock = 0;
            existingArticle.availableStockRequestIsLoading = true;
        }
    }

    // after add check the available stock
    checkAvailableStock();
};

const addMaterialSetToIssue = (materialSet) => {
    const existingArticleIds = externMaterialIssueForm.articles.map(a => a.id)
    const newArticles = materialSet.items.filter(item => !existingArticleIds.includes(item.article.id)).map(item => ({
        id: item.article.id,
        name: item.article.name,
        description: item.article.description,
        quantity: item.quantity || 1,
        availableStock: 0,
        availableStockRequestIsLoading: true,
    }))
    externMaterialIssueForm.articles.push(...newArticles)
    checkAvailableStock()
}

// Übernimmt alle Artikel einer anderen Materialausgabe mit deren Mengen
// (pivot.quantity). Bereits ausgewählte Artikel werden aufsummiert.
const addArticlesFromCopiedIssue = (issue) => {
    for (const article of issue?.articles ?? []) {
        if (!article?.id) continue;
        const copiedQuantity = Number(article.pivot?.quantity ?? 1);
        const existingArticleIndex = externMaterialIssueForm.articles.findIndex(
            (a) => a.id === article.id
        );

        if (existingArticleIndex === -1) {
            externMaterialIssueForm.articles.push({
                id: article.id,
                name: article.name,
                description: article.description,
                quantity: copiedQuantity,
                availableStock: 0,
                availableStockRequestIsLoading: true,
                detailedArticleQuantities: article.detailed_article_quantities || [],
                category: article.category || null,
                subCategory: article.subCategory || article.sub_category || null,
                images: article.images || [],
                properties: article.properties || [],
                room: article.room || null,
                manufacturer: article.manufacturer || null,
                status_values: article.status_values || [],
            });
        } else {
            const existingArticle = externMaterialIssueForm.articles[existingArticleIndex];
            existingArticle.quantity = Number(existingArticle.quantity ?? 0) + copiedQuantity;
        }
    }

    checkAvailableStock();
};

// Single source of truth for the "Found Articles" list. ALWAYS sends the current
// search term + date window, so paging (reset:false) stays filtered exactly like
// the initial search (reset:true).
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
            start_date: externMaterialIssueForm.issue_date || undefined,
            end_date: externMaterialIssueForm.return_date || undefined,
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
    } catch (e) {
        console.error('Fehler beim Laden der Artikel:', e);
    }
    loadingMore.value = false;
    isFetchingArticles.value = false;
}

// Thin wrappers so existing callers (date watcher, filter bar) don't need to change.
const loadMoreArticles = () => fetchArticles({ reset: false })
const reloadArticlesWithNewFilter = () => fetchArticles({ reset: true })

const getArticleDataForUsage = async (article) => {
    const startDate = props.planningDate || externMaterialIssueForm.issue_date;
    const endDate = props.planningDate || externMaterialIssueForm.return_date;
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
            }
        });
        // Die Nutzungsdaten werden im Modal angezeigt
        articleForUsageModal.value = response.data.data;
    } catch (error) {
        usageError.value = 'Usage details could not be loaded.';
        console.error('Fehler beim Abrufen der Artikel-Nutzungsdaten:', error);
    } finally {
        article.availableStockRequestIsLoading = false;
    }
};

const removeArticle = (index) => {
    externMaterialIssueForm.articles.splice(index, 1)
}
const emits = defineEmits(['close', 'saved'])

// Verhindert, dass Enter in einem Eingabefeld das Formular (und damit Speichern) auslöst.
// Textareas bleiben ausgenommen, damit Zeilenumbrüche weiterhin möglich sind.
const preventEnterSubmit = (event) => {
    if (event.target?.tagName !== 'TEXTAREA') {
        event.preventDefault();
    }
};

const submit = () => {

    // Create a list of existing file IDs to preserve them during update
    if (props.externMaterialIssue?.files) {
        externMaterialIssueForm.existing_file_ids = props.externMaterialIssue.files.map(file => file.id)
    }

    if (issueBy.value) {
        externMaterialIssueForm.issued_by_id = issueBy.value.id
    } else {
        externMaterialIssueForm.issued_by_id = null
    }

    if(props.externMaterialIssue?.id){
        // Use post instead of patch for better file upload handling
        externMaterialIssueForm._method = 'PATCH'
        externMaterialIssueForm.post(route('extern-issue-of-material.update', props.externMaterialIssue.id), {
            onSuccess: () => {
                clearConsumedBasket()
                emits('saved')
                emits('close')
            }
        })
    } else {
        externMaterialIssueForm.post(route('extern-issue-of-material.store'), {
            onSuccess: () => {
                clearConsumedBasket()
                emits('saved')
                emits('close')
            }
        })
    }
}


const checkAvailableStock = async () => {
    const startDate = props.planningDate || externMaterialIssueForm.issue_date;
    const endDate = props.planningDate || externMaterialIssueForm.return_date;

    if (!startDate || !endDate || externMaterialIssueForm.articles.length === 0) {
        return
    }

    const ids = externMaterialIssueForm.articles.map(a => a.id).filter(Boolean)
    for (const article of externMaterialIssueForm.articles) {
        article.availableStockRequestIsLoading = true
        article.availableStock = null
        article.overbooked = false
    }

    try {
        const response = await axios.post(
            route('inventory.articles.available-stock.batch'),
            {
                article_ids: ids,
                type: 'extern',
                issue_id: externMaterialIssueForm?.id || null,
                start_date: startDate,
                end_date: endDate,
            }
        )

        const resultMap = response.data.data

        for (const article of externMaterialIssueForm.articles) {
            const stock = resultMap[article.id]

            article.availableStockRequestIsLoading = false
            article.availableStock = stock

            if (article.quantity && stock && stock.available < article.quantity) {
                article.overbooked = true
            }
        }

    } catch (error) {
        console.error('Fehler bei Verfügbarkeitsabfrage (Batch):', error)
        for (const article of externMaterialIssueForm.articles) {
            article.availableStockRequestIsLoading = false
            article.availableStock = null
            article.overbooked = false
        }
    }
}

const upload = (event) => {
    const files = event.target.files
    if (files.length > 0) {
        for (let i = 0; i < files.length; i++) {
            externMaterialIssueForm.files.push(files[i])
        }
    }
}

const removeFile = (id) => {
    router.delete(route('extern-issue-of-material.file.delete', id), {
        onSuccess: () => {
            // Update both the form files and the original files array
            if (props.externMaterialIssue && props.externMaterialIssue.files) {
                props.externMaterialIssue.files = props.externMaterialIssue.files.filter(file => file.id !== id)
            }
        }
    })
}

const selectIssueBy = (user) => {
    issueBy.value = user
}

// Helper function to check if file is an image based on extension
const isImageFile = (filename) => {
    if (!filename) return false;
    const extension = filename.split('.').pop()?.toLowerCase();
    const imageExtensions = ['png', 'jpe', 'jpeg', 'jpg', 'gif', 'bmp', 'ico', 'tiff', 'tif', 'svg', 'svgz'];
    return imageExtensions.includes(extension);
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


watch(
    () => [externMaterialIssueForm.issue_date, externMaterialIssueForm.return_date],
    debounce(() => {
        checkAvailableStock()
        reloadArticlesWithNewFilter()
    }, 300)
)

onMounted(() => {
    if((props.externMaterialIssue?.articles?.length ?? 0) > 0){
        checkAvailableStock()
    }
    fetchArticles({ reset: true })

    if (props.loadArticleFormBasket){
        loadBaskets();
    }

    // Auto-load the next page as the user scrolls the found-articles list to its
    // end. Replaces the manual "load more" button; also fires when the sentinel is
    // already visible, so short result sets fill up automatically too.
    nextTick(() => {
        if (!loadMoreSentinel.value || !scrollContainer.value) return;
        articleObserver = new IntersectionObserver((entries) => {
            if (entries[0]?.isIntersecting && hasMoreArticles.value && !isFetchingArticles.value) {
                fetchArticles({ reset: false });
            }
        }, { root: scrollContainer.value, rootMargin: '250px' });
        articleObserver.observe(loadMoreSentinel.value);
    });
})

onBeforeUnmount(() => {
    if (articleObserver) {
        articleObserver.disconnect();
        articleObserver = null;
    }
})

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

        const idx = externMaterialIssueForm.articles.findIndex(a => a.id === art.id);

        if (idx === -1) {
            // Neu aufnehmen mit der in Basket hinterlegten Menge
            externMaterialIssueForm.articles.push(mapBasketArticleToIssueArticle(ba));
        } else {
            // Bereits vorhanden → Menge aufsummieren
            const addQty = Number(ba?.quantity ?? 1);
            externMaterialIssueForm.articles[idx].quantity = Number(externMaterialIssueForm.articles[idx].quantity ?? 0) + addQty;

            // Falls Felder bisher minimal waren, fehlende Felder nachziehen
            const enriched = mapBasketArticleToIssueArticle(ba);
            externMaterialIssueForm.articles[idx] = {
                ...enriched,
                // eigene Menge behalten (bereits gemerged)
                quantity: externMaterialIssueForm.articles[idx].quantity,
                // bereits ggf. geladene availableStock-Flags respektieren
                availableStock: externMaterialIssueForm.articles[idx].availableStock ?? enriched.availableStock,
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

<style scoped>

</style>
