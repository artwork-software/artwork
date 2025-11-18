<template>
    <div class="border border-gray-200 rounded-lg select-none">
        <div class="bg-gray-200 py-5 px-4 flex items-center justify-between" :class="!layoutClosed ? 'rounded-t-lg' : 'rounded-lg'" >
            <div  @click="layoutClosed = !layoutClosed" class="cursor-pointer flex items-center justify-between w-fit gap-x-10">
                <div>
                    <h3 class="headline3">{{ layout.name }}</h3>
                    <p class="xsDark mt-1">{{ layout.description }}</p>
                </div>
                <component :is="IconChevronDown" :class="layoutClosed ? 'rotate-180' : ''" class="transition-all duration-300 ease-in-out h-6 w-6" />
            </div>
            <div>
                <BaseMenu has-no-offset>
                    <BaseMenuItem title="Edit" icon="IconEdit" white-menu-background  @click="showCreateOrUpdateModal = true"/>
                    <BaseMenuItem title="Delete" :icon="IconTrash" @click="showDeleteModal = true"/>
                </BaseMenu>
            </div>
        </div>
        <div v-if="!layoutClosed" class="rounded-b-lg border-gray-100">
            <div class="p-6">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="xsDark mb-2">{{ $t('Header') }}</h4>
                    <div>
                        <component :is="IconNote" class="cursor-pointer size-5 hover:text-artwork-buttons-create ease-in-out duration-200" @click="layout.open_header_notes = !layout.open_header_notes" />
                    </div>
                </div>
                <div class="grid gap-4" :class="'grid-cols-' + layout['columns_header']">
                    <div v-for="index in layout['columns_header']" v-if="layout.open_header_notes">
                        <TextareaComponent
                            :id="'headerNote' + index"
                            v-model="layout.notes.header[index - 1]"
                            :label="$t('Header') + ' ' + index"
                            @focusout="saveNotes('header')"
                        />
                    </div>
                    <div v-else v-for="index in layout['columns_header']">
                        <p class="xxsLight" v-html="breakLine(layout.notes.header[index - 1])" />
                    </div>
                    <template v-for="col in layout['columns_header']" :key="col">
                        <div v-if="getComponent(layout, 'header', 1, col)" :key="getComponent(layout, 'header', 1, col).id">
                            <SingleComponentInPrintLayout :component="getComponent(layout, 'header', 1, col)" />
                        </div>
                        <div v-else>
                            <EmptyProjectPrintComponentDropElement has-no-offset :row="1" :col="col" type="header" :column-size="layout['columns_header']" :project-print-layout="layout" :components="components" />
                        </div>
                    </template>
                </div>
                <hr class="my-5 opacity-20">

                <h4 class="xsDark mb-2">{{ $t('Body') }}</h4>
                <div class="grid gap-4">
                    <template v-for="row in getRowCount(layout, 'body')" :key="row">
                        <div class="grid grid-cols-1 gap-4" :class="'grid-cols-' + layout['columns_body']">
                            <template v-for="col in layout['columns_body']" :key="col">
                                <div v-if="getComponent(layout, 'body', row, col)" :key="getComponent(layout, 'body', row, col).id">
                                    <SingleComponentInPrintLayout :component="getComponent(layout, 'body', row, col)" />
                                </div>
                                <div v-else-if="row === getRowCount(layout, 'body')">
                                    <EmptyProjectPrintComponentDropElement
                                        :row="row"
                                        :col="col"
                                        type="body"
                                        :column-size="layout['columns_body']"
                                        :project-print-layout="layout"
                                        :components="components"
                                        has-no-offset
                                    />
                                </div>
                                <div v-else>
                                    <EmptyProjectPrintComponentDropElement
                                        :row="row"
                                        :col="col"
                                        type="body"
                                        :column-size="layout['columns_body']"
                                        :project-print-layout="layout"
                                        :components="components"
                                        has-no-offset
                                    />
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <hr class="my-5 opacity-20">

                <div class="flex items-center justify-between mb-2">
                    <h4 class="xsDark mb-2">{{ $t('Footer') }}</h4>
                    <div>
                        <component :is="IconNote" class="cursor-pointer size-5 hover:text-artwork-buttons-create ease-in-out duration-200" @click="layout.open_footer_notes = !layout.open_footer_notes" />
                    </div>
                </div>
                <div class="grid gap-4" :class="'grid-cols-' + layout['columns_footer']">
                    <div v-for="index in layout['columns_header']" v-if="layout.open_footer_notes">
                        <TextareaComponent
                            :id="'footerNote' + index"
                            v-model="layout.notes.footer[index - 1]"
                            :label="$t('Footer') + ' ' + index"
                            @focusout="saveNotes('footer')"
                        />
                    </div>
                    <div v-else v-for="index in layout['columns_header']">
                        <p class="xxsLight" v-html="breakLine(layout.notes.footer[index - 1])" />
                    </div>
                    <template v-for="col in layout['columns_footer']" :key="col">
                        <div v-if="getComponent(layout, 'footer', 1, col)" :key="getComponent(layout, 'footer', 1, col).id">
                            <SingleComponentInPrintLayout :component="getComponent(layout, 'footer', 1, col)" />
                        </div>
                        <div v-else>
                            <EmptyProjectPrintComponentDropElement
                                :row="1"
                                :col="col"
                                type="footer"
                                :column-size="layout['columns_footer']"
                                :project-print-layout="layout"
                                :components="components"
                                has-no-offset
                            />
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <CreateOrUpdateProjectPrintLayoutModal
        :project-print-layout="layout"
        v-if="showCreateOrUpdateModal"
        @close="showCreateOrUpdateModal = false"
    />

    <ConfirmDeleteModal
        :title="$t('Delete print layout')"
        :description="$t('Are you sure you want to delete the selected print layout?')"
        v-if="showDeleteModal"
        @close="showDeleteModal = false"
        @delete="deleteLayout"
    />
