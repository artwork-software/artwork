<template>
    <AppLayout :title="`${project?.name} (${currentTab?.name})`">
        <div :style="{ '--project-header-height': `${headerHeight}px` }">
        <!-- Copy Toast -->
        <transition name="fade" appear>
            <div
                v-show="showCopyUrl"
                class="pointer-events-none fixed inset-x-0 top-5 z-50 sm:flex sm:justify-center sm:px-6 sm:pb-5 lg:px-8"
            >
                <div class="pointer-events-auto flex items-center justify-between gap-x-6 bg-gray-900 px-6 py-2.5 sm:rounded-xl sm:py-3 sm:pl-4 sm:pr-3.5 shadow-lg ring-1 ring-white/10">
                    <PropertyIcon name="IconClipboard" class="size-5 text-blue-400" aria-hidden="true" />
                    <p class="text-sm/6 text-white">{{ $t('Project URL has been copied') }}</p>
                    <button type="button" class="-m-1.5 flex-none p-1.5" @click="showCopyUrl = false">
                        <span class="sr-only">Dismiss</span>
                        <PropertyIcon name="IconX" class="size-5 text-white" aria-hidden="true" />
                    </button>
                </div>
            </div>
        </transition>

        <!-- ===== STICKY PROJECT NAVIGATOR ===== -->
        <div ref="stickyHeaderEl" class="sticky top-0 z-40 w-full inset-x-0 ">
            <!-- Glassy background layer -->
            <div class="bg-white/80 backdrop-blur supports-backdrop-filter:backdrop-blur border-b border-zinc-200/70">
                <div class="artwork-container pb-0! py-3">
                    <div class="flex items-center justify-between gap-3">
                        <!-- Left: Switcher trigger -->
                        <div class="min-w-0 flex items-center gap-3">
                            <button
                                type="button"
                                class="group flex items-center gap-3 rounded-2xl px-2 py-1.5 hover:bg-zinc-50/80 transition min-w-0"
                                @click="toggleProjectSwitcher"
                                :aria-expanded="showProjectSwitcher ? 'true' : 'false'"
                            >
                                <img
                                    :src="keyVisualSrc"
                                    @error="onKeyVisualError"
                                    class="size-10 rounded-xl object-cover ring-1 ring-white shadow-sm shrink-0"
                                    alt=""
                                    loading="lazy"
                                    decoding="async"
                                />

                                <div class="min-w-0 text-left">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <div class="truncate font-lexend font-extrabold tracking-wide text-[15px] text-zinc-900">
                                            {{ project?.name }}
                                        </div>

                                        <div
                                            v-if="projectState"
                                            class="hidden sm:inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-semibold"
                                            :style="statePillStyle"
                                        >
                                            {{ projectState.name }}
                                        </div>

                                        <PropertyIcon
                                            name="IconChevronDown"
                                            class="size-4 text-zinc-400 group-hover:text-zinc-600 transition shrink-0"
                                            aria-hidden="true"
                                        />
                                    </div>

                                    <div class="truncate text-[12px] text-zinc-600">
                                        <span v-if="stickySubline">{{ stickySubline }}</span>
                                        <span v-else class="text-zinc-400">{{ $t('No appointments within this project yet') }}</span>
                                    </div>
                                </div>
                            </button>



                            <div class="mt-2 flex items-center text-[13px] text-zinc-600" v-if="headerObject.project_history.length > 0">
                                <span>{{ $t('last modified') }}:</span>
                                <UserPopoverTooltip
                                    :user="headerObject.project_history[0]?.changer"
                                    :id="headerObject.project_history[0]?.changer.id"
                                    height="4"
                                    width="4"
                                    class="ml-2"
                                />
                                <span class="ml-2 tabular-nums">{{ headerObject.project_history[0]?.created_at }}</span>
                                <button class="inline-flex items-center gap-1 text-artwork-buttons-create hover:text-artwork-buttons-hover transition" @click="openProjectHistoryModal()">
                                    <PropertyIcon name="IconChevronRight" class="-mr-0.5 h-4 w-4" aria-hidden="true" />
                                    {{ $t('View history') }}
                                </button>
                            </div>

                        </div>



                        <!-- Right: Actions -->
                        <div class="flex items-center gap-2 shrink-0">
                            <ToolTipComponent
                                :tooltip-text="$t('Select print layout')"
                                icon="IconPrinter"
                                direction="bottom"
                                @click="showPrintLayoutSelectorModal = true"
                                stroke="2"
                            />
                            <ToolTipComponent
                                :tooltip-text="$t('Copy link')"
                                icon="IconLink"
                                direction="bottom"
                                @click="copyProjectUrlToClipboard"
                                stroke="2"
                            />

                            <BaseMenu menu-width="!w-fit" white-menu-background v-if="canWriteProject">
                                <BaseMenuItem white-menu-background @click="openEditProjectModal" title="Edit basic data" :icon="IconEdit" />

                                <template v-if="project?.is_group">
                                    <BaseMenuItem
                                        white-menu-background
                                        @click="showAddProjectToGroup = true"
                                        icon="IconCirclePlus"
                                        title="Add projects to group"
                                    />
                                    <BaseMenuItem
                                        white-menu-background
                                        @click="openCreateNewProjectInGroupModal"
                                        :icon="IconCirclePlus"
                                        title="Create project in this group"
                                    />
                                </template>

                                <BaseMenuItem white-menu-background @click="duplicateProject(project)" :icon="IconCopy" title="Duplicate" />

                                <BaseMenuItem
                                    white-menu-background
                                    v-if="canDeleteProject"
                                    @click="openDeleteProjectModal(project)"
                                    icon="IconTrash"
                                    title="Put in the trash"
                                />
                            </BaseMenu>
                        </div>
                    </div>

                    <!-- Compact project list (Group) -->
                    <div v-if="projectsOfGroup.length > 0" class="mt-1 mb-3">
                        <div class="flex items-center gap-2">
                            <div class="text-[11px] font-semibold text-zinc-500 shrink-0">
                                {{ $t('Projects in this group') }}
                            </div>

                            <div class="flex-1 overflow-x-auto no-scrollbar">
                                <div class="flex gap-2 min-w-max">
                                    <a
                                        v-for="gp in projectsOfGroup"
                                        :key="gp.id"
                                        :href="route('projects.tab', { project: gp.id, projectTab: first_project_tab_id })"
                                        class="group inline-flex items-center gap-2 rounded-full border border-zinc-200/80 bg-white/60 px-2 py-1 text-[12px] text-zinc-700 hover:bg-white hover:border-zinc-300 transition"
                                    >
                                        <img
                                            class="size-5 rounded-full object-cover ring-1 ring-white"
                                            :src="gp?.key_visual_path ? `/storage/keyVisual/${gp.key_visual_path}` : fallbackLogoSrc"
                                            alt=""
                                            loading="lazy"
                                            decoding="async"
                                        />
                                        <span class="max-w-[15rem] truncate">{{ gp.name }}</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Middle: Tabs (immer sichtbar) -->
                    <div class="flex items-center">
                        <BaseTabs :tabs="tabsForBaseTabComponent" :use-translation="false" />
                    </div>

                    <!-- Project Switcher Popover -->
                    <transition name="pop">
                        <div
                            v-if="showProjectSwitcher"
                            class="relative"
                            @keydown.esc.prevent="closeProjectSwitcher"
                        >
                            <!-- overlay (click outside) -->
                            <div class="fixed inset-0 z-60" @click="closeProjectSwitcher"></div>

                            <div
                                class="absolute z-70 mt-2 w-[min(720px,calc(100vw-2rem))] rounded-2xl border border-zinc-200/70 bg-white/95 backdrop-blur shadow-2xl ring-1 ring-black/5"
                            >
                                <div class="p-3 sm:p-4">
                                    <div class="flex items-center gap-2">
                                        <PropertyIcon name="IconSearch" class="size-4 text-zinc-400" aria-hidden="true" />
                                        <input
                                            ref="switcherInput"
                                            v-model="switcherQuery"
                                            type="text"
                                            class="h-10 w-full rounded-xl border border-zinc-200/70 bg-white px-3 text-sm text-zinc-900 shadow-sm outline-none
                                                   focus:border-zinc-300 focus:shadow transition"
                                            :placeholder="$t('Search project')"
                                            autocomplete="off"
                                        />
                                    </div>

                                    <div class="mt-3 grid gap-2 max-h-[420px] overflow-auto no-scrollbar pr-1 pb-2 scroll-pb-6">
                                        <button
                                            v-for="p in filteredSwitcherProjects"
                                            :key="p.id"
                                            type="button"
                                            class="group flex w-full items-center gap-3 rounded-2xl border border-transparent px-2 py-2 hover:border-zinc-200 hover:bg-zinc-50 transition text-left"
                                            @click="goToProject(p.id)"
                                        >
                                            <img
                                                :src="p?.key_visual_path ? `/storage/keyVisual/${p.key_visual_path}` : fallbackLogoSrc"
                                                class="size-10 rounded-xl object-cover ring-1 ring-white shadow-sm shrink-0"
                                                alt=""
                                                loading="lazy"
                                                decoding="async"
                                            />
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <div class="truncate font-semibold text-sm text-zinc-900">
                                                        {{ p.name }}
                                                    </div>
                                                    <!-- ✅ Type Badge -->
                                                    <span class="inline-flex items-center gap-1 rounded-full border border-zinc-200 bg-zinc-50 px-2 py-0.5 text-[11px] font-semibold text-zinc-600">
                                                        <img
                                                            v-if="p.is_group"
                                                            alt=""
                                                            src="/Svgs/IconSvgs/icon_group_black.svg"
                                                            class="h-3 w-3 opacity-70"
                                                        />
                                                        <span v-else class="h-2.5 w-2.5 rounded-[6px] bg-zinc-300"></span>
                                                        <span>{{ p.is_group ? $t('Group') : $t('Project') }}</span>
                                                      </span>

                                                    <span
                                                        v-if="p._state"
                                                        class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold"
                                                        :style="p._stateStyle"
                                                    >
                                                        {{ p._state.name }}
                                                    </span>
                                                </div>
                                                <div class="truncate text-[12px] text-zinc-500">
                                                    <span v-if="p.first_and_last_event_date">{{ p.first_and_last_event_date.first_event_date }} - {{ p.first_and_last_event_date.last_event_date }}</span>
                                                    <span v-else class="text-zinc-400">{{ $t('No appointments yet') }}</span>
                                                </div>
                                            </div>

                                            <PropertyIcon name="IconChevronRight" class="size-4 text-zinc-300 group-hover:text-zinc-500 transition" />
                                        </button>

                                        <div v-if="filteredSwitcherProjects.length === 0" class="py-10 text-center text-sm text-zinc-500">
                                            {{ $t('No projects found') }}
                                        </div>
                                    </div>

                                    <div class="mt-3 flex items-center justify-between text-[11px] text-zinc-500">
                                        <span>{{ $t('Tip') }}: Esc {{ $t('Close') }}</span>
                                        <span class="tabular-nums">{{ filteredSwitcherProjects.length }} / {{ switcherProjects.length }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </transition>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="mt-4">
            <slot />
        </div>

        <!-- ===== MODALS (wie gehabt) ===== -->
        <project-create-modal
            v-if="editingProject"
            :show="editingProject"
            :categories="headerObject.categories"
            :genres="headerObject.genres"
            :sectors="headerObject.sectors"
            :project-groups="headerObject.projectGroups"
            :states="headerObject.states"
            @close-create-project-modal="closeEditProjectModal"
            @open-project-state-change-modal="openProjectStateChangeModal"
            @open-project-planning-state-change-modal="openProjectPlanningStateChangeModal"
            :create-settings="createSettings"
            :project="projectForCreateModal"
            :selected-group="selectedGroup"
        />

        <project-history-component @closed="closeProjectHistoryModal" v-if="showProjectHistory" :project_id="project.id" />

        <ArtworkBaseModal
            @close="closeDeleteProjectModal"
            v-if="deletingProject"
            :title="$t('Delete project')"
            :description="$t('Are you sure you want to delete the project?', [projectToDelete?.name])"
        >
            <div class="mt-6 flex justify-between">
                <BaseUIButton label="Delete" use-translation @click="deleteProject" is-delete-button />
                <BaseUIButton label="No, not really" use-translation @click="closeDeleteProjectModal" icon="IconCancel" />
            </div>
        </ArtworkBaseModal>

        <ProjectGroupAddProject
            :project="project"
            v-if="showAddProjectToGroup"
            @close="showAddProjectToGroup = false"
            :projects-in-group="projectsOfGroup"
        />

        <PrintLayoutSelectorModal
            :print-layouts="printLayouts"
            :project="project"
            v-if="showPrintLayoutSelectorModal"
            @close="showPrintLayoutSelectorModal = false"
        />

        <ProjectStateChangeModal :project-id="project.id" v-if="showProjectStateChangeModal" @close="closeProjectStateChangeModal" />
        <ProjectPlanningStateChangeModal :project-id="project.id" v-if="showProjectPlanningStateChangeModal" @close="closePlanningStateChangeModal" />
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, shallowRef } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { router, usePage } from "@inertiajs/vue3";
import ProjectHistoryComponent from "@/Layouts/Components/ProjectHistoryComponent.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import ProjectCreateModal from "@/Layouts/Components/ProjectCreateModal.vue";
import ProjectGroupAddProject from "@/Pages/Projects/Components/Modals/ProjectGroupAddProject.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import PrintLayoutSelectorModal from "@/Pages/Projects/Components/PrintLayoutSelectorModal.vue";
import ProjectStateChangeModal from "@/Layouts/Components/ProjectStateChangeModal.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import { can, is } from "laravel-permission-to-vuejs";
import { useColorHelper } from "@/Composeables/UseColorHelper.js";
import ProjectPlanningStateChangeModal from "@/Layouts/Components/ProjectPlanningStateChangeModal.vue";
import { IconCirclePlus, IconCopy, IconEdit } from "@tabler/icons-vue";
import BaseTabs from "@/Artwork/Tabs/BaseTabs.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

