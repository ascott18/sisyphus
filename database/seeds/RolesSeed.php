<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        function makeRole($name, $display_name){
            $role = new Role();
            $role->name = $name;
            $role->display_name = $display_name;
            $role->save();

            return $role;
        }

        function makePerm($name, $display_name){
            $perm = new Permission();
            $perm->name = $name;
            $perm->display_name = $display_name;
            $perm->save();

            return $perm;
        }

        function p($name){
            return Permission::where('name', '=', $name)->firstOrFail();
        }



        makePerm('place-all-orders', "All - Place Orders");
        makePerm('edit-all-orders', "All - Edit Orders");
        makePerm('view-all-orders', "All - View Orders");

        makePerm('place-dept-orders', "Department - Place Orders");
        makePerm('view-dept-orders', "Department - View Orders");

        makePerm('view-all-courses', "All - View Courses");
        makePerm('view-dept-courses', "Department - View Courses");

        makePerm('edit-courses', "Visible - Edit Courses");
        makePerm('create-all-courses', "All - Create Course");
        makePerm('create-dept-courses', "Department - Create Course");

        makePerm('edit-books', "All - Edit Books");

        makePerm('manage-users', "All - Manage Users");
        makePerm('manage-roles', "All - Manage Roles");

        makePerm('view-terms', "All - View Terms");
        makePerm('edit-terms', "All - Edit Terms");

        makePerm('send-all-messages', "All - Send Messages");
        makePerm('send-dept-messages', "Department - Send Messages");


        makePerm('view-all-books', "All - View Books");
        makePerm('view-dept-books', "Department - View Books");


        makeRole('admin', "Administrator")->attachPermissions([
            p('manage-users'),
            p('manage-roles'),
            p('view-terms'),
            p('edit-terms'),
            p('edit-books'),
            p('edit-courses'),
            p('send-all-messages'),
            p('place-all-orders'),
            p('edit-all-orders'),
            p('view-all-orders'),
            p('view-all-courses'),
            p('create-all-courses'),
            p('view-all-books'),
        ]);

        makeRole('store', "Bookstore Staff")->attachPermissions([
            p('view-terms'),
            p('edit-books'),
            p('place-all-orders'),
            p('edit-all-orders'),
            p('view-all-orders'),
            p('view-all-courses'),
            p('view-all-books'),
        ]);

        makeRole('dept-sec', "Department Secretary")->attachPermissions([
            p('place-dept-orders'),
            p('view-dept-orders'),
            p('view-dept-courses'),
            p('create-dept-courses'),
            p('send-dept-messages'),
            p('view-dept-books'),
            p('edit-courses'),
        ]);

    }
}
