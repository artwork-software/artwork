<?php

namespace Artwork\Core\Cache;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Collection;

trait UsesArrayCache
{
    /**
     * Die vollständige Menge — wird ausschließlich über setAll() gefüllt.
     */
    protected static ?Collection $items = null;

    /**
     * Ob static::$items wirklich die komplette Menge enthält.
     *
     * Ohne dieses Flag konnte getAll() "leer" nicht von "noch nicht geladen"
     * unterscheiden: eine leere Collection ist in PHP truthy, sodass die
     * Prüfung `if (!$all = ...::getAll())` in den aufrufenden Services nicht
     * mehr anschlug, sobald irgendeine andere Cache-Methode static::$items
     * initialisiert hatte. Ergebnis war eine leere Event-Type-Liste, abhängig
     * von der Aufrufreihenfolge innerhalb des Requests.
     */
    protected static bool $allLoaded = false;

    /**
     * Einzeln geladene Modelle aus getItem()/getItemByName(). Absichtlich
     * getrennt von static::$items, damit Teiltreffer die Menge nicht als
     * vollständig erscheinen lassen.
     *
     * @var array<int|string, Model>
     */
    protected static array $itemsById = [];

    /**
     * @var array<string, Model>
     */
    protected static array $itemsByName = [];

    public static function forgetAll(): void
    {
        static::$items = null;
        static::$allLoaded = false;
        static::$itemsById = [];
        static::$itemsByName = [];
    }

    public static function getAll(): ?Collection
    {
        return static::$allLoaded ? static::$items : null;
    }

    public static function setAll(Collection $items): void
    {
        static::$items = $items;
        static::$allLoaded = true;

        foreach ($items as $item) {
            static::rememberItem($item);
        }
    }

    public static function setItem(Model $item): void
    {
        if (static::$allLoaded && static::$items !== null) {
            static::$items->add($item);
        }

        static::rememberItem($item);
    }

    public static function getItem(int $itemId): ?Model
    {
        if (array_key_exists($itemId, static::$itemsById)) {
            return static::$itemsById[$itemId];
        }

        /** @var ServiceWithArrayCache $service */
        $service = app()->get(static::$service);
        $item = $service->findByIdWithoutCache($itemId);

        // Ohne dieses Merken war der Cache wirkungslos: getItem() lieferte einen
        // Treffer zurück, wodurch der setItem()-Zweig der aufrufenden Services
        // nie lief und jeder Aufruf eine eigene Query auslöste (im Projekt-
        // Kalender-Tab 2.759x dieselbe event_types-Query).
        if ($item !== null) {
            static::rememberItem($item);
        }

        return $item;
    }

    public static function getItemByName(string $name): ?Model
    {
        if (array_key_exists($name, static::$itemsByName)) {
            return static::$itemsByName[$name];
        }

        /** @var ServiceWithArrayCache $service */
        $service = app()->get(static::$service);
        $item = $service->findByNameWithoutCache($name);

        if ($item !== null) {
            static::rememberItem($item);
        }

        return $item;
    }

    private static function rememberItem(Model $item): void
    {
        $id = $item->getAttribute('id');
        if ($id !== null) {
            static::$itemsById[$id] = $item;
        }

        $name = $item->getAttribute('name');
        if (is_string($name) && $name !== '') {
            static::$itemsByName[$name] = $item;
        }
    }
}
