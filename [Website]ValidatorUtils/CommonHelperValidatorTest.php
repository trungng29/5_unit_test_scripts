<?php

use PHPUnit\Framework\TestCase;

/**
 * Test các hàm tiện ích trong file helpers/common.helper.php
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh độc lập)
     * Dữ liệu đầu vào: Giá trị thô cần xử lý hoặc định dạng
     * Ghi chú: Đây là các hàm tiện ích tái sử dụng nhiều lần trong toàn dự án
 */
class CommonHelperValidatorTest extends TestCase
{
    // === htmlchars: Chuyển ký tự đặc biệt HTML thành dạng an toàn ===

    /**
     * Test Case ID: TC_S7_01
     * Mô tả: Kiểm tra htmlchars có chặn được mã độc XSS không
     * Kết quả mong đợi: Các ký tự nguy hiểm như <, >, & bị chuyển thành dạng an toàn
     * Hàm được test: htmlchars($string) — Chuyển các ký tự HTML nguy hiểm (<, >, &, ")
     *   thành dạng an toàn để hiển thị trên web, chống tấn công XSS.
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 7-10)
     * Ghi chú: Xác nhận hàm chuyển đúng <, >, &, " thành &lt;, &gt;, &amp;, &quot; — bảo vệ trang khỏi tấn công XSS
     */
    public function testHtmlcharsChongTanCongXSS()
    {
        // Chuẩn bị: chuỗi chứa mã độc XSS
        $input = '<script>alert("XSS & attack")</script>';

        // Thực thi
        $result = htmlchars($input);

        // Kiểm tra: phải chuyển hết ký tự nguy hiểm
        $this->assertNotContains('<script>', $result);
        $this->assertContains('&lt;', $result);
        $this->assertContains('&amp;', $result);
    }

    // === truncate_string: Cắt ngắn chuỗi khi quá dài ===

    /**
     * Test Case ID: TC_S7_02
     * Mô tả: Chuỗi dài hơn giới hạn phải bị cắt và thêm "..." ở cuối
     * Kết quả mong đợi: Chuỗi kết quả <= 10 ký tự và có "..." ở cuối
     * Hàm được test: truncate_string($string, $max_length, $ellipsis) — Cắt ngắn
     *   chuỗi văn bản khi vượt quá độ dài cho phép và thêm dấu "..." ở cuối.
     *   Dùng để hiển thị tiêu đề bài viết, mô tả ngắn trên giao diện.
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 22-53)
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh độc lập)
     * Dữ liệu đầu vào: Chuỗi dài hơn max_length, dấu ellipsis "..."
     * Ghi chú: Kết quả phải có đúng 3 ký tự "..." ở cuối và tổng độ dài không vượt giới hạn
     */
    public function testCatChuoiDaiVaThemBaCham()
    {
        $input = "This is a very long string that should be truncated";

        $result = truncate_string($input, 10, "...");

        $this->assertLessThanOrEqual(10, mb_strlen($result));
        $this->assertSame("...", mb_substr($result, -3));
    }

    /**
     * Test Case ID: TC_S7_03
     * Mô tả: Chuỗi ngắn hơn giới hạn thì giữ nguyên, không cắt
     * Kết quả mong đợi: Chuỗi "Short" giữ nguyên
     * Hàm được test: truncate_string($string, $max_length) — (giống TC_S7_02)
     *   Kiểm tra trường hợp chuỗi đã đủ ngắn thì không cần cắt.
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 22-53)
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh độc lập)
     * Dữ liệu đầu vào: Chuỗi "Short" (5 ký tự), max_length = 50
     * Ghi chú: Chuỗi ngắn hơn giới hạn phải giữ nguyên 100%, hàm không được thêm hay bớt ký tự nào
     */
    public function testGiuNguyenChuoiNgan()
    {
        $result = truncate_string("Short", 50);

        $this->assertSame("Short", $result);
    }

    // === url_slug: Tạo đường dẫn thân thiện từ tiêu đề ===

