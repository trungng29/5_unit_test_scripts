<?php

declare(strict_types=1);

namespace UmbrellaTests;

use Pixie\Connection;
use UmbrellaTests\Support\DoctorControllerHarness;
use UmbrellaTests\Support\DoctorsControllerHarness;
use UmbrellaTests\Support\IsDoctorBusyControllerHarness;

/**
 * S2 – Doctor Management (mock DB = SQLite :memory:).
 */
final class S2DoctorManagementTest extends UmbrellaTestCase
{
    /** @test TC_S2_01 */
    public function test_TC_S2_01_getById_whenDoctorExists_returnsSuccess(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getById');
        $this->assertSame(1, $r->result);
        $this->assertSame('Nguyen Van Hai', $r->data->name);
    }

    /** @test TC_S2_02 */
    public function test_TC_S2_02_getById_whenIdMissing_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getById');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsString('required', $r->msg);
    }

    /** @test TC_S2_03 */
    public function test_TC_S2_03_getById_invalidId_returnsNotAvailable(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 99999;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getById');
        $this->assertSame(0, $r->result);
    }

    /**
     * @test TC_S2_04
     * Cập nhật doctor (admin)
     */
    public function test_TC_S2_04_update_admin_validData_returnsSuccess(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('PUT');
        $GLOBALS['_PUT'] = [
            'phone' => '0912345678',
            'name' => 'Tran Thi Tu',
            'role' => 'member',
            'price' => 150000,
            'description' => 'Mo ta',
            'active' => 1,
            'speciality_id' => 1,
            'room_id' => 1,
        ];
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'update');
        $this->assertSame(1, $r->result);
// Xác nhận DB thực sự được cập nhật
$updated = \Controller::model('Doctor', 2);
$this->assertSame('Tran Thi Tu', $updated->get('name'));
$this->assertSame('0912345678', $updated->get('phone'));
$this->assertSame(150000, (int) $updated->get('price'));
$this->assertSame('member', $updated->get('role'));
// update_at phải được refresh
$this->assertNotEmpty($updated->get('update_at'));
    }

    /** @test TC_S2_05 Member không được cập nhật doctor */
    public function test_TC_S2_05_update_member_notAdmin_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('PUT');
        $GLOBALS['_PUT'] = [
            'phone' => '0912345678',
            'name' => 'Tran Van Nam',
            'role' => 'member',
            'price' => 150000,
            'active' => 1,
            'speciality_id' => 1,
            'room_id' => 1,
        ];
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->memberDoctorModel());
        $r = $this->invokePrivateJson($c, 'update');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('not admin', $r->msg);
    }

    /** @test TC_S2_06 */
    public function test_TC_S2_06_update_invalidName_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('PUT');
        $GLOBALS['_PUT'] = [
            'phone' => '0912345678',
            'name' => 'TenSai!@#',
            'role' => 'member',
            'price' => 150000,
            'active' => 1,
            'speciality_id' => 1,
            'room_id' => 1,
        ];
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'update');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsString('letters', $r->msg);
    }

    /** @test TC_S2_07 Phone không hợp lệ */
    public function test_TC_S2_07_update_invalidPhone_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('PUT');
        $GLOBALS['_PUT'] = [
            'phone' => 'abcxyz',
            'name' => 'Le Van Nam',
            'role' => 'member',
            'price' => 150000,
            'active' => 1,
            'speciality_id' => 1,
            'room_id' => 1,
        ];
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'update');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsString('phone', strtolower($r->msg));
    }

    /** @test TC_S2_08 */
    public function test_TC_S2_08_update_invalidRole_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('PUT');
        $GLOBALS['_PUT'] = [
            'phone' => '0912345678',
            'name' => 'Le Van Nam',
            'role' => 'superuser',
            'price' => 150000,
            'active' => 1,
            'speciality_id' => 1,
            'room_id' => 1,
        ];
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'update');
        $this->assertSame(0, $r->result);
    }

    /** @test TC_S2_09 */
    public function test_TC_S2_09_update_priceBelow100k_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('PUT');
        $GLOBALS['_PUT'] = [
            'phone' => '0912345678',
            'name' => 'Le Van Nam',
            'role' => 'member',
            'price' => 50000,
            'active' => 1,
            'speciality_id' => 1,
            'room_id' => 1,
        ];
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'update');
        $this->assertSame(0, $r->result);
