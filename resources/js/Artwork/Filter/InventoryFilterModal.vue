<template>
  <ArtworkBaseModal
    modal-size="max-w-4xl"
    title="Inventory Filter"
    description="Filter inventory articles by category, sub-category and properties."
    @close="$emit('close')"
    full-modal
  >
    <div class="p-5">
      <!-- Active Filters -->
      <div>
        <div class="flex items-start justify-between">
          <TinyPageHeadline
            :title="$t('Active filters')"
            :description="$t('Your active filters. Click on a filter to remove it.')"
          />
        </div>

        <div class="mb-4 pb-4 border-b-2 border-dashed border-gray-300">
          <div class="flex flex-wrap items-center gap-2 mt-3">
            <!-- Category chips -->
            <div
              v-for="f in activeFilters.categories"
              :key="`cat-${f.id}`"
              class="group block cursor-pointer shrink-0 bg-blue-50 w-fit px-2 py-1.5 rounded-full border border-blue-200"
            >
              <div class="flex items-center">
                <div class="mx-2">
                  <p class="text-blue-500 text-xs group-hover:text-blue-600">{{ f.name }}</p>
                </div>
                <div class="flex items-center">
                  <button type="button" @click="removeActiveFilter(f, 'category')">
                    <XIcon class="size-4 text-blue-500 hover:text-error" />
                  </button>
                </div>
              </div>
            </div>

            <!-- Sub-Category chips -->
            <div
              v-for="f in activeFilters.subCategories"
              :key="`sub-${f.id}`"
              class="group block cursor-pointer shrink-0 bg-blue-50 w-fit px-2 py-1.5 rounded-full border border-blue-200"
            >
              <div class="flex items-center">
                <div class="mx-2">
                  <p class="text-blue-500 text-xs group-hover:text-blue-600">{{ f.name }}</p>
                </div>
                <div class="flex items-center">
                  <button type="button" @click="removeActiveFilter(f, 'subcategory')">
                    <XIcon class="size-4 text-blue-500 hover:text-error" />
                  </button>
                </div>
              </div>
            </div>

            <!-- Property chips -->
            <div
              v-for="f in activeFilters.properties"
              :key="`prop-${f.id}`"
              class="group block cursor-pointer shrink-0 bg-blue-50 w-fit px-2 py-1.5 rounded-full border border-blue-200"
            >
              <div class="flex items-center">
                <div class="mx-2">
                  <p class="text-blue-500 text-xs group-hover:text-blue-600">
                    {{ f.name }}: <span class="font-medium">{{ f.value }}</span>
                  </p>
                </div>
                <div class="flex items-center">
                  <button type="button" @click="removeActiveFilter(f, 'property')">
                    <XIcon class="size-4 text-blue-500 hover:text-error" />
                  </button>
                </div>
              </div>
            </div>

            <!-- Hinweis, falls keine aktiven Filter -->
            <div v-if="totalActiveCount === 0" class="text-xs text-gray-500">
              {{ $t('No active filters') }}
            </div>
          </div>
        </div>
      </div>

      <!-- Sektionen: Categories / Sub-Categories / Properties -->
      <div class="space-y-1">
        <!-- CATEGORIES -->
        <div class="py-1">
          <div class="text-white bg-gray-900 rounded-lg px-4 py-2 font-lexend shadow text-sm">
            {{ $t('Categories') }}
          </div>

          <div class="space-y-2 mt-2">
            <div class="card white px-4">
              <div
                class="flex items-center select-none justify-between duration-200 ease-in-out cursor-pointer py-3"
                @click="sections.categories.open = !sections.categories.open"
              >
                <div class="text-sm text-gray-900">
                  {{ $t('All categories') }}
                </div>
                <div class="flex items-center gap-5">
                  <span
                    class="inline-flex items-center rounded-lg bg-green-50 px-2 py-1 text-xs/4 text-green-600 ring-1 ring-inset ring-green-500/10"
                    :class="selectedCategoryIds.length > 0 ? 'visible' : 'invisible'"
                  >
                    {{ selectedCategoryIds.length }} {{ $t('selected') }}
                  </span>
                  <component
                    is="IconChevronDown"
                    class="w-4 h-4 text-gray-400"
                    :class="sections.categories.open ? 'rotate-180' : ''"
                  />
                </div>
              </div>

              <div v-if="sections.categories.open" class="grid gird-cols-1 md:grid-cols-4 gap-4 my-3">
                <div v-for="cat in ui.categories" :key="`cat-item-${cat.id}`">
                  <div class="flex items-center gap-x-2">
                    <div class="flex h-6 shrink-0 items-center">
                      <div class="group grid size-4 grid-cols-1">
                        <input
                          v-model="cat.checked"
                          :id="`cat-${cat.id}`"
                          :name="`cat-${cat.id}`"
                          type="checkbox"
                          class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                          @change="onToggleCategory(cat)"
                        />
                        <svg
                          class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white"
                          viewBox="0 0 14 14"
                          fill="none"
                        >
                          <path
                            class="opacity-0 group-has-checked:opacity-100"
                            d="M3 8L6 11L11 3.5"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                          />
                        </svg>
                      </div>
                    </div>
                    <label :for="`cat-${cat.id}`" class="text-sm text-gray-900">
                      {{ cat.name }}
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- SUB-CATEGORIES (pro Kategorie einklappbar) -->
            <div class="card white px-4" v-if="Object.keys(ui.subCategoriesByCategory).length">
              <div
                class="flex items-center select-none justify-between duration-200 ease-in-out cursor-pointer py-3"
                @click="sections.subCategories.open = !sections.subCategories.open"
              >
                <div class="text-sm text-gray-900">
                  {{ $t('Sub-Categories') }}
                </div>
                <div class="flex items-center gap-5">
                  <span
                    class="inline-flex items-center rounded-lg bg-green-50 px-2 py-1 text-xs/4 text-green-600 ring-1 ring-inset ring-green-500/10"
                    :class="selectedSubCategoryIds.length > 0 ? 'visible' : 'invisible'"
                  >
                    {{ selectedSubCategoryIds.length }} {{ $t('selected') }}
                  </span>
                  <component
                    is="IconChevronDown"
                    class="w-4 h-4 text-gray-400"
                    :class="sections.subCategories.open ? 'rotate-180' : ''"
                  />
                </div>
              </div>

              <div v-if="sections.subCategories.open" class="space-y-2 my-3">
                <div
                  v-for="(subs, catName) in ui.subCategoriesByCategory"
                  :key="`sub-group-${catName}`"
                  class="border border-gray-100 rounded-lg"
                >
                  <div
                    class="flex items-center select-none justify-between cursor-pointer px-4 py-2"
                    @click="subs.open = !subs.open"
                  >
                    <div class="text-xs font-medium text-gray-700">{{ catName }}</div>
                    <div class="flex items-center gap-5">
                      <span
                        class="inline-flex items-center rounded-lg bg-green-50 px-2 py-1 text-xs/4 text-green-600 ring-1 ring-inset ring-green-500/10"
                        :class="subs.items.filter(s => s.checked).length > 0 ? 'visible' : 'invisible'"
                      >
                        {{ subs.items.filter(s => s.checked).length }} {{ $t('selected') }}
                      </span>
                      <component
                        is="IconChevronDown"
                        class="w-4 h-4 text-gray-400"
                        :class="subs.open ? 'rotate-180' : ''"
                      />
                    </div>
                  </div>

                  <div v-if="subs.open" class="grid gird-cols-1 md:grid-cols-4 gap-4 px-4 py-3">
                    <div v-for="sub in subs.items" :key="`sub-${sub.id}`">
                      <div class="flex items-center gap-x-2">
                        <div class="flex h-6 shrink-0 items-center">
                          <div class="group grid size-4 grid-cols-1">
                            <input
                              v-model="sub.checked"
                              :id="`sub-${sub.id}`"
                              :name="`sub-${sub.id}`"
                              type="checkbox"
                              class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                            />
                            <svg
                              class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white"
                              viewBox="0 0 14 14"
                              fill="none"
                            >
                              <path
                                class="opacity-0 group-has-checked:opacity-100"
                                d="M3 8L6 11L11 3.5"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                              />
                            </svg>
                          </div>
                        </div>
                        <label :for="`sub-${sub.id}`" class="text-sm text-gray-900">
                          {{ sub.name }}
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- PROPERTIES -->
          <div class="py-1">
              <div class="text-white bg-gray-900 rounded-lg px-4 py-2 font-lexend shadow text-sm">
                  {{ $t('Properties') }}
              </div>

              <div class="space-y-2 mt-2">
                  <div class="card white px-4">
                      <div
                          class="flex items-center select-none justify-between duration-200 ease-in-out cursor-pointer py-3"
                          @click="sections.properties.open = !sections.properties.open"
                      >
                          <div class="text-sm text-gray-900">
                              {{ $t('All properties') }}
                          </div>
                          <div class="flex items-center gap-5">
            <span
                class="inline-flex items-center rounded-lg bg-green-50 px-2 py-1 text-xs/4 text-green-600 ring-1 ring-inset ring-green-500/10"
                :class="filledPropertyCount > 0 ? 'visible' : 'invisible'"
            >
              {{ filledPropertyCount }} {{ $t('selected') }}
            </span>
                              <component
                                  is="IconChevronDown"
                                  class="w-4 h-4 text-gray-400"
                                  :class="sections.properties.open ? 'rotate-180' : ''"
                              />
                          </div>
                      </div>

                      <div v-if="sections.properties.open" class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 my-3">
                          <div v-for="prop in ui.properties" :key="`prop-${prop.id}`" class="flex items-center gap-2 w-full flex-wrap">
                              <label class="text-sm text-gray-900 min-w-36">{{ prop.name }}</label>

                              <!-- Spezielle Properties: ROOM -->
                              <InventoryCombobox
                                  v-if="prop.type === 'room'"
                                  v-model="prop.value"
                                  :items="rooms"
                                  :return-object="false"
                                  by="id"
                                  option-label="name"
                                  option-key="id"
                                  :placeholder="$t('Please select a Room')"
                                  :search-fields="['name']"
                                  coerce="number"
                              />

                              <!-- Spezielle Properties: MANUFACTURER -->
                              <InventoryCombobox
                                  v-else-if="prop.type === 'manufacturer'"
                                  v-model="prop.value"
                                  :items="manufacturers"
                                  :return-object="false"
                                  by="id"
                                  option-label="name"
                                  option-key="id"
                                  :placeholder="$t('Please select a Manufacturer')"
                                  :search-fields="['name']"
                                  coerce="number"
                              />

                              <!-- Allgemein: vordefinierte Werte -->
                              <select
                                  v-else-if="prop.select_values && prop.select_values.length"
                                  v-model="prop.value"
                                  class="border border-gray-300 bg-white shadow-md rounded px-2 py-3 text-xs w-full"
                              >
                                  <option value="">{{ $t('Any') }}</option>
                                  <option v-for="v in prop.select_values" :key="`${prop.id}-${v}`" :value="v">
                                      {{ v }}
                                  </option>
                              </select>

                              <!-- Fallback: Text -->
                              <BaseInput
                                  v-else
                                  :id="`prop-input-${prop.id}`"
                                  :label="prop.name"
                                  v-model="prop.value"
                                  type="text"
                              />

                              <!-- Clear -->
                              <button
                                  type="button"
                                  class="text-xs underline underline-offset-2 text-gray-400 hover:text-gray-600"
                                  v-if="prop.value"
                                  @click="prop.value = ''"
                              >
                                  {{ $t('Clear') }}
                              </button>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="px-5 py-4">
      <div class="flex items-center justify-between">
        <div>
          <div
            @click="resetFilter"
            class="underline text-artwork-buttons-create text-xs underline-offset-2 cursor-pointer hover:text-artwork-buttons-hover duration-200 ease-in-out"
          >
            {{ $t('Reset') }}
          </div>
        </div>
        <div class="flex items-center gap-4">
          <ArtworkBaseModalButton variant="primary" @click="applyFilter">
            {{ $t('Apply') }}
          </ArtworkBaseModalButton>
        </div>
      </div>
    </div>
  </ArtworkBaseModal>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import ArtworkBaseModalButton from '@/Artwork/Buttons/ArtworkBaseModalButton.vue'
