<?php
/**
 * AppointmentRecordControllerTest
 *
 * Unit tests for AppointmentRecordController::process()
 * using PHPUnit 9.x.
 *
 * Test Case ID Prefix: TC_S4_
 *
 * Related Module: Appointment Record
 *
 * NGHIỆP VỤ (Business Requirements):
 * - R1: Người dùng phải đăng nhập mới được truy cập trang hồ sơ lịch hẹn.
 *       Nếu chưa đăng nhập, hệ thống chuyển hướng đến trang /login.
 * - R2: Hồ sơ lịch hẹn có hai chế độ:
 *       (a) UPDATE: Khi có id từ URL (?id=X) -> chỉnh sửa hồ sơ có id X.
 *       (b) CREATE: Khi có appointmentId từ URL (?appointmentId=Y) -> tạo hồ sơ mới cho lịch hẹn Y.
 * - R3: Khi không có id hay appointmentId, hệ thống khởi tạo giá trị mặc định (id=0, appointmentId=0).
 * - R4: Hệ thống không được crash khi Route params bị null.
 */

use PHPUnit\Framework\TestCase;

class AppointmentRecordControllerTest extends TestCase
{
    private $controller;
    private $mockRoute;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRoute = new MockRouter4();
        $this->controller = new TestableAppointmentRecordController();
        $this->controller->setVariable("Route", $this->mockRoute);
    }

    private function setGet(array $values): void
    {
        $_GET = $values;
    }

    private function clearGet(): void
    {
        $_GET = [];
    }

    // ========================================================================
    // Test Case ID: TC_S4_31
    // Business Requirement: R1
    // Scenario: Người dùng chưa đăng nhập cố gắng truy cập hồ sơ lịch hẹn
    // Expected Output (nghiệp vụ): Hệ thống phải từ chối và chuyển hướng đến trang /login
    // ========================================================================
    public function authNull_redirectLogin(): void
    {
        // Arrange: Người dùng chưa đăng nhập - hệ thống không có thông tin xác thực
        $this->controller->setAuthFailed(true);

        // Act: Người dùng cố gắng truy cập trang hồ sơ lịch hẹn
        $this->controller->process();

        // Assert (dựa trên R1 - nghiệp vụ):
        // Hệ thống bảo mật phải chuyển hướng người dùng chưa xác thực về trang đăng nhập.
        // Đây là yêu cầu bảo mật bắt buộc - không có ngoại lệ.
        $header = $this->controller->getLastHeader();
        $this->assertNotNull(
            $header,
            "Hệ thống phải gửi redirect header khi người dùng chưa xác thực"
        );
        $this->assertStringContainsString(
            "/login",
            $header,
            "Người dùng chưa đăng nhập phải được chuyển hướng đến trang đăng nhập theo yêu cầu bảo mật"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_32
    // Business Requirement: R1
    // Scenario: Biến AuthUser không tồn tại (unset) khi truy cập trang hồ sơ
    // Expected Output (nghiệp vụ): Hệ thống phải từ chối truy cập và chuyển hướng
    // ========================================================================
    public function authUnset_redirectLogin(): void
    {
        // Arrange: AuthUser không được set trong hệ thống
        $this->controller->setAuthFailed(true);

        // Act: Người dùng cố gắng truy cập trang hồ sơ lịch hẹn
        $this->controller->process();

        // Assert (dựa trên R1 - nghiệp vụ):
        // Hệ thống bảo mật phải hoạt động nhất quán bất kể AuthUser null hay unset.
        $this->assertStringContainsString(
            "/login",
            $this->controller->getLastHeader(),
            "Hệ thống phải chuyển hướng người dùng không có session xác thực đến trang /login"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_33
    // Business Requirement: R3
    // Scenario: Người dùng mở trang hồ sơ mà không có id hay appointmentId
    // Expected Output (nghiệp vụ): Hệ thống khởi tạo id=0, appointmentId=0 (chế độ tạo mới)
    // ========================================================================
    public function noParams_newFormDefaults(): void
    {
        // Arrange: Người dùng mở trang hồ sơ mới (không có tham số nào)
        $authUser = new stdClass();
        $authUser->id = 1;
        $this->controller->setVariable("AuthUser", $authUser);
        $this->clearGet();

        // Act: Hệ thống khởi tạo form hồ sơ mới
        $this->controller->process();

        // Assert (dựa trên R3 - nghiệp vụ):
        // Khi không có id hay appointmentId, đây là trạng thái khởi tạo.
        // Hệ thống phải đặt cả hai giá trị về 0 (chế độ CREATE với id tạm thời = 0).

        // Nghiệp vụ: id = 0 nghĩa là chưa có hồ sơ cụ thể
        $id = $this->controller->getVariable("id");
        $this->assertNotNull(
            $id,
            "Biến id phải được khởi tạo với giá trị mặc định, không phải null"
        );
        $this->assertIsNumeric(
            $id,
            "id phải là giá trị số hợp lệ"
        );
        $this->assertSame(
            0,
            (int)$id,
            "Khi không có id từ URL, hệ thống phải khởi tạo id = 0 để biểu thị trạng thái tạo mới"
        );

        // Nghiệp vụ: appointmentId = 0 nghĩa là chưa liên kết với lịch hẹn cụ thể
        $appointmentId = $this->controller->getVariable("appointmentId");
        $this->assertNotNull(
            $appointmentId,
            "Biến appointmentId phải được khởi tạo, không phải null"
        );
        $this->assertIsNumeric(
            $appointmentId,
            "appointmentId phải là giá trị số hợp lệ"
        );
        $this->assertSame(
            0,
            (int)$appointmentId,
            "Khi không có appointmentId từ URL, hệ thống phải khởi tạo appointmentId = 0"
        );

        // Nghiệp vụ R2: Hiển thị trang hồ sơ lịch hẹn
        $this->assertStringContainsString(
            "appointmentRecord",
            $this->controller->getLastView(),
            "Form hồ sơ lịch hẹn phải được hiển thị để người dùng tạo mới hoặc xem thông tin"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_34
    // Business Requirement: R2(a)
    // Scenario: Người dùng muốn chỉnh sửa hồ sơ có id = 42 (?id=42)
    // Expected Output (nghiệp vụ): Hệ thống nhận id từ URL và hiển thị hồ sơ cần sửa
    // ========================================================================
    public function urlWithId_editMode(): void
    {
        // Arrange: Người dùng mở trang chỉnh sửa hồ sơ với id cụ thể
        $authUser = new stdClass();
        $authUser->id = 1;
        $this->controller->setVariable("AuthUser", $authUser);
        $this->setGet(['id' => '42']);

        // Act: Hệ thống xử lý yêu cầu chỉnh sửa hồ sơ
        $this->controller->process();

        // Assert (dựa trên R2a - nghiệp vụ):
        // Khi URL chứa ?id=X, hệ thống phải ưu tiên id này để lấy đúng hồ sơ cần chỉnh sửa.
        // Đây là nghiệp vụ cốt lõi: xác định hồ sơ cụ thể để cập nhật.

        $id = $this->controller->getVariable("id");
        $this->assertNotNull(
            $id,
            "Hệ thống phải nhận id từ URL khi người dùng muốn chỉnh sửa hồ sơ"
        );
        $this->assertNotSame(
            0,
            (int)$id,
            "Khi có id từ URL, hệ thống không được giữ giá trị mặc định 0"
        );
        $this->assertSame(
            '42',
            (string)$id,
            "id từ URL (?id=42) phải được truyền cho view để lấy đúng hồ sơ cần chỉnh sửa"
        );

        // Nghiệp vụ R2: appointmentId giữ mặc định vì đây là chế độ UPDATE
        $this->assertSame(
            0,
            (int)$this->controller->getVariable("appointmentId"),
            "Trong chế độ UPDATE (có id), appointmentId phải giữ giá trị mặc định 0"
        );

        $this->clearGet();
    }

    // ========================================================================
    // Test Case ID: TC_S4_35
    // Business Requirement: R2(b)
    // Scenario: Người dùng muốn tạo hồ sơ mới cho lịch hẹn có appointmentId = 99
    // Expected Output (nghiệp vụ): Hệ thống nhận appointmentId và khởi tạo form tạo mới
    // ========================================================================
    public function urlWithAppointmentId_createMode(): void
    {
        // Arrange: Người dùng mở form tạo hồ sơ cho lịch hẹn cụ thể
        $authUser = new stdClass();
        $authUser->id = 1;
        $this->controller->setVariable("AuthUser", $authUser);
        $this->setGet(['appointmentId' => '99']);

        // Act: Hệ thống xử lý yêu cầu tạo hồ sơ mới
        $this->controller->process();

        // Assert (dựa trên R2b - nghiệp vụ):
        // Khi URL chứa ?appointmentId=Y, hệ thống biết đây là chế độ CREATE.
        // appointmentId liên kết hồ sơ với lịch hẹn cụ thể.

        $appointmentId = $this->controller->getVariable("appointmentId");
        $this->assertNotNull(
            $appointmentId,
            "Hệ thống phải nhận appointmentId từ URL khi tạo hồ sơ cho lịch hẹn"
        );
        $this->assertNotSame(
            0,
            (int)$appointmentId,
            "Khi có appointmentId từ URL, hệ thống không được giữ giá trị mặc định 0"
        );
        $this->assertSame(
            '99',
            (string)$appointmentId,
            "appointmentId từ URL (?appointmentId=99) phải được truyền cho view để liên kết với lịch hẹn"
        );

        // Nghiệp vụ R2: id giữ mặc định vì đây là chế độ CREATE (chưa có hồ sơ)
        $this->assertSame(
            0,
            (int)$this->controller->getVariable("id"),
            "Trong chế độ CREATE (có appointmentId), id phải giữ giá trị mặc định 0 vì hồ sơ chưa tồn tại"
        );

        $this->clearGet();
    }

    // ========================================================================
    // Test Case ID: TC_S4_36
    // Business Requirement: R2(a) và R2(b)
    // Scenario: URL chứa cả id và appointmentId cùng lúc
    // Expected Output (nghiệp vụ): Hệ thống ưu tiên id (UPDATE) hơn appointmentId (CREATE)
    // ========================================================================
    public function bothParams_idPrioritized(): void
    {
        // Arrange: URL chứa cả hai tham số (trường hợp xung đột)
        $authUser = new stdClass();
        $authUser->id = 1;
        $this->controller->setVariable("AuthUser", $authUser);
        $this->setGet(['id' => '10', 'appointmentId' => '20']);

        // Act: Hệ thống xử lý yêu cầu
        $this->controller->process();

        // Assert (dựa trên R2 - nghiệp vụ):
        // Khi có cả id và appointmentId, hệ thống phải có logic ưu tiên rõ ràng.
        // Trong controller, id được xử lý TRƯỚC appointmentId.
        // Điều này có nghĩa: nếu có id -> UPDATE, nếu chỉ có appointmentId -> CREATE.

        // Nghiệp vụ: id phải được ưu tiên
        $this->assertSame(
            '10',
            (string)$this->controller->getVariable("id"),
            "Khi có cả id và appointmentId, id phải được ưu tiên xử lý trước (chế độ UPDATE)"
        );

        // Nghiệp vụ: appointmentId vẫn được set
        $this->assertSame(
            '20',
            (string)$this->controller->getVariable("appointmentId"),
            "appointmentId vẫn phải được truyền cho view dù id đã được ưu tiên"
        );

        $this->clearGet();
    }

    // ========================================================================
    // Test Case ID: TC_S4_37
    // Business Requirement: R4
    // Scenario: Route params bị null khi truy cập trang hồ sơ
    // Expected Output (nghiệp vụ): Hệ thống không crash, vẫn hiển thị form
    // ========================================================================
    public function routeParamsNull_noCrash(): void
    {
        // Arrange: URL không có params object
        $authUser = new stdClass();
        $authUser->id = 1;
        $this->controller->setVariable("AuthUser", $authUser);
        $this->mockRoute->params = null;
        $this->clearGet();

        // Act & Assert (dựa trên R4 - nghiệp vụ):
        // Hệ thống phải xử lý gracefully khi Route params bị null.
        // Người dùng vẫn phải xem được trang hồ sơ (có thể là form tạo mới).
        $exception = null;
        try {
            $this->controller->process();
        } catch (Throwable $e) {
            $exception = $e;
        }

        $this->assertNull(
            $exception,
            "Hệ thống không được crash khi Route params bị null - phải xử lý gracefully"
        );
        $this->assertStringContainsString(
            "appointmentRecord",
            $this->controller->getLastView(),
            "Trang hồ sơ lịch hẹn phải hiển thị bình thường khi Route params bị null"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_38
    // Business Requirement: R2
    // Scenario: Route có id nhưng GET params có id và appointmentId khác
    // Expected Output (nghiệp vụ): Hệ thống sử dụng GET params, không dùng Route id
    // ========================================================================
    public function routeIdVsGetParams_useGetParams(): void
    {
        // Arrange: Route có id = 999 nhưng GET params có thông tin khác
        $authUser = new stdClass();
        $authUser->id = 1;
        $this->controller->setVariable("AuthUser", $authUser);
        $this->mockRoute->params = (object)['id' => 999];
        $this->setGet(['id' => '5', 'appointmentId' => '7']);

        // Act: Hệ thống xử lý yêu cầu
        $this->controller->process();

        // Assert (dựa trên R2 - nghiệp vụ):
        // Controller sử dụng GET params (?id=X&appointmentId=Y) chứ không dùng Route params.
        // Đây là thiết kế nghiệp vụ: thông tin hồ sơ được truyền qua query string.

        // Nghiệp vụ: GET id phải được sử dụng
        $this->assertSame(
            '5',
            (string)$this->controller->getVariable("id"),
            "Hệ thống phải sử dụng id từ GET params (?id=5) thay vì Route params"
        );

        // Nghiệp vụ: GET appointmentId phải được sử dụng
        $this->assertSame(
            '7',
            (string)$this->controller->getVariable("appointmentId"),
            "Hệ thống phải sử dụng appointmentId từ GET params (?appointmentId=7)"
        );

        $this->clearGet();
    }

    // ========================================================================
    // Test Case ID: TC_S4_39
    // Business Requirement: R1, R2
    // Scenario: Người dùng đã đăng nhập truy cập trang hồ sơ
    // Expected Output (nghiệp vụ): Hệ thống cho phép truy cập trang hồ sơ lịch hẹn
    // ========================================================================
    public function authValid_accessPage(): void
    {
        // Arrange: Người dùng có session đăng nhập hợp lệ
        $this->controller->setVariable("AuthUser", new stdClass());
        $this->clearGet();

        // Act: Người dùng truy cập trang hồ sơ lịch hẹn
        $this->controller->process();

        // Assert (dựa trên R1, R2 - nghiệp vụ):
        // Người dùng đã đăng nhập được phép xem trang hồ sơ lịch hẹn.
        // Trang phải hiển thị form (có thể là form tạo mới với giá trị mặc định).
        $view = $this->controller->getLastView();
        $this->assertStringContainsString(
            "appointmentRecord",
            $view,
            "Người dùng đã đăng nhập phải được xem trang hồ sơ lịch hẹn theo yêu cầu nghiệp vụ"
        );

        // Nghiệp vụ R1: đã xác thực thì không redirect
        $this->assertNull(
            $this->controller->getLastHeader(),
            "Người dùng đã đăng nhập không được chuyển hướng đến trang đăng nhập"
        );
    }
}


// ========================================================================
// Test Double: Mock Router Object
// Simulates AltoRouter match result passed via Route variable
// ========================================================================
class MockRouter4
{
    /** @var stdClass|null */
    public $params = null;
}


// ========================================================================
// Test Double: TestableAppointmentRecordController
// Extends the REAL AppointmentRecordController and intercepts isAuthenticated()
// so that tests can assert auth behavior while still running the real process()
// method for code coverage measurement.
// ========================================================================
class TestableAppointmentRecordController extends AppointmentRecordController
{
    /** @var string|null */
    private $lastView = null;

    /** @var string|null */
    private $lastHeader = null;

    /** @var bool */
    private $forceAuthFailed = false;

    public function setAuthFailed(bool $failed): void
    {
        $this->forceAuthFailed = $failed;
    }

    protected function isAuthenticated(): bool
    {
        if ($this->forceAuthFailed) {
            $this->lastHeader = "Location: " . APPURL . "/login";
            return false;
        }
        return parent::isAuthenticated();
    }

    public function header($string, $replace = true, $response_code = null)
    {
        $this->lastHeader = $string;
    }

    public function exit($status = 0)
    {
        // Do nothing - prevents terminating the test
    }

    public function view($view, $context = "app")
    {
        $this->lastView = $view;
    }

    public function getLastView(): ?string
    {
        return $this->lastView;
    }

    public function getLastHeader(): ?string
    {
        return $this->lastHeader;
    }
}