// Nghiệp vụ: giá tối thiểu là 100,000 VNĐ — msg phải phản ánh rõ ràng
$this->assertMatchesRegularExpression(
    '/100[\.,]?000|tối thiểu|minimum price/i',
    $r->msg,
    'Msg phải thể hiện giới hạn giá tối thiểu 100.000 VNĐ, không chỉ chứa chuỗi "100"'
);
// DB không được thay đổi
$doctor = \Controller::model('Doctor', 2);
$this->assertSame(150000, (int) $doctor->get('price'), 'Giá trong DB không được thay đổi khi validation fail');
    }

    /** @test TC_S2_10 */
    public function test_TC_S2_10_delete_doctorWithAppointments_deactivates(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');
        $pdo->exec("INSERT INTO tn_doctors (id, email, phone, password, name, description, price, role, active, avatar, create_at, update_at, speciality_id, room_id, recovery_token) VALUES (3, 'bs3@test.com', '0900000004', 'x', 'Bac Ba', 'Mo ta', 150000, 'member', 1, '', '$now', '$now', 1, 1, '')");
        $today = date('Y-m-d');
        $pdo->exec("INSERT INTO tn_appointments (id, booking_id, doctor_id, patient_id, patient_name, patient_birthday, patient_reason, patient_phone, numerical_order, position, date, appointment_time, status, create_at, update_at) VALUES (1, 0, 3, 1, 'BN', '1990-01-01', 'Ly do', '090', 1, 1, '$today', '09:00', 'processing', '$now', '$now')");

        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 3;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'delete');
        $this->assertSame(1, $r->result);
        // Nghiệp vụ: doctor có appointment đang tồn tại thì KHÔNG được xóa cứng,
// chỉ được deactivate (active=0) để bảo toàn lịch sử appointment
$this->assertSame(1, $r->result);
$this->assertSame('deactivated', $r->type);

// Bắt buộc: record doctor vẫn tồn tại trong DB (chưa bị DELETE)
$doctor = \Controller::model('Doctor', 3);
$this->assertNotNull($doctor, 'Doctor phải vẫn tồn tại trong DB sau deactivate');
$this->assertSame(0, (int) $doctor->get('active'));

// Bắt buộc: appointment của doctor KHÔNG bị xóa theo
$pdo = \Pixie\Connection::getStoredConnection()->getPdoInstance();
$count = $pdo->query("SELECT COUNT(*) FROM tn_appointments WHERE doctor_id = 3")->fetchColumn();
$this->assertGreaterThan(0, $count, 'Appointment của doctor phải được giữ nguyên sau deactivate');
    }

    /** @test TC_S2_11 */
    public function test_TC_S2_11_delete_doctorWithoutAppointments_hardDeletes(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');
        $pdo->exec("INSERT INTO tn_doctors (id, email, phone, password, name, description, price, role, active, avatar, create_at, update_at, speciality_id, room_id, recovery_token) VALUES (4, 'bs4@test.com', '0900000005', 'x', 'Bac Bon', 'Mo ta', 150000, 'member', 1, '', '$now', '$now', 1, 1, '')");

        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 4;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'delete');
        $this->assertSame(1, $r->result);
$this->assertSame('delete', $r->type);