const props = defineProps({
    headerObject: { type: Object, required: true },
    project: { type: Object, required: true },
    currentTab: { type: Object, required: true },
    createSettings: { type: Object, required: false },
    first_project_tab_id: { type: Number, required: false },
    printLayouts: { type: Object, required: false },
});

const page = usePage();

const stickyHeaderEl = ref(null);
const headerHeight = ref(0);
let headerResizeObserver = null;

const updateHeaderHeight = () => {
    try {
        const el = stickyHeaderEl.value;
        if (!el) return;
        headerHeight.value = el.getBoundingClientRect().height || 0;
    } catch {
        // ignore
    }
};

const showProjectHistory = ref(false);
const editingProject = ref(false);
const deletingProject = ref(false);
const projectToDelete = ref(null);

const showAddProjectToGroup = ref(false);
const showPrintLayoutSelectorModal = ref(false);
const showProjectStateChangeModal = ref(false);
const showProjectPlanningStateChangeModal = ref(false);

const projectForCreateModal = shallowRef(props.project);
const selectedGroup = ref(null);

const showCopyUrl = ref(false);
let copyToastTimer = null;

const { backgroundColorWithOpacity, getTextColorBasedOnBackground } = useColorHelper();

// ---- sticky navigator: project switcher
const showProjectSwitcher = ref(false);
const switcherQuery = ref("");
const switcherInput = ref(null);

