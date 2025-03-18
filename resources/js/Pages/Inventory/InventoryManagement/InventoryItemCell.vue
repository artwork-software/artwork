<template>
    <td :class="getCellCls()" class="relative">
        <span v-if="hasCellValue()" :class="getCellValueCls()" @click="toggleCellEdit()">
            <template v-if="isTextColumn() || isSelectColumn() || isNumberColumn()">
                <span v-if="isTextColumn()" :title="cell.cell_value">
                    {{ cell.cell_value }}
                </span>
                <template v-else>
                    {{ cell.cell_value }}
                </template>
            </template>
            <template v-else-if="isDateColumn()">
                {{ formatDate(cell.cell_value) }}
            </template>
            <template v-else-if="isCheckboxColumn()">
                {{ cell.cell_value === 'true' ? $t('Yes') : $t('No') }}
            </template>
            <template v-else-if="isUploadColumn()">
                <div class="flex items-center justify-between gap-4">
                    <div @click="getDownloadLink">
                        <div class="text-blue-400 underline-offset-2 underline cursor-pointer flex items-center gap-1">
                            <div class="truncate max-w-52">{{ cell.cell_value }}</div>
                             {{ $t('View') }}
                        </div>
                    </div>
                    <div>
                        <div class="text-red-500 underline-offset-2 underline cursor-pointer"
                             @click="isDeleteModalOpen = true">
                            {{ $t('Delete') }}
                        </div>
                    </div>
                </div>
            </template>
            <template v-else-if="isLastEditField()">
                <div class="flex items-center gap-1 cursor-grab">
                    {{ computedJsonValue.date }}
                    <div class="flex items-center gap-1">
                        <UserPopoverTooltip :user="computedJsonValue.editor" height="5" width="5"/>
                        <div>
                            {{  computedJsonValue.editor.full_name  }}
                        </div>
                    </div>
                </div>
            </template>
        </span>
        <div v-else-if="isCheckboxColumn()" class="checkbox-container" @click="toggleCellEdit()">
            {{ $t('No') }}
        </div>
        <div v-else-if="isUploadColumn() && !hasCellValue()" class="checkbox-container">
            <div class="flex items-center">
                <input
                    ref="fileInputRefEmpty"
                    type="file"
                    class="text-input"
                    @change="uploadFileToColumn()"
                />
                <div>
                    <div class="text-red-500" v-if="uploadFeedback">
                        {{ uploadFeedback }}
                    </div>
                </div>
            </div>
        </div>
        <div v-else class="empty-cell-container" @click="toggleCellEdit()"/>
        <template v-if="cellClicked">
            <div v-if="isTextColumn()" :class="getInputCls()">
                <input ref="cellValueInputRef"
                       type="text"
                       class="text-input"
                       v-model="cellValue"
                       @focusout="applyCellValueChange()"/>
            </div>
            <div v-else-if="isDateColumn()" :class="getInputCls()">
                <input ref="cellValueInputRef"
                       type="date"
                       class="date-input"
                       v-model="cellValue"
                       @focusout="applyCellValueChange()"/>
            </div>
            <div v-else-if="isCheckboxColumn()" :class="getInputCls()">
                <input ref="cellValueInputRef"
                       type="checkbox"
                       class="checkbox-input"
                       v-model="cellValue"
                       @focusout="applyCellValueChange()"/>
            </div>
            <div v-else-if="isNumberColumn()" :class="getInputCls()">
                <input ref="cellValueInputRef"
                       type="number"
                       class="text-input"
                       v-model="cellValue"
                       step="0.01"
                       inputmode="numeric"
                       pattern="^\d*(\.\d{0,2})?$"
                       @focusout="applyCellValueChange()"/>
            </div>
            <div v-else-if="isSelectColumn()" :class="getInputCls()">
                <select ref="cellValueInputRef"
                        class="select-input"
                        v-model="cellValue" @focusout="applyCellValueChange()">
                    <option v-for="(option) in cell.column.type_options">
                        {{ option }}
                    </option>
                </select>
            </div>
        </template>
    </td>
    <ConfirmDeleteModal
        v-if="isDeleteModalOpen"
        @closed="isDeleteModalOpen = false"
        @delete="deleteFile"
        :title="$t('Delete file')"
        :description="$t('Are you sure you want to delete this file?')"/>

</template>

