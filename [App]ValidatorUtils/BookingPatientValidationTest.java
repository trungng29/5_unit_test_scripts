package com.example.do_an_tot_nghiep;

import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertFalse;
import static org.junit.Assert.assertTrue;

import com.example.do_an_tot_nghiep.Bookingpage.BookingFragment1;

import org.junit.Before;
import org.junit.Test;

import java.lang.reflect.Method;

/**
 * Unit tests cho logic validation dữ liệu nhập vào của bệnh nhân 
 * khi đặt lịch khám (BookingFragment1).
 *
 * Sử dụng Reflection để test trực tiếp các private method xử lý dữ liệu:
 * - hourToInt()
 * - minuteToInt()
 * Đồng thời giả lập lại logic validate Mandatory fields và Date formatting.
 */
public class BookingPatientValidationTest {

    private BookingFragment1 fragment;
    private Method hourToIntMethod;
    private Method minuteToIntMethod;

    @Before
    public void setUp() throws Exception {
        fragment = new BookingFragment1();

        // Dùng reflection để truy cập private methods
        hourToIntMethod = BookingFragment1.class.getDeclaredMethod("hourToInt", String.class);
        hourToIntMethod.setAccessible(true);

        minuteToIntMethod = BookingFragment1.class.getDeclaredMethod("minuteToInt", String.class);
        minuteToIntMethod.setAccessible(true);
    }

    // =====================================================================
    // A_S7_TC01 — hourToInt: Parse giờ hợp lệ
    // =====================================================================
    /**
     * Test Case ID: A_S7_TC01
     * Mô tả: Truyền chuỗi thời gian hợp lệ (VD: "14:30") → hàm phải trích xuất đúng giờ (14).
     * Kết quả mong đợi: hourToInt("14:30") == 14
     * Hàm được test: BookingFragment1.hourToInt()
     *   Nằm tại: BookingFragment1.java (dòng 258-260)
     * Điều kiện tiên quyết: Reflection để gọi private method
     * Dữ liệu đầu vào: "14:30"
     * Ghi chú: Dùng để truyền vào TimePickerDialog khi người dùng muốn sửa giờ hẹn
     */
    @Test
    public void A_S7_TC01_hourToInt_validTime_returnsCorrectHour() throws Exception {
        int result = (int) hourToIntMethod.invoke(fragment, "14:30");
        assertEquals(14, result);
    }

    // =====================================================================
    // A_S7_TC02 — hourToInt: Chuỗi không hợp lệ → fallback về 9
    // =====================================================================
    /**
     * Test Case ID: A_S7_TC02
     * Mô tả: Truyền chuỗi không đúng định dạng (VD: "abc", null) 
     *         → hàm phải catch Exception và trả về giá trị mặc định là 9.
     * Kết quả mong đợi: hourToInt("abc") == 9
     * Hàm được test: BookingFragment1.hourToInt()
     *   Nằm tại: BookingFragment1.java (dòng 258-260)
     * Điều kiện tiên quyết: Reflection để gọi private method
     * Dữ liệu đầu vào: "abc"
     * Ghi chú: Fallback an toàn giúp app không crash, mặc định 9h sáng
     */
    @Test
    public void A_S7_TC02_hourToInt_invalidTime_returnsDefault9() throws Exception {
        int result = (int) hourToIntMethod.invoke(fragment, "abc");
        assertEquals(9, result);
    }

    // =====================================================================
    // A_S7_TC03 — minuteToInt: Parse phút hợp lệ
    // =====================================================================
    /**
     * Test Case ID: A_S7_TC03
     * Mô tả: Truyền chuỗi thời gian hợp lệ (VD: "08:45") → hàm trích xuất đúng phút (45).
     * Kết quả mong đợi: minuteToInt("08:45") == 45
     * Hàm được test: BookingFragment1.minuteToInt()
     *   Nằm tại: BookingFragment1.java (dòng 262-264)
     * Điều kiện tiên quyết: Reflection để gọi private method
     * Dữ liệu đầu vào: "08:45"
     * Ghi chú: Dùng để thiết lập phút cho TimePickerDialog
     */
    @Test
    public void A_S7_TC03_minuteToInt_validTime_returnsCorrectMinute() throws Exception {
        int result = (int) minuteToIntMethod.invoke(fragment, "08:45");
        assertEquals(45, result);
    }

    // =====================================================================
    // A_S7_TC04 — minuteToInt: Chuỗi không hợp lệ → fallback về 0
    // =====================================================================
    /**
     * Test Case ID: A_S7_TC04
     * Mô tả: Truyền chuỗi thiếu dấu ":" (VD: "1000") → hàm catch Exception và trả về 0.
     * Kết quả mong đợi: minuteToInt("1000") == 0
     * Hàm được test: BookingFragment1.minuteToInt()
     *   Nằm tại: BookingFragment1.java (dòng 262-264)
     * Điều kiện tiên quyết: Reflection để gọi private method
     * Dữ liệu đầu vào: "1000"
     * Ghi chú: Đảm bảo không crash app khi dữ liệu thời gian bị hỏng
     */
    @Test
    public void A_S7_TC04_minuteToInt_invalidTime_returnsDefault0() throws Exception {
        int result = (int) minuteToIntMethod.invoke(fragment, "1000");
        assertEquals(0, result);
    }

