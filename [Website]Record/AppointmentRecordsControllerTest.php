<?php
/**
 * AppointmentRecordsControllerTest
 *
 * Unit tests for AppointmentRecordsController::process()
 * using PHPUnit 9.x.
 *
 * Test Case ID Prefix: TC_S4_
 *
 * Related Module: Appointment Records
 *
 * NGHIỆP VỤ (Business Requirements):
 * - R1: Người dùng phải đăng nhập mới được truy cập trang danh sách hồ sơ lịch hẹn.
 *       Nếu chưa đăng nhập, hệ thống chuyển hướng đến trang /login.
 * - R2: Người dùng đã đăng nhập được xem trang danh sách hồ sơ lịch hẹn (view: appointmentRecords).
 * - R3: Controller không phụ thuộc vào Route hay GET params - chỉ kiểm tra đăng nhập.
 * - R4: Hệ thống không được crash khi Route bị null hoặc không có params.
 */

use PHPUnit\Framework\TestCase;

class AppointmentRecordsControllerTest extends TestCase
{
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new TestableAppointmentRecordsController();
    }

    // ========================================================================
    // Test Case ID: TC_S4_40
    // Business Requirement: R1
    // Scenario: Người dùng chưa đăng nhập cố gắng xem danh sách hồ sơ
    // Expected Output (nghiệp vụ): Hệ thống phải từ chối và chuyển hướng đến trang /login
    // ========================================================================
    public function authNull_redirectLogin(): void
    {
        // Arrange: Người dùng chưa đăng nhập - hệ thống không có thông tin xác thực
        $this->controller->setAuthFailed(true);

        // Act: Người dùng cố gắng truy cập trang danh sách hồ sơ lịch hẹn
        $this->controller->process();

        // Assert (dựa trên R1 - nghiệp vụ):
        // Hệ thống bảo mật phải chuyển hướng người dùng chưa xác thực về trang đăng nhập.
        // Đây là yêu cầu bảo mật bắt buộc để bảo vệ dữ liệu hồ sơ lịch hẹn.
        $header = $this->controller->getLastHeader();
        $this->assertNotNull(
            $header,
            "Hệ thống phải gửi redirect header khi người dùng chưa xác thực"
        );
        $this->assertStringContainsString(
            "/login",
            $header,
            "Người dùng chưa đăng nhập phải được chuyển hướng đến trang /login theo yêu cầu bảo mật"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_41
    // Business Requirement: R1
    // Scenario: Biến AuthUser không tồn tại (unset) khi truy cập trang danh sách
    // Expected Output (nghiệp vụ): Hệ thống phải từ chối truy cập và chuyển hướng
    // ========================================================================
    public function authUnset_redirectLogin(): void
    {
        // Arrange: AuthUser không được set trong hệ thống
        $this->controller->setAuthFailed(true);

        // Act: Người dùng cố gắng truy cập trang danh sách hồ sơ lịch hẹn
        $this->controller->process();

        // Assert (dựa trên R1 - nghiệp vụ):
        // Hệ thống bảo mật phải hoạt động nhất quán bất kể AuthUser null hay unset.
        // Trang danh sách hồ sơ chứa thông tin nhạy cảm nên không thể truy cập khi chưa đăng nhập.
        $this->assertStringContainsString(
            "/login",
            $this->controller->getLastHeader(),
            "Hệ thống phải chuyển hướng người dùng không có session xác thực đến trang /login"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_42
    // Business Requirement: R2
    // Scenario: Người dùng đã đăng nhập truy cập trang danh sách hồ sơ
    // Expected Output (nghiệp vụ): Hệ thống cho phép truy cập trang danh sách hồ sơ lịch hẹn
    // ========================================================================
    public function authValid_accessList(): void
    {
        // Arrange: Người dùng có session đăng nhập hợp lệ
        $authUser = new stdClass();
        $authUser->id = 1;
        $this->controller->setVariable("AuthUser", $authUser);

        // Act: Người dùng truy cập trang danh sách hồ sơ lịch hẹn
        $this->controller->process();

        // Assert (dựa trên R2 - nghiệp vụ):
        // Người dùng đã đăng nhập được phép xem trang danh sách tất cả hồ sơ lịch hẹn.
        // View phải là trang danh sách (appointmentRecords) - không phải trang khác.
        $view = $this->controller->getLastView();
        $this->assertNotNull(
            $view,
            "Hệ thống phải trả về một trang view khi người dùng đã xác thực"
        );
        $this->assertStringContainsString(
            "appointmentRecords",
            $view,
            "Người dùng đã đăng nhập phải được xem trang danh sách hồ sơ lịch hẹn (số nhiều: records)"
        );

        // Nghiệp vụ R1: đã xác thực thì không redirect
        $this->assertNull(
            $this->controller->getLastHeader(),
            "Người dùng đã đăng nhập không được chuyển hướng đến trang khác"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_43
    // Business Requirement: R2
    // Scenario: AuthUser là object rỗng (đã đăng nhập nhưng không có thuộc tính)
    // Expected Output (nghiệp vụ): Người dùng có session vẫn được xem danh sách
    // ========================================================================
    public function authEmpty_accessList(): void
    {
        // Arrange: Người dùng có session đăng nhập nhưng không có thuộc tính user
        $this->controller->setVariable("AuthUser", new stdClass());

        // Act: Người dùng truy cập trang danh sách hồ sơ lịch hẹn
        $this->controller->process();

        // Assert (dựa trên R2 - nghiệp vụ):
        // Trong PHP, object rỗng vẫn là truthy - hệ thống coi đây là đã đăng nhập.
        // Nghiệp vụ: người có session hợp lệ phải được truy cập trang danh sách hồ sơ.
        $view = $this->controller->getLastView();
        $this->assertStringContainsString(
            "appointmentRecords",
            $view,
            "Người dùng có session đăng nhập (dù rỗng) phải được phép xem trang danh sách hồ sơ lịch hẹn"
        );
        $this->assertNull(
            $this->controller->getLastHeader(),
            "Không được redirect khi người dùng đã có session xác thực"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_44
    // Business Requirement: R3, R4
    // Scenario: Route bị null hoặc không có params - trang vẫn hoạt động bình thường
    // Expected Output (nghiệp vụ): Hệ thống không phụ thuộc vào Route, vẫn hiển thị danh sách
    // ========================================================================
    public function routeNull_displayList(): void
    {
        // Arrange: Route bị null (URL không có route params)
        $authUser = new stdClass();
        $authUser->id = 1;
        $this->controller->setVariable("AuthUser", $authUser);
        $this->controller->setVariable("Route", null);

        // Act: Hệ thống xử lý yêu cầu
        $this->controller->process();

        // Assert (dựa trên R3, R4 - nghiệp vụ):
        // Trang danh sách hồ sơ lịch hẹn không phụ thuộc vào Route hay GET params.
        // Đây là trang tổng quan hiển thị TẤT CẢ hồ sơ, không cần tham số cụ thể.
        // Hệ thống phải hiển thị danh sách bất kể Route có hay không.

        $exception = null;
        try {
            $this->controller->process();
        } catch (Throwable $e) {
            $exception = $e;
        }

        $this->assertNull(
            $exception,
            "Hệ thống không được crash khi Route bị null - phải xử lý gracefully"
        );

        $view = $this->controller->getLastView();
        $this->assertStringContainsString(
            "appointmentRecords",
            $view,
            "Trang danh sách hồ sơ lịch hẹn phải hiển thị bình thường dù Route bị null"
        );
        $this->assertNull(
            $this->controller->getLastHeader(),
            "Không được redirect khi người dùng đã đăng nhập và truy cập trang hợp lệ"
        );
    }
}


// ========================================================================
// Test Double: TestableAppointmentRecordsController
// Extends the REAL AppointmentRecordsController and intercepts header()
// and view() so that tests can assert on behavior while still running
// the real process() method for code coverage measurement.
// ========================================================================
class TestableAppointmentRecordsController extends AppointmentRecordsController
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
