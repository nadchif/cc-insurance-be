<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EntryController extends Controller
{

    public function __construct()
    {
        $this->required_fields = [
            'date_insured' => 'required|date|date_format:Y-m-d',
            'entity' => 'required|integer',
            'erf' => 'nullable|string',
            'address' => 'string',
            'type' => 'required|in:allrisk,bldg,contents,bldg\/cont',
            'description' => 'required|string|min:3',
            'serial' => 'nullable|string',
            'building_value' => 'nullable|numeric|min:0',
            'contents_value' => 'required_without:building_value|nullable|numeric|min:0',
        ];
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $currentUser = Auth::user();
        $envelopes = array();
        $entries = '';
        if ($currentUser->category === 'admin') {
            $entries = DB::table('entries')->join('entities', 'entities.id', '=', 'entries.entity')
                ->select([
                    'entries.*',
                    'entities.name as entity_name',
                ])->orderBy('date_insured', 'desc')->get()->toArray();
        } else {
            $entries = DB::table('entries')->join('entities', 'entities.id', '=', 'entries.entity')
                ->select([
                    'entries.*',
                    'entities.name as entity_name',
                ])->orderBy('date_insured', 'desc')->where('entries.entity', $currentUser->entity)->get()->toArray();
        }

        $result = array_map(function ($entry) {
            $entry->premium = $this->calcPremium($entry->building_value + $entry->contents_value);
            return $entry;
        }, $entries);

        return response()->json(array(
            'data' => $result,
            'error' => null,
        ), 200);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function get($id)
    {
        $currentUser = Auth::user();
        $entry = Entry::find($id);
        if ($currentUser->category === 'admin') {
            return response()->json(array(
                'data' => $entry,
                'error' => $entry ? null : 'This resource is not available',
            ), $entry ? 200 : 404);
        } else {
            if ($entry && $entry->entity == $currentUser->entity) {
                return response()->json(array(
                    'data' => $entry,
                    'error' => $entry ? null : 'This resource is not available',
                ), $entry ? 200 : 404);
            } else {
                return response()->json(array(
                    'data' => null,
                    'error' => 'This resource is not available to you',
                ), 403);
            }
        }
    }

    public function put(Request $request, $id)
    {
        $request->validate($this->required_fields);

        $currentUser = Auth::user();
        $result = $this->findWithCheckPermissions($id);
        if ($result['success'] === true) {
            $entry = $result['data'];
            $entry->date_insured = $request->date_insured;
            $entry->entity = $request->entity;
            if ($request->erf) {
                $entry->erf = $request->erf;
            }
            $entry->address = $request->address;
            $entry->type = $request->type;
            $entry->description = $request->description;
            if ($request->serial) {
                $entry->serial = $request->serial;
            }
            $entry->contents_value = $request->contents_value != null ? $request->contents_value : 0;
            $entry->building_value = $request->building_value != null ? $request->building_value : 0;
            $entry->save();

            return response()->json(array(
                'data' => $entry,
                'error' => null,
            ), 200);
        }
        return $this->handleEntryFindResponse($result);

    }
    public function post(Request $request)
    {

        $request->validate($this->required_fields);

        $currentUser = Auth::user();

        if ($currentUser->category === 'admin' || $currentUser->entity == $request->entity) {

            try {
                $entry = Entry::create([
                    'date_insured' => $request->date_insured,
                    'entity' => $request->entity,
                    'erf' => $request->erf,
                    'address' => $request->address,
                    'type' => $request->type,
                    'description' => $request->description,
                    'serial' => $request->serial != null ? $request->serial : '',
                    'contents_value' => $request->contents_value != null ? $request->contents_value : 0,
                    'building_value' => $request->building_value != null ? $request->building_value : 0,
                ]);
                return response()->json(array(
                    'data' => $entry,
                    'error' => null,
                ), 201);
            } catch (\Exception $e) {
                return response()->json(array(
                    'data' => false,
                    'error' => $e->getMessage(),
                ), 500);
            }
        }
        return response()->json(array(
            'data' => false,
            'error' => "Admin permissons required to post for other entities",
        ), 403);

    }

    public function delete($id)
    {
        $result = $this->findWithCheckPermissions($id);
        if ($result['success'] === true) {
            $entry = $result['data'];
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

    private function calcPremium($value)
    {
        $premium = $value * 0.0019;
        return number_format($premium, 2, '.', '');
    }

    private function findWithCheckPermissions($id)
    {
        $currentUser = Auth::user();
        $entry = Entry::find($id);
        if ($currentUser->category === 'admin') {
            return ['success' => true, 'data' => $entry];
        } else {
            if ($entry && $entry->entity == $currentUser->entity) {
                return ['success' => true, 'data' => $entry];
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
