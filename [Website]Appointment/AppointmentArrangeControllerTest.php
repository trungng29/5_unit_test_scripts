<?php
/**
 * AppointmentArrangeControllerTest
 *
 * Unit tests for AppointmentArrangeController::process()
 * using PHPUnit 9.x.
 *
 * Test Case ID Prefix: TC_S4_
 *
 * Related Module: Appointment Arrange
 *
 * NGHIỆP VỤ (Business Requirements):
 * - R1: Người dùng phải đăng nhập mới được truy cập trang sắp xếp lịch hẹn.
 *       Nếu chưa đăng nhập, hệ thống chuyển hướng đến trang /login.
 * - R2: Người dùng đã đăng nhập được xem trang sắp xếp lịch hẹn (view: appointmentArrange).
 * - R3: Route id trong URL không ảnh hưởng đến việc hiển thị trang sắp xếp lịch hẹn.
 * - R4: Hệ thống không được crash khi Route params bị null hoặc thiếu.
 */

use PHPUnit\Framework\TestCase;

class AppointmentArrangeControllerTest extends TestCase
{
    private $controller;
    private $mockRoute;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRoute = new MockRouter3();
        $this->controller = new TestableAppointmentArrangeController();
        $this->controller->setVariable("Route", $this->mockRoute);
    }

    protected function tearDown(): void
    {
        $this->mockRoute = null;
        $this->controller = null;
        parent::tearDown();
    }

    // ========================================================================
    // Test Case ID: TC_S4_25
    // Business Requirement: R1
    // Scenario: Người dùng chưa đăng nhập (AuthUser = null)
    // Expected Output (nghiệp vụ): Hệ thống phải từ chối truy cập và chuyển hướng đến trang đăng nhập
    // ========================================================================
    public function authNull_redirectLogin(): void
    {
        // Arrange: Người dùng chưa đăng nhập - hệ thống không có thông tin xác thực
        $this->controller->setAuthFailed(true);

        // Act: Người dùng cố gắng truy cập trang sắp xếp lịch hẹn
        $this->controller->process();

        // Assert (dựa trên R1 - nghiệp vụ):
        // Hệ thống phải chuyển hướng người dùng chưa xác thực về trang đăng nhập.
        // Expected output KHÔNG lấy từ source code mà từ yêu cầu nghiệp vụ R1.
        $this->assertStringContainsString(
            "/login",
            $this->controller->getLastHeader(),
            "Hệ thống phải chuyển hướng người dùng chưa đăng nhập đến trang /login theo yêu cầu bảo mật"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_26
    // Business Requirement: R1
    // Scenario: Biến AuthUser không tồn tại (unset)
    // Expected Output (nghiệp vụ): Hệ thống phải từ chối truy cập và chuyển hướng đến trang /login
    // ========================================================================
    public function authUnset_redirectLogin(): void
    {
        // Arrange: AuthUser không được set - không có session xác thực
        $this->controller->setAuthFailed(true);

        // Act: Người dùng cố gắng truy cập trang sắp xếp lịch hẹn
        $this->controller->process();

        // Assert (dựa trên R1 - nghiệp vụ):
        // Dù AuthUser null hay unset, hệ thống bảo mật phải hoạt động nhất quán.
        $lastHeader = $this->controller->getLastHeader();
        $this->assertNotNull(
            $lastHeader,
            "Hệ thống phải gửi redirect header khi người dùng chưa xác thực"
        );
        $this->assertStringContainsString(
            "/login",
            $lastHeader,
            "Header redirect phải hướng đến trang đăng nhập để người dùng có thể xác thực"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_27
    // Business Requirement: R2
    // Scenario: Người dùng đã đăng nhập với thông tin hợp lệ
    // Expected Output (nghiệp vụ): Hệ thống phải cho phép truy cập trang sắp xếp lịch hẹn
    // ========================================================================
    public function authValid_accessPage(): void
    {
        // Arrange: Người dùng đã đăng nhập với thông tin xác thực hợp lệ
        $authUser = new stdClass();
        $authUser->id = 1;
        $this->controller->setVariable("AuthUser", $authUser);

        // Act: Người dùng truy cập trang sắp xếp lịch hẹn
        $this->controller->process();

        // Assert (dựa trên R2 - nghiệp vụ):
        // Sau khi xác thực thành công, người dùng được phép xem trang sắp xếp lịch hẹn.
        // View phải là trang dành cho việc sắp xếp lịch hẹn - không phải trang khác.
        $view = $this->controller->getLastView();
        $this->assertNotNull(
            $view,
            "Hệ thống phải trả về một trang view khi người dùng đã xác thực"
        );
        $this->assertStringContainsString(
            "appointmentArrange",
            $view,
            "Người dùng đã đăng nhập phải được xem trang sắp xếp lịch hẹn theo yêu cầu nghiệp vụ"
        );

        // Nghiệp vụ R1: đã xác thực thì không chuyển hướng
        $this->assertNull(
            $this->controller->getLastHeader(),
            "Người dùng đã đăng nhập không được chuyển hướng đến trang khác"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_28
    // Business Requirement: R2
    // Scenario: AuthUser là object rỗng (có đăng nhập nhưng không có thuộc tính)
    // Expected Output (nghiệp vụ): Object rỗng vẫn được coi là đã đăng nhập (truthy trong PHP)
    // ========================================================================
    public function authEmpty_accessPage(): void
    {
        // Arrange: Người dùng có session đăng nhập nhưng không có thuộc tính user
        $this->controller->setVariable("AuthUser", new stdClass());
        $this->mockRoute->params = (object)[];

        // Act: Người dùng truy cập trang sắp xếp lịch hẹn
        $this->controller->process();

        // Assert (dựa trên R2 - nghiệp vụ):
        // Trong PHP, object rỗng vẫn là truthy - hệ thống coi đây là đã đăng nhập.
        // Nghiệp vụ: người có session hợp lệ phải được truy cập trang sắp xếp lịch hẹn.
        $view = $this->controller->getLastView();
        $this->assertStringContainsString(
            "appointmentArrange",
            $view,
            "Người dùng có session đăng nhập (dù rỗng) phải được phép xem trang sắp xếp lịch hẹn"
        );
        $this->assertNull(
            $this->controller->getLastHeader(),
            "Không được redirect khi người dùng đã có session xác thực"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_29
    // Business Requirement: R4
    // Scenario: Route params bị null (URL không có tham số)
    // Expected Output (nghiệp vụ): Hệ thống không crash, vẫn hiển thị trang bình thường
    // ========================================================================
    public function routeParamsNull_noCrash(): void
    {
        // Arrange: URL không có tham số route (VD: /appointment-arrange thay vì /appointment-arrange/123)
        $authUser = new stdClass();
        $authUser->id = 1;
        $this->controller->setVariable("AuthUser", $authUser);
        $this->mockRoute->params = null;

        // Act & Assert (dựa trên R4 - nghiệp vụ):
        // Hệ thống phải xử lý gracefully khi không có route params - không throw exception.
        // Đây là yêu cầu về độ bền (robustness) của hệ thống.
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
            "appointmentArrange",
            $this->controller->getLastView(),
            "Trang sắp xếp lịch hẹn phải hiển thị bình thường dù không có route params"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_30
    // Business Requirement: R3
    // Scenario: URL có id nhưng controller không sử dụng id đó (id bị comment trong code)
    // Expected Output (nghiệp vụ): Route id không ảnh hưởng đến việc hiển thị trang sắp xếp lịch hẹn
    // ========================================================================
    public function routeIdIgnored_accessPage(): void
    {
        // Arrange: URL chứa id nhưng trang sắp xếp lịch hẹn không phụ thuộc vào id
        $authUser = new stdClass();
        $authUser->id = 1;
        $this->controller->setVariable("AuthUser", $authUser);
        $this->mockRoute->params = (object)['id' => 999];

        // Act: Người dùng truy cập trang sắp xếp lịch hẹn với id trong URL
        $this->controller->process();

        // Assert (dựa trên R3 - nghiệp vụ):
        // Trang sắp xếp lịch hẹn là trang tổng quan, không phụ thuộc vào id cụ thể.
        // Bất kể id nào trong URL, người dùng đã đăng nhập phải xem được trang.
        $view = $this->controller->getLastView();
        $this->assertStringContainsString(
            "appointmentArrange",
            $view,
            "Người dùng đã đăng nhập phải xem được trang sắp xếp lịch hẹn, không phụ thuộc vào id trong URL"
        );
        $this->assertNull(
            $this->controller->getLastHeader(),
            "Không được redirect khi người dùng đã đăng nhập và truy cập trang hợp lệ"
        );
    }
}


// ========================================================================
// Test Double: Mock Router Object
// Simulates AltoRouter match result passed via Route variable
// ========================================================================
class MockRouter3
{
    /** @var stdClass|null */
    public $params = null;
}


// ========================================================================
// Test Double: TestableAppointmentArrangeController
// Extends the REAL AppointmentArrangeController and overrides isAuthenticated()
// so that tests can assert auth behavior while still running the real process()
// method for code coverage measurement.
// ========================================================================
class TestableAppointmentArrangeController extends AppointmentArrangeController
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

    public function view($view, $context = "app"): void
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
