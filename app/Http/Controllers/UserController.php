<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Http\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $roles = Role::all();

        return view('users.index', ['roles' => $roles]);
    }

    public function getRoles()
    {
        $roles = Role::all();
        $permissions = Permission::all();

        return view('users.roles', ['roles' => $roles, 'permissions' => $permissions]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllUsers(Request $request)
    {
        // TODO: please please please don't forget to gate this.

        return \App\Models\User::with(['departments', 'roles'])->get();
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllRoles(Request $request)
    {
        // TODO: don't forget to gate this.

        return \App\Models\Role::with(['permissions'])->get();
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postAddDepartment(Request $request)
    {
        $user_id = $request->get('user_id');

        $department = $request->get('department');
        $department = strtoupper($department);

        // TODO: validation on $department

        $user = User::findOrFail($user_id);

        $user->departments()->updateOrCreate(['department' => $department]);

        return ['success' => true];
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postRemoveDepartment(Request $request)
    {
        $user_id = $request->get('user_id');
        $department = $request->get('department');

        // TODO: validation on $department

        $user = User::findOrFail($user_id);

        $user_department = $user->departments()->where(['department' => $department])->first();

        if ($user_department)
        {
            $user_department->delete();
        }

        return ['success' => true];
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postSetRole(Request $request)
    {
        $user_id = $request->get('user_id');

        $role = $request->get('role');

        $user = User::findOrFail($user_id);

        if ($role === ""){
            // If the role is the empty string, we're making this user a "Faculty" user (no roles)
            $user->roles()->detach();
        }
        else {
            $dbRole = Role::where(['name' => $role])->firstOrFail();

            // Detatch all current roles, and attach the new one.
            $user->roles()->detach();
            $user->roles()->attach($dbRole);
        }

        return ['success' => true];
    }






    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postAddPermission(Request $request)
    {
        $role = Role::findOrFail($request->get('role_id'));
        $permission = Permission::findOrFail($request->get('permission_id'));

        if (!$role->hasPermission($permission->name))
            $role->attachPermission($permission);

        return ['success' => true];
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postRemovePermission(Request $request)
    {
        $role = Role::findOrFail($request->get('role_id'));
        $permission = Permission::findOrFail($request->get('permission_id'));

        if ($role->hasPermission($permission->name))
            $role->detachPermission($permission);

        return ['success' => true];
    }
}
