<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\Agent;
use App\Models\Country;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class UserController extends Controller
{
    /**
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('viewAny', User::class);
        $user = auth()->user();
        $userTypes = $this->getUserTypes($user);

        return view('staff.users.index', compact('userTypes'));
    }

    /**
     * @throws Exception
     */
    public function getDataTableList(): JsonResponse
    {
        $this->authorize('viewAny', User::class);
        $userTypes = $this->getUserTypes(auth()->user());
        return User::getDataTable($userTypes);
    }

    public function createStep1()
    {
        $this->authorize('create', User::class);

        $user = auth()->user();
        $user_types = [];

        if($user->can('create-staff-users')){
            $user_types[] = config('constants.system_roles.staff');
        }
        if($user->can('create-agent-users')){
            $user_types[] = config('constants.system_roles.agent');
        }
        if($user->can('create-student-users')){
            $user_types[] = config('constants.system_roles.student');
        }

        return view('staff.users.step1', compact('user_types'));
    }


    public function processStep1(Request $request)
    {
        $this->authorize('create', User::class);

        $user = auth()->user();
        $user_type = $request->user_type;

        if(!$this->canCreateUserType($user_type, $user))
            return redirect()->route('staff.users.create.step1')->withError('Please select a valid user type to continue');

        return redirect()->route('staff.users.create.step2',$user_type);

    }


    public function createStep2(Request $request,  string $user_type){

        $this->authorize('create', User::class);

        $user = auth()->user();

        if(!$this->canCreateUserType($user_type, $user))
            return redirect()->route('staff.users.create')->withError('Please select a valid user type to continue');

        $countries = Country::enabled()->select('id','name')->orderBy('name')->get();
        $agents = Agent::select('id','name')->orderBy('name')->get();

        $roles = Role::select('id','name');
        if(!auth()->user()->hasRole(config('constants.system_roles.superadmin')) ){
            $roles = $roles->where('name', '!=', config('constants.system_roles.superadmin'));
        }
        $roles = $roles->orderBy('name')->get();

        return view('staff.users.step2', compact('user_type','countries','roles','agents'));
    }


    /**
     * Check whether a user can create a user of the given type.
     * @param string|null $user_type User type for the new user that is being created.
     * @param User|null $user User to check the permissions for
     * @return bool
     */
    private function canCreateUserType(string|null $user_type, User $user = null){
        if(!$user_type)
            return false;

        if(!$user)
            $user = auth()->user();

        return match ($user_type){
            config('constants.system_roles.staff') => $user->can('create-staff-users'),
            config('constants.system_roles.agent') => $user->can('create-agent-users'),
            config('constants.system_roles.student') => $user->can('create-student-users'),
            default => false
        };

    }


    public function store(UserStoreRequest $request){

        if(!$this->canCreateUserType($request->user_type)){
            return response()->json(['success' => false, 'message' => 'You are not authorised to create a user of type: '.$request->user_type]);
        }

        $validated = $request->validated();

        $user = new User;
        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->email = $validated['email'];
        $user->mobile = $validated['mobile'] ?? null;
        $user->password = Hash::make($validated['password']);
        $user->country_id = $validated['country_id'];
        $user->user_type = $validated['user_type'];

        $user->enabled = $validated['enabled'];
        $user->email_verified_at = $validated['email_verified'] ? now() : null;
        $user->verified_at = $validated['user_verified'] ? now() : null;

        $user->parent_user_id = $validated['parent_user_id'] ?? null;
        $user->agent_id = $validated['agent_id'] ?? null;


        $user->save();

        if($request->has('role_id')){
            $user->roles()->sync($request->get('role_id'));
        }

        Session::flash('status' , 'User created!');
        return response()->json([
            'success' => true,
            'redirect_route' => route('staff.users.show', $user),
            'message' => 'User created successfully'
        ]);

    }

    public function show(User $user)
    {
        $this->authorize('view', $user);
        if($user->isBotUser())
            abort(403);

        return view('staff.users.show',compact('user'));
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);
        if($user->isBotUser())
            abort(403);

        $countries = Country::enabled()->select('id','name')->orderBy('name')->get();
        $agents = Agent::select('id','name')->orderBy('name')->get();

        $roles = Role::select('id','name');
        if(!auth()->user()->hasRole(config('constants.system_roles.superadmin')) ){
            $roles = $roles->where('name', '!=', config('constants.system_roles.superadmin'));
        }
        $roles = $roles->orderBy('name')->get();

        return view('staff.users.edit',compact('user','countries','roles','agents'));
    }

    public function update(UserUpdateRequest $request, User $user)
    {
        $this->authorize('update', $user);
        if($user->isBotUser())
            abort(403);

        $validated = $request->validated();


        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->email = $validated['email'];
        $user->mobile = $validated['mobile'] ?? null;
        if($validated['password']){
            $user->password = Hash::make($validated['password']);
        }
        $user->country_id = $validated['country_id'];
        $user->enabled = $validated['enabled'];
        $user->email_verified_at = $validated['email_verified'] ? now() : null;
        $user->verified_at = $validated['user_verified'] ? now() : null;

        if($request->has('parent_user_id')){
            $user->parent_user_id = $request->get('parent_user_id');
        }

        if($request->has('agent_id')){
            $user->agent_id = $request->get('agent_id');
        }


        $user->save();

        if($request->has('role_id')){
            $user->roles()->sync($request->get('role_id'));
        }

        Session::flash('status' , 'User update!');
        return response()->json([
            'success' => true,
            'redirect_route' => route('staff.users.show', $user),
            'message' => 'User updated successfully'
        ]);
    }

    /**
     * @param $user
     * @return array
     */
    private function getUserTypes($user): array
    {
        $userTypes = [];
        if($user->can('view-staff-users')){
            $userTypes[] = config('constants.system_roles.staff');
        }
        if($user->can('view-agent-users')){
            $userTypes[] = config('constants.system_roles.agent');
        }
        if($user->can('view-student-users')){
            $userTypes[] = config('constants.system_roles.student');
        }

        return $userTypes;
    }

    /**
     * Return Student manager list for dropdown in users form.
     * @param Request  $request
     * @return jsonResponse
     */
    public function searchManagers(Request $request){
        $agentTypes = [config('constants.user_types.agent'),config('constants.user_types.staff')];
        $search = $request->get('search');
        $users = User::select('id','email',DB::raw("CONCAT(users.first_name,' ',users.last_name)  AS name"))
                ->whereIn('user_type',$agentTypes)->where('enabled',1)
                ->where(function ($q) use ( $search){
                    $q->where('first_name','like' , '%'.$search.'%')
                        ->orWhere('last_name','like' , '%'.$search.'%')
                        ->orWhere('email','like' , '%'.$search.'%');
                })->get();
        return response()->json(['data'=>  $users,'success' => true]);
    }


    /**
     * @param $user_type
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function getUsersByUserType($user_type): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $users = User::select('id',DB::raw('CONCAT(first_name," ",last_name, " - ", email) AS label'));
        $list = $users->whereEnabled(1)
            ->whereUserType($user_type)
            ->get();
        return response()->json($list);
    }

}
