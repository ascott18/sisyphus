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
        $this->authorize('manage-users');

        $roles = Role::all();

        return view('users.index', ['roles' => $roles]);
    }

    public function getRoles()
    {
        $this->authorize('manage-roles');

        $roles = Role::all();
        $permissions = Permission::all();

        return view('users.roles', ['roles' => $roles, 'permissions' => $permissions]);
    }

    /**
     * Build the search query for the users controller
     *
     * @param \Illuminate\Database\Query $query
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Query
     */
    private function buildSearchQuery($request, $query) {
        if($request->input('lName'))
            $query = $query->where('last_name', 'LIKE', '%'.$request->input('lName').'%');
        if($request->input('fName'))
            $query = $query->where('first_name', 'LIKE', '%'.$request->input('fName').'%');
        if($request->input('netID'))
            $query = $query->where('net_id', 'LIKE', '%'.$request->input('netID').'%');
        if($request->input('email'))
            $query = $query->where('email', 'LIKE', '%'.$request->input('email').'%');

        return $query;
    }


    /**
     * Build the sort query for the users controller
     *
     * @param \Illuminate\Database\Query $query
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Query
     */
    private function buildSortQuery($request, $query) {
        if($request->input('sort'))
            if($request->input('dir'))
                $query = $query->orderBy($request->input('sort'), "desc");
            else
                $query = $query->orderBy($request->input('sort'));

        return $query;
    }

    /** GET: /users/user-list/
     * Searches the book list
     *
     * @param \Illuminate\Database\Eloquent\Builder
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserList(Request $request)
    {
        $this->authorize('manage-users');

        $query = \App\Models\User::query();

        $query = $query->with(['departments', 'roles']);

        $query = $this->buildSearchQuery($request, $query);

        $query = $this->buildSortQuery($request, $query);

        $users = $query->paginate(10);

        return $users;
    }


    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllRoles(Request $request)
    {
        $this->authorize('manage-roles');

        return \App\Models\Role::with(['permissions'])->get();
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postAddDepartment(Request $request)
    {
        $this->authorize('manage-users');
        $this->validate($request, [
            'department' => 'required|min:2|max:10',
            'user_id' => 'required',
        ]);

        $user_id = $request->get('user_id');
        $user = User::findOrFail($user_id);

        $department = $request->get('department');
        $department = strtoupper($department);

        $user->departments()->updateOrCreate(['department' => $department]);

        return ['success' => true];
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postRemoveDepartment(Request $request)
    {
        $this->authorize('manage-users');

        $user_id = $request->get('user_id');
        $department = $request->get('department');

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
        $this->authorize('manage-users');

        $user_id = $request->get('user_id');

        $role = $request->get('role');

        $user = User::findOrFail($user_id);
        $currentRole = $user->role();

        // Detect if the role isn't actually changing, and return success if so.
        if ($currentRole == null ? $role == "" : $currentRole->name == $role)
        {
            return ['success' => true];
        }

        if ($currentRole != null && $currentRole->users()->where('users.user_id', '!=', $user->user_id)->count() == 0){
            // Removing this user from the role would leave the role with no users.
            // Check that doing so will not cause there to be no users with an essential permission.

            $permissions = $currentRole->permissions;
            foreach ($permissions as $permission ) {
                if (in_array($permission->name, static::$essentialPermissions)){
                    // This role has an essential permission, so we need to make sure that
                    // removing this role from this user will not leave one of those permissions
                    // being orphaned.

                    $rolesWithPermission = $permission->roles;
                    $someoneHasPermission = false;
                    foreach ($rolesWithPermission as $roleWithPermission) {
                        if ($roleWithPermission->users()->where('users.user_id', '!=', $user->user_id)->count() > 0){
                            $someoneHasPermission = true;
                            break;
                        }
                    }
                    if (!$someoneHasPermission){
                        return response()->json([
                            'success' => false,
                            'message' => "Removing the $currentRole->display_name role from $user->net_id would leave no users with the $permission->display_name permission."],
                            Response::HTTP_BAD_REQUEST);
                    }
                }
            }
        }


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
        $this->authorize('manage-roles');

        $role = Role::findOrFail($request->get('role_id'));
        $permission = Permission::findOrFail($request->get('permission_id'));

        if (!$role->hasPermission($permission->name))
            $role->attachPermission($permission);

        return ['success' => true];
    }

    public static $essentialPermissions = [
        'manage-users',
        'manage-roles',
    ];

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postRemovePermission(Request $request)
    {
        $this->authorize('manage-roles');

        $role = Role::findOrFail($request->get('role_id'));
        $permission = Permission::findOrFail($request->get('permission_id'));


        // Silently succeed if the role doesn't actually have this permission.
        if (!$role->hasPermission($permission->name))
            return ['success' => true];

        // Guard against accidental removal of an essential permission.
        if (in_array($permission->name, static::$essentialPermissions)){
            $otherRolesWithPermission = $permission->roles()->where('roles.id', '!=', $role->id)->get();

            if (count($otherRolesWithPermission) == 0){
                return response()->json([
                    'success' => false,
                    'message' => "The $permission->display_name permission is an essential permission. You can't remove it from all roles."],
                    Response::HTTP_BAD_REQUEST);
            }
            else{
                $someoneHasPermission = false;
                foreach ($otherRolesWithPermission as $role) {
                    if ($role->users()->count() > 0){
                        $someoneHasPermission = true;
                        break;
                    }
                }
                if (!$someoneHasPermission){
                    return response()->json([
                        'success' => false,
                        'message' => "Removing the $permission->display_name permission would leave no users with it."],
                        Response::HTTP_BAD_REQUEST);
                }
            }
        }


        // Everything is in order. Actually remove the permission from the role.
        $role->detachPermission($permission);

        return ['success' => true];
    }
}
