<?php

use PHPUnit\Framework\TestCase;

/**
 * Test quản lý hồ sơ bệnh nhân (Patient Profile) — dựa trên UserModel
 * Trong dự án, bệnh nhân (Patient) sử dụng chung bảng Users (UserModel kế thừa DataEntry).
 * File nguồn: umbrella-corporation/app/models/UserModel.php
 */
class PatientProfileTest extends TestCase
{
    /**
     * Hàm tiện ích: Tạo nhanh 1 đối tượng bệnh nhân giả lập có dữ liệu sẵn
     * để test mà không cần kết nối Database.
     */
    private function taoBenhNhan(array $fields)
    {
        $u = new UserModel(0);
        foreach ($fields as $k => $v) {
            $u->set($k, $v, true);
        }
        $u->markAsAvailable();
        return $u;
    }

    // === extendDefaults: Tự động điền giá trị mặc định cho hồ sơ bệnh nhân ===

    /**
     * Test Case ID: TC_PM_01
     * Mô tả: Khi tạo hồ sơ bệnh nhân mới, hệ thống phải tự điền các trường mặc định
     * Kết quả mong đợi: account_type = "member", email/username/password không rỗng
     * Hàm được test: UserModel::extendDefaults() — Tự động điền giá trị mặc định.
     *   Nằm tại: umbrella-corporation/app/models/UserModel.php (dòng 66-90)
     * Điều kiện tiên quyết: Tạo đối tượng UserModel mới (chưa có bất kỳ dữ liệu nào)
     * Dữ liệu đầu vào: Gọi extendDefaults() trên đối tượng UserModel trống (không truyền tham số)
     * Ghi chú: Hàm này tự động gán 13 trường mặc định cho bệnh nhân mới đăng ký
     */
    public function testTuDongDienGiaTriMacDinh()
    {
        $bn = new UserModel(0);
        $bn->extendDefaults();

        $this->assertSame("member", $bn->get("account_type"));
        $this->assertNotNull($bn->get("email"));
        $this->assertNotNull($bn->get("username"));
        $this->assertNotNull($bn->get("password"));
    }

    /**
     * Test Case ID: TC_PM_02
     * Mô tả: Nếu bệnh nhân đã có email, hàm extendDefaults không được ghi đè email cũ
     * Kết quả mong đợi: Email giữ nguyên "benhnhan@gmail.com"
     * Hàm được test: UserModel::extendDefaults() — Tự động điền giá trị mặc định.
     *   Nằm tại: umbrella-corporation/app/models/UserModel.php (dòng 86-89)
     * Điều kiện tiên quyết: Đối tượng UserModel đã được gán email = "benhnhan@gmail.com"
     * Dữ liệu đầu vào: email = "benhnhan@gmail.com", sau đó gọi extendDefaults()
     * Ghi chú: Đảm bảo hàm chỉ điền vào trường NULL, không xoá dữ liệu đã có sẵn
     */
    public function testKhongGhiDeEmailDaCo()
    {
        $bn = new UserModel(0);
        $bn->set("email", "benhnhan@gmail.com");
        $bn->extendDefaults();

        $this->assertSame("benhnhan@gmail.com", $bn->get("email"));
    }

    // === isAvailable / markAsAvailable: Kiểm tra hồ sơ bệnh nhân đã sẵn sàng chưa ===

    /**
     * Test Case ID: TC_PM_03
     * Mô tả: Hồ sơ bệnh nhân mới tạo chưa được load từ DB → chưa sẵn sàng
     * Kết quả mong đợi: isAvailable() = false
     * Hàm được test: DataEntry::isAvailable() — Kiểm tra bệnh nhân đã có dữ liệu thật chưa.
     *   Nằm tại: umbrella-corporation/app/core/DataEntry.php (dòng 23-26)
     * Điều kiện tiên quyết: Tạo đối tượng UserModel(0) — chưa load từ Database
     * Dữ liệu đầu vào: new UserModel(0) — truyền ID = 0 để bỏ qua truy vấn DB
     * Ghi chú: Trạng thái mặc định phải là "chưa sẵn sàng" để ngăn thao tác trên dữ liệu rỗng
     */
    public function testHoSoMoiTaoChuaSanSang()
    {
        $bn = new UserModel(0);

        $this->assertFalse($bn->isAvailable());
    }

