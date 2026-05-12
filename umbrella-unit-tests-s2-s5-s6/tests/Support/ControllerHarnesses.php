<?php

declare(strict_types=1);

namespace UmbrellaTests\Support;

use DoctorController;
use DoctorsController;
use BookingsController;
use BookingController;
use PatientBookingsController;
use PatientBookingController;
use isDoctorBusyController;
use SpecialitiesController;
use SpecialityController;
use ServicesController;
use ServiceController;
use RoomsController;
use RoomController;
use ClinicsController;
use ClinicController;

class DoctorControllerHarness extends DoctorController
{
    use CaptureJsonEchoTrait;
}

class DoctorsControllerHarness extends DoctorsController
{
    use CaptureJsonEchoTrait;
}

class BookingsControllerHarness extends BookingsController
{
    use CaptureJsonEchoTrait;
}

class BookingControllerHarness extends BookingController
{
    use CaptureJsonEchoTrait;
}

class IsDoctorBusyControllerHarness extends isDoctorBusyController
{
    use CaptureJsonEchoTrait;
}

class PatientBookingsControllerHarness extends PatientBookingsController
{
    use CaptureJsonEchoTrait;
}

class PatientBookingControllerHarness extends PatientBookingController
{
    use CaptureJsonEchoTrait;
}

class SpecialitiesControllerHarness extends SpecialitiesController
{
    use CaptureJsonEchoTrait;
}

class SpecialityControllerHarness extends SpecialityController
{
    use CaptureJsonEchoTrait;
}

class ServicesControllerHarness extends ServicesController
{
    use CaptureJsonEchoTrait;
}

class ServiceControllerHarness extends ServiceController
{
    use CaptureJsonEchoTrait;
}

class RoomsControllerHarness extends RoomsController
{
    use CaptureJsonEchoTrait;
}

class RoomControllerHarness extends RoomController
{
    use CaptureJsonEchoTrait;
}

class ClinicsControllerHarness extends ClinicsController
{
    use CaptureJsonEchoTrait;
}

class ClinicControllerHarness extends ClinicController
{
    use CaptureJsonEchoTrait;
}
