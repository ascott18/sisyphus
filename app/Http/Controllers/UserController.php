<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Http\Response;
use App\Providers\SearchServiceProvider;


class UserController extends Controller
{
    /**
     * The permissions that must always be available to at least one user.
     * We will not allow any action that would leave any of these permissions orphaned.
     *
     * @var array
     */
    public static $ESSENTIAL_PERMISSIONS = [
        'manage-users',
        'manage-roles',
    ];






    // **** Users Index Page **** //

    /** GET: /users
     *
     * Display a listing of users. Allows for assignment of roles and departments.
     *
     * @return \Illuminate\View\View
     */
    public function getIndex()
    {
        $this->authorize('manage-users');

        $roles = Role::all();

        return view('users.index', ['roles' => $roles]);
    }


    /** GET: /users/user-list/
     *
     * Returns results for the users list, potentially searched or sorted.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserList(Request $request)
    {
        $this->authorize('manage-users');

        $tableState = json_decode($request->input('table_state'));

        $query = User::query();

        $query = $query->with(['departments', 'roles']);

        $query = $this->buildUserSearchQuery($tableState, $query);
        $query = $this->buildUserSortQuery($tableState, $query);

        $users = $query->paginate(10);

        return $users;
    }


    /** POST: /users/set-role
     *
     * Sets the role of the specified user, removing their existing role first.
     *
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
            // Check that doing so will not cause there to be any essential permissions without users.

            $permissions = $currentRole->permissions;
            foreach ($permissions as $permission ) {
                if (in_array($permission->name, static::$ESSENTIAL_PERMISSIONS)){
                    // This role has an essential permission, so we need to make sure that
                    // removing this role from this user will not cause that permissions
                    // to be orphaned.

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
            // While it is technically allowed for a user to have multiple roles, that is a needless complication.
            // It is a nicer user experience to just specialize roles to suit your needs then throwing many of them at a user.
            $user->roles()->detach();
            $user->roles()->attach($dbRole);
        }
        return ['success' => true];
    }


    /** POST: /users/add-department
     *
     * Adds a department (subject) to a user.
     *
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


    /** POST: /users/remove-department
     *
     * Removes the specified department (subject) from the specified user.
     *
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
     * Build the search query for the users index page
     *
     * @param object $tableState
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildUserSearchQuery($tableState, $query)
    {
        if (isset($tableState->search->predicateObject))
            $predicateObject = $tableState->search->predicateObject;
        else
            return $query;

        if (isset($predicateObject->lName) && $predicateObject->lName != '')
            $query = $query->where('last_name', 'LIKE', '%' . $predicateObject->lName . '%');

        if (isset($predicateObject->fName) && $predicateObject->fName != '')
            $query = $query->where('first_name', 'LIKE', '%' . $predicateObject->fName . '%');

        if (isset($predicateObject->netID) && $predicateObject->netID != '')
            $query = $query->where('net_id', 'LIKE', '%' . $predicateObject->netID . '%');

        if (isset($predicateObject->email) && $predicateObject->email != '')
            $query = $query->where('email', 'LIKE', '%' . $predicateObject->email . '%');

        return $query;
    }


    /**
     * Build the sort query for the users index page
     *
     * @param object $tableState
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildUserSortQuery($tableState, $query)
    {
        if (isset($tableState->sort->predicate)) {
            $sorts = [
                'last_name' => [
                    'last_name', '',
                ],
                'first_name' => [
                    'first_name', '',
                ],
                'net_id' => [
                    'net_id', '',
                ],
                'email' => [
                    'email', '',
                ]
            ];

            SearchServiceProvider::buildSortQuery($query, $tableState->sort, $sorts);
        }
        return $query;
    }






    // **** Roles Management Page **** //

    /** GET: /users/roles
     *
     * Display a listing of roles. Allows for the creation, editing, and deleting of roles.
     *
     * @return \Illuminate\View\View
     */
    public function getRoles()
    {
        $this->authorize('manage-roles');

        $roles = Role::all();
        $permissions = Permission::all();

        return view('users.roles', ['roles' => $roles, 'permissions' => $permissions]);
    }