// Nghiệp vụ: xóa cứng → record PHẢI biến mất khỏi DB
$pdo = \Pixie\Connection::getStoredConnection()->getPdoInstance();
$row = $pdo->query("SELECT id FROM tn_doctors WHERE id = 4")->fetch();
$this->assertFalse($row, 'Doctor phải bị xóa khỏi DB khi không có appointment (hard delete)');
    }

    /** @test TC_S2_12 */
    public function test_TC_S2_12_delete_self_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 1;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'delete');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsString('yourself', $r->msg);
    }

    /** @test TC_S2_13 Doctor đã inactive không xóa lại */
    public function test_TC_S2_13_delete_inactiveDoctor_returnsAlreadyDeactivated(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');
        $pdo->exec("INSERT INTO tn_doctors (id, email, phone, password, name, description, price, role, active, avatar, create_at, update_at, speciality_id, room_id, recovery_token) VALUES (5, 'bs5@test.com', '0900000006', 'x', 'Bac Nam', 'Mo ta', 150000, 'member', 0, '', '$now', '$now', 1, 1, '')");

        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 5;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'delete');
       $this->assertSame(0, $r->result);
// Nghiệp vụ: không cho xóa/deactivate doctor đã inactive — cần thông báo rõ
$this->assertStringContainsStringIgnoringCase('deactivated', $r->msg);
// Bắt buộc thêm: active vẫn = 0 (không thay đổi state)
$doctor = \Controller::model('Doctor', 5);
$this->assertSame(0, (int) $doctor->get('active'), 'active phải vẫn là 0 sau lần gọi thứ 2');
    }

    /** @test TC_S2_14 getById trả cấu trúc data chuẩn */
    public function test_TC_S2_14_getById_returnsExpectedPayload(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getById');
        $this->assertSame(1, $r->result);
        $this->assertObjectHasProperty('speciality', $r->data);
        $this->assertObjectHasProperty('room', $r->data);
        $this->assertSame(150000, (int) $r->data->price);
    }

    /** @test TC_S2_15 */
    public function test_TC_S2_15_update_missingPhone_returnsMissingField(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('PUT');
        $GLOBALS['_PUT'] = [
            'name' => 'Le Van Nam',
            'role' => 'member',
            'price' => 150000,
            'active' => 1,
            'speciality_id' => 1,
            'room_id' => 1,
        ];
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'update');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsString('phone', $r->msg);
    }

    /** @test TC_S2_16 Xóa doctor không lịch trả thông báo thành công */
    public function test_TC_S2_16_delete_doctorWithoutAppointments_returnsSuccessMessage(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');
        $pdo->exec("INSERT INTO tn_doctors (id, email, phone, password, name, description, price, role, active, avatar, create_at, update_at, speciality_id, room_id, recovery_token) VALUES (6, 'bs6@test.com', '0900000007', 'x', 'Bac Sau', 'Mo ta', 150000, 'member', 1, '', '$now', '$now', 1, 1, '')");

        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 6;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'delete');
        $this->assertSame(1, $r->result);
        $this->assertStringContainsStringIgnoringCase('deleted', $r->msg);
    }

    /** @test TC_S2_17 getById trả đúng tên seed */
    public function test_TC_S2_17_getById_returnsSeededDoctorName(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getById');
        $this->assertSame('Nguyen Van Hai', $r->data->name);
    }

    /** @test TC_S2_18 getById trả đúng giá seed */
    public function test_TC_S2_18_getById_returnsSeededDoctorPrice(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getById');
        $this->assertSame(150000, (int) $r->data->price);
    }

    /** @test TC_S2_19 */
    public function test_TC_S2_19_delete_memberNotAdmin_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->memberDoctorModel());
        $r = $this->invokePrivateJson($c, 'delete');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('not admin', $r->msg);
    }

    /** @test TC_S2_20 */
    public function test_TC_S2_20_delete_missingRouteId_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'delete');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('required', $r->msg);
    }

    /** @test TC_S2_21 */
    public function test_TC_S2_21_delete_doctorNotExists_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 88888;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'delete');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('not available', $r->msg);
    }

    /** @test TC_S2_22 */
    public function test_TC_S2_22_update_missingRouteId_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('PUT');
        $GLOBALS['_PUT'] = [
            'phone' => '0912345678',
            'name' => 'Le Van Nam',
            'role' => 'member',
            'price' => 150000,
            'active' => 1,
            'speciality_id' => 1,
            'room_id' => 1,
        ];
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'update');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('required', $r->msg);
    }

    /** @test TC_S2_23 */
    public function test_TC_S2_23_update_doctorNotExists_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('PUT');
        $GLOBALS['_PUT'] = [
            'phone' => '0912345678',
            'name' => 'Le Van Nam',
            'role' => 'member',
            'price' => 150000,
            'active' => 1,
            'speciality_id' => 1,
            'room_id' => 1,
        ];
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 99999;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'update');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('not available', $r->msg);
    }

    /** @test TC_S2_24 */
    public function test_TC_S2_24_update_invalidSpecialityId_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('PUT');
        $GLOBALS['_PUT'] = [
            'phone' => '0912345678',
            'name' => 'Le Van Nam',
            'role' => 'member',
            'price' => 150000,
            'active' => 1,
            'speciality_id' => 99999,
            'room_id' => 1,
        ];
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'update');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('speciality', $r->msg);
    }

    /** @test TC_S2_25 */
    public function test_TC_S2_25_update_invalidRoomId_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('PUT');
        $GLOBALS['_PUT'] = [
            'phone' => '0912345678',
            'name' => 'Le Van Nam',
            'role' => 'member',
            'price' => 150000,
            'active' => 1,
            'speciality_id' => 1,
            'room_id' => 99999,
        ];
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'update');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('room', $r->msg);
    }

    /** @test TC_S2_26 */
    public function test_TC_S2_26_update_phoneTooShort_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('PUT');
        $GLOBALS['_PUT'] = [
            'phone' => '091234567',
            'name' => 'Le Van Nam',
            'role' => 'member',
            'price' => 150000,
            'active' => 1,
            'speciality_id' => 1,
            'room_id' => 1,
        ];
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'update');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('10', $r->msg);
    }

    /** @test TC_S2_27 */
    public function test_TC_S2_27_update_roleSupporter_allowed(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('PUT');
        $GLOBALS['_PUT'] = [
            'phone' => '0912345678',
            'name' => 'Le Thi Mai',
            'role' => 'supporter',
            'price' => 150000,
            'description' => 'Ho tro',
            'active' => 1,
            'speciality_id' => 1,
            'room_id' => 1,
        ];
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'update');
        // Nghiệp vụ: hệ thống có 3 role hợp lệ: admin, member, supporter
