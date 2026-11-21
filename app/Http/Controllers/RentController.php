<?php

namespace App\Http\Controllers;

use App\Models\Garage;
use App\Models\Rent;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\RentService;
use Illuminate\Http\Request;

class RentController extends Controller
{
    public function __construct(
        protected RentService $rentService
    ) {
    }

    /**
     * Display a listing of rental transactions.
     * Admin sees all records, regular user sees only their own.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $role = session('user_role');
        $userId = session('user_id');
        $isAdmin = $role === 'admin';

        $query = Rent::with(['renter', 'garage', 'vehicle', 'rentDetails', 'payments', 'evCharges'])
            ->latest();

        if (!$isAdmin) {
            $query->where('renter_id', $userId);
        }

        $rents = $query->get();

        return view('rental', compact('rents', 'isAdmin'));
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

        // Validate that vehicle category matches garage category
        if ($vehicle->category !== $garage->category) {
            return redirect()->back()->withErrors([
                'vehicle_id' => "The selected vehicle category ({$vehicle->category}) does not match the garage category ({$garage->category}).",
            ])->withInput();
        }

        $validated = $request->validate([
            'garage_id' => 'required|exists:garages,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'payment_receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'url_payment_receipt' => 'nullable|string',
            'note' => 'nullable|string|max:500',
            'ev_charge.current_battery' => [
                $garage->category === 'Electric Car' ? 'required' : 'nullable',
                'numeric',
                'between:0,100',
            ],
            'ev_charge.note' => 'nullable|string|max:255',
        ]);

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

        $this->rentService->create($validated, $renter);

        return redirect()->back()->with('success', 'Garage rented successfully!');
    }
}