const fallbackLogoSrc = computed(() => page?.props?.big_logo || "/storage/logo/artwork_logo_small.svg");

const keyVisualSrc = computed(() => {
    const p = props.project;
    return p?.key_visual_path ? `/storage/keyVisual/${p.key_visual_path}` : "/storage/logo/artwork_logo_small.svg";
});

const onKeyVisualError = (e) => {
    if (e?.target) e.target.src = fallbackLogoSrc.value;
};

const projectState = computed(() => {
    return props.headerObject?.states?.find((s) => s.id === props.project?.state) || null;
});

const statePillStyle = computed(() => {
    if (!projectState.value?.color) return {};
    const c = projectState.value.color;
    return {
        backgroundColor: backgroundColorWithOpacity(c, 10),
        color: getTextColorBasedOnBackground(c),
        borderColor: getTextColorBasedOnBackground(c),
    };
});

const roomsWithAudienceList = computed(() => {
    const v = props.headerObject?.roomsWithAudience || [];
    return Array.isArray(v) ? v : Object.values(v);
});

const hasProjectPeriod = computed(() => {
    return !!(props.headerObject?.firstEventInProject && props.headerObject?.lastEventInProject);
});

const stickySubline = computed(() => {
    if (roomsWithAudienceList.value.length > 0) return roomsWithAudienceList.value.join(", ");
    if (hasProjectPeriod.value) {
        const s = props.headerObject?.firstEventInProject?.start_time || "";
        const e = props.headerObject?.lastEventInProject?.end_time || "";
        return [s, e].filter(Boolean).join(" – ");
    }
    return "";
});