</template>

<script setup>

import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import EmptyProjectPrintComponentDropElement from "@/Pages/Settings/ProjectPrintLayout/Components/EmptyProjectPrintComponentDropElement.vue";
import SingleComponentInPrintLayout
    from "@/Pages/Settings/ProjectPrintLayout/Components/SingleComponentInPrintLayout.vue";
import CreateOrUpdateProjectPrintLayoutModal
    from "@/Pages/Settings/ProjectPrintLayout/Components/CreateOrUpdateProjectPrintLayoutModal.vue";
import {ref} from "vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import {router} from "@inertiajs/vue3";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {IconChevronDown, IconNote, IconTrash} from "@tabler/icons-vue";

const props = defineProps({
    layout: {
        type: Object,
        required: true
    },
    components: {
        type: Object,
        required: true
    }
})

const showCreateOrUpdateModal = ref(false);
const showDeleteModal = ref(false);
const layoutClosed = ref(false);
const getComponent = (layout, section, row, col) => {
    return layout[section + '_components'].find(comp => comp.row === row && comp.position === col) || null;
};

const getRowCount = (layout, section) => {
    if (section !== 'body') return 1; // Header und Footer haben feste Zeilen
    const maxOccupiedRow = getMaxOccupiedRow(layout, section);
    const hasComponentInRow = layout[section + '_components'].some(comp => comp.row === maxOccupiedRow);
    return hasComponentInRow ? maxOccupiedRow + 1 : maxOccupiedRow;
};

const getMaxOccupiedRow = (layout, section) => {
    const components = layout[section + '_components'];
    return components.length ? Math.max(...components.map(c => c.row)) : 1;
};

const breakLine = (text) => {
    // add line breaks to text
    return text?.replace(/\n/g, '<br>');
}

const saveNotes = (type) => {
    router.patch(route('project-print-layout.update.header.note', props.layout.id), {
        header_note: props.layout.notes.header,
        footer_note: props.layout.notes.footer,
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            if (type === 'header') {
                props.layout.open_header_notes = true;
            } else {
                props.layout.open_footer_notes = true;
            }
        }
    });
}

const deleteLayout = () => {
    router.delete(route('project-print-layout.destroy', props.layout.id), {
        preserveScroll: true,
    });
}
</script>

<style scoped>

</style>
