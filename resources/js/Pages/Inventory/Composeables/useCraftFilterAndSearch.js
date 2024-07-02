import {computed, ref} from "vue";

const crafts = ref([]),
    searchValue = ref(''),
    craftFilters = ref([]),
    valueIncludesSearchValueWithUmlauts = (value, isDateColumn = false) => {
        let tmpSearchValue = searchValue.value.toLowerCase(),
            tmpValue = value.toLowerCase();

        if (isDateColumn) {
            let parts = value.split('-');

            tmpValue = parts[2] + '.' + parts[1] + '.' + parts[0];
        }

        return (
            tmpValue.indexOf(tmpSearchValue) > -1 ||
            tmpValue.indexOf(tmpSearchValue.replace('o', 'ö')) > -1 ||
            tmpValue.indexOf(tmpSearchValue.replace('u', 'ü')) > -1 ||
            tmpValue.indexOf(tmpSearchValue.replace('a', 'ä')) > -1
        );
    };

export default function useCraftFilterAndSearch() {
    const filteredCrafts = computed(() => {
        //clone object is important for replaceState after any updates from backend
        //(change craft filter for example)
        let filteringCrafts = JSON.parse(JSON.stringify(crafts.value));

        if (craftFilters.value.length > 0) {
            filteringCrafts = filteringCrafts.filter(
                (craft) => {
                    return craftFilters.value.length === 0 || craftFilters.value.includes(craft.id);
                }
            );
        }

        if (searchValue.value.length === 0) {
            filteringCrafts.forEach(
                (craft) => craft.filtered_inventory_categories = craft.inventory_categories
            )
            return filteringCrafts.map(
                (craft) => ref(craft)
            );
        }

        filteringCrafts.forEach((craft) => {
            let filteredCategories = [];

            craft.inventory_categories.forEach((category) => {
                let categoryMatches = false,
                    matchedGroups = [];

                if (valueIncludesSearchValueWithUmlauts(category.name)) {
                    categoryMatches = true;
                }

                category.groups.forEach((group) => {
                    let currentGroupMatched = false,
                        matchedItems = [];

                    if (valueIncludesSearchValueWithUmlauts(group.name)) {
                        currentGroupMatched = true;
                    }

                    //even if group is not matched we need to filter the items
                    //if group is matched we show all items if no item matches, if at least one item
                    //matches we show only matching items
                    group.items.forEach((item) => {
                        let matchingCells = item.cells.filter((cell) => {
                            return valueIncludesSearchValueWithUmlauts(cell.cell_value, (cell.column.type === 1));
                        });

                        if (matchingCells.length > 0) {
                            matchedItems.push(item);
                        }
                    });

                    //no items found and group not matching, just push if category matched
                    if (matchedItems.length === 0 && !currentGroupMatched) {
                        if (!categoryMatches) {
                            return;
                        }
                        matchedGroups.push(group);
                        return;
                    }

                    //no items found but group is matching, push it
                    if (matchedItems.length === 0 && currentGroupMatched) {
                        matchedGroups.push(group);
                        return;
                    }

                    //group matched and items too, replace with matched item and push it
                    group.items = matchedItems;
                    matchedGroups.push(group);
                });

                if (
                    categoryMatches ||
                    !categoryMatches && matchedGroups.length > 0
                ) {
                    category.groups = matchedGroups;
                    filteredCategories.push(category);
                }
            });

            craft.filtered_inventory_categories = filteredCategories;
        });

        return filteringCrafts.map(
            (craft) => ref(craft)
        );
    });

    return {searchValue, craftFilters, crafts, filteredCrafts};
}

