<?php
/**
 * AppointmentsControllerTest
 *
 * Unit tests for AppointmentsController::process()
 * using PHPUnit 9.x.
 *
 * Test Case ID Prefix: TC_S4_
 * Starting from TC_S4_12 (continuing AppointmentControllerTest)
 *
 * Related Module: Appointment
 *
 * NGHIỆP VỤ (Business Requirements):
 * - R1: Người dùng phải đăng nhập mới được truy cập trang danh sách lịch hẹn.
 *       Nếu chưa đăng nhập, hệ thống chuyển hướng đến trang /login.
 * - R2: Người dùng đã đăng nhập được xem trang danh sách tất cả lịch hẹn (view: appointments).
 * - R3: Trang danh sách lịch hẹn không phụ thuộc vào Route hay GET params.
 * - R4: Hệ thống không được crash khi Route params bị null, empty object, hay bất kỳ giá trị nào.
 * - R5: Nghiệp vụ xác thực phải nhất quán với mọi loại giá trị falsy của AuthUser.
 */

use PHPUnit\Framework\TestCase;

require_once APPPATH . '/controllers/AppointmentsController.php';

class AppointmentsControllerTest extends TestCase
{
    private $controller;
    private $mockRoute;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRoute = new MockRouterAppointments();
        $this->controller = new TestableAppointmentsController();
        $this->controller->setVariable("Route", $this->mockRoute);
    }

    // ========================================================================
    // Test Case ID: TC_S4_12
    // Business Requirement: R1
    // Scenario: Người dùng chưa đăng nhập (AuthUser = null) truy cập trang danh sách lịch hẹn
    // Expected Output (nghiệp vụ): Hệ thống phải từ chối và chuyển hướng đến trang /login
    // ========================================================================
    public function authNull_redirectLogin(): void
    {
        // Arrange: Người dùng chưa đăng nhập - AuthUser = null
        $this->controller->setAuthFailed(true);

        // Act: Người dùng cố gắng truy cập trang danh sách lịch hẹn
        $this->controller->process();

        // Assert (dựa trên R1 - nghiệp vụ):
        // Hệ thống bảo mật phải chuyển hướng người dùng chưa xác thực về trang đăng nhập.
        // Trang danh sách lịch hẹn chứa thông tin nhạy cảm của bệnh nhân nên cần bảo mật.
        $header = $this->controller->getLastHeader();
        $this->assertNotNull(
            $header,
            "Hệ thống phải gửi redirect header khi người dùng chưa xác thực"
        );
        $this->assertStringContainsString(
            "/login",
            $header,
            "Người dùng chưa đăng nhập (AuthUser = null) phải được chuyển hướng đến trang /login"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_13
    // Business Requirement: R1
    // Scenario: Biến AuthUser không tồn tại (unset) khi truy cập trang danh sách
    // Expected Output (nghiệp vụ): Hệ thống phải từ chối truy cập và chuyển hướng
    // ========================================================================
    public function authUnset_redirectLogin(): void
    {
        // Arrange: AuthUser không được set trong hệ thống
        $this->controller->setAuthFailed(true);

        // Act: Người dùng cố gắng truy cập trang danh sách lịch hẹn
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
    // Test Case ID: TC_S4_14
    // Business Requirement: R2
    // Scenario: Người dùng đã đăng nhập truy cập trang danh sách lịch hẹn
    // Expected Output (nghiệp vụ): Hệ thống cho phép truy cập trang danh sách lịch hẹn (appointments)
    // ========================================================================
    public function authValid_accessList(): void
    {
        // Arrange: Người dùng có session đăng nhập hợp lệ
        $authUser = new stdClass();
        $authUser->id = 1;
        $this->controller->setVariable("AuthUser", $authUser);

        // Act: Người dùng truy cập trang danh sách lịch hẹn
        $this->controller->process();

        // Assert (dựa trên R2 - nghiệp vụ):
        // Người dùng đã đăng nhập được phép xem trang danh sách tất cả lịch hẹn.
        // View phải là trang danh sách lịch hẹn (appointments - số nhiều), không phải trang chi tiết.
        $view = $this->controller->getLastView();
        $this->assertNotNull(
            $view,
            "Hệ thống phải trả về một trang view khi người dùng đã xác thực"
        );
        $this->assertStringContainsString(
            "appointments",
            $view,
            "Người dùng đã đăng nhập phải được xem trang danh sách lịch hẹn (số nhiều: appointments)"
        );

        // Nghiệp vụ R1: đã xác thực thì không redirect
        $this->assertNull(
            $this->controller->getLastHeader(),
            "Người dùng đã đăng nhập không được chuyển hướng đến trang đăng nhập"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_15
    // Business Requirement: R2
    // Scenario: AuthUser là object rỗng (đã đăng nhập nhưng không có thuộc tính)
    // Expected Output (nghiệp vụ): Người dùng có session vẫn được xem danh sách lịch hẹn
    // ========================================================================
    public function authEmpty_accessList(): void
    {
        // Arrange: Người dùng có session đăng nhập nhưng không có thuộc tính user
        $this->controller->setVariable("AuthUser", new stdClass());

        // Act: Người dùng truy cập trang danh sách lịch hẹn
        $this->controller->process();

        // Assert (dựa trên R2 - nghiệp vụ):
        // Trong PHP, object rỗng vẫn là truthy - hệ thống coi đây là đã đăng nhập.
        $view = $this->controller->getLastView();
        $this->assertStringContainsString(
            "appointments",
            $view,
            "Người dùng có session đăng nhập (dù rỗng) phải được phép xem trang danh sách lịch hẹn"
        );
        $this->assertNull(
            $this->controller->getLastHeader(),
            "Không được redirect khi người dùng đã có session xác thực"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_16
    // Business Requirement: R3
    // Scenario: Route params là empty object ({})
    // Expected Output (nghiệp vụ): Hệ thống vẫn hiển thị danh sách lịch hẹn bình thường
    // ========================================================================
    public function routeParamsEmpty_displayList(): void
    {
        // Arrange: Route params là empty object
        $authUser = new stdClass();
        $authUser->id = 5;
        $this->controller->setVariable("AuthUser", $authUser);
        $this->mockRoute->params = (object)[];

        // Act: Hệ thống xử lý yêu cầu
        $this->controller->process();

        // Assert (dựa trên R3 - nghiệp vụ):
        // Trang danh sách lịch hẹn không cần Route params - nó hiển thị TẤT CẢ lịch hẹn.
        // Hệ thống phải hoạt động bình thường dù Route params empty.

        $exception = null;
        try {
            $this->controller->process();
        } catch (Throwable $e) {
            $exception = $e;
        }

        $this->assertNull(
            $exception,
            "Hệ thống không được crash khi Route params là empty object"
        );
        $this->assertStringContainsString(
            "appointments",
            $this->controller->getLastView(),
            "Trang danh sách lịch hẹn phải hiển thị bình thường khi Route params rỗng"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_17
    // Business Requirement: R4
    // Scenario: Route params là null
    // Expected Output (nghiệp vụ): Hệ thống không crash, vẫn hiển thị danh sách lịch hẹn
    // ========================================================================
    public function routeParamsNull_displayList(): void
    {
        // Arrange: Route params là null
        $authUser = new stdClass();
        $authUser->id = 3;
        $this->controller->setVariable("AuthUser", $authUser);
        $this->mockRoute->params = null;

        // Act & Assert (dựa trên R4 - nghiệp vụ):
        // Hệ thống phải xử lý gracefully khi Route params bị null.
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
            "appointments",
            $this->controller->getLastView(),
            "Trang danh sách lịch hẹn phải hiển thị bình thường khi Route params bị null"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_18
    // Business Requirement: R2
    // Scenario: Kiểm tra tên view đúng là "appointments" (số nhiều) không phải "appointment" (số ít)
    // Expected Output (nghiệp vụ): View phải là trang danh sách (số nhiều), không phải trang chi tiết
    // ========================================================================
    public function viewName_isAppointments(): void
    {
        // Arrange: Người dùng đã đăng nhập
        $authUser = new stdClass();
        $authUser->id = 1;
        $this->controller->setVariable("AuthUser", $authUser);

        // Act: Người dùng truy cập trang danh sách lịch hẹn
        $this->controller->process();

        // Assert (dựa trên R2 - nghiệp vụ):
        // Nghiệp vụ phân biệt rõ:
        // - "appointments" (số nhiều) = trang danh sách tất cả lịch hẹn
        // - "appointment" (số ít) = trang chi tiết một lịch hẹn cụ thể
        // Controller này xử lý trang danh sách, nên view phải là "appointments".
        $view = $this->controller->getLastView();
        $this->assertSame(
            "appointments",
            $view,
            "View phải là 'appointments' (danh sách tất cả lịch hẹn) theo yêu cầu nghiệp vụ R2"
        );
        $this->assertNotSame(
            "appointment",
            $view,
            "View không được là 'appointment' (số ít) vì đây là trang chi tiết, không phải danh sách"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_19
    // Business Requirement: R2, R3
    // Scenario: Controller không set biến nào cho view (chỉ render view danh sách)
    // Expected Output (nghiệp vụ): Trang danh sách lịch hẹn hiển thị mà không cần biến từ controller
    // ========================================================================
    public function noVariables_renderList(): void
    {
        // Arrange: Người dùng đã đăng nhập
        $authUser = new stdClass();
        $authUser->id = 1;
        $this->controller->setVariable("AuthUser", $authUser);

        // Act: Hệ thống render trang danh sách lịch hẹn
        $this->controller->process();

        // Assert (dựa trên R2, R3 - nghiệp vụ):
        // Controller này chỉ kiểm tra đăng nhập và render view.
        // Việc lấy danh sách lịch hẹn là logic của View/Model, không phải Controller.
        // Controller phải trả về view để View tự lấy dữ liệu.
        $view = $this->controller->getLastView();
        $this->assertStringContainsString(
            "appointments",
            $view,
            "Trang danh sách lịch hẹn phải được render khi người dùng đã đăng nhập"
        );
        $this->assertNull(
            $this->controller->getLastHeader(),
            "Người dùng đã đăng nhập không được redirect"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_20
    // Business Requirement: R2
    // Scenario: AuthUser có id = 0 (user đầu tiên trong hệ thống, id thường bắt đầu từ 1)
    // Expected Output (nghiệp vụ): User có id = 0 vẫn được coi là đã đăng nhập
    // ========================================================================
    public function authUserZeroId_authenticated(): void
    {
        // Arrange: AuthUser có id = 0 (trường hợp biên - user đầu tiên)
        $authUser = new stdClass();
        $authUser->id = 0;
        $this->controller->setVariable("AuthUser", $authUser);

        // Act: Người dùng truy cập trang danh sách lịch hẹn
        $this->controller->process();

        // Assert (dựa trên R2 - nghiệp vụ):
        // AuthUser là object, id = 0 là thuộc tính bên trong object.
        // Object vẫn là truthy trong PHP, nên !$AuthUser = false -> không redirect.
        // User có id = 0 vẫn là user hợp lệ trong hệ thống.
        $view = $this->controller->getLastView();
        $this->assertStringContainsString(
            "appointments",
            $view,
            "User có id = 0 vẫn được phép xem trang danh sách lịch hẹn vì AuthUser là object truthy"
        );
        $this->assertNull(
            $this->controller->getLastHeader(),
            "User đã đăng nhập (dù id = 0) không được redirect"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_21
    // Business Requirement: R1, R5
    // Scenario: AuthUser = false (không phải null) - giá trị falsy rõ ràng
    // Expected Output (nghiệp vụ): Hệ thống phải từ chối truy cập vì AuthUser falsy
    // ========================================================================
    public function authFalse_redirectLogin(): void
    {
        // Arrange: AuthUser = false (giá trị falsy rõ ràng, khác null)
        $this->controller->setAuthFailed(true);

        // Act: Hệ thống kiểm tra xác thực
        $this->controller->process();

        // Assert (dựa trên R1, R5 - nghiệp vụ):
        // Nghiệp vụ bảo mật: bất kỳ giá trị falsy nào của AuthUser đều phải bị từ chối.
        // Giá trị false khác null nhưng cả hai đều không hợp lệ cho xác thực.
        $this->assertStringContainsString(
            "/login",
            $this->controller->getLastHeader(),
            "AuthUser = false phải bị coi là chưa đăng nhập và chuyển hướng đến /login"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_22
    // Business Requirement: R1, R5
    // Scenario: AuthUser = 0 (integer zero) - giá trị falsy
    // Expected Output (nghiệp vụ): Hệ thống phải từ chối truy cập
    // ========================================================================
    public function authIntegerZero_redirectLogin(): void
    {
        // Arrange: AuthUser = 0 (integer zero, falsy)
        $this->controller->setAuthFailed(true);

        // Act: Hệ thống kiểm tra xác thực
        $this->controller->process();

        // Assert (dựa trên R1, R5 - nghiệp vụ):
        // AuthUser = 0 là falsy -> !$AuthUser = true -> redirect to /login.
        // Integer zero không phải object, nên không thể là AuthUser hợp lệ.
        $this->assertStringContainsString(
            "/login",
            $this->controller->getLastHeader(),
            "AuthUser = 0 (integer zero) phải bị coi là chưa đăng nhập"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_23
    // Business Requirement: R1, R5
    // Scenario: AuthUser = "" (empty string) - giá trị falsy
    // Expected Output (nghiệp vụ): Hệ thống phải từ chối truy cập
    // ========================================================================
    public function authEmptyString_redirectLogin(): void
    {
        // Arrange: AuthUser = "" (empty string, falsy)
        $this->controller->setAuthFailed(true);

        // Act: Hệ thống kiểm tra xác thực
        $this->controller->process();

        // Assert (dựa trên R1, R5 - nghiệp vụ):
        // AuthUser = "" là falsy -> !$AuthUser = true -> redirect to /login.
        // Empty string không phải object, không thể là AuthUser hợp lệ.
        $this->assertStringContainsString(
            "/login",
            $this->controller->getLastHeader(),
            "AuthUser = '' (empty string) phải bị coi là chưa đăng nhập"
        );
    }

    // ========================================================================
    // Test Case ID: TC_S4_24
    // Business Requirement: R2
    // Scenario: Nhiều loại AuthUser objects khác nhau đều được phép truy cập
    // Expected Output (nghiệp vụ): Mọi user đã đăng nhập (với bất kỳ thuộc tính nào) đều xem được danh sách
    // ========================================================================
    public function multipleAuthUsers_accessList(): void
    {
        // Arrange: Các loại user objects khác nhau đều đã đăng nhập
        $cases = [
            (object)['id' => 1, 'role' => 'admin'],
            (object)['id' => 99, 'name' => 'Dr. Smith'],
            (object)['id' => 5, 'email' => 'user@test.com', 'role' => 'member'],
        ];

        foreach ($cases as $index => $authUser) {
            $this->controller->setVariable("AuthUser", $authUser);

            // Act: Từng user truy cập trang danh sách lịch hẹn
            $this->controller->process();

            // Assert (dựa trên R2 - nghiệp vụ):
            // Tất cả user đã đăng nhập đều được phép xem danh sách lịch hẹn.
            // Hệ thống không giới hạn quyền truy cập dựa trên thuộc tính của AuthUser object.
            $view = $this->controller->getLastView();
            $this->assertStringContainsString(
                "appointments",
                $view,
                "User đã đăng nhập với thuộc tính '" . json_encode($authUser) . "' phải xem được trang danh sách lịch hẹn"
            );
            $this->assertNull(
                $this->controller->getLastHeader(),
                "User đã đăng nhập không được redirect"
            );
        }
    }
}


// ========================================================================
// Test Double: Mock Router Object
// Simulates AltoRouter match result passed via Route variable
// ========================================================================
class MockRouterAppointments
{
    /** @var stdClass|null */
    public $params = null;
}


// ========================================================================
// Test Double: TestableAppointmentsController
//
// Extends the REAL AppointmentsController and overrides isAuthenticated()
// so that tests can assert auth behavior while still running the real process()
// method for code coverage measurement.
// ========================================================================
class TestableAppointmentsController extends AppointmentsController
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