    /** GET: /users/all-roles
     *
     * Returns a list of all roles in the database and the number of users that have each role.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllRoles(Request $request)
    {
        $this->authorize('manage-roles');

        $roles = Role::with(['permissions'])
            ->select(\DB::raw("*, (SELECT COUNT(role_user.user_id) FROM role_user WHERE roles.id = role_user.role_id) AS numUsers"))
            ->get();

        return $roles;
    }


    /** POST: /users/create-role
     *
     * Creates a new role with the specified name.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postCreateRole(Request $request)
    {
        $this->authorize('manage-roles');

        // Check that the displayed name isn't taken.
        $this->validate($request, [
            'name' => "required|unique:roles,display_name"
        ]);

        // Save the display name and the slugged name, and then
        // replace the name in the request with the slug so that we can
        // use laravel's validation to ensure uniqueness.
        $name = $request->get('name');
        $nameSlug = str_slug($request->get('name'));
        $request->replace(['name' => $nameSlug]);

        // Check that the slug isn't taken.
        $this->validate($request, [
            'name' => "required|unique:roles,name"
        ]);


        // If we got here, everything is fine.
        $role = Role::create([
            'name' => $nameSlug,
            'display_name' => $name
        ]);

        return ['success' => true, 'role' => $role];
    }


    /** POST: /users/delete-role
     *
     * Deletes the role with the specified role_id
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postDeleteRole(Request $request)
    {
        $this->authorize('manage-roles');
        $this->validate($request, [
            'role_id' => "required"
        ]);

        $role = Role::findOrFail($request->get('role_id'));

        if ($role->users()->count() > 0)
            return response([
                'success' => false,
                'message' => 'Cannot delete a role that any users belong to.'],
                Response::HTTP_BAD_REQUEST
            );

        $role->delete();

        return ['success' => true];
    }


    /** POST: /users/add-permission
     *
     * Adds the permission with the spcified permission_id to the role with the specified role_id.
     *
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


    /** POST: /user/remove-permission
     *
     * Removes the specified permission from the specified role.
     *
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
        if (in_array($permission->name, static::$ESSENTIAL_PERMISSIONS)){
            $otherRolesWithPermission = $permission->roles()->where('roles.id', '!=', $role->id)->get();

            if (count($otherRolesWithPermission) == 0){
                // There are no other roles that have this permission,
                // so we definitely can't remove it from this role.
                return response()->json([
                    'success' => false,
                    'message' => "The $permission->display_name permission is an essential permission. You can't remove it from all roles."],
                    Response::HTTP_BAD_REQUEST);
            }
            else {
                $someoneHasPermission = false;
                foreach ($otherRolesWithPermission as $role) {
                    if ($role->users()->count() > 0){
                        $someoneHasPermission = true;
                        break;
                    }
                }
                if (!$someoneHasPermission){
                    // All other roles that have this permission do not have any user in
                    // that role, so we can't allow the permission to be removed from this role.
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






    // **** User Creation/Editing **** //

    /**
     * The set of default validation rules for creating/editing users.
     */
    public static $USER_VALIDATION = [
        'user.first_name' => 'required|string',
        'user.last_name' => 'required|string',
        'user.email' => 'required|string|unique:users,email',
    ];


    /** GET: /users/create
     *
     * Displays a page where the user can create a new user.
     *
     * @return \Illuminate\View\View
     */
    public function getCreate()
    {
        $this->authorize('manage-users');

        return view('users.edit', ['panelTitle' => 'Create User']);
    }


    /** POST: /users/create
     *
     * Creates a user with the provided attributes, and then redirects back to /users/
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postCreate(Request $request)
    {
        $this->authorize('manage-users');

        // Perform the common validation that we do on all user modification (create or edit) requests.
        $this->validate($request, static::$USER_VALIDATION, [
            'user.email.unique' => 'That Email already belongs to a user.'
        ]);

        // Perform one additional piece of validation on the net_id
        // to make sure that it is not already in use.
        $this->validate($request, [
            'user.net_id' => 'required|string|unique:users,net_id'
        ], [
            'user.net_id.unique' => 'That NetID already belongs to a user.'
        ]);

        // Make our user model and save it to the database.
        // We assign attributes here one by one for security so that there
        // are no mass assignment attacks.
        $user = new User;
        $user->first_name = $request->input('user.first_name');
        $user->last_name = $request->input('user.last_name');
        $user->net_id = $request->input('user.net_id');
        $user->email = $request->input('user.email');
        $user->save();

        return redirect('users');
    }


    /** GET: /users/edit/{$user_id}
     *
     * Displays a page where the specified user can be edited.
     *
     * @param Request $request
     * @param $user_id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getEdit(Request $request, $user_id)
    {
        if ($request->user()->user_id == $user_id){
            // Let users edit themselves.
            $this->authorize('all');
        }
        else{
            $this->authorize('manage-users');
        }

        $user = User::findOrFail($user_id);

        return view('users.edit', ['panelTitle' => 'Edit User', 'user' => $user]);
    }


    /** POST: /users/edit
     *
     * Updates the given user with a new name and email.
     * Does not allow for changing their net_id since this is an identifying attribute and should not ever be changed.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function postEdit(Request $request)
    {
        $this->validate($request, static::$USER_VALIDATION);
        $user = $request->get('user');
        $user_id = $user['user_id'];

        if ($request->user()->user_id == $user_id){
            // Let users edit themselves.
            $this->authorize('all');
        }
        else{
            $this->authorize('manage-users');
        }

        // Grab only known valid attributes out of the request to ensure
        // that we don't actually get extra, weird data in our User models.
        // There is another layer of defense after this, though, which is
        // the $fillable attributes on the model. More defense is better with users, though.
        $newAttributes = $request->only('user.first_name', 'user.last_name', 'user.email')['user'];

        $dbUser = User::findOrFail($user_id);
        $dbUser->update($newAttributes);

        return redirect('users');
    }
}
