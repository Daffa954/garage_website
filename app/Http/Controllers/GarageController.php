<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Garage;
use App\Services\GarageService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GarageController extends Controller
{
    public function __construct(
        protected GarageService $garageService
    ) {
    }

    public function index()
    {
        $role = session('user_role');

        $query = Garage::query();

        // Renter hanya melihat garage yang tersedia.
        if ($role === 'renter') {
            $query->where('status', 'available');
        }

        $garages = $query->latest()->get();

        return view('garage', compact('garages'));
    }

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

        // Harga kWh hanya berlaku untuk Electric Car.
        if ($data['category'] !== 'Electric Car') {
            $data['price_kwh'] = null;
        } elseif ($data['price_kwh'] === null) {
            return back()
                ->withErrors([
                    'price_kwh' => 'Electricity price per kWh is required for EV garages.',
                ])
                ->withInput();
        }

        $this->garageService->create($data);

        return back()->with(
            'success',
            'Garage created successfully.'
        );
    }

}