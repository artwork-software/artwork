<template>
    <BaseModal @closed="$emit('close')" :modal-size="articleForm.is_detailed_quantity ? 'max-w-7xl' : 'max-w-4xl'"
               full-modal>
        <div class="px-6 pt-4">
            <ModalHeader
                :title="article ? $t('Edit article') : $t('Add Article')"
                :description="article ? $t('Edit the article details') : $t('Add a new article')"
            />
        </div>
        <form @submit.prevent="submit">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 px-6 pb-4">
                <div class="col-span-1">
                    <div @click="addImage"
                         class="relative block w-full rounded-lg border-2 border-dashed border-gray-300 p-12 cursor-pointer text-center hover:border-gray-400 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden">
                        <component is="IconPhotoPlus" class="mx-auto size-12 text-gray-400" aria-hidden="true"/>
                        <span class="mt-2 block text-sm font-semibold text-gray-900">{{ $t('Upload Images')}}</span>
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

                            <!-- X-Button zum Entfernen -->
                          <XCircleIcon @click.stop="removeImage(image)" class="absolute top-1 right-1 text-artwork-buttons-create h-5 w-5 hover:text-error "/>

                            <div class="flex flex-col items-center justify-center w-full truncate min-h-16 gap-y-2">
                                <!-- Für alte Bilder: Bild-Preview anzeigen, ansonsten den Dateinamen -->
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

                    <div class="col-span-full">
                        <BaseInput
                            type="number"
                            id="quantity" v-model="articleForm.quantity"
                            :label="$t('Quantity*')"
                            :max="10000000"
                            :maxlength="1000000"
                            required
                        />
                    </div>
                </div>


            </div>

            <!-- Category selector -->
            <div class="bg-gray-50 px-6 py-6 mb-5">
                <div class="mb-5">
                    <Listbox as="div" v-model="selectedCategory">
                        <ListboxLabel class="xsDark">
                            {{ $t('Select Category') }}
                        </ListboxLabel>
                        <div class="relative mt-2">
                            <ListboxButton class="menu-button bg-white">
                                <div class="col-start-1 row-start-1 truncate pr-6">
                                    {{ selectedCategory?.name ?? $t('Please select a Category') }}
                                </div>
                                <component is="IconChevronUp"
                                           class="col-start-1 row-start-1 size-5 self-center justify-self-end text-gray-500 sm:size-4"
                                           aria-hidden="true"/>
                            </ListboxButton>

                            <transition leave-active-class="transition ease-in duration-100"
                                        leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions
                                    class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base ring-1 shadow-lg ring-black/5 focus:outline-hidden sm:text-sm">
                                    <ListboxOption as="template" v-for="category in categories" :key="category.id"
                                                   :value="category" v-slot="{ active, selected }">
                                        <li :class="[active ? 'bg-indigo-600 text-white outline-hidden' : 'text-gray-900', 'relative cursor-default py-2 pr-9 pl-3 select-none']">
                                            <span
                                                :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{
                                                    category.name
                                                }}</span>

                                            <span v-if="selected"
                                                  :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                <component is="IconCheck" class="size-5" aria-hidden="true"/>
                                            </span>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </transition>
                        </div>
                    </Listbox>
                </div>
                <div class="pb-4" v-if="selectedCategory && selectedCategory.subcategories.length > 0">
                    <Listbox as="div" v-model="selectedSubCategory">
                        <ListboxLabel class="xsDark">
                            {{ $t('Select Sub-Category') }}
                        </ListboxLabel>
                        <div class="relative mt-2">
                            <ListboxButton class="menu-button">
                                <div class="col-start-1 row-start-1 truncate pr-6">
                                    {{ selectedSubCategory?.name ?? $t('Please select a Sub-Category') }}
                                </div>
                                <component is="IconChevronUp"
                                           class="col-start-1 row-start-1 size-5 self-center justify-self-end text-gray-500 sm:size-4"
                                           aria-hidden="true"/>
                            </ListboxButton>

                            <transition leave-active-class="transition ease-in duration-100"
                                        leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions
                                    class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base ring-1 shadow-lg ring-black/5 focus:outline-hidden sm:text-sm">
                                    <ListboxOption as="template" v-for="category in selectedCategory.subcategories"
                                                   :key="category.id" :value="category" @click="updateSelectedSubCategory(category)" v-slot="{ active, selected }">
                                        <li :class="[active ? 'bg-indigo-600 text-white outline-hidden' : 'text-gray-900', 'relative cursor-default py-2 pr-9 pl-3 select-none']">
                                            <span
                                                :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{
                                                    category.name
                                                }}</span>

                                            <span v-if="selected"
                                                  :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                <component is="IconCheck" class="size-5" aria-hidden="true"/>
                                            </span>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </transition>
                        </div>
                    </Listbox>

                    <div class="flex items-center justify-end mt-3" v-if="selectedSubCategory">
                        <div
                            class="text-xs text-artwork-buttons-create underline underline-offset-4 hover:text-artwork-buttons-hover duration-200 ease-in-out cursor-pointer"
                            @click="selectedSubCategory = null">{{ $t('Remove the sub-category assignment') }}
                        </div>
                    </div>
                </div>

                <div class="flex gap-3" v-if="selectedCategory">
                    <div class="flex h-6 shrink-0 items-center">
                        <div class="group grid size-4 grid-cols-1">
                            <input id="is_detailed_quantity" aria-describedby="is_detailed_quantity-description"
                                   v-model="articleForm.is_detailed_quantity" name="is_detailed_quantity"
                                   type="checkbox" class="input-checklist"/>
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
                </div>
            </div>

            <!-- category properties -->
            <div class="px-6"
                 v-if="articleForm.properties.length > 0 && selectedCategory && !articleForm.is_detailed_quantity">
                <div>
                    <TinyPageHeadline
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
                                                    icon="IconInfoCircle"
                                                    icon-size="size-4"
                                                    direction="top"
                                                    tooltip-width="break-all !text-xs"
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

                                        <Combobox v-if="property.type === 'room'" as="div" v-model="property.value"
                                                  @update:modelValue="query = ''">
                                            <div class="relative">
                                                <ComboboxInput
                                                    class="block w-full ring-0 border-none focus:ring-0 rounded-md bg-white py-1.5 pr-12 pl-3 text-base text-gray-900  placeholder:text-gray-400 sm:text-sm/6"
                                                    @change="query = $event.target.value" @blur="query = ''"
                                                    :display-value="(person) => property.value ? rooms?.find((room) => room.id === parseInt(property.value) ).name : ''"/>
                                                <ComboboxButton
                                                    class="absolute inset-y-0 right-0 flex items-center rounded-r-md px-2 focus:outline-hidden">
                                                    <component is="IconSelector" class="size-5 text-gray-400"
                                                               aria-hidden="true"/>
                                                </ComboboxButton>

                                                <ComboboxOptions v-if="filteredPeople.length > 0"
                                                                 class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base ring-1 shadow-lg ring-black/5 focus:outline-hidden sm:text-sm">
                                                    <ComboboxOption v-for="person in filteredPeople" :key="person.id"
                                                                    :value="person.id" as="template"
                                                                    v-slot="{ active, selected }">
                                                        <li :class="['relative cursor-default py-2 pr-9 pl-3 select-none', active ? 'bg-indigo-600 text-white outline-hidden' : 'text-gray-900']">
                                                                <span
                                                                    :class="['block truncate', selected && 'font-semibold']">
                                                                  {{ person.name }}
                                                                </span>
                                                            <span v-if="selected"
                                                                  :class="['absolute inset-y-0 right-0 flex items-center pr-4', active ? 'text-white' : 'text-indigo-600']">
                                                                  <component is="IconCheck" class="size-5"
                                                                             aria-hidden="true"/>
                                                                </span>
                                                        </li>
                                                    </ComboboxOption>
                                                </ComboboxOptions>
                                            </div>
                                        </Combobox>

                                        <Combobox v-if="property.type === 'manufacturer'" as="div"
                                                  v-model="property.value" @update:modelValue="queryManufacturer = ''">
                                            <div class="relative">
                                                <ComboboxInput
                                                    class="block w-full ring-0 border-none focus:ring-0 rounded-md bg-white py-1.5 pr-12 pl-3 text-xs text-gray-900  placeholder:text-gray-400"
                                                    @change="queryManufacturer = $event.target.value"
                                                    @blur="queryManufacturer = ''"
                                                    :display-value="(person) => property.value ? manufacturers?.find((manufacturer) => manufacturer.id === parseInt(property.value) ).name : ''"/>
                                                <ComboboxButton
                                                    class="absolute inset-y-0 right-0 flex items-center rounded-r-md px-2 focus:outline-hidden">
                                                    <component is="IconSelector" class="size-5 text-gray-400"
                                                               aria-hidden="true"/>
                                                </ComboboxButton>

                                                <ComboboxOptions v-if="filteredManufacturers.length > 0"
                                                                 class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-xs ring-1 shadow-lg ring-black/5 focus:outline-hidden sm:text-sm">
                                                    <ComboboxOption v-for="person in filteredManufacturers"
                                                                    :key="person.id" :value="person.id" as="template"
                                                                    v-slot="{ active, selected }">
                                                        <li :class="['relative cursor-default py-2 pr-9 pl-3 select-none', active ? 'bg-indigo-600 text-white outline-hidden' : 'text-gray-900']">
                                                                <span
                                                                    :class="['block truncate', selected && 'font-semibold']">
                                                                  {{ person.name }}
                                                                </span>
                                                            <span v-if="selected"
                                                                  :class="['absolute inset-y-0 right-0 flex items-center pr-4', active ? 'text-white' : 'text-indigo-600']">
                                                                  <component is="IconCheck" class="size-5"
                                                                             aria-hidden="true"/>
                                                                </span>
                                                        </li>
                                                    </ComboboxOption>
                                                </ComboboxOptions>
                                            </div>
                                        </Combobox>

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
                                                    <component is="IconPhoto" class="size-5 shrink-0 text-gray-400"
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
                                                    <component is="IconTrash" class="h-5 w-5" aria-hidden="true"/>
                                                </button>
                                            </div>
                                        </div>

                                        <div v-if="property.type === 'checkbox'" class="px-3">
                                            <input type="checkbox" :checked="booleanValue(property.value)"
                                                   @change="property.value = $event.target.checked"
                                                   class="input-checklist"/>
                                        </div>


                                        <div v-if="property.type === 'selection'" class="">
                                            <div class="mt-2 grid grid-cols-1">
                                                <select id="location" name="location" v-model="property.value" class="block w-full rounded-md bg-white border-none text-xs py-1.5 cursor-pointer text-gray-900 outline-0 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-0 ring-0 focus:ring-0">
                                                    <option v-for="value in property.select_values" :value="value" :key="value">{{ value }}</option>
                                                </select>
                                            </div>
                                        </div>


                                    </td>
                                </tr>
                                <!--<tr class="divide-x divide-gray-200">
                                    <td colspan="3" class="py-4 pr-4 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-0 first-letter:capitalize">
                                        <PropertiesMenu white-menu-background has-no-offset>
                                            <template v-slot:button>
                                                <div class="flex items-center gap-x-2 text-gray-400 font-lexend font-bold cursor-pointer hover:text-gray-600 duration-200 ease-in-out">
                                                    <component is="IconLibraryPlus" class="h-5 w-5" aria-hidden="true" />
                                                    <span>
                                                        {{ $t('Add individual properties') }}
                                                    </span>
                                                </div>
                                            </template>
                                            <template v-slot:menu>
                                                <div v-if="computedProperties.length > 0">
                                                    <div v-for="property in computedProperties">
                                                        <div @click="addPropertyToArticle(property)" class="px-4 py-3 cursor-pointer hover:bg-gray-50 rounded-lg duration-200 ease-in-out">
                                                            <div class="xsDark">
                                                                {{ property.name }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div v-else class="p-2">
                                                    <div class="rounded-md bg-red-50 p-4">
                                                        <div class="flex">
                                                            <div class="shrink-0">
                                                                <component is="IconInfoSquareRoundedFilled" class="size-5 text-red-400" aria-hidden="true" />
                                                            </div>
                                                            <div class="ml-3">
                                                                <p class="text-sm font-medium text-red-800">
                                                                    {{ $t('All properties are already added') }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </PropertiesMenu>
                                    </td>
                                </tr>-->
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
                            {{ $t('Single inventory capable') }}
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
                <div class="flow-root py-4">
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
                                        {{ $t('Description') }}
                                    </th>
                                    <th scope="col"
                                        class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pr-0">
                                        {{ $t('Quantity') }}
                                    </th>
                                    <th scope="col"
                                        class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-gray-900 sm:pr-0"
                                        v-for="property in articleForm.detailed_article_quantities?.[0]?.properties">
                                        {{ property.name }}<span v-if="property.is_required">*</span></th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                <tr class="divide-x divide-gray-200"
                                    v-for="(detailedArticle, index) in articleForm?.detailed_article_quantities">
                                    <td class="py-4 pr-4 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-0">
                                        <input type="text" v-model="detailedArticle.name"
                                               required
                                               class="block w-full rounded-md bg-white border-none text-xs px-3 py-1.5 text-gray-900 outline-0 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-0 ring-0 focus:ring-0"
                                               placeholder="Name"
                                        />
                                    </td>
                                    <td class="p-4 text-sm whitespace-nowrap text-gray-500 xsLight cursor-default">
                                        <input type="text" v-model="detailedArticle.description"
                                               class="block w-full rounded-md bg-white border-none text-xs px-3 py-1.5 text-gray-900 outline-0 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-0 ring-0 focus:ring-0"
                                               :placeholder="$t('Description')"
                                        />
                                    </td>
                                    <td class="text-sm whitespace-nowrap text-gray-500 sm:pr-0">
                                        <input type="text" v-model="detailedArticle.quantity"
                                               required
                                               class="block w-full rounded-md bg-white border-none text-xs px-3 py-1.5 text-gray-900 outline-0 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-0 ring-0 focus:ring-0"
                                               :placeholder="$t('Quantity*')"
                                        />
                                    </td>
                                    <td class="text-sm whitespace-nowrap text-gray-500 sm:pr-0"
                                        v-for="property in detailedArticle?.properties">
                                        <Combobox v-if="property.type === 'room'" as="div" v-model="property.value"
                                                  @update:modelValue="query = ''">
                                            <div class="relative">
                                                <ComboboxInput
                                                    class="block w-full ring-0 border-none focus:ring-0 rounded-md bg-white py-1.5 pr-12 pl-3 text-base text-gray-900  placeholder:text-gray-400 sm:text-sm/6"
                                                    @change="query = $event.target.value" @blur="query = ''"
                                                    :display-value="(person) => property.value ? rooms?.find((room) => room.id === parseInt(property.value) )?.name : ''"/>
                                                <ComboboxButton
                                                    class="absolute inset-y-0 right-0 flex items-center rounded-r-md px-2 focus:outline-hidden">
                                                    <component is="IconSelector" class="size-5 text-gray-400"
                                                               aria-hidden="true"/>
                                                </ComboboxButton>

                                                <ComboboxOptions v-if="filteredPeople.length > 0"
                                                                 class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base ring-1 shadow-lg ring-black/5 focus:outline-hidden sm:text-sm">
                                                    <ComboboxOption v-for="person in filteredPeople" :key="person.id"
                                                                    :value="person.id" as="template"
                                                                    v-slot="{ active, selected }">
                                                        <li :class="['relative cursor-default py-2 pr-9 pl-3 select-none', active ? 'bg-indigo-600 text-white outline-hidden' : 'text-gray-900']">
                                                                <span
                                                                    :class="['block truncate', selected && 'font-semibold']">
                                                                  {{ person.name }}
                                                                </span>
                                                            <span v-if="selected"
                                                                  :class="['absolute inset-y-0 right-0 flex items-center pr-4', active ? 'text-white' : 'text-indigo-600']">
                                                                  <component is="IconCheck" class="size-5"
                                                                             aria-hidden="true"/>
                                                                </span>
                                                        </li>
                                                    </ComboboxOption>
                                                </ComboboxOptions>
                                            </div>
                                        </Combobox>

                                        <Combobox v-if="property.type === 'manufacturer'" as="div"
                                                  v-model="property.value" @update:modelValue="queryManufacturer = ''">
                                            <div class="relative">
                                                <ComboboxInput
                                                    class="block w-full ring-0 border-none focus:ring-0 rounded-md bg-white py-1.5 pr-12 pl-3 text-base text-gray-900  placeholder:text-gray-400 sm:text-sm/6"
                                                    @change="queryManufacturer = $event.target.value"
                                                    @blur="queryManufacturer = ''"
                                                    :display-value="(person) => property.value ? manufacturers?.find((manufacturer) => manufacturer.id === parseInt(property.value) )?.name : ''"/>
                                                <ComboboxButton
                                                    class="absolute inset-y-0 right-0 flex items-center rounded-r-md px-2 focus:outline-hidden">
                                                    <component is="IconSelector" class="size-5 text-gray-400"
                                                               aria-hidden="true"/>
                                                </ComboboxButton>

                                                <ComboboxOptions v-if="filteredManufacturers.length > 0"
                                                                 class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base ring-1 shadow-lg ring-black/5 focus:outline-hidden sm:text-sm">
                                                    <ComboboxOption v-for="person in filteredManufacturers"
                                                                    :key="person.id" :value="person.id" as="template"
                                                                    v-slot="{ active, selected }">
                                                        <li :class="['relative cursor-default py-2 pr-9 pl-3 select-none', active ? 'bg-indigo-600 text-white outline-hidden' : 'text-gray-900']">
                                                                <span
                                                                    :class="['block truncate', selected && 'font-semibold']">
                                                                  {{ person.name }}
                                                                </span>
                                                            <span v-if="selected"
                                                                  :class="['absolute inset-y-0 right-0 flex items-center pr-4', active ? 'text-white' : 'text-indigo-600']">
                                                                  <component is="IconCheck" class="size-5"
                                                                             aria-hidden="true"/>
                                                                </span>
                                                        </li>
                                                    </ComboboxOption>
                                                </ComboboxOptions>
                                            </div>
                                        </Combobox>

                                        <input
                                            v-if="property.type !== 'file' && property.type !== 'checkbox' && property.type !== 'room' && property.type !== 'manufacturer'"
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
                                                    <component is="IconPhoto" class="size-5 shrink-0 text-gray-400"
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
                                                    <component is="IconTrash" class="h-5 w-5" aria-hidden="true"/>
                                                </button>
                                            </div>
                                        </div>

                                        <div v-if="property.type === 'checkbox'" class="px-3">
                                            <input type="checkbox" :checked="booleanValue(property.value)"
                                                   @change="property.value = $event.target.checked"
                                                   class="input-checklist"/>
                                        </div>
                                    </td>
                                </tr>

                                <tr class="divide-x divide-gray-200">
                                    <td colspan="2"
                                        class="py-2 pr-4 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-0 first-letter:capitalize">

                                    </td>
                                    <td colspan="1"
                                        class="p-2 text-xs whitespace-nowrap text-gray-500 font-lexend font-medium cursor-default">
                                        <div class="flex items-center justify-between">
                                            <span>{{ $t('Total quantity') }}:</span>
                                            <span v-if="calculateTotalQuantity > articleForm.quantity"
                                                  @click="articleForm.quantity = calculateTotalQuantity"
                                                  class="flex items-center gap-x-0.5  cursor-pointer">
                                                    <ToolTipWithTextComponent
                                                        :text="formatQuantity(calculateTotalQuantity)"
                                                        classes="text-artwork-buttons-create"
                                                        icon-right
                                                        stroke="2"
                                                        icon="IconClick"
                                                        icon-size="size-4"
                                                        :tooltip-text="$t('Click to set the article quantity to the detailed article quantity')"/>
                                                </span>
                                            <span class="font-bold"
                                                  v-else>{{ formatQuantity(calculateTotalQuantity) ?? 0 }}</span>
                                        </div>
                                    </td>
                                    <td :colspan="articleForm.detailed_article_quantities?.[0]?.properties.length ?? 0"
                                        class="p-2 text-xs whitespace-nowrap text-gray-500 font-lexend font-medium cursor-default flex items-center justify-between">
                                        <div v-if="calculateTotalQuantity > articleForm.quantity" class="text-red-600">
                                            <div>
                                                {{ $t('Detailed Article quantity is greater than article quantity') }}
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="pt-5">
                    <div @click="addNewDetailedArticle"
                         class="w-fit flex items-center gap-x-2 text-gray-400 font-lexend font-bold select-none cursor-pointer hover:text-gray-600 duration-200 ease-in-out">
                        <component is="IconLibraryPlus" class="h-5 w-5" aria-hidden="true"/>
                        <span>
                            {{ $t('Add new detailed article') }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-center my-10">
                <FormButton type="submit" :text="article ? $t('Update') : $t('Create')"
                            :disabled="articleForm.processing || !checkIfEveryPropertyWhereAreRequiredIsFilled || !selectedCategory || calculateTotalQuantity > articleForm.quantity"
                            :class="articleForm.processing ? 'bg-gray-200 hover:bg-gray-300' : ''"/>
            </div>
        </form>
    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import {useForm} from "@inertiajs/vue3";
import {computed, inject, onMounted, ref, watch, nextTick} from "vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import NumberInputComponent from "@/Components/Inputs/NumberInputComponent.vue";
import {
    Combobox, ComboboxButton, ComboboxInput, ComboboxOption, ComboboxOptions,
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions
} from "@headlessui/vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import ArticleModalTabs from "@/Pages/Inventory/Components/Article/Modals/Components/ArticleModalTabs.vue";
import ToolTipWithTextComponent from "@/Components/ToolTips/ToolTipWithTextComponent.vue";
import cloneDeep from 'lodash/cloneDeep';
import {XCircleIcon} from "@heroicons/vue/solid";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";

const props = defineProps({
    article: {
        type: Object,
        required: false,
        default: null
    },
})

const properties = inject('properties');
const categories = inject('categories');
const rooms = inject('rooms');
const manufacturers = inject('manufacturers');

const emits = defineEmits(["close"]);
const articleImageInput = ref(null);
const selectedCategory = ref(props.article ? categories.find((cate) => cate.id === props.article.inventory_category_id) : null);
const selectedSubCategory = ref(props.article ? categories.find((cate) => cate.id === props.article.inventory_category_id)?.subcategories.find((subCate) => subCate.id === props.article.inventory_sub_category_id) : null);
const currentTabId = ref(0);
const currentMainImage = ref(0);
const showArticleHeader = ref(true);
const queryManufacturer = ref('');

const query = ref('')
const filteredPeople = computed(() =>
    query.value === ''
        ? rooms
        : rooms.filter((room) => {
            return room.name.toLowerCase().includes(query.value.toLowerCase())
        }),
)

const filteredManufacturers = computed(() =>
    queryManufacturer.value === ''
        ? manufacturers
        : manufacturers.filter((manufacturer) => {
            return manufacturer.name.toLowerCase().includes(queryManufacturer.value.toLowerCase())
        }),
)
const getValue = (prop) => {
    return prop.value ?? prop.pivot?.value ?? '';
}

const booleanValue = (val) => {
    return val === true || val === 1 || val === "1" || val === "true";
};

const getIsDeletable = (id) => {
    return properties?.find(p => p.id === id)?.is_deletable ?? false;
}

const articleForm = useForm({
    name: props.article ? props.article.name : "",
    description: props.article ? props.article.description : "",
    inventory_category_id: props.article ? props.article.inventory_category_id : null,
    inventory_sub_category_id: props.article ? props.article.inventory_sub_category_id : null,
    quantity: props.article ? props.article.quantity : 0,
    is_detailed_quantity: props.article ? props.article.is_detailed_quantity : false,
    oldImages: [],
    newImages: [],
    removed_image_ids: [],
    properties: props.article ? props.article.properties.map((prop) => {
        return {
            id: prop.id,
            name: prop.name,
            tooltip_text: prop.tooltip_text,
            type: prop.type,
            value: getValue(prop),
            is_required: prop.is_required,
            categoryProperty: getIsDeletable(prop.id),
            select_values: prop.select_values
        }
    }) : [],
    detailed_article_quantities: props.article ? props.article.detailed_article_quantities.map((detailedArticle) => {
        return {
            name: detailedArticle.name,
            description: detailedArticle.description,
            quantity: detailedArticle.quantity,
            properties: detailedArticle.properties.map((prop) => {
                return {
                    id: prop.id,
                    name: prop.name,
                    tooltip_text: prop.tooltip_text,
                    type: prop.type,
                    value: getValue(prop),
                    is_required: prop.is_required,
                    categoryProperty: getIsDeletable(prop.id),
                    select_values: prop.select_values
                }
            }) ?? []
        }
    }) : [],
    main_image_index: 0
})

const updateTabId = (id) => {
    currentTabId.value = id;
    if (id === 1) {
        showArticleHeader.value = false
    }
}

const checkIfEveryPropertyWhereAreRequiredIsFilled = computed(() => {
    if (articleForm.is_detailed_quantity) {
        return articleForm.detailed_article_quantities?.every(detailedArticle => {
            return detailedArticle.properties?.every(property => {
                return !property.is_required || getValue(property);
            });
        });
    }

    return articleForm.properties?.every(property => {
        return !property.is_required || getValue(property);
    });
})

const formatQuantity = (quantity) => {
    return quantity?.toString()?.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

const addImage = () => {
    articleImageInput.value.click();
}

const capitalizeFirstLetter = (val) => {
    return String(val).charAt(0).toUpperCase() + String(val).slice(1);
}

const submit = () => {
    articleForm.main_image_index = currentMainImage.value;
    articleForm.inventory_sub_category_id = selectedSubCategory?.value ? selectedSubCategory.value.id : null;

    if (props.article) {
        articleForm.transform((data) => {
            data._method = 'PATCH';
            return data;
        });
        articleForm.post(route('inventory-management.articles.update', props.article.id), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                emits('close');
            }
        });
    } else {
        articleForm.post(route('inventory-management.articles.store'), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                articleForm.reset();
                selectedCategory.value = null;
                selectedSubCategory.value = null;
                articleImageInput.value = 0;
                emits('close');
            }
        });
    }
}