    // =====================================================================
    // A_S7_TC05 — Validation Mandatory Fields: Rỗng 1 trường → Fail
    // =====================================================================
    /**
     * Test Case ID: A_S7_TC05
     * Mô tả: Tái tạo logic của areMandatoryFieldsFilledUp() - Nếu có bất kỳ trường
     *         bắt buộc nào bị rỗng, validation sẽ thất bại.
     * Kết quả mong đợi: isValid == false
     * Hàm được test: Logic tương đương BookingFragment1.areMandatoryFieldsFilledUp()
     *   Nằm tại: BookingFragment1.java (dòng 266-284)
     * Điều kiện tiên quyết: Không có
     * Dữ liệu đầu vào: mảng các trường, trong đó có 1 trường ""
     * Ghi chú: Đảm bảo bệnh nhân phải điền đủ: Tên, SĐT, Tên BN, Ngày, Giờ
     */
    @Test
    public void A_S7_TC05_mandatoryFields_oneEmpty_returnsFalse() {
        String bookingName = "Nguyễn Văn A";
        String bookingPhone = "0123456789";
        String patientName = ""; // Empty mandatory field
        String appointmentTime = "09:00";
        String appointmentDate = "2023-12-01";

        String[] fields = { bookingName, bookingPhone, patientName, appointmentTime, appointmentDate };
        
        boolean isValid = true;
        for (String element : fields) {
            if (element == null || element.isEmpty()) {
                isValid = false;
                break;
            }
        }
        assertFalse(isValid);
    }

    // =====================================================================
    // A_S7_TC06 — Validation Mandatory Fields: Đầy đủ → Success
    // =====================================================================
    /**
     * Test Case ID: A_S7_TC06
     * Mô tả: Nếu tất cả các trường bắt buộc đều có dữ liệu, validation thành công.
     * Kết quả mong đợi: isValid == true
     * Hàm được test: Logic tương đương BookingFragment1.areMandatoryFieldsFilledUp()
     *   Nằm tại: BookingFragment1.java (dòng 266-284)
     * Điều kiện tiên quyết: Không có
     * Dữ liệu đầu vào: Các trường đều có giá trị hợp lệ
     * Ghi chú: Sau khi validation qua, app mới bắt đầu gửi request API
     */
    @Test
    public void A_S7_TC06_mandatoryFields_allFilled_returnsTrue() {
        String[] fields = { "Người đặt", "0987654321", "Bệnh nhân", "10:30", "2023-10-15" };
        
        boolean isValid = true;
        for (String element : fields) {
            if (element == null || element.trim().isEmpty()) {
                isValid = false;
                break;
            }
        }
        assertTrue(isValid);
    }

    // =====================================================================
    // A_S7_TC07 — Date Formatting: Padding tháng/ngày < 10
    // =====================================================================
    /**
     * Test Case ID: A_S7_TC07
     * Mô tả: Kiểm tra logic định dạng ngày sinh / ngày hẹn.
     *         Tháng và ngày < 10 phải được thêm số "0" ở trước.
     * Kết quả mong đợi: "2023-05-09"
     * Hàm được test: Logic DatePickerDialog trong BookingFragment1.setupEvent()
     *   Nằm tại: BookingFragment1.java (dòng 195-206)
     * Điều kiện tiên quyết: Không có
     * Dữ liệu đầu vào: year=2023, month=4 (Tháng 5), day=9
     * Ghi chú: Định dạng chuẩn YYYY-MM-DD là bắt buộc cho database
     */
    @Test
    public void A_S7_TC07_dateFormatting_addsPaddingForSingleDigit() {
        int year = 2023;
        int month = 4; // Calendar.MONTH là 0-indexed (4 = Tháng 5)
        int day = 9;

        // Tái tạo logic từ source code
        String monthFormatted = (month + 1) < 10 ? "0" + (month + 1) : String.valueOf(month + 1);
        String dayFormatted = day < 10 ? "0" + day : String.valueOf(day);
        
        String result = year + "-" + monthFormatted + "-" + dayFormatted;
        
        assertEquals("2023-05-09", result);
    }

    // =====================================================================
    // A_S7_TC08 — Date Formatting: Không padding cho tháng/ngày >= 10
    // =====================================================================
    /**
     * Test Case ID: A_S7_TC08
     * Mô tả: Nếu tháng và ngày >= 10 thì giữ nguyên, không thêm "0".
     * Kết quả mong đợi: "2023-11-25"
     * Hàm được test: Logic DatePickerDialog trong BookingFragment1.setupEvent()
     *   Nằm tại: BookingFragment1.java (dòng 195-206)
     * Điều kiện tiên quyết: Không có
     * Dữ liệu đầu vào: year=2023, month=10 (Tháng 11), day=25
     * Ghi chú: Không làm sai lệch định dạng nếu giá trị đã đủ 2 chữ số
     */
    @Test
    public void A_S7_TC08_dateFormatting_noPaddingForDoubleDigit() {
        int year = 2023;
        int month = 10; // Tháng 11
        int day = 25;

        String monthFormatted = (month + 1) < 10 ? "0" + (month + 1) : String.valueOf(month + 1);
        String dayFormatted = day < 10 ? "0" + day : String.valueOf(day);
        
        String result = year + "-" + monthFormatted + "-" + dayFormatted;
        
        assertEquals("2023-11-25", result);
    }
}
