<?php

use PHPUnit\Framework\TestCase;

/**
 * Test class Config (cấu hình), Event (sự kiện), Spintax (sinh văn bản ngẫu nhiên)
 * File nguồn: core/Config.php, core/Event.php, lib/Spintax.php
     * Điều kiện tiên quyết: Môi trường PHP cơ bản
     * Dữ liệu đầu vào: Chuỗi cấu hình, tên sự kiện, hoặc cú pháp spintax
     * Ghi chú: Lưu cấu hình toàn cục trong ứng dụng
 */
class ConfigEventSpintaxValidatorTest extends TestCase
{
    // === Config: Lưu trữ cài đặt dạng key-value ===

    /**
     * Test Case ID: TC_S7_30
     * Mô tả: Lưu 1 cài đặt rồi đọc lại → phải đúng giá trị đã lưu
     * Kết quả mong đợi: Config::get trả về "Umbrella Corp"
     * Hàm được test: Config::set($key, $value) và Config::get($key) —
     *   Nơi lưu trữ các cấu hình tĩnh trong suốt vòng đời của ứng dụng.
     *   Các file hệ thống hay dùng nó để lấy cài đặt (ví dụ: Config::get('app_name')).
     *   Nằm tại: umbrella-corporation/app/core/Config.php (dòng 16-30)
     * Điều kiện tiên quyết: Không có
     * Dữ liệu đầu vào: Set app_name = "Umbrella Corp" và Get lại app_name
     * Ghi chú: Đảm bảo class Config lưu và đọc đúng các giá trị cấu hình cơ bản ở memory tĩnh
     */
    public function testLuuVaDocCaiDat()
    {
        Config::set("app_name", "Umbrella Corp");

        $this->assertSame("Umbrella Corp", Config::get("app_name"));
    }

    /**
     * Test Case ID: TC_S7_31
     * Mô tả: Đọc cài đặt chưa từng lưu → trả về null (trống)
     * Kết quả mong đợi: null
     * Hàm được test: Config::get($key) — (giống TC_S7_30)
     *   Đảm bảo khi lấy một cấu hình không tồn tại sẽ không văng lỗi sập ứng dụng.
     *   Nằm tại: umbrella-corporation/app/core/Config.php (dòng 16-19)
     * Điều kiện tiên quyết: Gọi key không tồn tại trong Config
     * Dữ liệu đầu vào: Lấy Config::get("khong_ton_tai_" . uniqid())
     * Ghi chú: Đảm bảo khi lấy một cấu hình bị sai key hoặc chưa cài đặt, hệ thống trả về null thay vì Fatal Error
     */
    public function testTraNullKhiCaiDatChuaLuu()
    {
        $result = Config::get("khong_ton_tai_" . uniqid());

        $this->assertNull($result);
    }

    // === Event: Hệ thống sự kiện (đăng ký → kích hoạt) ===

    /**
     * Test Case ID: TC_S7_32
     * Mô tả: Đăng ký 1 hành động cho sự kiện, khi kích hoạt sự kiện thì hành động phải chạy
     * Kết quả mong đợi: Biến $daChay chuyển từ false → true
     * Hàm được test: Event::bind($event, $func) và Event::trigger($event) —
     *   Hệ thống hook/plugin của mã nguồn. Cho phép các module khác "lắng nghe" (bind)
     *   một sự kiện, và khi hệ thống "gọi" (trigger) thì các module đó sẽ chạy.
     *   Nằm tại: umbrella-corporation/app/core/Event.php (dòng 19-43)
     * Điều kiện tiên quyết: Định nghĩa hàm callback (cập nhật biến $daChay)
     * Dữ liệu đầu vào: Gọi Event::bind() rồi Event::trigger() với cùng tên sự kiện
     * Ghi chú: Tính năng cốt lõi cho phép hệ thống gọi hook/plugin linh hoạt mà không cần can thiệp core code
     */
    public function testDangKyVaKichHoatSuKien()
    {
        $daChay = false;
        $tenSuKien = "test.event." . uniqid();

        Event::bind($tenSuKien, function () use (&$daChay) {
            $daChay = true;
        });

        Event::trigger($tenSuKien);

        $this->assertTrue($daChay);
    }

    // === Spintax: Sinh văn bản ngẫu nhiên từ mẫu ===

    /**
     * Test Case ID: TC_S7_33
     * Mô tả: Cú pháp {A|B|C} sẽ chọn ngẫu nhiên 1 trong 3 → kết quả phải hợp lệ
     * Kết quả mong đợi: Kết quả là "Hello World" hoặc "Hi World" hoặc "Hey World"
     * Hàm được test: Spintax::process($text) — Tính năng "spin bài".
     *   Sinh ra nhiều đoạn văn bản khác nhau từ 1 mẫu để tránh bị đánh dấu thư rác/spam.
     *   Thường dùng khi gửi email hàng loạt hoặc đăng bài tự động lên MXH.
     *   Nằm tại: umbrella-corporation/app/lib/Spintax.php (dòng 10-17)
     * Điều kiện tiên quyết: Cung cấp chuỗi mẫu Spintax (vd: {A|B|C})
     * Dữ liệu đầu vào: Chuỗi "{Hello|Hi|Hey} World"
     * Ghi chú: Kết quả phải luôn hợp lệ dựa trên các từ cho sẵn, hỗ trợ đổi nội dung email/SMS hàng loạt
     */
    public function testChonNgauNhien1BienThe()
    {
        $result = Spintax::process("{Hello|Hi|Hey} World");

        $this->assertRegExp('/^(Hello|Hi|Hey) World$/', $result);
    }

    /**
     * Test Case ID: TC_S7_34
     * Mô tả: Cú pháp lồng nhau {{A|B} C|{D|E} F} cũng phải xử lý được
     * Kết quả mong đợi: Kết quả không còn dấu ngoặc nhọn { }
     * Hàm được test: Spintax::process($text) — (giống TC_S7_33)
     *   Đảm bảo Spintax hoạt động ổn định với cấu trúc nhiều lớp phức tạp.
     *   Nằm tại: umbrella-corporation/app/lib/Spintax.php (dòng 10-17)
     * Điều kiện tiên quyết: Cung cấp chuỗi mẫu Spintax chứa nhiều lớp lồng nhau
     * Dữ liệu đầu vào: Chuỗi "{{Good|Nice} morning|{Good|Nice} evening}"
     * Ghi chú: Đảm bảo regex đệ quy hoạt động tốt với cấu trúc lồng nhau sâu, văn bản đầu ra không còn dấu ngoặc nhọn
     */
    public function testXuLyCuPhapLongNhau()
    {
        $result = Spintax::process("{{Good|Nice} morning|{Good|Nice} evening}");

        $this->assertNotContains("{", $result);
        $this->assertNotContains("}", $result);
        $this->assertNotEmpty($result);
    }
}
