<?php
/**
 * AppointmentControllerTest
 *
 * Unit tests for AppointmentController::process()
 * using PHPUnit 9.x.
 *
 * Test Case ID Prefix: TC_S4_
 *
 * Related Module: Appointment
 *
 * NGHIỆP VỤ (Business Requirements):
 * - R1: Người dùng phải đăng nhập mới được truy cập trang thông tin lịch hẹn.
 *       Nếu chưa đăng nhập, hệ thống chuyển hướng đến trang /login.
 * - R2: Trang thông tin lịch hẹn hiển thị dữ liệu bệnh nhân gồm:
 *       bookingId, appointmentDate, appointmentTime, patientId, patientName,
 *       patientPhone, patientReason, patientBirthday, doctorId, serviceId.
 * - R3: Khi route có id (xem chi tiết lịch hẹn), hệ thống truyền id để lấy thông tin cụ thể.
 * - R4: Khi route không có id (tạo lịch hẹn mới), hệ thống khởi tạo các biến rỗng cho form.
 * - R5: Các biến mặc định phải có giá trị hợp lệ (chuỗi rỗng hoặc 0) khi chưa có dữ liệu.
 *
 * @coversDefaultClass \AppointmentController
 */

use PHPUnit\Framework\TestCase;

require_once APPPATH . '/controllers/AppointmentController.php';

class AppointmentControllerTest extends TestCase
{
    private $controller;
    private $capturedView;

    protected function setUp(): void
    {
        parent::setUp();
        $this->capturedView = null;
        $this->controller = $this->getMockBuilder(AppointmentController::class)
            ->onlyMethods(['view'])
            ->addMethods(['header', 'exit'])
            ->getMock();
        $this->controller->expects($this->any())->method('header');
        $this->controller->expects($this->any())->method('exit');
        $this->controller->expects($this->any())
            ->method('view')
            ->willReturnCallback(function ($viewName) {
                $this->capturedView = $viewName;
            });
    }

    private function clearGet(): void
    {
        $_GET = [];
    }

