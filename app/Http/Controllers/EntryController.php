<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use Illuminate\Http\Request;

class EntryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $entries = Entry::all();

        return response()->json(array(
            'data' => $entries,
            'errors' => null
        ), 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'date_insured'=> 'required|date|date_format:Y-m-d',
            'entity'=> 'required|integer',
            'erf'=> 'string',
            'address'=> 'string',
            'type'=> 'required|in:allrisk,bldg,contents,bldg\/cont',
            'description'=> 'required|string|min:3',
            'serial'=> 'string',
            'fnCT'=> 'string',
            'value1617'=> 'numeric|min:0',
            'value1718'=> 'numeric|min:0',
            'value_current'=> 'required|numeric|min:0',
            'account'=> 'string',
        ]);

        try {
            $entry = Entry::create([
            ]);
            DB::commit();
            return response()->json(array(
                'data' => $entry,
                'errors' => null
            ), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(array(
                'data' => false,
                'errors' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function get($id)
    {
        $entry = Entry::find($id);
        return response()->json(array(
            'data' => $entry,
            'errors' => $entry ? null : 'Not available'
        ), $entry ? 200 : 404);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Entry  $entry
     * @return \Illuminate\Http\Response
     */
    public function edit(Entry $entry)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Entry  $entry
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Entry $entry)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Entry  $entry
     * @return \Illuminate\Http\Response
     */
    public function destroy(Entry $entry)
    {
        //
    }
}
