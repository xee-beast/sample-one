<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PaymentMethodRequest;
use App\Models\PaymentMethod;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;

class PaymentMethodController extends Controller
{
    /**
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('viewAny', PaymentMethod::class);
        return view('settings.payment-methods.index');
    }

    /**
     * @return JsonResponse
     * @throws Exception
     */
    public function getDataTableList(): JsonResponse
    {
        $this->authorize('viewAny', PaymentMethod::class);
        return PaymentMethod::getDataTable();
    }

    /**
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', PaymentMethod::class);

        return $this->edit(new PaymentMethod());
    }

    /**
     * @param PaymentMethodRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(PaymentMethodRequest $request): JsonResponse
    {
        $this->authorize('create', PaymentMethod::class);

        $data = $request->validated();

        PaymentMethod::create($data);

        Session::flash('status' , 'Record created successfully');
        return response()->json([
            'success'=> true,
            'redirect_route' => route('staff.settings.fees.payment-methods.index')
        ]);
    }

    /**
     * @param PaymentMethod $payment_method
     * @return View
     * @throws AuthorizationException
     */
    public function show(PaymentMethod $payment_method): View
    {
        $this->authorize('view', $payment_method);

        return $this->edit($payment_method);
    }

    /**
     * @param PaymentMethod $payment_method
     * @return View
     * @throws AuthorizationException
     */
    public function edit(PaymentMethod $payment_method): View
    {
        $this->authorize('update', $payment_method);
        return view('settings.payment-methods.edit',compact('payment_method'));
    }

    /**
     * @param PaymentMethodRequest $request
     * @param PaymentMethod $payment_method
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(PaymentMethodRequest $request, PaymentMethod $payment_method): JsonResponse
    {
        $this->authorize('update', $payment_method);

        $data = $request->validated();

        $payment_method->fill($data);
        $payment_method->save();

        Session::flash('status' , 'Record updated successfully');
        return response()->json([
            'success'=> true,
            'redirect_route' => route('staff.settings.fees.payment-methods.index')
        ]);
    }

    /**
     * @param PaymentMethod $payment_method
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(PaymentMethod $payment_method): RedirectResponse
    {
        $this->authorize('destroy', $payment_method);
        if($payment_method->hasResources()){
            return redirect()->back()->with('error','Payment method cannot be deleted as its attached to multiple application(s)');
        }

        $payment_method->delete();

        return redirect()->route('staff.settings.fees.payment-methods.index')->withStatus('Payment Method deleted!');
    }

}
