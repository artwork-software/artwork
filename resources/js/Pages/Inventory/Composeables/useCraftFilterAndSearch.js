import { computed, ref } from "vue";

const crafts = ref([]),
    searchValue = ref(''),
    craftFilters = ref([]),
    amountFilterValue = ref(null),
    valueIncludesSearchValueWithUmlauts = (value, isDateColumn = false) => {
        let tmpSearchValue = searchValue.value.toLowerCase(),
            tmpValue = value.toLowerCase();

        if (isDateColumn) {
            let parts = value.split('-');
            tmpValue = parts[2] + '.' + parts[1] + '.' + parts[0];
        }

        return (
            tmpValue.includes(tmpSearchValue) ||
            tmpValue.includes(tmpSearchValue.replace('o', 'ö')) ||
            tmpValue.includes(tmpSearchValue.replace('u', 'ü')) ||
            tmpValue.includes(tmpSearchValue.replace('a', 'ä'))
        );
    };

export default function useCraftFilterAndSearch() {
    const filteredCrafts = computed(() => {
        let filteringCrafts = JSON.parse(JSON.stringify(crafts.value));

        filteringCrafts.forEach((craft) => {
            craft.filtered_inventory_categories = craft.inventory_categories;
        })

        if (craftFilters.value.length > 0) {
            filteringCrafts = filteringCrafts.filter(craft =>
                craftFilters.value.includes(craft.id)
            );
        }

        if (searchValue.value.length > 0) {
            filteringCrafts.forEach(craft => {
                let filteredCategories = [];

                craft.filtered_inventory_categories.forEach(category => {
                    let categoryMatches = valueIncludesSearchValueWithUmlauts(category.name);

                    if (categoryMatches) {
                        filteredCategories.push(category);
                        return;
                    }

                    let matchedGroups = category.groups.filter(group => {
                        let groupMatches = valueIncludesSearchValueWithUmlauts(group.name),
                            matchedFolders = group.folders.filter(folder =>
                                valueIncludesSearchValueWithUmlauts(folder.name) ||
                                folder.items.some(item =>
                                    item.cells.some(cell =>
                                        valueIncludesSearchValueWithUmlauts(cell.cell_value, cell.column.type === 1)
                                    )
                                )
                            );

                        if (groupMatches || matchedFolders.length > 0) {
                            group.folders = matchedFolders;
                            return true;
                        }

                        group.items = group.items.filter(item =>
                            item.cells.some(cell =>
                                valueIncludesSearchValueWithUmlauts(cell.cell_value, cell.column.type === 1)
                            )
                        );

                        return group.items.length > 0;
                    });

                    if (matchedGroups.length > 0) {
                        category.groups = matchedGroups;
                        filteredCategories.push(category);
                    }
                });

                craft.filtered_inventory_categories = filteredCategories;
            });
        }

        if (amountFilterValue.value > 0) {
            filteringCrafts.forEach(craft => {
                let filteredCategories = [];

                craft.filtered_inventory_categories.forEach(category => {
                    category.groups.forEach(
                        (group) => {
                            group.folders = group.folders.forEach(
                                (folder) => {
                                    folder.items.filter(
                                        (item) => {
                                            return item.cells.some(
                                                (cell) => {
                                                    return !isNaN(cell.cell_value) &&
                                                        Number.parseInt(cell.cell_value) >= amountFilterValue.value;
                                                }
                                            )
                                        }
                                    )
                                }
                            )

                            group.items = group.items.filter(
                                (item) => {
                                    return item.cells.some(
                                        (cell) => {
                                            return !isNaN(cell.cell_value) &&
                                                Number.parseInt(cell.cell_value) >= amountFilterValue.value;
                                        }
                                    );
                                }
                            );
                        }
                    );

                    filteredCategories.push(category);
                });
                craft.filtered_inventory_categories = filteredCategories;
            });
        }

        return filteringCrafts.map(
            (craft) => {
                return ref(craft);
            }
        );
    });

    return { searchValue, craftFilters, crafts, filteredCrafts, amountFilterValue };
}
