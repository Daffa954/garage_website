<?php

namespace App\Http\Controllers;

use App\Enums\VehicleCategory;
use App\Models\Category;
use App\Services\VehicleService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    /**
     * VehicleController constructor.
     *
     * @param VehicleService $vehicleService Service handling vehicle CRUD and querying operations.
     */
    public function __construct(
        protected VehicleService $vehicleService
    ) {
    }

    /**
     * Store a newly created vehicle record for the authenticated user.
     *
     * @param Request $request Incoming HTTP request containing brand, category, plate, and battery capacity.
     * @return \Illuminate\Http\RedirectResponse Redirect response with success flash message or errors.
     */
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
        try {
            $this->vehicleService->create($data);
            return redirect()->back()->with('success', 'Vehicle created successfully.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display a listing of vehicles owned by the authenticated user.
     *
     * @return \Illuminate\View\View User vehicle management view with vehicles paginator.
     */
    public function index()
    {
        $vehicles = $this->vehicleService->getAllVehiclesByUserID(
            session('user_id')
        );

        return view('user-vehicle', compact('vehicles'));
    }
}
