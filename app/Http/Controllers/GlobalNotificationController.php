<?php

namespace App\Http\Controllers;

use App\Models\GlobalNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

class GlobalNotificationController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $oldGlobalNotifications = GlobalNotification::all();
        foreach ($oldGlobalNotifications as $globalNotification) {
            if ($globalNotification->image_name) {
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
        return Redirect::back();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\GlobalNotification  $globalNotification
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(GlobalNotification $globalNotification): \Illuminate\Http\JsonResponse
    {
        $notification = $globalNotification->first();

        return response()->json($notification);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\GlobalNotification  $globalNotification
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(GlobalNotification $globalNotification): \Illuminate\Http\RedirectResponse
    {
        $globalNotification->delete();
        return Redirect::back()->with('success', 'Notification deleted');
    }
}
