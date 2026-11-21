<?php
namespace App\Enums;

enum VehicleCategory: string
{
    case BIKE= 'Bike';
    case MOTORCYCLE = 'Motor Cycle';
    case CAR = 'Car';
    case ELECTRIC_CAR = 'Electric Car';
}