<template>
    <ArtworkBaseModal @close="handleClose" v-if="cell && cell.id" :title="$t('Cell Details')" description="">
        <div class="mx-4">
            <!-- Error Indicator -->
            <div v-if="loadError" class="flex items-center mb-4 text-red-600 bg-red-50 p-3 rounded-md">
                <IconAlertCircle stroke-width="1.5" class="mr-2 h-5 w-5" />
                <div>
                    <p class="text-sm font-medium">{{ $t('Error loading cell data') }}</p>
                    <p class="text-xs mt-1">{{ loadError }}</p>
                    <button @click="handleClose" class="text-xs underline mt-2">{{ $t('Close and try again') }}</button>
                </div>
            </div>

            <!-- Lock Indicator -->
            <div v-if="cell?.column?.is_locked" class="flex items-center mb-4 text-yellow-600 bg-yellow-50 p-3 rounded-md">
                <IconLock stroke-width="1.5" class="mr-2 h-5 w-5" />
                <span class="text-sm font-medium">{{ $t('This cell is locked') }}</span>
            </div>

            <!-- Tabs Navigation -->
            <div class="mb-6">
                <nav class="flex space-x-1 bg-gray-100 rounded-lg p-1" aria-label="Tabs">
                    <button
                        v-for="tab in availableTabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            activeTab === tab.id
                                ? 'bg-white text-artwork-buttons-create shadow-sm'
                                : 'text-gray-600 hover:text-gray-900',
                            'flex-1 py-2.5 px-3 text-sm font-medium rounded-md transition-all duration-200'
                        ]"
                    >
                        <div class="flex items-center justify-center">
                            <component :is="tab.icon" class="w-5 h-5 mr-2" stroke-width="1.5" />
                            {{ tab.name }}
                        </div>
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="min-h-[400px]">
                <!-- Calculation Tab -->
                <div v-show="activeTab === 'calculation'" class="space-y-4">
                    <!-- Calculations List -->
                    <div v-if="calculations.length > 0" class="space-y-3">
                        <TransitionGroup name="list">
                            <div
                                v-for="(calc, index) in calculations"
                                :key="calc.tempId || calc.id"
                                class="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:border-artwork-buttons-create transition-all duration-200"
                            >
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1">
                                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                            {{ $t('Name') }}
                                        </label>
                                        <input
                                            v-model="calc.name"
                                            type="text"
                                            :disabled="cell?.column?.is_locked"
                                            :placeholder="$t('e.g. Personnel costs')"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-artwork-buttons-create focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed text-sm"
                                        />
                                    </div>
                                    <button
                                        v-if="!cell?.column?.is_locked"
                                        @click="removeCalculation(index)"
                                        class="ml-3 mt-6 p-2 text-red-500 hover:bg-red-50 rounded-md transition-colors"
                                        :title="$t('Delete')"
                                    >
                                        <IconTrash class="w-5 h-5" stroke-width="1.5" />
                                    </button>
                                </div>

                                <div class="grid grid-cols-2 gap-3 mb-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                            {{ $t('Value') }}
                                        </label>
                                        <input
                                            v-model.number="calc.value"
                                            type="number"
                                            step="0.01"
                                            :disabled="cell?.column?.is_locked"
                                            placeholder="0.00"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-artwork-buttons-create focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed text-sm"
                                        />
                                    </div>
                                    <div class="flex items-end">
                                        <div class="w-full px-3 py-2 bg-gray-100 rounded-md text-sm font-medium text-gray-700">
                                            {{ formatCurrency(calc.value || 0) }}
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                        {{ $t('Comment') }}
                                    </label>
                                    <textarea
                                        v-model="calc.description"
                                        rows="2"
                                        :disabled="cell?.column?.is_locked"
                                        :placeholder="$t('Additional notes...')"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-artwork-buttons-create focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed text-sm resize-none"
                                    />
                                </div>
                            </div>
                        </TransitionGroup>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-12">
                        <IconCalculator class="w-16 h-16 mx-auto text-gray-300 mb-4" stroke-width="1" />
                        <p class="text-gray-500 text-sm">{{ $t('No calculations yet') }}</p>
                        <p class="text-gray-400 text-xs mt-1">{{ $t('Add your first calculation item') }}</p>
                    </div>

                    <!-- Add Calculation Button -->
                    <button
                        v-if="!cell?.column?.is_locked"
                        @click="addCalculation"
                        class="w-full py-3 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-artwork-buttons-create hover:text-artwork-buttons-create hover:bg-blue-50 transition-all duration-200 flex items-center justify-center font-medium text-sm"
                    >
                        <IconPlus class="w-5 h-5 mr-2" stroke-width="2" />
                        {{ $t('Add calculation item') }}
                    </button>

                    <!-- Summary -->
                    <div class="mt-6 bg-gradient-to-r from-artwork-buttons-create to-blue-600 rounded-lg p-4 text-white">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-lg">{{ $t('Total') }}</span>
                            <span class="text-2xl font-bold">{{ formatCurrency(totalCalculated) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Comment Tab -->
                <div v-show="activeTab === 'comment'" class="space-y-4">
                    <!-- New Comment Input -->
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            {{ $t('Add new comment') }}
                        </label>
                        <textarea
                            v-model="newComment"
                            rows="4"
                            :placeholder="$t('What do I need to know about this item?')"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-artwork-buttons-create focus:border-transparent text-sm resize-none"
                        />
                        <div class="mt-3 flex justify-end">
                            <button
                                @click="saveCommentOnly"
                                :disabled="!newComment || newComment.trim() === ''"
                                class="px-4 py-2 bg-artwork-buttons-create text-white rounded-md hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors text-sm font-medium flex items-center"
                            >
                                <IconDeviceFloppy class="w-4 h-4 mr-2" stroke-width="1.5" />
                                {{ $t('Save comment') }}
                            </button>
                        </div>
                    </div>

                    <!-- Existing Comments -->
                    <div class="space-y-3">
                        <h3 class="text-sm font-semibold text-gray-700">{{ $t('Previous comments') }}</h3>
                        <div v-if="cell?.comments?.length > 0" class="space-y-3">
                            <TransitionGroup name="list">
                                <div
                                    v-for="comment in cell.comments"
                                    :key="comment.id"
                                    @mouseenter="hoveredCommentId = comment.id"
                                    @mouseleave="hoveredCommentId = null"
                                    class="bg-white rounded-lg p-4 border border-gray-200 hover:border-gray-300 transition-all duration-200"
                                >
                                    <div class="flex items-start justify-between mb-2">
                                        <div class="flex items-center space-x-2">
                                            <NewUserToolTip
                                                :id="comment.id"
                                                :user="comment.user"
                                                :height="8"
                                                :width="8"
                                            />
                                            <div class="text-xs text-gray-500">
                                                {{ formatDate(comment.created_at) }}
                                            </div>
                                        </div>
                                        <button
                                            v-if="hoveredCommentId === comment.id && comment.user_id === $page.props.auth.user.id"
                                            @click="deleteComment(comment)"
                                            class="p-1 text-red-500 hover:bg-red-50 rounded transition-colors"
                                            :title="$t('Delete')"
                                        >
                                            <IconTrash class="w-4 h-4" stroke-width="1.5" />
                                        </button>
                                    </div>
                                    <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ comment.description }}</p>
                                </div>
                            </TransitionGroup>
                        </div>
                        <div v-else class="text-center py-8">
                            <IconMessage class="w-12 h-12 mx-auto text-gray-300 mb-3" stroke-width="1" />
                            <p class="text-gray-500 text-sm">{{ $t('No comments yet') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Linking Tab -->
                <div v-show="activeTab === 'linking'" class="space-y-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <p class="text-sm text-blue-800">
                            {{ $t('Keep track of your funding sources. You can add the value to the source.') }}
                        </p>
                    </div>

                    <!-- Link Toggle -->
                    <div class="flex items-center bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <input
                            v-model="isLinked"
                            type="checkbox"
                            :disabled="cell?.column?.is_locked"
                            class="h-5 w-5 text-artwork-buttons-create border-gray-300 rounded focus:ring-artwork-buttons-create disabled:cursor-not-allowed"
                        />
                        <label
                            :class="[isLinked ? 'text-gray-900 font-medium' : 'text-gray-600']"
                            class="ml-3 text-sm cursor-pointer"
                            @click="!cell?.column?.is_locked && (isLinked = !isLinked)"
                        >
                            {{ $t('Link to funding source') }}
                        </label>
                    </div>

                    <!-- Link Configuration -->
                    <transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 -translate-y-2"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 -translate-y-2"
                    >
                        <div v-if="isLinked" class="space-y-4">
                            <!-- Type Selection -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    {{ $t('Type') }}
                                </label>
                                <Listbox v-model="linkedType" :disabled="cell?.column?.is_locked">
                                    <div class="relative">
                                        <ListboxButton
                                            class="relative w-full cursor-pointer rounded-md bg-white py-2.5 pl-3 pr-10 text-left border border-gray-300 focus:outline-none focus:ring-2 focus:ring-artwork-buttons-create disabled:bg-gray-100 disabled:cursor-not-allowed"
                                        >
                                            <span class="block truncate text-sm">{{ linkedType.name }}</span>
                                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                                <IconChevronDown class="h-5 w-5 text-gray-400" stroke-width="1.5" />
                                            </span>
                                        </ListboxButton>
                                        <transition
                                            leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100"
                                            leave-to-class="opacity-0"
                                        >
                                            <ListboxOptions
                                                class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-sm shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                            >
                                                <ListboxOption
                                                    v-for="type in linkTypes"
                                                    :key="type.name"
                                                    :value="type"
                                                    v-slot="{ active, selected }"
                                                    class="cursor-pointer"
                                                >
                                                    <div
                                                        :class="[
                                                            active ? 'bg-artwork-buttons-create text-white' : 'text-gray-900',
                                                            'relative cursor-pointer select-none py-2 pl-3 pr-9'
                                                        ]"
                                                    >
                                                        <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">
                                                            {{ type.name }}
                                                        </span>
                                                        <span
                                                            v-if="selected"
                                                            class="absolute inset-y-0 right-0 flex items-center pr-3"
                                                        >
                                                            <IconCheck class="h-5 w-5 text-success" stroke-width="2" />
                                                        </span>
                                                    </div>
                                                </ListboxOption>
                                            </ListboxOptions>
                                        </transition>
                                    </div>
                                </Listbox>
                            </div>

                            <!-- Money Source Search -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    {{ $t('Funding source') }}
                                </label>
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-3">
                                    <p class="text-xs text-yellow-800">
                                        {{ $t('Important: You will only find funding sources here that have specified the current project as a "Funded Project" in the sidebar.') }}
                                    </p>
                                </div>
                                <div class="relative">
                                    <input
                                        v-model="moneySourceQuery"
                                        type="text"
                                        :disabled="cell?.column?.is_locked"
                                        :placeholder="$t('Search funding source...')"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-artwork-buttons-create focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed text-sm"
                                    />

                                    <!-- Search Results -->
                                    <transition
                                        leave-active-class="transition ease-in duration-100"
                                        leave-from-class="opacity-100"
                                        leave-to-class="opacity-0"
                                    >
                                        <div
                                            v-if="moneySourceSearchResults.length > 0 && moneySourceQuery.length > 0"
                                            class="absolute z-10 mt-1 w-full max-h-60 overflow-auto rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5"
                                        >
                                            <div
                                                v-for="(source, index) in moneySourceSearchResults"
                                                :key="index"
                                                @click="selectMoneySource(source)"
                                                class="cursor-pointer px-4 py-3 hover:bg-artwork-buttons-create hover:text-white transition-colors border-l-4 border-transparent hover:border-success"
                                            >
                                                <p class="font-medium text-sm">{{ source.name }}</p>
                                            </div>
                                        </div>
                                    </transition>
                                </div>
                            </div>

                            <!-- Selected Source Display -->
                            <div v-if="selectedMoneySource" class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div class="flex items-center text-sm">
                                    <IconLink class="w-5 h-5 text-green-600 mr-2" stroke-width="1.5" />
                                    <span class="text-gray-700">{{ $t('Linked with') }}:</span>
                                    <span class="font-semibold text-gray-900 mx-2">{{ selectedMoneySource.name }}</span>
                                    <span class="text-gray-700">{{ $t('as') }}</span>
                                    <span class="font-semibold text-gray-900 ml-2">
                                        {{ linkedType.type === 'EARNING' ? $t('Revenue') : $t('Expenses') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </transition>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="mt-6 pt-4 border-t border-gray-200 flex justify-end space-x-3">
                <BaseUIButton is-cancel-button
                    @click="handleClose"
                >
                    {{ $t('Cancel') }}
                </BaseUIButton>
                <BaseUIButton is-add-button icon="IconDeviceFloppy"
                    @click="saveAndClose"
                    :disabled="cell?.column?.is_locked"
                >
                    {{ $t('Save & Close') }}
                </BaseUIButton>
            </div>
        </div>
        <ConfirmDeleteModal
            v-if="showConfirmCalculationModal"
            :title="$t('Save calculation')"
            :description="$t('Would you like to save your calculation? The previous figure in the budget table will be overwritten with the new figure irrevocably.')"
            :button="$t('Save')"
            :is_budget="true"
            @closed="closeConfirmCalculationModal"
            @delete="performSaveCalculations"
        />
    </ArtworkBaseModal>
</template>

<script>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import {
    IconLock,
    IconCalculator,
    IconMessage,
    IconLink,
    IconPlus,
    IconTrash,
    IconChevronDown,
    IconCheck,
    IconDeviceFloppy,
    IconAlertCircle
} from '@tabler/icons-vue';
import { Listbox, ListboxButton, ListboxOption, ListboxOptions } from '@headlessui/vue';
import NewUserToolTip from '@/Layouts/Components/NewUserToolTip.vue';
import { router } from '@inertiajs/vue3';
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";

export default {
    name: 'CellDetailModal',
    components: {
        BaseUIButton,
        ArtworkBaseModal,
        IconLock,
        IconCalculator,
        IconMessage,
        IconLink,
        IconPlus,
        IconTrash,
        IconChevronDown,
        IconCheck,
        IconDeviceFloppy,
        IconAlertCircle,
        Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        NewUserToolTip,
        ConfirmDeleteModal
    },
    props: {
        cell: {
            type: Object,
            required: true
        },
        projectId: {
            type: Number,
            required: true
        },
        initialTab: {
            type: String,
            default: null
        },
        budgetType: {
            type: String,
            default: null
        }
    },
    data() {
        // Default linkedType basierend auf budgetType setzen
        const defaultLinkedType = this.budgetType === 'BUDGET_TYPE_COST'
            ? { name: this.$t('Expenses'), type: 'COST' }
            : { name: this.$t('Revenue'), type: 'EARNING' };

        return {
            activeTab: 'calculation',
            calculations: [],
            newComment: '',
            isLinked: false,
            linkedType: defaultLinkedType,
            selectedMoneySource: null,
            moneySourceQuery: '',
            moneySourceSearchResults: [],
            hoveredCommentId: null,
            linkTypes: [
                { name: this.$t('Revenue'), type: 'EARNING' },
                { name: this.$t('Expenses'), type: 'COST' }
            ],
            tempIdCounter: 0,
            isLoading: true,
            loadError: null,
            showConfirmCalculationModal: false
        };
    },
    computed: {
        availableTabs() {
            const tabs = [];

            if (this.cell?.column?.type === 'empty') {
                tabs.push({
                    id: 'calculation',
                    name: this.$t('Calculation'),
                    icon: 'IconCalculator'
                });
            }

            tabs.push(
                {
                    id: 'comment',
                    name: this.$t('Comment'),
                    icon: 'IconMessage'
                },
                {
                    id: 'linking',
                    name: this.$t('Linking'),
                    icon: 'IconLink'
                }
            );

            return tabs;
        },
        totalCalculated() {
            return this.calculations.reduce((sum, calc) => sum + (parseFloat(calc.value) || 0), 0);
        }
    },
    mounted() {
        this.initializeData();

        // Set tab based on initialTab prop or default logic
        if (this.initialTab) {
            // Map type names to tab IDs
            const tabMapping = {
                'comment': 'comment',
                'calculation': 'calculation',
                'moneySource': 'linking',
                'linking': 'linking'
            };
            this.activeTab = tabMapping[this.initialTab] || this.initialTab;
        } else {
            // Set default tab
            if (this.cell?.column?.type !== 'empty') {
                this.activeTab = 'comment';
            }
        }
    },
    watch: {
        cell: {
            handler(newCell, oldCell) {

                if (newCell) {
                    this.initializeData();
                }
            },
            deep: true,
            immediate: true
        },
        moneySourceQuery(newValue) {
            if (newValue.length > 0) {
                this.searchMoneySources(newValue);
            } else {
                this.moneySourceSearchResults = [];
            }
        }
    },
    methods: {
        initializeData() {
            this.isLoading = true;

            // Validate cell object
            if (!this.cell) {
                console.error('Cell is null or undefined!');
                this.loadError = 'Cell data is missing';
                this.isLoading = false;
                return;
            }

            if (!this.cell.id) {
                console.error('Cell ID is missing!', this.cell);
                this.loadError = 'Cell ID is missing';
                this.isLoading = false;
                return;
            }

            try {
                // Reset calculations
                this.calculations = [];

                // Initialize calculations with deep clone
                if (this.cell?.calculations && Array.isArray(this.cell.calculations) && this.cell.calculations.length > 0) {
                    this.calculations = this.cell.calculations.map(calc => ({
                        id: calc.id,
                        name: calc.name || '',
                        value: parseFloat(calc.value) || 0,
                        description: calc.description || '',
                        position: calc.position || 0
                    }));
                }

                // Initialize linking
                this.isLinked = false;
                this.selectedMoneySource = null;

                // Check if cell has a linked money source
                if (this.cell?.linked_money_source_id) {
                    this.isLinked = true;

                    // Try to get from loaded relation first
                    if (this.cell?.linkedMoneySource) {
                        this.selectedMoneySource = this.cell.linkedMoneySource;
                    } else if (this.cell?.linked_money_source) {
                        // Fallback for snake_case
                        this.selectedMoneySource = this.cell.linked_money_source;
                    } else {
                        // If not loaded, we need to fetch it or get it from moneySources prop
                        this.loadMoneySource(this.cell.linked_money_source_id);
                    }
                }

                if (this.cell?.linked_type) {
                    const type = this.linkTypes.find(t => t.type === this.cell.linked_type);
                    if (type) {
                        this.linkedType = type;
                    }
                }

                // Set correct tab based on cell type
                if (this.cell?.column?.type !== 'empty') {
                    this.activeTab = 'comment';
                }

                this.loadError = null;
            } catch (error) {
                console.error('Error initializing cell data:', error);
                this.loadError = 'Failed to load cell data';
            } finally {
                this.isLoading = false;
            }
        },

        addCalculation() {
            const newCalc = {
                tempId: `temp-${this.tempIdCounter++}`,
                name: '',
                value: 0,
                description: '',
                position: this.calculations.length
            };
            this.calculations.push(newCalc);
        },

        removeCalculation(index) {
            this.calculations.splice(index, 1);
            // Update positions
            this.calculations.forEach((calc, idx) => {
                calc.position = idx;
            });
        },

        async saveCommentOnly() {
            if (!this.newComment || this.newComment.trim() === '') {
                return;
            }

            if (!this.cell || !this.cell.id) {
                console.error('Cell or cell.id is missing:', this.cell);
                alert(this.$t('Error: Cell data not loaded. Please close and reopen the modal.'));
                return;
            }

            try {
                const response = await axios.post(
                    route('project.budget.cell.comment.store', { columnCell: this.cell.id }),
                    { description: this.newComment },
                    {
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    }
                );

                if (response.data && response.data.comment) {
                    // Leere das Input-Feld
                    this.newComment = '';

                    // Emitte Event, damit BudgetComponent die Tabelle UND Modal aktualisiert
                    this.$emit('comment-saved', {
                        cellId: this.cell.id,
                        comment: response.data.comment
                    });
                }
            } catch (error) {
                console.error('Error saving comment:', error);
                alert(this.$t('Error saving comment. Please try again.'));
            }
        },

        async deleteComment(comment) {
            if (!confirm(this.$t('Are you sure you want to delete this comment?'))) {
                return;
            }

            try {
                // Verwende AJAX statt Inertia
                await axios.delete(
                    route('project.budget.cell.comment.delete', { cellComment: comment.id }),
                    {
                        headers: {
                            'Accept': 'application/json'
                        }
                    }
                );

                // Entferne Kommentar aus der Liste
                if (this.cell.comments) {
                    const index = this.cell.comments.findIndex(c => c.id === comment.id);
                    if (index !== -1) {
                        this.cell.comments.splice(index, 1);

                        // Emitte Event, damit BudgetComponent die Tabelle aktualisieren kann
                        this.$emit('comment-deleted', {
                            cellId: this.cell.id,
                            commentId: comment.id
                        });
                    }
                }
            } catch (error) {
                alert(this.$t('Error deleting comment. Please try again.'));
            }
        },

        async searchMoneySources(query) {
            try {
                const response = await axios.get('/money_sources/search/money_source', {
                    params: {
                        query: query,
                        projectId: this.projectId
                    }
                });
                this.moneySourceSearchResults = response.data.filter(
                    source => source.is_group === 0 || source.is_group === false
                );
            } catch (error) {
                console.error('Error searching money sources:', error);
            }
        },

        async loadMoneySource(moneySourceId) {
            try {
                // Try to load the money source by ID
                const response = await axios.get(`/money_sources/${moneySourceId}`);
                this.selectedMoneySource = response.data;
            } catch (error) {
                console.error('Error loading money source:', error);
                // Fallback: try to find it in search results or load all money sources
                this.selectedMoneySource = null;
            }
        },

        selectMoneySource(source) {
            this.selectedMoneySource = source;
            this.moneySourceQuery = '';
            this.moneySourceSearchResults = [];
        },

        async saveAndClose() {
            // Speichere nur die Daten des aktuell aktiven Tabs
            try {
                if (this.activeTab === 'calculation') {
                    // Calculation Tab: Speichere Berechnungen
                    if (this.cell?.column?.type === 'empty') {
                        await this.saveCalculations();
                        // Modal closing is handled by performSaveCalculations() after confirmation
                    }
                } else if (this.activeTab === 'comment') {
                    // Comment Tab: Speichere neuen Kommentar, falls vorhanden
                    if (this.newComment && this.newComment.trim() !== '') {
                        await this.saveCommentOnly();
                    }
                    // Emit budget-updated to refresh the table (for comment icons)
                    this.$emit('budget-updated');
                    // Close modal nach erfolgreichem Speichern
                    this.$emit('closed', true);
                } else if (this.activeTab === 'linking') {
                    // Linking Tab: Speichere Verlinkungen
                    await this.saveLinking();
                    // Emit budget-updated to refresh the table (for money source icons)
                    this.$emit('budget-updated');
                    // Close modal nach erfolgreichem Speichern
                    this.$emit('closed', true);
                }
            } catch (error) {
                console.error('Error in saveAndClose:', error);
                // Modal bleibt offen bei Fehler
            }
        },

        async saveCalculations() {
            // Check if cell already has a value
            const cellValue = Number(this.cell?.value ?? this.cell?.sage_value ?? this.cell?.current_value);

            console.log('saveCalculations - cellValue:', cellValue, 'totalCalculated:', this.totalCalculated);

            if (cellValue !== 0 && cellValue !== this.totalCalculated) {
                console.log('Showing confirmation modal');
                this.showConfirmCalculationModal = true;
                return;
            }

            console.log('No confirmation needed, saving directly');
            await this.performSaveCalculations();
        },

        async performSaveCalculations() {
            console.log('performSaveCalculations called');
            try {
                // Füge cell_id zu allen Kalkulationen hinzu (für neue Kalkulationen)
                const calculationsWithCellId = this.calculations.map(calc => ({
                    id: calc.id,
                    cell_id: this.cell.id,
                    name: calc.name || '',
                    value: calc.value || 0,
                    description: calc.description || '',
                    position: calc.position || 0
                }));

                console.log('Saving calculations:', calculationsWithCellId);

                const response = await axios.patch(
                    route('project.budget.cell-calculation.update'),
                    {
                        cell_id: this.cell.id,  // Sende cell_id immer mit, auch bei leerem Array
                        calculations: calculationsWithCellId,
                    },
                    {
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    }
                );

                console.log('Calculations saved successfully:', response.data);

                // Emitte Event, damit BudgetComponent die Tabelle aktualisiert
                this.$emit('calculations-saved', {
                    cellId: this.cell.id,
                    calculations: calculationsWithCellId,
                    cellValue: response.data.cell_value
                });

                console.log('Closing confirmation modal and parent modal');
                // Close the confirmation modal and parent modal after successful save
                this.closeConfirmCalculationModal();
                this.$emit('closed', true);
            } catch (error) {
                console.error('Error saving calculations:', error);
                alert(this.$t('Error saving calculations. Please try again.'));
            }
        },

        async saveLinking() {
            if (!this.cell || !this.cell.id) {
                console.error('Cell or cell.id is missing:', this.cell);
                alert(this.$t('Error: Cell data not loaded. Please close and reopen the modal.'));
                return;
            }

            try {
                console.log('Saving linking:', {
                    cell_id: this.cell.id,
                    linked_type: this.isLinked ? this.linkedType.type : null,
                    money_source_id: this.isLinked ? this.selectedMoneySource?.id : null
                });

                await axios.patch(
                    route('project.budget.cell-source.update'),
                    {
                        cell_id: this.cell.id,
                        linked_type: this.isLinked ? this.linkedType.type : null,
                        money_source_id: this.isLinked ? this.selectedMoneySource?.id : null
                    },
                    {
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    }
                );

                console.log('Linking saved successfully');
            } catch (error) {
                console.error('Error saving linking:', error);
                alert(this.$t('Error saving linking. Please try again.'));
            }
        },

        handleClose() {
            this.$emit('closed', false);
        },

        closeConfirmCalculationModal() {
            this.showConfirmCalculationModal = false;
        },

        formatCurrency(value) {
            return new Intl.NumberFormat('de-DE', {
                style: 'currency',
                currency: 'EUR'
            }).format(value);
        },

        formatDate(date) {
            const dateObj = new Date(date);
            return dateObj.toLocaleString('de-DE', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }
};
</script>

<style scoped>
/* List transitions */
.list-enter-active,
.list-leave-active {
    transition: all 0.3s ease;
}

.list-enter-from {
    opacity: 0;
    transform: translateY(-10px);
}

.list-leave-to {
    opacity: 0;
    transform: translateX(10px);
}

.list-move {
    transition: transform 0.3s ease;
}
</style>

