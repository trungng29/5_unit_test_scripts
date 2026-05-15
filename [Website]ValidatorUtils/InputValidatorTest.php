<?php

use PHPUnit\Framework\TestCase;

/**
 * Test class Input — xử lý lấy dữ liệu người dùng gửi lên (form, URL...)
 * File nguồn: umbrella-corporation/app/core/Input.php
     * Điều kiện tiên quyết: Giả lập request người dùng ($GLOBALS)
     * Dữ liệu đầu vào: Truyền method='get' và dữ liệu tương ứng
     * Ghi chú: Lớp Input kiểm soát toàn bộ dữ liệu đầu vào của hệ thống
 */
class InputValidatorTest extends TestCase
{
    /**
     * Test Case ID: TC_S7_18
     * Mô tả: Gọi phương thức không hợp lệ (không phải get/post/request...) phải báo lỗi
     * Kết quả mong đợi: Ném ra lỗi Exception
     * Hàm được test: Input::getInput($method, $input_name) — Lấy dữ liệu người dùng
     *   gửi lên theo các cách khác nhau (GET qua URL, POST qua form, SESSION...).
     *   Là hàm trung tâm xử lý mọi dữ liệu đầu vào trong toàn bộ hệ thống.
     *   Nằm tại: umbrella-corporation/app/core/Input.php (dòng 24-56)
     * Điều kiện tiên quyết: Không có
     * Dữ liệu đầu vào: Truyền method không tồn tại (vd: "invalid_method")
     * Ghi chú: Hệ thống phải bắt lỗi kịp thời nếu developer truyền sai method HTTP để tránh xử lý ngầm sai logic
     */
    public function testLoiKhiGoiPhuongThucSai()
    {
        $this->expectException(\Exception::class);

        Input::getInput("invalid_method", "test_field");
    }

    /**
     * Test Case ID: TC_S7_19
     * Mô tả: Khi trường dữ liệu không tồn tại thì trả về null (trống)
     * Kết quả mong đợi: null
     * Hàm được test: Input::getInput($method, $input_name) — (giống TC_S7_18)
     *   Kiểm tra hàm xử lý đúng khi người dùng không gửi trường dữ liệu nào đó.
     *   Nằm tại: umbrella-corporation/app/core/Input.php (dòng 24-56)
     * Điều kiện tiên quyết: Giả lập không có trường 'khong_co' trong request ($GLOBALS)
     * Dữ liệu đầu vào: Truyền method='get' và tên trường 'khong_co'
     * Ghi chú: Hệ thống không được crash khi thiếu trường, phải trả về null để code bên ngoài dễ kiểm tra bằng if
     */
    public function testTraNullKhiTruongKhongTonTai()
    {
        unset($GLOBALS['_GET']['khong_co']);

        $result = Input::getInput("get", "khong_co");

        $this->assertNull($result);
    }

    /**
     * Test Case ID: TC_S7_20
     * Mô tả: Đọc dữ liệu từ URL (GET) và tự động xoá khoảng trắng thừa đầu cuối
     * Kết quả mong đợi: "   hello world   " → "hello world"
     * Hàm được test: Input::getInput($method, $input_name) — (giống TC_S7_18)
     *   Kiểm tra tính năng tự động xoá khoảng trắng thừa khi đọc dữ liệu,
     *   tránh lưu dữ liệu bẩn vào cơ sở dữ liệu.
     *   Nằm tại: umbrella-corporation/app/core/Input.php (dòng 52-53)
     * Điều kiện tiên quyết: Giả lập trường 'ten' có khoảng trắng thừa 2 đầu trong $GLOBALS
     * Dữ liệu đầu vào: Lấy dữ liệu với input_name='ten'
     * Ghi chú: Đảm bảo dữ liệu từ URL hoặc Form luôn được làm sạch trước khi dùng lưu vào DB
     */
    public function testDocDuLieuVaXoaKhoangTrangThua()
    {
        $GLOBALS['_GET']['ten'] = '   hello world   ';

        $result = Input::getInput("get", "ten");

        $this->assertSame("hello world", $result);

        unset($GLOBALS['_GET']['ten']);
    }

    /**
     * Test Case ID: TC_S7_21
     * Mô tả: Khi dữ liệu là danh sách (mảng), có thể lấy phần tử theo vị trí
     * Kết quả mong đợi: Vị trí 1 trong ["táo", "chuối", "cam"] → "chuối"
     * Hàm được test: Input::getInput($method, $input_name, $index) — (giống TC_S7_18)
     *   Hỗ trợ lấy 1 phần tử cụ thể khi form gửi lên nhiều giá trị
     *   (ví dụ: chọn nhiều checkbox, upload nhiều file).
     *   Nằm tại: umbrella-corporation/app/core/Input.php (dòng 35-45)
     * Điều kiện tiên quyết: Giả lập trường 'items' chứa 1 mảng dữ liệu (ví dụ checkbox)
     * Dữ liệu đầu vào: Tham số index = 1 để lấy phần tử thứ 2 trong mảng
     * Ghi chú: Cho phép truy xuất nhanh 1 phần tử trong danh sách gửi lên thay vì phải lấy cả mảng rồi xử lý sau
     */
    public function testLayPhanTuTheoViTri()
    {
        $GLOBALS['_POST']['items'] = array('táo', 'chuối', 'cam');

        $result = Input::getInput("post", "items", 1);

        $this->assertSame("chuối", $result);

        unset($GLOBALS['_POST']['items']);
    }

