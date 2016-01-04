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



        makePerm('place-all-orders', "Place All Orders");
        makePerm('edit-all-orders', "Edit All Orders");
        makePerm('view-all-orders', "View All Orders");

        makePerm('place-dept-orders', "Place Department Orders");
        makePerm('view-dept-orders', "View Department Orders");

        makePerm('view-course-list', "View Course List");
        makePerm('view-all-courses', "View All Courses");
        makePerm('view-dept-courses', "View Department Courses");

        makePerm('edit-courses', "Edit Courses");

        makePerm('edit-books', "Edit Books");

        makePerm('manage-users', "Manage Users");
        makePerm('manage-roles', "Manage Roles");

        makePerm('view-terms', "View Terms");
        makePerm('edit-terms', "Edit Terms");

        makePerm('send-messages-to-all', "Send Messages To Everyone");
        makePerm('send-messages-to-department', "Send Messages To Department");


        makeRole('admin', "Administrator")->attachPermissions([
            p('send-messages-to-all'),
            p('manage-users'),
            p('manage-roles'),
            p('view-terms'),
            p('edit-terms'),
            p('view-course-list'),
            p('edit-books'),
            p('place-all-orders'),
            p('edit-all-orders'),
            p('view-all-orders'),
            p('view-all-courses'),
        ]);

        makeRole('store', "Bookstore Staff")->attachPermissions([
            p('view-course-list'),
            p('view-terms'),
            p('edit-books'),
            p('place-all-orders'),
            p('edit-all-orders'),
            p('view-all-orders')
        ]);

        makeRole('dept-sec', "Department Secretary")->attachPermissions([
            p('send-messages-to-department'),
            p('view-course-list'),
            p('place-dept-orders'),
            p('view-dept-orders'),
            p('view-dept-courses'),
        ]);

    }
}