    /**
     * Test Case ID: TC_S7_04
     * Mô tả: Chuyển tiêu đề thành đường dẫn: chữ thường, dấu cách thành gạch ngang
     * Kết quả mong đợi: "Hello World Test" → "hello-world-test"
     * Hàm được test: url_slug($string) — Chuyển tiêu đề bài viết thành đường dẫn URL
     *   thân thiện (SEO-friendly). Ví dụ: "Bài viết mới" → "bai-viet-moi".
     *   Dùng khi tạo bài viết, trang mới trên hệ thống.
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 62-92)
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh độc lập)
     * Dữ liệu đầu vào: "Hello World Test" (có 2 dấu cách)
     * Ghi chú: Chữ hoa → thường, dấu cách → gạch ngang; kết quả phải dùng được trực tiếp làm URL path
     */
    public function testTaoDuongDanThanThien()
    {
        $result = url_slug("Hello World Test");

        $this->assertSame("hello-world-test", $result);
    }

    /**
     * Test Case ID: TC_S7_05
     * Mô tả: Ký tự đặc biệt (@, #, $, !) phải bị loại bỏ khỏi đường dẫn
     * Kết quả mong đợi: Chỉ còn chữ cái, số và dấu gạch ngang
     * Hàm được test: url_slug($string) — (giống TC_S7_04)
     *   Kiểm tra hàm có loại bỏ hết ký tự không hợp lệ trong URL không.
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 62-92)
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh độc lập)
     * Dữ liệu đầu vào: "Hello @World! #Test$" (chứa @, #, $, !)
     * Ghi chú: URL chỉ được chứa [a-z0-9-]; mọi ký tự đặc biệt phải bị loại bỏ hoàn toàn
     */
    public function testLoaiBoDauDacBiet()
    {
        $result = url_slug("Hello @World! #Test$");

        $this->assertRegExp('/^[a-z0-9\-]+$/', $result);
    }

    /**
     * Test Case ID: TC_S7_06
     * Mô tả: Nhiều dấu gạch ngang liên tiếp phải gộp lại thành một
     * Kết quả mong đợi: Không có "--" trong kết quả
     * Hàm được test: url_slug($string) — (giống TC_S7_04)
     *   Kiểm tra hàm có gộp nhiều dấu "-" liên tiếp thành 1 không.
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 62-92)
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh độc lập)
     * Dữ liệu đầu vào: "Hello   ---   World" (3 dấu cách + 3 gạch ngang)
     * Ghi chú: Nhiều dấu "-" liên tiếp phải gộp thành 1 để URL không bị dư dấu gạch
     */
    public function testGopDauGachNgangThua()
    {
        $result = url_slug("Hello   ---   World");

        $this->assertNotContains("--", $result);
    }

    // === format_price: Hiển thị giá tiền đẹp ===

    /**
     * Test Case ID: TC_S7_07
     * Mô tả: Giá tiền phải hiển thị phần thập phân bằng thẻ HTML <sup>
     * Kết quả mong đợi: Kết quả chứa thẻ <sup> để phần thập phân nhỏ hơn
     * Hàm được test: format_price($price) — Định dạng giá tiền để hiển thị trên web.
     *   Phần thập phân (xu) được bọc trong thẻ <sup> cho nhỏ hơn, dễ đọc.
     *   Dùng tại trang thanh toán, bảng giá dịch vụ.
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 125-139)
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh độc lập)
     * Dữ liệu đầu vào: 29.5 (số thực có phần thập phân)
     * Ghi chú: Hàm bọc phần xu trong <sup> để hiển thị nhỏ hơn trên UI; phần "50" phải xuất hiện trong kết quả
     */
    public function testHienThiGiaTienCoThapPhan()
    {
        $result = format_price(29.5);

        $this->assertContains("<sup>", $result);
        $this->assertContains("50", $result);
    }

