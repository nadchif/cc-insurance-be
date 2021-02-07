<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    public function get()
    {
        $currentUser = Auth::user();
        if ($currentUser->category !== 'admin') {
            return $this->respondNoPermission();
        }
        $system_settings = Setting::firstOrFail();

        $admin_users = User::where('category', 'admin')->get()->toArray();
        $admins = array_map(function ($user) {
            return [
                'id' => $user['id'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
            ];
        }, $admin_users);

        $blocked_users = User::where('blocked', 1)->get()->toArray();
        $blocked = array_map(function ($user) {
            return [
                'id' => $user['id'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
            ];
        }, $blocked_users);

        $settings = [
            'admins' => $admins,
            'blocked_users' => $blocked,
            'receiving_email' => $system_settings->receiving_email,
            'user_signup' => $system_settings->user_signup,
            'monthly_backup' => $system_settings->monthly_backup,
        ];

        if ($currentUser->category === 'admin') {
            return response()->json(array(
                'data' => $settings,
                'error' => null,
            ), 200);
        } else {
            // in the event user level need to retrieve settings. at the moment only admins need settings
        }

    }

    public function patch(Request $request)
    {
        $currentUser = Auth::user();
        if ($currentUser->category !== 'admin') {
            return $this->respondNoPermission();
        }

        $request->validate([
            'receiving_email' => 'email',
            'user_signup' => 'boolean',
            'monthly_backup' => 'boolean',
            'admins' => 'array|min:1|max:25',
            'admins.*' => 'integer',
            'blocked_users' => 'array',
            'blocked_users.*' => 'integer',
        ]);

        if ($request->admins != null) {
            // get all current admins
            $admin_user_ids = User::where('category', 'admin')->get()->map(function ($user) {
                return $user->id;
            })->toArray();
            $admin_list = $request->admins;

            // find out who are no longer in the list
            $to_be_removed_ids = array_filter($admin_user_ids, (function ($id) use ($admin_list) {
                return !in_array($id, $admin_list);
            }));

            foreach ($to_be_removed_ids as $id) {
                $user = User::find($id);
                $user->category = 'user';
                $user->save();
            }

            foreach ($admin_list as $id) {
                $user = User::find($id);
                $user->category = 'admin';
                $user->save();
            }
        }

        if ($request->blocked_users != null) {
            $blocked_users_ids = User::where('blocked', 1)->get()->map(function ($user) {
                return $user->id;
            })->toArray();
            $blocked_users_list = $request->blocked_users;
            
            $to_be_removed_ids = array_filter($blocked_users_ids, (function ($id) use ($blocked_users_list) {
                return !in_array($id, $blocked_users_list);
            }));

            foreach ($to_be_removed_ids as $id) {
                $user = User::find($id);
                $user->blocked = 0;
                $user->save();
            }

            foreach ($blocked_users_list as $id) {
                $user = User::find($id);
                $user->blocked = 1;
                $user->save();
            }
        }

        $system_settings = Setting::firstOrFail();
        if ($request->receiving_email != null) {
            $system_settings->receiving_email = $request->receiving_email;
        }
        if ($request->user_signup != null) {
            $system_settings->user_signup = $request->user_signup;
        }
        if ($request->monthly_backup != null) {
            $system_settings->monthly_backup = $request->monthly_backup;
        }
        try {
            $system_settings->save();
            return response(null, 204);
        } catch (\Exception $e) {

            return response()->json(array(
                'data' => null,
                'error' => $e->getMessage(),
            ), 500);

        }

    }

    private function respondNoPermission()
    {
        return response()->json(array(
            'data' => null,
            'error' => "You have no permission to access this resource",
        ), 403);
    }

}
