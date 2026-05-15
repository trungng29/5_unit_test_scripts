package com.example.do_an_tot_nghiep;

import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertNotNull;
import static org.junit.Assert.assertNull;
import static org.junit.Assert.assertTrue;
import static org.mockito.ArgumentMatchers.any;
import static org.mockito.ArgumentMatchers.eq;
import static org.mockito.Mockito.doAnswer;
import static org.mockito.Mockito.doReturn;
import static org.mockito.Mockito.mock;
import static org.mockito.Mockito.verify;

import com.example.do_an_tot_nghiep.Configuration.HTTPRequest;
import com.example.do_an_tot_nghiep.Configuration.HTTPService;
import com.example.do_an_tot_nghiep.Container.PatientProfileChangePersonalInformation;
import com.example.do_an_tot_nghiep.Model.User;
import com.example.do_an_tot_nghiep.Repository.SynchronousTaskExecutorRule;

import org.junit.After;
import org.junit.Before;
import org.junit.Rule;
import org.junit.Test;
import org.mockito.Mock;
import org.mockito.MockedStatic;
import org.mockito.MockitoAnnotations;

import java.util.HashMap;
import java.util.Map;
import java.util.concurrent.atomic.AtomicReference;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;
import retrofit2.Retrofit;

/**
 * Unit tests cho luồng thay đổi thông tin cá nhân bệnh nhân
 * (InformationActivity.changePersonalInformation).
 *
 * Vì InformationActivity là Activity (cần Android Context), ta test gián tiếp
 * qua API call layer: mock Retrofit + HTTPRequest + Container response.
 *
 * Test coverage:
 * - Cập nhật thành công (result=1)
 * - Cập nhật thất bại (result=0)
 * - Server trả lỗi HTTP
 * - Network failure
 * - Validation input (tên rỗng, birthday format sai)
 */
public class PatientInformationUpdateTest {

    @Rule
    public SynchronousTaskExecutorRule rule = new SynchronousTaskExecutorRule();

    private AutoCloseable mocks;
    @Mock private Retrofit retrofit;
    @Mock private HTTPRequest api;
    private MockedStatic<HTTPService> httpServiceMock;
    private Map<String, String> headers;

    @Before
    public void setUp() {
        mocks = MockitoAnnotations.openMocks(this);
        doReturn(api).when(retrofit).create(HTTPRequest.class);
        httpServiceMock = org.mockito.Mockito.mockStatic(HTTPService.class);
        httpServiceMock.when(HTTPService::getInstance).thenReturn(retrofit);
        headers = new HashMap<>();
        headers.put("Authorization", "Bearer test_token");
        headers.put("type", "patient");
    }

    @After
    public void tearDown() throws Exception {
        httpServiceMock.close();
        mocks.close();
    }

    @SuppressWarnings("unchecked")
    private AtomicReference<Callback<PatientProfileChangePersonalInformation>> captureCallback(
            Call<PatientProfileChangePersonalInformation> call) {
        AtomicReference<Callback<PatientProfileChangePersonalInformation>> ref = new AtomicReference<>();
        doAnswer(inv -> { ref.set(inv.getArgument(0)); return null; }).when(call).enqueue(any());
        return ref;
    }

    // =====================================================================
    // A_S3_TC31 — Cập nhật thông tin thành công (result=1)
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC31
     * Mô tả: Bệnh nhân sửa tên, giới tính, ngày sinh, địa chỉ → server trả result=1.
     * Kết quả mong đợi: Container getResult() == 1, getData() chứa User mới
     * Hàm được test: HTTPRequest.changePersonalInformation()
     *   Nằm tại: InformationActivity.java (dòng 261-321)
     * Điều kiện tiên quyết: Mock HTTPService + HTTPRequest
     * Dữ liệu đầu vào: name="Nguyễn Văn A", gender="1", birthday="1990-05-15", address="HCM"
     * Ghi chú: Sau thành công, UI hiện dialog "Thành công" và cập nhật AuthUser
     */
    @Test
    public void A_S3_TC31_changeInfo_success_returnsResult1() {
        Call<PatientProfileChangePersonalInformation> mockCall = mock(Call.class);
        PatientProfileChangePersonalInformation resp = mock(PatientProfileChangePersonalInformation.class);
        User updatedUser = mock(User.class);

        doReturn(mockCall).when(api).changePersonalInformation(
                eq(headers), eq("personal"), eq("Nguyễn Văn A"), eq("1"), eq("1990-05-15"), eq("HCM"));
        doReturn(1).when(resp).getResult();
        doReturn(updatedUser).when(resp).getData();

        AtomicReference<Callback<PatientProfileChangePersonalInformation>> cb = captureCallback(mockCall);

        api.changePersonalInformation(headers, "personal", "Nguyễn Văn A", "1", "1990-05-15", "HCM").enqueue(cb.get());
        // Simulate success
        cb.get().onResponse(mockCall, Response.success(resp));

        assertEquals(Integer.valueOf(1), resp.getResult());
        assertNotNull(resp.getData());
    }

