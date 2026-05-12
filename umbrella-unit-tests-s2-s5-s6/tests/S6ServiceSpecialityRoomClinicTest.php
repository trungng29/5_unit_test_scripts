<?php

declare(strict_types=1);

namespace UmbrellaTests;

use Pixie\Connection;
use UmbrellaTests\Support\SpecialitiesControllerHarness;
use UmbrellaTests\Support\SpecialityControllerHarness;
use UmbrellaTests\Support\ServicesControllerHarness;
use UmbrellaTests\Support\ServiceControllerHarness;
use UmbrellaTests\Support\RoomsControllerHarness;
use UmbrellaTests\Support\RoomControllerHarness;
use UmbrellaTests\Support\ClinicsControllerHarness;
use UmbrellaTests\Support\ClinicControllerHarness;

/**
 * S6 – Service, Speciality, Room, Clinic.
 */
final class S6ServiceSpecialityRoomClinicTest extends UmbrellaTestCase
{
    /** @test TC_S6_01 */
    public function test_TC_S6_01_specialitiesSave_new_returnsSuccess(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'name' => 'Tim Mach',
            'description' => 'Mo ta chuyen khoa',
        ];
        $c = new SpecialitiesControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(1, $r->result);
    }

    /** @test TC_S6_02 Tên speciality rỗng */
    public function test_TC_S6_02_specialitiesSave_emptyName_returnsMissingField(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'name' => '',
            'description' => 'X',
        ];
        $c = new SpecialitiesControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsString('Missing field', $r->msg);
        $this->assertStringContainsString('name', $r->msg);
    }

    /** @test TC_S6_03 */
    public function test_TC_S6_03_specialityGetById_valid(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new SpecialityControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 1;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getById');
        $this->assertSame(1, $r->result);
        $this->assertSame('Chuyen khoa mac dinh', $r->data->name);
    }

    /** @test TC_S6_04 */
    public function test_TC_S6_04_specialityUpdate_admin_ok(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('PUT');
        $GLOBALS['_PUT'] = [
            'name' => 'Noi than',
            'description' => 'Cap nhat mo ta',
        ];
        $c = new SpecialityControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'update');
        $this->assertSame(1, $r->result);
        $this->assertSame('Noi than', $r->data->name);
    }

    /** @test TC_S6_05 */
    public function test_TC_S6_05_specialityDelete_whenDoctorsLinked_blocked(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $pdo->exec('UPDATE tn_doctors SET speciality_id = 2 WHERE id = 2');

        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new SpecialityControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'delete');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsString('doctors', $r->msg);
    }

    /** @test TC_S6_58 */
    public function test_TC_S6_58_specialityUpdateAvatar_noFile_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = ['action' => 'avatar'];
        $c = new SpecialityControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 1;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());

        try {
            $c->process();
        } catch (\UmbrellaTests\Support\JsonEchoExit $e) {
            $r = $e->response;
            $this->assertSame(0, $r->result);
            $this->assertStringContainsString('Photo is not received', $r->msg);
            return;
        }

        $this->fail('SpecialityController::process() did not finish via jsonecho()');
    }

    /** @test TC_S6_06 */
    public function test_TC_S6_06_servicesSave_new_ok(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'name' => 'Kham da lieu',
            'description' => 'DV da lieu',
        ];
        $c = new ServicesControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(1, $r->result);
    }

    /** @test TC_S6_07 */
    public function test_TC_S6_07_serviceGetById_ok(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new ServiceControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 1;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getById');
        $this->assertSame(1, $r->result);
    }

    /** @test TC_S6_08 */
    public function test_TC_S6_08_roomsSave_new_ok(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'name' => 'Phong chup XQuang',
            'location' => 'Tang ham',
        ];
        $c = new RoomsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(1, $r->result);
    }

    /** @test TC_S6_09 */
    public function test_TC_S6_09_roomDelete_emptyRoom_ok(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new RoomControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'delete');
        $this->assertSame(1, $r->result);
    }

    /** @test TC_S6_10 */
    public function test_TC_S6_10_clinicsSave_valid_ok(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'name' => 'Benh Vien Bach Mai',
            'address' => '84 Pho Duong Thanh Cong, Q Ba Dinh',
        ];
        $c = new ClinicsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(1, $r->result);
    }

    /** @test TC_S6_11 */
    public function test_TC_S6_11_clinicGetById_ok(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new ClinicControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 1;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getById');
        $this->assertSame(1, $r->result);
    }

    /** @test TC_S6_12 Service không tồn tại */
    public function test_TC_S6_12_serviceGetById_missing_returnsNotAvailable(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new ServiceControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 88888;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getById');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('not available', $r->msg);
    }

    /** @test TC_S6_13 Member không tạo speciality */
    public function test_TC_S6_13_specialitiesSave_member_forbidden(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'name' => 'Than Kinh',
            'description' => 'Mo ta',
        ];
        $c = new SpecialitiesControllerHarness();
        $c->setVariable('AuthUser', $this->memberDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('not admin', $r->msg);
    }

    /** @test TC_S6_14 duplicate speciality name */
    public function test_TC_S6_14_specialitiesSave_duplicateName_rejected(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'name' => 'Chuyen khoa mac dinh',
            'description' => 'Trung ten',
        ];
        $c = new SpecialitiesControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(0, $r->result);
    }

    /** @test TC_S6_15 Không xóa phòng mặc định id=1 */
    public function test_TC_S6_15_roomDelete_defaultRoom_blocked(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new RoomControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 1;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'delete');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('default', $r->msg);
    }

    /** @test TC_S6_16 room getById non-admin blocked */
    public function test_TC_S6_16_roomGetById_memberBlocked(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new RoomControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 1;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->memberDoctorModel());
        $r = $this->invokePrivateJson($c, 'getById');
        $this->assertSame(0, $r->result);
    }

    /** @test TC_S6_17 Tên bệnh viện có ký tự đặc biệt */
    public function test_TC_S6_17_clinicsSave_invalidHospitalName_rejected(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'name' => 'BV@@@',
            'address' => '1 Duong XYZ, Q1',
        ];
        $c = new ClinicsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('letters', $r->msg);
    }

    /** @test TC_S6_18 Tạo speciality hợp lệ */
    public function test_TC_S6_18_specialitiesSave_valid_returnsSuccess(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'name' => 'Ho hap',
            'description' => 'Mo ta ho hap',
        ];
        $c = new SpecialitiesControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(1, $r->result);
    }

    /** @test TC_S6_19 Trùng tên service */
    public function test_TC_S6_19_servicesSave_duplicateName_rejected(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'name' => 'Dich vu mac dinh',
            'description' => 'Trung',
        ];
        $c = new ServicesControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('exists', $r->msg);
    }

    /** @test TC_S6_20 Clinic id không tồn tại */
    public function test_TC_S6_20_clinicGetById_missing_returnsNotAvailable(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new ClinicControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 999;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getById');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('not available', $r->msg);
    }

    // --- ServicesController: getAll / save (bổ sung) ---

    /** @test TC_S6_21 */
    public function test_TC_S6_21_servicesGetAll_returnsSeededService(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new ServicesControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getAll');
        $this->assertSame(1, $r->result);
        $this->assertGreaterThanOrEqual(1, $r->quantity);
        $this->assertNotEmpty($r->data);
        $names = array_column($r->data, 'name');
        $this->assertContains('Dich vu mac dinh', $names);
    }

    /** @test TC_S6_22 */
    public function test_TC_S6_22_servicesGetAll_searchPrefix_filters(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $GLOBALS['_GET'] = ['search' => 'Dich'];
        $c = new ServicesControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getAll');
        $this->assertSame(1, $r->result);
        foreach ($r->data as $row) {
            $name = is_array($row) ? $row['name'] : $row->name;
            $this->assertStringStartsWith('Dich', $name);
        }
    }

    /** @test TC_S6_23 */
    public function test_TC_S6_23_servicesGetAll_lengthOne_returnsAtMostOne(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $GLOBALS['_GET'] = ['length' => 1, 'start' => 0];
        $c = new ServicesControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getAll');
        $this->assertSame(1, $r->result);
        $this->assertLessThanOrEqual(1, count($r->data));
    }

    /** @test TC_S6_24 */
    public function test_TC_S6_24_servicesSave_missingName_returnsMissingField(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = ['description' => 'Khong co ten'];
        $c = new ServicesControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsString('name', $r->msg);
    }

    /** @test TC_S6_25 */
    public function test_TC_S6_25_servicesSave_member_forbidden(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'name' => 'Dich Vu Member',
            'description' => 'X',
        ];
        $c = new ServicesControllerHarness();
        $c->setVariable('AuthUser', $this->memberDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(0, $r->result);
    }

    /** @test TC_S6_26 */
    public function test_TC_S6_26_servicesSave_returnsCreatedPayload(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'name' => 'Noi soi da day',
            'description' => 'Kham noi soi',
        ];
        $c = new ServicesControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(1, $r->result);
        $this->assertSame('Noi soi da day', $r->data->name);
        $this->assertObjectHasProperty('id', $r->data);
    }

    // --- ServiceController: getById / update / delete ---

    /** @test TC_S6_27 */
    public function test_TC_S6_27_serviceGetById_missingRouteId_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new ServiceControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getById');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('required', $r->msg);
    }

    /** @test TC_S6_28 */
    public function test_TC_S6_28_serviceGetById_returnsDescriptionAndImage(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new ServiceControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 1;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getById');
        $this->assertSame(1, $r->result);
        $this->assertObjectHasProperty('description', $r->data);
        $this->assertObjectHasProperty('image', $r->data);
    }

    /** @test TC_S6_29 */
    public function test_TC_S6_29_serviceUpdate_admin_valid_returnsSuccess(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('PUT');
        $GLOBALS['_PUT'] = [
            'name' => 'Dich vu mac dinh',
            'description' => 'Mo ta cap nhat unit test',
        ];
        $c = new ServiceControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 1;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'update');
        $this->assertSame(1, $r->result);
        $this->assertStringContainsStringIgnoringCase('updated', $r->msg);
    }

    /** @test TC_S6_30 */
    public function test_TC_S6_30_serviceUpdate_member_forbidden(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('PUT');
        $GLOBALS['_PUT'] = [
            'name' => 'Dich vu mac dinh',
            'description' => 'Member thu',
        ];
        $c = new ServiceControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 1;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->memberDoctorModel());
        $r = $this->invokePrivateJson($c, 'update');
        $this->assertSame(0, $r->result);
    }

    /** @test TC_S6_31 */
    public function test_TC_S6_31_serviceUpdate_missingDescription_returnsMissingField(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('PUT');
        $GLOBALS['_PUT'] = ['name' => 'Ten DV'];
        $c = new ServiceControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 1;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'update');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsString('description', $r->msg);
    }

    /** @test TC_S6_32 */
    public function test_TC_S6_32_serviceUpdate_unknownId_returnsNotAvailable(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('PUT');
        $GLOBALS['_PUT'] = [
            'name' => 'X',
            'description' => 'Y',
        ];
        $c = new ServiceControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 99999;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'update');
        $this->assertSame(0, $r->result);
    }

    /** @test TC_S6_33 */
    public function test_TC_S6_33_serviceDelete_defaultService_blocked(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new ServiceControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 1;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'delete');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('default', $r->msg);
    }

    /** @test TC_S6_34 */
    public function test_TC_S6_34_serviceDelete_member_forbidden(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $pdo->exec("INSERT INTO tn_services (id, name, image, description) VALUES (50, 'DV Xoa Test', 'default_avatar.jpg', 'X')");

        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new ServiceControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 50;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->memberDoctorModel());
        $r = $this->invokePrivateJson($c, 'delete');
        $this->assertSame(0, $r->result);
    }

    /** @test TC_S6_35 */
    public function test_TC_S6_35_serviceDelete_whenBookingExists_blocked(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');
        $d = $this->tomorrowDate();
        $pdo->exec("INSERT INTO tn_services (id, name, image, description) VALUES (51, 'DV Co Booking', 'default_avatar.jpg', 'X')");
        $pdo->exec("INSERT INTO tn_booking (id, doctor_id, patient_id, service_id, booking_name, booking_phone, name, gender, birthday, address, reason, appointment_date, appointment_time, status, create_at, update_at) VALUES (500, 0, 1, 51, 'Le Van A', '0901111111', 'Le Van A', 0, '1990-01-01', '1 ABC', '', '$d', '10:00', 'verified', '$now', '$now')");

        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new ServiceControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 51;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'delete');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('booking', $r->msg);
    }

    /** @test TC_S6_36 */
    public function test_TC_S6_36_serviceDelete_whenDoctorLinked_blocked(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $pdo->exec("INSERT INTO tn_services (id, name, image, description) VALUES (52, 'DV Gan Bac Si', 'default_avatar.jpg', 'X')");
        $pdo->exec('INSERT INTO tn_doctor_and_service (id, doctor_id, service_id) VALUES (900, 2, 52)');

        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new ServiceControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 52;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'delete');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('doctor', $r->msg);
    }

    /** @test TC_S6_37 */
    public function test_TC_S6_37_serviceDelete_unusedService_ok(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $pdo->exec("INSERT INTO tn_services (id, name, image, description) VALUES (53, 'DV Xoa Duoc', 'default_avatar.jpg', 'Khong gan')");

        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new ServiceControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 53;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'delete');
        $this->assertSame(1, $r->result);
        $this->assertFalse(\Controller::model('Service', 53)->isAvailable());
    }

    /** @test TC_S6_38 */
    public function test_TC_S6_38_serviceDelete_unknownId_returnsNotAvailable(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new ServiceControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 77777;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'delete');
        $this->assertSame(0, $r->result);
    }

    /** @test TC_S6_59 */
    public function test_TC_S6_59_serviceUpdateAvatar_noFile_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = ['action' => 'avatar'];
        $c = new ServiceControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 1;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());

        try {
            $c->process();
        } catch (\UmbrellaTests\Support\JsonEchoExit $e) {
            $r = $e->response;
            $this->assertSame(0, $r->result);
            $this->assertStringContainsString('Photo is not received', $r->msg);
            return;
        }

        $this->fail('ServiceController::process() did not finish via jsonecho()');
    }

    /** @test TC_S6_76 */
    public function test_TC_S6_76_servicesProcess_get_dispatchesToGetAll(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new ServicesControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());

        try {
            $c->process();
        } catch (\UmbrellaTests\Support\JsonEchoExit $e) {
            $r = $e->response;
            $this->assertSame(1, $r->result);
            $this->assertIsArray($r->data);
            return;
        }

        $this->fail('ServicesController::process() did not finish via jsonecho()');
    }

    /** @test TC_S6_77 */
    public function test_TC_S6_77_specialitiesProcess_get_dispatchesToGetAll(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new SpecialitiesControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());

        try {
            $c->process();
        } catch (\UmbrellaTests\Support\JsonEchoExit $e) {
            $r = $e->response;
            $this->assertSame(1, $r->result);
            $this->assertIsArray($r->data);
            return;
        }

        $this->fail('SpecialitiesController::process() did not finish via jsonecho()');
    }

    /** @test TC_S6_78 */
    public function test_TC_S6_78_roomsProcess_get_dispatchesToGetAll(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new RoomsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());

        try {
            $c->process();
        } catch (\UmbrellaTests\Support\JsonEchoExit $e) {
            $r = $e->response;
            $this->assertSame(1, $r->result);
            $this->assertIsArray($r->data);
            return;
        }

        $this->fail('RoomsController::process() did not finish via jsonecho()');
    }

    /** @test TC_S6_79 */
    public function test_TC_S6_79_clinicsProcess_get_dispatchesToGetAll(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new ClinicsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());

        try {
            $c->process();
        } catch (\UmbrellaTests\Support\JsonEchoExit $e) {
            $r = $e->response;
            $this->assertSame(1, $r->result);
            $this->assertIsArray($r->data);
            return;
        }

        $this->fail('ClinicsController::process() did not finish via jsonecho()');
    }

    /** @test TC_S6_39 */
    public function test_TC_S6_39_servicesGetAll_searchNoMatch_returnsEmptyData(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $GLOBALS['_GET'] = ['search' => 'ZZZNOMATCH999'];
        $c = new ServicesControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getAll');
        $this->assertSame(1, $r->result);
        $this->assertSame(0, $r->quantity);
        $this->assertCount(0, $r->data);
    }

    /** @test TC_S6_40 */
    public function test_TC_S6_40_servicesSave_emptyStringName_treatedAsMissing(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'name' => '',
            'description' => 'X',
        ];
        $c = new ServicesControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(0, $r->result);
    }

    /**
     * Nghiệp vụ phân trang: `quantity` = tổng số records (trước LIMIT) để frontend tính số trang.
     *
     * @test TC_S6_41
     */
    public function test_TC_S6_41_regreport_servicesGetAll_quantityEqualsPageSize(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $pdo->exec("INSERT INTO tn_services (id, name, image, description) VALUES (60, 'DV Demo A', 'default_avatar.jpg', 'A')");
        $pdo->exec("INSERT INTO tn_services (id, name, image, description) VALUES (61, 'DV Demo B', 'default_avatar.jpg', 'B')");

        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $GLOBALS['_GET'] = ['length' => 1, 'start' => 0];
        $c = new ServicesControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getAll');
        $this->assertSame(1, $r->result);
        $this->assertCount(1, $r->data);
        $this->assertSame(3, (int) $r->quantity, 'quantity phải là tổng số service (trước LIMIT) để frontend tính phân trang');
    }

    /**
     * BUG NGHIỆP VỤ: danh sách service (ServicesController::getAll) chỉ dành cho admin/supporter,
     * không cho member truy cập endpoint quản trị.
     *
     * @test TC_S6_42
     */
    public function test_TC_S6_42_servicesGetAll_memberMustBeForbidden(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new ServicesControllerHarness();
        $c->setVariable('AuthUser', $this->memberDoctorModel());
        $r = $this->invokePrivateJson($c, 'getAll');

        $this->assertSame(
            0,
            $r->result,
            'BUG NGHIỆP VỤ: member không được phép list services qua endpoint quản trị.'
        );
    }

    /**
     * BUG NGHIỆP VỤ: danh sách speciality (SpecialitiesController::getAll) chỉ dành cho admin/supporter,
     * không cho member truy cập endpoint quản trị.
     *
     * @test TC_S6_43
     */
    public function test_TC_S6_43_specialitiesGetAll_memberMustBeForbidden(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new SpecialitiesControllerHarness();
        $c->setVariable('AuthUser', $this->memberDoctorModel());
        $r = $this->invokePrivateJson($c, 'getAll');

        $this->assertSame(
            0,
            $r->result,
            'BUG NGHIỆP VỤ: member không được phép list specialities qua endpoint quản trị.'
        );
    }
    /** @test TC_S6_44 */
    public function test_TC_S6_44_specialitiesGetAll_admin_returnsQuantityAndDoctorCount(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new SpecialitiesControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getAll');

        $this->assertSame(1, $r->result);
        $this->assertGreaterThanOrEqual(2, (int) $r->quantity);
        $this->assertNotEmpty($r->data);
        $this->assertObjectHasProperty('doctor_quantity', $r->data[0]);
    }

    /** @test TC_S6_45 */
    public function test_TC_S6_45_specialityDelete_defaultSpeciality_blocked(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new SpecialityControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 1;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'delete');

        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('default', $r->msg);
    }

    /** @test TC_S6_46 */
    public function test_TC_S6_46_specialityDelete_unknownId_returnsNotAvailable(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new SpecialityControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 9999;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'delete');

        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('not available', $r->msg);
    }

    /** @test TC_S6_47 */
    public function test_TC_S6_47_roomsGetAll_adminFilterBySpeciality_returnsRows(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $GLOBALS['_GET'] = ['speciality_id' => 1];
        $c = new RoomsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getAll');

        $this->assertSame(1, $r->result);
        $this->assertGreaterThanOrEqual(1, (int) $r->quantity);
        foreach ($r->data as $row) {
            $this->assertObjectHasProperty('doctor_quantity', $row);
        }
    }

    /** @test TC_S6_48 */
    public function test_TC_S6_48_roomUpdate_admin_valid_updatesDb(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('PUT');
        $GLOBALS['_PUT'] = [
            'name' => 'Phong 2 Moi',
            'location' => 'Tang 9',
        ];
        $c = new RoomControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'update');

        $this->assertSame(1, $r->result);
        $room = \Controller::model('Room', 2);
        $this->assertSame('Phong 2 Moi', $room->get('name'));
        $this->assertSame('Tang 9', $room->get('location'));
    }

    /** @test TC_S6_49 */
    public function test_TC_S6_49_roomDelete_whenDoctorsLinked_blocked(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $pdo->exec('UPDATE tn_doctors SET room_id = 2 WHERE id = 2');

        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new RoomControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'delete');

        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('doctors', $r->msg);
    }

    /**
     * @test TC_S6_50
     * Business expectation: quantity must be total rows before LIMIT for paging.
     */
    public function test_TC_S6_50_clinicsGetAll_quantityShouldBeTotalBeforeLimit(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $pdo->exec("INSERT INTO tn_clinics (id, name, address) VALUES (2, 'Phong Kham A', '2 Duong A, Q1')");
        $pdo->exec("INSERT INTO tn_clinics (id, name, address) VALUES (3, 'Phong Kham B', '3 Duong B, Q1')");

        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $GLOBALS['_GET'] = ['length' => 1, 'start' => 0];
        $c = new ClinicsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getAll');

        $this->assertSame(1, $r->result);
        $this->assertCount(1, $r->data);
        $this->assertSame(
            3,
            (int) $r->quantity,
            'BUG NGHIEP VU: quantity phai la tong clinic truoc LIMIT de frontend phan trang dung.'
        );
    }

    /** @test TC_S6_51 */
    public function test_TC_S6_51_clinicUpdate_admin_valid_updatesDb(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('PUT');
        $GLOBALS['_PUT'] = [
            'name' => 'Benh Vien Moi',
            'address' => '99 Duong Moi, Q1',
        ];
        $c = new ClinicControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 1;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'update');

        $this->assertSame(1, $r->result);
        $clinic = \Controller::model('Clinic', 1);
        $this->assertSame('Benh Vien Moi', $clinic->get('name'));
        $this->assertSame('99 Duong Moi, Q1', $clinic->get('address'));
    }

    /**
     * @test TC_S6_52
     * Business expectation: deleting an unused non-default clinic should succeed.
     */
    public function test_TC_S6_52_clinicDelete_unusedClinic_shouldSucceed(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $pdo->exec("INSERT INTO tn_clinics (id, name, address) VALUES (4, 'Phong Kham Xoa', '4 Duong Xoa, Q1')");

        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new ClinicControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 4;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        try {
            $r = $this->invokePrivateJson($c, 'delete');
        } catch (\Throwable $e) {
            $this->fail(
                'BUG NGHIEP VU: clinic khong co rang buoc doctor thi phai xoa duoc, nhung controller dang loi schema/query: '
                . $e->getMessage()
            );
        }

        $this->assertSame(
            1,
            $r->result,
            'BUG NGHIEP VU: clinic khong co rang buoc doctor thi phai xoa duoc, khong duoc loi do query/schema sai.'
        );
        $count = (int) $pdo->query('SELECT COUNT(*) FROM tn_clinics WHERE id = 4')->fetchColumn();
        $this->assertSame(0, $count);
    }

    /** @test TC_S6_53 */
    public function test_TC_S6_53_clinicsGetAll_memberForbidden(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new ClinicsControllerHarness();
        $c->setVariable('AuthUser', $this->memberDoctorModel());
        $r = $this->invokePrivateJson($c, 'getAll');

        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('not admin', $r->msg);
    }

    /** @test TC_S6_54 */
    public function test_TC_S6_54_specialityProcess_get_dispatchesToGetById(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new SpecialityControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 1;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());

        try {
            $c->process();
        } catch (\UmbrellaTests\Support\JsonEchoExit $e) {
            $r = $e->response;
            $this->assertSame(1, $r->result);
            $this->assertSame('Chuyen khoa mac dinh', $r->data->name);
            return;
        }

        $this->fail('SpecialityController::process() did not finish via jsonecho()');
    }

    /** @test TC_S6_55 */
    public function test_TC_S6_55_roomProcess_get_dispatchesToGetById(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new RoomControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 1;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());

        try {
            $c->process();
        } catch (\UmbrellaTests\Support\JsonEchoExit $e) {
            $r = $e->response;
            $this->assertSame(1, $r->result);
            $this->assertSame('Phong 1', $r->data->name);
            return;
        }

        $this->fail('RoomController::process() did not finish via jsonecho()');
    }

    /** @test TC_S6_56 */
    public function test_TC_S6_56_clinicProcess_get_dispatchesToGetById(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new ClinicControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 1;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());

        try {
            $c->process();
        } catch (\UmbrellaTests\Support\JsonEchoExit $e) {
            $r = $e->response;
            $this->assertSame(1, $r->result);
            $this->assertSame('Phong kham mac dinh', $r->data->name);
            return;
        }

        $this->fail('ClinicController::process() did not finish via jsonecho()');
    }

    /** @test TC_S6_57 */
    public function test_TC_S6_57_serviceProcess_get_dispatchesToGetById(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new ServiceControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 1;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());

        try {
            $c->process();
        } catch (\UmbrellaTests\Support\JsonEchoExit $e) {
            $r = $e->response;
            $this->assertSame(1, $r->result);
            $this->assertSame('Dich vu mac dinh', $r->data->name);
            return;
        }

        $this->fail('ServiceController::process() did not finish via jsonecho()');
    }
}
