<?php
namespace App\Http\Controllers;

use App\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EntityController extends Controller
{
    public function __construct() {
       $this->required_fields = [
            'name' => 'required|string|min:2',
            'address' => 'required|string',
            'phone' => 'nullable|regex:/^([0-9\s\-\+\(\)]*)$/|min:5',
            'code' => 'required|string|min:2',
       ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $entities = Entity::orderBy('name')->get();
        return response()->json(array(
            'data' => $entities,
            'error' => null,
        ), 200);
    }
    
    public function post(Request $request)
    {
        $request->validate($this->required_fields);

        try {
            $entity = Entity::create([
                'name' => $request->name,
                'code' => $request->code,
                'address'=>$request->address,
                'phone' => $request->phone !== null ? $request->phone : ''
            ]);
            DB::commit();
            return response()->json(array(
                'data' => $entity,
                'error' => null,
            ), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(array(
                'data' => false,
                'error' => $e->getMessage(),
            ), 500);
        }
    }

    public function put(Request $request, $id)
    {
        $request->validate($this->required_fields);

        $currentUser = Auth::user();
        $result = $this->findWithCheckPermissions($id);
        if ($result['success'] === true) {
            $entity = $result['data'];
            $entity->name = $request->name;
            $entity->code = $request->code;
            $entity->address =$request->address;
            $entity->phone = $request->phone !== null ? $request->phone : '';
            $entity->save();

            return response()->json(array(
                'data' => $entity,
                'error' => null,
            ), 200);
        }
        return $this->handleEntryFindResponse($result);

    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function get($id)
    {
        $entity = Entity::find($id);
        return response()->json(array(
            'data' => $entity,
            'error' => $entity ? null : 'Not available',
        ), $entity ? 200 : 404);
    }

    public function delete($id)
    {
        $result = $this->findWithCheckPermissions($id);
        if ($result['success'] === true) {
            $entry = $result['data'];
            $entry->delete();
            return response(null, 204);
        }
        return $this->handleEntityFindResponse($result);
    }

    public function batchDelete(Request $request)
    {

        $request->validate(['ids' => 'required|array|min:2|max:25']);
        $deleteList = array();
        foreach ($request->ids as $id) {
            $result = $this->findWithCheckPermissions($id);
            if ($result['success'] !== true) {
                return $this->handleEntityFindResponse($result, "You do not have permission to delete resource with id: " . $id);
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
        $entry = Entity::find($id);
        if ($currentUser->category === 'admin') {
            return ['success' => $entry !== null ? true : false, 'data' => $entry, 'status' => $entry !== null ? 'ok' : 'not_found'];
        } else {
            return ['success' => false, 'data' => null, 'status' => 'no_permission'];
        }
        return ['success' => false, 'data' => null, 'status' => 'not_found'];
    }

    private function handleEntityFindResponse($result, $noPermissionMsg = "You have no permission to modify this resource")
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
