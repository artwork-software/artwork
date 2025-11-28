<template>
    <ProjectSettingsHeader
        :title="$t('Component Settings')"
        :description="$t('Define global settings for projects.')"
    >
        <!-- Actions -->
        <template #actions>
            <button class="ui-button-add inline-flex items-center gap-2" @click="showAddNewComponentModal = true">
                <IconPlus class="size-5" stroke-width="1" />
                {{ $t('Create a new component') }}
            </button>
        </template>

        <div class="mt-4">
            <!-- Toolbar -->
            <div class="mb-3 flex items-center justify-end">
                <div class="w-44 md:w-56 lg:w-72">
                    <BaseInput id="search" type="text" name="search" v-model="searchComponent" :label="$t('Search')" />
                </div>
            </div>

            <!-- Karten -->
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-xs">
                <!-- Normale Komponenten (gruppiert) mit Virtual Scrolling -->
                <RecycleScroller
                    v-if="virtualScrollItems.length > 0"
                    :items="virtualScrollItems"
                    size-field="size"
                    key-field="key"
                    :buffer="300"
                    class="mb-6"
                    style="height: calc(80vh - 350px); min-height: 500px;"
                    v-slot="{ item }"
                >
                    <!-- Group Header -->
                    <div v-if="item.type === 'header'" class="mb-2 pt-3 first:pt-0">
                        <h2 class="text-sm font-semibold text-gray-900">
                            {{ $t(item.groupKey) }}
                        </h2>
                    </div>

                    <!-- Component Row -->
                    <div
                        v-else-if="item.type === 'row'"
                        class="grid w-full grid-cols-1 gap-3 mb-3 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 3xl:grid-cols-12"
                    >
                        <DropComponentsToolTip
                            v-for="component in item.components"
                            :key="component.id ?? component.name"
                            :top="true"
                            :tooltip-text="component.special ? $t(component.name) : component.name"
                        >
                            <div
                                class="group relative flex h-28 min-w-28 flex-col items-center justify-center truncate rounded-xl border border-gray-200 bg-white p-4 transition hover:border-gray-300 hover:shadow-sm"
                            >
                                <SingleComponent :component="component" />
                            </div>
                        </DropComponentsToolTip>
                    </div>
                </RecycleScroller>

                <!-- Fallback: Keine normalen Komponenten -->
                <div v-else class="mb-6 text-center text-sm text-gray-500 py-8">
                    {{ $t('No components found') }}
                </div>

                <!-- Special Components OHNE RecycleScroller -->
                <div v-if="filteredSpecialComponents.length">
                    <h2 class="mb-2 text-sm font-semibold text-gray-900">
                        {{ $t('Special components') }}
                    </h2>

                    <div class="grid grid-cols-1 gap-3 mb-3 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 2xl:grid-cols-8">
                        <DropComponentsToolTip
                            v-for="component in filteredSpecialComponents"
                            :key="component.id ?? component.name"
                            :top="true"
                            :tooltip-text="component.special ? $t(component.name) : component.name"
                        >
                            <div
                                class="group relative flex h-28 flex-col items-center justify-center truncate rounded-xl border border-gray-200 bg-white p-4 transition hover:border-gray-300 hover:shadow-sm"
                            >
                                <SingleComponent :component="component" />
                            </div>
                        </DropComponentsToolTip>
                    </div>
                </div>


                <!-- Fallback: Keine Special Komponenten -->
                <div v-else-if="filteredSpecialComponents.length === 0 && virtualScrollItems.length === 0" class="text-center text-sm text-gray-500 py-8">
                    {{ $t('No special components found') }}
                </div>
            </div>
        </div>

        <!-- Modal: Neu anlegen -->
        <ComponentModal
            v-if="showAddNewComponentModal"
            :show="showAddNewComponentModal"
            mode="create"
            :tab-component-types="tabComponentTypes"
            @close="showAddNewComponentModal = false"
        />
    </ProjectSettingsHeader>
</template>

