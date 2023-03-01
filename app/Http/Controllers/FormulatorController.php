<?php

namespace App\Http\Controllers;

use App\Entity\Comment;
use App\Entity\Formulator;
use App\Entity\FormulatorTeamMember;
use App\Entity\Lsp;
use App\Http\Resources\FormulatorResource;
use App\Laravue\Acl;
use App\Laravue\Models\Role;
use App\Laravue\Models\User;
use App\Notifications\ChangeUserEmailNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class FormulatorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->uklUpl) {
            return Formulator::where(function($query) use($request) {
                $query->where(function($q) use($request) {
                    $q->whereRaw("LOWER(name) LIKE '%" . strtolower($request->search) . "%'");
                });
            })->whereHas('user')->orderBy('name')->limit(20)->get();
        }

        if($request->byUserId) {
            $formulator = Formulator::where('email', $request->email)->first();
            return $formulator;
        }

        if($request->avatar) {
            $user = User::where('email', $request->email)->first();
            if($user) {
                return $user->avatar;
            } else {
                return 'null';
            }
        }

        return FormulatorResource::collection(
           $formulator =  Formulator::with('user')->where(function ($query) use ($request) {
                if ($request->active && ($request->active == 'aktif')) {
                    $query->where([['date_start', '<=', date('Y-m-d H:i:s')], ['date_end', '>=', date('Y-m-d H:i:s')]]);
                } else if($request->active && ($request->active === 'nonaktif')) {
                    $query->where('date_start', '>', date('Y-m-d H:i:s')) 
                        ->orWhere('date_end', '<', date('Y-m-d H:i:s'))
                        ->orWhere('date_end', null);
                } else if($request->active && ($request->active === 'bersertifikat')) {
                    $query->where('membership_status', 'KTPA')
                        ->orWhere('membership_status', 'ATPA');
                } else if($request->active && ($request->active === 'tidakBersertifikat')) {
                        $query->where('membership_status', 'TA');
                } 
            })
            ->where(function($query) use($request) {
                if($request->search) {
                    $query->where(function($q) use($request) {
                        $q->whereRaw("LOWER(name) LIKE '%" . strtolower($request->search) . "%'");
                    })->orWhere(function($q) use($request) {
                        $q->whereRaw("LOWER(reg_no) LIKE '%" . strtolower($request->search) . "%'");
                    })->orWhere(function($q) use($request) {
                        $q->whereRaw("LOWER(cert_no) LIKE '%" . strtolower($request->search) . "%'");
                    })->orWhere(function($q) use($request) {
                        $q->whereRaw("LOWER(membership_status) LIKE '%" . strtolower($request->search) . "%'");
                    });
                }
            })
            ->where(function ($query) use ($request) {
                $findIdLsp = Lsp::where('email',$request->email)->first();
                return $findIdLsp ? $query
                    ->where('membership_status', '=', 'TA')
                    ->orWhere('id_lsp', $findIdLsp->id) : '';
            })
            ->where(function ($query) use ($request) {
                $findStatus = $request->membershipStatus;
                return $findStatus ? $query->where('membership_status', $findStatus) : '';
            })
            ->where(function ($query) use ($request) {
                $findLsp = $request->lspFilter;
                return $findLsp ? $query->where('id_lsp', $findLsp) : '';
            })
            ->with(['dataLsp' => function ($query){
                return $query->select(['id', 'lsp_name']);
            }])
            ->orderBy('created_at', 'DESC')->paginate($request->limit)
            ->appends(['active' => $request->active])
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
        if($request->registration) {
            return $this->registration($request);
        }

        if($request->sertifikasi) {
            // CHECK EXIST NO REGISTRATION
            $error = [];
            $check_reg_no = Formulator::where([['id', '!=', $request->id],['reg_no', $request->reg_no]])->count();
            if($check_reg_no > 0) {
                $error['error_reg_no'] = true;
            }

            // CHECK EXIST NO SERTIFIKAT
            $check_cert_no = Formulator::where([['id', '!=', $request->id],['cert_no', $request->cert_no]])->count();
            if($check_cert_no > 0) {
                $error['error_cert_no'] = true;
            }

            if(count($error) > 0) {
                return response()->json($error);
            }

            $formulator = Formulator::findOrFail($request->id);
            if($this->checkStringNull($request->email)) {
                if(strtolower($request->email) != $formulator->email) {
                    $check_user = User::where('email', strtolower($request->email))->first();
                    $check_formulator = Formulator::where('email', strtolower($request->email))->first();
                    if($check_user || $check_formulator) {
                        $error['error_exist_email'] = true;
                        return response()->json($error);
                    }
                }
            }

            $old_email = $formulator->email;

            if ($request->hasFile('file_sertifikat')) {
                //create file sertifikat
                $fileSertifikat = $request->file('file_sertifikat');
                $fileSertifikatName = 'penyusun/' . uniqid() . '.' . $fileSertifikat->extension();
                $fileSertifikat->storePubliclyAs('public', $fileSertifikatName);
                $formulator->cert_file = $fileSertifikatName;
            }

            $formulator->name = $request->name;
            $formulator->nik = $this->checkStringNull($request->nik);
            $formulator->address = $this->checkStringNull($request->address);
            $formulator->province = $this->checkStringNull($request->province);
            $formulator->district = $this->checkStringNull($request->district);
            $formulator->phone = $this->checkStringNull($request->phone);

            if($this->checkStringNull($request->email)) {
                $formulator->email = strtolower($request->email);
            }

            $formulator->reg_no = $request->reg_no;
            $formulator->id_lsp = $request->id_lsp;
            $formulator->cert_no = $request->cert_no;
            $formulator->membership_status = $request->membership_status;
            $formulator->date_start = $request->date_start;
            $formulator->date_end =  Carbon::createFromDate($request->date_start)->addYears(3);
            $formulator->expertise = $this->checkStringNull($request->expertise);
            $formulator->save();

            $email_notification = false;
            $user = null;
            if($old_email) {
                $user = User::where('email', $old_email)->first();
                if($user) {
                    $user->name = $this->checkStringNull($request->name);
                    if($request->hasFile('avatarFile')) {
                        $fileAvatar = $request->file('avatarFile');
                        $fileAvatarName = 'avatar/' . uniqid() . '.' . $fileAvatar->extension();
                        $fileAvatar->storePubliclyAs('public', $fileAvatarName);
                        $user->avatar = $fileAvatarName;
                    }
                    if($this->checkStringNull($request->email)) {
                        if(strtolower($request->email) != $old_email) {
                            $user->email = strtolower($request->email);
                            $email_notification = true;
                        }
                    }
                    $user->save();
                }
            }

            if($email_notification) {
                Notification::send([$user], new ChangeUserEmailNotification());
                Notification::route('mail', $old_email)->notify(new ChangeUserEmailNotification($user->name, $user->email, $user->roles->first()->name));
            }

            return response()->json(['message' => 'success']);

        }

        //validate request
        $validator = Validator::make(
            $request->all(),
            [
                'name'              => 'required',
                'expertise'         => 'required',
                // 'cert_no'           => 'required',
                // 'date_start'        => 'required',
                // 'date_end'          => 'required',
                // 'reg_no'            => 'required',
                // 'id_institution'    => 'required',
                // 'membership_status' => 'required',
                // 'id_lsp'            => 'required',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 403);
        } else {
            $email = $this->checkStringNull($request->get('email')) ? strtolower($request->get('email')) : null;

            if($this->checkStringNull($request->email)) {
                $found = User::where('email', $email)->first();

                if($found) {
                    return response()->json(['error' => 'Email yang anda masukkan sudah terpakai']);
                }
            }

            if($this->checkStringNull($request->reg_no)) {
                $found_reg_no = Formulator::where('reg_no', $request->reg_no)->first();
                if($found_reg_no) {
                    return response()->json(['error' => 'No Registrasi Sudah Terdaftar']);
                }
            }

            $params = $request->all();

            DB::beginTransaction();

            if ($request->file('cv_penyusun') !== null) {
                //create file cv
                $fileCv = $request->file('cv_penyusun');
                $cvName = 'penyusun/' . uniqid() . '.' . $fileCv->extension();
                $fileCv->storePubliclyAs('public', $cvName);
            }

            if ($request->file_sertifikat && ($request->file('file_sertifikat') !== null)) {
                //create file sertifikat
                $fileSertifikat = $request->file('file_sertifikat');
                $fileSertifikatName = 'penyusun/' . uniqid() . '.' . $fileSertifikat->extension();
                $fileSertifikat->storePubliclyAs('public', $fileSertifikatName);
            }

            if ($this->checkStringNull($request->email)) {
                $formulatorRole = Role::findByName(Acl::ROLE_FORMULATOR);
                $random_password = Str::random(8);
                $user = User::create([
                    'name' => ucfirst($params['name']),
                    'email' => strtolower($params['email']),
                    'password' => isset($params['password']) ? Hash::make($params['password']) : Hash::make($random_password),
                    'original_password' => isset($params['password']) ? $params['password'] : $random_password
                ]);
                $user->syncRoles($formulatorRole);
            }

            $date_start = null;
            $membership_status = null;

            if(isset($params['date_start'])) {
                if($params['date_start']) {
                    $date_start = $params['date_start'];
                }
            }

            if(isset($params['membership_status'])) {
                if($params['membership_status']) {
                    $membership_status = $params['membership_status'];
                }
            }

            //create Penyusun
            $formulator = Formulator::create([
                'name'              => $params['name'],
                'expertise'         => isset($params['expertise']) ? $params['expertise'] : null,
                'cert_no'           => isset($params['cert_no'])  ? $params['cert_no'] : null,
                'date_start'        => $date_start,
                'date_end'          => $request->date_start ? Carbon::createFromDate($request->date_start)->addYears(3) : null,
                'cert_file'         => isset($fileSertifikatName) ? $fileSertifikatName : null,
                'cv_file'           => isset($cvName) ? $cvName : null,
                'reg_no'            => isset($params['reg_no']) ? $params['reg_no'] : null,
                'id_institution'    => isset($params['id_institution']) ? $params['id_institution'] : null,
                'membership_status' => $membership_status ? $membership_status : 'TA',
                'id_lsp'            => isset($params['id_lsp']) ? $params['id_lsp'] : null,
                'nik'               => isset($params['nik']) ? $params['nik'] : null,
                'district'          => isset($params['district']) ? $params['district'] : null,
                'province'          => isset($params['province']) ? $params['province'] : null,
                'phone'             => isset($params['phone']) ? $params['phone'] : null,
                'address'           => isset($params['address']) ? $params['address'] : null,
                'email'             => $request->email ? strtolower($params['email']) : $request->email,
            ]);

            if (!$formulator) {
                DB::rollback();
            } else {
                DB::commit();
            }

            return new FormulatorResource($formulator);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Entity\Formulator  $formulator
     * @return \Illuminate\Http\Response
     */
    public function show(Formulator $formulator)
    {
        $province = null;
        $district = null;

        if($formulator->formulatorProvince) {
            $province = $formulator->formulatorProvince->name;
        }

        if($formulator->formulatorDistrict) {
            $district = $formulator->formulatorDistrict->name;
        }

        $formulator->setAttribute('formulator_province', $province);
        $formulator->setAttribute('formulator_district', $district);
        return $formulator;
    }

    public function showByEmail(Request $request)
    {
        if ($request->email) {
            $formulator = Formulator::where('email', $request->email)->first();

            if ($formulator) {
                return $formulator;
            } else {
                return response()->json(null, 200);
            }
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Entity\Formulator  $formulator
     * @return \Illuminate\Http\Response
     */
    public function edit(Formulator $formulator)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Entity\Formulator  $formulator
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Formulator $formulator)
    {
        if ($formulator === null) {
            return response()->json(['error' => 'formulator not found'], 404);
        }

        if($request->profile) {
            if($request->cv_file) {
                $file = $this->base64ToFile($request->cv_file);
                $file_name = 'penyusun/' . uniqid() . '.' . $file['extension'];
                Storage::disk('public')->put($file_name, $file['file']);

                if($formulator->cv_file) {
                    Storage::disk('public')->delete($formulator->rawCvFile());
                }

                $formulator->cv_file = $file_name;
            }

            if($request->cert_file) {
                $file = $this->base64ToFile($request->cert_file);
                $file_name = 'penyusun/' . uniqid() . '.' . $file['extension'];
                Storage::disk('public')->put($file_name, $file['file']);

                if($formulator->cert_file) {
                    Storage::disk('public')->delete($formulator->rawCertFile());
                }

                $formulator->cert_file = $file_name;
            }

            $formulator->expertise = $request->expertise;
            $formulator->save();
            return response()->json(['cv_file' => $formulator->cv_file, 'cert_file' => $formulator->cert_file]);
        }

        // $validator = Validator::make(
        //     $request->all(),
        //     [
        //         'name'              => 'required',
        //         'expertise'         => 'required',
        //         'cert_no'           => 'required',
        //         'date_start'        => 'required',
        //         'date_end'          => 'required',
        //         'reg_no'            => 'required',
        //         'id_institution'    => 'required',
        //         'membership_status' => 'required',
        //         'id_lsp'            => 'required',
        //         'email'             => 'required',
        //     ]
        // );

        // if ($validator->fails()) {
        //     return response()->json(['errors' => $validator->errors()], 403);
        // } else {
        $params = $request->all();
        $email_changed_notif = null;
        $old_email = null;
        $password = Str::random(8);


        if($request->email) {
            if(strtolower($request->email) != $formulator->email) {
                $found = User::where('email', strtolower($request->email))->first();
                if($found) {
                    $old_email = $found->email;
                    $found->password = Hash::make($password);
                    $found->email = strtolower($request->email);
                    $found->save();
                    $email_changed_notif = $found;
                } else {
                    if($formulator->email) {
                        return response()->json(['error' => 'Email yang anda masukkan sudah terpakai']);
                    } else {
                        $formulatorRole = Role::findByName(Acl::ROLE_FORMULATOR);
                        $random_password = Str::random(8);
                        $user = User::create([
                            'name' => ucfirst($params['name']),
                            'email' => strtolower($params['email']),
                            'password' => isset($params['password']) ? Hash::make($params['password']) : Hash::make('amdalnet'),
                            'original_password' => isset($params['password']) ? $params['password'] : $random_password
                        ]);
                        $user->syncRoles($formulatorRole);
                    }
                }
            }
        }

        if($request->reg_no) {
            if($request->reg_no != $formulator->reg_no) {
                $found_reg_no = Formulator::where('reg_no', $request->reg_no)->first();
                if($found_reg_no) {
                    return response()->json(['error' => 'No Registrasi Sudah Terdaftar']);
                }
            }
        }

        if ($request->file('cv_penyusun') !== null) {
            //create file cv
            $fileCv = $request->file('cv_penyusun');
            $cvName = 'penyusun/' . uniqid() . '.' . $fileCv->extension();
            $fileCv->storePubliclyAs('public', $cvName);
            $formulator->cv_file = $cvName;
        }

        if ($request->file('file_sertifikat') !== null) {
            //create file sertifikat
            $fileSertifikat = $request->file('file_sertifikat');
            $fileSertifikatName = 'penyusun/' . uniqid() . '.' . $fileSertifikat->extension();
            $fileSertifikat->storePubliclyAs('public', $fileSertifikatName);
            $formulator->cert_file = $fileSertifikatName;
        }

        $formulator->name = $params['name'];
        $formulator->expertise = isset($params['expertise']) ? $params['expertise'] : null;
        $formulator->cert_no = $params['cert_no'];
        $formulator->date_start = $params['date_start'];
        $formulator->date_end = $params['date_start'] ? Carbon::createFromDate($params['date_start'])->addYears(3) : null;
        $formulator->reg_no = $params['reg_no'];
        $formulator->id_institution = $params['id_institution'];
        $formulator->membership_status = $params['membership_status'];
        $formulator->id_lsp = $params['id_lsp'];
        $formulator->email = $request->email ? strtolower($params['email']) : $request->email;
        $formulator->nip = $params['nip'] ? $params['nip'] : null;
        $formulator->save();
        // }

         // send notification if existing user email changed
         if($email_changed_notif) {
            Notification::send([$email_changed_notif], new ChangeUserEmailNotification(null,null,null,$password));
            Notification::route('mail', $old_email)->notify(new ChangeUserEmailNotification($email_changed_notif->name, $email_changed_notif->email, $email_changed_notif->roles->first()->name));
        }

        return new FormulatorResource($formulator);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Entity\Formulator  $formulator
     * @return \Illuminate\Http\Response
     */
    public function destroy(Formulator $formulator)
    {
        DB::beginTransaction();
        $error = false;

        try {
            // Delete user data
            $user = User::where('email', $formulator->email)->first();
            if($user) {
                // 1. delete comments
                Comment::whereHas('replied', function($q) use($user) {
                    $q->where('id_user', $user->id);
                })->delete();
                Comment::where('id_user', $user->id)->delete();
                // 2. delete user
                User::destroy($user->id);
            }

            // Delete formulator data
            // 1. delete formulator as team member
            FormulatorTeamMember::where('id_formulator', $formulator->id)->delete();
            // 2. delete formulator
            Formulator::destroy($formulator->id);
        } catch(Exception $e) {
            $error = true;
        }

        if($error) {
            DB::rollBack();
            return response()->json(['message' => 'failed']);
        }

        DB::commit();
        return response()->json(['message' => 'success']);
    }

    private function registration(Request $request)
    {
        $formulator = null;
        $certificate_file = null;

        if($request->isCertified === 'true') {
            // check if no registration is exist
            $formulator = Formulator::where('reg_no', $request->reg_no)->first();
            if($formulator) {
                $certificate_file = $formulator->cert_file;
                $user = $this->checkUser($request->email, $formulator->email, $request->reg_no);
                if($user !== null) {
                    return $user;
                }
            } else {
                return response(['error' => 'reg_no']);
            }
        } else {
            $user = $this->checkUser($request->email);
            if($user !== null) {
                return $user;
            }

            $formulator = new Formulator();
            $formulator->membership_status = 'TA';
            $formulator->expertise = $request->expertise;
        }

        if ($request->file('file_sertifikat') !== null) {
            //create file sertifikat
            if(!$certificate_file) {
                $fileSertifikat = $request->file('file_sertifikat');
                $fileSertifikatName = 'penyusun/' . uniqid() . '.' . $fileSertifikat->extension();
                $fileSertifikat->storePubliclyAs('public', $fileSertifikatName);
                $formulator->cert_file = $fileSertifikatName;
            }
        }

        $formulator->name = $request->name;
        $formulator->nik = $request->nik;
        $formulator->address = $request->address;
        $formulator->province = $request->province;
        $formulator->district = $request->district;
        $formulator->email = $request->email ? strtolower($request->email) : $request->email;
        $formulator->phone = $request->phone;
        $formulator->save();

        $formulatorRole = Role::findByName(Acl::ROLE_FORMULATOR);
        $password = Str::random(8);
        $user_data = [
            'name' => $request->name,
            'email' => $request->email ? strtolower($request->email) : $request->email,
            'password' => $request->password ? Hash::make($request->password) : $password
        ];

        if(!$request->password) {
            $user_data['original_password'] = $password;
        }

        $user = User::create($user_data);
        $user->syncRoles($formulatorRole);

        if($certificate_file) {
            return response()->json(['message' => 'success_certificate']);
        }

        return response()->json(['message' => 'success']);
    }

    public function getFormulatorName()
    {
        $getData = DB::table('formulators')
            ->select('formulators.id', 'formulators.name')
            ->get();

        return response()->json($getData);
    }

    private function base64ToFile($file_64)
    {
        $extension = explode('/', explode(':', substr($file_64, 0, strpos($file_64, ';')))[1])[1];   // .jpg .png .pdf

        $replace = substr($file_64, 0, strpos($file_64, ',')+1);

        // find substring fro replace here eg: data:image/png;base64,

        $file = str_replace($replace, '', $file_64);

        $file = str_replace(' ', '+', $file);

        return [
            'extension' => $extension,
            'file' => base64_decode($file)
        ];
    }

    private function checkUser($email_user, $email_formulator = null, $no_reg = null)
    {
        if($email_formulator && $no_reg) {
            $user = User::where([['email', $email_formulator],['active', 1]])->first();
            if($user) {
                return response()->json(['error' => 'Penyusun dengan nomor registrasi ' . $no_reg . ' sudah terdaftar']);
            }
        }

        $user = User::where('email', strtolower($email_user))->first();
        if($user) {
            return response()->json(['error' => 'Email yang anda masukkan sudah terpakai']);
        }

        return null;
    }

    private function checkStringNull($val)
    {
        if($val) {
            if($val !== 'null') {
                return $val;
            }
        }

        return null;
    }
}
