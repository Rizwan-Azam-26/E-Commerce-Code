<?php

namespace App\Http\Controllers;

use App\Constants\AppConstants;
use App\Constants\IStatus;
use App\Http\Requests\UserRequests;
use App\Models\Plan;
use App\Models\PlanOptions;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// use App\Role;

class UserController extends Controller
{
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
        parent::__construct('user');
    }

    public function index()
    {
        $users = User::with('roles')->where('business_id',
            Auth::user()->business_id)->whereNull('deleted_at')->paginate(AppConstants::PAGINATE_SMALL);

        $groups = Permission::select('group')->where('is_business', 1)->groupBy('group')->get();
        $roles = Role::with('permissions')->where('business_id', Auth::user()->business_id)->get();

        return view("business.settings.users-management.users.list", compact('groups', 'roles', 'users'));
    }

    public function create(Request $request)
    {

        $plan = Plan::find(Auth::user()->business->plan_id);
        $planOption = PlanOptions::wherePlanId($plan->id)->whereOption(AppConstants::PLAN_OPTION_STAFF)->first();
        $userCount = User::whereBusinessId(Auth::user()->business_id)->whereNull('deleted_at')->count('id');

        /*if ($planOption->values <= $userCount) {
            flash('You have reached to your maximum staff limit. Please upgrade your subscription to continue!')->error();
            return redirect()->route('user-list');
        }*/

        $users = User::with('roles')->where('business_id', Auth::user()->business_id)->get();
        $groups = Permission::select('group')->where('is_business', 1)->groupBy('group')->get();
        $permissions = Permission::where('is_business', 1)->get();
        $roles = Role::with('permissions')->where('business_id', Auth::user()->business_id)->get();

        return view("business.settings.users-management.users.add", compact('permissions', 'groups', 'roles', 'users'));

    }

    public function store(UserRequests $request)
    {
        $this->userService->save($request, null, 0, true);
        flash('User created successfully.');
        return redirect()->route('user-list');

    }

    public function edit(Request $request, $user_id)
    {
        $userdetails = user::with('role')->with('image')->where('id', $user_id)->first();
        $users = User::with('roles')->where('business_id', Auth::user()->business_id)->get();
        $groups = Permission::select('group')->where('is_business', 1)->groupBy('group')->get();
        $permissions = Permission::where('is_business', 1)->get();
        $roles = Role::with('permissions')->where('business_id', Auth::user()->business_id)->get();

        return view("business.settings.users-management.users.edit",
            compact('userdetails', 'permissions', 'groups', 'roles', 'users'));

    }

    public function update(UserRequests $request, $user)
    {

        $this->userService->update($request, $user);
        flash('User updated successfully.');

        return redirect()->route('user-list');
    }

    public function userStatusUpdate(Request $request)
    {
        $plan = Plan::find(Auth::user()->business->plan_id);
        $planOption = PlanOptions::wherePlanId($plan->id)->whereOption(AppConstants::PLAN_OPTION_STAFF)->first();

        $userCount = User::whereBusinessId(Auth::user()->business_id)->whereNull('deleted_at')->count('id');

        $status = $request->status_id;
        if (!empty($planOption->values) && ($planOption->values > $userCount || $status == IStatus::DISABLE)) {
            $id = $request->user_id;
            $user = User::find($id);

            if ($user) {
                $user->is_active = $status;
                $user->save();
            }
            if ($status == IStatus::ACTIVE) {
                flash('User status activated successfully')->success()->important();
            } else {
                flash('User status inactivated successfully')->success()->important();
            }
        } else {
            flash('Cannot active this staff. You have reached to your maximum staff limit. Please upgrade your subscription to continue! ')->error()->important();
        }

        return redirect()->back();
    }

    public function destroy($id)
    {
        $this->userService->delete($id);
        flash('User deleted successfully')->success();
        return redirect()->route('user-list');
    }

    public function show($id)
    {
        return true;
    }

}