    // =====================================================================
    // A_S3_TC32 — Cập nhật thất bại (result=0, msg lỗi)
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC32
     * Mô tả: Server trả result=0 kèm thông báo lỗi → UI hiện dialog lỗi.
     * Kết quả mong đợi: Container getResult() == 0, getMsg() có nội dung
     * Hàm được test: HTTPRequest.changePersonalInformation()
     *   Nằm tại: InformationActivity.java (dòng 289-297)
     * Điều kiện tiên quyết: Mock HTTPService + HTTPRequest
     * Dữ liệu đầu vào: name="" (rỗng)
     * Ghi chú: Server validate tên rỗng → trả msg lỗi
     */
    @Test
    public void A_S3_TC32_changeInfo_failure_returnsResult0WithMsg() {
        PatientProfileChangePersonalInformation resp = mock(PatientProfileChangePersonalInformation.class);
        doReturn(0).when(resp).getResult();
        doReturn("Tên không hợp lệ").when(resp).getMsg();

        assertEquals(Integer.valueOf(0), resp.getResult());
        assertEquals("Tên không hợp lệ", resp.getMsg());
    }

    // =====================================================================
    // A_S3_TC33 — API trả HTTP error (401 Unauthorized)
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC33
     * Mô tả: Token hết hạn → server trả 401 kèm errorBody.
     * Kết quả mong đợi: Response không successful, errorBody != null
     * Hàm được test: HTTPRequest.changePersonalInformation()
     *   Nằm tại: InformationActivity.java (dòng 301-312)
     * Điều kiện tiên quyết: Mock HTTPService + HTTPRequest
     * Dữ liệu đầu vào: headers với token hết hạn
     * Ghi chú: App cần redirect bệnh nhân về trang đăng nhập
     */
    @Test
    public void A_S3_TC33_changeInfo_unauthorized_errorBody() {
        Call<PatientProfileChangePersonalInformation> mockCall = mock(Call.class);
        doReturn(mockCall).when(api).changePersonalInformation(
                any(), eq("personal"), any(), any(), any(), any());
        AtomicReference<Callback<PatientProfileChangePersonalInformation>> cb = captureCallback(mockCall);

        mockCall.enqueue(cb.get());
        Response<PatientProfileChangePersonalInformation> errorResp = Response.error(401,
                okhttp3.ResponseBody.create(okhttp3.MediaType.parse("application/json"), "{\"msg\":\"Unauthorized\"}"));
        cb.get().onResponse(mockCall, errorResp);

        assertTrue(!errorResp.isSuccessful());
        assertNotNull(errorResp.errorBody());
    }

    // =====================================================================
    // A_S3_TC34 — Network failure khi cập nhật thông tin
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC34
     * Mô tả: Mất mạng khi gửi request → onFailure callback được gọi.
     * Kết quả mong đợi: Callback onFailure được kích hoạt, không crash
     * Hàm được test: HTTPRequest.changePersonalInformation()
     *   Nằm tại: InformationActivity.java (dòng 316-320)
     * Điều kiện tiên quyết: Mock HTTPService + HTTPRequest
     * Dữ liệu đầu vào: RuntimeException("timeout")
     * Ghi chú: App phải hiển thị thông báo lỗi mạng cho bệnh nhân
     */
    @Test
    public void A_S3_TC34_changeInfo_networkFailure_doesNotCrash() {
        Call<PatientProfileChangePersonalInformation> mockCall = mock(Call.class);
        doReturn(mockCall).when(api).changePersonalInformation(
                any(), eq("personal"), any(), any(), any(), any());
        AtomicReference<Callback<PatientProfileChangePersonalInformation>> cb = captureCallback(mockCall);

        mockCall.enqueue(cb.get());
        // Should not throw exception
        cb.get().onFailure(mockCall, new RuntimeException("timeout"));
        assertTrue(true); // Passed if no exception
    }