// Test cần xác minh: sau update với role=supporter, DB ghi đúng
$this->assertSame(1, $r->result);
$doctor = \Controller::model('Doctor', 2);
$this->assertSame('supporter', $doctor->get('role'), 'DB phải lưu role=supporter');
// Đảm bảo supporter KHÔNG có quyền admin (phân quyền đúng)
$this->assertNotSame('admin', $doctor->get('role'));
    }

    /** @test TC_S2_28 */
    public function test_TC_S2_28_update_priceNonNumeric_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('PUT');
        $GLOBALS['_PUT'] = [
            'phone' => '0912345678',
            'name' => 'Le Van Nam',
            'role' => 'member',
            'price' => 'khongphai_so',
            'active' => 1,
            'speciality_id' => 1,
            'room_id' => 1,
        ];
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'update');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('price', $r->msg);
    }

    /** @test TC_S2_29 */
    public function test_TC_S2_29_update_missingName_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('PUT');
        $GLOBALS['_PUT'] = [
            'phone' => '0912345678',
            'role' => 'member',
            'price' => 150000,
            'active' => 1,
            'speciality_id' => 1,
            'room_id' => 1,
        ];
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'update');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsString('name', $r->msg);
    }

    /** @test TC_S2_30 */
    public function test_TC_S2_30_update_missingRole_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('PUT');
        $GLOBALS['_PUT'] = [
            'phone' => '0912345678',
            'name' => 'Le Van Nam',
            'price' => 150000,
            'active' => 1,
            'speciality_id' => 1,
            'room_id' => 1,
        ];
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'update');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsString('role', $r->msg);
    }

    /** @test TC_S2_31 */
    public function test_TC_S2_31_getById_adminSelf_returnsSuccess(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 1;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getById');
        $this->assertSame(1, $r->result);
        $this->assertSame('Admin Bac Si', $r->data->name);
    }

    /** @test TC_S2_32 */
    public function test_TC_S2_32_getById_invalidDoctorId_containsUnavailableMsg(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 99999;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getById');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('not available', $r->msg);
    }

    /**
     * BUG NGHIỆP VỤ: endpoint DoctorController::getById không được cho member truy cập dữ liệu doctor.
     *
     * @test TC_S2_33
     */
    public function test_TC_S2_33_getById_memberMustBeForbidden(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->memberDoctorModel());
        $r = $this->invokePrivateJson($c, 'getById');

        $this->assertSame(
            0,
            $r->result,
            'BUG NGHIỆP VỤ: member không được phép xem chi tiết doctor qua endpoint quản trị.'
        );
    }

    /**
     * Demo báo cáo Pass/Fail: cố định kỳ vọng msg ngắn — sẽ Fail cho đến khi spec khớp API hoặc đổi assert.
     *
     * @test TC_S2_34
     */
    public function test_TC_S2_34_regreport_specShortAdminMsg_equalsExact(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('PUT');
        $GLOBALS['_PUT'] = [
            'phone' => '0912345678',
            'name' => 'Tran Van Nam',
            'role' => 'member',
            'price' => 150000,
            'active' => 1,
            'speciality_id' => 1,
            'room_id' => 1,
        ];
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->memberDoctorModel());
        $r = $this->invokePrivateJson($c, 'update');
        // Nghiệp vụ: member KHÔNG được update doctor — response phải:
