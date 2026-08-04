<script setup>
import draggable from "vuedraggable";
import DropNewComponent from "@/Pages/Settings/Components/DropNewComponent.vue";
import AddEditTabModal from "@/Pages/Settings/Components/AddEditTabModal.vue";
import SidebarConfigElement from "@/Pages/Settings/Components/Sidebar/SidebarConfigElement.vue";
import SingleComponent from "@/Pages/Settings/Components/SingleComponent.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import ErrorComponent from "@/Layouts/Components/ErrorComponent.vue";

import { Switch } from "@headlessui/vue";
import { router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import {
    IconChevronDown,
    IconChevronUp,
    IconEdit,
    IconTrash,
    IconEye,
    IconUsers,
    IconBuildingCommunity,
    IconLock,
    IconWorld,
} from "@tabler/icons-vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";

const props = defineProps({
    tab: { type: Object, required: true },
    allTabs: { type: Array, required: true },
});

const dragging = ref(false);
const showAddEditModal = ref(false);
const tabClosed = ref(true);
const showComponentTabCannotBeDeletedModal = ref(false);

const lastComponentOrder = computed(() => (props.tab?.components?.length || 0) + 1);
const componentCount = computed(() => props.tab?.components?.length || 0);

const visibleUsers = computed(() => props.tab?.visible_users ?? []);
const visibleDepartments = computed(() => props.tab?.visible_departments ?? []);

const isRestricted = computed(() => !props.tab?.visible_for_all);
const accessUserCount = computed(() => visibleUsers.value.length);
const accessDeptCount = computed(() => visibleDepartments.value.length);

const hasAccessList = computed(() => accessUserCount.value > 0 || accessDeptCount.value > 0);

// Schöne Namen (provider_name existiert nicht)
function userLabel(u) {
    const fn = (u?.first_name ?? "").trim();
    const ln = (u?.last_name ?? "").trim();
    const full = `${fn} ${ln}`.trim();
    return full || `#${u?.id ?? "?"}`;
}

function updateComponentOrder(components) {
    components.forEach((component, index) => {
        component.order = index + 1;
    });
    const minimalComponents = components.map((c) => ({ id: c.id, order: c.order }));
    router.post(
        route("tab.update.component.order", { projectTab: props.tab.id }),
        { components: minimalComponents },
        { preserveScroll: true }
    );
}

function removeTab() {
    if ((props.allTabs?.length || 0) === 1) {
        showComponentTabCannotBeDeletedModal.value = true;
        return;
    }
    router.delete(route("tab.destroy", { projectTab: props.tab.id }));
}

function editTab() {
    showAddEditModal.value = true;
}

function openTab() {
    tabClosed.value = false;
}

function updateDefaultTab() {
    router.patch(route("tab.update.default", { projectTab: props.tab.id }), {
        default: !props.tab.default,
    });
}
</script>

<template>
    <!-- Container -->
    <div
        class="rounded-2xl border border-border-subtle bg-white/70 backdrop-blur px-4 py-5 shadow-sm transition"
        :class="dragging ? 'ring-2 ring-success-border' : ''"
    >
        <!-- Header -->
        <div class="relative">
            <div
                class="flex items-start justify-between gap-4 pl-3 pb-3 border-b border-dashed border-border-subtle select-none"
                :class="dragging ? 'cursor-grabbing' : 'cursor-grab'"
            >
                <!-- Left -->
                <button
                    type="button"
                    class="flex items-start gap-3 outline-none w-full"
                    @click="tabClosed = !tabClosed"
                    :aria-expanded="!tabClosed"
                >
                    <!-- Chevron -->
                    <span
                        class="grid place-items-center size-8 rounded-2xl border bg-white/70 border-border-subtle transition"
                        aria-hidden="true"
                    >
                        <IconChevronDown
                            v-if="tabClosed"
                            class="h-4 w-4 text-text-muted transition-transform duration-200"
                        />
                        <IconChevronUp
                            v-else
                            class="h-4 w-4 text-text-muted transition-transform duration-200"
                        />
                    </span>

                    <div class="flex flex-col items-start min-w-0 flex-1">
                        <!-- Title row -->
                        <div class="flex items-center gap-2 min-w-0 w-full">
                            <h3 class="headline3 text-text truncate" :title="tab.name">{{ tab.name }}</h3>

                            <!-- Default badge -->
                            <span
                                v-if="tab.default"
                                class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] leading-4 border-success-border bg-success-surface text-success shrink-0"
                            >
                                Default
                            </span>

                            <!-- Components count -->
                            <span
                                class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] leading-4 border-border-subtle bg-white/70 text-text-muted shrink-0"
                            >
                                {{ componentCount }} {{ $t("components") }}
                            </span>
                        </div>

                        <!-- Subtitle row -->
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <!-- Visibility pill -->
                            <span
                                v-if="tab.visible_for_all"
                                class="inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-[11px] leading-4 border-border-subtle bg-white/70 text-text-muted"
                            >
                                <IconWorld class="h-3.5 w-3.5" />
                                {{ $t("Visible for all") }}
                            </span>

                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-[11px] leading-4 border-warning-border bg-warning-surface text-warning"
                            >
                                <IconLock class="h-3.5 w-3.5" />
                                {{ $t("Restricted") }}
                            </span>

                            <!-- Access summary -->
                            <span
                                v-if="isRestricted"
                                class="inline-flex items-center gap-2 text-[11px] text-text-subtle"
                            >
                                <span class="inline-flex items-center gap-1">
                                    <IconUsers class="h-3.5 w-3.5" />
                                    {{ accessUserCount }}
                                </span>
                                <span class="opacity-50">•</span>
                                <span class="inline-flex items-center gap-1">
                                    <IconBuildingCommunity class="h-3.5 w-3.5" />
                                    {{ accessDeptCount }}
                                </span>
                            </span>

                            <!-- Warning if restricted but empty -->
                            <span
                                v-if="isRestricted && !hasAccessList"
                                class="inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-[11px] leading-4 border-danger-border bg-danger-surface text-danger"
                            >
                                <IconEye class="h-3.5 w-3.5" />
                                {{ $t("No access configured") }}
                            </span>
                        </div>

                        <!-- Access chips (clean + limited) -->
                        <div v-if="isRestricted && hasAccessList" class="mt-2 flex flex-wrap gap-1.5">
                            <!-- Users -->
                            <span
                                v-for="u in visibleUsers.slice(0, 6)"
                                :key="'vu-' + u.id"
                                class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] bg-white/70 border-border-subtle text-text-muted"
                                :title="userLabel(u)"
                            >
                                {{ userLabel(u) }}
                            </span>

                            <!-- Depts -->
                            <span
                                v-for="d in visibleDepartments.slice(0, 6)"
                                :key="'vd-' + d.id"
                                class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] bg-white/70 border-border-subtle text-text-muted"
                                :title="d.name"
                            >
                                {{ d.name }}
                            </span>

                            <!-- Overflow -->
                            <span
                                v-if="accessUserCount + accessDeptCount > 12"
                                class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] bg-surface-sunken border-border-subtle text-text-muted"
                            >
                                +{{ (accessUserCount + accessDeptCount) - 12 }}
                            </span>
                        </div>

                        <!-- Helper text -->
                        <p class="mt-2 text-[11px] text-text-subtle">
                            {{ $t("Build and order components for this tab") }}
                        </p>
                    </div>
                </button>

                <!-- Right actions -->
                <div class="flex items-center gap-3 shrink-0">
                    <!-- Default Switch -->
                    <div class="flex items-center gap-2">
                        <Switch
                            v-model="tab.default"
                            @click="updateDefaultTab"
                            :class="[ tab.default ? 'bg-artwork-buttons-create' : 'bg-border-subtle',
                                'relative inline-flex h-5 w-10 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-artwork-buttons-create/70 focus:ring-offset-1'
                            ]"
                            :aria-label="$t('Set as default tab')"
                        >
                            <span
                                :class="[ tab.default ? 'translate-x-5' : 'translate-x-0',
                                    'pointer-events-none relative inline-block size-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                ]"
                            />
                        </Switch>
                        <span class="text-[11px] text-text-muted">{{ $t("Default") }}</span>
                    </div>

                    <!-- Menu -->
                    <BaseMenu has-no-offset white-menu-background>
                        <BaseMenuItem :icon="IconEdit" title="Edit" @click="editTab" white-menu-background />
                        <BaseMenuItem :icon="IconTrash" title="Delete" @click="removeTab" white-menu-background />
                    </BaseMenu>
                </div>
            </div>
        </div>

        <!-- Open -->
        <div v-if="!tabClosed" class="mt-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <div class="my-2">
                        <h3 class="text-sm font-semibold text-text-muted tracking-wide">
                            {{ $t("Main area") }}
                        </h3>
                        <p class="mt-0.5 text-[11px] text-text-subtle">
                            {{ $t("These components are displayed one below the other in the tab") }}
                        </p>
                    </div>

                    <DropNewComponent
                        :is-sidebar="false"
                        :all-tabs="allTabs"
                        :tab="tab"
                        :order="1"
                        @tab-opened="openTab"
                    />

                    <div class="transition" :class="dragging ? 'ring-2 ring-success-border' : ''">
                        <draggable
                            ghost-class="opacity-50"
                            key="draggableKey"
                            item-key="id"
                            :list="tab.components"
                            @start="dragging = true"
                            @end="dragging = false"
                            @change="updateComponentOrder(tab.components)"
                        >
                            <template #item="{ element }">
                                <div
                                    v-show="!element.temporary"
                                    :key="element.id"
                                    class="rounded-lg"
                                >
                                    <SingleComponent :element="element" :tab="tab" :all-tabs="allTabs" />
                                    <DropNewComponent
                                        :is-sidebar="false"
                                        :all-tabs="allTabs"
                                        :tab="tab"
                                        :order="element.order + 1"
                                        @tab-opened="openTab"
                                    />
                                </div>
                            </template>
                        </draggable>

                        <div
                            v-if="!tab.components || tab.components.length === 0"
                            class="text-[12px] text-text-subtle px-2 py-4 text-center"
                        >
                            {{ $t("Drop components here to build your tab layout") }}
                        </div>
                    </div>
                </div>

                <SidebarConfigElement :tab="tab" />
            </div>
        </div>

        <!-- Closed -->
        <div v-else class="mt-4">
            <DropNewComponent
                :all-tabs="allTabs"
                :is-sidebar="false"
                :tab="tab"
                :order="lastComponentOrder"
                @tab-opened="openTab"
            />
        </div>
    </div>

    <!-- Modals -->
    <AddEditTabModal v-if="showAddEditModal" :tab-to-edit="tab" @close="showAddEditModal = false" />

    <error-component
        v-if="showComponentTabCannotBeDeletedModal"
        :titel="$t('An error has occurred')"
        :description="$t('At least one project tab must be available')"
        :confirm="$t('Close message')"
        @closed="showComponentTabCannotBeDeletedModal = false"
    />
</template>