    /**
     * Test Case ID: TC_PM_04
     * Mô tả: Sau khi đánh dấu sẵn sàng, hồ sơ bệnh nhân mới có thể thao tác
     * Kết quả mong đợi: isAvailable() = true
     * Hàm được test: DataEntry::markAsAvailable() — Đánh dấu hồ sơ đã load xong.
     *   Nằm tại: umbrella-corporation/app/core/DataEntry.php (dòng 32-36)
     * Điều kiện tiên quyết: Đối tượng UserModel đã tồn tại nhưng chưa markAsAvailable
     * Dữ liệu đầu vào: Gọi markAsAvailable() trên đối tượng UserModel
     * Ghi chú: Sau khi đánh dấu, các hàm isAdmin, canEdit, isExpired mới hoạt động đúng
     */
    public function testDanhDauSanSang()
    {
        $bn = new UserModel(0);
        $bn->markAsAvailable();

        $this->assertTrue($bn->isAvailable());
    }

    // === Lưu / đọc thông tin cá nhân bệnh nhân ===

    /**
     * Test Case ID: TC_PM_05
     * Mô tả: Lưu tên bệnh nhân có khoảng trắng thừa → đọc ra phải tự xoá
     * Kết quả mong đợi: "Nguyễn Văn A" (không còn khoảng trắng đầu/cuối)
     * Hàm được test: DataEntry::set() và get() — Lưu và đọc thông tin bệnh nhân.
     *   Nằm tại: umbrella-corporation/app/core/DataEntry.php (dòng 54-85)
     * Điều kiện tiên quyết: Đối tượng bệnh nhân đã sẵn sàng (markAsAvailable)
     * Dữ liệu đầu vào: firstname = "  Nguyễn Văn A  " (có 2 dấu cách ở đầu và cuối)
     * Ghi chú: Hàm set() tự động trim() chuỗi để dữ liệu sạch trước khi lưu vào DB
     */
    public function testLuuTenBenhNhanXoaKhoangTrangThua()
    {
        $bn = $this->taoBenhNhan(array());

        $bn->set('firstname', '  Nguyễn Văn A  ');

        $this->assertSame('Nguyễn Văn A', $bn->get('firstname'));
    }

    /**
     * Test Case ID: TC_PM_06
     * Mô tả: Lưu và đọc cài đặt cá nhân (preferences) dạng JSON lồng nhau
     * Kết quả mong đợi: preferences.dateformat = "Y-m-d"
     * Hàm được test: DataEntry::set() với dot-notation — Cập nhật trực tiếp vào JSON.
     *   Nằm tại: umbrella-corporation/app/core/DataEntry.php (dòng 62-79)
     * Điều kiện tiên quyết: Bệnh nhân có preferences chứa dateformat="d/m/Y" và timeformat="12"
     * Dữ liệu đầu vào: Gọi set('preferences.dateformat', 'Y-m-d') để đổi định dạng ngày
     * Ghi chú: Dot-notation cho phép sửa 1 thuộc tính con trong JSON mà không cần parse toàn bộ
     */
    public function testCapNhatCaiDatCaNhanJSON()
    {
        $bn = $this->taoBenhNhan(array(
            'preferences' => json_encode(array('dateformat' => 'd/m/Y', 'timeformat' => '12'))
        ));

        $bn->set('preferences.dateformat', 'Y-m-d');

        $this->assertSame('Y-m-d', $bn->get('preferences.dateformat'));
    }

    // === CÁC TEST CASE PHÁT HIỆN LỖI (SẼ FAIL) ===

    /**
     * Test Case ID: TC_PM_07
     * Mô tả: Password mặc định là uniqid() — lưu dạng TEXT THUẦN, không mã hoá!
     * Kết quả mong đợi: Password phải dài hơn 30 ký tự (đã mã hoá md5/bcrypt)
     * Hàm đang test: UserModel::extendDefaults() — Tạo giá trị mặc định.
     *   Nằm tại: umbrella-corporation/app/models/UserModel.php (dòng 72)
     * Điều kiện tiên quyết: Tạo đối tượng UserModel mới, gọi extendDefaults()
     * Dữ liệu đầu vào: Không truyền password, để hàm tự tạo mật khẩu mặc định bằng uniqid()
     * Ghi chú: LỖI BẢO MẬT — uniqid() chỉ tạo chuỗi 13 ký tự dạng text thuần, hacker đọc DB sẽ thấy ngay mật khẩu
     */
    public function testPasswordMacDinhKhongMaHoa()
    {
        $bn = new UserModel(0);
        $bn->extendDefaults();

        $password = $bn->get('password');

        $this->assertGreaterThan(30, strlen($password),
            'LỖI BẢO MẬT: Password mặc định không được mã hoá, lưu dạng text thuần!');
    }

