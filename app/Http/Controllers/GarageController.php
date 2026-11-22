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
    public function __construct(
        protected GarageService $garageService
    ) {
    }

    /**
     * Display a listing of all garages.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $data = $this->garageService->getGarageIndexData(session('user_id'));

        return view('garage', $data);
    }

    /**
     * Store a newly created garage facility.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',

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
            $this->garageService->create($data);

            return back()->with(
                'success',
                'Garage created successfully.'
            );
        } catch (\Throwable $e) {
            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

}