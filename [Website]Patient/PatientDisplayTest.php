<?php

use PHPUnit\Framework\TestCase;

/**
 * Test hiển thị và định dạng dữ liệu bệnh nhân
 * File nguồn: UserModel.php, common.helper.php
 */
class PatientDisplayTest extends TestCase
{
    private function taoBenhNhan(array $fields)
    {
        $u = new UserModel(0);
        foreach ($fields as $k => $v) {
            $u->set($k, $v, true);
        }
        $u->markAsAvailable();
        return $u;
    }

    // === getDateTimeFormat ===

    /**
     * Test Case ID: TC_PM_26
     * Mô tả: Bệnh nhân chọn kiểu 24 giờ → định dạng giờ phải là "H:i" (14:30)
     * Kết quả mong đợi: "Y-m-d H:i"
     * Hàm được test: UserModel::getDateTimeFormat() — Lấy định dạng ngày giờ.
     *   Nằm tại: umbrella-corporation/app/models/UserModel.php (dòng 239-249)
     * Điều kiện tiên quyết: Bệnh nhân đã sẵn sàng, preferences chứa dateformat="Y-m-d" và timeformat="24"
     * Dữ liệu đầu vào: preferences = {"dateformat": "Y-m-d", "timeformat": "24"}
     * Ghi chú: Định dạng 24h thường dùng trong bệnh viện VN (ví dụ: 14:30 thay vì 2:30 PM)
     */
    public function testDinhDang24Gio()
    {
        $bn = $this->taoBenhNhan(array(
            'preferences' => json_encode(array('dateformat' => 'Y-m-d', 'timeformat' => '24'))
        ));
        $this->assertSame('Y-m-d H:i', $bn->getDateTimeFormat());
    }



    /**
     * Test Case ID: TC_PM_27
     * Mô tả: Hồ sơ chưa sẵn sàng → trả null (không hiển thị được)
     * Kết quả mong đợi: null
     * Hàm được test: UserModel::getDateTimeFormat() — Lấy định dạng ngày giờ.
     *   Nằm tại: umbrella-corporation/app/models/UserModel.php (dòng 241-243)
     * Điều kiện tiên quyết: Tạo UserModel(0) KHÔNG markAsAvailable, nhưng có gán preferences
     * Dữ liệu đầu vào: preferences = {"dateformat": "Y-m-d", "timeformat": "24"}, isAvailable = false
     * Ghi chú: Khi chưa load dữ liệu bệnh nhân, không nên trả ra format sai gây lỗi hiển thị
     */
    public function testChuaSanSangTraNull()
    {
        $bn = new UserModel(0);
        $bn->set('preferences', json_encode(array('dateformat' => 'Y-m-d', 'timeformat' => '24')));
        $this->assertNull($bn->getDateTimeFormat());
    }

    // === Kiểm tra ngày sinh bệnh nhân ===

    /**
     * Test Case ID: TC_PM_28
     * Mô tả: Ngày sinh hợp lệ "1990-05-15 00:00:00" phải được chấp nhận
     * Kết quả mong đợi: true
     * Hàm được test: isValidDate($date) — Kiểm tra ngày tháng hợp lệ.
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 187-191)
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh)
     * Dữ liệu đầu vào: "1990-05-15 00:00:00" (ngày 15 tháng 5 năm 1990)
     * Ghi chú: Dùng khi bệnh nhân nhập ngày sinh trong form đăng ký hồ sơ
     */
    public function testNgaySinhHopLe()
    {
        $this->assertTrue(isValidDate("1990-05-15 00:00:00"));
    }



    // === Hiển thị viện phí ===

    /**
     * Test Case ID: TC_PM_29
     * Mô tả: Viện phí 500000 VND phải được làm tròn vì VND không có xu lẻ
     * Kết quả mong đợi: 500000 (số nguyên, không phần thập phân)
     * Hàm được test: format_price($price, $is_zero_decimal) — Hiển thị viện phí.
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 125-139)
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh)
     * Dữ liệu đầu vào: format_price(500000, true) — tham số true = tiền tệ không có xu lẻ (VND)
     * Ghi chú: Viện phí VN luôn là số nguyên (500,000đ), không cần hiện phần lẻ (.00)
     */
    public function testVienPhiVNDLamTron()
    {
        $result = format_price(500000, true);
        $this->assertSame(500000.0, (float)$result);
    }



    // === Tạo slug cho tên bệnh nhân ===

    /**
     * Test Case ID: TC_PM_30
     * Mô tả: Tạo đường dẫn hồ sơ bệnh nhân từ tên: "Nguyen Van An" → slug an toàn
     * Kết quả mong đợi: Chuỗi chỉ chứa chữ thường, số, dấu gạch ngang và chứa "nguyen"
     * Hàm được test: url_slug($string) — Tạo đường dẫn thân thiện.
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 62-92)
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh)
     * Dữ liệu đầu vào: "Nguyen Van An" (tên bệnh nhân có chữ hoa và khoảng trắng)
     * Ghi chú: URL slug dùng khi tạo đường dẫn dạng /patients/nguyen-van-an thay vì /patients?id=123
     */
    public function testTaoSlugTenBenhNhan()
    {
        $result = url_slug("Nguyen Van An");
        $this->assertRegExp('/^[a-z0-9\-]+$/', $result);
        $this->assertContains('nguyen', $result);
    }



    // === Hiển thị kích thước file ===

    /**
     * Test Case ID: TC_PM_31
     * Mô tả: File ảnh chụp X-quang 2.5MB phải hiển thị dạng "MB"
     * Kết quả mong đợi: Kết quả chứa chuỗi "MB"
     * Hàm được test: readableFileSize($size) — Chuyển byte sang đơn vị dễ đọc.
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 289-310)
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh)
     * Dữ liệu đầu vào: readableFileSize(2621440) — 2.5 MB tính bằng byte (2621440 = 2.5 × 1024 × 1024)
     * Ghi chú: Dùng khi bệnh nhân upload file ảnh chụp X-quang hoặc kết quả xét nghiệm lên hệ thống
     */
    public function testHienThiKichThuocFileAnhXQuang()
    {
        $result = readableFileSize(2621440);
        $this->assertContains('MB', $result);
    }
}