    /**
     * Test Case ID: TC_S7_22
     * Mô tả: Lấy vị trí vượt quá số phần tử trong mảng phải báo lỗi
     * Kết quả mong đợi: Ném ra lỗi Exception
     * Hàm được test: Input::getInput($method, $input_name, $index) — (giống TC_S7_18)
     *   Kiểm tra hàm có bắt lỗi khi truy cập vị trí không tồn tại trong mảng.
     *   Nằm tại: umbrella-corporation/app/core/Input.php (dòng 39-41)
     * Điều kiện tiên quyết: Giả lập mảng 'items' chỉ có 2 phần tử (index 0, 1)
     * Dữ liệu đầu vào: Truyền index = 5 (vượt quá độ dài mảng)
     * Ghi chú: Nếu truyền index sai, hàm phải báo lỗi rõ ràng thay vì trả về kết quả sai lệch hoặc cảnh báo ngầm
     */
    public function testLoiKhiViTriVuotQuaMang()
    {
        $GLOBALS['_POST']['items'] = array('táo', 'chuối');

        $this->expectException(\Exception::class);

        Input::getInput("post", "items", 5);

        unset($GLOBALS['_POST']['items']);
    }

    // === CÁC TEST CASE PHÁT HIỆN LỖI (SẼ FAIL) ===

    /**
     * Test Case ID: TC_S7_40
     * Mô tả: Đọc chuỗi "   spaced   " với index=0 → kỳ vọng vẫn xoá khoảng trắng
     *         nhưng thực tế KHÔNG xoá vì 0 bị hiểu là "tắt xoá khoảng trắng"
     * Kết quả mong đợi: "spaced" nhưng thực tế "   spaced   " → LỖI
     * Hàm đang test: Input::getInput() — Lấy thông tin user gửi lên.
     *   Lỗi thiết kế: Dùng 1 tham số (`$index`) cho 2 mục đích khác nhau (vừa để chọn vị trí mảng,
     *   vừa để bật/tắt tự làm sạch chữ). Cực kỳ dễ gây nhầm lẫn khi người khác code.
     *   Nằm tại: umbrella-corporation/app/core/Input.php (dòng 48-49)
     * Điều kiện tiên quyết: Chuỗi chứa khoảng trắng đầu cuối
     * Dữ liệu đầu vào: Tham số thứ 3 (index) được truyền bằng 0
     * Ghi chú: [BUG] Code dùng 0 để kiểm tra falsy khiến nó tắt luôn tính năng xoá khoảng trắng; cần đổi sang so sánh === false
     */
    public function testNhamLanKhiTruyenIndex0()
    {
        $GLOBALS['_GET']['name'] = '   spaced   ';

        $result = Input::getInput("get", "name", 0);

        // Kỳ vọng đã xoá khoảng trắng nhưng thực tế chưa
        $this->assertSame("spaced", $result);

        unset($GLOBALS['_GET']['name']);
    }

    /**
     * Test Case ID: TC_S7_49
     * Description: Kiểm tra Magic Method __callStatic để lấy dữ liệu POST
     * Scenario Type: Positive
     * Expected Result: Lấy thành công dữ liệu qua Input::post()
     * Related Module: Input
     * Precondition: Class Input có __callStatic.
     * Input: call Input::post('username')
     * Notes: Phủ sóng khối lệnh __callStatic.
     */
    public function testCallStaticMethod()
    {
        $GLOBALS['_POST']['username'] = 'admin';
        $result = Input::post('username');
        $this->assertEquals('admin', $result);
        unset($GLOBALS['_POST']['username']);
    }

    /**
     * Test Case ID: TC_S7_50
     * Description: Kiểm tra Magic Method __call để lấy dữ liệu GET
     * Scenario Type: Positive
     * Expected Result: Lấy thành công dữ liệu qua $input->get()
     * Related Module: Input
     * Precondition: Khởi tạo đối tượng Input.
     * Input: call $input->get('page')
     * Notes: Phủ sóng khối lệnh __call.
     */
    public function testCallMethod()
    {
        $GLOBALS['_GET']['page'] = '2';
        $input = new Input();
        $result = $input->get('page');
        $this->assertEquals('2', $result);
        unset($GLOBALS['_GET']['page']);
    }

    /**
     * Test Case ID: TC_S7_51
     * Description: Kiểm tra alias req ánh xạ sang request
     * Scenario Type: Positive
     * Expected Result: Lấy thành công dữ liệu REQUEST qua Input::req()
     * Related Module: Input
     * Precondition: Có dữ liệu trong $_REQUEST.
     * Input: call Input::req('token')
     * Notes: Phủ sóng logic đổi tên 'req' thành 'request'.
     */
    public function testReqAlias()
    {
        $GLOBALS['_REQUEST']['token'] = 'abc';
        $result = Input::req('token');
        $this->assertEquals('abc', $result);
        unset($GLOBALS['_REQUEST']['token']);
    }

    /**
     * Test Case ID: TC_S7_52
     * Description: Ném Exception khi gọi sai method
     * Scenario Type: Negative
     * Expected Result: Ném ra Exception 'Invalid method'
     * Related Module: Input
     * Precondition: Không có phương thức hợp lệ.
     * Input: call Input::unknown()
     * Notes: Phủ sóng logic ném lỗi.
     */
    public function testInvalidMethodException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid method');
        Input::unknown('key');
    }
}