const addNewDetailedArticle = () => {
    articleForm.detailed_article_quantities.push({
        name: '',
        description: '',
        quantity: '',
        properties: articleForm.detailed_article_quantities?.[0]?.properties.map(prop => ({
            id: prop.id,
            name: prop.name,
            tooltip_text: prop.tooltip_text,
            type: prop.type,
            value: '',
            is_required: prop.is_required,
            categoryProperty: getIsDeletable(prop.id),
            select_values: prop.select_values
        })) ?? []
    });
}

const calculateTotalQuantity = computed(() => {
    return articleForm.detailed_article_quantities?.reduce((total, detailedArticle) => {
        const quantity = parseInt(detailedArticle?.quantity, 10);
        return total + (isNaN(quantity) ? 0 : quantity);
    }, 0);
});

const handleImageInput = (event) => {
    // Wir konvertieren die FileList in ein Array
    articleForm.newImages = Array.from(event.target.files);
};

const allImages = computed(() => {
    // Kennzeichnen der Herkunft, damit wir später unterscheiden können
    const old = articleForm.oldImages.map(img => ({...img, _origin: 'old'}));
    const neu = articleForm.newImages.map(file => ({
        _origin: 'new',
        file,
    }));
    return [...old, ...neu];
});
const updateSelectedSubCategory = (newSubCategory) => {
    // Setze zuerst auf null, um den Watcher zu triggern
    selectedSubCategory.value = null;
    nextTick(() => {
        selectedSubCategory.value = newSubCategory;
    });
};

