<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Ho_Chi_Minh');

$WWW_ROOT = dirname(__DIR__);
$API_ROOT = $WWW_ROOT . DIRECTORY_SEPARATOR . 'PTIT-Do-An-Tot-Nghiep' . DIRECTORY_SEPARATOR . 'api';
$APP_PATH = $API_ROOT . DIRECTORY_SEPARATOR . 'app';

if (!is_dir($APP_PATH)) {
    throw new RuntimeException('API not found at: ' . $APP_PATH);
}

define('ROOTPATH', $API_ROOT);
define('APPPATH', $APP_PATH);
define('APPURL', 'http://umbrella-test.local');
define('BASEPATH', '');
define('UPLOAD_PATH', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'umbrella_unit_uploads');
define('TABLE_PREFIX', 'tn_');

define('TABLE_SPECIALITIES', 'specialities');
define('TABLE_DOCTORS', 'doctors');
define('TABLE_BOOKINGS', 'booking');
define('TABLE_APPOINTMENTS', 'appointments');
define('TABLE_PATIENTS', 'patients');
define('TABLE_SERVICES', 'services');
define('TABLE_NOTIFICATIONS', 'notifications');
define('TABLE_ROOMS', 'rooms');
define('TABLE_DOCTOR_AND_SERVICE', 'doctor_and_service');
define('TABLE_CLINICS', 'clinics');
define('TABLE_DRUGS', 'drugs');
define('TABLE_TREATMENTS', 'treatments');
define('TABLE_APPOINTMENT_RECORDS', 'appointment_records');
define('TABLE_BOOKING_PHOTOS', 'booking_photo');

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'test_mock');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_ENCODING', 'utf8');

require_once $APP_PATH . '/vendor/autoload.php';
require_once $APP_PATH . '/autoload.php';
require_once $APP_PATH . '/helpers/helpers.php';

if (!is_dir(UPLOAD_PATH)) {
    @mkdir(UPLOAD_PATH, 0777, true);
}

$config = [
    'driver' => 'sqlite',
    'database' => ':memory:',
];

new \Pixie\Connection('sqlite', $config, 'DB');

require_once __DIR__ . '/tests/Support/SchemaInstaller.php';
require_once __DIR__ . '/tests/Support/JsonEchoExit.php';
require_once __DIR__ . '/tests/Support/CaptureJsonEchoTrait.php';
require_once __DIR__ . '/tests/Support/ControllerHarnesses.php';

\UmbrellaTests\Support\SchemaInstaller::install();
