<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\RoleRequest;
use App\Models\PermissionsGroup;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        $this->authorize('viewAny', Role::class);
        return view('settings.roles.index');
    }

    /**
     * Returns data table list for roles index.
     * @return JsonResponse
     */
    public function getDataTableList(){
        return Role::getDataTable();
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return View
     */
    public function create()
    {
        $this->authorize('create', Role::class);

        return $this->edit(new Role);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  RoleRequest $request
     * @return RedirectResponse
     */
    public function store(RoleRequest $request)
    {
        $this->authorize('create', Role::class);

        $data = $request->validated();

        $role = Role::create($data);

        return redirect()->route('staff.settings.roles.edit',$role)->withStatus('Role created!');
    }

    /**
     * Display the specified resource.
     *
     * @param  Role $role
     * @return RedirectResponse
     */
    public function show(Role $role)
    {
        $this->authorize('view', $role);

        return redirect()->route('staff.settings.roles.edit',$role);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Role  $role
     * @return view
     */
    public function edit(Role $role)
    {
        $this->authorize('view', $role);

        //Get users that are enabled and are of type staff and have the role assigned
        $users = User::enabled()->staff()->role($role->name)->get();
        $permissionTree = $this->getPermissionTreeData($role);
        return view('settings.roles.edit',[
            'role' => $role,
            'users' => $users,
            'permissionTree' => $permissionTree
        ]);
    }

    /**
     * Get permissions and permissions group data for permission tree .
     *
     * @param  Role  $role
     * @return array
     */
    public function getPermissionTreeData($role){
        $selectedRolePermissions = $role->permissions()->pluck('id')->toArray();
        $roots = PermissionsGroup::whereNull('parent_id')->get()->pluck('id')->toArray();
        $rootsArray = [];
        foreach($roots as $item){
            $rootsArray[] =$this->getNodeId('group',$item);
        }
        $nodeGroup = PermissionsGroup::with('children')->get();

        $groupArray = [];
        $permissionsArray = []; 

        foreach($nodeGroup as $group){
            $checkedState = false;
            $groupArray[$this->getNodeId('group',$group->id)]['text'] = $group->label ;
            $children = $group->children()->pluck('id')->toArray();
            $permissions =  $group->permissions;
           
            $childPermissions = [];
            $childGroup = [];
            foreach($permissions as $permission){
                $childPermissions[] = $this->getNodeId('permission',$permission->id);
                $permissionsArray[$this->getNodeId('permission',$permission->id)] = [
                    'text' => $permission->label,
                    'id' => $permission->id
                ];
                if(in_array($permission->id,$selectedRolePermissions)){
                    $checkedState = true;
                    $permissionsArray[$this->getNodeId('permission',$permission->id)]['state'] = [
                            'checked' => true,
                            'opened' => true
                    ];
                }
            }
            if($checkedState){
                $groupArray[$this->getNodeId('group',$group->id)]['state'] = ['indeterminate' => true];
            }
            $groupArray[$this->getNodeId('group',$group->id)]['state']['opened'] = true;
            if(count($children)){
                foreach($children as $child){
                    $childGroup[] = $this->getNodeId('group',$child);
                }
            }
            $childNodes = array_merge($childPermissions,$childGroup);
            $groupArray[$this->getNodeId('group',$group->id)]['children'] = $childNodes;
        }
        return ['roots' => $rootsArray,'groups' => $groupArray,'permissions' =>$permissionsArray];
    }
    
    /**
     * Return id of permission or permission group with string to be used in permission tree .
     *
     * @param  $type | [group,permission]
     * @param  $id | integer
     * @return string
     */
    public function getNodeId($type,$id){
        return $type.'-'.$id;
    }

    /**
     * Update the role details.
     *
     * @param  RoleRequest $request
     * @param  Role $role
     * @return RedirectResponse
     */
    public function update(RoleRequest $request, Role $role)
    {
        $this->authorize('update', $role);

        $data = $request->validated();
        $role->fill($data);
        $role->save();

        return redirect()->route('staff.settings.roles.edit',$role)->withStatus('Role updated!');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Role $role
     * @return RedirectResponse
     */
    public function destroy(Role $role)
    {
        $this->authorize('delete', $role);

        if($role->system){
            return redirect()->route('staff.settings.roles.index')->withError('This is a system role and it cannot be deleted');
        }

        $role->delete();

        return redirect()->route('staff.settings.roles.index')->withStatus('Role deleted!');
    }

    /**
     * Remove the specified resource from storage.
     * @param Request $request
     * @param  Role $role
     * @return RedirectResponse
     */
    public function updatePermissions(Request $request, Role $role)
    {
        $this->authorize('update', $role);
        $permissionIds = $this->getPermissionsIds($request);
        $role->syncPermissions($permissionIds);
        Session::flash('status' , 'Permissions updated!');
        return response()->json(['success' => true, 'message' => 'Permissions updated!']);
    }

    public function getPermissionsIds($request){
        $permissionIds = [];
        foreach($request->get('data') as $key=>$node){
            if( str_contains($key,'permission') && (isset($node['state']) && $node['state']['checked'] == true)){
                $permissionIds[] = str_replace('permission-','',$node['id']);
            }
        }
        return $permissionIds;
    }

}
