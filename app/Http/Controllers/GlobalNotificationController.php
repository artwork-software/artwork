<?php

namespace App\Http\Controllers;

use Artwork\Modules\Notification\Models\GlobalNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class GlobalNotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $oldGlobalNotifications = GlobalNotification::all();
        foreach ($oldGlobalNotifications as $globalNotification){
            if($globalNotification->image_name) {
                Storage::disk('public')->delete($globalNotification->image_name);
            }
            $globalNotification->delete();
        }
        $image = $request->file('notificationImage');

        $user = Auth::user();
        $user->globalNotifications()->create([
            'title' => $request->notificationName,
            'description' => $request->notificationDescription,
            'image_name' => $image?->storePublicly('notificationImage', ['disk' => 'public']),
            'expiration_date' => new \DateTime($request->notificationDeadlineDate . ' ' . $request->notificationDeadlineTime)
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Artwork\Modules\Notification\Models\GlobalNotification  $globalNotification
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(GlobalNotification $globalNotification)
    {
        $notification = $globalNotification->first();

        return response()->json($notification);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Artwork\Modules\Notification\Models\GlobalNotification  $globalNotification
     * @return \Illuminate\Http\Response
     */
    public function edit(GlobalNotification $globalNotification)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Artwork\Modules\Notification\Models\GlobalNotification  $globalNotification
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, GlobalNotification $globalNotification)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Artwork\Modules\Notification\Models\GlobalNotification  $globalNotification
     * @return \Illuminate\Http\Response
     */
    public function destroy(GlobalNotification $globalNotification)
    {
        $globalNotification->delete();
    }
}
