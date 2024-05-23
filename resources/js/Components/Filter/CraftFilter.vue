<script>
import IconLib from "@/Mixins/IconLib.vue";

export default {
    name: "CraftFilter",
    mixins: [IconLib],
    props: {
        is_tiny: {
            type: Boolean,
            default: false
        },
        crafts: {
            type: Array,
            default: () => []
        },
    },
    data() {
        return {
            showCraftFilter: false,
            selectedCrafts: this.$page.props.user ? this.$page.props.user.show_crafts ?? [] : []
        }
    },
    methods: {
        addCraftToUser(craftId) {
            // add or remove craft from user
            if (this.selectedCrafts?.includes(craftId)) {
                this.selectedCrafts = this.selectedCrafts.filter(craft => craft !== craftId);
            } else {
                this.selectedCrafts.push(craftId);
            }

            // save to user
            this.$inertia.patch(route('user.update.show_crafts', {user: this.$page.props.user.id}), {
                show_crafts: this.selectedCrafts
            });
        }
    }

}
</script>

<template>
    <div class="my-3">
        <div>
            <div class="h-9 flex items-center cursor-pointer" @click="showCraftFilter = !showCraftFilter">
                <div class=""  :class="is_tiny ? 'flex items-center text-white text-xs' : 'flex w-full py-2 justify-between rounded-lg text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500'">
                    {{ $t('Crafts') }}

                    <IconChevronDown v-if="!showCraftFilter" class="w-4 h-4 ml-2"/>
                    <IconChevronUp v-if="showCraftFilter" class="w-4 h-4 ml-2"/>
                </div>
            </div>

            <div v-if="showCraftFilter">
                <div v-for="craft in crafts" :key="craft.id">
                    <div class="relative flex items-start">
                        <div class="flex h-6 items-center">
                            <input @change="addCraftToUser(craft.id)" :id="'craft-' + craft.id" :checked="selectedCrafts?.includes(craft.id)" aria-describedby="comments-description" name="comments" type="checkbox" class="h-4 w-4 border-gray-300 text-artwork-buttons-create focus:ring-artwork-buttons-create" />
                        </div>
                        <div class="ml-3 text-sm leading-6">
                            <label :for="'craft-' + craft.id" class="font-medium text-white">{{ craft.name }}</label>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>

</style>
