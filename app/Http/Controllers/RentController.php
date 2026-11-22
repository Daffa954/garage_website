<?php

namespace App\Http\Controllers;

use App\Models\EvCharge;
use App\Models\Garage;
use App\Models\Rent;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\RentService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RentController extends Controller
{
    public function __construct(
        protected RentService $rentService
    ) {
    }

    /**
     * Display a listing of rental transactions and rental reports.
     * Supports filtering by:
     * - Date Range (start_date and end_date, looping through rent_details to find the latest end_date)
     * - Garage Type (Per Tipe Garasi)
     * - Renter (Per Penyewa - for admin)
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $role = session('user_role');
        $userId = session('user_id');
        $isAdmin = $role === 'admin';

        $baseQuery = Rent::query();
        if (!$isAdmin) {
            $baseQuery->where('renter_id', $userId);
        }

        $totalCount = (clone $baseQuery)->count();
        $activeCount = (clone $baseQuery)->whereDate('end_date', '>=', today())->count();
        $completedCount = (clone $baseQuery)->whereDate('end_date', '<', today())->count();

        // Build report query with active filters
        $query = Rent::with(['renter', 'garage', 'vehicle', 'rentDetails', 'payments', 'evCharges'])
            ->when(!$isAdmin, fn($q) => $q->where('renter_id', $userId));

        // 1. Filter by Garage Type (Per Tipe Garasi)
        if ($request->filled('garage_type')) {
            $query->whereHas('garage', fn($g) => $g->where('category', $request->input('garage_type')));
        }

        // 2. Filter by Renter (Per Penyewa)
        if ($isAdmin && $request->filled('renter_id')) {
            $query->where('renter_id', $request->input('renter_id'));
        }

        // 3. Filter by Date Range (start_date & end_date) directly in database
        if ($request->filled('start_date')) {
            $query->whereDate('start_date', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('end_date', '<=', $request->input('end_date'));
        }

        // Aggregate Report Metrics across all matching records
        $reportCount = (clone $query)->count();
        $reportTotalDays = (clone $query)->sum('total_day');
        $reportRentalRevenue = (clone $query)->sum('total_price');

        $filteredRentIds = (clone $query)->pluck('id');
        $reportEvRevenue = EvCharge::whereIn('rent_id', $filteredRentIds)->sum('price_charge');
        $reportGrandTotal = $reportRentalRevenue + $reportEvRevenue;

        $isFiltered = $request->anyFilled(['start_date', 'end_date', 'garage_type', 'renter_id']);

        // Paginate results with persistent query string for pagination links
        $rents = (clone $query)->latest()->paginate(10)->withQueryString();

        $garageCategories = ['Car', 'Electric Car', 'Motor Cycle', 'Bike'];
        $renters = $isAdmin ? User::orderBy('name')->get(['id', 'name', 'email']) : collect();

        return view('rental', compact(
            'rents',
            'isAdmin',
            'totalCount',
            'activeCount',
            'completedCount',
            'reportCount',
            'reportTotalDays',
            'reportRentalRevenue',
            'reportEvRevenue',
            'reportGrandTotal',
            'isFiltered',
            'garageCategories',
            'renters'
        ));
    }

    /**
     * Store a new rental booking transaction.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $garage = Garage::findOrFail($request->input('garage_id'));
        $vehicle = Vehicle::whereKey($request->input('vehicle_id'))
            ->where('owner_id', session('user_id'))
            ->firstOrFail();

        // Check if vehicle is already parked in an active/occupied garage
        $isVehicleParked = $vehicle->rents()
            ->whereHas('garage', fn($q) => $q->where('status', '!=', 'available'))
            ->exists();

        if ($isVehicleParked) {
            return redirect()->back()->withErrors([
                'vehicle_id' => 'The selected vehicle is currently parked in another garage.',
            ])->withInput();
        }

        // Compatibility check using polymorphic method on Vehicle
        if (!$vehicle->canParkInGarage($garage)) {
            return redirect()->back()->withErrors([
                'vehicle_id' => "The selected vehicle ({$vehicle->brand} - {$vehicle->category}) cannot park in a {$garage->category} garage.",
            ])->withInput();
        }

        $validated = $request->validate([
            'garage_id' => 'required|exists:garages,id',
            'type' => 'nullable|string',
            'vehicle_id' => 'required|exists:vehicles,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'payment_receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'url_payment_receipt' => 'nullable|string',
            'note' => 'nullable|string|max:500',
            'ev_charge.current_battery' => 'nullable|numeric|between:0,100',
            'ev_charge.note' => 'nullable|string|max:255',
        ]);

        $currentBattery = isset($validated['ev_charge']['current_battery']) && $validated['ev_charge']['current_battery'] !== ''
            ? (float) $validated['ev_charge']['current_battery']
            : null;

        // EV charging is only created if:
        // - Garage is Electric Car
        // - Vehicle is Electric Car
        // - Battery is not full (less than 100%)
        if ($garage->category === 'Electric Car' && $vehicle->isElectric() && $currentBattery !== null && !$vehicle->isBatteryFull($currentBattery)) {
            $validated['ev_charge']['current_battery'] = $currentBattery;
        } else {
            unset($validated['ev_charge']);
        }

        // Process payment receipt upload
        if ($request->hasFile('payment_receipt')) {
            $path = $request->file('payment_receipt')->store('receipts', 'public');
            $validated['url_payment_receipt'] = '/storage/' . $path;
        } else {
            $validated['url_payment_receipt'] = $validated['url_payment_receipt'] ?? '';
        }

        // Pass payment receipt to EV charge if charging is configured
        if (!empty($validated['ev_charge']) && empty($validated['ev_charge']['url_payment_receipt'])) {
            $validated['ev_charge']['url_payment_receipt'] = $validated['url_payment_receipt'];
        }

        $renter = User::findOrFail(session('user_id'));

        try {
            $this->rentService->create($validated, $renter);

            return redirect()->back()->with('success', 'Garage rented successfully!');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Extend an existing rental contract (Admin only).
     *
     * @param Request $request
     * @param int $id Rent ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function extend(Request $request, int $id)
    {
        $rent = Rent::with('garage')->findOrFail($id);

        $validated = $request->validate([
            'new_end_date' => [
                'required',
                'date',
                'after:' . ($rent->end_date ? $rent->end_date->format('Y-m-d') : 'today'),
            ],
            'payment_receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'url_payment_receipt' => 'nullable|string',
            'note' => 'nullable|string|max:500',
        ]);

        // Process payment receipt upload if provided
        if ($request->hasFile('payment_receipt')) {
            $path = $request->file('payment_receipt')->store('receipts', 'public');
            $validated['url_payment_receipt'] = '/storage/' . $path;
        }

        try {
            $this->rentService->extend($rent, $validated);

            return redirect()->back()->with('success', 'Rental contract extended successfully!');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }
}