    /**
     * Test Case ID: TC_S7_08
     * Mô tả: Tiền tệ không có thập phân (như JPY) thì làm tròn thành số nguyên
     * Kết quả mong đợi: 1234.56 → 1235
     * Hàm được test: format_price($price, $is_zero_decimal) — (giống TC_S7_07)
     *   Khi tiền tệ không có xu lẻ (VND, JPY), hàm làm tròn thành số nguyên.
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 125-139)
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh độc lập)
     * Dữ liệu đầu vào: 1234.56 và cờ $is_zero_decimal = true
     * Ghi chú: Khi cờ zero_decimal = true, hàm phải làm tròn lên 1235 (không giữ phần thập phân)
     */
    public function testLamTronChoTienTeKhongThapPhan()
    {
        $result = format_price(1234.56, true);

        $this->assertSame(1235.0, (float)$result);
    }

    /**
     * Test Case ID: TC_S7_09
     * Mô tả: Nếu truyền vào không phải số thì trả nguyên giá trị gốc
     * Kết quả mong đợi: "not-a-number" trả nguyên "not-a-number"
     * Hàm được test: format_price($price) — (giống TC_S7_07)
     *   Kiểm tra hàm xử lý đúng khi nhận giá trị không phải số.
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 125-139)
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh độc lập)
     * Dữ liệu đầu vào: Chuỗi "not-a-number" (không phải số)
     * Ghi chú: Hàm phải trả nguyên giá trị khi đầu vào không phải số, không được crash hay trả chuỗi rỗng
     */
    public function testTraNguyenKhiKhongPhaiSo()
    {
        $result = format_price("not-a-number");

        $this->assertSame("not-a-number", $result);
    }

    // === isValidDate: Kiểm tra ngày tháng có hợp lệ không ===

    /**
     * Test Case ID: TC_S7_10
     * Mô tả: Ngày giờ đúng định dạng "Năm-Tháng-Ngày Giờ:Phút:Giây" phải được chấp nhận
     * Kết quả mong đợi: Trả về true
     * Hàm được test: isValidDate($date, $format) — Kiểm tra chuỗi ngày tháng có đúng
     *   định dạng và hợp lệ không. Dùng khi xử lý ngày hết hạn tài khoản,
     *   ngày đặt lịch, ngày đăng bài viết.
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 187-191)
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh độc lập)
     * Dữ liệu đầu vào: "2026-05-10 14:30:00" (đúng định dạng Y-m-d H:i:s)
     * Ghi chú: Ngày và giờ đều hợp lệ, hàm phải trả true không điều kiện
     */
    public function testNgayDungDinhDang()
    {
        $result = isValidDate("2026-05-10 14:30:00");

        $this->assertTrue($result);
    }

    /**
     * Test Case ID: TC_S7_11
     * Mô tả: Ngày tháng vô nghĩa (tháng 13, ngày 40) phải bị từ chối
     * Kết quả mong đợi: Trả về false
     * Hàm được test: isValidDate($date, $format) — (giống TC_S7_10)
     *   Kiểm tra hàm có từ chối đúng các ngày không tồn tại.
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 187-191)
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh độc lập)
     * Dữ liệu đầu vào: "2026-13-40 25:70:99" (tháng 13, ngày 40 — không tồn tại)
     * Ghi chú: Hàm phải từ chối mọi ngày không tồn tại trong lịch thực, trả false
     */
    public function testTuChoiNgaySai()
    {
        $result = isValidDate("2026-13-40 25:70:99");

        $this->assertFalse($result);
    }

    // === textInitials: Lấy chữ cái viết tắt (VD: "Nguyễn Văn An" → "N") ===

    /**
     * Test Case ID: TC_S7_12
     * Mô tả: Lấy chữ cái đầu tiên của chuỗi
     * Kết quả mong đợi: "Hello World" → "H"
     * Hàm được test: textInitials($text, $length) — Lấy chữ cái viết tắt từ tên người dùng.
     *   Dùng để hiển thị avatar chữ cái khi người dùng chưa có ảnh đại diện.
     *   Ví dụ: "Nguyễn Văn An" với length=2 → "NA".
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 223-255)
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh độc lập)
     * Dữ liệu đầu vào: text = "Hello World", length = 1
     * Ghi chú: Với length=1 chỉ lấy chữ đầu từ đầu tiên; dùng làm avatar chữ cái trên UI
     */
    public function testLayChuVietTat()
    {
        $result = textInitials("Hello World", 1);

        $this->assertSame("H", $result);
    }