const projectsOfGroup = computed(() => props.headerObject?.projectsOfGroup || []);
const belongsToGroups = computed(() => props.project?.groups || []);

const canWriteProject = computed(() => {
    const userId = page?.props?.auth?.user?.id;
    const mgr = props.headerObject?.projectManagerIds?.includes(userId);
    const wrt = props.headerObject?.projectWriteIds?.includes(userId);
    return can("write projects") || is("artwork admin") || mgr || wrt;
});

const canDeleteProject = computed(() => {
    const userId = page?.props?.auth?.user?.id;
    return is("artwork admin") || props.headerObject?.projectDeleteIds?.includes(userId);
});

// Tabs
const tabsForBaseTabComponent = computed(() => {
    const tabs = props.headerObject?.tabs || [];
    const currentId = props.headerObject?.currentTabId;
    return tabs.map((tab) => ({
        id: tab.id,
        name: tab.name,
        href: route("projects.tab", { project: props.project.id, projectTab: tab.id }),
        current: tab.id === currentId,
        permission: true,
    }));
});

// ---------- Switcher data (performant) ----------
const switcherProjects = computed(() => {
    // Idee: nimm “projectsOfGroup” + “belongsToGroups” + aktuelles Projekt,
    // dedupe nach ID, reiche minimalen View-Datensatz an.
    const states = props.headerObject?.states || [];
    const stateById = new Map(states.map((s) => [s.id, s]));

    const list = [];
    const push = (p) => {
        if (!p?.id) return;
        const st = stateById.get(p.state) || null;
        list.push({
            id: p.id,
            name: p.name,
            key_visual_path: p.key_visual_path,
            _state: st,
            _stateStyle: st?.color
                ? {
                    backgroundColor: backgroundColorWithOpacity(st.color, 10),
                    color: getTextColorBasedOnBackground(st.color),
                    borderColor: getTextColorBasedOnBackground(st.color),
                }
                : {},
            is_group: !!p.is_group,
            _sub: "",
            first_and_last_event_date: p.first_and_last_event_date
        });
    };

    push(props.project);
    projectsOfGroup.value.forEach(push);
    belongsToGroups.value.forEach(push);

    // dedupe
    const seen = new Set();
    return list.filter((p) => (seen.has(p.id) ? false : (seen.add(p.id), true)));
});

