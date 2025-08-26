<template>
    <ArtworkBaseModal modal-size="max-w-4xl" title="Inventory Filter" description="Filter inventory articles by category, sub-category and properties." @close="$emit('close')" full-modal>
        <div class="p-5">
            <div class="space-y-4">
                <div>
                    <TinyPageHeadline :title="$t('Categories')" :description="$t('Select categories to filter articles.')" />
                    <div class="flex flex-wrap gap-2 mt-2">
                        <div v-for="cat in filterOptions.categories" :key="cat.id" class="group block cursor-pointer bg-blue-50 px-2 py-1.5 rounded-full border border-blue-200">
                            <label>
                                <input type="checkbox" v-model="selectedCategories" :value="cat.id" />
                                <span class="ml-2">{{ cat.name }}</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div>
                    <TinyPageHeadline :title="$t('Sub-Categories')" :description="$t('Select sub-categories to filter articles.')" />
                    <div class="flex flex-wrap gap-2 mt-2">
                        <div v-for="sub in allSubCategories" :key="sub.id" class="group block cursor-pointer bg-green-50 px-2 py-1.5 rounded-full border border-green-200">
                            <label>
                                <input type="checkbox" v-model="selectedSubCategories" :value="sub.id" />
                                <span class="ml-2">{{ sub.name }}</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div>
                    <TinyPageHeadline :title="$t('Properties')" :description="$t('Select filterable properties.')" />
                    <div class="flex flex-wrap gap-2 mt-2">
                        <div v-for="prop in filterOptions.filterable_properties" :key="prop.id" class="group block cursor-pointer bg-yellow-50 px-2 py-1.5 rounded-full border border-yellow-200">
                            <label>
                                <span>{{ prop.name }}</span>
                                <select v-if="prop.select_values && prop.select_values.length" v-model="selectedProperties[prop.id]" class="ml-2 border rounded px-1 py-0.5">
                                    <option value="">{{ $t('Any') }}</option>
                                    <option v-for="val in prop.select_values" :key="val" :value="val">{{ val }}</option>
                                </select>
                                <input v-else type="text" v-model="selectedProperties[prop.id]" class="ml-2 border rounded px-1 py-0.5" />
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="px-5 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <div @click="resetFilter" class="underline text-artwork-buttons-create text-xs underline-offset-2 cursor-pointer hover:text-artwork-buttons-hover duration-200 ease-in-out">{{ $t('Reset') }}</div>
                </div>
                <div class="flex items-center gap-4">
                    <ArtworkBaseModalButton variant="primary" @click="applyFilter">
                        {{ $t('Apply') }}
                    </ArtworkBaseModalButton>
                </div>
            </div>
        </div>

        <pre>
            {{  filterOptions  }}
        </pre>
    </ArtworkBaseModal>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';

const emits = defineEmits(['close']);

const page = usePage();
const filterOptions = computed(() => page.props.inventoryUserFilter?.categories ?? []);
const filterableProperties = computed(() => page.props.inventoryUserFilter?.filterable_properties ?? []);
const userFilter = computed(() => page.props.inventoryUserFilter?.user_filter ?? { category_ids: [], sub_category_ids: [], property_filters: {} });

const selectedCategories = ref([]);
const selectedSubCategories = ref([]);
const selectedProperties = ref({});

const allSubCategories = computed(() => {
    let subs = [];
    filterOptions.value.forEach(cat => {
        if (cat.sub_categories) {
            subs = subs.concat(cat.sub_categories);
        }
    });
    return subs;
});

const resetFilter = () => {
    selectedCategories.value = [];
    selectedSubCategories.value = [];
    selectedProperties.value = {};
    applyFilter();
};

const applyFilter = () => {
    const data = {
        category_ids: selectedCategories.value,
        sub_category_ids: selectedSubCategories.value,
        property_filters: selectedProperties.value
    };
    router.post(route('inventory.filter.store'), data, {
        preserveScroll: true,
        onFinish: () => emits('close')
    });
};

onMounted(() => {
    selectedCategories.value = userFilter.value.category_ids || [];
    selectedSubCategories.value = userFilter.value.sub_category_ids || [];
    selectedProperties.value = userFilter.value.property_filters || {};
});
</script>

<style scoped>
</style>
