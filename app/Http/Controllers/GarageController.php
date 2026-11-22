<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Garage;
use App\Models\Vehicle;
use App\Services\GarageService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GarageController extends Controller
{
    /**
     * GarageController constructor.
     *
     * @param GarageService $garageService Service handling garage data queries and batch creation.
     */
    public function __construct(
        protected GarageService $garageService
    ) {
    }

    /**
     * Display a listing of all garages and the current user's available vehicles.
     *
     * @return \Illuminate\View\View Garage listing view with garages paginator and vehicles collection.
     */
    public function index()
    {
        $data = $this->garageService->getGarageIndexData(session('user_id'));

        return view('garage', $data);
    }

    /**
     * Store a newly created batch of garage facilities (Admin only).
     *
     * @param Request $request Incoming HTTP request with garage category, price, quantity, and EV price_kwh.
     * @return \Illuminate\Http\RedirectResponse Redirect response with success flash or validation errors.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'quantity' => 'nullable|integer|min:1|max:999',

            'price_kwh' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'category' => [
                'required',
                Rule::in([
                    'Bike',
                    'Motor Cycle',
                    'Car',
                    'Electric Car',
                ]),
            ],
        ]);

        $data['quantity'] = (int) ($data['quantity'] ?? 1);

        // Electricity price per kWh only applies to Electric Car garages.
        if ($data['category'] !== 'Electric Car') {
            $data['price_kwh'] = null;
        } elseif ($data['price_kwh'] === null) {
            return back()
                ->withErrors([
                    'price_kwh' => 'Electricity price per kWh is required for EV garages.',
                ])
                ->withInput();
        }

        try {
            $created = $this->garageService->createBatch($data);
            $count = $created->count();
            $codeMsg = $count > 1
                ? "{$created->first()->garage_code} to {$created->last()->garage_code}"
                : "{$created->first()->garage_code}";

            return back()->with(
                'success',
                "Successfully created {$count} garage(s) ({$codeMsg})."
            );
        } catch (\Throwable $e) {
            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }
}
