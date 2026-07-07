<template>
    <ArtworkBaseModal @close="handleClose" v-if="selectedSumDetail && selectedSumDetail.id" :title="$t('Sum Details')" description="">
        <div class="mx-4">
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
                                @click="saveComment"
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
                        <div v-if="selectedSumDetail?.comments?.length > 0" class="space-y-3">
                            <TransitionGroup name="list">
                                <div
                                    v-for="comment in selectedSumDetail.comments"
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
                                    <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ comment.comment }}</p>
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
                            {{ $t('Keep track of your funding sources. You can either add or subtract the value from the source.') }}
                        </p>
                    </div>

                    <!-- Link Toggle -->
                    <div class="flex items-center bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <input
                            v-model="isLinked"
                            type="checkbox"
                            class="h-5 w-5 text-artwork-buttons-create border-gray-300 rounded focus:ring-artwork-buttons-create"
                        />
                        <label
                            :class="[isLinked ? 'text-gray-900 font-medium' : 'text-gray-600']"
                            class="ml-3 text-sm cursor-pointer"
                            @click="isLinked = !isLinked"
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
                                <Listbox v-model="linkedType">
                                    <div class="relative">
                                        <ListboxButton
                                            class="relative w-full cursor-pointer rounded-md bg-white py-2.5 pl-3 pr-10 text-left border border-gray-300 focus:outline-none focus:ring-2 focus:ring-artwork-buttons-create"
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
                                <div class="relative">
                                    <input
                                        v-model="moneySourceQuery"
                                        type="text"
                                        :placeholder="$t('Search funding source...')"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-artwork-buttons-create focus:border-transparent text-sm"
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

                            <!-- Save Button for Linking -->
                            <div class="flex justify-end">
                                <button
                                    @click="saveMoneySourceLink"
                                    :disabled="!selectedMoneySource"
                                    class="px-4 py-2 bg-artwork-buttons-create text-white rounded-md hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors text-sm font-medium flex items-center"
                                >
                                    <IconDeviceFloppy class="w-4 h-4 mr-2" stroke-width="1.5" />
                                    {{ $t('Save') }}
                                </button>
                            </div>
                        </div>
                    </transition>

                    <!-- Remove Link Option (when already linked) -->
                    <div v-if="!isLinked && selectedSumDetail?.sum_money_source" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p class="text-sm text-yellow-800 mb-3">
                            {{ $t('This sum was linked to a funding source. The link will be removed when you save.') }}
                        </p>
                        <button
                            @click="removeMoneySourceLink"
                            class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors text-sm font-medium"
                        >
                            {{ $t('Remove link') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="mt-6 pt-4 border-t border-gray-200 flex justify-end space-x-3">
                <BaseUIButton is-cancel-button @click="handleClose">
                    {{ $t('Close') }}
                </BaseUIButton>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import {
    IconMessage,
    IconLink,
    IconTrash,
    IconChevronDown,
    IconCheck,
    IconDeviceFloppy
} from '@tabler/icons-vue';
import { Listbox, ListboxButton, ListboxOption, ListboxOptions } from '@headlessui/vue';
import NewUserToolTip from '@/Layouts/Components/NewUserToolTip.vue';
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import Permissions from "@/Mixins/Permissions.vue";

export default {
    name: 'SumDetailComponent',
    mixins: [Permissions],
    components: {
        BaseUIButton,
        ArtworkBaseModal,
        IconMessage,
        IconLink,
        IconTrash,
        IconChevronDown,
        IconCheck,
        IconDeviceFloppy,
        Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        NewUserToolTip
    },
    props: {
        selectedSumDetail: {
            type: Object,
            required: true
        },
        projectId: {
            type: Number,
            required: true
        },
        openTab: {
            type: String,
            default: 'comment'
        },
        budgetType: {
            type: String,
            default: null
        }
    },
    emits: ['closed', 'comment-saved', 'comment-deleted', 'money-source-updated'],
    data() {
        // Default linkedType basierend auf budgetType setzen
        let defaultLinkedType;
        if (this.selectedSumDetail?.sum_money_source?.linked_type) {
            defaultLinkedType = this.selectedSumDetail.sum_money_source.linked_type === 'EARNING'
                ? { name: this.$t('Revenue'), type: 'EARNING' }
                : { name: this.$t('Expenses'), type: 'COST' };
        } else {
            defaultLinkedType = this.budgetType === 'BUDGET_TYPE_COST'
                ? { name: this.$t('Expenses'), type: 'COST' }
                : { name: this.$t('Revenue'), type: 'EARNING' };
        }

        return {
            activeTab: 'comment',
            newComment: '',
            isLinked: this.selectedSumDetail?.sum_money_source !== null,
            linkedType: defaultLinkedType,
            selectedMoneySource: this.selectedSumDetail?.sum_money_source?.money_source ?? null,
            moneySourceQuery: '',
            moneySourceSearchResults: [],
            hoveredCommentId: null,
            linkTypes: [
                { name: this.$t('Revenue'), type: 'EARNING' },
                { name: this.$t('Expenses'), type: 'COST' }
            ]
        };
    },
    computed: {
        availableTabs() {
            return [
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
            ];
        }
    },
    mounted() {
        // Set tab based on openTab prop
        if (this.openTab) {
            const tabMapping = {
                'comment': 'comment',
                'moneySource': 'linking',
                'linking': 'linking'
            };
            this.activeTab = tabMapping[this.openTab] || 'comment';
        }
    },
    watch: {
        moneySourceQuery: {
            handler(newQuery) {
                if (newQuery.length > 0) {
                    this.searchMoneySources(newQuery);
                } else {
                    this.moneySourceSearchResults = [];
                }
            }
        }
    },
    methods: {
        handleClose() {
            this.$emit('closed', true);
        },

        formatDate(date) {
            const dateFormat = new Date(date);
            return dateFormat.toLocaleString('de-de', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        async saveComment() {
            if (!this.newComment || this.newComment.trim() === '') {
                return;
            }

            try {
                const response = await axios.post(
                    route('sum.comments.store'),
                    {
                        comment: this.newComment,
                        commentable_id: this.selectedSumDetail.id,
                        commentable_type: this.selectedSumDetail.class
                    },
                    {
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    }
                );

                if (response.data && response.data.comment) {
                    // Add comment to local list
                    if (!this.selectedSumDetail.comments) {
                        this.selectedSumDetail.comments = [];
                    }
                    this.selectedSumDetail.comments.unshift(response.data.comment);

                    // Clear input
                    this.newComment = '';

                    // Emit event
                    this.$emit('comment-saved', {
                        sumDetailId: this.selectedSumDetail.id,
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
                await axios.delete(
                    route('sum.comments.delete', { comment: comment.id }),
                    {
                        headers: {
                            'Accept': 'application/json'
                        }
                    }
                );

                // Remove comment from local list
                if (this.selectedSumDetail.comments) {
                    const index = this.selectedSumDetail.comments.findIndex(c => c.id === comment.id);
                    if (index !== -1) {
                        this.selectedSumDetail.comments.splice(index, 1);

                        // Emit event
                        this.$emit('comment-deleted', {
                            sumDetailId: this.selectedSumDetail.id,
                            commentId: comment.id
                        });
                    }
                }
            } catch (error) {
                console.error('Error deleting comment:', error);
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
                this.moneySourceSearchResults = [];
            }
        },

        selectMoneySource(source) {
            this.selectedMoneySource = source;
            this.moneySourceQuery = '';
            this.moneySourceSearchResults = [];
        },

        async saveMoneySourceLink() {
            if (!this.isLinked || !this.selectedMoneySource) {
                return;
            }

            try {
                if (this.selectedSumDetail.sum_money_source === null) {
                    // Create new link
                    await axios.post(
                        route('project.sum.money.source.store'),
                        {
                            sourceable_id: this.selectedSumDetail.id,
                            sourceable_type: this.selectedSumDetail.class,
                            linked_type: this.linkedType.type,
                            money_source_id: this.selectedMoneySource.id
                        },
                        {
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            }
                        }
                    );
                } else {
                    // Update existing link
                    await axios.patch(
                        route('project.sum.money.source.update', { sumMoneySource: this.selectedSumDetail.sum_money_source.id }),
                        {
                            linked_type: this.linkedType.type,
                            money_source_id: this.selectedMoneySource.id
                        },
                        {
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            }
                        }
                    );
                }

                this.$emit('money-source-updated');
                this.handleClose();
            } catch (error) {
                console.error('Error saving money source link:', error);
                alert(this.$t('Error saving funding source link. Please try again.'));
            }
        },

        async removeMoneySourceLink() {
            if (!this.selectedSumDetail.sum_money_source) {
                return;
            }

            try {
                await axios.delete(
                    route('project.sum.money.source.destroy', { sumMoneySource: this.selectedSumDetail.sum_money_source.id }),
                    {
                        headers: {
                            'Accept': 'application/json'
                        }
                    }
                );

                this.$emit('money-source-updated');
                this.handleClose();
            } catch (error) {
                console.error('Error removing money source link:', error);
                alert(this.$t('Error removing funding source link. Please try again.'));
            }
        }
    }
};
</script>

<style scoped>
.list-enter-active,
.list-leave-active {
    transition: all 0.3s ease;
}

.list-enter-from,
.list-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