import TinyPageHeadline from '@/Components/Headlines/TinyPageHeadline.vue'
import { XIcon } from '@heroicons/vue/outline'
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import InventoryCombobox from "@/Pages/Inventory/Components/Article/Modals/Components/InventoryCombobox.vue";

const emits = defineEmits(['close'])
const page = usePage()

/**
 * Eingehende Daten aus Inertia::share (siehe InventoryUserFilterShareService)
 */
const categories = computed(() => page.props?.categories ?? [])
const filterableProperties = computed(() => page.props?.filterable_properties ?? [])
const userFilter = computed(() => page.props?.user_filter ?? { category_ids: [], sub_category_ids: [], property_filters: {} })
const rooms = computed(() => page.props?.rooms ?? [])                   // [{id,name}]
const manufacturers = computed(() => page.props?.manufacturers ?? [])   // [{id,name}]
/**
 * UI-Modelle (mit checked/value + open Zuständen),
 * damit wir wie im Calendar-Filter arbeiten können.
 */
const ui = reactive({
  categories: [], // [{id,name,checked}]
  subCategoriesByCategory: {
    // [catName]: { open: true/false, items: [{id,name,checked}] }
  },
  properties: [] // [{id,name,select_values?, value:''}]
})

const sections = reactive({
  categories: { open: true },
  subCategories: { open: true },
  properties: { open: true }
})

