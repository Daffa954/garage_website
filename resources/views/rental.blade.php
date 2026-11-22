@extends('layouts.app')
@php
    $role = session('user_role');
    $isAdmin = $role === 'admin';
    $isRenter = $role === 'user';
@endphp

@section('title', $isAdmin ? 'All Rentals & Report - GarageApp' : 'My Rentals & Report - GarageApp')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-8" x-data="{
        showDetailModal: false,
        showExtendModal: false,
        selectedRent: null,
        newEndDate: '',
        minEndDate: '',
        get daysDifference() {
            if (!this.selectedRent || !this.newEndDate) return 0;
            const current = new Date((this.selectedRent.end_date || '').split('T')[0]);
            const next = new Date(this.newEndDate);
            const diffTime = next - current;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            return diffDays > 0 ? diffDays : 0;
        },
        openDetail(rent) {
            this.selectedRent = rent;
            this.showDetailModal = true;
        },
        openExtend(rent) {
            this.selectedRent = rent;
            const rawEnd = (rent.end_date || '').split('T')[0];
            const currentEnd = new Date(rawEnd);
            currentEnd.setDate(currentEnd.getDate() + 1);
            this.minEndDate = currentEnd.toISOString().split('T')[0];
            this.newEndDate = this.minEndDate;
            this.showExtendModal = true;
        },
        formatDate(dateStr) {
            if (!dateStr) return '-';
            const d = new Date(dateStr.split('T')[0]);
            return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        },
        formatCurrency(amount) {
            return 'Rp ' + Number(amount || 0).toLocaleString('id-ID');
        },
        get evChargeTotal() {
            if (!this.selectedRent) return 0;
            const charges = this.selectedRent.ev_charges || this.selectedRent.evCharges || [];
            return charges.reduce((acc, curr) => acc + Number(curr.price_charge || 0), 0);
        },
        get grandTotal() {
            if (!this.selectedRent) return 0;
            return Number(this.selectedRent.total_price || 0) + this.evChargeTotal;
        }
    }">

        {{-- Page Header & Printable Header --}}
        <x-rental.header
            :is-admin="$isAdmin"
            :is-filtered="$isFiltered"
            :renters="$renters"
        />

        {{-- Filter & Report Criteria Form --}}
        <x-rental.filter-form
            :is-admin="$isAdmin"
            :is-filtered="$isFiltered"
            :garage-categories="$garageCategories"
            :renters="$renters"
        />

        {{-- Report Financial Summary Metrics --}}
        <x-rental.report-summary
            :report-count="$reportCount"
            :report-total-days="$reportTotalDays"
            :report-rental-revenue="$reportRentalRevenue"
            :report-ev-revenue="$reportEvRevenue"
            :report-grand-total="$reportGrandTotal"
            :is-filtered="$isFiltered"
        />

        {{-- Rental Transactions List Table --}}
        <x-rental.rental-table
            :rents="$rents"
            :is-admin="$isAdmin"
        />

        {{-- Detail Modal --}}
        <x-rental.detail-modal />

        {{-- Extend Modal (Admin Only) --}}
        @if ($isAdmin)
            <x-rental.extend-modal />
        @endif

    </div>

    {{-- Print Styles --}}
    <style>
        @media print {
            header, nav, footer, .no-print, [role="navigation"] {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                color: #000000 !important;
                font-size: 11px !important;
            }
            .print-only {
                display: block !important;
            }
            .max-w-7xl {
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .shadow-sm, .shadow-md, .shadow-lg, .shadow {
                box-shadow: none !important;
            }
            .border, .border-b, .border-t {
                border-color: #cbd5e1 !important;
            }
            table {
                width: 100% !important;
                border-collapse: collapse !important;
            }
            th, td {
                border: 1px solid #e2e8f0 !important;
                padding: 6px 8px !important;
            }
        }
        @media screen {
            .print-only {
                display: none !important;
            }
        }
    </style>
@endsection
