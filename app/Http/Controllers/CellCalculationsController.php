<?php

namespace App\Http\Controllers;

use Artwork\Modules\Budget\Models\CellCalculations;
use Illuminate\Http\Request;

class CellCalculationsController extends Controller
{
    /**
     * Remove the specified resource from storage.
     *
     * @param  \Artwork\Modules\Budget\Models\CellCalculations  $cellCalculations
     * @return \Illuminate\Http\Response
     */
    public function destroy(CellCalculations $cellCalculation): \Illuminate\Http\RedirectResponse
    {
        $cellCalculation->delete();

        // reset Positions on all other calculations in this cell
        $cellCalculation->cell->calculations->each(function ($calculation, $index): void {
            $calculation->update([
                'position' => $index
            ]);
        });

        return back();
    }
}