<script setup lang="ts">
import { computed, ref, getCurrentInstance } from 'vue'
import { RecycleScroller } from 'vue-virtual-scroller'
import 'vue-virtual-scroller/dist/vue-virtual-scroller.css'
import ProjectSettingsHeader from '@/Pages/Settings/Components/ProjectSettingsHeader.vue'
import SingleComponent from '@/Pages/Settings/ComponentManagement/Components/SingleComponent.vue'
import ComponentModal from '@/Pages/Settings/ComponentManagement/Components/ComponentModal.vue'
import DropComponentsToolTip from '@/Components/ToolTips/DropComponentsToolTip.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import { IconPlus } from '@tabler/icons-vue'

defineOptions({ name: 'ComponentSettingsIndex' })

const props = defineProps<{
    components: Record<string, Array<{ id?: number|string; name: string; special?: boolean; type?: string; data?: any }>>
    componentsSpecial: Array<{ id?: number|string; name: string; special?: boolean; type?: string; data?: any }>
    tabComponentTypes: Record<string, any>
}>()

const searchComponent = ref('')
const showAddNewComponentModal = ref(false)

const { proxy } = getCurrentInstance() as any
const t = (s: string) => (proxy?.$t ? proxy.$t(s) : s)

/** Normale Komponenten gruppiert filtern */
const filteredComponents = computed(() => {
    const query = searchComponent.value.toLowerCase().trim()
    const result: Record<string, any[]> = {}
    if (!props.components) return result

    for (const [key, list] of Object.entries(props.components)) {
        const filtered = (list || []).filter((c) =>
            (c?.name || '').toString().toLowerCase().includes(query)
        )
        if (filtered.length) result[key] = filtered
    }
    return result
})

/** Specials filtern – inkl. Übersetzung im Namen */
const filteredSpecialComponents = computed(() => {
    const query = searchComponent.value.toLowerCase().trim()
    return (props.componentsSpecial || []).filter((c) =>
        t(c?.name || '').toLowerCase().includes(query)
    )
})

// Hilfsfunktion: Teilt ein Array in Chunks für Grid-Rows
function chunkArray<T>(array: T[], size: number): T[][] {
    const result: T[][] = []
    for (let i = 0; i < array.length; i += size) {
        result.push(array.slice(i, i + size))
    }
    return result
}

// Konstanten oben definieren
const HEADER_SIZE = 64      // oder was DevTools dir als Höhe anzeigt
const ROW_SIZE = 176        // z.B. h-28 (112) + mb-3 + gap + Sicherheitsaufschlag

/** Normale Komponenten: Virtual Scrolling */
const virtualScrollItems = computed(() => {
    const items: Array<{ type: 'header' | 'row'; key: string; size: number; groupKey?: string; components?: any[] }> = []

    for (const [groupKey, componentsArray] of Object.entries(filteredComponents.value)) {
        items.push({
            type: 'header',
            key: `header-${groupKey}`,
            groupKey,
            size: HEADER_SIZE, // <-- angepasst
        })

        const rows = chunkArray(componentsArray, 5)
        rows.forEach((rowComponents, rowIndex) => {
            items.push({
                type: 'row',
                key: `row-${groupKey}-${rowIndex}`,
                groupKey,
                components: rowComponents,
                size: ROW_SIZE,  // <-- angepasst
            })
        })
    }

    return items
})


/** Flache Liste für Virtual Scrolling: Special Komponenten */
const virtualScrollSpecialItems = computed(() => {
    const items: Array<{ type: 'header' | 'row'; key: string; size: number; components?: any[] }> = []

    if (filteredSpecialComponents.value.length > 0) {
        // Header
        items.push({
            type: 'header',
            key: 'header-special',
            size: 30 // Header: text-sm + mb-2, kein pt-3
        })

        // Komponenten in Rows aufteilen (8 pro Row für 2xl:grid-cols-8)
        const rows = chunkArray(filteredSpecialComponents.value, 8)
        rows.forEach((rowComponents, rowIndex) => {
            items.push({
                type: 'row',
                key: `special-row-${rowIndex}`,
                components: rowComponents,
                size: 144 // Row: h-28 (112px) + mb-3 (12px) + gap-3 (12px)
            })
        })
    }

    return items
})
</script>

<style scoped>
.shadow-xs {
    --tw-shadow: 0 1px 2px rgb(0 0 0 / 0.05);
    --tw-shadow-colored: 0 1px 2px var(--tw-shadow-color);
    box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000),
    var(--tw-ring-shadow, 0 0 #0000),
    var(--tw-shadow);
}
</style>