<script setup>
import {usePermission} from "@/Composeables/Permission.js";
import {usePage} from "@inertiajs/vue3";
import { is, can } from 'laravel-permission-to-vuejs'
import {computed, onMounted, ref} from "vue";
import {router} from "@inertiajs/vue3";
import Input from "@/Layouts/Components/InputComponent.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {useTranslation} from "@/Composeables/Translation.js";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
const $t = useTranslation()
const emits = defineEmits(['isEditingCellValue']),
    props = defineProps({
        cell: {
            type: Object,
            required: true
        }
    }),
    cellValueInputRef = ref(null),
    cellValue = ref(props.cell.cell_value),
    cellClicked = ref(false),
    fileInputRefEmpty = ref(null),
    isDeleteModalOpen = ref(false),
    uploadFeedback = ref(''),
    getCellCls = () => {
        let addedClasses = '';

        if (isLastEditField()){
            addedClasses = '!cursor-default';
        }

        return [
            getBackgroundCls(),
            'max-w-40 h-10 px-3 border subpixel-antialiased relative text-xs ' +
                'overflow-ellipsis overflow-hidden whitespace-nowrap ' +
                addedClasses
        ].join(' ');
    },
    getBackgroundCls = () => {
        return props.cell.column.background_color !== 'whiteColumn' ?
            props.cell.column.background_color:
            '';
    },
    getInputCls = () => {
        return [
            getBackgroundClsForInput(),
            'flex flex-row items-center gap-x-2 w-[calc(100%-0.8rem)] -translate-x-1 h-full top-0 z-50 absolute'
        ].join(' ');
    },
    getBackgroundClsForInput = () => {
        return props.cell.column.background_color !== 'bg-secondary' ?
            props.cell.column.background_color :
            'bg-white';
    },
    formatDate = (date) => {
        let parts = date.split('-');

        return parts[2] + '.' + parts[1] + '.' + parts[0];
    },
    hasCellValue = () => {
        return props.cell.cell_value.length > 0;
    },
    getCellValueCls = () => {
        return isDateColumn() || isCheckboxColumn() ? 'text-center block cursor-text' : 'cursor-text';
    },
    isTextColumn = () => {
        return props.cell.column.type === 0;
    },
    isDateColumn = () => {
        return props.cell.column.type === 1;
    },
    isCheckboxColumn = () => {
        return props.cell.column.type === 2;
    },
    isSelectColumn = () => {
        return props.cell.column.type === 3;
    },
    isNumberColumn = () => {
        return props.cell.column.type === 4;
    },
    isUploadColumn = () => {
        return props.cell.column.type === 5;
    },
    isLastEditField = () => {
        return props.cell.column.type === 99;
    },
    toggleCellEdit = () => {
        if ( !(is('artwork admin') || can('can manage inventory stock')) ) {
            return;
        }

        if(isUploadColumn() || isLastEditField()) {
            return;
        }
        cellClicked.value = !cellClicked.value;

        //emit to prevent item from being dragged, causing input
        //events to not work properly if draggable while editing value
        emits.call(this, 'isEditingCellValue', cellClicked.value, props.cell.id);

        if (cellClicked.value) {
            setTimeout(() => {
                if (isTextColumn() || isNumberColumn()) {
                    cellValueInputRef.value.select();
                    return;
                }

                cellValueInputRef.value.focus();
            }, 5);
        }
    },
    applyCellValueChange = () => {
        if (isNumberColumn() && cellValue.value !== '' && !isNaN(cellValue.value)) {
            cellValue.value = parseFloat(cellValue.value).toFixed(2);
        }

        //compare as strings in case of checkbox which are preserved as string in database
        if (props.cell.cell_value.toString() === cellValue.value.toString()) {
            toggleCellEdit();
            return;
        }

        router.patch(
            route(
                'inventory-management.inventory.item-cell.update.cell-value',
                {
                    craftInventoryItemCell: props.cell.id
                }
            ),
            {
                cell_value: String(cellValue.value)
            },
            {
                preserveScroll: true,
                onSuccess: toggleCellEdit
            }
        );
    };

const uploadFileToColumn = () => {
    const file = fileInputRefEmpty.value.files[0];
    uploadFeedback.value = '';
    if (!file) {
        uploadFeedback.value = $t('No file selected');
        return;
    }

    const formData = new FormData();
    formData.append('file', file);
    formData.append('cell_value', String(file.name));

    router.post(
        route(
            'inventory-management.inventory.item-cell.update.cell-value.upload',
            {
                craftInventoryItemCell: props.cell.id,
            }
        ),
        formData, // FormData direkt verwenden
        {
            headers: {
                'Content-Type': 'multipart/form-data', // optional, wird meist automatisch gesetzt
            },
            preserveScroll: true,
            onSuccess: () => {
                uploadFeedback.value = '';
            },
        }
    );
};

const computedJsonValue = computed( () => {
    return JSON.parse(props.cell.cell_value);
})

const getDownloadLink = () => {
    let link = document.createElement('a');
    link.href = route('inventory-management.inventory.item-cell.download', {craftInventoryItemCell: props.cell.id});
    link.click();
};

const deleteFile = () => {
    router.delete(
        route(
            'inventory-management.inventory.item-cell.update.cell-value.delete.file',
            {
                craftInventoryItemCell: props.cell.id
            }
        ),
        {
            preserveScroll: true,
            onSuccess: () => {
                isDeleteModalOpen.value = false;
            }
        }
    );
};

</script>

<style scoped>
.whiteColumn {
    background-color: #FCFCFBFF;
}

.darkBlueColumn {
    background-color: #D3DADE;
}

.darkGreenColumn {
    background-color: #DBE9E8;
}

.darkLightBlueColumn {
    background-color: #D2E9F3;
}

.lightBlueNew {
    background-color: #DAF3F6;
}

.greenColumn {
    background-color: #D7EEE0;
}

.lightGreenColumn {
    background-color: #E7F3DE;
}

.yellowColumn {
    background-color: #FCF0DB;
}

.orangeColumn {
    background-color: #FBE4DA;
}

.redColumn {
    background-color: #F7D9E7;
}

.pinkColumn {
    background-color: #E1D1DC;
}
</style>