/* ---------- Helpers ---------- */

const selectedCategoryIds = computed(() => ui.categories.filter(c => c.checked).map(c => c.id))
const selectedSubCategoryIds = computed(() => {
  const ids = []
  Object.values(ui.subCategoriesByCategory).forEach(group => {
    group.items.forEach(s => s.checked && ids.push(s.id))
  })
  return ids
})
const filledPropertyCount = computed(() => ui.properties.filter(p => !!p.value).length)

const totalActiveCount = computed(() =>
  selectedCategoryIds.value.length + selectedSubCategoryIds.value.length + filledPropertyCount.value
)


// Hilfsfunktionen, um ID -> Name für Chips umzusetzen
function resolvePropertyDisplay(prop, val) {
    if (val === '' || val === null || val === undefined) return ''
    if (prop.type === 'room') {
        const r = rooms.value.find(r => String(r.id) === String(val))
        return r ? r.name : val
    }
    if (prop.type === 'manufacturer') {
        const m = manufacturers.value.find(m => String(m.id) === String(val))
        return m ? m.name : val
    }
    return String(val)
}

/**
 * Aktive Filter für die Chip-Anzeige
 */
const activeFilters = computed(() => {
    const cats = ui.categories.filter(c => c.checked).map(c => ({ id: c.id, name: c.name }))
    const subs = []
    Object.values(ui.subCategoriesByCategory).forEach(group => {
        group.items.forEach(s => { if (s.checked) subs.push({ id: s.id, name: s.name }) })
    })
    const props = ui.properties
        .filter(p => p.value !== '' && p.value !== null && p.value !== undefined)
        .map(p => ({ id: p.id, name: p.name, value: resolvePropertyDisplay(p, p.value) }))

    return { categories: cats, subCategories: subs, properties: props }
})

