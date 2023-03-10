<?php

namespace App\Http\Controllers;

use App\Entity\ProjectKegiatanLainSekitar;
use Illuminate\Http\Request;
use App\Entity\KegiatanLainSekitar;
use App\Entity\ImpactKegiatanLainSekitar;

class ProjectKegiatanLainSekitarController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //
        $params = $request->all();
        if(isset($params['inquire']) && ($params['inquire'])){
            return response([1], 200);
        }

        return response(
            KegiatanLainSekitar::from('kegiatan_lain_sekitars')
             ->select(
                 'kegiatan_lain_sekitars.*',
                 'kegiatan_lain_sekitars.name as value',
                 'project_kegiatan_lain_sekitars.description',
                 'project_kegiatan_lain_sekitars.measurement',
                 'project_kegiatan_lain_sekitars.province_id',
                 'provinces.name as province_name',
                 'project_kegiatan_lain_sekitars.district_id',
                 'districts.name as district_name',
                 'project_kegiatan_lain_sekitars.address',
                 'project_kegiatan_lain_sekitars.id as id_project_kegiatan_lain_sekitar',
                 'project_kegiatan_lain_sekitars.is_andal'
             )
             ->join('project_kegiatan_lain_sekitars', 'project_kegiatan_lain_sekitars.kegiatan_lain_sekitar_id', '=', 'kegiatan_lain_sekitars.id')
             ->leftJoin('provinces', 'provinces.id', '=', 'project_kegiatan_lain_sekitars.province_id')
             ->leftJoin('districts', 'districts.id', '=', 'project_kegiatan_lain_sekitars.district_id')
             ->where('project_kegiatan_lain_sekitars.is_andal', $request->mode)
             ->where('project_kegiatan_lain_sekitars.project_id', $request->id_project)->get()
        );
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
        //
        $params = $request->all();
        $kls = null;
        if($request->id){
            $kls = KegiatanLainSekitar::first('id', $request->id);
        } else {
            $kls = KegiatanLainSekitar::create([
                'name' => $request->name,
                'is_master' => $request->is_master,
                'originator_id' => $request->id_project
            ]);
        }
        if(isset($params['id_project_kegiatan_lain_sekitar']))
        {
            $pKLS = ProjectKegiatanLainSekitar::where('id', $request->id_project_kegiatan_lain_sekitar)->first();
        }else {
            $pKLS = new ProjectKegiatanLainSekitar();
            $pKLS->kegiatan_lain_sekitar_id =  $kls->id;
            $pKLS->project_id = $request->id_project;
            $pKLS->is_andal = $request->mode;
        }
        $pKLS->province_id = $request->province_id;
        $pKLS->district_id = $request->district_id;
        $pKLS->description = $request->description;
        $pKLS->measurement = $request->measurement;
        $pKLS->address = $request->address;
        $pKLS->save();

        return response($pKLS, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Entity\ProjectKegiatanLainSekitar  $projectKegiatanLainSekitar
     * @return \Illuminate\Http\Response
     */
    public function show(ProjectKegiatanLainSekitar $projectKegiatanLainSekitar)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Entity\ProjectKegiatanLainSekitar  $projectKegiatanLainSekitar
     * @return \Illuminate\Http\Response
     */
    public function edit(ProjectKegiatanLainSekitar $projectKegiatanLainSekitar)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Entity\ProjectKegiatanLainSekitar  $projectKegiatanLainSekitar
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ProjectKegiatanLainSekitar $projectKegiatanLainSekitar)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Entity\ProjectKegiatanLainSekitar  $projectKegiatanLainSekitar
     * @return \Illuminate\Http\Response
     */
    public function destroy(ProjectKegiatanLainSekitar $projectKegiatanLainSekitar)
    {
        //
        ImpactKegiatanLainSekitar::where('id_project_kegiatan_lain_sekitar', $projectKegiatanLainSekitar->id)->delete();
        $ids = ProjectKegiatanLainSekitar::where([
            'kegiatan_lain_sekitar_id' => $projectKegiatanLainSekitar->kegiatan_lain_sekitar_id,
            'is_andal' => $projectKegiatanLainSekitar->is_andal ? false : true,
            'project_id' => $projectKegiatanLainSekitar->project_id,
        ])->pluck('id');

        if(count($ids) === 0){
            KegiatanLainSekitar::where([
                'originator_id' => $projectKegiatanLainSekitar->project_id,
                'is_master' => false,
                'id' => $projectKegiatanLainSekitar->kegiatan_lain_sekitar_id,
            ])->delete();
        }
        return response($projectKegiatanLainSekitar->delete(), 200);
    }
}
