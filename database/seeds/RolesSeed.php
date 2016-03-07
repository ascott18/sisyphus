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

        function r($name){
            return Role::where('name', '=', $name)->firstOrFail();
        }



        makePerm('place-all-orders', "All - Place Requests");
        makePerm('place-dept-orders', "Department - Place Requests");
        makePerm('order-outside-period', "Other - Order for Non-current Terms");

        makePerm('view-all-orders', "All - View Requests");
        makePerm('view-dept-orders', "Department - View Requests");

        makePerm('view-all-courses', "All - View Courses");
        makePerm('view-dept-courses', "Department - View Courses");

        makePerm('edit-courses', "Other - Create/Edit Courses");

        makePerm('edit-books', "All - Edit Books");

        makePerm('manage-users', "All - Manage Users");
        makePerm('manage-roles', "All - Manage Roles");

        makePerm('view-terms', "All - View Terms");
        makePerm('edit-terms', "All - Edit Terms");

        makePerm('send-all-messages', "All - Send Messages");
        makePerm('send-dept-messages', "Department - Send Messages");

        makePerm('make-reports',"Other - Make Reports");
        makePerm('view-dashboard', "Other - View Dashboard");

        makePerm('receive-dept-tickets', "Department - Receive Tickets");
        makePerm('receive-all-tickets', "Other - Receive Unassigned Tickets");


        // These three roles were created with a Bookstore rollout in mind.
        // We'll make a specialized one for Connie instead.
//        makeRole('admin', "Administrator")->attachPermissions([
//            p('manage-users'),
//            p('manage-roles'),
//            p('view-terms'),
//            p('edit-terms'),
//            p('edit-books'),
//            p('edit-courses'),
//            p('send-all-messages'),
//            p('place-all-orders'),
//            p('view-all-orders'),
//            p('view-all-courses'),
//            p('order-outside-period'),
//            p('make-reports'),
//            p('view-dashboard'),
//            p('receive-all-tickets'),
//        ]);
//
//        makeRole('store', "Bookstore Staff")->attachPermissions([
//            p('view-terms'),
//            p('edit-books'),
//            p('place-all-orders'),
//            p('view-all-orders'),
//            p('view-all-courses'),
//            p('make-reports'),
//            p('view-dashboard'),
//            p('receive-all-tickets'),
//        ]);
//
//        makeRole('dept-sec', "Department Secretary")->attachPermissions([
//            p('place-dept-orders'),
//            p('view-dept-orders'),
//            p('view-dept-courses'),
//            p('send-dept-messages'),
//            p('edit-courses'),
//            p('make-reports'),
//            p('view-dashboard'),
//            p('view-terms'),
//            p('receive-dept-tickets'),
//        ]);


        // This role exists with a CSCD-specific rollout in mind.
        // It shouldn't exist with a Bookstore rollout
        // (since it gives Connie things that she wouldn't need to do with a campus rollout,
        // like editing term stat/end dates and editing books).
        makeRole('dept-sec', "Department Secretary")->attachPermissions([
            p('manage-users'),
            p('manage-roles'),
            p('edit-terms'),
            p('edit-books'),
            p('order-outside-period'),
            p('place-dept-orders'),
            p('view-dept-orders'),
            p('view-dept-courses'),
            p('send-dept-messages'),
            p('edit-courses'),
            p('make-reports'),
            p('view-dashboard'),
            p('view-terms'),
            p('receive-dept-tickets'),
        ]);

        // This is for people like Rob Lemelin who place the orders for a subject,
        // but won't be submitting them to the bookstore (Connie does that).
        // This role is useful for either a CSCD or a Bookstore rollout.
        makeRole('dept-coordinator', "Subject Coordinator")->attachPermissions([
            p('place-dept-orders'),
            p('view-dept-orders'),
            p('view-dept-courses'),
            p('view-terms'),
        ]);

        $secretaries = [
            \App\Models\User::create([
                'first_name' => 'Connie',
                'last_name' => 'Bean',
                'email' => 'cbean@ewu.edu',
                'net_id' => 'cbean',
            ]),
            \App\Models\User::create([
                'first_name' => 'Margo',
                'last_name' => 'Stanzak',
                'email' => 'mstanzak@ewu.edu',
                'net_id' => 'mstanzak',
            ])
        ];

        foreach ($secretaries as $secretary) {
            $secretary->attachRole(r('dept-sec'));
            $secretary->departments()->save(new \App\Models\UserDepartment([
                'department' => 'CSCD',
            ]));
            $secretary->departments()->save(new \App\Models\UserDepartment([
                'department' => 'CPLA',
            ]));
        }

        $lemelin = \App\Models\User::create([
            'first_name' => 'Rob',
            'last_name' => 'Lemelin',
            'email' => 'rlemelin@ewu.edu',
            'net_id' => 'rlemelin',
        ]);
        $lemelin->attachRole(r('dept-coordinator'));
        $lemelin->departments()->save(new \App\Models\UserDepartment([
            'department' => 'CPLA',
        ]));
    }
}