watch(selectedCategory, (value) => {
    const updateProps = (newProps) => {
        if (articleForm.is_detailed_quantity && articleForm.detailed_article_quantities.length) {
            articleForm.detailed_article_quantities.forEach((detailedArticle) => {
                // Falls detailedArticle.properties null ist, benutze ein leeres Array
                const currentProps = detailedArticle.properties || [];
                const mergedProps = [];

                newProps.forEach((np) => {
                    // Suche nach einer bestehenden Property mit derselben ID
                    const existing = currentProps.find((oldProp) => oldProp.id === np.id);
                    if (existing) {
                        // Behalte den alten Wert, aber aktualisiere andere Felder (wie Name, Tooltip, etc.)
                        mergedProps.push({
                            ...np,
                            value: existing.value,
                        });
                    } else {
                        // Neue Property erhält einen leeren Wert
                        mergedProps.push({
                            ...np,
                            value: '',
                        });
                    }
                });

                detailedArticle.properties = mergedProps;
            });
        } else {
            articleForm.properties = newProps;
        }
    };

    const getCurrentProps = () => {
        if (articleForm.is_detailed_quantity && articleForm.detailed_article_quantities.length) {
            return articleForm.detailed_article_quantities[0].properties;
        }
        return articleForm.properties;
    };

    let props = getCurrentProps().filter(prop => !prop.categoryProperty);

    if (!value || !Array.isArray(value.properties)) {
        articleForm.inventory_category_id = null;
        updateProps(props);
        return;
    }

    articleForm.inventory_category_id = value.id;
    selectedSubCategory.value = null;

    value.properties.forEach(prop => {
        const existing = props.find(p => p.id === prop.id);
        if (existing) {
            existing.categoryProperty = true;
        } else {
            props.push({
                id: prop.id,
                name: prop.name,
                tooltip_text: prop.tooltip_text,
                type: prop.type,
                value: '',
                is_required: prop.is_required,
                categoryProperty: getIsDeletable(prop.id),
                select_values: prop.select_values
            });
        }
    });

    updateProps(props);
});

