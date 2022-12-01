<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgentCommissionRequest;
use App\Http\Requests\AgentRequest;
use App\Models\Agent;
use App\Services\EbecasService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentsController extends Controller
{
    private EbecasService $ebecasService;

    /**
     * Instantiate a new UserController instance.
     */
    public function __construct(EbecasService $ebecasService)
    {
        $this->ebecasService = $ebecasService;
    }


    public function index()
    {
        $this->authorize('viewAny', Agent::class);
        return view('staff.agents.index');
    }

    /**
     * @throws Exception
     */
    public function getDataTableList(): JsonResponse
    {
        $this->authorize('viewAny', Agent::class);
        return Agent::getDataTable();
    }

    public function create()
    {
        $this->authorize('create', Agent::class);

        $agents_list = $this->ebecasService->getAllAgents();

        $agents_list = $agents_list['data'];

        return view('staff.agents.create',compact('agents_list'));

    }

    public function store(AgentRequest $request)
    {
        $this->authorize('create', Agent::class);
        $agent_id = $request->ebecas_id;

        $response = $this->ebecasService->getAgentById($agent_id);

        if(!$response['success']){
            return redirect()->route('staff.agents.create')->withError('There was an error retrieving the agent details from eBECAS. ' . $response['message']);
        }

        $agent_data = $response['data'];

        $agent = Agent::create([
            'name' => $agent_data['AgentName'],
            'ebecas_id'=>$agent_data['AgentId'],
        ]);

        return redirect()->route('staff.agents.show',$agent)->withStatus('Agent created!');

    }

    public function show(Agent $agent)
    {
        $this->authorize('view', $agent);

        $ebecas_data = $this->ebecasService->getAgentById($agent->ebecas_id);
        $ebecas_commissions = $this->ebecasService->getAgentCommissions($agent->ebecas_id);

        if(!$ebecas_data['success'] or !$ebecas_commissions['success']){
            return redirect()->route('staff.agents.index')->withError('There was an error retrieving the agent details from eBECAS. ' . $ebecas_data['message']);
        }

        $ebecas_data = $ebecas_data['data'];
        $ebecas_commissions = $ebecas_commissions['data'];

        return view('staff.agents.show',compact('agent','ebecas_data', 'ebecas_commissions'));

    }

    public function sync(Agent $agent)
    {
        $this->authorize('update', $agent);

        $response = $this->ebecasService->getAgentById($agent->ebecas_id);

        if(!$response['success']){
            return redirect()->route('staff.agents.show',$agent)->withError('There was an error retrieving the agent details from eBECAS. ' . $response['message']);
        }

        $ebecas_data = $response['data'];

        $agent->name = $ebecas_data['AgentName'];
        $agent->save();

        return redirect()->route('staff.agents.show',$agent)->withStatus('Agent details updated!');

    }

    /**
     * @param Agent $agent
     * @param AgentCommissionRequest $request
     * @return mixed
     * @throws AuthorizationException
     */
    public function commissionUpdate(Agent $agent, AgentCommissionRequest $request){

        $this->authorize('update', $agent);

        $agent->language_commission_id = $request->language_commission_id;
        $agent->vet_commission_id = $request->vet_commission_id;
        $agent->save();

        return redirect()->route('staff.agents.show',$agent)->withStatus('Agent details updated!');
    }


}
