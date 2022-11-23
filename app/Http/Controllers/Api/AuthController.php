<?php
/**
 * File AuthController.php
 *
 * @author Tuan Duong <bacduong@gmail.com>
 * @package Laravue
 * @version 1.0
 */
namespace App\Http\Controllers\Api;

use App\Entity\Initiator;
use App\Entity\OssLicense;
use App\Http\Resources\UserResource;
use App\Laravue\Acl;
use App\Laravue\JsonResponse;
use App\Laravue\Models\Role;
use App\Laravue\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Password;

/**
 * Class AuthController
 *
 * @package App\Http\Controllers\Api
 */
class AuthController extends BaseController
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $credentials["active"]=1;
        // $credentials=>active = 1;

        // return $credentials;
        if (!Auth::attempt($credentials)) {
            return response()->json(new JsonResponse([], 'Maaf Email atau Password yang anda masukkan kurang tepat'), Response::HTTP_UNAUTHORIZED);
        }

        $user = $request->user();

        return response()->json(new JsonResponse(new UserResource($user)), Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        return response()->json((new JsonResponse())->success([]), Response::HTTP_OK);
    }

    public function validateToken(Request $request)
    {
        $validated = $request->only('token');
        $resp = $this->getValidateToken($validated['token']);
        return $resp->json();
    }

    public function updateToken(Request $request)
    {
        $validated = $request->only('refresh_token');
        $resp = $this->postUpdateToken($validated['refresh_token']);
        return $resp->json();
    }

    public function getUserInfo(Request $request)
    {
        $validated = $request->only('token');
        $resp = $this->getOssUserInfo($validated['token']);
        return $resp->json();
    }

    public function isEmailRegistered(Request $request)
    {
        $validated = $request->only('email');
        $user = User::where('email', $validated['email'])->first();
        if ($user) {
            return response()->json([
                'status' => 200,
                'code' => 200,
                'data' => true,
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'code' => 404,
                'data' => false,
            ], 200);
        }
    }

    public function loginOss(Request $request)
    {
        $validated = $request->only('email', 'token');
        $resp = $this->getValidateToken($validated['token']);
        $resp_json = $resp->json();
        $user = User::where('email', $validated['email'])->first();
        if ($user && $resp_json['session'] == 'VALID') {
            Auth::login($user);
            return response()->json([
                'status' => 200,
                'code' => 200,
                'data' => true,
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'code' => 404,
                'message' => 'User not found',
            ], 404);
        }
    }
    public function registerOss(Request $request)
    {
        $validated = $request->only('email', 'username', 'name',
            'password', 'pic', 'user_type', 'phone', 'address', 'nib');
        $initiatorRole = Role::findByName(Acl::ROLE_INITIATOR);
        $password = $validated['password'];
        if (empty($password)) {
            $password = Str::random(10);
        }
        DB::beginTransaction();

        $user = new User;
        $user->setRawPassword($password);
        $user->name = ucfirst($validated['name']);
        $user->email = $validated['email'];
        $user->oss_username = $validated['username'];
        $user->password = Hash::make($password);
        $user->active = 1;
        $user->save();

        $user->syncRoles($initiatorRole);
        $initiator = Initiator::create([
            'name'        =>  $validated['name'],
            'pic'         =>  $validated['pic'],
            'email'       =>  $validated['email'],
            'phone'       =>  $validated['phone'],
            'address'     =>  $validated['address'],
            'user_type'   =>  $validated['user_type'],
            'nib'   =>  $validated['nib'],
        ]);
        if ($user && $initiator) {
            DB::commit();
            return response()->json([
                'status' => 200,
                'code' => 200,
                // 'data' => $user,
            ], 200);
        } else {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'code' => 500,
                'message' => 'Failed to create User',
            ], 500);
        }
    }

    public function receiveToken(Request $request)
    {
        /*
         * Receive token and licenses from OSS
         * Store access-token & refresh_token to `users` table
         * Store kd_izin & id_izin to `oss_licenses` table
         */
        $validator = Validator::make(
            $request->all(),
            [
                'access-token' => 'required',
                'refresh_token' => 'required',
                'kd_izin' => 'required',
                'id_izin' => 'required',
            ]
        );
        if ($validator->fails()) {
            Log::error('Bad request.');
            Log::error('Request data = ' . json_encode($request->all()));
            return response()->json([
                'status' => 400,
                'message' => 'Request parameter tidak valid.',
            ], 400);
        }
        $validated = $request->only('access-token', 'refresh_token', 'kd_izin', 'id_izin');
        Log::debug('Validated data from OSS = ' . json_encode($validated));

        $resp = $this->getOssUserInfo($validated['access-token'])->json();
        // $user_info =  null;
        // if ($resp['status'] != 200) {
        //     // update token
        //     $respUpdate = $this->postUpdateToken($validated['refresh_token'])->json();
        //     if ($respUpdate['code'] == 200) {
        //         $validated['access-token'] = $respUpdate['data']['access_token'];
        //         $resp = $this->getOssUserInfo($respUpdate['data']['access_token'])->json();
        //         // $user_info = $resp['data'];
        //     } else {
        //         return response()->json([
        //             'status' => 500,
        //             'message' => 'Gagal update token',
        //         ], 500);
        //     }
        // }
        $user_info = $resp['data'];
        $user = User::where('email', $user_info['email'])->first();
        $initiator = Initiator::where('email', $user_info['email'])->first();
        DB::beginTransaction();
        $id_initiator = 0;
        if ($user && $initiator) {
            $user->access_token = $validated['access-token'];
            $user->refresh_token = $validated['refresh_token'];
            $user->save();
            $id_initiator = $initiator->id;
        }
        $created = OssLicense::create([
            'id_initiator' => $id_initiator,
            'email' => $user_info['email'],
            'id_izin' => $validated['id_izin'],
            'kd_izin' => $validated['kd_izin'],
        ]);
        if ($created) {
            Log::debug('Token berhasil diterima.');
            DB::commit();
            return response()->json([
                'status' => 200,
                'message' => 'Token berhasil diterima.',
            ], 200);
        } else {
            DB::rollBack();
        }
        Log::error('Gagal menyimpan token');
        return response()->json([
            'status' => 500,
            'message' => 'Gagal menyimpan token',
            // 'data' => $resp,
        ], 500);
    }

    private function setUserAsActive($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->active = 1;
            $user->save();
        }
        return $user;
    }

    private function getValidateToken($accessToken)
    {
        return Http::withHeaders([
            'user_key' => env('OSS_USER_KEY'),
        ])->withToken($accessToken)
        ->post(env('OSS_ENDPOINT_SSO') . '/users/validate-token');
    }

    private function postUpdateToken($refreshToken)
    {
        return Http::withHeaders([
            'user_key' => env('OSS_USER_KEY'),
        ])->withToken($refreshToken)
        ->post(env('OSS_ENDPOINT_SSO') . '/users/update-token');
    }

    private function getOssUserInfo($accessToken)
    {
        return Http::withHeaders([
            'user_key' => env('OSS_USER_KEY'),
        ])->withToken($accessToken)
        ->get(env('OSS_ENDPOINT_SSO') . '/users/userinfo-token');
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
                    ? response()->json([
                        'status' => 200,
                        'message' => 'Silahkan cek email anda untuk melanjutkan reset password.',
                    ], 200) : response()->json([
                        'status' => 500,
                        'errors' => ['email' => [__($status)]]
                    ], 500);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        // return json
        return $status === Password::PASSWORD_RESET ? response()->json([
            'status' => 200,
            'message' => 'Password berhasil diubah. Silahkan login.',
        ], 200) : response()->json([
            'status' => 500,
            'errors' => ['email' => [__($status)]]
        ], 500);
    }
}