watch(selectedSubCategory, (value) => {
    const updateProps = (newProps) => {
        if (articleForm.is_detailed_quantity && articleForm.detailed_article_quantities.length) {
            articleForm.detailed_article_quantities.forEach((detailedArticle) => {
                // Falls detailedArticle.properties null ist, benutze ein leeres Array
                const currentProps = detailedArticle.properties || [];
                const mergedProps = [];

                newProps.forEach((np) => {
                    // Suche nach einer bestehenden Property mit derselben ID
                    const existing = currentProps.find((oldProp) => oldProp.id === np.id);
                    if (existing) {
                        // Behalte den alten Wert, aber aktualisiere andere Felder (wie Name, Tooltip, etc.)
                        mergedProps.push({
                            ...np,
                            value: existing.value,
                        });
                    } else {
                        // Neue Property erhält einen leeren Wert
                        mergedProps.push({
                            ...np,
                            value: '',
                        });
                    }
                });

                detailedArticle.properties = mergedProps;
            });
        } else {
            articleForm.properties = newProps;
        }
    };

    const getCurrentProps = () => {
        if (articleForm.is_detailed_quantity && articleForm.detailed_article_quantities.length) {
            return articleForm.detailed_article_quantities[0].properties;
        }
        return articleForm.properties;
    };

    let props = getCurrentProps();

    if (!value) {
        articleForm.inventory_sub_category_id = null;

        if (selectedCategory.value) {
            const categoryPropertyIds = new Set(selectedCategory.value.properties.map(p => p.id));
            props = props.filter(prop => !prop.categoryProperty || categoryPropertyIds.has(prop.id));
        } else {
            props = props.filter(prop => !prop.categoryProperty);
        }

        updateProps(props);
        return;
    }

    if (!Array.isArray(value.properties)) return;

    articleForm.inventory_sub_category_id = value.id;

    value.properties.forEach(prop => {
        const existing = props.find(p => p.id === prop.id);
        if (existing) {
            existing.categoryProperty = true;
        } else {
            props.push({
                id: prop.id,
                name: prop.name,
                tooltip_text: prop.tooltip_text,
                type: prop.type,
                value: '',
                is_required: prop.is_required,
                categoryProperty: getIsDeletable(prop.id),
                select_values: prop.select_values
            });
        }
    });

    updateProps(props);
});