    // === readableFileSize: Đổi byte sang đơn vị dễ đọc ===

    /**
     * Test Case ID: TC_S7_13
     * Mô tả: 1024 bytes phải được chuyển thành "1kB"
     * Kết quả mong đợi: Kết quả chứa "kB"
     * Hàm được test: readableFileSize($size) — Chuyển kích thước file từ byte sang
     *   đơn vị dễ đọc (kB, MB, GB). Dùng khi hiển thị dung lượng file tải lên,
     *   dung lượng ổ đĩa trong trang quản trị.
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 289-310)
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh độc lập)
     * Dữ liệu đầu vào: 1024 (bytes, đúng 1 kB)
     * Ghi chú: Kết quả phải chứa "kB"; dùng hiển thị dung lượng file tải lên trong trang quản trị
     */
    public function testDoiByteSangKB()
    {
        $result = readableFileSize(1024);

        $this->assertContains("kB", $result);
    }

    /**
     * Test Case ID: TC_S7_14
     * Mô tả: 1048576 bytes (1024*1024) phải chuyển thành "1MB"
     * Kết quả mong đợi: Kết quả chứa "MB"
     * Hàm được test: readableFileSize($size) — (giống TC_S7_13)
     *   Kiểm tra với dung lượng lớn hơn (cấp MB).
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 289-310)
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh độc lập)
     * Dữ liệu đầu vào: 1048576 (bytes = 1024 × 1024, đúng 1 MB)
     * Ghi chú: Kết quả phải chứa "MB"; kiểm tra hàm tự chọn đúng đơn vị khi vượt ngưỡng kB
     */
    public function testDoiByteSangMB()
    {
        $result = readableFileSize(1048576);

        $this->assertContains("MB", $result);
    }

    // === readableNumber: Đổi số lớn sang dạng K, M, B ===

    /**
     * Test Case ID: TC_S7_15
     * Mô tả: 1500 phải hiển thị thành "1.5 K" (1.5 nghìn)
     * Kết quả mong đợi: "1.5 K"
     * Hàm được test: readableNumber($number) — Chuyển số lớn thành dạng viết tắt
     *   dễ đọc. Ví dụ: 1500 → "1.5 K", 2500000 → "2.5 M".
     *   Dùng để hiển thị số lượt xem, số người dùng trên dashboard.
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 318-329)
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh độc lập)
     * Dữ liệu đầu vào: 1500 (= 1.5 nghìn)
     * Ghi chú: Kết quả phải là chuỗi "1.5 K" chính xác; dùng hiển thị lượt xem, follower trên dashboard
     */
    public function testDoiSoSangDangNgan()
    {
        $result = readableNumber(1500);

        $this->assertSame("1.5 K", $result);
    }

    // === isZeroDecimalCurrency: Kiểm tra tiền tệ không có xu lẻ ===

    /**
     * Test Case ID: TC_S7_16
     * Mô tả: VND (Việt Nam Đồng) không có xu lẻ → trả true
     * Kết quả mong đợi: true
     * Hàm được test: isZeroDecimalCurrency($currency) — Kiểm tra loại tiền tệ có phần
     *   xu lẻ (thập phân) hay không. VND, JPY không có xu → trả true; USD, EUR có → false.
     *   Dùng khi xử lý thanh toán (Stripe, PayPal) để tính đúng số tiền.
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 423-437)
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh độc lập)
     * Dữ liệu đầu vào: "VND" (Việt Nam Đồng)
     * Ghi chú: VND thuộc danh sách tiền tệ không có xu lẻ; kết quả true đảm bảo tính tiền không nhân 100
     */
    public function testVNDKhongCoXuLe()
    {
        $this->assertTrue(isZeroDecimalCurrency("VND"));
    }

