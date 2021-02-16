<?php

namespace App\Http\Controllers;

use App\Models\Record;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RecordController extends Controller
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
        $records = '';
        if ($currentUser->category === 'admin') {
            $records = DB::table('records')->join('entities', 'entities.id', '=', 'records.entity')
                ->select([
                    'records.*',
                    'entities.name as entity_name',
                ])->orderBy('date_insured', 'desc')->get()->toArray();
        } else {
            $records = DB::table('records')->join('entities', 'entities.id', '=', 'records.entity')
                ->select([
                    'records.*',
                    'entities.name as entity_name',
                ])->orderBy('date_insured', 'desc')->where('records.entity', $currentUser->entity)->get()->toArray();
        }

        $result = array_map(function ($record) {
            $record->premium = $this->calcPremium($record->building_value + $record->contents_value);
            return $record;
        }, $records);

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
        $record = Record::find($id);
        if ($currentUser->category === 'admin') {
            return response()->json(array(
                'data' => $record,
                'error' => $record ? null : 'This resource is not available',
            ), $record ? 200 : 404);
        } else {
            if ($record && $record->entity == $currentUser->entity) {
                return response()->json(array(
                    'data' => $record,
                    'error' => $record ? null : 'This resource is not available',
                ), $record ? 200 : 404);
            } else {
                return response()->json(array(
                    'data' => null,
                    'error' => 'This resource is not available to you',
                ), 403);
            }
        }
    }

    public function getDuplicates()
    {
        $currentUser = Auth::user();
        $envelopes = array();
        $records = [];
        if ($currentUser->category === 'admin') {

            $records = DB::table('records')->join('entities', 'entities.id', '=', 'records.entity')

                ->select([
                    'records.*',
                    'entities.name as entity_name',
                ])
                ->orderBy('date_insured', 'desc')
                ->get()->toArray();

        }
        $identicals = $this->getIdenticals($records);

        $similars = $this->getSimilars($records, 75);

        return response()->json(array(
            'data' => ['similar' => $similars, 'identical' => $identicals],
            'error' => null,
        ), 200);
    }

    public function put(Request $request, $id)
    {
        $request->validate($this->required_fields);

        $currentUser = Auth::user();
        $result = $this->findWithCheckPermissions($id);
        if ($result['success'] === true) {
            $record = $result['data'];
            $record->date_insured = $request->date_insured;
            $record->entity = $request->entity;
            if ($request->erf) {
                $record->erf = $request->erf;
            }
            $record->address = $request->address;
            $record->type = $request->type;
            $record->description = $request->description;
            if ($request->serial) {
                $record->serial = $request->serial;
            }
            $record->contents_value = $request->contents_value != null ? $request->contents_value : 0;
            $record->building_value = $request->building_value != null ? $request->building_value : 0;
            $record->save();

            return response()->json(array(
                'data' => $record,
                'error' => null,
            ), 200);
        }
        return $this->handleRecordFindResponse($result);

    }
    public function post(Request $request)
    {

        $request->validate($this->required_fields);

        $currentUser = Auth::user();

        if ($currentUser->category === 'admin' || $currentUser->entity == $request->entity) {

            try {
                $record = Record::create([
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
                    'data' => $record,
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
            $record = $result['data'];
            if ($record !== null) {
                $record->delete();
                return response(null, 204);
            }

            return response(null, 404);
        }
        return $this->handleRecordFindResponse($result);
    }

    public function batchDelete(Request $request)
    {

        $request->validate(['ids' => 'required|array|min:1|max:25']);
        $deleteList = array();
        foreach ($request->ids as $id) {
            $result = $this->findWithCheckPermissions($id);
            if ($result['success'] !== true) {
                return $this->handleRecordFindResponse($result, "You do not have permission to delete resource with id: " . $id);
            }
            $deleteList[] = $result['data'];
        }
        foreach ($deleteList as $record) {
            $record->delete();
        }
        response(null, 204);
    }

    private function calcPremium($value)
    {
        $premium = $value * 0.0019;
        return number_format($premium, 2, '.', '');
    }
    private function getKeywordSet($record)
    {
        $to_check_keys = ['date_insured', 'type', 'address', 'building_value', 'contents_value', 'serial', 'description', 'erf', 'entity'];
        $similarity_signature = [];
        foreach ($to_check_keys as $key) {

            $similarity_signature[] = $key . ':' . $record->$key;

        }
        return $similarity_signature;
    }
    private function getSimilars($records, $similarity_threshold)
    {
        // 1. convert all records to arrays with key data
        $keyword_collection = [];
        foreach ($records as $record) {
            $keyword_collection[] = $this->getKeywordSet($record);
        }
        // cross match each record against the arrays
        $matching_data = [];
        $already_matched = [];
        foreach ($records as $key => $value) {
            $record = $records[$key];
            $matches = [];
            foreach ($keyword_collection as $subkey => $subvalue) {
                $keywords = $keyword_collection[$subkey];
                $similar = array_intersect($keyword_collection[$key], $keywords);
                $percent = count($similar) / count($keywords);
                if ($percent > 0.85 && ($subkey != $key)) {
                    $match = $records[$subkey];
                    $match->p = round($percent, 2);
                    $matches[] = $match;
                }

            }
            if (count($matches) > 0) {
                if (!in_array($record->id, $already_matched)) {
                    $already_matched[] = $record->id;
                    foreach ($matches as $match) {
                        $already_matched[] = $match->id;
                    }
                    $matches[] = $record;
                    $matching_data[] = $matches;
                }

            }
        }
        return $matching_data;
    }
    private function getIdenticals($records)
    {
        $base = [];
        $to_check_keys = ['date_insured', 'type', 'address', 'building_value', 'contents_value', 'serial', 'description', 'erf', 'entity'];
        foreach ($records as $record) {
            $similarity_signature = '';
            foreach ($to_check_keys as $key) {
                $similarity_signature .= "--" . $record->$key . "--";
            }
            $base[$similarity_signature][] = $record;
        }
        $result = (array_filter($base, function ($cluster) {
            return count($cluster) > 1;
        }));
        sort($result);
        return $result;
    }
    private function findWithCheckPermissions($id)
    {
        $currentUser = Auth::user();
        $record = Record::find($id);
        if ($currentUser->category === 'admin') {
            return ['success' => $record !== null ? true : false, 'data' => $record, 'status' => $record !== null ? 'ok' : 'not_found'];
        } else {
            if ($record && $record->entity == $currentUser->entity) {
                return ['success' => true, 'data' => $record];
            } else {
                return ['success' => false, 'data' => null, 'status' => 'no_permission'];
            }
        }
        return ['success' => false, 'data' => null, 'status' => 'not_found'];
    }

    private function handleRecordFindResponse($result, $noPermissionMsg = "You have no permission to modify this resource")
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