    // ========================================================================
    // Test Case ID: TC_S4_03
    // Business Requirement: R3
    // Scenario: Người dùng truy cập trang xem chi tiết lịch hẹn với id trong URL
    // Expected Output (nghiệp vụ): Hệ thống phải nhận diện id từ URL và hiển thị trang chi tiết
    // ========================================================================
    /**
     * @covers AppointmentController::process()
     */
    public function withId_accessible(): void
    {
        // Arrange: Người dùng đã đăng nhập và truy cập trang chi tiết lịch hẹn có id
        $authUser = new stdClass();
        $authUser->id = 1;
        $this->controller->setVariable("AuthUser", $authUser);
        $this->controller->setVariable("Route", (object)['params' => (object)['id' => 42]]);

        // Act: Người dùng xem chi tiết lịch hẹn
        $this->controller->process();

        // Assert (dựa trên R3 - nghiệp vụ):
        // Khi người dùng cung cấp id, hệ thống phải truyền id này cho view để lấy thông tin lịch hẹn.
        // Đây là nghiệp vụ cốt lõi: hiển thị thông tin lịch hẹn cụ thể theo id.
        $idFromView = $this->controller->getVariable("id");
        $this->assertNotNull(
            $idFromView,
            "Hệ thống phải nhận id từ URL khi người dùng xem chi tiết lịch hẹn"
        );
        $this->assertNotEmpty(
            (string)$idFromView,
            "Id của lịch hẹn phải được truyền cho view (không phải giá trị mặc định rỗng)"
        );

        // Nghiệp vụ R2: Trang phải hiển thị thông tin lịch hẹn
        $this->controller->process();

        $this->assertStringContainsString(
            "appointment",
            $this->capturedView,
            "Người dùng đã đăng nhập phải được xem trang thông tin lịch hẹn"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_04
    // Business Requirement: R2
    // Scenario: Bệnh nhân điền form đặt lịch hẹn mới với đầy đủ thông tin
    // Expected Output (nghiệp vụ): Tất cả thông tin bệnh nhân phải được hiển thị lại để xác nhận
    // ========================================================================
    /**
     * @covers AppointmentController::process()
     */
    public function bookingFormWithData_displayAllFields(): void
    {
        // Arrange: Bệnh nhân điền form đặt lịch hẹn với đầy đủ thông tin
        $authUser = new stdClass();
        $authUser->id = 1;
        $this->controller->setVariable("AuthUser", $authUser);
        $this->controller->setVariable("Route", (object)['params' => (object)[]]);

        $_GET = [
            'doctorId'         => 'Dr01',
            'serviceId'        => 'SVC5',
            'bookingId'        => 'BK100',
            'appointmentDate'  => '2026-05-15',
            'appointmentTime'  => '09:00',
            'patientId'        => 'P200',
            'patientName'      => 'Nguyen Van A',
            'patientPhone'     => '0909123456',
            'patientReason'    => 'Kham tong quat',
            'patientBirthday'  => '1990-01-01',
        ];

        // Act: Hệ thống xử lý form đặt lịch
        $this->controller->process();

        // Assert (dựa trên R2 - nghiệp vụ):
        // Sau khi bệnh nhân điền form, hệ thống phải hiển thị lại TẤT CẢ thông tin
        // để bệnh nhân xác nhận trước khi gửi. Đây là yêu cầu UX nghiệp vụ.
        // Expected output KHÔNG lấy từ source code mà từ R2.

        // Nghiệp vụ: thông tin bác sĩ phải được hiển thị
        $this->assertNotEmpty(
            $this->controller->getVariable("doctorId"),
            "Bác sĩ được chọn phải được hiển thị để bệnh nhân xác nhận"
        );
        $this->assertNotEmpty(
            $this->controller->getVariable("serviceId"),
            "Dịch vụ khám phải được hiển thị để bệnh nhân xác nhận"
        );

        // Nghiệp vụ: thông tin lịch hẹn phải được hiển thị
        $appointmentDate = $this->controller->getVariable("appointmentDate");
        $this->assertNotEmpty(
            $appointmentDate,
            "Ngày hẹn khám phải được hiển thị để bệnh nhân xác nhận"
        );
        $this->assertNotEmpty(
            $this->controller->getVariable("appointmentTime"),
            "Giờ hẹn khám phải được hiển thị để bệnh nhân xác nhận"
        );

        // Nghiệp vụ: thông tin bệnh nhân phải được hiển thị
        $this->assertNotEmpty(
            $this->controller->getVariable("patientName"),
            "Tên bệnh nhân phải được hiển thị để xác nhận danh tính"
        );
        $this->assertNotEmpty(
            $this->controller->getVariable("patientPhone"),
            "Số điện thoại bệnh nhân phải được hiển thị để liên hệ xác nhận"
        );
        $this->assertNotEmpty(
            $this->controller->getVariable("patientReason"),
            "Lý do khám phải được hiển thị để bác sĩ chuẩn bị trước"
        );

        // Nghiệp vụ R2: hiển thị view xác nhận
        $this->assertStringContainsString(
            "appointment",
            $this->capturedView,
            "Sau khi điền form, hệ thống phải hiển thị trang xác nhận thông tin lịch hẹn"
        );

        $this->clearGet();
    }

    // ========================================================================
    // Test Case ID: TC_S4_05
    // Business Requirement: R5
    // Scenario: Người dùng mở trang tạo lịch hẹn mới (không có query params)
    // Expected Output (nghiệp vụ): Các trường phải có giá trị mặc định hợp lệ, không null
    // ========================================================================
    /**
     * @covers AppointmentController::process()
     */
    public function newForm_empty_defaultValuesNotNull(): void
    {
        // Arrange: Người dùng mở trang tạo lịch hẹn mới (chưa điền gì)
        $authUser = new stdClass();
        $authUser->id = 1;
        $this->controller->setVariable("AuthUser", $authUser);
        $this->controller->setVariable("Route", (object)['params' => (object)[]]);
        $this->clearGet();

        // Act: Hệ thống khởi tạo form trống
        $this->controller->process();

        // Assert (dựa trên R5 - nghiệp vụ):
        // Form mới phải có giá trị mặc định hợp lệ. Không được để null/undefined.
        // Đây là yêu cầu về tính toàn vẹn dữ liệu (data integrity).

        // Nghiệp vụ: bookingId rỗng nghĩa là chưa có mã đặt lịch
        $bookingId = $this->controller->getVariable("bookingId");
        $this->assertNotNull(
            $bookingId,
            "bookingId phải được khởi tạo (không phải null) khi chưa có đặt lịch"
        );
        $this->assertIsString(
            $bookingId,
            "bookingId phải là chuỗi hợp lệ, không phải kiểu khác"
        );

        // Nghiệp vụ: ngày giờ mặc định là rỗng (chưa chọn)
        $appointmentDate = $this->controller->getVariable("appointmentDate");
        $this->assertNotNull(
            $appointmentDate,
            "appointmentDate phải được khởi tạo để form không bị lỗi"
        );

        // Nghiệp vụ: thông tin bệnh nhân mặc định rỗng
        $patientName = $this->controller->getVariable("patientName");
        $this->assertNotNull(
            $patientName,
            "patientName phải được khởi tạo để form không bị undefined"
        );
        $this->assertIsString(
            $patientName,
            "Tất cả biến bệnh nhân phải là chuỗi, không phải kiểu khác"
        );

        $this->assertStringContainsString(
            "appointment",
            $this->capturedView,
            "Form tạo lịch hẹn mới phải hiển thị để người dùng điền thông tin"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_06
    // Business Requirement: R3
    // Scenario: Route id = 0 (trường hợp biên - id hợp lệ có thể bằng 0)
    // Expected Output (nghiệp vụ): Hệ thống phải xử lý được id = 0 như một giá trị hợp lệ
    // ========================================================================
    /**
     * @covers AppointmentController::process()
     */
    public function idZero_noCrash(): void
    {
        // Arrange: Route có id = 0 (có thể là lịch hẹn đầu tiên hoặc trường hợp biên)
        $authUser = new stdClass();
        $authUser->id = 1;
        $this->controller->setVariable("AuthUser", $authUser);
        $this->controller->setVariable("Route", (object)['params' => (object)['id' => 0]]);

        // Act: Hệ thống xử lý id = 0
        $this->controller->process();

        // Assert (dựa trên R3 - nghiệp vụ):
        // Id = 0 là một giá trị số hợp lệ. Hệ thống phải xử lý mà không bị crash.
        // Trong CSDL, id thường bắt đầu từ 1, nhưng hệ thống không nên giả định điều này.
        $id = $this->controller->getVariable("id");
        $this->assertSame(
            0,
            $id,
            "Hệ thống phải xử lý được id = 0 như một giá trị hợp lệ"
        );
        $this->assertStringContainsString(
            "appointment",
            $this->capturedView,
            "Trang thông tin lịch hẹn phải hiển thị kể cả khi id = 0"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_07
    // Business Requirement: R5
    // Scenario: Route params bị null (URL không có tham số)
    // Expected Output (nghiệp vụ): Hệ thống không crash, vẫn hiển thị form đặt lịch
    // ========================================================================
    /**
     * @covers AppointmentController::process()
     */
    public function routeParamsNull_noCrash(): void
    {
        // Arrange: URL không có params object (VD: /appointment thay vì /appointment/123)
        $authUser = new stdClass();
        $authUser->id = 1;
        $this->controller->setVariable("AuthUser", $authUser);
        $this->controller->setVariable("Route", (object)['params' => null]);
        $this->clearGet();

        // Act & Assert (dựa trên R5 - nghiệp vụ):
        // Hệ thống phải xử lý gracefully khi Route params bị null.
        $exception = null;
        try {
            $this->controller->process();
        } catch (Throwable $e) {
            $exception = $e;
        }

        $this->assertNull(
            $exception,
            "Hệ thống không được crash khi Route params bị null"
        );
        $this->assertStringContainsString(
            "appointment",
            $this->capturedView,
            "Form đặt lịch hẹn phải hiển thị bình thường khi không có Route params"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_08
    // Business Requirement: R5
    // Scenario: Controller khởi tạo các biến ban đầu cho view
    // Expected Output (nghiệp vụ): Các biến phải được khởi tạo với giá trị hợp lệ
    // ========================================================================
    /**
     * @covers AppointmentController::process()
     */
    public function noInput_initializeDefaults(): void
    {
        // Arrange
        $this->controller->setVariable("AuthUser", new stdClass());
        $this->controller->setVariable("Route", (object)['params' => (object)[]]);
        $this->clearGet();

        // Act: Controller khởi tạo biến cho view
        $this->controller->process();

        // Assert (dựa trên R5 - nghiệp vụ):
        // Trước khi nhận dữ liệu từ URL, các biến phải có giá trị mặc định hợp lệ.
        // Đây là yêu cầu về khởi tạo an toàn (safe initialization).

        // Nghiệp vụ: id mặc định phải là số, không phải null
        $id = $this->controller->getVariable("id");
        $this->assertNotNull(
            $id,
            "Biến id phải được khởi tạo với giá trị mặc định, không phải null"
        );
        $this->assertIsInt(
            $id,
            "id phải là số nguyên"
        );
        $this->assertSame(
            0,
            $id,
            "id mặc định phải là 0 khi chưa có lịch hẹn cụ thể"
        );

        // Nghiệp vụ: bookingId mặc định phải là chuỗi rỗng
        $bookingId = $this->controller->getVariable("bookingId");
        $this->assertNotNull($bookingId, "bookingId phải được khởi tạo");
        $this->assertSame(
            "",
            $bookingId,
            "bookingId mặc định phải là chuỗi rỗng khi chưa có mã đặt lịch"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_09
    // Business Requirement: R3
    // Scenario: Route có id cụ thể phải ghi đè giá trị mặc định
    // Expected Output (nghiệp vụ): id từ route phải được ưu tiên hơn giá trị mặc định
    // ========================================================================
    /**
     * @covers AppointmentController::process()
     */
    public function routeIdProvided_overrideDefault(): void
    {
        // Arrange: Route có id cụ thể của lịch hẹn
        $authUser = new stdClass();
        $authUser->id = 1;
        $this->controller->setVariable("AuthUser", $authUser);
        $this->controller->setVariable("Route", (object)['params' => (object)['id' => 99]]);

        // Act: Hệ thống xử lý lịch hẹn cụ thể
        $this->controller->process();

        // Assert (dựa trên R3 - nghiệp vụ):
        // Khi người dùng cung cấp id cụ thể, hệ thống phải ưu tiên id đó.
        // Đây là logic nghiệp vụ cốt lõi: hiển thị đúng lịch hẹn được yêu cầu.
        $id = $this->controller->getVariable("id");
        $this->assertNotSame(
            0,
            $id,
            "Khi có id từ route, hệ thống không được giữ giá trị mặc định 0"
        );
        $this->assertSame(
            99,
            $id,
            "id từ route phải được truyền cho view để lấy đúng lịch hẹn"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_10
    // Business Requirement: R5
    // Scenario: Route không có id -> giữ giá trị mặc định
    // Expected Output (nghiệp vụ): Khi không có id, form phải ở trạng thái khởi tạo
    // ========================================================================
    /**
     * @covers AppointmentController::process()
     */
    public function noRouteId_keepDefaultZero(): void
    {
        // Arrange: URL không có id (trang tạo lịch hẹn mới)
        $authUser = new stdClass();
        $authUser->id = 1;
        $this->controller->setVariable("AuthUser", $authUser);
        $this->controller->setVariable("Route", (object)['params' => (object)[]]);
        $this->clearGet();

        // Act: Hệ thống khởi tạo form mới
        $this->controller->process();

        // Assert (dựa trên R5 - nghiệp vụ):
        // Khi không có id, hệ thống phải giữ giá trị mặc định (id = 0).
        // id = 0 nghĩa là "chưa có lịch hẹn cụ thể" - đây là trạng thái khởi tạo hợp lệ.
        $id = $this->controller->getVariable("id");
        $this->assertSame(
            0,
            $id,
            "Khi không có id từ route, hệ thống phải giữ giá trị mặc định id = 0 để biểu thị trạng thái khởi tạo"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_11
    // Business Requirement: R2
    // Scenario: AuthUser là object rỗng (đã đăng nhập nhưng không có thuộc tính)
    // Expected Output (nghiệp vụ): Người dùng đã đăng nhập vẫn được xem trang lịch hẹn
    // ========================================================================
    /**
     * @covers AppointmentController::process()
     */
    public function authEmpty_accessPage(): void
    {
        // Arrange: Người dùng có session đăng nhập
        $this->controller->setVariable("AuthUser", new stdClass());
        $this->controller->setVariable("Route", (object)['params' => (object)[]]);
        $this->clearGet();

        // Act: Người dùng truy cập trang lịch hẹn
        $this->controller->process();

        // Assert (dựa trên R2 - nghiệp vụ):
        // Người dùng có session (dù rỗng) được coi là đã đăng nhập.
        // Hệ thống phải cho phép truy cập trang thông tin lịch hẹn.
        $this->assertStringContainsString(
            "appointment",
            $this->capturedView,
            "Người dùng đã đăng nhập phải được xem trang thông tin lịch hẹn theo yêu cầu nghiệp vụ"
        );
    }
}
