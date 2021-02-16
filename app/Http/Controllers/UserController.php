<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function __construct()
    {
        $this->required_fields = [
            'first_name' => 'required|string|min:2',
            'last_name' => 'required|string|min:2',
            'phone' => 'string',
            'address' => 'string',
            'email' => 'required|email',
            'entity' => 'required|integer',
            'password' => 'required|string',
        ];
    }

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

        $users = DB::table('users')->join('entities', 'entities.id', '=', 'users.entity')
            ->select([
                'users.*',
                'entities.name as entity_name',
            ])->orderBy('last_name', 'asc')->get()->toArray();

        return response()->json(array(
            'data' => $users,
            'errors' => null,
        ), 200);
    }

    public function store(Request $request)
    {
        $request->validate($this->required_fields);
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
            $code = $e->getCode();
            return response()->json(array(
                'data' => false,
                'error' => $code == 23000 ? 'Cannot use email/username. Try another' : $e->getMessage(),
                
            ), $code == 23000 ? 409 : 500);
        }

    }

    public function get($id)
    {
        $result = $this->findWithCheckPermissions($id);
        if ($result['success'] === true) {
            $user = $result['data'];
            return response()->json(array(
                'data' => $user,
                'error' => null,
            ), 200);
        }
        return $this->handleEntryFindResponse($result);
    }
    public function patch(Request $request, $id)
    {
        $request->validate([
            'first_name' => 'string|min:2',
            'last_name' => 'string|min:2',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'entity' => 'integer',
            'blocked' => 'boolean',
        ]);

        $currentUser = Auth::user();
        $result = $this->findWithCheckPermissions($id);
        if ($result['success'] === true) {
            $user = $result['data'];

            if ($request->first_name != null) {
                $user->first_name = $request->first_name;
            }
            if ($request->last_name != null) {
                $user->last_name = $request->last_name;
            }
            if ($request->phone != null) {
                $user->phone = $request->phone;
            }
            if ($request->address != null) {
                $user->address = $request->address;
            }
            if ($request->entity != null) {
                $user->entity = $request->entity;
            }
            if ($currentUser->category === 'admin') {
                if ($request->blocked !== null) {
                    if ($request->blocked == 1 && strtolower($user->category) == 'admin') {
                        return response()->json(array(
                            'data' => false,
                            'error' => 'Cannot block admin. Make sure user is a regular user first',
                        ), 403);
                    }
                    $user->blocked = $request->blocked;
                }
            }
            $user->save();

            return response()->json(array(
                'data' => $user,
                'error' => null,
            ), 200);
        }
        return $this->handleEntryFindResponse($result);

    }
    public function delete($id)
    {
        $result = $this->findWithCheckPermissions($id);
        if ($result['success'] === true) {
            $entry = $result['data'];
            if (strtolower($entry->category) == 'admin') {
                return response()->json(array(
                    'data' => false,
                    'error' => 'Cannot delete admin. Make sure user is a regular user first',
                ), 403);
            }
            $entry->delete();
            return response(null, 204);
        }
        return $this->handleEntryFindResponse($result);
    }
    public function batchDelete(Request $request)
    {

        $request->validate(['ids' => 'required|array|min:2|max:25']);
        $deleteList = array();
        foreach ($request->ids as $id) {
            $result = $this->findWithCheckPermissions($id);
            if ($result['success'] !== true) {
                return $this->handleEntryFindResponse($result, "You do not have permission to delete resource with id: " . $id);
            }
            $deleteList[] = $result['data'];
        }
        foreach ($deleteList as $entry) {
            $entry->delete();
        }
        response(null, 204);
    }

    private function findWithCheckPermissions($id)
    {
        $currentUser = Auth::user();
        $item = User::find($id);
        if ($currentUser->category === 'admin') {
            return ['success' => $item !== null ? true : false, 'data' => $item, 'status' => $item !== null ? 'ok' : 'not_found'];
        }else {
            if ($item && $item->id == $currentUser->id) {
                return ['success' => true, 'data' => $item];
            } else {
                return ['success' => false, 'data' => null, 'status' => 'no_permission'];
            }
        }
        return ['success' => false, 'data' => null, 'status' => 'not_found'];
    }

    private function handleEntryFindResponse($result, $noPermissionMsg = "You have no permission to modify this resource")
    {
        if ($result['status'] === 'no_permission') {
            return response()->json(array(
                'data' => null,
                'error' => $noPermissionMsg,
            ), 403);
        }
        return response()->json(array(
            'data' => null,
            'error' => "This resource could not be found",
        ), 404);
    }
}
