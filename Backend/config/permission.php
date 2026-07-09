<?php

return [

    'models' => [

        /*
        |--------------------------------------------------------------------------
        | Models
        |--------------------------------------------------------------------------
        |
        | When using the "HasRoles" trait from this package, we need to know which
        | Eloquent model should be used to retrieve your permissions. Of course, it
        | is often just the "Permission" model but you may use whatever you like.
        |
        | The model you want to use as a Permission model needs to implement the
        | `Spatie\Permission\Contracts\Permission` contract.
        |
        */

        'permission' => Spatie\Permission\Models\Permission::class,

        /*
        |--------------------------------------------------------------------------
        | Role Model
        |--------------------------------------------------------------------------
        |
        | When using the "HasRoles" trait, we need to know which Eloquent model
        | should be used to retrieve your roles. Of course, it is often just the
        | "Role" model but you may use whatever you like.
        |
        | The model you want to use as a Role model needs to implement the
        | `Spatie\Permission\Contracts\Role` contract.
        |
        */

        'role' => Spatie\Permission\Models\Role::class,

    ],

    'table_names' => [

        /*
        |--------------------------------------------------------------------------
        | Table Names
        |--------------------------------------------------------------------------
        |
        | When using the "HasRoles" trait, we need to know which table should be
        | used to store your permissions, roles, and model associations. We have
        | chosen a basic default value but you may easily change it to any table
        | name you like.
        |
        */

        'roles' => 'roles',

        'permissions' => 'permissions',

        'model_has_roles' => 'model_has_roles',

        'model_has_permissions' => 'model_has_permissions',

        'role_has_permissions' => 'role_has_permissions',
    ],

    'column_names' => [
        /*
        |--------------------------------------------------------------------------
        | Column Names
        |--------------------------------------------------------------------------
        |
        | You can change the default column names used in pivot tables. The
        | defaults should be fine for most use cases, but you are free to
        | change them if needed.
        |
        */

        'role_pivot_key' => 'role_id',

        'permission_pivot_key' => 'permission_id',

        /**
         * The column name for the model's morph key (default 'model_id').
         */
        'model_morph_key' => 'model_id',

        /*
        |--------------------------------------------------------------------------
        | Teams Column
        |--------------------------------------------------------------------------
        |
        | If using the teams feature, you must set the "team_foreign_key"
        | column name. The default is 'team_id', but you can change it.
        |
        */

        'team_foreign_key' => 'team_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Register Permission Check Method
    |--------------------------------------------------------------------------
    |
    | When set to true, the package will register the `checkPermission` method
    | on the Gate facade, which can be used to check permissions without
    | needing to use the `can` method on the User model.
    |
    */

    'register_permission_check_method' => true,

    /*
    |--------------------------------------------------------------------------
    | Teams Feature
    |--------------------------------------------------------------------------
    |
    | When set to true, the package implements teams using the 'team_foreign_key'
    | column. You can configure the teams feature below.
    |
    */

    'teams' => false,

    'team_foreign_key' => 'team_id',

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Spatie Permission uses a cache to store permission and role information.
    | You can configure the cache driver and cache lifetime here.
    |
    */

    'cache' => [

        'driver' => env('PERMISSION_CACHE_DRIVER', 'file'),

        'key' => 'spatie.permission.cache',

        'expiration_time' => 3600,

        'store' => env('PERMISSION_CACHE_STORE', 'file'),
    ],
];