    /**
     * Test Case ID: TC_PM_08
     * Mô tả: Bệnh nhân mới tạo mặc định bị khoá (is_active=0)
     * Kết quả mong đợi: is_active = "1" (kích hoạt ngay sau đăng ký)
     * Hàm đang test: UserModel::extendDefaults() — Tạo giá trị mặc định.
     *   Nằm tại: umbrella-corporation/app/models/UserModel.php (dòng 79)
     * Điều kiện tiên quyết: Tạo đối tượng UserModel mới, gọi extendDefaults()
     * Dữ liệu đầu vào: Không truyền is_active, để hàm tự gán giá trị mặc định
     * Ghi chú: LỖI NGHIỆP VỤ — Bệnh nhân đăng ký xong không thể đăng nhập vì tài khoản mặc định bị khoá
     */
    public function testBenhNhanMoiMacDinhBiKhoa()
    {
        $bn = new UserModel(0);
        $bn->extendDefaults();

        $this->assertSame("1", $bn->get('is_active'),
            'LỖI: Bệnh nhân mới tạo mặc định bị khoá tài khoản (is_active=0)');
    }

    /**
     * Test Case ID: TC_PM_09
     * Mô tả: Ngày hết hạn mặc định = thời điểm hiện tại → bệnh nhân hết hạn ngay khi tạo!
     * Kết quả mong đợi: isExpired() = false (bệnh nhân mới phải CHƯA hết hạn)
     * Hàm đang test: UserModel::extendDefaults() — Tạo giá trị mặc định.
     *   Nằm tại: umbrella-corporation/app/models/UserModel.php (dòng 80)
     * Điều kiện tiên quyết: Tạo đối tượng UserModel mới, gọi extendDefaults(), markAsAvailable()
     * Dữ liệu đầu vào: Không truyền expire_date, để hàm gán expire_date = date("Y-m-d H:i:s")
     * Ghi chú: LỖI LOGIC — expire_date = NOW nghĩa là bệnh nhân hết hạn ngay lập tức, không thể dùng hệ thống
     */
    public function testNgayHetHanMacDinhLaNgayHienTai()
    {
        $bn = new UserModel(0);
        $bn->extendDefaults();
        $bn->markAsAvailable();

        $this->assertFalse($bn->isExpired(),
            'LỖI: Bệnh nhân vừa tạo đã hết hạn ngay vì expire_date = thời điểm hiện tại');
    }

    /**
     * Test Case ID: TC_PM_10
     * Mô tả: Email mặc định dùng domain "@thepostcode.co" — không phải domain bệnh viện
     * Kết quả mong đợi: Email mặc định KHÔNG chứa "@thepostcode.co"
     * Hàm đang test: UserModel::extendDefaults() — Tạo giá trị mặc định.
     *   Nằm tại: umbrella-corporation/app/models/UserModel.php (dòng 70)
     * Điều kiện tiên quyết: Tạo đối tượng UserModel mới, gọi extendDefaults()
     * Dữ liệu đầu vào: Không truyền email, để hàm gán email = uniqid()."@thepostcode.co"
     * Ghi chú: LỖI THIẾT KẾ — Domain "thepostcode.co" là của framework gốc, không phải domain bệnh viện
     */
    public function testEmailMacDinhDungDomainSai()
    {
        $bn = new UserModel(0);
        $bn->extendDefaults();

        $email = $bn->get('email');

        $this->assertNotContains('@thepostcode.co', $email,
            'LỖI: Email mặc định dùng domain "thepostcode.co" thay vì domain bệnh viện');
    }


    /**
     * Test Case ID: TC_PM_11
     * Mô tả: Kiểm tra các thao tác Database giả lập (insert, update, delete, select, sendEmail)
     */
    public function testDatabaseOperations()
    {
        // 1. Khởi tạo bằng ID (gọi hàm select)
        $bn = new UserModel(1);
        $this->assertNotNull($bn);

        // 2. Thử insert
        $bn2 = new UserModel(0);
        $bn2->insert();
        $this->assertTrue($bn2->isAvailable());

        // 3. Update
        $bn2->update();

        // 4. Set email as verified
        $bn2->setEmailAsVerified();

        // 5. Send Verification Email
        $bn2->sendVerificationEmail(true);

        // 6. Delete
        $bn2->delete();
        $this->assertFalse($bn2->isAvailable());
    }
}

// 5. Send Verification Email (Cần bypass Controller::model cho GeneralData)
if (!class_exists('Controller')) {
    class Controller {
        public static function model() {
            $mock = new stdClass();
            $mock->get = function() { return 'Test Site'; };
            return $mock;
        }
    }
}
if (!class_exists('Email')) {
    class Email {
        public $Subject;
        public function addAddress() {}
        public function sendmail() {}
    }
}
