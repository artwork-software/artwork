<?php

namespace Artwork\Modules\MoneySource\Http\Requests;

/**
 * Gleiche Regeln wie beim Anlegen; die Route-Bindung liefert die Quelle, der Controller prüft
 * MoneySourcePolicy::update auf ihr und auf allen per group_id/sub_money_source_ids verknüpften Quellen.
 */
class UpdateMoneySourceRequest extends StoreMoneySourceRequest
{
}
