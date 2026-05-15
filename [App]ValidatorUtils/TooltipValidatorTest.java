package com.example.do_an_tot_nghiep;

import static org.junit.Assert.*;

import com.example.do_an_tot_nghiep.Helper.Tooltip;

import org.junit.Test;

import java.util.Date;
import java.util.concurrent.TimeUnit;

/**
 * Unit tests cho Tooltip utility class — các hàm xử lý date/time.
 * Đây là pure Java static methods, không cần Android Context (trừ getReadableToday/setLocale).
 */
public class TooltipValidatorTest {

    // A_S7_TC09 — getToday() trả về không null
    /** Test Case ID: A_S7_TC09
     * Mô tả: Hàm getToday() phải luôn trả về chuỗi không null.
     * Kết quả mong đợi: result != null
     * Hàm được test: Tooltip.getToday() — Nằm tại: Helper/Tooltip.java (dòng 33-55)
     * Ghi chú: Đây là hàm cốt lõi cung cấp ngày hiện tại cho toàn bộ hệ thống đặt lịch */
    @Test
    public void A_S7_TC09_getToday_returnsNotNull() {
        assertNotNull(Tooltip.getToday());
    }

    // A_S7_TC10 — getToday() đúng format yyyy-MM-dd
    /** Test Case ID: A_S7_TC10
     * Mô tả: Chuỗi trả về phải khớp regex yyyy-MM-dd.
     * Kết quả mong đợi: matches("\\d{4}-\\d{2}-\\d{2}")
     * Hàm được test: Tooltip.getToday() — Nằm tại: Helper/Tooltip.java (dòng 33-55)
     * Ghi chú: Format sai sẽ khiến API server từ chối request đặt lịch */
    @Test
    public void A_S7_TC10_getToday_matchesFormat() {
        assertTrue(Tooltip.getToday().matches("\\d{4}-\\d{2}-\\d{2}"));
    }

    // A_S7_TC11 — getToday() luôn có length = 10
    /** Test Case ID: A_S7_TC11
     * Mô tả: Chuỗi ngày phải luôn dài đúng 10 ký tự (yyyy-MM-dd).
     * Kết quả mong đợi: length == 10
     * Hàm được test: Tooltip.getToday() — Nằm tại: Helper/Tooltip.java (dòng 33-55)
     * Ghi chú: Nếu thiếu padding 0, length sẽ là 9 → gây lỗi substring ở beautifierDatetime */
    @Test
    public void A_S7_TC11_getToday_hasLengthTen() {
        assertEquals(10, Tooltip.getToday().length());
    }

    // A_S7_TC12 — getToday() phần ngày luôn là 2 chữ số
    /** Test Case ID: A_S7_TC12
     * Mô tả: Phần ngày (substring 8-10) phải là 2 chữ số, kể cả khi ngày < 10.
     * Kết quả mong đợi: dayPart matches "\\d{2}"
     * Hàm được test: Tooltip.getToday() — Nằm tại: Helper/Tooltip.java (dòng 43-46)
     * Ghi chú: Kiểm tra logic padding "0" + dateValue khi date < 10 */
    @Test
    public void A_S7_TC12_getToday_dayPartIsTwoDigits() {
        String dayPart = Tooltip.getToday().substring(8, 10);
        assertTrue(dayPart.matches("\\d{2}"));
    }

    // A_S7_TC13 — getToday() phần tháng luôn là 2 chữ số
    /** Test Case ID: A_S7_TC13
     * Mô tả: Phần tháng (substring 5-7) phải là 2 chữ số, kể cả khi tháng < 10.
     * Kết quả mong đợi: monthPart matches "\\d{2}"
     * Hàm được test: Tooltip.getToday() — Nằm tại: Helper/Tooltip.java (dòng 48-51)
     * Ghi chú: Kiểm tra logic padding "0" + monthValue khi month < 10 */
    @Test
    public void A_S7_TC13_getToday_monthPartIsTwoDigits() {
        String monthPart = Tooltip.getToday().substring(5, 7);
        assertTrue(monthPart.matches("\\d{2}"));
    }

    // A_S7_TC14 — getDateDifference: 1 ngày
    /** Test Case ID: A_S7_TC14
     * Mô tả: date2 sau date1 đúng 1 ngày → kết quả = 1 (DAYS).
     * Kết quả mong đợi: diff == 1
     * Hàm được test: Tooltip.getDateDifference() — Nằm tại: Helper/Tooltip.java (dòng 166-169)
     * Ghi chú: Dùng để tính khoảng cách ngày hẹn với hôm nay trong BookingpageInfoActivity */
    @Test
    public void A_S7_TC14_getDateDifference_oneDayApart() {
        Date d1 = new Date(0);
        Date d2 = new Date(86400000L); // +1 day
        assertEquals(1, Tooltip.getDateDifference(d1, d2, TimeUnit.DAYS));
    }

    // A_S7_TC15 — getDateDifference: cùng ngày → 0
    /** Test Case ID: A_S7_TC15
     * Mô tả: Hai ngày giống nhau → kết quả = 0.
     * Kết quả mong đợi: diff == 0
     * Hàm được test: Tooltip.getDateDifference() — Nằm tại: Helper/Tooltip.java (dòng 166-169)
     * Ghi chú: Khi diff == 0, booking vẫn được phép cancel (ngay hôm nay) */
    @Test
    public void A_S7_TC15_getDateDifference_sameDate_returnsZero() {
        Date d = new Date();
        assertEquals(0, Tooltip.getDateDifference(d, d, TimeUnit.DAYS));
    }

    // A_S7_TC16 — getDateDifference: ngày quá khứ → giá trị âm
    /** Test Case ID: A_S7_TC16
     * Mô tả: date2 trước date1 → kết quả âm (lịch đã qua).
     * Kết quả mong đợi: diff < 0
     * Hàm được test: Tooltip.getDateDifference() — Nằm tại: Helper/Tooltip.java (dòng 166-169)
     * Ghi chú: Nếu diff < 0, nút Cancel booking sẽ bị ẩn */
    @Test
    public void A_S7_TC16_getDateDifference_pastDate_returnsNegative() {
        Date d1 = new Date(86400000L);
        Date d2 = new Date(0);
        assertTrue(Tooltip.getDateDifference(d1, d2, TimeUnit.DAYS) < 0);
    }

    // A_S7_TC17 — getDateDifference: đơn vị HOURS
    /** Test Case ID: A_S7_TC17
     * Mô tả: Chênh lệch 1 ngày tính bằng giờ → 24.
     * Kết quả mong đợi: diff == 24
     * Hàm được test: Tooltip.getDateDifference() — Nằm tại: Helper/Tooltip.java (dòng 166-169)
     * Ghi chú: Kiểm tra hàm convert đúng đơn vị TimeUnit */
    @Test
    public void A_S7_TC17_getDateDifference_inHours() {
        Date d1 = new Date(0);
        Date d2 = new Date(86400000L);
        assertEquals(24, Tooltip.getDateDifference(d1, d2, TimeUnit.HOURS));
    }
}