    /**
     * Test Case ID: TC_S7_17
     * Mô tả: USD (Đô la Mỹ) có xu lẻ (cent) → trả false
     * Kết quả mong đợi: false
     * Hàm được test: isZeroDecimalCurrency($currency) — (giống TC_S7_16)
     *   Kiểm tra USD có xu lẻ nên trả false.
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 423-437)
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh độc lập)
     * Dữ liệu đầu vào: "USD" (Đô la Mỹ)
     * Ghi chú: USD có cent (xu lẻ); kết quả false đảm bảo cổng thanh toán nhân số tiền với 100 trước khi gửi
     */
    public function testUSDCoXuLe()
    {
        $this->assertFalse(isZeroDecimalCurrency("USD"));
    }

    // === CÁC TEST CASE PHÁT HIỆN LỖI (SẼ FAIL) ===

    /**
     * Test Case ID: TC_S7_35
     * Mô tả: Khi tên có 2 khoảng trắng liên tiếp "Hello  World", lấy viết tắt 2 chữ
     *         phải ra "HW" nhưng bị sai thành "H" vì phần tử rỗng không bị xoá
     * Kết quả mong đợi: "HW" (nhưng thực tế ra "H" → LỖI)
     * Hàm đang test: textInitials($text, $length) — Lấy chữ viết tắt từ tên.
     *   Chức năng này bị lỗi nếu người dùng lỡ nhập dư 2 dấu cách liên tiếp trong tên.
     *   Lý do: Lỗi dùng sai lệnh unset() với biến tham chiếu (reference).
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 233-236)
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh độc lập)
     * Dữ liệu đầu vào: Tên người dùng và số lượng ký tự cần lấy
     * Ghi chú: Đây là các hàm tiện ích tái sử dụng nhiều lần trong toàn dự án
     */
    public function testLayVietTatSaiKhiCoKhoangTrangLienTiep()
    {
        $result = textInitials("Hello  World", 2);

        // Kỳ vọng "HW" nhưng thực tế ra "H" vì mảng vẫn chứa phần tử rỗng ""
        $this->assertSame("HW", $result);
    }

    /**
     * Test Case ID: TC_S7_36
     * Mô tả: Số 1.5 nghìn tỷ phải hiển thị đơn vị T (Trillion) nhưng danh sách thiếu
     * Kết quả mong đợi: Kết quả phải có chữ cái đơn vị (K/M/B/T) nhưng thực tế thiếu
     * Hàm đang test: readableNumber($numbers) — Viết tắt số lớn.
     *   Chức năng này bị lỗi "tràn mảng" khi con số vượt quá hàng nghìn tỷ.
     *   Hệ thống sẽ bị sập (Fatal Error) nếu chạy trên bản PHP mới.
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 320)
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh độc lập)
     * Dữ liệu đầu vào: Giá trị thô cần xử lý hoặc định dạng
     * Ghi chú: Đây là các hàm tiện ích tái sử dụng nhiều lần trong toàn dự án
     */
    public function testLoiKhiSoQuaLon()
    {
        $result = @readableNumber(1500000000000);

        // Kỳ vọng có đơn vị "T" nhưng mảng chỉ có đến "B" → ra "1.5 " (thiếu đơn vị)
        $this->assertRegExp('/[A-Z]/', $result);
    }

    /**
     * Test Case ID: TC_S7_37
     * Mô tả: Truyền số nguyên vào hàm xử lý chuỗi HTML — hàm chấp nhận nhưng không an toàn
     * Kết quả mong đợi: Pass (hàm vẫn chạy) nhưng chứng minh thiếu kiểm tra đầu vào
     * Hàm đang test: htmlchars($string) — Tránh mã độc HTML.
     *   Lỗi: Hàm này không thèm kiểm tra xem người ta truyền vào cái gì (là chữ, hay mảng, hay số).
     *   Nếu ai đó truyền vào 1 mảng dữ liệu, trang web sẽ lỗi sập nguồn ngay lập tức.
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 7-10)
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh độc lập)
     * Dữ liệu đầu vào: Giá trị thô cần xử lý hoặc định dạng
     * Ghi chú: Đây là các hàm tiện ích tái sử dụng nhiều lần trong toàn dự án
     */
    public function testChapNhanSoNhungThieuKiemTra()
    {
        // Truyền số → chạy được nhưng không đúng mục đích
        $result = htmlchars(12345);
        $this->assertTrue(is_string($result));
        $this->assertSame("12345", $result);

        // Truyền null → chạy được nhưng PHP 8.1+ sẽ cảnh báo
        $resultNull = htmlchars(null);
        $this->assertSame("", $resultNull);
    }