watch(() => articleForm.is_detailed_quantity, (value) => {
    if (value) {
        articleForm.detailed_article_quantities = [{
            name: articleForm.name,
            description: articleForm.description,
            quantity: '',
            properties: articleForm.properties.map(prop => {
                return ({
                    id: prop.id,
                    name: prop.name,
                    tooltip_text: prop.tooltip_text,
                    type: prop.type,
                    value: '',
                    is_required: prop.is_required,
                    categoryProperty: getIsDeletable(prop.id),
                    select_values: prop.select_values
                })
            })
        }];
        articleForm.properties = [];
    } else {
        articleForm.properties = articleForm.detailed_article_quantities[0].properties.map(prop => {
            return ({
                id: prop.id,
                name: prop.name,
                tooltip_text: prop.tooltip_text,
                type: prop.type,
                value: '',
                is_required: prop.is_required,
                categoryProperty: getIsDeletable(prop.id),
                select_values: prop.select_values
            })
        });
        articleForm.detailed_article_quantities = [];
    }
})

const removeImage = (image) => {
    if (image._origin === 'old') {
        // Bei alten Bildern: ID merken und aus dem Array entfernen
        articleForm.removed_image_ids.push(image.id);
        const index = articleForm.oldImages.findIndex(i => i.id === image.id);
        if (index > -1) {
            articleForm.oldImages.splice(index, 1);
        }
    } else if (image._origin === 'new') {
        // Bei neuen Bildern: Greife auf image.file zu
        const index = articleForm.newImages.findIndex(file =>
            file.name === image.file.name &&
            file.lastModified === image.file.lastModified
        );
        if (index > -1) {
            articleForm.newImages.splice(index, 1);
        }
    }
};

