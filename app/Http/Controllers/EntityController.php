<?php
namespace App\Http\Controllers;

use App\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EntityController extends Controller
{

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
            'error' => null
        ), 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:2',
            'address' => 'required|string',
            'phone' => 'required|string',
            'code' => 'required|string'
        ]);

        try {
            $entity = Entity::create([
                'name' => $request->name,
                'code' => $request->code
            ]);
            DB::commit();
            return response()->json(array(
                'data' => $entity,
                'error' => null
            ), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(array(
                'data' => false,
                'error' => $e->getMessage()
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
        $entity = Entity::find($id);
        return response()->json(array(
            'data' => $entity,
            'error' => $entity ? null : 'Not available'
        ), $entity ? 200 : 404);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}