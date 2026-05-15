<?php

use PHPUnit\Framework\TestCase;

/**
 * Test class DataEntry — lớp cơ sở lưu trữ dữ liệu (như 1 dòng trong bảng)
 * File nguồn: umbrella-corporation/app/core/DataEntry.php
 */
class DataEntryValidatorTest extends TestCase
{
    /**
     * Test Case ID: TC_S7_23
     * Mô tả: Khi mới tạo, dữ liệu chưa sẵn sàng sử dụng (chưa load từ database)
     * Kết quả mong đợi: isAvailable() trả về false
     * Hàm được test: DataEntry::isAvailable() — Kiểm tra xem đối tượng DataEntry
     *   đã chứa dữ liệu thực sự (được load từ Database) hay chưa.
     *   Dùng để biết khi nào được phép gọi save() hay update() trong Model.
     *   Nằm tại: umbrella-corporation/app/core/DataEntry.php (dòng 23-26)
     * Điều kiện tiên quyết: Khởi tạo đối tượng DataEntry mới bằng new DataEntry()
     * Dữ liệu đầu vào: Gọi hàm isAvailable()
     * Ghi chú: Trạng thái mặc định phải là "chưa sẵn sàng" để ngăn chặn các thao tác gọi update() lên đối tượng rỗng
     */
    public function testMoiTaoChuaSanSang()
    {
        $entry = new DataEntry();

        $this->assertFalse($entry->isAvailable());
    }

    /**
     * Test Case ID: TC_S7_24
     * Mô tả: Sau khi đánh dấu sẵn sàng, dữ liệu có thể được sử dụng
     * Kết quả mong đợi: isAvailable() trả về true
     * Hàm được test: DataEntry::markAsAvailable() — Đánh dấu rằng đối tượng đã
     *   có dữ liệu hợp lệ. Thường được gọi sau khi fetch dữ liệu từ Database.
     *   Nằm tại: umbrella-corporation/app/core/DataEntry.php (dòng 32-36)
     * Điều kiện tiên quyết: Đối tượng DataEntry mới (isAvailable = false)
     * Dữ liệu đầu vào: Gọi hàm markAsAvailable()
     * Ghi chú: Sau khi đánh dấu, hệ thống mới cho phép gọi save() hoặc update() để lưu vào DB
     */
    public function testDanhDauSanSang()
    {
        $entry = new DataEntry();

        $entry->markAsAvailable();

        $this->assertTrue($entry->isAvailable());
    }

    /**
     * Test Case ID: TC_S7_25
     * Mô tả: Lưu chuỗi có khoảng trắng thừa → khi đọc ra tự động xoá khoảng trắng
     * Kết quả mong đợi: "  John Doe  " → "John Doe"
     * Hàm được test: DataEntry::set($field, $value) và get($field) —
     *   Lưu trữ và lấy giá trị của các cột trong bảng. Hàm set tự động
     *   trim() đối với chuỗi văn bản để làm sạch dữ liệu.
     *   Nằm tại: umbrella-corporation/app/core/DataEntry.php (dòng 54-85 và 95-108)
     * Điều kiện tiên quyết: Khởi tạo đối tượng DataEntry (hoặc class con)
     * Dữ liệu đầu vào: name = "  John Doe  " (có khoảng trắng thừa)
     * Ghi chú: Hàm set() phải tự động trim() chuỗi văn bản trước khi lưu để đảm bảo dữ liệu sạch
     */
    public function testLuuVaDocChuoiTuDongXoaKhoangTrang()
    {
        $entry = new DataEntry();

        $entry->set('name', '  John Doe  ');

        $this->assertSame('John Doe', $entry->get('name'));
    }

    /**
     * Test Case ID: TC_S7_26
     * Mô tả: Cập nhật dữ liệu lồng nhau bên trong JSON (dùng dấu chấm phân tách)
     * Kết quả mong đợi: Đọc lại 'settings.theme' trả về 'dark'
     * Hàm được test: DataEntry::set() và get() dùng dot-notation —
     *   Cho phép lưu và đọc trực tiếp vào 1 thuộc tính nằm sâu trong cấu trúc JSON
     *   (như settings trong database). Rất hữu ích khi lưu các cấu hình nhỏ.
     *   Nằm tại: umbrella-corporation/app/core/DataEntry.php (dòng 62-79)
     * Điều kiện tiên quyết: Khởi tạo đối tượng DataEntry (hoặc class con)
     * Dữ liệu đầu vào: Key dưới dạng dot-notation (vd: 'settings.theme')
     * Ghi chú: Dùng dot-notation cho phép sửa một nhánh nhỏ trong chuỗi JSON lớn mà không cần decode toàn bộ
     */
    public function testCapNhatDuLieuLongNhauJSON()
    {
        $entry = new DataEntry();
        $entry->set('settings', json_encode(array('theme' => 'light', 'lang' => 'en')));

        $entry->set('settings.theme', 'dark');

        $this->assertSame('dark', $entry->get('settings.theme'));
    }

    /**
     * Test Case ID: TC_S7_27
     * Mô tả: Đọc trường không tồn tại phải trả về null (trống)
     * Kết quả mong đợi: null
     * Hàm được test: DataEntry::get($field) — (giống TC_S7_25)
     *   Kiểm tra tính an toàn của hàm: khi truy cập trường dữ liệu không có,
     *   hệ thống không bị sập mà trả về an toàn là null.
     *   Nằm tại: umbrella-corporation/app/core/DataEntry.php (dòng 95-108)
     * Điều kiện tiên quyết: Khởi tạo đối tượng DataEntry (hoặc class con)
     * Dữ liệu đầu vào: Gọi get('truong_khong_co')
     * Ghi chú: Khi đọc field chưa tồn tại, hệ thống không được crash mà trả về an toàn là null
     */
    public function testDocTruongKhongTonTai()
    {
        $entry = new DataEntry();

        $this->assertNull($entry->get('truong_khong_co'));
    }

