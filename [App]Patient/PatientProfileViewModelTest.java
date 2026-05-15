package com.example.do_an_tot_nghiep;

import static org.junit.Assert.assertNull;
import static org.junit.Assert.assertSame;
import static org.mockito.ArgumentMatchers.any;
import static org.mockito.ArgumentMatchers.isNull;
import static org.mockito.Mockito.doAnswer;
import static org.mockito.Mockito.doReturn;
import static org.mockito.Mockito.mock;
import static org.mockito.Mockito.never;
import static org.mockito.Mockito.verify;

import com.example.do_an_tot_nghiep.Configuration.HTTPRequest;
import com.example.do_an_tot_nghiep.Configuration.HTTPService;
import com.example.do_an_tot_nghiep.Container.PatientProfile;
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
 * Unit tests cho MainViewModel — đọc hồ sơ bệnh nhân (Patient Profile).
 *
 * Các thành phần được test:
 * - MainViewModel.readPersonalInformation(headers)
 * - Callback xử lý response thành công / lỗi / failure
 * - Container: PatientProfile (result, msg, data)
 *
 * Một số test case cố ý FAIL để phát hiện bug thực tế trong mã nguồn.
 */
public class PatientProfileViewModelTest {

    @Rule
    public SynchronousTaskExecutorRule rule = new SynchronousTaskExecutorRule();

    private AutoCloseable mocks;
    @Mock private Retrofit retrofit;
    @Mock private HTTPRequest api;
    private MockedStatic<HTTPService> httpServiceMock;
    private MainViewModel vm;
    private Map<String, String> headers;