const createImageURL = (imageObj) => {
    if (!imageObj) return '';

    // Altes Bild?
    if (imageObj._origin === 'old' && imageObj.image) {
        return '/storage/' + imageObj.image;
    }

    // Neues Bild?
    if (imageObj._origin === 'new' && imageObj.file instanceof Blob) {
        return window.URL.createObjectURL(imageObj.file);
    }

    return '';
};

onMounted(() => {
    if (props.article) {
        selectedCategory.value = categories?.find(c => c.id === props.article.inventory_category_id) ?? null;
        selectedSubCategory.value = props.article ? categories.find((cate) => cate.id === props.article.inventory_category_id)?.subcategories.find((subCate) => subCate.id === props.article.inventory_sub_category_id) : null;

        const categoryProps = [
            ...(selectedCategory.value?.properties ?? []),
            ...(selectedSubCategory.value?.properties ?? [])
        ].map(prop => ({
            id: prop.id,
            name: prop.name,
            tooltip_text: prop.tooltip_text,
            type: prop.type,
            value: '',
            is_required: prop.is_required,
            categoryProperty: getIsDeletable(prop.id),
            select_values: prop.select_values
        }));

        if (props.article.is_detailed_quantity) {
            if (!props.article.detailed_article_quantities?.length || !props.article.detailed_article_quantities[0].properties?.length) {
                articleForm.detailed_article_quantities = [{
                    name: props.article.name,
                    description: props.article.description,
                    quantity: '',
                    properties: [...categoryProps]
                }];
            } else {
                articleForm.detailed_article_quantities = props.article.detailed_article_quantities.map(da => ({
                    name: da.name,
                    description: da.description,
                    quantity: da.quantity,
                    properties: da.properties.map(prop => ({
                        id: prop.id,
                        name: prop.name,
                        tooltip_text: prop.tooltip_text,
                        type: prop.type,
                        value: prop.value ?? prop.pivot?.value ?? '',
                        is_required: prop.is_required,
                        categoryProperty: getIsDeletable(prop.id),
                        select_values: prop.select_values
                    }))
                }));
            }
            articleForm.properties = [];
        } else {
            if (!props.article.properties?.length) {
                articleForm.properties = [...categoryProps];
            } else {
                articleForm.properties = props.article.properties.map(prop => ({
                    id: prop.id,
                    name: prop.name,
                    tooltip_text: prop.tooltip_text,
                    type: prop.type,
                    value: prop.value ?? prop.pivot?.value ?? '',
                    is_required: prop.is_required,
                    categoryProperty: getIsDeletable(prop.id),
                    select_values: prop.select_values
                }));
            }
            articleForm.detailed_article_quantities = [];
        }
        if (props.article.images) {
            articleForm.oldImages = [...props.article.images];
        }
    }

})

</script>

<style scoped>

</style>
