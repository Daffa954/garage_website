<?php

namespace App\Http\Controllers;

use App\Enums\VehicleCategory;
use App\Models\Category;
use App\Services\VehicleService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    //
    public function __construct(
        protected VehicleService $vehicleService
    ) {
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'brand' => 'required|string|max:255',
            'license_plate' => 'nullable|string|max:20',
            'category' => [
                'required',
                Rule::enum(VehicleCategory::class),
            ],
            'total_kwh' => 'required_if:category,Electric Car|nullable|numeric|min:0.01',
        ]);

        $data['owner_id'] = session('user_id');
        $this->vehicleService->create($data);
        return redirect()->back()->with('success', 'Vehicle created successfully.');
    }

    public function index()
    {
        $vehicles = $this->vehicleService->getAllVehiclesByUserID(
            session('user_id')
        );

        return view('user-vehicle', compact('vehicles'));
    }


}
