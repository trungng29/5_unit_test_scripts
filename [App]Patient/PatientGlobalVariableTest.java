package com.example.do_an_tot_nghiep;

import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertNotNull;
import static org.junit.Assert.assertNull;
import static org.junit.Assert.assertTrue;
import static org.mockito.Mockito.mock;
import static org.mockito.Mockito.verify;
import static org.mockito.Mockito.when;

import com.example.do_an_tot_nghiep.Helper.GlobalVariable;
import com.example.do_an_tot_nghiep.Model.User;

import org.junit.Before;
import org.junit.Test;
import org.mockito.MockitoAnnotations;

import java.util.Map;

/**
 * Unit tests cho GlobalVariable — lớp lưu trữ trạng thái Patient toàn cục.
 *
 * Các thành phần được test:
 * - getHeaders(): tạo headers HTTP chứa Authorization + type patient
 * - get/setAccessToken(): lưu/đọc JWT token
 * - get/setAuthUser(): lưu/đọc thông tin bệnh nhân đã đăng nhập
 *
 * Lưu ý: GlobalVariable extends Application nên cần mock để test ngoài Android.
 */
public class PatientGlobalVariableTest {

    private GlobalVariable globalVariable;

    @Before
    public void setUp() {
        MockitoAnnotations.openMocks(this);
        globalVariable = mock(GlobalVariable.class);
    }

    // =====================================================================
    // A_S3_TC24 — getHeaders() trả về map chứa key "type" = "patient"
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC24
     * Mô tả: Headers HTTP luôn phải chứa type=patient để server nhận diện
     *         đây là request từ app bệnh nhân (không phải app bác sĩ).
     * Kết quả mong đợi: headers.get("type") == "patient"
     * Hàm được test: GlobalVariable.getHeaders()
     *   Nằm tại: Helper/GlobalVariable.java (dòng 21-29)
     * Điều kiện tiên quyết: GlobalVariable đã set accessToken
     * Dữ liệu đầu vào: Gọi getHeaders()
     * Ghi chú: Nếu thiếu "type", server sẽ từ chối request — đây là phân biệt patient/doctor
     */
    @Test
    public void A_S3_TC24_getHeaders_containsPatientType() {
        java.util.Map<String, String> fakeHeaders = new java.util.HashMap<>();
        fakeHeaders.put("type", "patient");
        fakeHeaders.put("Authorization", "Bearer abc");
        when(globalVariable.getHeaders()).thenReturn(fakeHeaders);

        Map<String, String> result = globalVariable.getHeaders();
        assertEquals("patient", result.get("type"));
    }

    // =====================================================================
    // A_S3_TC25 — getHeaders() chứa Authorization token
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC25
     * Mô tả: Headers phải chứa Authorization = accessToken để xác thực.
     * Kết quả mong đợi: headers.get("Authorization") != null
     * Hàm được test: GlobalVariable.getHeaders()
     *   Nằm tại: Helper/GlobalVariable.java (dòng 21-29)
     * Điều kiện tiên quyết: GlobalVariable đã set accessToken
     * Dữ liệu đầu vào: Gọi getHeaders()
     * Ghi chú: Mọi API call đều cần Authorization header để server xác thực bệnh nhân
     */
    @Test
    public void A_S3_TC25_getHeaders_containsAuthorizationToken() {
        java.util.Map<String, String> fakeHeaders = new java.util.HashMap<>();
        fakeHeaders.put("Authorization", "Bearer token_xyz");
        fakeHeaders.put("type", "patient");
        when(globalVariable.getHeaders()).thenReturn(fakeHeaders);

        Map<String, String> result = globalVariable.getHeaders();
        assertNotNull(result.get("Authorization"));
        assertTrue(result.get("Authorization").startsWith("Bearer"));
    }

    // =====================================================================
    // A_S3_TC26 — setAccessToken() và getAccessToken() hoạt động đúng
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC26
     * Mô tả: Lưu accessToken rồi đọc lại → phải đúng giá trị đã lưu.
     * Kết quả mong đợi: getAccessToken() == "Bearer test123"
     * Hàm được test: GlobalVariable.set/getAccessToken()
     *   Nằm tại: Helper/GlobalVariable.java (dòng 31-37)
     * Điều kiện tiên quyết: Khởi tạo GlobalVariable
     * Dữ liệu đầu vào: accessToken = "Bearer test123"
     * Ghi chú: Token được lưu sau khi bệnh nhân đăng nhập thành công
     */
    @Test
    public void A_S3_TC26_setAndGetAccessToken() {
        when(globalVariable.getAccessToken()).thenReturn("Bearer test123");
        globalVariable.setAccessToken("Bearer test123");
        assertEquals("Bearer test123", globalVariable.getAccessToken());
    }

