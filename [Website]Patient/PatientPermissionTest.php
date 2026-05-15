<?php

use PHPUnit\Framework\TestCase;

/**
 * Test phân quyền và trạng thái bệnh nhân
 * File nguồn: umbrella-corporation/app/models/UserModel.php
 */
class PatientPermissionTest extends TestCase
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

    // === isAdmin ===

    /**
     * Test Case ID: TC_PM_12
     * Mô tả: Tài khoản loại "admin" phải được xác nhận là quản trị viên
     * Kết quả mong đợi: isAdmin() = true
     * Hàm được test: UserModel::isAdmin() — Kiểm tra quyền quản trị.
     *   Nằm tại: umbrella-corporation/app/models/UserModel.php (dòng 179-186)
     * Điều kiện tiên quyết: Tạo bệnh nhân với account_type = "admin", đã markAsAvailable
     * Dữ liệu đầu vào: account_type = "admin"
     * Ghi chú: Admin có quyền xem toàn bộ danh sách bệnh nhân và sửa hồ sơ người khác
     */
    public function testAdminDuocXacNhan()
    {
        $bn = $this->taoBenhNhan(array('account_type' => 'admin'));
        $this->assertTrue($bn->isAdmin());
    }



    /**
     * Test Case ID: TC_PM_13
     * Mô tả: Tài khoản bệnh nhân thường (member) KHÔNG phải admin
     * Kết quả mong đợi: isAdmin() = false
     * Hàm được test: UserModel::isAdmin() — Kiểm tra quyền quản trị.
     *   Nằm tại: umbrella-corporation/app/models/UserModel.php (dòng 181)
     * Điều kiện tiên quyết: Tạo bệnh nhân với account_type = "member", đã markAsAvailable
     * Dữ liệu đầu vào: account_type = "member"
     * Ghi chú: Bệnh nhân thường chỉ có quyền xem và sửa hồ sơ của chính mình
     */
    public function testBenhNhanThuongKhongPhaiAdmin()
    {
        $bn = $this->taoBenhNhan(array('account_type' => 'member'));
        $this->assertFalse($bn->isAdmin());
    }

    /**
     * Test Case ID: TC_PM_14
     * Mô tả: Hồ sơ chưa sẵn sàng (chưa load từ DB) thì không thể là admin
     * Kết quả mong đợi: isAdmin() = false
     * Hàm được test: UserModel::isAdmin() — Kiểm tra quyền quản trị.
     *   Nằm tại: umbrella-corporation/app/models/UserModel.php (dòng 181)
     * Điều kiện tiên quyết: Tạo UserModel(0), gán account_type = "admin" nhưng KHÔNG markAsAvailable
     * Dữ liệu đầu vào: account_type = "admin", isAvailable = false
     * Ghi chú: Bảo vệ hệ thống: Dù có account_type admin nhưng dữ liệu chưa xác minh từ DB thì vẫn từ chối
     */
    public function testChuaSanSangKhongTheAdmin()
    {
        $bn = new UserModel(0);
        $bn->set('account_type', 'admin');
        $this->assertFalse($bn->isAdmin());
    }

    // === canEdit ===

    /**
     * Test Case ID: TC_PM_15
     * Mô tả: Developer có quyền sửa hồ sơ bất kỳ bệnh nhân nào
     * Kết quả mong đợi: canEdit() = true
     * Hàm được test: UserModel::canEdit($User) — Kiểm tra quyền chỉnh sửa.
     *   Nằm tại: umbrella-corporation/app/models/UserModel.php (dòng 195-214)
     * Điều kiện tiên quyết: Developer (id=1) và bệnh nhân member (id=2) đều đã sẵn sàng
     * Dữ liệu đầu vào: editor.account_type = "developer", target.account_type = "member"
     * Ghi chú: Developer là kỹ thuật viên hệ thống, cần quyền sửa để hỗ trợ xử lý sự cố
     */
    public function testDeveloperSuaDuocMoiBenhNhan()
    {
        $dev = $this->taoBenhNhan(array('account_type' => 'developer', 'id' => 1));
        $bn = $this->taoBenhNhan(array('account_type' => 'member', 'id' => 2));
        $this->assertTrue($dev->canEdit($bn));
    }





    /**
     * Test Case ID: TC_PM_16
     * Mô tả: Bệnh nhân thường KHÔNG được sửa hồ sơ bệnh nhân khác
     * Kết quả mong đợi: canEdit() = false
     * Hàm được test: UserModel::canEdit($User) — Kiểm tra quyền chỉnh sửa.
     *   Nằm tại: umbrella-corporation/app/models/UserModel.php (dòng 195-214)
     * Điều kiện tiên quyết: Hai bệnh nhân member khác ID (id=1 và id=2)
     * Dữ liệu đầu vào: editor.id = 1, target.id = 2, cả hai đều là "member"
     * Ghi chú: Bảo mật: Bệnh nhân A không thể xem hoặc sửa hồ sơ bệnh nhân B
     */
    public function testBenhNhanKhongSuaDuocNguoiKhac()
    {
        $bn_a = $this->taoBenhNhan(array('account_type' => 'member', 'id' => 1));
        $bn_b = $this->taoBenhNhan(array('account_type' => 'member', 'id' => 2));
        $this->assertFalse($bn_a->canEdit($bn_b));
    }

    /**
     * Test Case ID: TC_PM_17
     * Mô tả: Admin KHÔNG được sửa hồ sơ Developer (cấp cao hơn)
     * Kết quả mong đợi: canEdit() = false
     * Hàm được test: UserModel::canEdit($User) — Kiểm tra quyền chỉnh sửa.
     *   Nằm tại: umbrella-corporation/app/models/UserModel.php (dòng 205)
     * Điều kiện tiên quyết: Admin (id=1) và Developer (id=2) đều đã sẵn sàng
     * Dữ liệu đầu vào: editor.account_type = "admin", target.account_type = "developer"
     * Ghi chú: Phân cấp quyền hạn: Admin < Developer. Admin không thể can thiệp vào tài khoản cấp cao hơn
     */
    public function testAdminKhongSuaDuocDeveloper()
    {
        $admin = $this->taoBenhNhan(array('account_type' => 'admin', 'id' => 1));
        $dev = $this->taoBenhNhan(array('account_type' => 'developer', 'id' => 2));
        $this->assertFalse($admin->canEdit($dev));
    }

    /**
     * Test Case ID: TC_PM_18
     * Mô tả: Người dùng chưa sẵn sàng (chưa đăng nhập) không được sửa bất kỳ ai
     * Kết quả mong đợi: canEdit() = false
     * Hàm được test: UserModel::canEdit($User) — Kiểm tra quyền chỉnh sửa.
     *   Nằm tại: umbrella-corporation/app/models/UserModel.php (dòng 197)
     * Điều kiện tiên quyết: Editor chưa markAsAvailable (mô phỏng chưa đăng nhập), target là member
     * Dữ liệu đầu vào: editor.isAvailable = false, target.account_type = "member"
     * Ghi chú: Bảo mật: Chặn mọi thao tác sửa khi người dùng chưa xác thực danh tính
     */
    public function testChuaDangNhapKhongSuaDuocAi()
    {
        $anonymous = new UserModel(0);
        $bn = $this->taoBenhNhan(array('account_type' => 'member', 'id' => 2));
        $this->assertFalse($anonymous->canEdit($bn));
    }

    // === isExpired ===

    /**
     * Test Case ID: TC_PM_19
     * Mô tả: Tài khoản có ngày hết hạn trong tương lai → chưa hết hạn
     * Kết quả mong đợi: isExpired() = false
     * Hàm được test: UserModel::isExpired() — Kiểm tra tài khoản hết hạn.
     *   Nằm tại: umbrella-corporation/app/models/UserModel.php (dòng 221-232)
     * Điều kiện tiên quyết: Tạo bệnh nhân với expire_date = ngày mai (+1 ngày)
     * Dữ liệu đầu vào: expire_date = date("Y-m-d H:i:s", time() + 86400)
     * Ghi chú: Bệnh nhân còn hạn sử dụng dịch vụ → được phép đặt lịch khám mới
     */
    public function testTaiKhoanChuaHetHan()
    {
        $ngay_tuong_lai = date('Y-m-d H:i:s', time() + 86400);
        $bn = $this->taoBenhNhan(array('expire_date' => $ngay_tuong_lai));
        $this->assertFalse($bn->isExpired());
    }



    /**
     * Test Case ID: TC_PM_20
     * Mô tả: Hồ sơ chưa sẵn sàng thì luôn coi như đã hết hạn
     * Kết quả mong đợi: isExpired() = true
     * Hàm được test: UserModel::isExpired() — Kiểm tra tài khoản hết hạn.
     *   Nằm tại: umbrella-corporation/app/models/UserModel.php (dòng 223)
     * Điều kiện tiên quyết: Tạo UserModel(0) KHÔNG markAsAvailable (chưa load từ DB)
     * Dữ liệu đầu vào: Đối tượng UserModel trống, isAvailable = false
     * Ghi chú: An toàn: Nếu chưa load dữ liệu từ DB thì mặc định coi là hết hạn để ngăn truy cập trái phép
     */
    public function testChuaSanSangCoiNhuHetHan()
    {
        $bn = new UserModel(0);
        $this->assertTrue($bn->isExpired());
    }

    // === isEmailVerified ===

    /**
     * Test Case ID: TC_PM_21
     * Mô tả: Email đã xác thực (không có mã xác thực tồn đọng) → trả true
     * Kết quả mong đợi: isEmailVerified() = true
     * Hàm được test: UserModel::isEmailVerified() — Kiểm tra email xác thực.
     *   Nằm tại: umbrella-corporation/app/models/UserModel.php (dòng 256-267)
     * Điều kiện tiên quyết: Tạo bệnh nhân với trường data = JSON rỗng (không chứa hash)
     * Dữ liệu đầu vào: data = "{}" (JSON rỗng, không có trường email_verification_hash)
     * Ghi chú: Khi không tồn tại hash → nghĩa là bệnh nhân đã bấm link xác nhận email thành công
     */
    public function testEmailDaXacThuc()
    {
        $bn = $this->taoBenhNhan(array('data' => json_encode(array())));
        $this->assertTrue($bn->isEmailVerified());
    }



    /**
     * Test Case ID: TC_PM_22
     * Mô tả: Hồ sơ chưa sẵn sàng thì email luôn coi là chưa xác thực
     * Kết quả mong đợi: isEmailVerified() = false
     * Hàm được test: UserModel::isEmailVerified() — Kiểm tra email xác thực.
     *   Nằm tại: umbrella-corporation/app/models/UserModel.php (dòng 258-260)
     * Điều kiện tiên quyết: Tạo UserModel(0) KHÔNG markAsAvailable
     * Dữ liệu đầu vào: Đối tượng UserModel trống, isAvailable = false
     * Ghi chú: An toàn: Chưa load dữ liệu thì mặc định email chưa xác thực
     */
    public function testChuaSanSangEmailChuaXacThuc()
    {
        $bn = new UserModel(0);
        $this->assertFalse($bn->isEmailVerified());
    }


    /**
     * Test Case ID: TC_PM_23
     * Mô tả: Account_type có khoảng trắng " admin " vẫn bị coi là admin do set() tự trim
     * Kết quả mong đợi: isAdmin() = false (vì " admin " ≠ "admin")
     * Hàm đang test: UserModel::isAdmin() — Kiểm tra quyền quản trị.
     *   Nằm tại: umbrella-corporation/app/models/UserModel.php (dòng 181)
     * Điều kiện tiên quyết: Tạo bệnh nhân với account_type = " admin " (có khoảng trắng đầu/cuối)
     * Dữ liệu đầu vào: account_type = " admin " (chứa dấu cách thừa ở 2 đầu)
     * Ghi chú: LỖI LOGIC — Hàm set() tự trim() biến " admin " thành "admin", làm mất khả năng phát hiện dữ liệu bẩn
     */
    public function testAdminCoKhoangTrangBiTuChoi()
    {
        $bn = $this->taoBenhNhan(array('account_type' => ' admin '));
        $this->assertFalse($bn->isAdmin());
    }

    /**
     * Test Case ID: TC_PM_24
     * Mô tả: Email verification hash là chuỗi rỗng "" → hệ thống coi là đã xác thực SAI!
     * Kết quả mong đợi: isEmailVerified() = false (hash vẫn tồn tại dù rỗng)
     * Hàm đang test: UserModel::isEmailVerified() — Kiểm tra email xác thực.
     *   Nằm tại: umbrella-corporation/app/models/UserModel.php (dòng 262)
     * Điều kiện tiên quyết: Tạo bệnh nhân với email_verification_hash = "" (chuỗi rỗng)
     * Dữ liệu đầu vào: data = JSON chứa email_verification_hash = "" (chuỗi rỗng, không phải null)
     * Ghi chú: LỖI LOGIC — PHP coi "" là falsy nên if("") = false, hàm bỏ qua kiểm tra → trả true SAI
     */
    public function testEmailHashRongVanCoiLaDaXacThuc()
    {
        $bn = $this->taoBenhNhan(array(
            'data' => json_encode(array('email_verification_hash' => ''))
        ));
        $this->assertFalse($bn->isEmailVerified(),
            'LỖI: Hash rỗng "" bị coi là không tồn tại, email được xác thực SAI');
    }

    /**
     * Test Case ID: TC_PM_25
     * Mô tả: Admin A sửa được hồ sơ Admin B → rủi ro leo thang quyền trong bệnh viện
     * Kết quả mong đợi: canEdit() = false (admin không nên sửa admin khác)
     * Hàm đang test: UserModel::canEdit() — Kiểm tra quyền chỉnh sửa.
     *   Nằm tại: umbrella-corporation/app/models/UserModel.php (dòng 205)
     * Điều kiện tiên quyết: Hai Admin khác nhau (id=1 và id=2) đều đã sẵn sàng
     * Dữ liệu đầu vào: editor = Admin(id=1), target = Admin(id=2)
     * Ghi chú: LỖI BẢO MẬT — Admin A có thể khoá tài khoản hoặc thay đổi mật khẩu Admin B
     */
    public function testAdminSuaDuocAdminKhacLaRuiRo()
    {
        $admin_a = $this->taoBenhNhan(array('account_type' => 'admin', 'id' => 1));
        $admin_b = $this->taoBenhNhan(array('account_type' => 'admin', 'id' => 2));
        $this->assertFalse($admin_a->canEdit($admin_b),
            'LỖI BẢO MẬT: Admin A có thể sửa hồ sơ Admin B — rủi ro leo thang quyền');
    }
}