    /**
     * Test Case ID: TC_S7_38
     * Mô tả: Cắt "Hello World" với giới hạn 3 ký tự nhưng dấu ba chấm "[...]" dài 5 ký tự
     *         → kết quả ra "[.." (dấu ba chấm bị cắt), mất hoàn toàn chữ "Hello"
     * Kết quả mong đợi: Phải chứa "H" nhưng thực tế chỉ có "[.." → LỖI
     * Hàm đang test: truncate_string() — Cắt chữ.
     *   Lỗi logic: Hàm cắt mù quáng. Nếu thiết lập cái "dấu ba chấm" còn dài hơn
     *   cả độ dài tối đa cho phép, nó sẽ ăn hết chữ gốc luôn.
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 44-46)
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh độc lập)
     * Dữ liệu đầu vào: Giá trị thô cần xử lý hoặc định dạng
     * Ghi chú: Đây là các hàm tiện ích tái sử dụng nhiều lần trong toàn dự án
     */
    public function testMatNoiDungKhiDauBaChamQuaDai()
    {
        $result = truncate_string("Hello World", 3, "[...]");

        // Kỳ vọng kết quả có chữ "H" nhưng thực tế ra "[.." (3 ký tự đầu của "[...]")
        $this->assertContains("H", $result);
    }

    /**
     * Test Case ID: TC_S7_41
     * Mô tả: Truyền chuỗi chữ "not_a_number" vào hàm đổi kích thước file
     *         → PHP cảnh báo lỗi và trả "0B" thay vì báo đầu vào sai
     * Kết quả mong đợi: Trả false (báo lỗi) nhưng thực tế trả "0B" → LỖI
     * Hàm đang test: readableFileSize() — Đổi byte thành kB, MB.
     *   Lỗi: Giống htmlchars, hàm này nhận đại mọi thứ. Đưa chữ cái vào để tính toán
     *   kích thước file thì hệ thống sẽ lôi chữ ra thực hiện phép chia → sập nguồn.
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 289)
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh độc lập)
     * Dữ liệu đầu vào: Giá trị thô cần xử lý hoặc định dạng
     * Ghi chú: Đây là các hàm tiện ích tái sử dụng nhiều lần trong toàn dự án
     */
    public function testKhongKiemTraDauVao()
    {
        $coCanhBao = false;
        set_error_handler(function ($errno) use (&$coCanhBao) {
            $coCanhBao = true;
        });

        $result = readableFileSize("not_a_number");

        restore_error_handler();

        // PHP đã cảnh báo → chứng tỏ thiếu kiểm tra đầu vào
        $this->assertTrue($coCanhBao, 'LỖI: PHP cảnh báo vì thiếu kiểm tra đầu vào');
        // Hàm nên trả false nhưng thực tế trả "0B"
        $this->assertFalse($result, 'LỖI: Nên trả false cho đầu vào không phải số');
    }

    /**
     * Test Case ID: TC_S7_42
     * Mô tả: format_price(29.50) kỳ vọng "29.50" (số thuần)
     *         nhưng thực tế ra "29.<sup>50</sup>" (có HTML) → không linh hoạt
     * Kết quả mong đợi: "29.50" nhưng thực tế "29.<sup>50</sup>" → LỖI
     * Hàm đang test: format_price() — Hiển thị tiền tệ.
     *   Lỗi thiết kế: Hàm ép chết việc in ra mã HTML `<sup>`. Điều này khiến API (App di động)
     *   hoặc khi muốn ghi lịch sử giao dịch vào File Excel cũng bị dính cái chữ HTML `<sup>` này.
     *   Nằm tại: umbrella-corporation/app/helpers/common.helper.php (dòng 136-137)
     * Điều kiện tiên quyết: Không cần điều kiện đặc biệt (hàm tĩnh độc lập)
     * Dữ liệu đầu vào: Truyền số tiền và cờ làm tròn (true/false)
     * Ghi chú: Đây là các hàm tiện ích tái sử dụng nhiều lần trong toàn dự án
     */
    public function testLuonChenHTML()
    {
        $result = format_price(29.50);

        // Kỳ vọng số thuần nhưng hàm luôn chèn thẻ HTML
        $this->assertSame("29.50", $result);
    }

