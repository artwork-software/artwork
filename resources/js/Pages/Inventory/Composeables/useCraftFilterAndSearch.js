import { computed, ref } from "vue";

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
            tmpValue.includes(tmpSearchValue) ||
            tmpValue.includes(tmpSearchValue.replace('o', 'ö')) ||
            tmpValue.includes(tmpSearchValue.replace('u', 'ü')) ||
            tmpValue.includes(tmpSearchValue.replace('a', 'ä'))
        );
    };

export default function useCraftFilterAndSearch() {
    const filteredCrafts = computed(() => {
        let filteringCrafts = JSON.parse(JSON.stringify(crafts.value));

        // Filter basierend auf craftFilters
        if (craftFilters.value.length > 0) {
            filteringCrafts = filteringCrafts.filter(craft =>
                craftFilters.value.includes(craft.id)
            );
        }

        // Wenn kein Suchwert vorhanden ist, gib alle zurück
        if (searchValue.value.length === 0) {
            filteringCrafts.forEach(craft => {
                craft.filtered_inventory_categories = craft.inventory_categories;
            });
            return filteringCrafts.map(craft => ref(craft));
        }

        // Filterlogik für Suchwert
        filteringCrafts.forEach(craft => {
            let filteredCategories = [];

            craft.inventory_categories.forEach(category => {
                // Prüfen, ob Kategorie den Suchwert enthält
                let categoryMatches = valueIncludesSearchValueWithUmlauts(category.name);

                // Wenn die Kategorie passt, alle Gruppen und Ordner übernehmen
                if (categoryMatches) {
                    filteredCategories.push(category);
                    return;
                }

                // Ansonsten nur passende Gruppen und Ordner filtern
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

                    // Wenn Gruppe oder Ordner passt, zeige alle Inhalte
                    if (groupMatches || matchedFolders.length > 0) {
                        group.folders = matchedFolders;
                        return true;
                    }

                    // Filtere Gruppen basierend auf Items
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

        return filteringCrafts.map(craft => ref(craft));
    });

    return { searchValue, craftFilters, crafts, filteredCrafts };
}
