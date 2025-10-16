<template>
    <form @submit.prevent="submit" class="mx-auto max-w-7xl px-4 md:px-6">
        <!-- Page Header -->
        <header class="mb-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-sky-100 to-indigo-100 px-3 py-1 ring-1 ring-inset ring-sky-200">
                        <span class="inline-block size-1.5 rounded-full bg-sky-500"></span>
                        <span class="text-[11px] font-semibold text-sky-800 tracking-wide">{{ $t('External Material Issue') }}</span>
                    </div>
                    <h1 class="mt-2 text-xl md:text-2xl font-bold tracking-tight text-zinc-900">
                        {{ externMaterialIssue?.id ? $t('Edit external material issue') : $t('Create external material issue') }}
                    </h1>
                    <p class="text-sm text-zinc-500">
                        {{ $t('Here you can capture the basic information for the external material issue. Fields marked with * are required.') }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-200">
                    {{ externMaterialIssueForm.articles?.length || 0 }} {{ $t('articles') }}
                    </span>
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">
                    {{ externMaterialIssueForm.files?.length || 0 }} {{ $t('files') }}
                    </span>
                                        <span v-if="externMaterialIssueForm.special_items?.length" class="inline-flex items-center rounded-full bg-violet-50 px-2.5 py-1 text-xs font-medium text-violet-700 ring-1 ring-inset ring-violet-200">
                    {{ externMaterialIssueForm.special_items.length }} {{ $t('Special article') }}
                    </span>
                </div>
            </div>
        </header>

        <!-- Konflikt-Leiste: Zeigt Überbuchungen im Zeitraum an -->
        <section v-if="hasConflicts" class="mb-6 rounded-2xl border border-red-200 bg-red-50/70 p-4 ring-1 ring-inset ring-red-200">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="grid size-8 place-items-center rounded-full bg-red-600 text-white text-xs font-bold">!</div>
                    <div>
                        <h3 class="text-sm font-semibold text-red-900">{{ $t('Conflicts regarding availability') }}</h3>
                        <p class="text-xs text-red-800/90">
                            {{ $t('There are') }} <strong>{{ conflicts.length }}</strong> {{ $t('Items with a quantity higher than available in the selected period.') }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="inline-flex items-center rounded-lg bg-white px-3 py-1.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-300 hover:bg-red-100" @click="scrollToFirstConflict">
                        {{ $t('Show first conflict') }}
                    </button>
                    <button type="button" class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700" @click="fixAllConflicts">
                        {{ $t('Automatically adjust quantities') }}
                    </button>
                </div>
            </div>


            <!-- Konfliktliste (kompakt) -->
            <div class="mt-3 grid gap-2 md:grid-cols-2">
                <div v-for="c in conflicts" :key="c.id" class="flex items-center justify-between rounded-lg bg-white px-3 py-2 ring-1 ring-zinc-200">
                    <div class="min-w-0 pr-2">
                        <p class="truncate text-xs font-medium text-zinc-900">{{ c.name }}</p>
                        <p class="text-[11px] text-zinc-500">{{ $t('Requested') }}: {{ c.wanted }} • {{ $t('Available') }}: {{ c.available }}</p>
                    </div>
                    <button type="button" class="text-xs font-medium text-blue-700 underline shrink-0" @click="openLightbox(0, externMaterialIssueForm.articles[c.index]?.images || [])">{{ $t('details') }}</button>
                </div>
            </div>
        </section>



        <div class="space-y-8">
            <!-- Base data & External contact -->
            <section class="rounded-2xl border border-zinc-200 bg-white shadow-sm">
                <div class="border-b border-zinc-100 bg-gradient-to-r from-sky-50 via-sky-50/60 to-transparent px-6 py-4 rounded-t-2xl">
                    <h2 class="text-base font-semibold text-zinc-900 flex items-center gap-2">
                        <span class="inline-block size-2 rounded-full bg-sky-500"></span>
                        {{ $t('Base data') }}
                    </h2>
                    <p class="text-xs text-zinc-500">{{ $t('Capture name, value, period and contact.') }}</p>
                </div>
                <div class="p-6 grid grid-cols-1 gap-6 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <BaseInput id="name" type="text" v-model="externMaterialIssueForm.name" :label="$t('Name') + ' *'" />
                        <p class="text-xs text-red-500 mt-0.5" v-if="externMaterialIssueForm.errors.name">{{ externMaterialIssueForm.errors.name }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <BaseInput id="material_value" type="number" :step="0.01" v-model="externMaterialIssueForm.material_value" :label="$t('Material Value') + ' *'" />
                        <p class="text-xs text-red-500 mt-0.5" v-if="externMaterialIssueForm.errors.material_value">{{ externMaterialIssueForm.errors.material_value }}</p>
                    </div>


                    <div class="md:col-span-2">
                        <BaseInput id="issue_date" v-model="externMaterialIssueForm.issue_date" :label="$t('Issue Date') + ' *'" type="date" />
                        <p class="text-xs text-red-500 mt-0.5" v-if="externMaterialIssueForm.errors.issue_date">{{ externMaterialIssueForm.errors.issue_date }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <BaseInput id="return_date" v-model="externMaterialIssueForm.return_date" :label="$t('Return Date') + ' *'" type="date" />
                        <p class="text-xs text-red-500 mt-0.5" v-if="externMaterialIssueForm.errors.return_date">{{ externMaterialIssueForm.errors.return_date }}</p>
                        <p class="text-xs font-medium text-red-600 mt-1" v-if="isReturnDateBeforeIssueDate">{{ $t('Return date cannot be earlier than issue date') }}</p>
                    </div>


                    <div class="md:col-span-2">
                        <BaseInput id="external_name" v-model="externMaterialIssueForm.external_name" :label="$t('External Name') + ' *'" type="text" />
                        <p class="text-xs text-red-500 mt-0.5" v-if="externMaterialIssueForm.errors.external_name">{{ externMaterialIssueForm.errors.external_name }}</p>
                    </div>
                    <div>
                        <BaseInput id="external_email" v-model="externMaterialIssueForm.external_email" :label="$t('External E-Mail')" type="email" />
                        <p class="text-xs text-red-500 mt-0.5" v-if="externMaterialIssueForm.errors.external_email">{{ externMaterialIssueForm.errors.external_email }}</p>
                    </div>
                    <div>
                        <BaseInput id="external_phone" v-model="externMaterialIssueForm.external_phone" :label="$t('External Phone')" type="text" />
                        <p class="text-xs text-red-500 mt-0.5" v-if="externMaterialIssueForm.errors.external_phone">{{ externMaterialIssueForm.errors.external_phone }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <BaseInput id="external_address" v-model="externMaterialIssueForm.external_address" :label="$t('External Address')" type="text" />
                        <p class="text-xs text-red-500 mt-0.5" v-if="externMaterialIssueForm.errors.external_address">{{ externMaterialIssueForm.errors.external_address }}</p>
                    </div>


                    <!-- Issued By -->
                    <div class="md:col-span-4">
                        <UserSearch @user-selected="selectIssueBy" :label="$t('Issued By')" v-if="!issueBy" />
                        <div v-else class="mt-1">
                            <span class="text-xs font-medium text-zinc-500">{{ $t('Issued By') }}</span>
                            <div class="mt-1 flex items-center justify-between rounded-xl border border-indigo-100 bg-indigo-50/60 px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <img class="size-8 rounded-full object-cover" :src="issueBy.profile_photo_url" alt="" />
                                    <span class="text-sm font-semibold text-indigo-800">{{ issueBy.first_name }} {{ issueBy.last_name }}</span>
                                </div>
                                <button type="button" class="text-xs font-medium text-indigo-700 underline" @click="issueBy = null">
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
            <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Left: search -->
                <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm lg:col-span-1">
                    <div class="sticky top-0 z-10 border-b border-zinc-100 bg-white/90 backdrop-blur px-5 py-3 rounded-t-2xl">
                        <div class="flex items-center w-full gap-x-3">
                            <BaseInput
                                id="articleSearchFilter"
                                v-model="articleSearchFilter"
                                class="w-full"
                                :label="$t('Search Articles')"
                                :placeholder="$t('Filter articles by name...')"
                            />
                            <ToolTipComponent @click="showSelectMaterialSetModal = true" :icon="IconParentheses" :tooltip-text="$t('Select material set')" icon-size="size-7" tooltip-width="w-fit whitespace-nowrap" position="top" />
                            <InventoryFunctionBarFilter @close="reloadArticlesWithNewFilter" />
                        </div>
                    </div>

                    <div class="px-5 py-3 flex items-center justify-between">
                        <h3 class="font-semibold flex items-center gap-2">
                            <span class="inline-block size-2 rounded-full bg-indigo-500"></span>
                            {{ $t('Found Articles') }}
                        </h3>
                        <div v-if="filteredArticles && filteredArticles.length > 0" class="text-sm text-zinc-500">
                            {{ filteredArticles.length }} {{ filteredArticles.length === 1 ? $t('article found') : $t('articles found') }}
                        </div>
                    </div>

                    <div ref="scrollContainer" class="max-h-[28rem] overflow-y-auto px-5 pb-5">
                        <div v-for="article in filteredArticles" :key="article.id" class="mb-2 rounded-xl border border-zinc-200 bg-zinc-50/60 p-3 shadow-sm hover:bg-zinc-50 transition">
                            <button type="button" class="w-full text-left" @click="addArticleToIssue(article)">
                                <div class="flex items-start gap-3">
                                    <img v-if="article?.images?.[0]?.image" :src="'/storage/' + article.images[0].image" :alt="article.images[0].alt || ''" class="h-12 w-12 rounded-lg border border-zinc-200 object-cover" @error="(e) => e.target.src = usePage().props.big_logo" />
                                    <div class="min-w-0">
                                        <div class="font-medium truncate">{{ article.name }}</div>
                                        <div class="text-xs text-zinc-500 line-clamp-2" v-if="article.description">{{ article.description }}</div>
                                        <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px]">
                                            <template v-for="(status, i) in article.status_values" :key="i">
                                                <div v-if="status.name === 'Ready for use' || status.name === 'Einsatzbereit'" class="inline-flex items-center gap-1 rounded-md border px-1.5 py-0.5" :style="{ borderColor: status.color, backgroundColor: status.color + '15' }" :title="status.name">
                                                    <span class="inline-block size-1.5 rounded-full" :style="{ backgroundColor: status.color }"></span>
                                                    <span class="tabular-nums">{{ status.name }}</span>
                                                    <span class="tabular-nums">{{ article.availableStock?.ready ?? status.pivot.value ?? 0 }}</span>
                                                </div>
                                            </template>
                                            <span class="ml-auto text-zinc-500">{{ $t('Category') }}: {{ article.category.name }}<span v-if="article.sub_category"> • {{ $t('Subcategory') }}: {{ article.sub_category.name }}</span></span>
                                        </div>
                                    </div>
                                </div>
                            </button>
                        </div>

                        <div v-if="articles" class="flex justify-center pt-4">
                            <button type="button" @click="loadMoreArticles" :disabled="loadingMore" class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100 disabled:opacity-50">
                                <span v-if="loadingMore" class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"></span>
                                {{ $t('Load more items') }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right: selected -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm">
                        <div class="border-b border-zinc-100 px-6 py-4 rounded-t-2xl">
                            <h3 class="text-base font-semibold text-zinc-900 flex items-center gap-2">
                                <span class="inline-block size-2 rounded-full bg-indigo-500"></span>
                                {{ $t('Selected items') }}
                            </h3>
                            <p class="text-xs text-zinc-500">{{ $t('Here you can see the items you have selected for the material issue. Adjust the quantity or remove items.') }}</p>
                        </div>

                        <div class="p-5">
                            <div v-if="externMaterialIssueForm.articles.length > 0" class="divide-y divide-zinc-200/80">
                                <div v-for="(article, index) in externMaterialIssueForm.articles" :key="index" :data-article-row="index" class="flex flex-col gap-3 py-3 md:flex-row md:items-center md:justify-between">
                                    <div class="flex w-full items-start gap-4">
                                        <!-- Single preview with zoom overlay -->
                                        <div v-if="article?.images?.length" class="shrink-0" style="max-width: 120px">
                                            <div class="group relative cursor-zoom-in overflow-hidden rounded-lg border border-zinc-200 shadow-sm" @click="openLightbox(0, article.images)">
                                                <img :src="'/storage/' + article.images[0].image" :alt="article.images[0].alt || ''" class="block h-auto w-full object-cover" @error="(e) => e.target.src = usePage().props.big_logo" />
                                                <div class="pointer-events-none absolute inset-0 grid place-items-center bg-black/0 transition group-hover:bg-black/30">
                                                    <component :is="IconWindowMaximize" class="h-4 w-4 text-white opacity-0 transition group-hover:opacity-100" />
                                                </div>
                                            </div>
                                        </div>

                                        <div class="min-w-0">
                                            <h4 class="text-sm font-semibold text-zinc-900 flex items-center gap-1">
                                                {{ article.name }}
                                                <component :is="IconListDetails" class="h-4 w-4 text-zinc-400 hover:text-zinc-600" @click="articleForDetailModal = article" />
                                            </h4>
                                            <div class="mt-0.5 text-xs text-zinc-600 flex items-center gap-1">
                                                {{ $t('Available stock in period') }}:
                                                <span v-if="!article.availableStockRequestIsLoading" class="tabular-nums inline-flex items-center gap-1" :class="{
                                                  'text-emerald-600': (article.availableStock?.available ?? 0) > 0,
                                                  'text-red-600': (article.availableStock?.available ?? 0) === 0
                                                }">{{ article.availableStock?.available ?? 0 }}
                                                  <button type="button" class="underline" @click="getArticleDataForUsage(article)">
                                                    <component :is="IconInfoCircle" class="h-3.5 w-3.5" stroke-width="1.5" />
                                                  </button>
                                                </span>
                                                <span v-else class="inline-flex items-center gap-1 text-zinc-500">
                                                    {{ $t('Fetching') }}
                                                    <component :is="IconLoader" class="h-3.5 w-3.5 animate-spin text-zinc-400" stroke-width="1.5" />
                                                </span>
                                            </div>
                                            <div v-if="article.quantity > (article.availableStock?.available ?? 0) && externMaterialIssueForm.issue_date && externMaterialIssueForm.return_date" class="mt-1 inline-flex items-center gap-1 rounded-md bg-red-50 px-2 py-1 text-[11px] font-medium text-red-700 ring-1 ring-inset ring-red-200">
                                                <span>
                                                    {{ $t('You have selected more items than are available.') }}
                                                    <button type="button" class="underline" @click="getArticleDataForUsage(article)">{{ $t('Show usage') }}</button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-4 md:gap-6">
                                        <div class="w-28">
                                            <BaseInput :id="'article-quantity-' + index" type="number" v-model="article.quantity" :label="$t('Menge')" :input-classes="article.quantity > (article.availableStock?.available ?? 0) && externMaterialIssueForm.issue_date && externMaterialIssueForm.return_date ? '!border-red-500 !bg-red-50' : ''" />
                                        </div>
                                        <button type="button" class="rounded-md p-2 text-zinc-400 hover:bg-zinc-100 hover:text-red-600" @click="removeArticle(index)">
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

                    <!-- Special items -->
                    <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between gap-3 border-b border-zinc-100 px-6 py-4 rounded-t-2xl">
                            <div>
                                <h3 class="text-base font-semibold text-zinc-900 flex items-center gap-2">
                                    <span class="inline-block size-2 rounded-full bg-violet-500"></span>
                                    {{ $t('Special items') }}
                                </h3>
                                <p class="text-xs text-zinc-500">{{ $t('Add items that are not listed in the system.') }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="flex items-center gap-2 text-xs text-zinc-700">
                                    <input type="checkbox" v-model="externMaterialIssueForm.special_items_done" class="h-4 w-4 rounded border-zinc-300 text-violet-600 focus:ring-violet-500" />
                                    <span>{{ $t('Special items done') }}</span>
                                </label>
                                <button type="button" class="inline-flex items-center gap-1 rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-violet-700" @click="addSpecialItem">
                                    <component :is="IconPlus" class="h-3.5 w-3.5" />
                                    {{ $t('Add special article') }}
                                </button>
                            </div>
                        </div>

                        <div class="max-h-[26rem] overflow-y-auto p-6">
                            <div class="divide-y divide-dashed divide-zinc-200">
                                <div v-for="(article, index) in externMaterialIssueForm.special_items" :key="index" class="py-3">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-9">
                                        <BaseInput :id="'special-article-name-' + index" type="text" v-model="article.name" :label="$t('Article Name')" class="md:col-span-6" />
                                        <BaseInput :id="'special-article-quantity' + index" type="number" v-model="article.quantity" :label="$t('Quantity')" class="md:col-span-2" />
                                        <div class="flex items-center justify-center">
                                            <button type="button" class="rounded-md p-2 text-zinc-400 hover:bg-zinc-100 hover:text-red-600" @click="removeSpecialArticle(index)">
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
            <section class="rounded-2xl border border-zinc-200 bg-white shadow-sm">
                <div class="border-b border-zinc-100 px-6 py-4 rounded-t-2xl bg-gradient-to-r from-emerald-50 via-emerald-50/60 to-transparent">
                    <h2 class="text-base font-semibold text-zinc-900 flex items-center gap-2">
                        <span class="inline-block size-2 rounded-full bg-emerald-500"></span>
                        {{ $t('Files for material output') }}
                    </h2>
                </div>
                <div class="p-6 grid grid-cols-1 gap-6 md:grid-cols-2 items-stretch">
                    <div>
                        <button @click="$refs.externMaterialIssueFiles.click()" type="button" class="relative block w-full max-h-56 min-h-56 rounded-2xl border-2 border-dashed border-emerald-300/70 p-10 text-center hover:border-emerald-400 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                            <component :is="IconFile" class="mx-auto size-12 text-emerald-600" stroke-width="1" />
                            <span class="mt-2 block text-sm font-semibold text-emerald-800">{{ $t('Select file') }}</span>
                            <input @change="upload" class="hidden" ref="externMaterialIssueFiles" id="file" type="file" multiple />
                        </button>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 max-h-56 min-h-56 overflow-y-auto">
                        <div v-if="props.externMaterialIssue?.files?.length" class="space-y-2">
                            <div v-for="(file, index) in props.externMaterialIssue.files" :key="'existing-' + index" class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-white px-3 py-2">
                                <!-- Thumbnail für Bilddateien -->
                                <div v-if="isImageFile(file.original_name)" class="shrink-0">
                                    <div class="overflow-hidden rounded border border-zinc-200 shadow-sm" style="width: 40px; height: 40px;">
                                        <img :src="'/storage/' + file.file_path" :alt="file.original_name" class="block h-full w-full object-cover" @error="(e) => e.target.src = usePage().props.big_logo" />
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <a :href="'/storage/' + file.file_path" target="_blank" download class="truncate text-sm font-medium text-blue-700 hover:underline">
                                        {{ file.original_name }}
                                    </a>
                                </div>
                                <button type="button" class="rounded-md p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-red-600" @click="removeFile(file.id)">
                                    <component :is="IconTrash" class="h-4 w-4" stroke-width="1.5" />
                                </button>
                            </div>
                        </div>

                        <div v-if="externMaterialIssueForm.files?.length" class="mt-3 space-y-2">
                            <div v-for="(file, index) in externMaterialIssueForm.files" :key="'new-' + index" class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-white px-3 py-2">
                                <!-- Thumbnail für neue Bilddateien -->
                                <div v-if="isImageFile(file.name || file.original_name) && filePreviewUrl(file)" class="shrink-0">
                                    <div class="overflow-hidden rounded border border-zinc-200 shadow-sm" style="width: 40px; height: 40px;">
                                        <img :src="filePreviewUrl(file)" :alt="file.name || file.original_name" class="block h-full w-full object-cover" @error="(e) => e.target.src = usePage().props.big_logo" />
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h4 class="truncate text-sm font-medium">{{ file.name ?? file.original_name }}</h4>
                                    <p v-if="file.size" class="text-[11px] text-zinc-500">{{ (file.size / 1024 / 1024).toFixed(2) }} MB</p>
                                </div>
                                <button type="button" class="rounded-md p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-red-600" @click="externMaterialIssueForm.files.splice(index, 1)">
                                    <component :is="IconTrash" class="h-4 w-4" stroke-width="1.5" />
                                </button>
                            </div>
                        </div>

                        <div v-if="!props.externMaterialIssue?.files?.length && !externMaterialIssueForm.files?.length" class="grid h-full place-items-center text-xs text-zinc-500">
                            {{ $t('No files selected') }}
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Sticky Action Bar -->
        <div class="sticky bottom-0 z-40 mt-8 -mx-4 md:-mx-6 bg-gradient-to-t from-white via-white/80 to-transparent pt-4">
            <div class="mx-auto max-w-7xl px-4 md:px-6">
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-zinc-200 bg-white/90 p-3 backdrop-blur shadow-sm">
                    <div class="text-xs text-zinc-600">
                        {{ $t('Selected') }}: <span class="font-medium">{{ externMaterialIssueForm.articles?.length || 0 }}</span> {{ $t('articles') }} • {{ $t('Files') }}: <span class="font-medium">{{ externMaterialIssueForm.files?.length || 0 }}</span>
                    </div>
                    <FormButton :text="externMaterialIssue?.id ? $t('Update') : $t('Save')" :disabled="externMaterialIssueForm.processing || !externMaterialIssueForm.issue_date || !externMaterialIssueForm.return_date || !externMaterialIssueForm.material_value || isReturnDateBeforeIssueDate" type="submit" />
                </div>
            </div>
        </div>

        <!-- Single global Galleria for lightbox -->
        <Galleria
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

    <ArticleDetailModal :article="articleForDetailModal" v-if="articleForDetailModal" @close="articleForDetailModal = null" :show-button-for-edit-and-delete="false" />

    <ArticleUsageModal :details-for-modal="articleForUsageModal" v-if="articleForUsageModal" @close="articleForUsageModal = null" />
</template>

<script setup>

import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import RoomSearch from "@/Components/SearchBars/RoomSearch.vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import {XIcon} from "@heroicons/vue/outline";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import ArticleSearchFilterModal from "@/Pages/IssueOfMaterial/Components/ArticleSearchFilterModal.vue";
import ProjectSearch from "@/Components/SearchBars/ProjectSearch.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import {router, useForm, usePage} from "@inertiajs/vue3";
import {computed, nextTick, onMounted, ref, watch} from "vue";
import debounce from "lodash.debounce";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import SelectMaterialSetModal from "@/Pages/IssueOfMaterial/Components/SelectMaterialSetModal.vue";
import InventoryFunctionBarFilter from "@/Artwork/Filter/InventoryFunctionBarFilter.vue";
import axios from "axios";
import ArticleUsageModal from "@/Pages/Inventory/Components/Planning/ArticleUsageModal.vue";
import ArticleDetailModal from "@/Pages/Inventory/Components/Article/Modals/ArticleDetailModal.vue";
import Galleria from "primevue/galleria";
import {
    IconFile,
    IconInfoCircle,
    IconListDetails,
    IconLoader, IconParentheses,
    IconPlus,
    IconTrash,
    IconWindowMaximize
} from "@tabler/icons-vue";

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
            issued_by_id: null
        })
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
    issued_by_id: props.externMaterialIssue?.issued_by_id || null
})

const showArticleFilterModal = ref(false)
const showSelectMaterialSetModal = ref(false)
const issueBy = ref(props.externMaterialIssue?.issued_by || null)

// Artikel Pagination State (aligned with internal)
const articles = ref([])
const loadingMore = ref(false)
const scrollContainer = ref(null)
const hasMoreArticles = ref(true)
const paginationPage = ref(1)
const articleForDetailModal = ref(null);
const articleForUsageModal = ref(null);
const articleSearchFilter = ref("");

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

// Filtered articles based on search input
const filteredArticles = computed(() => {
    if (!articleSearchFilter.value) {
        return articles.value;
    }

    const searchTerm = articleSearchFilter.value.toLowerCase();
    return articles.value.filter(article =>
        article.name?.toLowerCase().includes(searchTerm)
    );
})

// Scrollt zur ersten konfliktbehafteten Zeile und hebt sie kurz hervor
function scrollToFirstConflict () {
    if (!conflicts.value.length) return
    nextTick(() => {
        const idx = conflicts.value[0].index
        const el = document.querySelector(`[data-article-row="${idx}"]`) | null
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' })
            el.classList.add('ring-2','ring-red-400','rounded-xl')
            setTimeout(() => el.classList.remove('ring-2','ring-red-400','rounded-xl'), 1400)
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
        // Article exists, don't modify its quantity
        // Just ensure it has the correct properties
        const existingArticle =
            externMaterialIssueForm.articles[existingArticleIndex];
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

const reloadArticlesWithNewFilter = async () => {
    articles.value = []
    loadingMore.value = true
    paginationPage.value = 1

    await loadMoreArticles()
}

const getArticleDataForUsage = async (article) => {
    if (!article?.id || !externMaterialIssueForm.issue_date || !externMaterialIssueForm.return_date) {
        return;
    }
    article.availableStockRequestIsLoading = true;
    try {
        const response = await axios.get(route('inventory.articles.usage'), {
            params: {
                article_id: article.id,
                start_date: externMaterialIssueForm.issue_date,
                end_date: externMaterialIssueForm.return_date,
            }
        });
        // Die Nutzungsdaten werden im Modal angezeigt
        articleForUsageModal.value = response.data.data;
    } catch (error) {
        console.error('Fehler beim Abrufen der Artikel-Nutzungsdaten:', error);
    } finally {
        article.availableStockRequestIsLoading = false;
    }
};

const loadMoreArticles = async () => {
    loadingMore.value = true

    try {
        const response = await axios.get(route('inventory.articles.api', {
            page: paginationPage.value,
            start_date: externMaterialIssueForm.issue_date,
            end_date: externMaterialIssueForm.return_date,
        }))

        const newArticles = response.data.articles.data.reverse()

        for (const article of newArticles) {
            const exists = articles.value.find((a) => a.id === article.id)
            if (!exists) {
                articles.value.push(article)
            }
        }

        if (!response.data.articles.next_page_url) {
            hasMoreArticles.value = false
            paginationPage.value = 1
        }
    } catch (e) {
        console.error('Fehler beim Nachladen von Nachrichten:', e)
    }
    paginationPage.value += 1
    loadingMore.value = false
}

const removeArticle = (index) => {
    externMaterialIssueForm.articles.splice(index, 1)
}
const emits = defineEmits(['close'])

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
                emits('close')
            }
        })
    } else {
        externMaterialIssueForm.post(route('extern-issue-of-material.store'), {
            onSuccess: () => {
                emits('close')
            }
        })
    }
}


const checkAvailableStock = async () => {
    if (!externMaterialIssueForm.issue_date || !externMaterialIssueForm.return_date || externMaterialIssueForm.articles.length === 0) {
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
                start_date: externMaterialIssueForm.issue_date,
                end_date: externMaterialIssueForm.return_date,
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
    if(props.externMaterialIssue.articles.length > 0){
        checkAvailableStock()
    }
    loadMoreArticles()
})
</script>

<style scoped>

</style>