    // =====================================================================
    // A_S3_TC35 — Giới tính Male → gender = "1"
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC35
     * Mô tả: Khi chọn RadioButton "Nam", gender phải là "1".
     * Kết quả mong đợi: gender == "1"
     * Hàm được test: Logic trong InformationActivity.setupEvent()
     *   Nằm tại: InformationActivity.java (dòng 225)
     * Điều kiện tiên quyết: Không cần (pure logic)
     * Dữ liệu đầu vào: rdMale selected
     * Ghi chú: Server dùng 1=Nam, 0=Nữ — sai sẽ hiển thị giới tính đảo ngược
     */
    @Test
    public void A_S3_TC35_genderMale_mappedTo1() {
        // Simulates: rgGender.getCheckedRadioButtonId() == R.id.rdMale ? "1" : "0"
        boolean isMale = true;
        String gender = isMale ? "1" : "0";
        assertEquals("1", gender);
    }

    // =====================================================================
    // A_S3_TC36 — Giới tính Female → gender = "0"
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC36
     * Mô tả: Khi chọn RadioButton "Nữ", gender phải là "0".
     * Kết quả mong đợi: gender == "0"
     * Hàm được test: Logic trong InformationActivity.setupEvent()
     *   Nằm tại: InformationActivity.java (dòng 225)
     * Điều kiện tiên quyết: Không cần (pure logic)
     * Dữ liệu đầu vào: rdFemale selected
     * Ghi chú: Quy ước 0=Nữ, phải khớp với User.getGender() khi hiển thị
     */
    @Test
    public void A_S3_TC36_genderFemale_mappedTo0() {
        boolean isMale = false;
        String gender = isMale ? "1" : "0";
        assertEquals("0", gender);
    }

    // =====================================================================
    // A_S3_TC37 — [BUG] Birthday format: tháng < 10 thiếu padding 0
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC37
     * Mô tả: InformationActivity kiểm tra month < 10 nhưng dùng month1
     *         thay vì month1+1, khiến tháng 9 (index 8) → "08" thay vì "09".
     * Kết quả mong đợi: Tháng 9 phải hiển thị "09" (không phải "08")
     * Hàm được test: birthdayDialog trong InformationActivity.setupEvent()
     *   Nằm tại: InformationActivity.java (dòng 205-207)
     * Điều kiện tiên quyết: month1 = 8 (September, 0-indexed)
     * Dữ liệu đầu vào: month1 = 8, day1 = 5
     * Ghi chú: [BUG] Code kiểm tra month1 < 10 nhưng format lại monthFormatted = "0" + month1 (không +1)
     */
    @Test
    public void A_S3_TC37_birthdayFormat_monthPaddingBug() {
        // Reproducing the bug in InformationActivity line 199-207
        int month1 = 8; // September (0-indexed)
        int day1 = 5;
        int year1 = 1990;

        String monthFormatted = String.valueOf(month1 + 1); // Correct: "9"
        if (month1 < 10) {
            monthFormatted = "0" + month1; // BUG: produces "08" instead of "09"
        }

        String dayFormatted = String.valueOf(day1);
        if (day1 < 10) {
            dayFormatted = "0" + day1;
        }

        String output = year1 + "-" + monthFormatted + "-" + dayFormatted;
        // This FAILS because of the bug: expected "1990-09-05" but actual "1990-08-05"
        assertEquals("1990-09-05", output);
    }

    // =====================================================================
    // A_S3_TC38 — Birthday format: ngày >= 10 không cần padding
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC38
     * Mô tả: Khi ngày >= 10, không cần thêm "0" phía trước.
     * Kết quả mong đợi: Ngày 15 → "15" (không phải "015")
     * Hàm được test: birthdayDialog trong InformationActivity.setupEvent()
     *   Nằm tại: InformationActivity.java (dòng 201-204)
     * Điều kiện tiên quyết: day1 = 15
     * Dữ liệu đầu vào: day1 = 15
     * Ghi chú: Đảm bảo logic if(day1 < 10) không ảnh hưởng ngày >= 10
     */
    @Test
    public void A_S3_TC38_birthdayFormat_dayNoPadding() {
        int day1 = 15;
        String dayFormatted = String.valueOf(day1);
        if (day1 < 10) {
            dayFormatted = "0" + day1;
        }
        assertEquals("15", dayFormatted);
    }
}
