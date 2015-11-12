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

        makePerm('edit-books', "Edit Books");


        makeRole('admin', "Administrator")->attachPermissions([
            p('edit-books'),
            p('place-all-orders'),
            p('edit-all-orders'),
            p('view-all-orders')
        ]);

        makeRole('store', "Bookstore Staff")->attachPermissions([
            p('edit-books'),
            p('place-all-orders'),
            p('edit-all-orders'),
            p('view-all-orders')
        ]);

        makeRole('dept-sec', "Department Secretary")->attachPermissions([
            p('place-dept-orders'),
            p('view-dept-orders')
        ]);

    }
}