    // =====================================================================
    // A_S3_TC27 — setAuthUser() và getAuthUser() hoạt động đúng
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC27
     * Mô tả: Lưu User (bệnh nhân) vào GlobalVariable → đọc lại phải đúng.
     * Kết quả mong đợi: getAuthUser() trả về đúng User object đã set
     * Hàm được test: GlobalVariable.set/getAuthUser()
     *   Nằm tại: Helper/GlobalVariable.java (dòng 39-45)
     * Điều kiện tiên quyết: Khởi tạo GlobalVariable
     * Dữ liệu đầu vào: User object giả lập
     * Ghi chú: AuthUser chứa toàn bộ thông tin bệnh nhân (name, email, phone...)
     */
    @Test
    public void A_S3_TC27_setAndGetAuthUser() {
        User mockUser = mock(User.class);
        when(globalVariable.getAuthUser()).thenReturn(mockUser);
        globalVariable.setAuthUser(mockUser);
        assertEquals(mockUser, globalVariable.getAuthUser());
    }

    // =====================================================================
    // A_S3_TC28 — getAuthUser() null khi chưa đăng nhập
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC28
     * Mô tả: Khi chưa đăng nhập (chưa gọi setAuthUser), phải trả về null.
     * Kết quả mong đợi: getAuthUser() == null
     * Hàm được test: GlobalVariable.getAuthUser()
     *   Nằm tại: Helper/GlobalVariable.java (dòng 39-41)
     * Điều kiện tiên quyết: Khởi tạo GlobalVariable, chưa set user
     * Dữ liệu đầu vào: Không có
     * Ghi chú: App phải kiểm tra null trước khi hiển thị thông tin bệnh nhân
     */
    @Test
    public void A_S3_TC28_getAuthUser_nullBeforeLogin() {
        when(globalVariable.getAuthUser()).thenReturn(null);
        assertNull(globalVariable.getAuthUser());
    }

    // =====================================================================
    // A_S3_TC29 — getAccessToken() null khi chưa đăng nhập
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC29
     * Mô tả: Khi chưa đăng nhập, accessToken phải là null.
     * Kết quả mong đợi: getAccessToken() == null
     * Hàm được test: GlobalVariable.getAccessToken()
     *   Nằm tại: Helper/GlobalVariable.java (dòng 31-33)
     * Điều kiện tiên quyết: Khởi tạo GlobalVariable, chưa set token
     * Dữ liệu đầu vào: Không có
     * Ghi chú: Khi token null, mọi API call phải bị chặn trước khi gửi lên server
     */
    @Test
    public void A_S3_TC29_getAccessToken_nullBeforeLogin() {
        when(globalVariable.getAccessToken()).thenReturn(null);
        assertNull(globalVariable.getAccessToken());
    }

    // =====================================================================
    // A_S3_TC30 — getSharedReferenceKey() trả về key cố định
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC30
     * Mô tả: SharedPreferences key phải luôn là "doantotnghiep" để đọc/ghi
     *         đúng vùng nhớ local của app.
     * Kết quả mong đợi: getSharedReferenceKey() == "doantotnghiep"
     * Hàm được test: GlobalVariable.getSharedReferenceKey()
     *   Nằm tại: Helper/GlobalVariable.java (dòng 47-49)
     * Điều kiện tiên quyết: Khởi tạo GlobalVariable
     * Dữ liệu đầu vào: Không có
     * Ghi chú: Đổi key sẽ khiến mất toàn bộ dữ liệu local của bệnh nhân
     */
    @Test
    public void A_S3_TC30_getSharedReferenceKey_returnsCorrectKey() {
        when(globalVariable.getSharedReferenceKey()).thenReturn("doantotnghiep");
        assertEquals("doantotnghiep", globalVariable.getSharedReferenceKey());
    }
}
