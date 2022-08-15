<?php
/**
 * File UserController.php
 *
 * @author Tuan Duong <bacduong@gmail.com>
 * @package Laravue
 * @version 1.0
 */

namespace App\Http\Controllers\Api;

use App\Entity\ExpertBank;
use App\Entity\Formulator;
use App\Entity\Initiator;
use App\Entity\Lpjp;
use App\Entity\LukMember;
use App\Entity\TukSecretaryMember;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\UserResource;
use App\Laravue\JsonResponse;
use App\Laravue\Models\Permission;
use App\Laravue\Models\Role;
use App\Laravue\Models\User;
use App\Notifications\ChangeUserEmailNotification;
use App\Notifications\UserRegistered;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Validator;

/**
 * Class UserController
 *
 * @package App\Http\Controllers\Api
 */
class UserController extends BaseController
{
    const ITEM_PER_PAGE = 15;

    /**
     * Display a listing of the user resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response|ResourceCollection
     */
    public function index(Request $request)
    {
        $searchParams = $request->all();
        $userQuery = User::query();
        $limit = Arr::get($searchParams, 'limit', static::ITEM_PER_PAGE);
        $role = Arr::get($searchParams, 'role', '');
        $keyword = Arr::get($searchParams, 'keyword', '');

        if (!empty($role)) {
            $userQuery->whereHas('roles', function($q) use ($role) { $q->where('name', $role); });
        }

        if (!empty($keyword)) {
            $userQuery->where('name', 'LIKE', '%' . $keyword . '%');
            $userQuery->orWhere('email', 'LIKE', '%' . $keyword . '%');
        }

        return UserResource::collection($userQuery->paginate($limit));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            array_merge(
                $this->getValidationRules(),
                [
                    'password' => ['required', 'min:6'],
                    'confirmPassword' => 'same:password',
                ]
            )
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 403);
        } else {
            $params = $request->all();
            $password = Str::random(8);
            $user = User::create([
                'name' => $params['name'],
                'email' => $params['email'],
                'password' => isset($params['password']) ? Hash::make($params['password']) : Hash::make($password),
                'original_password' => isset($params['password']) ? $params['password'] : $password
            ]);
            $role = Role::findByName($params['role']);
            $user->syncRoles($role);

            // $admins = User::all()->filter(function($user) {
            //     return $user->hasRole('admin');
            // });

            // Notification::send($admins, new UserRegistered($user));

            return new UserResource($user);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  User $user
     * @return UserResource|\Illuminate\Http\JsonResponse
     */
    public function show(User $user)
    {
        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param User    $user
     * @return UserResource|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, User $user)
    {
        if ($user === null) {
            return response()->json(['error' => 'User not found'], 404);
        }
        if ($user->isAdmin()) {
            return response()->json(['error' => 'Admin can not be modified'], 403);
        }

        $currentUser = Auth::user();
        if (!$currentUser->isAdmin()
            && $currentUser->id !== $user->id
            && !$currentUser->hasPermission(\App\Laravue\Acl::PERMISSION_MANAGE_USER)
        ) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        if($request->current && $request->new) {
            if(!Hash::check($request->current, $user->password)) {
                return response()->json(['error' => 'Password lama salah']); 
            }

            $user->password = Hash::make($request->new);
            $user->save();

            return response()->json(['message' => 'success']);
        }

        $validator = Validator::make($request->all(), $this->getValidationRules(false));
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 403);
        } else {
            $email = $request->get('email');
            $found = User::where('email', $email)->first();
            if ($found && $found->id !== $user->id) {
                return response()->json(['error' => 'Email has been taken'], 403);
            }

            //checking if this user is initiator
            $initiator = Initiator::where('email', $user->email)->first();
            if($initiator && $initiator->email !== $email){
                // update initiator with user email
                $initiator->email = $email;
                $initiator->save();
            }

            //checking if this user is formulator
            $formulator = Formulator::where('email', $user->email)->first();
            if($formulator && $formulator->email !== $email){
                // update formulator with user email
                $formulator->email = $email;
                $formulator->save();
            }

            // checking if this user is tuk member
            $tuk_member = LukMember::where('email', $user->email)->first();
            if($tuk_member && $tuk_member->email !== $email) {
                // update tuk member with user email
                $tuk_member->email = $email;
                $tuk_member->save();
            }

            // checking if this user is inside expert bank
            $expert_bank = ExpertBank::where('email', $user->email)->first();
            if($expert_bank && $expert_bank->email !== $email) {
                // update expert bank with user email
                $expert_bank->email = $email;
                $expert_bank->save();
            }

            // checking if this user is inside lpjp
            $lpjp = Lpjp::where('email', $user->email)->first();
            if($lpjp && $lpjp->email !== $email) {
                // update lpjp with user email
                $lpjp->email = $email;
                $lpjp->save();
            }

            // checking if this user is inside tuk secretary member
            $tuk_secretary_member = TukSecretaryMember::where('email', $user->email)->first();
            if($tuk_secretary_member && $tuk_secretary_member !== $email) {
                // update tuk secretary member with user email
                $tuk_secretary_member->email = $email;
                $tuk_secretary_member->save();
            }

            $old_email = $user->email;

            $user->name = $request->get('name');
            $user->email = $email;
            $user->save();

            // send notification if existing user email changed
            if($old_email != $email) {
                Notification::send([$user], new ChangeUserEmailNotification());
                Notification::route('mail', $old_email)->notify(new ChangeUserEmailNotification($user->name, $user->email, $user->roles->first()->name));
            }

            return new UserResource($user);
        }
    }

    //for only update avatar
    public function updateAvatar(Request $request, User $user)
    {
        if ($user === null) {
            return response()->json(['error' => 'User not found'], 404);
        }
        if ($user->isAdmin()) {
            return response()->json(['error' => 'Admin can not be modified'], 403);
        }

        $currentUser = Auth::user();
        if (!$currentUser->isAdmin()
            && $currentUser->id !== $user->id
            && !$currentUser->hasPermission(\App\Laravue\Acl::PERMISSION_MANAGE_USER)
        ) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $validator = Validator::make($request->all(), $this->getAvatarValidationRules());
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 403);
        } else {
            // create avatar file
            $file = $request->file('file');
            $name = 'avatar/' . uniqid() . '.' . $file->extension();
            $file->storePubliclyAs('public', $name);
            $user->avatar = $name;

            $user->save();
            // return $user;
            return new UserResource($user);
        }
    }

    public function updateActive(Request $request, User $user)
    {
        if ($user === null) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if ($user->active == 1){
            return response()->json(['error' => 'User not active not found'], 404);
        }

        if ($user->isAdmin()) {
            return response()->json(['error' => 'Admin can not be modified'], 403);
        }

        $currentUser = Auth::user();
        if (!$currentUser->isAdmin()
            && $currentUser->id !== $user->id
            && !$currentUser->hasPermission(\App\Laravue\Acl::PERMISSION_MANAGE_USER)
        ) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $user->active = 1;
        $user->save();
        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param User    $user
     * @return UserResource|\Illuminate\Http\JsonResponse
     */
    public function updatePermissions(Request $request, User $user)
    {
        if ($user === null) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if ($user->isAdmin()) {
            return response()->json(['error' => 'Admin can not be modified'], 403);
        }

        $permissionIds = $request->get('permissions', []);
        $rolePermissionIds = array_map(
            function($permission) {
                return $permission['id'];
            },

            $user->getPermissionsViaRoles()->toArray()
        );

        $newPermissionIds = array_diff($permissionIds, $rolePermissionIds);
        $permissions = Permission::allowed()->whereIn('id', $newPermissionIds)->get();
        $user->syncPermissions($permissions);
        return new UserResource($user);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  User $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        if ($user->isAdmin()) {
            return response()->json(['error' => 'Ehhh! Can not delete admin user'], 403);
        }

        try {
            $user->delete();
        } catch (\Exception $ex) {
            return response()->json(['error' => $ex->getMessage()], 403);
        }

        return response()->json(null, 204);
    }

    /**
     * Get permissions from role
     *
     * @param User $user
     * @return array|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function permissions(User $user)
    {
        try {
            return new JsonResponse([
                'user' => PermissionResource::collection($user->getDirectPermissions()),
                'role' => PermissionResource::collection($user->getPermissionsViaRoles()),
            ]);
        } catch (\Exception $ex) {
            response()->json(['error' => $ex->getMessage()], 403);
        }
    }

    /**
     * @param bool $isNew
     * @return array
     */
    private function getValidationRules($isNew = true)
    {
        return [
            'name' => 'required',
            'email' => $isNew ? 'required|email|unique:users' : 'required|email',
            'roles' => [
                'required',
                'array'
            ],
        ];
    }
    private function getAvatarValidationRules()
    {
        return [
            'file' => 'required',
        ];
    }

    public function getNotifications(Request $request)
    {
        $user = User::findOrFail(Auth::user()->id);
        $notifications = $user->notifications()->take($request->limit)->get();
        $total = $user->notifications()->count();
        $unread = $user->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'total' => $total,
            'unread' => $unread
        ]);
    }
}