    /**
     * Test Case ID: TC_S7_28
     * Mô tả: Khi tên trường bị bỏ trống, không lưu gì cả
     * Kết quả mong đợi: get('') trả về null
     * Hàm được test: DataEntry::set($field, $value) — (giống TC_S7_25)
     *   Hàm kiểm tra bảo mật: bỏ qua thao tác lưu nếu developer quên
     *   hoặc truyền sai tên cột rỗng.
     *   Nằm tại: umbrella-corporation/app/core/DataEntry.php (dòng 54-55)
     * Điều kiện tiên quyết: Khởi tạo đối tượng DataEntry (hoặc class con)
     * Dữ liệu đầu vào: Key rỗng '', Value = 'gia_tri'
     * Ghi chú: Bảo vệ hệ thống khỏi lỗi dev khi truyền nhầm key rỗng; không lưu gì và trả về null
     */
    public function testBoQuaKhiTenTruongRong()
    {
        $entry = new DataEntry();

        $entry->set('', 'gia_tri');

        $this->assertNull($entry->get(''));
    }

    /**
     * Test Case ID: TC_S7_29
     * Mô tả: Lưu giá trị số nguyên → đọc ra vẫn là số nguyên (không bị đổi kiểu)
     * Kết quả mong đợi: Lưu 42 → đọc ra 42
     * Hàm được test: DataEntry::set() và get() — (giống TC_S7_25)
     *   Đảm bảo kiểu dữ liệu nguyên vẹn. Việc lưu số không làm biến dạng
     *   nó thành chuỗi khi đọc ngược ra.
     *   Nằm tại: umbrella-corporation/app/core/DataEntry.php (dòng 54-85)
     * Điều kiện tiên quyết: Khởi tạo đối tượng DataEntry (hoặc class con)
     * Dữ liệu đầu vào: Key = 'count', Value = 42 (integer)
     * Ghi chú: Đảm bảo cơ chế lưu trữ không vô tình ép kiểu nguyên thành chuỗi "42" khi đọc ra
     */
    public function testLuuSoNguyenDungKieu()
    {
        $entry = new DataEntry();

        $entry->set('count', 42);

        $this->assertSame(42, $entry->get('count'));
    }

    // === CÁC TEST CASE PHÁT HIỆN LỖI (SẼ FAIL) ===

    /**
     * Test Case ID: TC_S7_39
     * Mô tả: Gọi set() với tham số thứ 3 là false → PHP phát ra cảnh báo vì biến chưa khai báo
     * Kết quả mong đợi: Không có cảnh báo, nhưng thực tế có → LỖI
     * Hàm đang test: DataEntry::set() — Lưu dữ liệu hệ thống.
     *   Lỗi cấu trúc: Code gọi 1 biến (`$fields`) mà chưa hề tạo ra biến đó trước.
     *   Điều này làm rác log hệ thống vì chứa đầy cảnh báo "Undefined variable".
     *   Nằm tại: umbrella-corporation/app/core/DataEntry.php (dòng 57-61)
     * Điều kiện tiên quyết: Khởi tạo đối tượng DataEntry (hoặc class con)
     * Dữ liệu đầu vào: set('my.dotted.field', 'value', false)
     * Ghi chú: [BUG] Code gốc gọi biến $fields chưa khởi tạo; test này chỉ ra lỗi PHP Notice tiềm ẩn
     */
    public function testCanhBaoBienChuaKhaiBao()
    {
        $entry = new DataEntry();

        // Bật bắt cảnh báo
        $coCanhBao = false;
        set_error_handler(function ($errno) use (&$coCanhBao) {
            if ($errno === E_NOTICE) {
                $coCanhBao = true;
            }
        });

        $entry->set('my.dotted.field', 'value', false);

        restore_error_handler();

        // Kỳ vọng không có cảnh báo nhưng thực tế có → LỖI
        $this->assertFalse($coCanhBao, 'Lỗi: PHP cảnh báo biến $fields chưa khai báo');
    }

    /**
     * Test Case ID: TC_S7_48
     * Description: Phủ các hàm rỗng của abstract DataEntry
     * Scenario Type: Positive
     * Expected Result: Các hàm trả về chính object đó (hoặc thực thi không lỗi).
     * Related Module: DataEntry
     * Precondition: Khởi tạo DataEntry.
     * Input: Không có.
     * Notes: Nhằm tăng coverage cho các hàm base rỗng.
     */
    public function testDataEntryEmptyMethods()
    {
        $entry = new DataEntry();
        
        $entry->insert();
        $entry->update();
        $entry->delete();
        $entry->remove();
        $entry->extendDefaults();
        
        // save() sẽ gọi update() vì isAvailable = false -> insert()
        $entry->save();

        $entry->markAsAvailable();
        // save() sẽ gọi update() vì isAvailable = true
        $entry->save();

        // Hàm refresh() yêu cầu select(), mà select() trong base cũng trả về $this
        $entry->refresh();

        $this->assertTrue(true, "Các hàm rỗng chạy không bị văng lỗi");
    }
}
