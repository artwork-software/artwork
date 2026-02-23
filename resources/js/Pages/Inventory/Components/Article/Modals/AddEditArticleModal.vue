<template>
    <ArtworkBaseModal @close="$emit('close')" :modal-size="articleForm.is_detailed_quantity ? 'max-w-7xl' : 'max-w-4xl'"
                      full-modal :title="article ? $t('Edit article') : $t('Add Article')"
                      :description="article ? $t('Edit the article details') : $t('Add a new article')">
        <form @submit.prevent="submit">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 px-6 pb-4">
                <div class="col-span-1">
                    <div @click="addImage"
                         class="relative block w-full rounded-lg border-2 border-dashed border-gray-300 p-12 cursor-pointer text-center hover:border-gray-400 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden">
                        <component :is="IconPhotoPlus" class="mx-auto size-12 text-gray-400" aria-hidden="true"/>
                        <span class="mt-2 block text-sm font-semibold text-gray-900">{{ $t('Upload Images') }}</span>
                        <input type="file" accept="image/*" class="sr-only" ref="articleImageInput" multiple
                               @input="handleImageInput"/>
                    </div>
                </div>
                <div class="col-span-3">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div v-for="(image, index) in allImages" :key="image._origin === 'old' ? image.id : index"
                             class="relative p-2 rounded-lg border bg-white shadow-sm hover:border-yellow-500 cursor-pointer transition duration-200 ease-in-out"
                             @click="currentMainImage = index"
                             :class="currentMainImage === index ? 'border-yellow-400' : 'border-gray-200'">
                            <XCircleIcon @click.stop="removeImage(image)"
                                         class="absolute top-1 right-1 text-artwork-buttons-create h-5 w-5 hover:text-error "/>
                            <div class="flex flex-col items-center justify-center w-full truncate min-h-16 gap-y-2">
                                <div v-if="image._origin === 'old'">
                                    <img :src="createImageURL(image)" alt="New image preview"
                                         class="w-full h-24 object-cover"/>
                                </div>
                                <div v-else>
                                    <img :src="createImageURL(image)" alt="New image preview"
                                         class="w-full h-24 object-cover"/>
                                </div>
                                <div class="flex w-full justify-center">
                                    <div class="truncate font-medium font-lexend text-xs">
                                        {{ image._origin === 'old' ? image.image.split('/').pop() : image.name }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 pb-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="col-span-full">
                        <BaseInput
                            id="name" v-model="articleForm.name"
                            :label="$t('Name*')"
                            required
                        />
                    </div>

                    <div class="col-span-full">
                        <BaseTextarea
                            id="description"
                            v-model="articleForm.description"
                            :label="$t('Description')"
                        />
                    </div>
                </div>
            </div>

            <!-- tags -->
            <div class="px-6 pb-4 border-t border-gray-100 pt-5">
                <BasePageTitle
                    :title="$t('Tags')"
                    :description="$t('Use tags to better organize this article. Depending on tag permissions, saving may be restricted. You can only assign tags for which you have permission.')"
                />

                <!-- Ausgewählte Tags -->
                <div class="mt-3 flex flex-wrap gap-2 min-h-[2rem]">
                    <button
                        v-for="tag in selectedTags"
                        :key="tag.id"
                        type="button"
                        class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium border bg-white transition"
                        :style="{
                            backgroundColor: (tag.color || '#4f46e5') + '10',
                            borderColor: (tag.color || '#4f46e5') + '40',
                            color: tag.color || '#4f46e5'
                        }"
                        @click="toggleTag(tag)">
                    <span class="inline-flex h-2 w-2 rounded-full" :style="{ backgroundColor: tag.color || '#4f46e5' }"/>
                        <span class="truncate max-w-[150px]">{{ tag.name }}</span>
                        <IconLock v-if="tag.has_restricted_permissions" class="h-3 w-3"/>
                        <span class="ml-1 text-[10px] opacity-70 hover:opacity-100" @click.stop="toggleTag(tag)">×</span>
                    </button>

                    <span v-if="!selectedTags.length" class="text-[11px] text-gray-400">
                        {{ $t('No tags assigned yet. Select tags from the list below.') }}
                    </span>
                </div>

                <!-- Tag-Suche & Auswahl -->
                <div class="mt-4 space-y-3">
                    <BaseInput
                        v-model="tagSearch"
                        :label="$t('Search tags')"
                        :placeholder="$t('Search by tag name')"
                        id="tag-search"
                    />

                    <div class="max-h-44 overflow-y-auto rounded-xl border border-gray-100 bg-gray-50 px-3 py-2 text-xs">
                        <template v-for="group in tagGroupsForSelection" :key="group.key">
                            <p class="mt-2 mb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400 flex items-center gap-2">
                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-md bg-white shadow-sm">
                                    <IconTag class="h-3.5 w-3.5 text-gray-400" />
                                </span>
                                <span class="truncate">
                                    {{ group.label }}
                                </span>
                            </p>

                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="tag in group.tags"
                                    :key="tag.id"
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 bg-white hover:bg-gray-50 transition"
                                    :class="{
                                        'opacity-40 cursor-not-allowed': tag.has_restricted_permissions && !userCanUseTag(tag)
                                    }"
                                    :style="{ borderColor: (tag.color || '#4f46e5') + '40' }"
                                    @click="canSelectTag(tag) && toggleTag(tag)">
                                    <span class="inline-flex h-1.5 w-1.5 rounded-full" :style="{ backgroundColor: tag.color || '#4f46e5' }"/>
                                    <span class="truncate max-w-[140px]">{{ tag.name }}</span>
                                    <IconLock v-if="tag.has_restricted_permissions" class="h-3 w-3" :class="userCanUseTag(tag) ? 'text-amber-500' : 'text-gray-300'"/>
                                </button>
                            </div>
                        </template>

                        <p v-if="!tagGroupsForSelection.length || !tagGroupsForSelection.some(g => g.tags.length)" class="text-[11px] text-gray-400 mt-1">
                            {{ $t('No tags match your search.') }}
                        </p>
                    </div>
                </div>

                <!-- Hinweis bei fehlenden Berechtigungen -->
                <BaseAlertComponent
                    v-if="forbiddenTags.length"
                    class="mt-3"
                    type="error"
                    :message="$t('You do not have permission to use one or more of the selected tags. Please remove them or contact your administrator.')"
                />
            </div>

            <div class="bg-gray-50 px-10 -mx-4 py-6 mb-5">
                <div class="mb-5">
                    <ArtworkBaseListbox
                        v-model="selectedCategory"
                        :items="categories"
                        by="id"
                        option-label="name"
                        option-key="id"
                        :label="$t('Select Category')"
                        :placeholder="$t('Please select a Category')"
                    />
                </div>
                <div class="pb-4" v-if="selectedCategory && selectedCategory.subcategories.length > 0">
                    <ArtworkBaseListbox
                        v-model="selectedSubCategory"
                        :items="selectedCategory.subcategories"
                        by="id"
                        option-label="name"
                        option-key="id"
                        :label="$t('Select Sub-Category')"
                        :placeholder="$t('Please select a Sub-Category')"
                        @change="val => updateSelectedSubCategory(val)"
                    />
                    <div class="flex items-center justify-end mt-3" v-if="selectedSubCategory">
                        <div
                            class="text-xs text-artwork-buttons-create underline underline-offset-4 hover:text-artwork-buttons-hover duration-200 ease-in-out cursor-pointer"
                            @click="selectedSubCategory = null">{{ $t('Remove the sub-category assignment') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 pb-5" v-if="selectedCategory">
                <div class="grid grid-cols-6 gap-x-4">
                    <div class="col-span-3">
                        <BaseInput
                            type="number"
                            id="quantity" v-model="articleForm.quantity"
                            :label="$t('Total quantity*')"
                            :max="10000000"
                            :maxlength="1000000"
                            required
                        />
                    </div>
                    <div class="col-span-3">
                        <div class="mb-4">
                            <BaseCheckbox
                                v-model="articleForm.is_detailed_quantity"
                                :label="$t('Single inventory capable')"
                                :description="$t('If activated, each individual piece of this article can be provided with its own properties')"
                            />
                        </div>
                        <!--<div class="flex gap-3 w-full" v-if="selectedCategory">
                            <div class="flex h-6 shrink-0 items-center">
                                <div class="group grid size-4 grid-cols-1">
                                    <input id="is_detailed_quantity" aria-describedby="is_detailed_quantity-description"
                                           v-model="articleForm.is_detailed_quantity" name="is_detailed_quantity"
                                           type="checkbox" class="aw-checklist-input"/>
                                    <svg class="aw-input-svg" viewBox="0 0 14 14" fill="none">
                                        <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                            </div>
                            <div class="text-sm/6">
                                <label for="is_detailed_quantity" class="font-medium text-gray-900">
                                    {{ $t('Single inventory capable') }}
                                </label>
                                <p id="is_required-description" class="text-gray-500">
                                    {{
                                        $t('If activated, each individual piece of this article can be provided with its own properties')
                                    }}
                                </p>
                            </div>
                        </div>-->
                    </div>
                </div>

                <div v-if="!articleForm.is_detailed_quantity && selectedCategory" class="ml-4 relative">
                    <div v-for="(statusValue, index) in articleForm.statusValues">
                        <div v-if="statusValue.id !== 5" class="grid grid-cols-2 gap-x-4 mb-3">
                            <div class="flex items-center">
                                <div class="absolute top-0 left-0 w-px h-[90%] bg-gray-300"></div>
                                <div class="font-lexend text-sm flex items-center text-secondary">
                                    <div class="w-5 h-px bg-gray-300"></div>
                                    <div class="ml-4 text-primary">{{ statusValue.name }}</div>
                                </div>
                            </div>
                            <div>
                                <BaseInput
                                    type="number"
                                    :id="'quantity-' + statusValue.id" v-model="statusValue.value"
                                    :label="$t('Quantity')"
                                    :max="10000000"
                                    :maxlength="1000000"
                                    required
                                />
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="calculateStatusQuantityInArticle > articleForm.quantity || calculateStatusQuantityInArticle < articleForm.quantity">
                        <p class="text-red-500 font-lexend text-sm mt-2"
                           v-if="calculateStatusQuantityInArticle > articleForm.quantity">
                            {{
                                $t('The sum of the quantities of the status values exceeds the total quantity of the article')
                            }}
                        </p>
                        <p class="text-red-500 font-lexend text-sm mt-2"
                           v-if="calculateStatusQuantityInArticle < articleForm.quantity">
                            {{
                                $t('The sum of the quantities of the status values falls below the total quantity of the article')
                            }}
                        </p>
                        <div class="flex items-center justify-between font-lexend text-sm mt-1">
                            <span>{{ $t('Total quantity') }}:</span>
                            <span
                                v-if="calculateStatusQuantityInArticle > articleForm.quantity || calculateStatusQuantityInArticle < articleForm.quantity"
                                @click="articleForm.quantity = calculateStatusQuantityInArticle"
                                class="flex items-center gap-x-0.5  cursor-pointer">
                                   <ToolTipWithTextComponent
                                       :text="formatQuantity(calculateStatusQuantityInArticle)"
                                       classes="text-artwork-buttons-create"
                                       icon-right
                                       stroke="2"
                                       :icon="IconClick"
                                       icon-size="size-4"
                                       :tooltip-text="$t('Click to set the article quantity to the detailed article quantity')"/>
                            </span>
                            <span class="font-bold"
                                  v-else>{{ formatQuantity(calculateStatusQuantityInArticle) ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>



            <!-- category properties -->
            <div class="px-6"
                 v-if="articleForm.properties.length > 0 && selectedCategory && !articleForm.is_detailed_quantity">
                <div>
                    <BasePageTitle
                        :title="$t('Category & subcategory based properties')"
                        :description="$t('These properties are based on the selected category and subcategory, here you can delete unwanted properties for this article and set the values for the others')"
                    />
                </div>
                <div class="my-8 flow-root pb-4" v-if="articleForm.properties.length > 0">
                    <div class="-my-2">
                        <div class="inline-block min-w-full py-2 align-middle">
                            <table class="min-w-full divide-y divide-gray-300">
                                <thead>
                                <tr class="divide-x divide-gray-200">
                                    <th scope="col"
                                        class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-0">
                                        Name
                                    </th>
                                    <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        {{ $t('Type') }}
                                    </th>
                                    <th scope="col"
                                        class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pr-0">
                                        {{ $t('Value') }}
                                    </th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="property in articleForm.properties" :key="property?.id"
                                    class="divide-x divide-gray-200">
                                    <td class="py-4 pr-4 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-0 first-letter:capitalize">
                                        <div class="flex items-center justify-between">
                                            {{ property?.name }}
                                            <div class="flex items-center gap-x-2">
                                                <ToolTipComponent
                                                    v-if="property?.tooltip_text"
                                                    :tooltip-text="property?.tooltip_text"
                                                    :icon="IconInfoCircle"
                                                    icon-size="size-4"
                                                    direction="top"
                                                    tooltipCssClass="break-all !text-xs"
                                                />
                                                <!--<component is="IconTrash" class="h-5 w-5 text-red-600 cursor-pointer"
                                                           v-if="property.categoryProperty"
                                                           @click="articleForm.properties = articleForm.properties.filter(prop => prop.id !== property.id)"/>-->
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 text-sm whitespace-nowrap text-gray-500 capitalize xsLight cursor-default">
                                        {{ $t(capitalizeFirstLetter(property?.type)) }}
                                    </td>

                                    <td class="text-sm whitespace-nowrap text-gray-500 sm:pr-0">
                                        <InventoryStylelessCombobox
                                            v-if="property.type === 'room'"
                                            v-model="property.value"
                                            :items="rooms"
                                            :returnObject="false"
                                            by="id"
                                            option-label="name"
                                            option-key="id"
                                            :placeholder="$t('Please select a Room')"
                                            :label="$t('Room')"
                                            :search-fields="['name']"
                                            coerce="number"
                                        />

                                        <InventoryStylelessCombobox
                                            v-if="property.type === 'manufacturer'"
                                            v-model="property.value"
                                            :items="manufacturers"
                                            :returnObject="false"
                                            by="id"
                                            option-label="name"
                                            option-key="id"
                                            :placeholder="$t('Please select a Manufacturer')"
                                            :label="$t('Manufacturer')"
                                            :search-fields="['name']"
                                            coerce="number"
                                        />

                                        <input
                                            v-if="property.type !== 'file' && property.type !== 'checkbox' && property.type !== 'room' && property.type !== 'manufacturer' && property.type !== 'selection'"
                                            :type="property.type" v-model="property.value"
                                            :required="property.is_required"
                                            class="block w-full rounded-md bg-white border-none text-xs px-3 py-1.5 text-gray-900 outline-0 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-0 ring-0 focus:ring-0"
                                            :placeholder="property.is_required ? $t('Value*') : $t('Value')"
                                        />

                                        <div v-if="property.type === 'file'">
                                            <input type="file" @input="property.value = $event.target.files"
                                                   class="sr-only"/>
                                            <div class="flex items-center gap-x-2">
                                                <div class="flex items-center gap-x-2">
                                                    <component :is="IconPhoto" class="size-5 shrink-0 text-gray-400"
                                                               aria-hidden="true"/>
                                                    <div class="flex">
                                                        <div class="truncate font-medium">{{
                                                                property.value ? property.value[0].name : $t('Select a file')
                                                            }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="button"
                                                        class="text-gray-400 hover:text-red-600 hover:animate-pulse duration-200 ease-in-out"
                                                        @click="property.value = null">
                                                    <component :is="IconTrash" class="h-5 w-5" aria-hidden="true"/>
                                                </button>
                                            </div>
                                        </div>


                                        <div v-if="property.type === 'checkbox'" class="px-3">
                                            <input type="checkbox" :checked="booleanValue(property.value)"
                                                   @change="property.value = $event.target.checked" class="input-checklist"/>
                                        </div>


                                        <div v-if="property.type === 'selection'" class="">
                                            <div class="grid grid-cols-1 p-2">
                                                <select id="location" name="location" v-model="property.value"
                                                        :required="property.is_required"
                                                        class="block w-full rounded-md bg-white border-none text-xs py-1.5 cursor-pointer text-gray-900 outline-0 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-0 ring-0 focus:ring-0">
                                                    <option v-if="property.is_required" value="" disabled selected>
                                                        {{ $t('Please select') }}*
                                                    </option>
                                                    <option v-for="value in property.select_values" :value="value" :key="value">{{ value }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>


                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>


            <div class="px-6 bg-gray-50 py-6 hidden">
                <div class="flex gap-3">
                    <div class="flex h-6 shrink-0 items-center">
                        <div class="group grid size-4 grid-cols-1">
                            <input id="is_detailed_quantity" aria-describedby="is_detailed_quantity-description"
                                   v-model="articleForm.is_detailed_quantity" name="is_detailed_quantity"
                                   type="checkbox" class="input-checklist"/>
                        </div>
                    </div>
                    <div class="text-sm/6">
                        <label for="is_detailed_quantity" class="font-medium text-gray-900">
                            {{ $t('Single inventory capable')}}
                        </label>
                        <p id="is_required-description" class="text-gray-500">
                            {{
                                $t('If activated, each individual piece of this article can be provided with its own properties')
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Detailed quantity -->
            <div class="px-6" v-if="articleForm.is_detailed_quantity">
                <!-- Neu: zentrale, artikelübergreifende Eigenschaften -->
                <div v-if="acrossProperties.length" class="mb-6">
                    <BasePageTitle
                        :title="$t('Across-article properties')"
                        :description="$t('These properties are shared across all detailed articles and can be edited here once')"
                    />
                    <div class="mt-4 bg-gray-50 rounded-lg border border-gray-200 p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="col-span-1" v-for="prop in acrossProperties" :key="prop.id">
                                <div class="flex items-center justify-between">
                                    <div class="block text-xs font-medium text-gray-600 py-1.5">
                                        {{ prop.name }} <span v-if="prop.is_required">*</span>
                                    </div>
                                    <div>
                                        <ToolTipComponent
                                            v-if="prop.tooltip_text"
                                            :tooltip-text="prop.tooltip_text"
                                            :icon="IconInfoCircle" icon-size="size-4"
                                            direction="left"
                                        />
                                    </div>
                                </div>

                                <!-- room -->
                                <InventoryCombobox
                                    v-if="prop.type === 'room'"
                                    v-model="acrossValues[prop.id]"
                                    :items="rooms"
                                    :return-object="false"
                                    by="id"
                                    option-label="name"
                                    option-key="id"
                                    :placeholder="$t('Please select a Room')"
                                    :search-fields="['name']"
                                    coerce="number"
                                />

                                <!-- manufacturer -->
                                <InventoryCombobox
                                    v-else-if="prop.type === 'manufacturer'"
                                    v-model="acrossValues[prop.id]"
                                    :items="manufacturers"
                                    :return-object="false"
                                    by="id"
                                    option-label="name"
                                    option-key="id"
                                    :placeholder="$t('Please select a Manufacturer')"
                                    :search-fields="['name']"
                                    coerce="number"
                                />
                                <!-- selection -->
                                <div v-else-if="prop.type === 'selection'">
                                    <div class="grid grid-cols-1 p-2">
                                        <select
                                            v-model="acrossValues[prop.id]"
                                            :required="prop.is_required"
                                            class="block w-full rounded-md bg-white border-none text-xs py-1.5 cursor-pointer text-gray-900 outline-0 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-0 ring-0 focus:ring-0"
                                        >
                                            <option v-if="prop.is_required" value="" disabled selected>
                                                {{ $t('Please select') }}*
                                            </option>
                                            <option v-for="value in prop.select_values" :value="value" :key="value">
                                                {{ value }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <!-- file -->
                                <div v-else-if="prop.type === 'file'">
                                    <input type="file" class="sr-only" @input="setAcrossValue(prop.id, $event.target.files)" />
                                    <div class="flex items-center gap-x-2">
                                        <div class="flex items-center gap-x-2">
                                            <component :is="IconPhoto" class="size-5 shrink-0 text-gray-400" aria-hidden="true"/>
                                            <div class="flex">
                                                <div class="truncate font-medium">
                                                    {{ acrossValues[prop.id]?.[0]?.name ?? $t('Select a file') }}
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button"
                                                class="text-gray-400 hover:text-red-600 hover:animate-pulse duration-200 ease-in-out"
                                                @click="setAcrossValue(prop.id, null)">
                                            <component :is="IconTrash" class="h-5 w-5" aria-hidden="true"/>
                                        </button>
                                    </div>
                                </div>

                                <!-- checkbox -->
                                <div v-else-if="prop.type === 'checkbox'" class="px-3 items-center flex">
                                    <div class="flex gap-3">
                                        <div class="flex h-6 shrink-0 items-center">
                                            <div class="group grid size-4 grid-cols-1">
                                                <input
                                                    :checked="booleanValue(acrossValues[prop.id])"
                                                    @change="setAcrossValue(prop.id, $event.target.checked)"
                                                    type="checkbox" class="aw-checklist-input"
                                                    :id="'across-property-' + prop.id"
                                                />
                                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25 dark:group-has-disabled:stroke-white/25" viewBox="0 0 14 14" fill="none">
                                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- default input -->
                                <BaseInput
                                    v-else
                                    :id="'across-property-' + prop.id"
                                    :type="prop.type"
                                    v-model="acrossValues[prop.id]"
                                    :required="prop.is_required"
                                    :label="prop.is_required ? $t('Value*') : $t('Value')"
                                />
                            </div>
                        </div>
                    </div>
                </div>


                <div class="mb-4">
                    <BasePageTitle
                        :title="$t('Detailed article properties')"
                        :description="$t('These properties apply to each individual article. Select the desired article from the list on the left or add new ones and maintain their individual properties on the right.')"
                    />
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_1.8fr] items-stretch">
                    <div class="h-full min-h-0 flex flex-col bg-gray-50 rounded-lg border border-gray-200 insert-shadow-md">
                        <div class="space-y-2 p-4">
                            <div>
                                <BaseInput
                                    v-model="searchDetailedArticleQuery"
                                    id="search_detailed_article"
                                    :label="$t('Search for detailed articles')"/>
                            </div>

                            <!-- NEU: Auswahl- und Multi-Action-Leiste -->
                            <div class="flex items-center justify-between mt-1">
                                <div class="flex gap-1.5">
                                    <div class="flex h-6 shrink-0 items-center">
                                        <div class="group grid size-4 grid-cols-1">
                                            <input
                                                id="allVisibleSelected"
                                                aria-describedby="allVisibleSelected-description"
                                                name="comments"
                                                type="checkbox"
                                                :checked="allVisibleSelected"
                                                @change="toggleSelectAllVisible($event.target.checked)"
                                                class="aw-checklist-input" />
                                            <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25 dark:group-has-disabled:stroke-white/25" viewBox="0 0 14 14" fill="none">
                                                <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="text-sm/6">
                                        <label for="allVisibleSelected" class="text-xs text-gray-900 dark:text-white">{{ $t('Select all') }}</label>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3" v-if="hasSelection">
                                    <span class="text-xs text-gray-500">{{ selectionCount }}</span>
                                    <button
                                        type="button"
                                        class="text-artwork-buttons-create text-xs hover:text-artwork-buttons-hover duration-200 ease-in-out cursor-pointer"
                                        @click="openBulkEdit"
                                    >
                                        {{ $t('Edit selection') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="text-red-600 text-xs hover:text-red-700 duration-200 ease-in-out cursor-pointer"
                                        @click="confirmMultiEditDeleteModalOpen = true"
                                    >
                                        {{ $t('Delete selection') }}
                                    </button>
                                </div>
                            </div>

                            <!-- NEU: Bulk-Edit-Panel -->
                            <div v-if="bulkEdit.open" class="mt-2 border border-gray-200 rounded-md bg-white p-3">
                                <div class="grid grid-cols-1 gap-3">
                                    <div class="col-span-full">
                                        <div
                                            class="px-3 py-3 text-sm block w-full font-lexend shadow-sm border border-gray-200 rounded-md placeholder-transparent focus:outline-none focus:ring-1 focus:ring-artwork-buttons-create focus:border-artwork-buttons-create">
                                            <label class="block text-[10px] font-medium text-gray-700 pl-1 pb-1">
                                                {{ $t('Status') }}
                                            </label>
                                            <select class="focus:outline-hidden w-full"
                                                    v-model="bulkEdit.status">
                                                <option :value="null">{{ $t('Do not change') }}</option>
                                                <option v-for="status in statuses" :value="status" :key="status.id">
                                                    {{ status.name }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-span-full">
                                        <BaseInput
                                            type="number"
                                            id="bulk_quantity"
                                            v-model="bulkEdit.quantity"
                                            :label="$t('Quantity')"
                                            :max="10000000"
                                            :maxlength="1000000"
                                        />
                                    </div>

                                    <!-- NEU: Property-Auswahl (nur nicht-across_articles, Schnittmenge der Auswahl) -->
                                    <div class="col-span-full" v-if="bulkEditableProperties.length">
                                        <div
                                            class="px-3 py-3 text-sm block w-full font-lexend shadow-sm border border-gray-200 rounded-md placeholder-transparent focus:outline-none focus:ring-1 focus:ring-artwork-buttons-create focus:border-artwork-buttons-create">
                                            <label class="block text-[10px] font-medium text-gray-700 pl-1 pb-1">
                                                {{ $t('Property') }}
                                            </label>
                                            <select class="focus:outline-hidden w-full"
                                                    v-model="bulkEdit.propertyId">
                                                <option :value="null">{{ $t('Do not change') }}</option>
                                                <option v-for="p in bulkEditableProperties" :key="p.id" :value="p.id">
                                                    {{ p.name }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- NEU: Property-Wert-Eingabe je nach Prop-Typ -->
                                    <div class="col-span-full" v-if="selectedBulkProp">
                                        <div
                                            class="px-3 py-3 text-sm block w-full font-lexend shadow-sm border border-gray-200 rounded-md placeholder-transparent focus:outline-none focus:ring-1 focus:ring-artwork-buttons-create focus:border-artwork-buttons-create">
                                            <label class="block text-[10px] font-medium text-gray-700 pl-1 pb-1">
                                                {{ $t('Property value') }}
                                            </label>

                                            <!-- Checkbox -->
                                            <div v-if="selectedBulkProp.type === 'checkbox'" class="px-1">
                                                <input type="checkbox"
                                                       class="aw-checklist-input"
                                                       :checked="Boolean(bulkEdit.propertyValue)"
                                                       @change="bulkEdit.propertyValue = $event.target.checked" />
                                            </div>

                                            <!-- Selection -->
                                            <div v-else-if="selectedBulkProp.type === 'selection'">
                                                <select class="focus:outline-hidden w-full"
                                                        v-model="bulkEdit.propertyValue">
                                                    <option v-for="val in selectedBulkProp.select_values" :key="val" :value="val">
                                                        {{ val }}
                                                    </option>
                                                </select>
                                            </div>

                                            <!-- Room -->
                                            <InventoryStylelessCombobox
                                                v-else-if="selectedBulkProp.type === 'room'"
                                                v-model="bulkEdit.propertyValue"
                                                :items="rooms"
                                                :returnObject="false"
                                                by="id"
                                                option-label="name"
                                                option-key="id"
                                                :placeholder="$t('Please select a Room')"
                                                :search-fields="['name']"
                                                coerce="number"
                                            />

                                            <!-- Manufacturer -->
                                            <InventoryStylelessCombobox
                                                v-else-if="selectedBulkProp.type === 'manufacturer'"
                                                v-model="bulkEdit.propertyValue"
                                                :items="manufacturers"
                                                :returnObject="false"
                                                by="id"
                                                option-label="name"
                                                option-key="id"
                                                :placeholder="$t('Please select a Manufacturer')"
                                                :search-fields="['name']"
                                                coerce="number"
                                            />

                                            <!-- Default Input -->
                                            <BaseInput
                                                v-else
                                                :type="selectedBulkProp.type"
                                                id="bulk_property_value"
                                                v-model="bulkEdit.propertyValue"
                                                :label="$t('Value')"
                                            />
                                        </div>
                                    </div>

                                    <div class="col-span-full flex justify-end gap-2">
                                        <button type="button"
                                                class="text-xs text-gray-500 hover:text-gray-700"
                                                @click="cancelBulkEdit">
                                            {{ $t('Cancel') }}
                                        </button>
                                        <button type="button"
                                                class="text-xs text-artwork-buttons-create hover:text-artwork-buttons-hover"
                                                @click="applyBulkEdit">
                                            {{ $t('Apply') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex-1 min-h-0 flex flex-col">
                            <!-- Liste -->
                            <ul class="flex-1 min-h-0 overflow-y-auto divide-y divide-gray-200 divide-dashed" role="list">
                                <li v-for="(item, idx) in filteredDetailedArticles" :key="item.id ?? item._key ?? `idx-${idx}`" class="">
                                    <!-- NEU: Checkbox + Button nebeneinander -->
                                    <div class="flex items-center group w-full cursor-pointer hover:text-artwork-buttons-create hover:bg-gray-200 focus:text-artwork-buttons-create focus:outline-hidden gap-1.5 px-4 py-2" :class="isActiveDetailedArticle(item) || isSelected(item) ? 'text-blue-500 bg-gray-100' : 'text-gray-900'">

                                        <div class="flex gap-1.5">
                                            <div class="flex h-6 shrink-0 items-center">
                                                <div class="group grid size-4 grid-cols-1">
                                                    <input
                                                        id="allVisibleSelected"
                                                        aria-describedby="allVisibleSelected-description"
                                                        name="comments"
                                                        type="checkbox"
                                                        :checked="isSelected(item)"
                                                        @change.stop="toggleSelection(item, $event.target.checked)"
                                                        class="aw-checklist-input" />
                                                    <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25 dark:group-has-disabled:stroke-white/25" viewBox="0 0 14 14" fill="none">
                                                        <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                        <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            class="flex items-center justify-between w-full"
                                            @click="changeActiveDetailedArticleForEditing(item)"
                                            @keydown.enter.prevent="changeActiveDetailedArticleForEditing(item)"
                                            @keydown.space.prevent="changeActiveDetailedArticleForEditing(item)"
                                            :aria-pressed="isActiveDetailedArticle(item)">
                                          <span class="text-sm font-medium truncate flex items-center gap-x-3">
                                            {{ item.name }}
                                          </span>

                                            <span class="flex items-center gap-x-2">
                                            <!-- Hover/Fokus-Actions -->
                                                <span class="opacity-0 group-hover:opacity-100 group-focus:opacity-100 ease-in-out duration-200 flex items-center gap-x-2">
                                                  <!-- Kopieren -->
                                                  <button
                                                      type="button"
                                                      class="text-gray-400 hover:text-gray-600 duration-200 ease-in-out"
                                                      aria-label="Kopieren"
                                                      @click.stop="copyDetailedArticle(item)"
                                                  >
                                                    <component :is="IconCopy" class="h-4 w-4" aria-hidden="true"/>
                                                  </button>

                                                    <!-- Löschen -->
                                                  <button
                                                      type="button"
                                                      class="text-gray-400 hover:text-red-600 duration-200 ease-in-out"
                                                      aria-label="Löschen"
                                                      @click.stop="removeOpenDetailedArticle(idx)"
                                                  >
                                                    <component :is="IconTrash" class="h-4 w-4" aria-hidden="true"/>
                                                  </button>
                                                </span>

                                                <!-- Menge -->
                                                <span v-if="Number(item.quantity) > 0" class="text-xs border rounded-lg px-3 py-0.5" :style="{backgroundColor: item.status.color + '20', borderColor: item.status.color + '50', color: item.status.color}">
                                                  {{ item.quantity }}
                                                </span>
                                          </span>
                                        </button>
                                    </div>
                                </li>

                                <!-- Empty State -->
                                <li v-if="articleForm.detailed_article_quantities.length === 0"
                                    class="text-red-500 text-sm px-4">
                                    {{ $t('No detailed articles found') }}
                                </li>
                            </ul>

                            <!-- Footer / Add -->
                            <div class="p-4">
                                <button
                                    type="button"
                                    class="text-artwork-buttons-create hover:text-artwork-buttons-hover duration-200 ease-in-out text-xs flex items-center gap-x-2 cursor-pointer"
                                    @click="addNewDetailedArticle">
                                    <component :is="IconPlus" class="h-5 w-5" aria-hidden="true"/>
                                    <span>{{ $t('Add Detailed Article') }}</span>
                                </button>
                            </div>


                        </div>
                    </div>
                    <div>
                        <!-- NEU: Panel nur anzeigen, wenn ein aktiver Detailed-Artikel existiert -->
                        <div v-if="activeDetailedArticleForEditing" class="grid grid-cols-1 md:grid-cols-4 gap-4 pt-4">
                            <div class="col-span-full">
                                <BaseInput
                                    :id="'name-' + activeDetailedArticleForEditing.name"
                                    v-model="activeDetailedArticleForEditing.name"
                                    :label="$t('Name*')"
                                    required
                                />
                            </div>

                            <div class="col-span-full">
                                <BaseTextarea
                                    :id="'description-' + activeDetailedArticleForEditing.name"
                                    v-model="activeDetailedArticleForEditing.description"
                                    :label="$t('Description')"
                                />
                            </div>

                            <div class="col-span-full">
                                <BaseInput
                                    type="number"
                                    :id="'quantity-' + activeDetailedArticleForEditing.name"
                                    v-model="activeDetailedArticleForEditing.quantity"
                                    :label="$t('Quantity*')"
                                    :max="10000000"
                                    :maxlength="1000000"
                                    required
                                />
                            </div>

                            <div class="col-span-full">
                                <div
                                    class="px-3 py-3 text-sm block w-full font-lexend shadow-sm border border-gray-200 rounded-md placeholder-transparent focus:outline-none focus:ring-1 focus:ring-artwork-buttons-create focus:border-artwork-buttons-create">
                                    <label for="location" class="block text-[10px] font-medium text-gray-700 pl-1 pb-1">
                                        {{ $t('Status*') }}
                                    </label>
                                    <select id="location" name="location" class=" focus:outline-hidden"
                                            v-model="activeDetailedArticleForEditing.status" required>
                                        <option value="" disabled selected>{{ $t('Please select a status') }}*</option>
                                        <option v-for="status in statuses" :value="status" :key="status.id">
                                            {{ status.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Vorher: v-for und v-if auf demselben Element -->
                            <!-- Nachher: v-for in template, v-if im inneren Div und :key gesetzt -->
                            <template v-for="property in activeDetailedArticleForEditing?.properties" :key="property?.id">
                                <div class="col-span-full" v-if="!property.across_articles">
                                    <div class="flex items-center justify-between">
                                        <div class="block text-xs font-medium text-gray-600 py-1.5">
                                            {{ property.name }} <span v-if="property.is_required">*</span>
                                        </div>
                                        <div>
                                            <ToolTipComponent
                                                v-if="property.tooltip_text"
                                                :tooltip-text="property.tooltip_text"
                                                :icon="IconInfoCircle" icon-size="size-4"
                                                direction="left"
                                            />
                                        </div>
                                    </div>

                                    <InventoryCombobox
                                        v-if="property.type === 'room'"
                                        v-model="property.value"
                                        :items="rooms"
                                        :return-object="false"
                                        by="id"
                                        option-label="name"
                                        option-key="id"
                                        :placeholder="$t('Please select a Room')"
                                        :search-fields="['name']"
                                        coerce="number"
                                    />

                                    <InventoryCombobox
                                        v-else-if="property.type === 'manufacturer'"
                                        v-model="property.value"
                                        :items="manufacturers"
                                        :return-object="false"
                                        by="id"
                                        option-label="name"
                                        option-key="id"
                                        :placeholder="$t('Please select a Manufacturer')"
                                        :search-fields="['name']"
                                        coerce="number"
                                    />

                                    <BaseInput
                                        :id="'property-' + property.id"
                                        v-else-if="property.type !== 'file' && property.type !== 'checkbox' && property.type !== 'selection'"
                                        :type="property.type" v-model="property.value"
                                        :required="property.is_required"
                                        :label="property.is_required ? $t('Value*') : $t('Value')"
                                    />

                                    <div v-else-if="property.type === 'file'">
                                        <input type="file" @input="property.value = $event.target.files" class="sr-only"/>
                                        <div class="flex items-center gap-x-2">
                                            <div class="flex items-center gap-x-2">
                                                <component :is="IconPhoto" class="size-5 shrink-0 text-gray-400" aria-hidden="true"/>
                                                <div class="flex">
                                                    <div class="truncate font-medium">
                                                        {{ property.value ? property.value[0].name : $t('Select a file') }}
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button"
                                                    class="text-gray-400 hover:text-red-600 hover:animate-pulse duration-200 ease-in-out"
                                                    @click="property.value = null">
                                                <component :is="IconTrash" class="h-5 w-5" aria-hidden="true"/>
                                            </button>
                                        </div>
                                    </div>

                                    <div v-else-if="property.type === 'checkbox'" class="px-3 items-center flex">
                                        <input type="checkbox" :checked="booleanValue(property.value)"
                                               @change="property.value = $event.target.checked"
                                               class="input-checklist"/>
                                    </div>

                                    <div v-else-if="property.type === 'selection'" class="">
                                        <div class="relative w-full">
                                            <select id="location" name="location" v-model="property.value"
                                                    :required="property.is_required"
                                                    class="block w-full font-lexend shadow-sm border border-gray-200 rounded-md focus:outline-none focus:ring-1 focus:ring-artwork-buttons-create focus:border-artwork-buttons-create transition-[box-shadow,border-color] duration-150 ease-in-out pl-4 pr-8 py-3 text-sm bg-white cursor-pointer">
                                                <option v-if="property.is_required" disabled selected>
                                                    {{ $t('Please select') }}*
                                                </option>
                                                <option v-for="value in property.select_values" :value="value" :key="value">
                                                    {{ value }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

            </div>
            <div class="flex items-center justify-center my-10">
                <BaseUIButton type="submit" :label="article ? $t('Update') : $t('Create')" is-add-button
                            :processing="articleForm.processing"
                            :disabled="!checkIfEveryPropertyWhereAreRequiredIsFilled || !selectedCategory ||
                            (articleForm.is_detailed_quantity && (calculateTotalQuantity > articleForm.quantity || calculateTotalQuantity < articleForm.quantity)) ||
                            (!articleForm.is_detailed_quantity && (calculateStatusQuantityInArticle > articleForm.quantity || calculateStatusQuantityInArticle < articleForm.quantity)) || !canSaveWithTags"
                />
            </div>
        </form>

        <ConfirmDeleteModal
            v-if="confirmMultiEditDeleteModalOpen"
            :title="$t('Delete selected articles')"
            :description="$t('Are you sure you want to delete the selected articles? This action cannot be undone.')"
            @delete="bulkDeleteSelected"
            @closed="confirmMultiEditDeleteModalOpen = false"
        />

        <!-- delete Single Article -->
        <ConfirmDeleteModal
            v-if="confirmSingleDeleteModalOpen"
            :title="$t('Delete Detailed Article')"
            :description="$t('Are you sure you want to delete this detailed article? This action cannot be undone.')"
            @delete="removeDetailedArticle"
            @closed="confirmSingleDeleteModalOpen = null"
        />

    </ArtworkBaseModal>


</template>

<script setup>

import {useForm, usePage} from '@inertiajs/vue3'
import {usePermission} from '@/Composeables/Permission.js'
import {computed, inject, onMounted, ref, watch, nextTick} from 'vue'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'
import FormButton from '@/Layouts/Components/General/Buttons/FormButton.vue'
import TinyPageHeadline from '@/Components/Headlines/TinyPageHeadline.vue'
import ToolTipWithTextComponent from '@/Components/ToolTips/ToolTipWithTextComponent.vue'
import {XCircleIcon} from '@heroicons/vue/solid'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import InventoryStylelessCombobox
    from "@/Pages/Inventory/Components/Article/Modals/Components/InventoryStylelessCombobox.vue";
import InventoryCombobox from "@/Pages/Inventory/Components/Article/Modals/Components/InventoryCombobox.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import BaseCheckbox from "@/Artwork/Inputs/BaseCheckbox.vue";
import {
    IconClick,
    IconCopy,
    IconInfoCircle,
    IconPhoto,
    IconPhotoPlus,
    IconPlus,
    IconTrash,
    IconTag,
    IconLock
} from '@tabler/icons-vue'
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import {useTranslation} from "@/Composeables/Translation.js";


const $t = useTranslation()
const acrossValues = ref({})
const props = defineProps({article: {type: Object, required: false, default: null}})

const properties = inject('properties')
const categories = inject('categories')
const rooms = inject('rooms')
const manufacturers = inject('manufacturers')
const statuses = inject('statuses')

// inject tag groups and tags
const tagGroups = inject('tagGroups', [])
const tags = inject('tags', [])

const emits = defineEmits(['close'])

const articleImageInput = ref(null)
const articleToDelete = ref(null)
const confirmMultiEditDeleteModalOpen = ref(false)
const confirmSingleDeleteModalOpen = ref(false)
const selectedCategory = ref(props.article ? categories.find(c => c.id === props.article.inventory_category_id) : null)
const selectedSubCategory = ref(props.article ? categories.find(c => c.id === props.article.inventory_category_id)?.subcategories.find(s => s.id === props.article.inventory_sub_category_id) : null)
const currentMainImage = ref(0)
const queryManufacturer = ref('')
const query = ref('')
const searchDetailedArticleQuery = ref('')
const currentPageLanguage = ref(document.documentElement.lang || 'en')
const activeId = ref(null)
const activeKey = ref(null)
const storedDetailedArticleQuantities = ref([])
/* Neu: zentrale Werte für artikelübergreifende Eigenschaften */


const filteredPeople = computed(() => query.value ? rooms.filter(r => r.name.toLowerCase().includes(query.value.toLowerCase())) : rooms)
const filteredDetailedArticles = computed(() =>
  (articleForm.detailed_article_quantities || [])
    .filter(d => (d?.name ?? '').toLowerCase().includes(searchDetailedArticleQuery.value.toLowerCase()))
)
const filteredManufacturers = computed(() => queryManufacturer.value ? manufacturers.filter(m => m.name.toLowerCase().includes(queryManufacturer.value.toLowerCase())) : manufacturers)
const itemsDetailed = computed(() => articleForm.detailed_article_quantities ?? [])

const changeActiveDetailedArticleForEditing = (d) => {
    activeDetailedArticleForEditing.value = d
}
const isActiveDetailedArticle = (d) => !!d && ((activeId.value != null && d.id === activeId.value) || (activeKey.value != null && d._key === activeKey.value))
const uid = () => Math.random().toString(36).slice(2) + Date.now().toString(36)
const ensureKeys = (arr = []) => {
    for (const a of arr) if (a && a._key == null) a._key = uid()
}
const getIsDeletable = id => properties?.find(p => p.id === id)?.is_deletable ?? false
const defaultStatus = () => statuses.find(s => s.default) ?? (statuses.length > 0 ? statuses[2] : null)

const getValue = (prop) => {
    if (prop.type === 'selection' && (prop.value === '' || prop.value == null)) return ''
    return prop.value ?? prop.pivot?.value ?? ''
}

const booleanValue = (val) => {
    if (val === true || val === 1 || val === '1' || val === 'true' || val === 'on') return true
    if (typeof val === 'string') return !['', '0', 'false'].includes(val.toLowerCase())
    return !!val
}

const findSelectValues = (propId) => properties?.find?.(p => p.id === propId)?.select_values ?? []

const articleForm = useForm({
    name: props.article?.name ?? '',
    description: props.article?.description ?? '',
    inventory_category_id: props.article?.inventory_category_id ?? null,
    inventory_sub_category_id: props.article?.inventory_sub_category_id ?? null,
    quantity: props.article?.quantity ?? 0,
    statusValues: statuses.map(s => ({
        id: s.id,
        name: s.name,
        value: props.article ? props.article.status_values.find(v => v.id === s.id)?.pivot?.value ?? 0 : 0
    })),
    is_detailed_quantity: props.article?.is_detailed_quantity ?? false,
    oldImages: [],
    newImages: [],
    removed_image_ids: [],

    // 🔴 NEU: Tags am Artikel
    tag_ids: props.article?.tags?.map(t => t.id) ?? [],
    properties: props.article ? props.article.properties.map(p => ({
        id: p.id,
        name: p.name,
        tooltip_text: p.tooltip_text,
        type: p.type,
        value: p.individual_value ? '' : getValue(p),
        is_required: p.is_required,
        categoryProperty: getIsDeletable(p.id),
        select_values: p.select_values,
        across_articles: !!p.across_articles,
        individual_value: !!p.individual_value,
    })) : [],
    detailed_article_quantities: props.article ? (props.article.detailed_article_quantities?.map(da => ({
        name: da.name, description: da.description, quantity: da.quantity,
        properties: da.properties.map(p => ({
            id: p.id,
            name: p.name,
            tooltip_text: p.tooltip_text,
            type: p.type,
            value: p.individual_value ? '' : getValue(p),
            is_required: p.is_required,
            categoryProperty: getIsDeletable(p.id),
            select_values: p.select_values,
            across_articles: !!p.across_articles,
            individual_value: !!p.individual_value,
        })) ?? [],
        status: da.status ? (statuses.find(s => s.id === da.status.id) ?? da.status) : defaultStatus(),
    })) ?? []) : [],
    main_image_index: 0,
})

const removeOpenDetailedArticle = (idx) => {
    articleToDelete.value = idx
    confirmSingleDeleteModalOpen.value = true
}

const buildProp = (src, existing = null) => {
    const isSel = src.type === 'selection'
    return {
        id: src.id,
        name: src.name,
        tooltip_text: src.tooltip_text,
        type: src.type,
        value: existing?.value ?? src.value ?? src.pivot?.value ?? '',
        is_required: !!src.is_required,
        categoryProperty: getIsDeletable(src.id),
        select_values: isSel ? (Array.isArray(src.select_values) ? src.select_values : findSelectValues(src.id)) : src.select_values,
        across_articles: !!src.across_articles,
        individual_value: !!src.individual_value,
    }
}

const mergePropsById = (currentProps = [], incoming = []) => {
    const currentMap = new Map(currentProps.map(p => [p.id, {...p}]))
    // NUR incoming Properties zurückgeben, aber mit Werten aus currentProps mergen
    return incoming.map(np => buildProp(np, currentMap.get(np.id)))
}

const applyPropsToForm = (newProps) => {
    if (articleForm.is_detailed_quantity && articleForm.detailed_article_quantities.length) {
        for (const da of articleForm.detailed_article_quantities) {
            da.properties = mergePropsById(Array.isArray(da.properties) ? da.properties : [], newProps)
        }
    } else {
        articleForm.properties = mergePropsById(articleForm.properties || [], newProps)
    }
}

const getCurrentProps = () => (articleForm.is_detailed_quantity && articleForm.detailed_article_quantities.length)
    ? (articleForm.detailed_article_quantities[0].properties || [])
    : (articleForm.properties || [])

/* Neu: berechnete Liste artikelübergreifender Eigenschaften */
const acrossProperties = computed(() => {
    if (!articleForm.is_detailed_quantity) return []
    const first = articleForm.detailed_article_quantities?.[0]
    const propsArr = first?.properties || []
    const result = []
    const seen = new Set()
    for (const p of propsArr) {
        if (p?.across_articles && !seen.has(p.id)) {
            result.push(p)
            seen.add(p.id)
        }
    }
    return result
})

/* Neu: Helper zum Setzen eines Wertes */
const setAcrossValue = (id, val) => {
    acrossValues.value = {...acrossValues.value, [id]: val}
}

/* Neu: Synchronisiert acrossValues in alle Unterartikel-Properties */
const syncAcrossValuesToDetailedArticles = () => {
    const vals = acrossValues.value || {}
    for (const da of articleForm.detailed_article_quantities || []) {
        for (const p of da.properties || []) {
            if (p.across_articles && Object.prototype.hasOwnProperty.call(vals, p.id)) {
                p.value = vals[p.id]
            }
        }
    }
}

/* Neu: Initialisiert acrossValues aus den vorhandenen Properties und hält sie aktuell */
watch(acrossProperties, (list) => {
    const map = {...acrossValues.value}
    let changed = false
    for (const p of list) {
        if (!(p.id in map)) {
            map[p.id] = p.value ?? ''
            changed = true
        }
    }
    for (const key of Object.keys(map)) {
        if (!list.some(p => String(p.id) === String(key))) {
            delete map[key]
            changed = true
        }
    }
    if (changed) acrossValues.value = map
    syncAcrossValuesToDetailedArticles()
}, {immediate: true, deep: true})

/* Neu: Reagiere auf Änderungen an acrossValues */
watch(acrossValues, () => {
    syncAcrossValuesToDetailedArticles()
}, {deep: true})



const activeDetailedArticleForEditing = computed({
    get() {
        const arr = itemsDetailed.value;
        ensureKeys(arr)
        if (!arr.length) return null
        if (activeId.value != null) {
            const byId = arr.find(a => a?.id === activeId.value);
            if (byId) return byId
        }
        if (activeKey.value != null) {
            const byKey = arr.find(a => a?._key === activeKey.value);
            if (byKey) return byKey
        }
        const first = arr[0];
        first?.id != null ? activeId.value = first.id : activeKey.value = first._key;
        return first
    },
    set(val) {
        if (!val) {
            activeId.value = null;
            activeKey.value = null;
            return
        }
        if (val.id != null) {
            activeId.value = val.id;
            activeKey.value = null;
            return
        }
        ensureKeys(itemsDetailed.value);
        activeKey.value = val._key;
        activeId.value = null
    }
})

watch(itemsDetailed, (arr) => {
    ensureKeys(arr)
    if (!arr?.length) {
        activeId.value = null;
        activeKey.value = null;
        return
    }
    if (!activeDetailedArticleForEditing.value) {
        const first = arr[0];
        first?.id != null ? activeId.value = first.id : activeKey.value = first._key
    }
}, {deep: true, immediate: true})


const checkIfEveryPropertyWhereAreRequiredIsFilled = computed(() => {
    const checkList = (propsArr) => propsArr?.every(p => !p.is_required || getValue(p))
    if (articleForm.is_detailed_quantity) return articleForm.detailed_article_quantities?.every(da => da.status && checkList(da.properties))
    return checkList(articleForm.properties)
})

const formatQuantity = (q) => q?.toString()?.replace(/\B(?=(\d{3})+(?!\d))/g, '.')
const addImage = () => articleImageInput.value?.click()
const capitalizeFirstLetter = (v) => String(v).charAt(0).toUpperCase() + String(v).slice(1)


const submit = () => {

    if (!canSaveWithTags.value) {
        // Optional: Scroll zum Tag-Bereich oder Toast
        return
    }


    articleForm.main_image_index = currentMainImage.value
    articleForm.inventory_sub_category_id = selectedSubCategory.value?.id ?? null

    if (!articleForm.is_detailed_quantity) storedDetailedArticleQuantities.value = []

    if (props.article) {
        articleForm.transform(d => ({...d, _method: 'PATCH'}))
        articleForm.post(route('inventory-management.articles.update', props.article.id), {
            preserveScroll: true, forceFormData: true, onSuccess: () => emits('close')
        })
    } else {
        articleForm.post(route('inventory-management.articles.store'), {
            preserveScroll: true, forceFormData: true,
            onSuccess: () => {
                articleForm.reset();
                selectedCategory.value = null;
                selectedSubCategory.value = null;
                articleImageInput.value = 0;
                storedDetailedArticleQuantities.value = [];
                emits('close')
            }
        })
    }
}

const addNewDetailedArticle = () => {
    const baseProps = articleForm.detailed_article_quantities?.[0]?.properties?.map(p => ({
        id: p.id, name: p.name, tooltip_text: p.tooltip_text, type: p.type, value: '', is_required: p.is_required,
        categoryProperty: getIsDeletable(p.id), select_values: p.select_values,
        across_articles: p.across_articles ?? false, individual_value: p.individual_value ?? false,
    })) ?? []

    const newItem = {
        _key: uid(),
        name: currentPageLanguage.value === 'de' ? 'Neuer Artikel' : 'New Article',
        description: '',
        quantity: 0,
        properties: baseProps,
        status: defaultStatus()
    }
    articleForm.detailed_article_quantities.push(newItem)
    activeDetailedArticleForEditing.value = newItem
    // Neu: Sicherstellen, dass across-Werte in den neuen Artikel geschrieben werden
    syncAcrossValuesToDetailedArticles()
}

const removeDetailedArticle = () => {
    let index = articleToDelete.value
    const arr = articleForm.detailed_article_quantities
    if (!Array.isArray(arr) || index < 0 || index >= arr.length) return
    const removed = arr[index]
    arr.splice(index, 1)

    // Auswahl bereinigen
    const k = itemKey(removed)
    if (k != null && selectedDetailedKeys.value.has(k)) {
        const next = new Set(selectedDetailedKeys.value)
        next.delete(k)
        selectedDetailedKeys.value = next
    }

    // Aktiven Eintrag neu setzen
    activeDetailedArticleForEditing.value = arr.length ? arr[Math.min(index, arr.length - 1)] : null

    // Wenn nichts mehr übrig ist: Auswahl und Bulk-Edit schließen
    if (!arr.length) {
        clearSelection()
        cancelBulkEdit()

    }
    // close all confirm modals
    confirmSingleDeleteModalOpen.value = false
}

const copyDetailedArticle = (d) => {
    const copiedProps = d.properties.map(p => ({...p, value: p.individual_value ? '' : p.value}))
    const newItem = {
        _key: uid(),
        name: d.name + ' (Copy)',
        description: d.description,
        quantity: d.quantity ?? 0,
        properties: copiedProps,
        status: d.status
    }
    articleForm.detailed_article_quantities.push(newItem)
    activeDetailedArticleForEditing.value = newItem
    // Neu: across-Werte angleichen
    syncAcrossValuesToDetailedArticles()
}

const handleImageInput = (e) => {
    articleForm.newImages = Array.from(e.target.files)
}

const allImages = computed(() => [
    ...articleForm.oldImages.map(img => ({...img, _origin: 'old'})),
    ...articleForm.newImages.map(file => ({_origin: 'new', file}))
])

const removeImage = (image) => {
    if (image._origin === 'old') {
        articleForm.removed_image_ids.push(image.id)
        const i = articleForm.oldImages.findIndex(x => x.id === image.id)
        if (i > -1) articleForm.oldImages.splice(i, 1)
    } else if (image._origin === 'new') {
        const i = articleForm.newImages.findIndex(f => f.name === image.file.name && f.lastModified === image.file.lastModified)
        if (i > -1) articleForm.newImages.splice(i, 1)
    }
}

const createImageURL = (img) => !img ? '' : (img._origin === 'old' && img.image ? `/storage/${img.image}` : (img._origin === 'new' && img.file instanceof Blob ? URL.createObjectURL(img.file) : ''))

const updateSelectedSubCategory = (sc) => {
    selectedSubCategory.value = null;
    nextTick(() => {
        selectedSubCategory.value = sc
    })
}

watch(selectedCategory, (val, oldVal) => {
    let propsArr = getCurrentProps()

    // Alte Kategorie-Properties explizit entfernen (basierend auf alter Kategorie)
    if (oldVal && Array.isArray(oldVal.properties)) {
        const oldCatPropIds = new Set(oldVal.properties.map(p => p.id))
        propsArr = propsArr.filter(p => !oldCatPropIds.has(p.id))
    }

    // Zusätzlich alle mit categoryProperty-Flag entfernen (Fallback)
    propsArr = propsArr.filter(p => !p.categoryProperty)

    if (!val || !Array.isArray(val.properties)) {
        articleForm.inventory_category_id = null;
        applyPropsToForm(propsArr);
        return
    }

    articleForm.inventory_category_id = val.id
    selectedSubCategory.value = null

    const incoming = val.properties.map(p => {
        const withSelects = {...p, ...(p.type === 'selection' && !p.select_values ? {select_values: findSelectValues(p.id)} : {})};
        const merged = buildProp(withSelects, propsArr.find(ep => ep.id === p.id));
        merged.categoryProperty = true;
        return merged
    })
    const ids = new Set(incoming.map(p => p.id))
    propsArr = [...propsArr.filter(p => !ids.has(p.id)), ...incoming]
    applyPropsToForm(propsArr)
    // Neu: nach Übernahme der Kategorie-Properties synchronisieren
    syncAcrossValuesToDetailedArticles()
})

watch(selectedSubCategory, (val, oldVal) => {
    let propsArr = getCurrentProps()

    // Alte Subkategorie-Properties explizit entfernen (basierend auf alter Subkategorie)
    if (oldVal && Array.isArray(oldVal.properties)) {
        const oldSubCatPropIds = new Set(oldVal.properties.map(p => p.id))
        propsArr = propsArr.filter(p => !oldSubCatPropIds.has(p.id))
    }

    // Wenn keine Subkategorie mehr: NUR Kategorie-Properties behalten
    if (!val) {
        articleForm.inventory_sub_category_id = null
        if (selectedCategory.value && Array.isArray(selectedCategory.value.properties)) {
            const catIds = new Set(selectedCategory.value.properties.map(p => p.id))
            // Behalte nur: manuelle Properties (!categoryProperty) ODER echte Kategorie-Properties
            propsArr = propsArr.filter(p => !p.categoryProperty || catIds.has(p.id))
        } else {
            propsArr = propsArr.filter(p => !p.categoryProperty)
        }
        applyPropsToForm(propsArr);
        return
    }

    // Alte Subkategorie-Properties entfernen, aber Kategorie-Properties behalten (für Wechsel zu neuer Subkategorie)
    if (selectedCategory.value) {
        const catIds = new Set((selectedCategory.value.properties || []).map(p => p.id))
        propsArr = propsArr.filter(p => !p.categoryProperty || catIds.has(p.id))
    } else {
        propsArr = propsArr.filter(p => !p.categoryProperty)
    }

    if (!Array.isArray(val.properties)) return
    articleForm.inventory_sub_category_id = val.id
    const incoming = val.properties.map(p => {
        const withSelects = {...p, ...(p.type === 'selection' && !p.select_values ? {select_values: findSelectValues(p.id)} : {})};
        const merged = buildProp(withSelects, propsArr.find(ep => ep.id === p.id));
        merged.categoryProperty = true;
        return merged
    })
    const ids = new Set(incoming.map(p => p.id))
    propsArr = [...propsArr.filter(p => !ids.has(p.id)), ...incoming]
    applyPropsToForm(propsArr)
    // Neu: nach Übernahme der Subkategorie-Properties synchronisieren
    syncAcrossValuesToDetailedArticles()
})

watch(() => articleForm.is_detailed_quantity, (isDetailed) => {
    if (isDetailed) {
        if (storedDetailedArticleQuantities.value.length) {
            articleForm.detailed_article_quantities = storedDetailedArticleQuantities.value
        } else {
            const baseProps = (articleForm.properties || []).map(p => ({
                id: p.id,
                name: p.name,
                tooltip_text: p.tooltip_text,
                type: p.type,
                value: p.value ?? p.pivot?.value ?? '',
                is_required: !!p.is_required,
                categoryProperty: getIsDeletable(p.id),
                select_values: p.select_values,
                across_articles: !!p.across_articles,
                individual_value: !!p.individual_value,
            }))
            articleForm.detailed_article_quantities = [{
                name: articleForm.name || (currentPageLanguage.value === 'de' ? 'Neuer Artikel' : 'New Article'),
                description: articleForm.description,
                quantity: '',
                properties: baseProps,
                status: defaultStatus()
            }]
        }
        articleForm.properties = []
        ensureKeys(articleForm.detailed_article_quantities)
        if (articleForm.detailed_article_quantities.length) activeDetailedArticleForEditing.value = articleForm.detailed_article_quantities[0]
        // Neu: beim Umschalten auf detailliert initialisieren und synchronisieren
        const firstProps = articleForm.detailed_article_quantities?.[0]?.properties || []
        const init = {}
        for (const p of firstProps) if (p.across_articles) init[p.id] = p.value ?? ''
        acrossValues.value = init
        syncAcrossValuesToDetailedArticles()
        // NEU: Auswahl/Bulk-Edit beim Umschalten initialisieren
        clearSelection()
        cancelBulkEdit()
    } else {
        storedDetailedArticleQuantities.value = [...articleForm.detailed_article_quantities]
        articleForm.properties = articleForm.detailed_article_quantities[0]?.properties?.length
            ? articleForm.detailed_article_quantities[0].properties.map(p => ({
                id: p.id,
                name: p.name,
                tooltip_text: p.tooltip_text,
                type: p.type,
                value: p.value ?? p.pivot?.value ?? '',
                is_required: !!p.is_required,
                categoryProperty: getIsDeletable(p.id),
                select_values: p.select_values,
                across_articles: !!p.across_articles,
                individual_value: !!p.individual_value,
            }))
            : []
        articleForm.detailed_article_quantities = []
        activeDetailedArticleForEditing.value = null
        // NEU: Auswahl/Bulk-Edit beim Verlassen bereinigen
        clearSelection()
        cancelBulkEdit()
    }
})

const calculateTotalQuantity = computed(() => {
    const total = (articleForm.detailed_article_quantities || []).reduce((sum, d) => {
        const q = parseInt(d?.quantity, 10);
        return sum + (isNaN(q) ? 0 : q)
    }, 0)
    articleForm.quantity = total
    return total
})

const calculateStatusQuantityInArticle = computed(() =>
    articleForm.statusValues.reduce((t, s) => {
        const q = parseInt(s.value, 10);
        return t + (isNaN(q) ? 0 : q)
    }, 0)
)

onMounted(() => {
    if (props.article) {
        selectedCategory.value = categories?.find(c => c.id === props.article.inventory_category_id) ?? null
        selectedSubCategory.value = categories.find(c => c.id === props.article.inventory_category_id)?.subcategories.find(sc => sc.id === props.article.inventory_sub_category_id) ?? null

        const categoryProps = [...(selectedCategory.value?.properties ?? []), ...(selectedSubCategory.value?.properties ?? [])]
            .map(p => ({
                id: p.id,
                name: p.name,
                tooltip_text: p.tooltip_text,
                type: p.type,
                value: p.value ?? p.pivot?.value ?? '',
                is_required: p.is_required,
                across_articles: p.across_articles ?? false,
                individual_value: p.individual_value ?? false,
                categoryProperty: getIsDeletable(p.id),
                select_values: p.select_values
            }))

        if (props.article.is_detailed_quantity) {
            if (!props.article.detailed_article_quantities?.length) {
                articleForm.detailed_article_quantities = [{
                    name: props.article.name,
                    description: props.article.description,
                    quantity: '',
                    properties: [...categoryProps],
                    status: defaultStatus()
                }]
            } else {
                articleForm.detailed_article_quantities = props.article.detailed_article_quantities.map(da => {
                    const daProps = da.properties || []
                    const daPropIds = new Set(daProps.map(p => p.id))

                    // Existing detailed article properties with their values
                    const existingDaProps = daProps.map(p => ({
                        id: p.id,
                        name: p.name,
                        tooltip_text: p.tooltip_text,
                        type: p.type,
                        value: p.value ?? p.pivot?.value ?? '',
                        is_required: p.is_required,
                        across_articles: p.across_articles ?? false,
                        individual_value: p.individual_value ?? false,
                        categoryProperty: getIsDeletable(p.id),
                        select_values: p.select_values
                    }))

                    // Add new category properties that are not yet in this detailed article
                    const newCatProps = categoryProps.filter(cp => !daPropIds.has(cp.id))

                    return {
                        name: da.name,
                        description: da.description,
                        quantity: da.quantity,
                        properties: [...existingDaProps, ...newCatProps],
                        status: da.status ? (statuses.find(s => s.id === da.status.id) ?? da.status) : defaultStatus(),
                    }
                })
            }
            articleForm.properties = []
        } else {
            // Merge article properties with category/subcategory properties
            const articleProps = props.article.properties || []
            const articlePropIds = new Set(articleProps.map(p => p.id))

            // Existing article properties with their values
            const existingProps = articleProps.map(p => ({
                id: p.id,
                name: p.name,
                tooltip_text: p.tooltip_text,
                type: p.type,
                value: p.value ?? p.pivot?.value ?? '',
                is_required: p.is_required,
                across_articles: p.across_articles ?? false,
                individual_value: p.individual_value ?? false,
                categoryProperty: getIsDeletable(p.id),
                select_values: p.select_values
            }))

            // Add new category properties that are not yet in the article
            const newCategoryProps = categoryProps.filter(cp => !articlePropIds.has(cp.id))

            articleForm.properties = [...existingProps, ...newCategoryProps]
            articleForm.detailed_article_quantities = []
        }

        if (props.article.images) articleForm.oldImages = [...props.article.images]
    }

    if (articleForm.detailed_article_quantities.length) {
        activeDetailedArticleForEditing.value = articleForm.detailed_article_quantities[0]
        // Neu: Initialbefüllung und Sync beim Editieren bestehender Artikel
        const firstProps = articleForm.detailed_article_quantities?.[0]?.properties || []
        const init = {}
        for (const p of firstProps) if (p.across_articles) init[p.id] = p.value ?? ''
        acrossValues.value = init
        syncAcrossValuesToDetailedArticles()
        // NEU: Auswahl initial leeren
        clearSelection()
    }
})

// NEU: Auswahl- und Bulk-Edit-States
const selectedDetailedKeys = ref(new Set())
const bulkEdit = ref({ open: false, status: null, quantity: '', propertyId: null, propertyValue: null })

// NEU: Helper für Keys/Selection
const itemKey = (it) => {
  if (!it) return null
  if (it.id != null) return it.id
  if (it._key == null) it._key = uid()
  return it._key
}
const isSelected = (it) => selectedDetailedKeys.value.has(itemKey(it))

const visibleKeys = computed(() => {
  ensureKeys(articleForm.detailed_article_quantities || [])
  return filteredDetailedArticles.value.map(itemKey).filter(k => k != null)
})

const selectionCount = computed(() => selectedDetailedKeys.value.size)
const hasSelection = computed(() => selectionCount.value > 0)
const allVisibleSelected = computed(() =>
  visibleKeys.value.length > 0 &&
  visibleKeys.value.every(k => selectedDetailedKeys.value.has(k))
)

// NEU: Ausgewählte Detailed-Artikel
const selectedDetailedItems = computed(() => {
  const keys = selectedDetailedKeys.value
  if (!keys.size) return []
  const arr = articleForm.detailed_article_quantities || []
  ensureKeys(arr)
  return arr.filter(da => keys.has(itemKey(da)))
})

// NEU: Schnittmenge editierbarer Properties (nicht across_articles) über die Selektion
const bulkEditableProperties = computed(() => {
  const sel = selectedDetailedItems.value
  if (!sel.length) return []
  const count = sel.length
  const map = new Map()
  for (const da of sel) {
    for (const p of (da.properties || [])) {
      if (p.across_articles) continue
      const entry = map.get(p.id)
      if (!entry) {
        map.set(p.id, { id: p.id, name: p.name, type: p.type, select_values: p.select_values ?? [], seen: 1 })
      } else {
        entry.seen++
      }
    }
  }
  return Array.from(map.values()).filter(e => e.seen === count).map(({seen, ...rest}) => rest)
})

// NEU: aktuell gewählte Bulk-Property
const selectedBulkProp = computed(() =>
  bulkEditableProperties.value.find(p => p.id === bulkEdit.value.propertyId) || null
)

const toggleSelection = (it, checked = null) => {
  const k = itemKey(it)
  if (k == null) return
  const next = new Set(selectedDetailedKeys.value)
  const shouldSelect = checked == null ? !next.has(k) : !!checked
  if (shouldSelect) next.add(k); else next.delete(k)
  selectedDetailedKeys.value = next
}

const toggleSelectAllVisible = (checked) => {
  const next = new Set(selectedDetailedKeys.value)
  for (const k of visibleKeys.value) checked ? next.add(k) : next.delete(k)
  selectedDetailedKeys.value = next
}

const clearSelection = () => { selectedDetailedKeys.value = new Set() }

// NEU: Bulk-Edit-Steuerung
const openBulkEdit = () => { bulkEdit.value.open = true }
const cancelBulkEdit = () => { bulkEdit.value = { open: false, status: null, quantity: '', propertyId: null, propertyValue: null } }

// NEU: Selektion ändert sich -> ungültige Property-Auswahl zurücksetzen
watch(selectedDetailedKeys, () => {
  if (!bulkEditableProperties.value.find(p => p.id === bulkEdit.value.propertyId)) {
    bulkEdit.value.propertyId = null
    bulkEdit.value.propertyValue = null
  }
})

// NEU: applyBulkEdit um Property-Update erweitern
const applyBulkEdit = () => {
  const arr = articleForm.detailed_article_quantities || []
  const qRaw = bulkEdit.value.quantity
  const hasQ = qRaw !== '' && qRaw != null && !isNaN(parseInt(qRaw, 10))
  const qVal = hasQ ? parseInt(qRaw, 10) : null
  const statusVal = bulkEdit.value.status
  const propId = bulkEdit.value.propertyId
  const propValRaw = bulkEdit.value.propertyValue
  const propMeta = selectedBulkProp.value

  for (const da of arr) {
    const k = itemKey(da)
    if (!selectedDetailedKeys.value.has(k)) continue

    if (statusVal) da.status = statusVal
    if (hasQ) da.quantity = qVal

    if (propId && propMeta) {
      const p = (da.properties || []).find(pp => pp.id === propId && !pp.across_articles)
      if (p) {
        if (propMeta.type === 'checkbox') {
          p.value = Boolean(propValRaw)
        } else if (propMeta.type === 'room' || propMeta.type === 'manufacturer') {
          p.value = propValRaw === '' || propValRaw == null ? '' : Number(propValRaw)
        } else {
          p.value = propValRaw
        }
      }
    }
  }

  cancelBulkEdit()
}

const bulkDeleteSelected = () => {
  const keys = selectedDetailedKeys.value
  if (!keys.size) return
  const arr = articleForm.detailed_article_quantities
  for (let i = arr.length - 1; i >= 0; i--) {
    const k = itemKey(arr[i])
    if (keys.has(k)) arr.splice(i, 1)
  }
  clearSelection()
    confirmMultiEditDeleteModalOpen.value = false
  activeDetailedArticleForEditing.value = arr.length ? arr[0] : null
}


const page = usePage()
const { hasAdminRole } = usePermission(page.props)
const currentUser = computed(() => page.props.auth?.user || null)
const currentUserDepartmentIds = computed(() =>
    (page.props.auth?.user?.department_ids || [])
)

// 🔹 Tag-Auswahl-States
const tagSearch = ref('')

const selectedTagIds = computed({
    get: () => articleForm.tag_ids || [],
    set: (val) => { articleForm.tag_ids = val || [] }
})

const allTags = computed(() => tags || [])

const selectedTags = computed(() =>
    allTags.value.filter(t => selectedTagIds.value.includes(t.id))
)

const availableTags = computed(() => {
    const q = tagSearch.value.toLowerCase()
    return allTags.value.filter(tag => {
        if (selectedTagIds.value.includes(tag.id)) return false
        if (!q) return true
        return (tag.name || '').toLowerCase().includes(q)
    })
})

// 🔹 Tags nach Gruppen für Auswahl gruppieren
const tagGroupsForSelection = computed(() => {
    const map = new Map()

    ;(tagGroups || []).forEach(g => {
        map.set(g.id, {
            key: `g-${g.id}`,
            label: g.name,
            tags: []
        })
    })

    const ungrouped = {
        key: 'ungrouped',
        label: $t('Ungrouped tags'),
        tags: []
    }

    availableTags.value.forEach(tag => {
        const gid = tag.inventory_tag_group_id
        if (gid && map.has(gid)) {
            map.get(gid).tags.push(tag)
        } else {
            ungrouped.tags.push(tag)
        }
    })

    const result = Array.from(map.values()).filter(g => g.tags.length)
    if (ungrouped.tags.length) result.push(ungrouped)

    return result
})

// 🔹 Berechtigungsprüfung je Tag
const userCanUseTag = (tag) => {
    if (!tag?.has_restricted_permissions) return true

    // Admins dürfen immer alle Tags verwenden
    if (hasAdminRole()) return true

    const user = currentUser.value
    if (!user) return false

    // explizit freigegebene User
    if ((tag.allowed_users || []).some(u => u.id === user.id)) {
        return true
    }

    // Departments (Teams)
    const deptIds = currentUserDepartmentIds.value
    if (!deptIds.length) return false

    return (tag.allowed_departments || []).some(d => deptIds.includes(d.id))
}

// 🔹 Tags, für die der User KEINE Berechtigung hat
const forbiddenTags = computed(() =>
    selectedTags.value.filter(t => !userCanUseTag(t))
)

// 🔹 darf gespeichert werden?
const canSaveWithTags = computed(() => forbiddenTags.value.length === 0)

// 🔹 kann Tag angeklickt werden?
const canSelectTag = (tag) => userCanUseTag(tag)

// 🔹 Tag toggeln
const toggleTag = (tag) => {
    const ids = new Set(selectedTagIds.value)
    if (ids.has(tag.id)) {
        ids.delete(tag.id)
    } else {
        ids.add(tag.id)
    }
    selectedTagIds.value = Array.from(ids)
}
</script>


<style scoped>

</style>