    @Before
    public void setUp() {
        mocks = MockitoAnnotations.openMocks(this);
        vm = new MainViewModel();
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

    /* ========== HELPER ========== */
    @SuppressWarnings("unchecked")
    private AtomicReference<Callback<PatientProfile>> captureCallback(Call<PatientProfile> call) {
        AtomicReference<Callback<PatientProfile>> ref = new AtomicReference<>();
        doAnswer(inv -> { ref.set(inv.getArgument(0)); return null; }).when(call).enqueue(any());
        return ref;
    }

    // =====================================================================
    // A_S3_TC01 — Đọc hồ sơ bệnh nhân thành công
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC01
     * Mô tả: Gửi request đọc hồ sơ bệnh nhân với headers hợp lệ,
     *         server trả về 200 OK kèm PatientProfile → LiveData được cập nhật.
     * Kết quả mong đợi: LiveData chứa đúng đối tượng PatientProfile từ server.
     * Hàm được test: MainViewModel.readPersonalInformation(headers)
     *   Nằm tại: MainViewModel.java (dòng 37-78)
     * Điều kiện tiên quyết: Mock HTTPService, Retrofit, HTTPRequest
     * Dữ liệu đầu vào: headers = {Authorization: "Bearer test_token", type: "patient"}
     * Ghi chú: Đây là luồng chính (happy path) của tính năng xem hồ sơ bệnh nhân
     */
    @Test
    public void A_S3_TC01_readProfile_success_updatesLiveData() {
        Call<PatientProfile> mockCall = mock(Call.class);
        PatientProfile expected = mock(PatientProfile.class);
        doReturn(mockCall).when(api).readPersonalInformation(headers);
        AtomicReference<Callback<PatientProfile>> cb = captureCallback(mockCall);

        vm.readPersonalInformation(headers);
        cb.get().onResponse(mockCall, Response.success(expected));

        assertSame(expected, vm.getResponse().getValue());
    }

    // =====================================================================
    // A_S3_TC02 — Server trả lỗi 400 với errorBody → LiveData bị xóa
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC02
     * Mô tả: Server trả HTTP 400 kèm errorBody (ví dụ: token hết hạn)
     *         → LiveData phải được set null để UI hiển thị trạng thái lỗi.
     * Kết quả mong đợi: LiveData.getValue() == null
     * Hàm được test: MainViewModel.readPersonalInformation(headers)
     *   Nằm tại: MainViewModel.java (dòng 58-69)
     * Điều kiện tiên quyết: Mock HTTPService trả về API call
     * Dữ liệu đầu vào: Response.error(400, body={"message":"bad request"})
     * Ghi chú: Kiểm tra hệ thống xử lý đúng khi token không hợp lệ hoặc hết hạn
     */
    @Test
    public void A_S3_TC02_readProfile_errorResponse_clearsLiveData() {
        Call<PatientProfile> mockCall = mock(Call.class);
        doReturn(mockCall).when(api).readPersonalInformation(headers);
        AtomicReference<Callback<PatientProfile>> cb = captureCallback(mockCall);

        vm.readPersonalInformation(headers);
        cb.get().onResponse(mockCall,
                Response.error(400, okhttp3.ResponseBody.create(
                        okhttp3.MediaType.parse("application/json"), "{\"message\":\"bad request\"}")));

        assertNull(vm.getResponse().getValue());
    }

    // =====================================================================
    // A_S3_TC03 — Response 200 nhưng body null → LiveData giữ null
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC03
     * Mô tả: Server trả 200 OK nhưng body rỗng (null) — trường hợp biên.
     * Kết quả mong đợi: LiveData.getValue() == null (không crash)
     * Hàm được test: MainViewModel.readPersonalInformation(headers)
     *   Nằm tại: MainViewModel.java (dòng 50-57)
     * Điều kiện tiên quyết: Mock HTTPService trả về API call
     * Dữ liệu đầu vào: Response.success(null)
     * Ghi chú: Kiểm tra hệ thống không bị NullPointerException khi body rỗng
     */
    @Test
    public void A_S3_TC03_readProfile_nullBody_doesNotCrash() {
        Call<PatientProfile> mockCall = mock(Call.class);
        doReturn(mockCall).when(api).readPersonalInformation(headers);
        AtomicReference<Callback<PatientProfile>> cb = captureCallback(mockCall);

        vm.readPersonalInformation(headers);
        cb.get().onResponse(mockCall, Response.success((PatientProfile) null));

        assertNull(vm.getResponse().getValue());
    }

    // =====================================================================
    // A_S3_TC04 — [BUG] Truyền null headers → vẫn gọi API
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC04
     * Mô tả: Truyền null headers vào readPersonalInformation() — code hiện tại
     *         không kiểm tra nên vẫn gọi API với null, có thể crash trên server.
     * Kết quả mong đợi: API KHÔNG được gọi khi headers null
     * Hàm được test: MainViewModel.readPersonalInformation(null)
     *   Nằm tại: MainViewModel.java (dòng 37-43)
     * Điều kiện tiên quyết: Mock HTTPService trả về API call
     * Dữ liệu đầu vào: null
     * Ghi chú: [BUG] Thiếu validation null cho headers — test này sẽ FAIL để chỉ ra lỗi
     */
    @Test
    public void A_S3_TC04_readProfile_nullHeaders_shouldNotCallApi() {
        Call<PatientProfile> mockCall = mock(Call.class);
        doReturn(mockCall).when(api).readPersonalInformation(isNull());

        vm.readPersonalInformation(null);

        verify(api, never()).readPersonalInformation(isNull());
    }

    // =====================================================================
    // A_S3_TC05 — [BUG] Error không có errorBody → dữ liệu cũ còn tồn tại
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC05
     * Mô tả: Lần 1 thành công → lần 2 lỗi nhưng errorBody == null
     *         → LiveData vẫn giữ dữ liệu cũ (stale data).
     * Kết quả mong đợi: LiveData phải được xóa (null) khi có lỗi
     * Hàm được test: MainViewModel.readPersonalInformation(headers)
     *   Nằm tại: MainViewModel.java (dòng 48-69)
     * Điều kiện tiên quyết: Gọi 2 lần liên tiếp: lần 1 thành công, lần 2 lỗi
     * Dữ liệu đầu vào: Response lỗi không có errorBody
     * Ghi chú: [BUG] Code chỉ xử lý lỗi khi errorBody != null, bỏ qua trường hợp còn lại
     */
    @Test
    public void A_S3_TC05_readProfile_staleData_notCleared() {
        Call<PatientProfile> successCall = mock(Call.class);
        Call<PatientProfile> failCall = mock(Call.class);
        PatientProfile cached = mock(PatientProfile.class);
        doReturn(successCall, failCall).when(api).readPersonalInformation(headers);
        AtomicReference<Callback<PatientProfile>> cb1 = captureCallback(successCall);
        AtomicReference<Callback<PatientProfile>> cb2 = captureCallback(failCall);

        vm.readPersonalInformation(headers);
        cb1.get().onResponse(successCall, Response.success(cached));
        assertSame(cached, vm.getResponse().getValue());

        vm.readPersonalInformation(headers);
        @SuppressWarnings("unchecked")
        Response<PatientProfile> errorResp = mock(Response.class);
        doReturn(false).when(errorResp).isSuccessful();
        doReturn(null).when(errorResp).errorBody();
        cb2.get().onResponse(failCall, errorResp);

        assertNull(vm.getResponse().getValue());
    }

    // =====================================================================
    // A_S3_TC06 — Network failure → LiveData set null
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC06
     * Mô tả: Mạng bị mất hoặc timeout → callback onFailure được gọi
     *         → LiveData phải được set null để UI xử lý.
     * Kết quả mong đợi: LiveData.getValue() == null
     * Hàm được test: MainViewModel.readPersonalInformation(headers)
     *   Nằm tại: MainViewModel.java (dòng 72-77)
     * Điều kiện tiên quyết: Mock HTTPService trả về API call
     * Dữ liệu đầu vào: RuntimeException("network down")
     * Ghi chú: Đảm bảo app không crash khi mất kết nối mạng
     */
    @Test
    public void A_S3_TC06_readProfile_networkFailure_clearsLiveData() {
        Call<PatientProfile> mockCall = mock(Call.class);
        doReturn(mockCall).when(api).readPersonalInformation(headers);
        AtomicReference<Callback<PatientProfile>> cb = captureCallback(mockCall);

        vm.readPersonalInformation(headers);
        cb.get().onFailure(mockCall, new RuntimeException("network down"));

        assertNull(vm.getResponse().getValue());
    }

    // =====================================================================
    // A_S3_TC07 — Gọi API → verify enqueue được gọi đúng 1 lần
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC07
     * Mô tả: Khi gọi readPersonalInformation(), hệ thống phải gọi enqueue()
     *         đúng 1 lần để gửi request bất đồng bộ lên server.
     * Kết quả mong đợi: verify(call).enqueue(any()) thành công
     * Hàm được test: MainViewModel.readPersonalInformation(headers)
     *   Nằm tại: MainViewModel.java (dòng 47)
     * Điều kiện tiên quyết: Mock HTTPService trả về API call
     * Dữ liệu đầu vào: headers hợp lệ
     * Ghi chú: Đảm bảo request được gửi đi đúng 1 lần, không bị duplicate
     */
    @Test
    public void A_S3_TC07_readProfile_verifiesEnqueueCalledOnce() {
        Call<PatientProfile> mockCall = mock(Call.class);
        doReturn(mockCall).when(api).readPersonalInformation(headers);

        vm.readPersonalInformation(headers);

        verify(mockCall).enqueue(any());
    }

    // =====================================================================
    // A_S3_TC08 — Headers thiếu Authorization → API vẫn được gọi
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC08
     * Mô tả: Headers chỉ có "type" mà thiếu "Authorization" → code hiện tại
     *         không validate nên vẫn gọi API. Server sẽ trả 401.
     * Kết quả mong đợi: API vẫn được gọi (vì thiếu validation phía client)
     * Hàm được test: MainViewModel.readPersonalInformation(headers)
     *   Nằm tại: MainViewModel.java (dòng 37-43)
     * Điều kiện tiên quyết: Mock HTTPService trả về API call
     * Dữ liệu đầu vào: headers = {type: "patient"} (thiếu Authorization)
     * Ghi chú: Lỗi thiết kế — client nên validate token trước khi gọi API
     */
    @Test
    public void A_S3_TC08_readProfile_missingAuth_stillCallsApi() {
        Map<String, String> incompleteHeaders = new HashMap<>();
        incompleteHeaders.put("type", "patient");
        Call<PatientProfile> mockCall = mock(Call.class);
        doReturn(mockCall).when(api).readPersonalInformation(incompleteHeaders);

        vm.readPersonalInformation(incompleteHeaders);

        verify(api).readPersonalInformation(incompleteHeaders);
    }

    // =====================================================================
    // A_S3_TC09 — Headers rỗng (empty map) → API vẫn được gọi
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC09
     * Mô tả: Truyền map rỗng → code không validate nên vẫn gọi API.
     * Kết quả mong đợi: API vẫn được gọi với empty headers
     * Hàm được test: MainViewModel.readPersonalInformation(headers)
     *   Nằm tại: MainViewModel.java (dòng 37-43)
     * Điều kiện tiên quyết: Mock HTTPService trả về API call
     * Dữ liệu đầu vào: new HashMap<>() (rỗng)
     * Ghi chú: Lỗi thiết kế — thiếu guard clause cho empty headers
     */
    @Test
    public void A_S3_TC09_readProfile_emptyHeaders_stillCallsApi() {
        Map<String, String> emptyHeaders = new HashMap<>();
        Call<PatientProfile> mockCall = mock(Call.class);
        doReturn(mockCall).when(api).readPersonalInformation(emptyHeaders);

        vm.readPersonalInformation(emptyHeaders);

        verify(api).readPersonalInformation(emptyHeaders);
    }

    // =====================================================================
    // A_S3_TC10 — getResponse() trả về LiveData không null
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC10
     * Mô tả: Ngay sau khi khởi tạo MainViewModel, getResponse() phải trả về
     *         đối tượng MutableLiveData (không null) để Observer có thể đăng ký.
     * Kết quả mong đợi: vm.getResponse() != null
     * Hàm được test: MainViewModel.getResponse()
     *   Nằm tại: MainViewModel.java (dòng 29-31)
     * Điều kiện tiên quyết: Khởi tạo MainViewModel
     * Dữ liệu đầu vào: Không có
     * Ghi chú: Đảm bảo LiveData được khởi tạo ngay trong field declaration
     */
    @Test
    public void A_S3_TC10_getResponse_returnsNonNullLiveData() {
        org.junit.Assert.assertNotNull(vm.getResponse());
    }

    // =====================================================================
    // A_S3_TC11 — LiveData ban đầu chứa giá trị null
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC11
     * Mô tả: Khi chưa gọi readPersonalInformation(), LiveData chưa có
     *         dữ liệu → getValue() phải là null.
     * Kết quả mong đợi: vm.getResponse().getValue() == null
     * Hàm được test: MainViewModel.getResponse()
     *   Nằm tại: MainViewModel.java (dòng 27)
     * Điều kiện tiên quyết: Khởi tạo MainViewModel, chưa gọi API
     * Dữ liệu đầu vào: Không có
     * Ghi chú: Trạng thái khởi tạo đúng cho LiveData trước khi có dữ liệu
     */
    @Test
    public void A_S3_TC11_liveData_initialValueIsNull() {
        assertNull(vm.getResponse().getValue());
    }

    // =====================================================================
    // A_S3_TC12 — Gọi liên tiếp 2 lần thành công → LiveData chứa data mới nhất
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC12
     * Mô tả: Gọi readPersonalInformation() 2 lần liên tiếp, cả 2 đều thành công
     *         → LiveData phải chứa data từ lần gọi thứ 2.
     * Kết quả mong đợi: LiveData chứa profile2 (không phải profile1)
     * Hàm được test: MainViewModel.readPersonalInformation(headers)
     *   Nằm tại: MainViewModel.java (dòng 37-78)
     * Điều kiện tiên quyết: Mock HTTPService trả về 2 API calls liên tiếp
     * Dữ liệu đầu vào: 2 PatientProfile khác nhau
     * Ghi chú: Đảm bảo LiveData luôn chứa dữ liệu mới nhất
     */
    @Test
    public void A_S3_TC12_readProfile_consecutiveCalls_latestDataWins() {
        Call<PatientProfile> call1 = mock(Call.class);
        Call<PatientProfile> call2 = mock(Call.class);
        PatientProfile profile1 = mock(PatientProfile.class);
        PatientProfile profile2 = mock(PatientProfile.class);
        doReturn(call1, call2).when(api).readPersonalInformation(headers);
        AtomicReference<Callback<PatientProfile>> cb1 = captureCallback(call1);
        AtomicReference<Callback<PatientProfile>> cb2 = captureCallback(call2);

        vm.readPersonalInformation(headers);
        cb1.get().onResponse(call1, Response.success(profile1));
        assertSame(profile1, vm.getResponse().getValue());

        vm.readPersonalInformation(headers);
        cb2.get().onResponse(call2, Response.success(profile2));
        assertSame(profile2, vm.getResponse().getValue());
    }
}
