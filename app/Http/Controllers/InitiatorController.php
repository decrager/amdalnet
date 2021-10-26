<?php

namespace App\Http\Controllers;

use App\Entity\Initiator;
use App\Http\Resources\InitiatorResource;
use App\Laravue\Acl;
use App\Laravue\Models\Role;
use App\Laravue\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;
use Illuminate\Support\Facades\Hash;

class InitiatorController extends Controller
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
        //validate request
        $validator = Validator::make(
            $request->all(),
            [
                'name'      => 'required',
                'pic'       => 'required',
                'email'     => 'required',
                'phone'     => 'required',
                'address'   => 'required',
                'user_type' => 'required',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 403);
        } else {
            $params = $request->all();

            DB::beginTransaction();
            
            $initiatorRole = Role::findByName(Acl::ROLE_INITIATOR);

            $user = User::create([
                'name' => ucfirst($params['name']),
                'email' => $params['email'],
                'password' => Hash::make('amdalnet')
            ]);
            $user->syncRoles($initiatorRole);

            //create rona awal
            $initiator = Initiator::create([
                'name'      =>  $params['name'],
                'pic'       =>  $params['pic'],
                'email'     =>  $params['email'],
                'phone'     =>  $params['phone'],
                'address'   =>  $params['address'],
                'user_type' =>  $params['user_type'],
            ]);



            if (!$initiator) {
                DB::rollback();
            } else {
                DB::commit();
            }

            return new InitiatorResource($initiator);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Entity\Initiator  $initiator
     * @return \Illuminate\Http\Response
     */
    public function show(Initiator $initiator)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Entity\Initiator  $initiator
     * @return \Illuminate\Http\Response
     */
    public function edit(Initiator $initiator)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Entity\Initiator  $initiator
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Initiator $initiator)
    {
        if ($initiator === null) {
            return response()->json(['error' => 'rona awal not found'], 404);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'id_component_type'  => 'required',
                'name'               => 'required',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 403);
        } else {
            $params = $request->all();

            $initiator->id_component_type = $params['id_component_type'];
            $initiator->name = $params['name'];
            $initiator->save();
        }

        return new InitiatorResource($initiator);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Entity\Initiator  $initiator
     * @return \Illuminate\Http\Response
     */
    public function destroy(Initiator $initiator)
    {
        //
    }
}
