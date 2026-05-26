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
                <!-- Normale Komponenten (gruppiert) -->
                <div v-if="Object.keys(filteredComponents).length > 0" class="mb-6">
                    <div v-for="(componentsArray, groupKey) in filteredComponents" :key="groupKey">
                        <h2 class="mb-2 pt-3 first:pt-0 text-sm font-semibold text-gray-900">
                            {{ $t(groupKey) }}
                        </h2>

                        <div class="flex flex-wrap gap-3 mb-3">
                            <DropComponentsToolTip
                                v-for="component in componentsArray"
                                :key="component.id ?? component.name"
                                :top="true"
                                :tooltip-text="component.special ? $t(component.name) : component.name"
                            >
                                <div
                                    class="group relative flex h-28 w-28 flex-col items-center justify-center truncate rounded-xl border border-gray-200 bg-white p-4 transition hover:border-gray-300 hover:shadow-sm"
                                >
                                    <SingleComponent :component="component" />
                                </div>
                            </DropComponentsToolTip>
                        </div>
                    </div>
                </div>

                <!-- Fallback: Keine normalen Komponenten -->
                <div v-else class="mb-6 text-center text-sm text-gray-500 py-8">
                    {{ $t('No components found') }}
                </div>

                <!-- Special Components OHNE RecycleScroller -->
                <div v-if="filteredSpecialComponents.length">
                    <h2 class="mb-2 text-sm font-semibold text-gray-900">
                        {{ $t('Special components') }}
                    </h2>

                    <div class="flex flex-wrap gap-3 mb-3">
                        <DropComponentsToolTip
                            v-for="component in filteredSpecialComponents"
                            :key="component.id ?? component.name"
                            :top="true"
                            :tooltip-text="component.special ? $t(component.name) : component.name"
                        >
                            <div
                                class="group relative flex h-28 w-28 flex-col items-center justify-center truncate rounded-xl border border-gray-200 bg-white p-4 transition hover:border-gray-300 hover:shadow-sm"
                            >
                                <SingleComponent :component="component" />
                            </div>
                        </DropComponentsToolTip>
                    </div>
                </div>


                <!-- Fallback: Keine Special Komponenten -->
                <div v-else-if="filteredSpecialComponents.length === 0 && Object.keys(filteredComponents).length === 0" class="text-center text-sm text-gray-500 py-8">
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
