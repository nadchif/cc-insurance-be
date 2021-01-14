<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    //
    public function index()
    {
        $currentUser = Auth::user();
        if ($currentUser->category != 'admin') {

            $user = User::where('email', $currentUser->email)->first();

            return response()->json(array(
                'data' => array([
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'category' => $user->category,
                    'phone' => $user->phone,
                    'address' => $user->address,
                ]),

                'errors' => null,
            ), 200);
        }

        $users = DB::select('select * from users', [
            1,
        ]);

        // The array we're going to return
        $result = array_map(function ($user) {
            return array(
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'category' => $user->category,
                'phone' => $user->phone,
                'address' => $user->address,
            );
        }, $users);

        return response()->json(array(
            'data' => $result,
            'errors' => null,
        ), 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|min:2',
            'last_name' => 'required|string|min:2',
            'phone' => 'string',
            'address' => 'string',
            'email' => 'required|email',
            'entity' => 'required|integer',
            'password' => 'required|string',
        ]);
        DB::beginTransaction();
        try {
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => password_hash($request->password, PASSWORD_DEFAULT),
                'church' => $request->church,
                'phone' => isset($request->phone) ? $request->phone : '',
                'address' => isset($request->address) ? $request->address : '',
                'entity' => $request->entity,
            ]);
            $user->sendEmailVerificationNotification();
            DB::commit();
            return response()->json(array(
                'data' => $user,
                'errors' => null,
            ), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(array(
                'data' => false,
                'errors' => $e->getMessage(),
            ), 500);
        }

    }
}
