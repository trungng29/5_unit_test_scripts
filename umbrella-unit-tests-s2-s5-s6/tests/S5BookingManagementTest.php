<?php

declare(strict_types=1);

namespace UmbrellaTests;

use Pixie\Connection;
use UmbrellaTests\Support\BookingsControllerHarness;
use UmbrellaTests\Support\PatientBookingsControllerHarness;
use UmbrellaTests\Support\PatientBookingControllerHarness;
use UmbrellaTests\Support\BookingControllerHarness;

/**
 * S5 – Booking Management.
 */
final class S5BookingManagementTest extends UmbrellaTestCase
{
    /** @test TC_S5_01 */
    public function test_TC_S5_01_bookingsSave_adminValid_returnsSuccess(): void
    {
        $d = $this->tomorrowDate();
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'service_id' => 1,
            'booking_name' => 'Nguyen Van A',
            'booking_phone' => '0901234567',
            'name' => 'Nguyen Van A',
            'appointment_time' => '09:00',
            'appointment_date' => $d,
            'patient_id' => 1,
            'doctor_id' => 0,
            'gender' => 0,
            'birthday' => '1990-05-05',
            'address' => '12 Tran Hung Dao, Q1',
            'reason' => 'Kham tong quat',
        ];
        $c = new BookingsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(1, $r->result);
        $this->assertTrue(property_exists($r, 'data'), 'Response phải có field data');
        $this->assertNotEmpty($r->data->id ?? null, 'Response phải trả về booking id');

