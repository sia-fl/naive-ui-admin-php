<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUserEditRequest;
use App\Http\Requests\Admin\AdminUserIndexRequest;
use App\Http\Requests\PasswordRequest;
use App\Models\Admin\AdminDepartment;
use App\Models\Admin\AdminPosition;
use App\Models\Admin\AdminUser;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    function forgotPassword(PasswordRequest $request, $id)
    {
        $password = $request->input('password');
        $password = Hash::make($password);
        AdminUser::query()
            ->where('id', $id)
            ->update([
                'password' => $password
            ]);
        return ss();
    }

    function status()
    {
        AdminUser::status();
        return ss();
    }

    function positions()
    {
        $positions = AdminDepartment::query()
            ->select([
                'id',
                'name'
            ])
            ->with(
                'positions:id,name,admin_department_id',
            )
            ->get();
        return result($positions);
    }

    function find($id)
    {
        $user = AdminUser::query()
            ->select([
                'id',
                'status',
                'phone',
                'email',
                'gender',
                'username',
                'admin_position_id'
            ])
            ->where('id', $id)
            ->firstOrFail();
        return result($user);
    }

    function list(AdminUserIndexRequest $request)
    {
        $paginator = AdminUser::indexFilter($request->validated())
            ->with('position.department:id,name')
            ->with('position:id,name,admin_department_id')
            ->paginate(...usePage());

        return page($paginator);
    }

    function enabledList(AdminUserIndexRequest $request)
    {
        $paginator = AdminUser::indexFilter($request->validated())
            ->with('position.department:id,name')
            ->with('position:id,name,admin_department_id')
            ->whereIn('status', ['?????????', '?????????'])
            ->paginate(...usePage());

        return page($paginator);
    }

    function create(AdminUserEditRequest $request)
    {
        // ????????????
        $post       = $request->validated();
        $positionId = $post['admin_position_id'];

        // ????????????
        $user           = new AdminUser($request->validated());
        $user->id       = uni();
        $user->password = bcrypt($user->password);
        $ok             = $user->getConnection()->transaction(function () use (
            $user,
            // ????????????
            $positionId
        ) {
            // ????????????
            AdminPosition::used($positionId);

            // ??????
            $user->save();
            return true;
        });
        return tx($ok);
    }

    function update(AdminUserEditRequest $request, $id)
    {
        // ????????????
        $post       = $request->validated();
        $positionId = $post['admin_position_id'];

        // ????????????
        $user = new AdminUser();
        $ok   = $user->getConnection()->transaction(function () use (
            $id, $post,
            // ????????????
            $positionId
        ) {
            // ????????????
            AdminPosition::used($positionId);

            // ??????
            if (isset($post['password'])) {
                $post['password'] = bcrypt($post['password']);
            }
            AdminUser::query()->where('id', $id)->update($post);
            return true;
        });
        return tx($ok);
    }

    function delete()
    {
        AdminUser::clear();
        return ss();
    }
}