function removeActiveFilter(filter, type) {
    if (type === 'category') {
        const item = ui.categories.find(c => c.id === filter.id)
        if (item) item.checked = false
    } else if (type === 'subcategory') {
        Object.values(ui.subCategoriesByCategory).forEach(group => {
            const found = group.items.find(s => s.id === filter.id)
            if (found) found.checked = false
        })
    } else if (type === 'property') {
        const p = ui.properties.find(pp => pp.id === filter.id)
        if (p) p.value = ''
    }
}

/**
 * Beim (Ent)Selektieren einer Kategorie:
 * - keine harte Einschränkung der sichtbaren Subcategories (UX: weiterhin sichtbar),
 *   aber du kannst hier z.B. gruppen-Open je nach Auswahl steuern.
 */
function onToggleCategory(_) {}

function resetFilter() {
    ui.categories.forEach(c => (c.checked = false))
    Object.values(ui.subCategoriesByCategory).forEach(group => group.items.forEach(s => (s.checked = false)))
    ui.properties.forEach(p => (p.value = ''))
    applyFilter()
}

function applyFilter() {
    const property_filters = {}
    ui.properties.forEach(p => {
        if (p.value !== '' && p.value !== null && p.value !== undefined) {
            property_filters[p.id] = p.value   // bei room/manufacturer: die jeweilige ID
        }
    })

    const data = {
        category_ids: selectedCategoryIds.value,
        sub_category_ids: selectedSubCategoryIds.value,
        property_filters
    }

    router.post(route('inventory.filter.store'), data, {
        preserveScroll: true,
        onFinish: () => emits('close')
    })
}

function buildUiModels() {
    // Kategorien
    ui.categories = (categories.value || []).map(c => ({
        id: c.id, name: c.name, checked: false
    }))

    // Subkategorien (gruppiert)
    ui.subCategoriesByCategory = {}
    ;(categories.value || []).forEach(cat => {
        const items = (cat.sub_categories || []).map(s => ({ id: s.id, name: s.name, checked: false }))
        ui.subCategoriesByCategory[cat.name] = { open: false, items }
    })

    // Properties (NEU: type übernehmen)
    ui.properties = (filterableProperties.value || []).map(p => ({
        id: p.id,
        name: p.name,
        type: p.type ?? null,                 // 'room' | 'manufacturer' | 'text' | 'select' | ...
        select_values: p.select_values || [],
        value: ''
    }))
}

function restoreFromUserFilter() {
    const uf = userFilter.value || {}

    const catSet = new Set((uf.category_ids || []).map(Number))
    ui.categories.forEach(c => (c.checked = catSet.has(Number(c.id))))

    const subSet = new Set((uf.sub_category_ids || []).map(Number))
    Object.values(ui.subCategoriesByCategory).forEach(group => {
        group.items.forEach(s => (s.checked = subSet.has(Number(s.id))))
        group.open = group.items.some(s => s.checked)
    })

    const propFilters = uf.property_filters || {}
    ui.properties.forEach(p => {
        const incoming = propFilters[p.id] ?? propFilters[String(p.id)]
        p.value =
            typeof incoming === 'object' && incoming !== null && 'value' in incoming
                ? (incoming.value ?? '')
                : (incoming ?? '')
    })

    sections.categories.open = true
    sections.subCategories.open = true
    sections.properties.open = true
}

onMounted(() => {
    buildUiModels()
    restoreFromUserFilter()
})
</script>

<style scoped>
/* optional: kleine Politur, ansonsten nutzt es deine bestehenden Utility-Klassen */
</style>