    /**
     * Test Case ID: TC_S7_43
     * Description: Kiểm tra hàm textInitials với độ dài yêu cầu lớn hơn độ dài của chuỗi
     * Scenario Type: Positive
     * Expected Result: Trả về nguyên chuỗi "A" do độ dài chuỗi nhỏ hơn length yêu cầu.
     * Related Module: CommonHelper
     * Precondition: Hàm textInitials nhận đầu vào là một chuỗi chữ cái.
     * Input: text="A", length=2
     * Notes: Hàm bỏ qua việc sinh initial nếu độ dài yêu cầu quá lớn.
     */
    public function testTextInitialsShortString()
    {
        $result = textInitials("A", 2);
        $this->assertEquals("A", $result);
    }

    /**
     * Test Case ID: TC_S7_44
     * Description: Kiểm tra hàm textInitials để lấy 2 chữ cái đầu của 2 từ
     * Scenario Type: Positive
     * Expected Result: Trả về "JD"
     * Related Module: CommonHelper
     * Precondition: Có một chuỗi gồm 2 từ.
     * Input: text="John Doe", length=2
     * Notes: Hoạt động đúng logic cắt chữ.
     */
    public function testTextInitialsNormal()
    {
        $result = textInitials("John Doe", 2);
        $this->assertEquals("JD", $result);
    }

    /**
     * Test Case ID: TC_S7_45
     * Description: Phát hiện Bug trong readableRandomString khi truyền độ dài là số lẻ
     * Scenario Type: Negative
     * Expected Result: Chuỗi trả về có độ dài là 5.
     * Related Module: CommonHelper
     * Precondition: Hàm readableRandomString sử dụng logic $length/2 để lặp sinh chuỗi.
     * Input: length=5
     * Notes: [BUG] Hàm thực tế trả về chuỗi có độ dài 4 do lỗi làm tròn xuống trong vòng lặp for ($i=1; $i<=2.5). Test case này sẽ FAIL để chỉ ra lỗi code.
     */
    public function testReadableRandomStringOddLengthBug()
    {
        $result = readableRandomString(5);
        $this->assertEquals(5, strlen($result), "BUG: Hàm không trả về đúng độ dài 5 ký tự khi truyền số lẻ.");
    }

    /**
     * Test Case ID: TC_S7_46
     * Description: Phát hiện Bug trong readableRandomString khi truyền số chẵn
     * Scenario Type: Positive
     * Expected Result: Chuỗi trả về có độ dài đúng bằng 6.
     * Related Module: CommonHelper
     * Precondition: Gọi hàm tạo chuỗi ngẫu nhiên.
     * Input: length=6
     * Notes: Hàm hoạt động đúng với số chẵn.
     */
    public function testReadableRandomStringEvenLength()
    {
        $result = readableRandomString(6);
        $this->assertEquals(6, strlen($result));
    }

    /**
     * Test Case ID: TC_S7_47
     * Description: Kiểm tra hàm readableFileSize với dung lượng chuẩn
     * Scenario Type: Positive
     * Expected Result: Trả về "1MB"
     * Related Module: CommonHelper
     * Precondition: Có kích thước file theo byte.
     * Input: size=1048576 (1MB)
     * Notes: Kiểm tra logic làm tròn và đổi đơn vị.
     */
    public function testReadableFileSizeNormal()
    {
        $result = readableFileSize(1048576, 0); // 1024 * 1024
        $this->assertEquals("1MB", $result);
    }
}
