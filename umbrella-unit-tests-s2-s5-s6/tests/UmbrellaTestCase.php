<?php

declare(strict_types=1);

namespace UmbrellaTests;

use PHPUnit\Framework\TestCase;
use Pixie\Connection;
use UmbrellaTests\Support\JsonEchoExit;
use UmbrellaTests\Support\SchemaInstaller;

abstract class UmbrellaTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        SchemaInstaller::install();
        $this->seedBaseline();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_GET'], $GLOBALS['_POST'], $GLOBALS['_PUT'], $GLOBALS['_DELETE'], $GLOBALS['_PATCH']);
        parent::tearDown();
    }

    protected function resetInputGlobals(): void
    {
        $GLOBALS['_GET'] = [];
        $GLOBALS['_POST'] = [];
        $GLOBALS['_PUT'] = [];
        $GLOBALS['_DELETE'] = [];
        $GLOBALS['_PATCH'] = [];
    }

    protected function setServerMethod(string $method): void
    {
        $_SERVER['REQUEST_METHOD'] = $method;
    }

    /**
     * Gọi method private controller; jsonecho() trong harness ném JsonEchoExit.
     */
    protected function invokePrivateJson(object $object, string $method): object
    {
        $ref = new \ReflectionMethod($object, $method);
        $ref->setAccessible(true);
        try {
            $ref->invoke($object);
        } catch (JsonEchoExit $e) {
            return $e->response;
        }
        throw new \LogicException('Controller did not finish via jsonecho(): ' . $method);
    }

    protected function tomorrowDate(): string
    {
        return date('Y-m-d', strtotime('+1 day'));
    }

    /**
     * Minimal rows so Doctor / Booking / Speciality flows can hit DB (SQLite mock).
     */
    protected function seedBaseline(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');

        $pdo->exec("INSERT INTO tn_specialities (id, name, description, image) VALUES (1, 'Chuyen khoa mac dinh', 'Mo ta', 'default_avatar.jpg')");
        $pdo->exec("INSERT INTO tn_specialities (id, name, description, image) VALUES (2, 'Chuyen khoa xoa duoc', 'Mo ta 2', 'default_avatar.jpg')");

        $pdo->exec("INSERT INTO tn_rooms (id, name, location) VALUES (1, 'Phong 1', 'Tang 1')");
        $pdo->exec("INSERT INTO tn_rooms (id, name, location) VALUES (2, 'Phong 2', 'Tang 2')");

        $pdo->exec("INSERT INTO tn_services (id, name, image, description) VALUES (1, 'Dich vu mac dinh', 'default_avatar.jpg', 'Mo ta dich vu')");

        $pdo->exec("INSERT INTO tn_patients (id, email, phone, password, name, gender, birthday, address, avatar, create_at, update_at) VALUES (1, 'p@test.com', '0900000001', 'x', 'Benh Nhan Mot', 0, '1990-01-01', '12 Le Loi Q1', '', '$now', '$now')");

        $pdo->exec("INSERT INTO tn_doctors (id, email, phone, password, name, description, price, role, active, avatar, create_at, update_at, speciality_id, room_id, recovery_token) VALUES (1, 'admin@test.com', '0900000002', 'x', 'Admin Bac Si', 'Mo ta', 150000, 'admin', 1, '', '$now', '$now', 1, 1, '')");
        $pdo->exec("INSERT INTO tn_doctors (id, email, phone, password, name, description, price, role, active, avatar, create_at, update_at, speciality_id, room_id, recovery_token) VALUES (2, 'bs@test.com', '0900000003', 'x', 'Nguyen Van Hai', 'Mo ta', 150000, 'member', 1, '', '$now', '$now', 1, 1, '')");
        $pdo->exec("INSERT INTO tn_doctors (id, email, phone, password, name, description, price, role, active, avatar, create_at, update_at, speciality_id, room_id, recovery_token) VALUES (7, 'hotro@test.com', '0900000008', 'x', 'Ho Tro Vien', 'Mo ta', 150000, 'supporter', 1, '', '$now', '$now', 1, 1, '')");

        $pdo->exec("INSERT INTO tn_clinics (id, name, address) VALUES (1, 'Phong kham mac dinh', '1 Duong ABC, Q1')");
    }

    protected function adminDoctorModel(): \DoctorModel
    {
        return \Controller::model('Doctor', 1);
    }

    protected function memberDoctorModel(): \DoctorModel
    {
        return \Controller::model('Doctor', 2);
    }

    protected function supporterDoctorModel(): \DoctorModel
    {
        return \Controller::model('Doctor', 7);
    }

    protected function patientModel(): \PatientModel
    {
        return \Controller::model('Patient', 1);
    }
}