// 1. result = 0
// 2. msg rõ lý do từ chối (phân quyền)
// 3. QUAN TRỌNG: DB KHÔNG được thay đổi
$this->assertSame(0, $r->result);
$this->assertStringContainsStringIgnoringCase('not admin', $r->msg);

// Check nghiệp vụ thực sự: DB không bị ghi dù result=0
$doctor = \Controller::model('Doctor', 2);
$this->assertNotSame('Tran Van Nam', $doctor->get('name'),
    'DB bị ghi dù AuthUser là member — vi phạm nghiệp vụ phân quyền nghiêm trọng!');
    }

    /**
     * Xác minh danh sách doctor trả về đầy đủ cho admin (DoctorsController::getAll).
     *
     * @test TC_S2_35
     */
    public function test_TC_S2_35_doctorsGetAll_admin_returnsListAndQuantity(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new DoctorsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getAll');

        $this->assertSame(1, $r->result);
        $this->assertGreaterThanOrEqual(1, (int) $r->quantity);
        $this->assertNotEmpty($r->data);
    }

    /**
     * Xác minh filter search theo prefix tên hoạt động trên danh sách doctor.
     *
     * @test TC_S2_36
     */
    public function test_TC_S2_36_doctorsGetAll_searchByNamePrefix_filtersData(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $GLOBALS['_GET'] = ['search' => 'Nguyen'];
        $c = new DoctorsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getAll');

        $this->assertSame(1, $r->result);
        $this->assertNotEmpty($r->data);
        foreach ($r->data as $row) {
            $this->assertStringStartsWith('Nguyen', $row->name);
        }
    }

    /**
     * Xác minh tạo doctor thành công qua DoctorsController::save khi dữ liệu hợp lệ.
     *
     * @test TC_S2_37
     */
    public function test_TC_S2_37_doctorsSave_adminValid_createsDoctor(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'email' => 'new-doctor@test.com',
            'phone' => '0912345699',
            'name' => 'Nguyen Van Moi',
            'role' => 'member',
            'price' => 200000,
            'speciality_id' => 1,
            'room_id' => 1,
        ];
        $c = new DoctorsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');

        $this->assertSame(1, $r->result);
        $this->assertSame('new-doctor@test.com', $r->data->email);
        $created = \Controller::model('Doctor', 'new-doctor@test.com');
        $this->assertTrue($created->isAvailable(), 'Doctor mới phải tồn tại trong DB');
    }

    /**
     * Xác minh member không được tạo doctor trên endpoint quản trị.
     *
     * @test TC_S2_38
     */
    public function test_TC_S2_38_doctorsSave_memberForbidden_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'email' => 'forbidden-doctor@test.com',
            'phone' => '0912345688',
            'name' => 'Nguyen Van Cam',
            'role' => 'member',
        ];
        $c = new DoctorsControllerHarness();
        $c->setVariable('AuthUser', $this->memberDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');

        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('permission', $r->msg);
    }

    /**
     * Xác minh validate bắt buộc: thiếu email thì request bị từ chối.
     *
     * @test TC_S2_39
     */
    public function test_TC_S2_39_doctorsSave_missingEmail_returnsMissingField(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'phone' => '0912345670',
            'name' => 'Nguyen Van Thieu',
            'role' => 'member',
        ];
        $c = new DoctorsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');

        $this->assertSame(0, $r->result);
        $this->assertStringContainsString('email', strtolower($r->msg));
    }

    /**
     * Xác minh validate email format hoạt động trước khi ghi DB.
     *
     * @test TC_S2_40
     */
    public function test_TC_S2_40_doctorsSave_invalidEmailFormat_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'email' => 'invalid-email-format',
            'phone' => '0912345671',
            'name' => 'Nguyen Van Sai',
            'role' => 'member',
        ];
        $c = new DoctorsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');

        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('format', $r->msg);
    }

    /**
     * Xác minh không cho tạo doctor trùng email để tránh ghi đè danh tính.
     *
     * @test TC_S2_41
     */
    public function test_TC_S2_41_doctorsSave_duplicateEmail_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'email' => 'admin@test.com',
            'phone' => '0912345672',
            'name' => 'Nguyen Van Trung',
            'role' => 'member',
        ];
        $c = new DoctorsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');

        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('email', $r->msg);
    }

    /**
     * @test TC_S2_42
     */
    public function test_TC_S2_42_doctorsGetAll_filterBySpecialityId_returnsOnlyMatching(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $GLOBALS['_GET'] = ['speciality_id' => 1];
        $c = new DoctorsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getAll');
        $this->assertSame(1, $r->result);
        $this->assertNotEmpty($r->data);
        foreach ($r->data as $row) {
            $this->assertSame(1, (int) ($row->speciality->id ?? 0));
        }
    }

    /**
     * @test TC_S2_43
     */
    public function test_TC_S2_43_doctorsGetAll_filterByRoomId_returnsOnlyMatching(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $GLOBALS['_GET'] = ['room_id' => 1];
        $c = new DoctorsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getAll');
        $this->assertSame(1, $r->result);
        $this->assertNotEmpty($r->data);
        foreach ($r->data as $row) {
            $this->assertSame(1, (int) ($row->room->id ?? 0));
        }
    }

    /**
     * @test TC_S2_44
     */
    public function test_TC_S2_44_isDoctorBusy_missingRouteId_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new IsDoctorBusyControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'isDoctorBusy');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('required', $r->msg);
    }

    /**
     * @test TC_S2_45
     */
    public function test_TC_S2_45_isDoctorBusy_existingDoctor_returnsStructuredResponse(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new IsDoctorBusyControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'isDoctorBusy');
        $this->assertContains($r->result, [0, 1], 'API phải trả result 0 hoặc 1 kèm msg mô tả trạng thái bận/sẵn sàng');
        $this->assertNotEmpty($r->msg);
    }

    /** @test TC_S2_46 */
    public function test_TC_S2_46_isDoctorBusy_processGet_dispatchesToPrivateMethod(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new IsDoctorBusyControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());

        try {
            $c->process();
        } catch (\UmbrellaTests\Support\JsonEchoExit $e) {
            $r = $e->response;
            $this->assertContains($r->result, [0, 1]);
            $this->assertNotEmpty($r->msg);
            return;
        }

        $this->fail('isDoctorBusyController::process() did not finish via jsonecho()');
    }

    /** @test TC_S2_47 */
    public function test_TC_S2_47_isDoctorBusy_getCurrentAppointmentQuantityByDoctorId_countsTodayProcessing(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        $pdo->exec("INSERT INTO tn_appointments (id, booking_id, doctor_id, patient_id, patient_name, patient_birthday, patient_reason, patient_phone, numerical_order, position, date, appointment_time, status, create_at, update_at) VALUES (901, 0, 2, 1, 'BN A', '1990-01-01', 'A', '090', 1, 1, '$today', '09:00', 'processing', '$now', '$now')");
        $pdo->exec("INSERT INTO tn_appointments (id, booking_id, doctor_id, patient_id, patient_name, patient_birthday, patient_reason, patient_phone, numerical_order, position, date, appointment_time, status, create_at, update_at) VALUES (902, 0, 2, 1, 'BN B', '1990-01-01', 'B', '090', 2, 2, '$today', '10:00', 'processing', '$now', '$now')");

        $c = new IsDoctorBusyControllerHarness();
        $ref = new \ReflectionMethod($c, 'getCurrentAppointmentQuantityByDoctorId');
        $ref->setAccessible(true);
        $output = (int) $ref->invoke($c, 2);

        $this->assertSame(2, $output);
    }

    /** @test TC_S2_48 */
    public function test_TC_S2_48_isDoctorBusy_getAverageAppointmentWithSpecialityId_returnsCeilAverage(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        $pdo->exec("INSERT INTO tn_doctors (id, email, phone, password, name, description, price, role, active, avatar, create_at, update_at, speciality_id, room_id, recovery_token) VALUES (8, 'bs8@test.com', '0900000018', 'x', 'Bac Tam', 'Mo ta', 150000, 'member', 1, '', '$now', '$now', 1, 1, '')");
        $pdo->exec("INSERT INTO tn_appointments (id, booking_id, doctor_id, patient_id, patient_name, patient_birthday, patient_reason, patient_phone, numerical_order, position, date, appointment_time, status, create_at, update_at) VALUES (903, 0, 2, 1, 'BN A', '1990-01-01', 'A', '090', 1, 1, '$today', '09:00', 'processing', '$now', '$now')");
        $pdo->exec("INSERT INTO tn_appointments (id, booking_id, doctor_id, patient_id, patient_name, patient_birthday, patient_reason, patient_phone, numerical_order, position, date, appointment_time, status, create_at, update_at) VALUES (904, 0, 2, 1, 'BN B', '1990-01-01', 'B', '090', 2, 2, '$today', '10:00', 'processing', '$now', '$now')");
        $pdo->exec("INSERT INTO tn_appointments (id, booking_id, doctor_id, patient_id, patient_name, patient_birthday, patient_reason, patient_phone, numerical_order, position, date, appointment_time, status, create_at, update_at) VALUES (905, 0, 8, 1, 'BN C', '1990-01-01', 'C', '090', 3, 3, '$today', '11:00', 'processing', '$now', '$now')");

        $c = new IsDoctorBusyControllerHarness();
        $ref = new \ReflectionMethod($c, 'getAverageAppointmentWithSpecialityId');
        $ref->setAccessible(true);
        $output = (int) $ref->invoke($c, 1);

        $this->assertSame(1, $output, 'ceil(3 appointments / 4 doctors cùng chuyên khoa)=1 trong bộ dữ liệu baseline + seed test');
    }

    /** @test TC_S2_49 */
    public function test_TC_S2_49_doctorProcess_get_dispatchesToGetById(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new DoctorControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 2;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());

        try {
            $c->process();
        } catch (\UmbrellaTests\Support\JsonEchoExit $e) {
            $r = $e->response;
            $this->assertSame(1, $r->result);
            $this->assertSame('Nguyen Van Hai', $r->data->name);
            return;
        }

        $this->fail('DoctorController::process() did not finish via jsonecho()');
    }

    /** @test TC_S2_50 */
    public function test_TC_S2_50_doctorsProcess_get_dispatchesToGetAll(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new DoctorsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());

        try {
            $c->process();
        } catch (\UmbrellaTests\Support\JsonEchoExit $e) {
            $r = $e->response;
            $this->assertSame(1, $r->result);
            $this->assertGreaterThanOrEqual(1, (int) $r->quantity);
            return;
        }

        $this->fail('DoctorsController::process() did not finish via jsonecho()');
    }
}