const filteredSwitcherProjects = computed(() => {
    const q = switcherQuery.value.trim().toLowerCase();
    const base = switcherProjects.value;

    if (!q) return base.slice(0, 60);

    // schnelle Filterung + harte Begrenzung
    const out = [];
    for (let i = 0; i < base.length; i++) {
        const p = base[i];
        if (p.name?.toLowerCase().includes(q)) out.push(p);
        if (out.length >= 60) break;
    }
    return out;
});

// ---------- Actions / modals ----------
function toggleProjectSwitcher() {
    showProjectSwitcher.value = !showProjectSwitcher.value;
    if (showProjectSwitcher.value) {
        nextTick(() => switcherInput.value?.focus?.());
    }
}

function closeProjectSwitcher() {
    showProjectSwitcher.value = false;
    switcherQuery.value = "";
}

function goToProject(projectId) {
    closeProjectSwitcher();
    router.visit(route("projects.tab", { project: projectId, projectTab: props.headerObject?.currentTabId || props.first_project_tab_id }));
}

function openCreateNewProjectInGroupModal() {
    projectForCreateModal.value = null;
    selectedGroup.value = props.project;
    editingProject.value = true;
}

function openProjectHistoryModal() {
    showProjectHistory.value = true;
}

function closeProjectHistoryModal() {
    showProjectHistory.value = false;
}

