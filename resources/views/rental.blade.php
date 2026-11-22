@extends('layouts.app')
@php
    $role = session('user_role');
    $isAdmin = $role === 'admin';
    $isRenter = $role === 'user';
@endphp

@section('title', $isAdmin ? 'Laporan & Seluruh Persewaan - Sewa Garasi' : 'Riwayat Persewaan Saya - Sewa Garasi')

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
            const parts = rawEnd.split('-');
            const currentEnd = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
            currentEnd.setDate(currentEnd.getDate() + 1);
            const pad = n => String(n).padStart(2, '0');
            this.minEndDate = `${currentEnd.getFullYear()}-${pad(currentEnd.getMonth() + 1)}-${pad(currentEnd.getDate())}`;
            this.newEndDate = this.minEndDate;
            this.showExtendModal = true;
        },
        formatDate(dateStr) {
            if (!dateStr) return '-';
            const d = new Date(dateStr.split('T')[0]);
            return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
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
            body {
                background: white !important;
                color: black !important;
            }
            .no-print, header, footer, button, select, input {
                display: none !important;
            }
            .print-only {
                display: block !important;
            }
            .shadow-sm, .shadow-xl, .shadow-2xl {
                box-shadow: none !important;
            }
            .border {
                border-color: #ddd !important;
            }
        }
        @media screen {
            .print-only {
                display: none !important;
            }
        }
    </style>
@endsection