        // Nghiệp vụ (S5 admin/supporter): booking tạo bởi admin/supporter được "verified" ngay.
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $booking = $pdo
            ->query("SELECT status, appointment_date, appointment_time FROM tn_booking WHERE id = " . (int) $r->data->id)
            ->fetch(\PDO::FETCH_OBJ);
        $this->assertNotFalse($booking, 'Booking phải tồn tại trong DB');
        $this->assertSame('verified', $booking->status, 'Booking tạo từ endpoint admin/supporter phải có status=verified');
        $this->assertSame($d, $booking->appointment_date);
        $this->assertSame('09:00', $booking->appointment_time);
    }

    /** @test TC_S5_02 */
    public function test_TC_S5_02_bookingsSave_invalidDoctor_returnsError(): void
    {
        $d = $this->tomorrowDate();
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'service_id' => 1,
            'booking_name' => 'Nguyen Van A',
            'booking_phone' => '0901234567',
            'name' => 'Nguyen Van A',
            'appointment_time' => '09:00',
            'appointment_date' => $d,
            'patient_id' => 1,
            'doctor_id' => 99999,
            'gender' => 0,
            'address' => '12 Tran Hung Dao, Q1',
        ];
        $c = new BookingsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(0, $r->result);
    }

    /** @test TC_S5_03 Giờ trước 7h bị từ chối */
    public function test_TC_S5_03_bookingsSave_beforeSevenAm_returnsWorkingHoursError(): void
    {
        $d = $this->tomorrowDate();
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'service_id' => 1,
            'booking_name' => 'Nguyen Van A',
            'booking_phone' => '0901234567',
            'name' => 'Nguyen Van A',
            'appointment_time' => '06:00',
            'appointment_date' => $d,
            'patient_id' => 1,
            'doctor_id' => 0,
            'gender' => 0,
            'address' => '12 Tran Hung Dao, Q1',
        ];
        $c = new BookingsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('working hours', $r->msg);
    }

    /** @test TC_S5_04 Giờ 06:59 (boundary) bị từ chối */
    public function test_TC_S5_04_bookingsSave_sixFiftyNineAm_returnsWorkingHoursError(): void
    {
        $d = $this->tomorrowDate();
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'service_id' => 1,
            'booking_name' => 'Nguyen Van A',
            'booking_phone' => '0901234567',
            'name' => 'Nguyen Van A',
            'appointment_time' => '06:59',
            'appointment_date' => $d,
            'patient_id' => 1,
            'doctor_id' => 0,
            'gender' => 0,
            'address' => '12 Tran Hung Dao, Q1',
        ];
        $c = new BookingsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(0, $r->result, 'Ngoài giờ làm việc 07:00–20:00 (06:59) phải bị từ chối');
        $this->assertStringContainsStringIgnoringCase('working hours', $r->msg);
    }

    /** @test TC_S5_05 Giờ 07:00 (boundary) được chấp nhận */
    public function test_TC_S5_05_bookingsSave_sevenAm_boundary_isAccepted(): void
    {
        $d = $this->tomorrowDate();
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'service_id' => 1,
            'booking_name' => 'Nguyen Van D',
            'booking_phone' => '0901234599',
            'name' => 'Nguyen Van D',
            'appointment_time' => '07:00',
            'appointment_date' => $d,
            'patient_id' => 1,
            'doctor_id' => 0,
            'gender' => 0,
            'address' => '12 Tran Hung Dao, Q1',
        ];
        $c = new BookingsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(1, $r->result, 'Giờ làm việc bắt đầu từ 07:00 nên 07:00 phải được chấp nhận');
    }

    /** @test TC_S5_06 Giờ 20:00 (boundary) được chấp nhận */
    public function test_TC_S5_06_bookingsSave_twentyPm_boundary_isAccepted(): void
    {
        $d = $this->tomorrowDate();
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'service_id' => 1,
            'booking_name' => 'Nguyen Van E',
            'booking_phone' => '0901234588',
            'name' => 'Nguyen Van E',
            'appointment_time' => '20:00',
            'appointment_date' => $d,
            'patient_id' => 1,
            'doctor_id' => 0,
            'gender' => 0,
            'address' => '12 Tran Hung Dao, Q1',
        ];
        $c = new BookingsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(1, $r->result, 'Boundary 20:00 phải được chấp nhận nếu giờ làm kết thúc là 20:00');
    }

    /** @test TC_S5_07 Giờ 20:01 (boundary) bị từ chối */
    public function test_TC_S5_07_bookingsSave_twentyOhOneOutsideHours_returnsError(): void
    {
        $d = $this->tomorrowDate();
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'service_id' => 1,
            'booking_name' => 'Nguyen Van F',
            'booking_phone' => '0901234577',
            'name' => 'Nguyen Van F',
            'appointment_time' => '20:01',
            'appointment_date' => $d,
            'patient_id' => 1,
            'doctor_id' => 0,
            'gender' => 0,
            'address' => '12 Tran Hung Dao, Q1',
        ];
        $c = new BookingsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(0, $r->result, 'Ngoài giờ làm việc 07:00–20:00 (20:01) phải bị từ chối');
        $this->assertStringContainsStringIgnoringCase('working hours', $r->msg);
    }

    /** @test TC_S5_08 Giờ hợp lệ trong ca (ví dụ 10:30) */
    public function test_TC_S5_08_bookingsSave_validHour_returnsSuccess(): void
    {
        $d = $this->tomorrowDate();
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'service_id' => 1,
            'booking_name' => 'Nguyen Van B',
            'booking_phone' => '0901234568',
            'name' => 'Nguyen Van B',
            'appointment_time' => '10:30',
            'appointment_date' => $d,
            'patient_id' => 1,
            'doctor_id' => 0,
            'gender' => 0,
            'address' => '15 Ly Thuong Kiet, Q1',
        ];
        $c = new BookingsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(1, $r->result);
    }

    /** @test TC_S5_09 Patient chưa có booking: danh sách rỗng */
    public function test_TC_S5_09_patientBookingsGetAll_emptyList_returnsSuccess(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new PatientBookingsControllerHarness();
        $c->setVariable('AuthUser', $this->patientModel());
        $r = $this->invokePrivateJson($c, 'getAll');
        $this->assertSame(1, $r->result);
        $this->assertSame(0, $r->quantity);
        $this->assertIsArray($r->data);
        $this->assertCount(0, $r->data);
    }

    /** @test TC_S5_10 */
    public function test_TC_S5_10_patientBookingsGetAll_onlyOwnPatientRows(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');
        $d = $this->tomorrowDate();
        $pdo->exec("INSERT INTO tn_booking (id, doctor_id, patient_id, service_id, booking_name, booking_phone, name, gender, birthday, address, reason, appointment_date, appointment_time, status, create_at, update_at) VALUES (10, 2, 1, 1, 'A', '0901111111', 'A', 0, '1990-01-01', '1 ABC', '', '$d', '11:00', 'verified', '$now', '$now')");
        $pdo->exec("INSERT INTO tn_booking (id, doctor_id, patient_id, service_id, booking_name, booking_phone, name, gender, birthday, address, reason, appointment_date, appointment_time, status, create_at, update_at) VALUES (11, 2, 2, 1, 'B', '0902222222', 'B', 0, '1990-01-01', '2 ABC', '', '$d', '12:00', 'verified', '$now', '$now')");
        $pdo->exec("INSERT INTO tn_patients (id, email, phone, password, name, gender, birthday, address, avatar, create_at, update_at) VALUES (2, 'p2@test.com', '0900000099', 'x', 'Benh Nhan Hai', 0, '1991-01-01', '99 ABC', '', '$now', '$now')");

        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new PatientBookingsControllerHarness();
        $c->setVariable('AuthUser', $this->patientModel());
        $r = $this->invokePrivateJson($c, 'getAll');
        $this->assertSame(1, $r->result);
        $this->assertNotEmpty($r->data, 'Patient phải có ít nhất 1 booking trong data');
        foreach ($r->data as $row) {
            $this->assertSame(1, $row->patient_id);
        }
        // Nghiệp vụ: data isolation + số lượng phải khớp DB cho patient hiện tại
        $dbCount = (int) $pdo->query("SELECT COUNT(*) FROM tn_booking WHERE patient_id = 1")->fetchColumn();
        $this->assertCount($dbCount, $r->data, 'Số booking trả về phải bằng số booking thực trong DB của patient_id=1');
    }

    /** @test TC_S5_11 */
    public function test_TC_S5_11_patientBookingCancel_processing_ok(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');
        $d = $this->tomorrowDate();
        $pdo->exec("INSERT INTO tn_booking (id, doctor_id, patient_id, service_id, booking_name, booking_phone, name, gender, birthday, address, reason, appointment_date, appointment_time, status, create_at, update_at) VALUES (20, 2, 1, 1, 'A', '0901111111', 'A', 0, '1990-01-01', '1 ABC', '', '$d', '14:00', 'processing', '$now', '$now')");

        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new PatientBookingControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 20;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->patientModel());
        $r = $this->invokePrivateJson($c, 'delete');
        $this->assertSame(1, $r->result);

        // DB phải thực sự thay đổi status
        $booking = $pdo->query("SELECT status FROM tn_booking WHERE id = 20")->fetch(\PDO::FETCH_OBJ);
        $this->assertSame('cancelled', $booking->status, 'Booking phải có status=cancelled trong DB sau khi patient hủy');

        // Nghiệp vụ: update_at phải được cập nhật
        $b = \Controller::model('Booking', 20);
        $this->assertNotEmpty($b->get('update_at'));

        // Nghiệp vụ: KHÔNG tạo appointment sau khi cancelled
        $count = (int) $pdo->query("SELECT COUNT(*) FROM tn_appointments WHERE booking_id = 20")->fetchColumn();
        $this->assertSame(0, $count, 'Booking cancelled không được tạo appointment');
    }

    /** @test TC_S5_12 member cannot save booking via admin endpoint */
    public function test_TC_S5_12_bookingsSave_memberForbidden(): void
    {
        $d = $this->tomorrowDate();
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'service_id' => 1,
            'booking_name' => 'Nguyen Van A',
            'booking_phone' => '0901234567',
            'name' => 'Nguyen Van A',
            'appointment_time' => '09:00',
            'appointment_date' => $d,
            'patient_id' => 1,
            'doctor_id' => 0,
            'gender' => 0,
            'address' => '12 Tran Hung Dao, Q1',
        ];
        $c = new BookingsControllerHarness();
        $c->setVariable('AuthUser', $this->memberDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(0, $r->result);
    }

    /** @test TC_S5_13 Patient không hủy được booking đã verified */
    public function test_TC_S5_13_patientBookingCancel_verified_returnsError(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');
        $d = $this->tomorrowDate();
        $pdo->exec("INSERT INTO tn_booking (id, doctor_id, patient_id, service_id, booking_name, booking_phone, name, gender, birthday, address, reason, appointment_date, appointment_time, status, create_at, update_at) VALUES (21, 2, 1, 1, 'A', '0901111111', 'A', 0, '1990-01-01', '1 ABC', '', '$d', '15:00', 'verified', '$now', '$now')");

        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new PatientBookingControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 21;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->patientModel());
        $r = $this->invokePrivateJson($c, 'delete');
        $this->assertSame(0, $r->result);
        $this->assertMatchesRegularExpression(
            '/processing|chỉ.*hủy|only.*cancel|not.*cancel/i',
            $r->msg,
            'Msg phải giải thích patient chỉ được hủy booking ở trạng thái processing'
        );

        // Bắt buộc: status trong DB KHÔNG thay đổi
        $booking = $pdo->query("SELECT status FROM tn_booking WHERE id = 21")->fetch(\PDO::FETCH_OBJ);
        $this->assertSame('verified', $booking->status, 'Status trong DB phải vẫn là verified — không được đổi khi từ chối hủy');
    }

    /** @test TC_S5_14 Thiếu appointment_time */
    public function test_TC_S5_14_bookingsSave_missingAppointmentTime_returnsMissingField(): void
    {
        $d = $this->tomorrowDate();
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'service_id' => 1,
            'booking_name' => 'Nguyen Van A',
            'booking_phone' => '0901234567',
            'name' => 'Nguyen Van A',
            'appointment_date' => $d,
            'patient_id' => 1,
            'gender' => 0,
            'address' => '12 Tran Hung Dao, Q1',
        ];
        $c = new BookingsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsString('appointment_time', $r->msg);
    }

    /** @test TC_S5_15 patient cannot hit admin bookings save */
    public function test_TC_S5_15_patientModelOnBookingsController_stillForbidden(): void
    {
        $d = $this->tomorrowDate();
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'service_id' => 1,
            'booking_name' => 'Nguyen Van A',
            'booking_phone' => '0901234567',
            'name' => 'Nguyen Van A',
            'appointment_time' => '09:00',
            'appointment_date' => $d,
            'patient_id' => 1,
            'gender' => 0,
            'address' => '12 Tran Hung Dao, Q1',
        ];
        $c = new BookingsControllerHarness();
        $c->setVariable('AuthUser', $this->patientModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(0, $r->result);
    }

    /** @test TC_S5_16 Admin tạo booking hợp lệ (tên khác seed) */
    public function test_TC_S5_16_bookingsSave_validSecondPatientName_returnsSuccess(): void
    {
        $d = $this->tomorrowDate();
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'service_id' => 1,
            'booking_name' => 'Pham Thi Lan',
            'booking_phone' => '0903333333',
            'name' => 'Pham Thi Lan',
            'appointment_time' => '11:00',
            'appointment_date' => $d,
            'patient_id' => 1,
            'doctor_id' => 0,
            'gender' => 0,
            'address' => '88 Nguyen Hue, Q1',
        ];
        $c = new BookingsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(1, $r->result);
    }

    /** @test TC_S5_17 Patient getAll khi chưa có booking vẫn result=1 */
    public function test_TC_S5_17_patientBookingsGetAll_noRows_returnsSuccess(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new PatientBookingsControllerHarness();
        $c->setVariable('AuthUser', $this->patientModel());
        $r = $this->invokePrivateJson($c, 'getAll');
        $this->assertSame(1, $r->result);
    }

    /** @test TC_S5_18 Supporter tạo booking hợp lệ */
    public function test_TC_S5_18_bookingsSave_supporterValid_returnsSuccess(): void
    {
        $d = $this->tomorrowDate();
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'service_id' => 1,
            'booking_name' => 'Tran Van Nam',
            'booking_phone' => '0901234569',
            'name' => 'Tran Van Nam',
            'appointment_time' => '09:15',
            'appointment_date' => $d,
            'patient_id' => 1,
            'doctor_id' => 0,
            'gender' => 0,
            'address' => '22 Ly Thuong Kiet Q1',
        ];
        $c = new BookingsControllerHarness();
        $c->setVariable('AuthUser', $this->supporterDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(1, $r->result);
    }

    /** @test TC_S5_19 */
    public function test_TC_S5_19_bookingsSave_missingServiceId_returnsMissingField(): void
    {
        $d = $this->tomorrowDate();
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'booking_name' => 'Nguyen Van A',
            'booking_phone' => '0901234567',
            'name' => 'Nguyen Van A',
            'appointment_time' => '09:00',
            'appointment_date' => $d,
            'patient_id' => 1,
            'doctor_id' => 0,
            'gender' => 0,
            'address' => '12 Tran Hung Dao Q1',
        ];
        $c = new BookingsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsString('service_id', $r->msg);
    }

    /** @test TC_S5_20 */
    public function test_TC_S5_20_bookingsSave_invalidPatientId_returnsError(): void
    {
        $d = $this->tomorrowDate();
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'service_id' => 1,
            'booking_name' => 'Nguyen Van A',
            'booking_phone' => '0901234567',
            'name' => 'Nguyen Van A',
            'appointment_time' => '10:00',
            'appointment_date' => $d,
            'patient_id' => 99999,
            'doctor_id' => 0,
            'gender' => 0,
            'address' => '12 Tran Hung Dao Q1',
        ];
        $c = new BookingsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('patient', $r->msg);
    }

    /** @test TC_S5_21 */
    public function test_TC_S5_21_bookingsSave_invalidServiceId_returnsError(): void
    {
        $d = $this->tomorrowDate();
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'service_id' => 88888,
            'booking_name' => 'Nguyen Van A',
            'booking_phone' => '0901234567',
            'name' => 'Nguyen Van A',
            'appointment_time' => '10:15',
            'appointment_date' => $d,
            'patient_id' => 1,
            'doctor_id' => 0,
            'gender' => 0,
            'address' => '12 Tran Hung Dao Q1',
        ];
        $c = new BookingsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('service', $r->msg);
    }

    /** @test TC_S5_22 */
    public function test_TC_S5_22_bookingsSave_invalidBookingPhone_returnsError(): void
    {
        $d = $this->tomorrowDate();
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'service_id' => 1,
            'booking_name' => 'Nguyen Van A',
            'booking_phone' => '090abc1234',
            'name' => 'Nguyen Van A',
            'appointment_time' => '10:20',
            'appointment_date' => $d,
            'patient_id' => 1,
            'doctor_id' => 0,
            'gender' => 0,
            'address' => '12 Tran Hung Dao Q1',
        ];
        $c = new BookingsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('phone', $r->msg);
    }

    /** @test TC_S5_23 */
    public function test_TC_S5_23_bookingsSave_invalidBookingName_returnsError(): void
    {
        $d = $this->tomorrowDate();
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'service_id' => 1,
            'booking_name' => 'John123',
            'booking_phone' => '0901234567',
            'name' => 'Nguyen Van A',
            'appointment_time' => '10:25',
            'appointment_date' => $d,
            'patient_id' => 1,
            'doctor_id' => 0,
            'gender' => 0,
            'address' => '12 Tran Hung Dao Q1',
        ];
        $c = new BookingsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('Booking name', $r->msg);
    }

    /** @test TC_S5_24 */
    public function test_TC_S5_24_bookingsSave_invalidAddress_returnsError(): void
    {
        $d = $this->tomorrowDate();
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'service_id' => 1,
            'booking_name' => 'Nguyen Van A',
            'booking_phone' => '0901234567',
            'name' => 'Nguyen Van A',
            'appointment_time' => '10:35',
            'appointment_date' => $d,
            'patient_id' => 1,
            'doctor_id' => 0,
            'gender' => 0,
            'address' => '!!!@@@###',
        ];
        $c = new BookingsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('address', $r->msg);
    }

    /** @test TC_S5_25 */
    public function test_TC_S5_25_bookingsSave_invalidGender_returnsError(): void
    {
        $d = $this->tomorrowDate();
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'service_id' => 1,
            'booking_name' => 'Nguyen Van A',
            'booking_phone' => '0901234567',
            'name' => 'Nguyen Van A',
            'appointment_time' => '10:40',
            'appointment_date' => $d,
            'patient_id' => 1,
            'doctor_id' => 0,
            'gender' => 2,
            'address' => '12 Tran Hung Dao Q1',
        ];
        $c = new BookingsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('gender', $r->msg);
    }

    /** @test TC_S5_26 */
    public function test_TC_S5_26_bookingsSave_timeTwentyTenOutsideHours_returnsError(): void
    {
        $d = $this->tomorrowDate();
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'service_id' => 1,
            'booking_name' => 'Nguyen Van A',
            'booking_phone' => '0901234567',
            'name' => 'Nguyen Van A',
            'appointment_time' => '20:10',
            'appointment_date' => $d,
            'patient_id' => 1,
            'doctor_id' => 0,
            'gender' => 0,
            'address' => '12 Tran Hung Dao Q1',
        ];
        $c = new BookingsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'save');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('working hours', $r->msg);
    }

    /** @test TC_S5_27 */
    public function test_TC_S5_27_bookingsGetAll_admin_returnsSuccess(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new BookingsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getAll');
        $this->assertSame(1, $r->result);
        $this->assertIsArray($r->data);
    }

    /** @test TC_S5_28 */
    public function test_TC_S5_28_patientBookingGetById_ownBooking_returnsSuccess(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');
        $d = $this->tomorrowDate();
        $pdo->exec("INSERT INTO tn_booking (id, doctor_id, patient_id, service_id, booking_name, booking_phone, name, gender, birthday, address, reason, appointment_date, appointment_time, status, create_at, update_at) VALUES (40, 2, 1, 1, 'A', '0901111111', 'A', 0, '1990-01-01', '1 ABC', '', '$d', '16:00', 'processing', '$now', '$now')");

        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new PatientBookingControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 40;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->patientModel());
        $r = $this->invokePrivateJson($c, 'getById');
        $this->assertSame(1, $r->result);
        $this->assertSame(40, (int) $r->data->id);
    }

    /** @test TC_S5_29 */
    public function test_TC_S5_29_patientBookingGetById_wrongPatient_returnsError(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');
        $d = $this->tomorrowDate();
        $pdo->exec("INSERT INTO tn_patients (id, email, phone, password, name, gender, birthday, address, avatar, create_at, update_at) VALUES (3, 'p3@test.com', '0900000098', 'x', 'Benh Nhan Ba', 0, '1992-01-01', '88 XYZ', '', '$now', '$now')");
        $pdo->exec("INSERT INTO tn_booking (id, doctor_id, patient_id, service_id, booking_name, booking_phone, name, gender, birthday, address, reason, appointment_date, appointment_time, status, create_at, update_at) VALUES (41, 2, 3, 1, 'B', '0902222222', 'B', 0, '1990-01-01', '2 ABC', '', '$d', '16:30', 'verified', '$now', '$now')");

        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new PatientBookingControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 41;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->patientModel());
        $r = $this->invokePrivateJson($c, 'getById');
        $this->assertSame(0, $r->result);
    }

    /** @test TC_S5_30 */
    public function test_TC_S5_30_patientBookingDelete_missingId_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new PatientBookingControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->patientModel());
        $r = $this->invokePrivateJson($c, 'delete');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('required', $r->msg);
    }

    /** @test TC_S5_31 */
    public function test_TC_S5_31_patientBookingDelete_cancelledNoAction_returnsMessage(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');
        $d = $this->tomorrowDate();
        $pdo->exec("INSERT INTO tn_booking (id, doctor_id, patient_id, service_id, booking_name, booking_phone, name, gender, birthday, address, reason, appointment_date, appointment_time, status, create_at, update_at) VALUES (42, 2, 1, 1, 'A', '0901111111', 'A', 0, '1990-01-01', '1 ABC', '', '$d', '17:00', 'cancelled', '$now', '$now')");

        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new PatientBookingControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 42;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->patientModel());
        $r = $this->invokePrivateJson($c, 'delete');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('cancelled', $r->msg);
    }

    /**
     * BUG NGHIỆP VỤ (PatientBookingsController::getAll):
     * - `quantity` phải là tổng số records (trước LIMIT) để phân trang, không phải page size
     * - API phải hỗ trợ phân trang đúng: data = length, quantity = total
     *
     * @test TC_S5_32
     */
    public function test_TC_S5_32_patientBookingsGetAll_quantityShouldBeTotalBeforeLimit(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');
        $d = $this->tomorrowDate();

        // tạo 3 booking cho patient_id=1
        $pdo->exec("INSERT INTO tn_booking (id, doctor_id, patient_id, service_id, booking_name, booking_phone, name, gender, birthday, address, reason, appointment_date, appointment_time, status, create_at, update_at) VALUES (301, 2, 1, 1, 'A', '0901111101', 'A', 0, '1990-01-01', '1 ABC', '', '$d', '11:00', 'processing', '$now', '$now')");
        $pdo->exec("INSERT INTO tn_booking (id, doctor_id, patient_id, service_id, booking_name, booking_phone, name, gender, birthday, address, reason, appointment_date, appointment_time, status, create_at, update_at) VALUES (302, 2, 1, 1, 'B', '0901111102', 'B', 0, '1990-01-01', '1 ABC', '', '$d', '11:30', 'processing', '$now', '$now')");
        $pdo->exec("INSERT INTO tn_booking (id, doctor_id, patient_id, service_id, booking_name, booking_phone, name, gender, birthday, address, reason, appointment_date, appointment_time, status, create_at, update_at) VALUES (303, 2, 1, 1, 'C', '0901111103', 'C', 0, '1990-01-01', '1 ABC', '', '$d', '12:00', 'processing', '$now', '$now')");

        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $GLOBALS['_GET'] = ['length' => 1, 'start' => 0];
        $c = new PatientBookingsControllerHarness();
        $c->setVariable('AuthUser', $this->patientModel());
        $r = $this->invokePrivateJson($c, 'getAll');

        $this->assertSame(1, $r->result);
        $this->assertCount(1, $r->data, 'data phải đúng page size length=1');
        $this->assertSame(
            3,
            (int) $r->quantity,
            'BUG NGHIỆP VỤ: quantity phải là tổng booking của patient (3) để frontend tính số trang; không phải số dòng trên trang (1).'
        );
    }

    /**
     * BUG NGHIỆP VỤ (PatientBookingsController::getAll search):
     * Search theo booking_name/name/appointment_time/status phải hoạt động, và không được lỗi SQL/undefined var.
     *
     * @test TC_S5_33
     */
    public function test_TC_S5_33_patientBookingsGetAll_searchByBookingName_filtersCorrectly(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');
        $d = $this->tomorrowDate();

        $pdo->exec("INSERT INTO tn_booking (id, doctor_id, patient_id, service_id, booking_name, booking_phone, name, gender, birthday, address, reason, appointment_date, appointment_time, status, create_at, update_at) VALUES (401, 2, 1, 1, 'Nguyen Van Tim', '0901111191', 'Nguyen Van Tim', 0, '1990-01-01', '1 ABC', 'x', '$d', '09:00', 'processing', '$now', '$now')");
        $pdo->exec("INSERT INTO tn_booking (id, doctor_id, patient_id, service_id, booking_name, booking_phone, name, gender, birthday, address, reason, appointment_date, appointment_time, status, create_at, update_at) VALUES (402, 2, 1, 1, 'Le Thi Mai', '0901111192', 'Le Thi Mai', 0, '1990-01-01', '1 ABC', 'y', '$d', '10:00', 'processing', '$now', '$now')");

        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $GLOBALS['_GET'] = ['search' => 'Nguyen'];
        $c = new PatientBookingsControllerHarness();
        $c->setVariable('AuthUser', $this->patientModel());
        $r = $this->invokePrivateJson($c, 'getAll');

        $this->assertSame(1, $r->result, 'Search phải chạy được và trả result=1 (không được throw SQL/undefined var)');
        $this->assertNotEmpty($r->data);
        foreach ($r->data as $row) {
            $this->assertStringStartsWith('Nguyen', $row->booking_name);
        }
    }

    /**
     * BUG NGHIỆP VỤ (BookingsController::getAll phân quyền):
     * Endpoint admin/supporter listing bookings không được cho role=member truy cập.
     *
     * @test TC_S5_34
     */
    public function test_TC_S5_34_bookingsGetAll_memberMustBeForbidden(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new BookingsControllerHarness();
        $c->setVariable('AuthUser', $this->memberDoctorModel());
        $r = $this->invokePrivateJson($c, 'getAll');

        $this->assertSame(
            0,
            $r->result,
            'BUG NGHIỆP VỤ: role=member không được phép list bookings (chỉ admin/supporter).'
        );
    }

    /**
     * Demo báo cáo: giả định nghiệp vụ từ chối trùng lịch — API hiện không chặn → Fail có chủ đích.
     *
     * @test TC_S5_35
     */
    public function test_TC_S5_35_duplicateSameSlot_rejected(): void
    {
        $d = $this->tomorrowDate();
        $payload = [
            'service_id' => 1,
            'booking_name' => 'Nguyen Van C',
            'booking_phone' => '0901234570',
            'name' => 'Nguyen Van C',
            'appointment_time' => '13:00',
            'appointment_date' => $d,
            'patient_id' => 1,
            // Nghiệp vụ quan trọng: bác sĩ không thể có 2 booking cùng time slot
            'doctor_id' => 2,
            'gender' => 0,
            'address' => '10 Hoang Dieu Q1',
        ];
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = $payload;
        $c = new BookingsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r1 = $this->invokePrivateJson($c, 'save');
        $this->assertSame(1, $r1->result);

        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = $payload;
        $c2 = new BookingsControllerHarness();
        $c2->setVariable('AuthUser', $this->adminDoctorModel());
        $r2 = $this->invokePrivateJson($c2, 'save');
        $this->assertSame(
            0,
            $r2->result,
            'BUG NGHIỆP VỤ: Bác sĩ id=2 đã có booking cùng slot (date+time) nhưng API vẫn cho tạo thêm. '
            . 'Hệ thống đặt lịch y tế phải chặn double-booking bác sĩ.'
        );
    }

    /**
     * Demo báo cáo: giả định patient được hủy booking verified — code chỉ cho processing.
     *
     * @test TC_S5_36
     */
    public function test_TC_S5_36_patientMayCancelVerifiedBooking(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');
        $d = $this->tomorrowDate();
        $pdo->exec("INSERT INTO tn_booking (id, doctor_id, patient_id, service_id, booking_name, booking_phone, name, gender, birthday, address, reason, appointment_date, appointment_time, status, create_at, update_at) VALUES (99, 2, 1, 1, 'A', '0901111111', 'A', 0, '1990-01-01', '1 ABC', '', '$d', '13:30', 'verified', '$now', '$now')");

        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new PatientBookingControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 99;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->patientModel());
        $r = $this->invokePrivateJson($c, 'delete');

        // Nghiệp vụ đúng: patient KHÔNG được tự hủy booking đã verified.
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('processing', $r->msg);

        // Xác nhận DB không thay đổi
        $status = $pdo->query("SELECT status FROM tn_booking WHERE id = 99")->fetchColumn();
        $this->assertSame('verified', $status);
    }

    /**
     * BookingController (quản trị): admin/supporter xem chi tiết booking theo id.
     *
     * @test TC_S5_37
     */
    public function test_TC_S5_37_bookingGetById_admin_returnsSuccess(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');
        $d = $this->tomorrowDate();
        $pdo->exec("INSERT INTO tn_booking (id, doctor_id, patient_id, service_id, booking_name, booking_phone, name, gender, birthday, address, reason, appointment_date, appointment_time, status, create_at, update_at) VALUES (500, 2, 1, 1, 'X', '0901111199', 'X', 0, '1990-01-01', '1 ABC', '', '$d', '14:30', 'processing', '$now', '$now')");

        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new BookingControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 500;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getById');
        $this->assertSame(1, $r->result);
        $this->assertSame(500, (int) $r->data->id);
        $this->assertTrue(property_exists($r->data, 'service'), 'Payload phải có object service kèm booking');
    }

    /**
     * @test TC_S5_38
     */
    public function test_TC_S5_38_bookingGetById_memberDoctorForbidden(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');
        $d = $this->tomorrowDate();
        $pdo->exec("INSERT INTO tn_booking (id, doctor_id, patient_id, service_id, booking_name, booking_phone, name, gender, birthday, address, reason, appointment_date, appointment_time, status, create_at, update_at) VALUES (501, 2, 1, 1, 'Y', '0901111188', 'Y', 0, '1990-01-01', '1 ABC', '', '$d', '15:00', 'processing', '$now', '$now')");

        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new BookingControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 501;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->memberDoctorModel());
        $r = $this->invokePrivateJson($c, 'getById');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('permission', $r->msg);
    }

    /**
     * @test TC_S5_39
     */
    public function test_TC_S5_39_bookingGetById_missingRouteId_returnsError(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new BookingControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'getById');
        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('required', $r->msg);
    }

    /** @test TC_S5_40 */
    public function test_TC_S5_40_patientBookingsSave_valid_createsProcessingAndNotification(): void
    {
        $d = $this->tomorrowDate();
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'service_id' => 1,
            'doctor_id' => 2,
            'booking_name' => 'Nguyen Van Benh',
            'booking_phone' => '0901234000',
            'name' => 'Nguyen Van Benh',
            'appointment_time' => '08:30',
            'appointment_date' => $d,
            'gender' => 0,
            'birthday' => '1992-02-02',
            'address' => '45 Le Loi, Q1',
            'reason' => 'Dau rang',
        ];
        $c = new PatientBookingsControllerHarness();
        $c->setVariable('AuthUser', $this->patientModel());
        $r = $this->invokePrivateJson($c, 'save');

        $this->assertSame(1, $r->result);
        $this->assertSame('processing', $r->data->status);

        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $booking = $pdo->query('SELECT patient_id, doctor_id, status FROM tn_booking WHERE id = ' . (int) $r->data->id)->fetch(\PDO::FETCH_OBJ);
        $this->assertNotFalse($booking);
        $this->assertSame(1, (int) $booking->patient_id);
        $this->assertSame(2, (int) $booking->doctor_id);
        $this->assertSame('processing', $booking->status);

        $notificationCount = (int) $pdo->query('SELECT COUNT(*) FROM tn_notifications WHERE record_type = "booking" AND record_id = ' . (int) $r->data->id)->fetchColumn();
        $this->assertSame(1, $notificationCount, 'Patient tao booking phai sinh notification de theo doi trang thai.');
    }

    /** @test TC_S5_41 */
    public function test_TC_S5_41_patientBookingsSave_duplicateProcessingSameServiceSameDay_rejected(): void
    {
        $d = $this->tomorrowDate();
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');
        $pdo->exec("INSERT INTO tn_booking (id, doctor_id, patient_id, service_id, booking_name, booking_phone, name, gender, birthday, address, reason, appointment_date, appointment_time, status, create_at, update_at) VALUES (601, 2, 1, 1, 'Nguyen Van Benh', '0901234001', 'Nguyen Van Benh', 0, '1992-02-02', '45 Le Loi, Q1', 'Dau rang', '$d', '08:00', 'processing', '$now', '$now')");

        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'service_id' => 1,
            'doctor_id' => 2,
            'booking_name' => 'Nguyen Van Benh',
            'booking_phone' => '0901234001',
            'name' => 'Nguyen Van Benh',
            'appointment_time' => '09:00',
            'appointment_date' => $d,
            'gender' => 0,
            'birthday' => '1992-02-02',
            'address' => '45 Le Loi, Q1',
            'reason' => 'Tai kham',
        ];
        $c = new PatientBookingsControllerHarness();
        $c->setVariable('AuthUser', $this->patientModel());
        $r = $this->invokePrivateJson($c, 'save');

        $this->assertSame(0, $r->result);
        $this->assertStringContainsString('Dich vu mac dinh', $r->msg);
    }

    /** @test TC_S5_42 */
    public function test_TC_S5_42_bookingUpdate_processing_updatesDb(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');
        $d = $this->tomorrowDate();
        $pdo->exec("INSERT INTO tn_booking (id, doctor_id, patient_id, service_id, booking_name, booking_phone, name, gender, birthday, address, reason, appointment_date, appointment_time, status, create_at, update_at) VALUES (700, 2, 1, 1, 'Nguyen Van Cu', '0901111177', 'Nguyen Van Cu', 0, '1990-01-01', '1 ABC', 'Cu', '$d', '09:00', 'processing', '$now', '$now')");

        $this->resetInputGlobals();
        $this->setServerMethod('PUT');
        $GLOBALS['_PUT'] = [
            'service_id' => 1,
            'booking_name' => 'Nguyen Van Moi',
            'booking_phone' => '0901111188',
            'name' => 'Nguyen Van Moi',
            'appointment_time' => '10:00',
            'appointment_date' => $d,
            'gender' => 1,
            'birthday' => '1991-02-02',
            'address' => '22 Tran Hung Dao, Q1',
            'reason' => 'Cap nhat',
        ];
        $c = new BookingControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 700;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'update');

        $this->assertSame(1, $r->result);
        $booking = $pdo->query('SELECT booking_name, booking_phone, appointment_time, status FROM tn_booking WHERE id = 700')->fetch(\PDO::FETCH_OBJ);
        $this->assertSame('Nguyen Van Moi', $booking->booking_name);
        $this->assertSame('0901111188', $booking->booking_phone);
        $this->assertSame('10:00', $booking->appointment_time);
        $this->assertSame('processing', $booking->status, 'Update thong tin khong duoc tu y doi status.');
    }

    /** @test TC_S5_43 */
    public function test_TC_S5_43_bookingUpdate_verifiedBooking_isRejected(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');
        $d = $this->tomorrowDate();
        $pdo->exec("INSERT INTO tn_booking (id, doctor_id, patient_id, service_id, booking_name, booking_phone, name, gender, birthday, address, reason, appointment_date, appointment_time, status, create_at, update_at) VALUES (701, 2, 1, 1, 'Nguyen Van Xac Nhan', '0901111166', 'Nguyen Van Xac Nhan', 0, '1990-01-01', '1 ABC', 'Verified', '$d', '11:00', 'verified', '$now', '$now')");

        $this->resetInputGlobals();
        $this->setServerMethod('PUT');
        $GLOBALS['_PUT'] = [
            'service_id' => 1,
            'booking_name' => 'Nguyen Van Sua',
            'booking_phone' => '0901111155',
            'name' => 'Nguyen Van Sua',
            'appointment_time' => '12:00',
            'appointment_date' => $d,
            'gender' => 0,
            'birthday' => '1991-02-02',
            'address' => '22 Tran Hung Dao, Q1',
            'reason' => 'Cap nhat',
        ];
        $c = new BookingControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 701;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'update');

        $this->assertSame(0, $r->result);
        $this->assertStringContainsStringIgnoringCase('processing', $r->msg);
    }

    /**
     * @test TC_S5_44
     * Business expectation: confirm verified must also create an appointment for the booking.
     */
    public function test_TC_S5_44_bookingConfirm_verified_mustCreateAppointment(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');
        $d = $this->tomorrowDate();
        $pdo->exec("INSERT INTO tn_booking (id, doctor_id, patient_id, service_id, booking_name, booking_phone, name, gender, birthday, address, reason, appointment_date, appointment_time, status, create_at, update_at) VALUES (702, 2, 1, 1, 'Nguyen Van Confirm', '0901111144', 'Nguyen Van Confirm', 0, '1990-01-01', '1 ABC', 'Confirm', '$d', '13:00', 'processing', '$now', '$now')");

        $this->resetInputGlobals();
        $this->setServerMethod('PATCH');
        $GLOBALS['_PATCH'] = ['newStatus' => 'verified'];
        $c = new BookingControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 702;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'confirm');

        $this->assertSame(1, $r->result);
        $status = $pdo->query('SELECT status FROM tn_booking WHERE id = 702')->fetchColumn();
        $this->assertSame('verified', $status);
        $appointmentCount = (int) $pdo->query('SELECT COUNT(*) FROM tn_appointments WHERE booking_id = 702')->fetchColumn();
        $this->assertSame(
            1,
            $appointmentCount,
            'BUG NGHIEP VU: xac nhan booking sang verified phai sinh appointment tuong ung de dua vao luong kham.'
        );
    }

    /** @test TC_S5_45 */
    public function test_TC_S5_45_bookingConfirm_cancelled_updatesStatus(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');
        $d = $this->tomorrowDate();
        $pdo->exec("INSERT INTO tn_booking (id, doctor_id, patient_id, service_id, booking_name, booking_phone, name, gender, birthday, address, reason, appointment_date, appointment_time, status, create_at, update_at) VALUES (703, 2, 1, 1, 'Nguyen Van Huy', '0901111133', 'Nguyen Van Huy', 0, '1990-01-01', '1 ABC', 'Cancel', '$d', '14:00', 'processing', '$now', '$now')");

        $this->resetInputGlobals();
        $this->setServerMethod('PATCH');
        $GLOBALS['_PATCH'] = ['newStatus' => 'cancelled'];
        $c = new BookingControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 703;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());
        $r = $this->invokePrivateJson($c, 'confirm');

        $this->assertSame(1, $r->result);
        $status = $pdo->query('SELECT status FROM tn_booking WHERE id = 703')->fetchColumn();
        $this->assertSame('cancelled', $status);
    }

    /** @test TC_S5_46 */
    public function test_TC_S5_46_bookingsProcess_get_dispatchesToGetAll(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new BookingsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());

        try {
            $c->process();
        } catch (\UmbrellaTests\Support\JsonEchoExit $e) {
            $r = $e->response;
            $this->assertSame(1, $r->result);
            $this->assertIsArray($r->data);
            return;
        }

        $this->fail('BookingsController::process() did not finish via jsonecho()');
    }

    /** @test TC_S5_47 */
    public function test_TC_S5_47_bookingsProcess_post_dispatchesToSave(): void
    {
        $d = $this->tomorrowDate();
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'service_id' => 1,
            'booking_name' => 'Dispatch Test',
            'booking_phone' => '0901234555',
            'name' => 'Dispatch Test',
            'appointment_time' => '09:30',
            'appointment_date' => $d,
            'patient_id' => 1,
            'doctor_id' => 0,
            'gender' => 0,
            'address' => '12 Tran Hung Dao, Q1',
        ];
        $c = new BookingsControllerHarness();
        $c->setVariable('AuthUser', $this->adminDoctorModel());

        try {
            $c->process();
        } catch (\UmbrellaTests\Support\JsonEchoExit $e) {
            $r = $e->response;
            $this->assertSame(1, $r->result);
            $this->assertObjectHasProperty('id', $r->data);
            return;
        }

        $this->fail('BookingsController::process() did not finish via jsonecho()');
    }

    /** @test TC_S5_48 */
    public function test_TC_S5_48_bookingProcess_patch_dispatchesToConfirm(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');
        $d = $this->tomorrowDate();
        $pdo->exec("INSERT INTO tn_booking (id, doctor_id, patient_id, service_id, booking_name, booking_phone, name, gender, birthday, address, reason, appointment_date, appointment_time, status, create_at, update_at) VALUES (704, 2, 1, 1, 'Nguyen Van Process', '0901111122', 'Nguyen Van Process', 0, '1990-01-01', '1 ABC', 'Process', '$d', '15:00', 'processing', '$now', '$now')");

        $this->resetInputGlobals();
        $this->setServerMethod('PATCH');
        $GLOBALS['_PATCH'] = ['newStatus' => 'cancelled'];
        $c = new BookingControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 704;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->adminDoctorModel());

        try {
            $c->process();
        } catch (\UmbrellaTests\Support\JsonEchoExit $e) {
            $r = $e->response;
            $this->assertSame(1, $r->result);
            $status = $pdo->query('SELECT status FROM tn_booking WHERE id = 704')->fetchColumn();
            $this->assertSame('cancelled', $status);
            return;
        }

        $this->fail('BookingController::process() did not finish via jsonecho()');
    }

    /** @test TC_S5_49 */
    public function test_TC_S5_49_patientBookingsProcess_get_dispatchesToGetAll(): void
    {
        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new PatientBookingsControllerHarness();
        $c->setVariable('AuthUser', $this->patientModel());

        try {
            $c->process();
        } catch (\UmbrellaTests\Support\JsonEchoExit $e) {
            $r = $e->response;
            $this->assertSame(1, $r->result);
            $this->assertIsArray($r->data);
            return;
        }

        $this->fail('PatientBookingsController::process() did not finish via jsonecho()');
    }

    /** @test TC_S5_50 */
    public function test_TC_S5_50_patientBookingsProcess_post_dispatchesToSave(): void
    {
        $d = $this->tomorrowDate();
        $this->resetInputGlobals();
        $this->setServerMethod('POST');
        $GLOBALS['_POST'] = [
            'service_id' => 1,
            'doctor_id' => 2,
            'booking_name' => 'Process Patient',
            'booking_phone' => '0901234999',
            'name' => 'Process Patient',
            'appointment_time' => '09:45',
            'appointment_date' => $d,
            'gender' => 0,
            'birthday' => '1992-02-02',
            'address' => '45 Le Loi, Q1',
            'reason' => 'Kham',
        ];
        $c = new PatientBookingsControllerHarness();
        $c->setVariable('AuthUser', $this->patientModel());

        try {
            $c->process();
        } catch (\UmbrellaTests\Support\JsonEchoExit $e) {
            $r = $e->response;
            $this->assertSame(1, $r->result);
            $this->assertSame('processing', $r->data->status);
            return;
        }

        $this->fail('PatientBookingsController::process() did not finish via jsonecho()');
    }

    /** @test TC_S5_51 */
    public function test_TC_S5_51_patientBookingProcess_get_dispatchesToGetById(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');
        $d = $this->tomorrowDate();
        $pdo->exec("INSERT INTO tn_booking (id, doctor_id, patient_id, service_id, booking_name, booking_phone, name, gender, birthday, address, reason, appointment_date, appointment_time, status, create_at, update_at) VALUES (710, 2, 1, 1, 'A', '0901111001', 'A', 0, '1990-01-01', '1 ABC', '', '$d', '16:00', 'processing', '$now', '$now')");

        $this->resetInputGlobals();
        $this->setServerMethod('GET');
        $c = new PatientBookingControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 710;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->patientModel());

        try {
            $c->process();
        } catch (\UmbrellaTests\Support\JsonEchoExit $e) {
            $r = $e->response;
            $this->assertSame(1, $r->result);
            $this->assertSame(710, (int) $r->data->id);
            return;
        }

        $this->fail('PatientBookingController::process() did not finish via jsonecho()');
    }

    /** @test TC_S5_52 */
    public function test_TC_S5_52_patientBookingProcess_delete_dispatchesToDelete(): void
    {
        $pdo = Connection::getStoredConnection()->getPdoInstance();
        $now = date('Y-m-d H:i:s');
        $d = $this->tomorrowDate();
        $pdo->exec("INSERT INTO tn_booking (id, doctor_id, patient_id, service_id, booking_name, booking_phone, name, gender, birthday, address, reason, appointment_date, appointment_time, status, create_at, update_at) VALUES (711, 2, 1, 1, 'A', '0901111002', 'A', 0, '1990-01-01', '1 ABC', '', '$d', '17:00', 'processing', '$now', '$now')");

        $this->resetInputGlobals();
        $this->setServerMethod('DELETE');
        $c = new PatientBookingControllerHarness();
        $route = new \stdClass();
        $route->params = new \stdClass();
        $route->params->id = 711;
        $c->setVariable('Route', $route);
        $c->setVariable('AuthUser', $this->patientModel());

        try {
            $c->process();
        } catch (\UmbrellaTests\Support\JsonEchoExit $e) {
            $r = $e->response;
            $this->assertSame(1, $r->result);
            $status = $pdo->query('SELECT status FROM tn_booking WHERE id = 711')->fetchColumn();
            $this->assertSame('cancelled', $status);
            return;
        }

        $this->fail('PatientBookingController::process() did not finish via jsonecho()');
    }
}