function openEditProjectModal() {
    projectForCreateModal.value = props.project;
    selectedGroup.value = null;
    editingProject.value = true;
}

function closeEditProjectModal() {
    editingProject.value = false;
}

function duplicateProject(project) {
    router.post(route("projects.duplicate", project.id));
}

function openDeleteProjectModal(project) {
    projectToDelete.value = project;
    deletingProject.value = true;
}

function closeDeleteProjectModal() {
    deletingProject.value = false;
    projectToDelete.value = null;
}

function deleteProject() {
    router.delete(route("projects.destroy", projectToDelete.value.id));
    closeDeleteProjectModal();
}

function openProjectStateChangeModal() {
    showProjectStateChangeModal.value = true;
}

const openProjectPlanningStateChangeModal = () => {
    showProjectPlanningStateChangeModal.value = true;
};

const closePlanningStateChangeModal = () => {
    showProjectPlanningStateChangeModal.value = false;
    nextTick(() => router.reload());
};

const closeProjectStateChangeModal = () => {
    showProjectStateChangeModal.value = false;
    nextTick(() => router.reload());
};

const copyProjectUrlToClipboard = async () => {
    try {
        await navigator.clipboard.writeText(window.location.href);
        showCopyUrl.value = true;

        if (copyToastTimer) clearTimeout(copyToastTimer);
        copyToastTimer = setTimeout(() => {
            showCopyUrl.value = false;
            copyToastTimer = null;
        }, 2600);
    } catch (err) {
        console.error("Could not copy text:", err);
    }
};

onBeforeUnmount(() => {
    if (copyToastTimer) clearTimeout(copyToastTimer);

    if (headerResizeObserver) {
        try { headerResizeObserver.disconnect(); } catch { /* ignore */ }
        headerResizeObserver = null;
    }
});

onMounted(() => {
    updateHeaderHeight();

    if (typeof ResizeObserver !== 'undefined') {
        headerResizeObserver = new ResizeObserver(() => updateHeaderHeight());
        if (stickyHeaderEl.value) headerResizeObserver.observe(stickyHeaderEl.value);
    }

    window.addEventListener('resize', updateHeaderHeight, { passive: true });
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', updateHeaderHeight);
});
</script>

<style scoped>
.tabular-nums { font-variant-numeric: tabular-nums; }

.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

.fade-enter-active, .fade-leave-active { transition: opacity .18s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* Popover Animation (modern, but subtle) */
.pop-enter-active { transition: opacity .14s ease, transform .14s ease; }
.pop-leave-active { transition: opacity .10s ease, transform .10s ease; }
.pop-enter-from, .pop-leave-to { opacity: 0; transform: translateY(-6px) scale(.99); }

/* Focus */
:deep(button:focus-visible),
:deep(a:focus-visible),
:deep(input:focus-visible) {
    outline: 2px solid rgb(59 130 246 / 0.6);
    outline-offset: 2px;
    border-radius: 0.75rem;
}
</style>
