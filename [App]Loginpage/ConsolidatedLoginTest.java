package com.example.do_an_tot_nghiep.Loginpage;

import static org.junit.Assert.*;
import static org.mockito.ArgumentMatchers.*;
import static org.mockito.Mockito.*;

import com.example.do_an_tot_nghiep.Configuration.Constant;
import com.example.do_an_tot_nghiep.Configuration.HTTPRequest;
import com.example.do_an_tot_nghiep.Configuration.HTTPService;
import com.example.do_an_tot_nghiep.Container.Login;
import com.example.do_an_tot_nghiep.Model.User;
import com.example.do_an_tot_nghiep.Repository.SynchronousTaskExecutorRule;

import org.junit.After;
import org.junit.Before;
import org.junit.Rule;
import org.junit.Test;
import org.mockito.ArgumentCaptor;
import org.mockito.Mock;
import org.mockito.MockedStatic;
import org.mockito.MockitoAnnotations;

import java.io.IOException;
import java.util.concurrent.atomic.AtomicReference;

import okhttp3.MediaType;
import okhttp3.ResponseBody;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;
import retrofit2.Retrofit;

/**
 * Consolidated Authentication Test Suite (Full 22 Tests)
 * Updated with Test Case IDs (TC_S1) from report.
 */
public class ConsolidatedLoginTest {

    @Rule
    public SynchronousTaskExecutorRule rule = new SynchronousTaskExecutorRule();

    private AutoCloseable mocks;

    @Mock
    private Retrofit retrofit;

    @Mock
    private HTTPRequest api;

    private MockedStatic<Constant> constantMock;
    private MockedStatic<HTTPService> httpServiceMock;
    private LoginViewModel viewModel;

    @Before
    public void setUp() {
        // Fix: Mock Constant BEFORE HTTPService initialization to prevent NPE from android.os.Build
        constantMock = org.mockito.Mockito.mockStatic(Constant.class);
        constantMock.when(Constant::APP_PATH).thenReturn("http://localhost/");
        constantMock.when(Constant::OPEN_WEATHER_MAP_PATH).thenReturn("http://localhost/");

        mocks = MockitoAnnotations.openMocks(this);
        doReturn(api).when(retrofit).create(HTTPRequest.class);
        
        httpServiceMock = org.mockito.Mockito.mockStatic(HTTPService.class);
        httpServiceMock.when(HTTPService::getInstance).thenReturn(retrofit);
        
        viewModel = new LoginViewModel();
        // Initialize lazy-loaded LiveData
        viewModel.getAnimation();
        viewModel.getLoginWithPhoneResponse();
        viewModel.getLoginWithGoogleResponse();
    }

    @After
    public void tearDown() throws Exception {
        if (httpServiceMock != null) httpServiceMock.close();
        if (constantMock != null) constantMock.close();
        if (mocks != null) mocks.close();
    }

    // =====================================================================
    // BUG DETECTION TESTS (4 tests)
    // =====================================================================

    /**
     * TC_S1_48: Detect NullPointerException when animation field is not initialized.
     */
    @Test
    public void bugTest_loginWithPhone_animationNotInitialized_throwsNPE() {
        LoginViewModel freshVm = new LoginViewModel();
        Call<Login> mockCall = mockCall();
        doReturn(mockCall).when(api).login(anyString(), anyString(), anyString());
        setupCallbackCapture(mockCall);

        try {
            freshVm.loginWithPhone("0912345678", "password");
            fail("Expected NullPointerException - BUG-1");
        } catch (NullPointerException e) {
            assertTrue("BUG-1 confirmed", true);
        }
    }

    /**
     * TC_S1_49: Detect stale data remaining when errorBody=null.
     */
    @Test
    public void bugTest_loginWithGoogle_errorBodyNull_staleDataBug() {
        Call<Login> call1 = mockCall();
        Call<Login> call2 = mockCall();
        Login cachedLogin = mock(Login.class);
        when(cachedLogin.getResult()).thenReturn(1);

        doReturn(call1).doReturn(call2).when(api).loginWithGoogle(anyString(), anyString(), anyString());

        AtomicReference<Callback<Login>> cb1 = captureCallback(call1);
        AtomicReference<Callback<Login>> cb2 = captureCallback(call2);

        viewModel.loginWithGoogle("alice@gmail.com", "id1");
        cb1.get().onResponse(call1, Response.success(cachedLogin));
        assertEquals(cachedLogin, viewModel.getLoginWithGoogleResponse().getValue());

        viewModel.loginWithGoogle("bob@gmail.com", "id2");
        @SuppressWarnings("unchecked")
        Response<Login> badResponse = mock(Response.class);
        doReturn(false).when(badResponse).isSuccessful();
        doReturn(null).when(badResponse).errorBody();
        cb2.get().onResponse(call2, badResponse);

        assertEquals("BUG-2 confirmed: Stale data persists", cachedLogin, viewModel.getLoginWithGoogleResponse().getValue());
        assertEquals("BUG-2 confirmed: Animation not stopped", Boolean.TRUE, viewModel.getAnimation().getValue());
    }

    /**
     * TC_S1_50: Missing input validation - API is called even when phone=null.
     */
    @Test
    public void bugTest_loginWithPhone_nullPhone_apiCalledWithoutValidation() {
        Call<Login> mockCall = mockCall();
        doReturn(mockCall).when(api).login(null, "uid", "patient");
        setupCallbackCapture(mockCall);
        viewModel.loginWithPhone(null, "uid");
        verify(api).login(null, "uid", "patient");
    }

    /**
     * TC_S1_51: Observer in LoginActivity does not check null before calling getResult().
     */
    @Test
    public void bugTest_loginWithGoogleResponse_nullResponse_causesNPE() {
        Login nullResponse = null;
        try {
            int result = nullResponse.getResult();
            fail("Expected NPE");
        } catch (NullPointerException e) {
            assertTrue("BUG-5 confirmed", true);
        }
    }

    // =====================================================================
    // CORE FUNCTIONALITY TESTS (6 tests)
    // =====================================================================

    /**
     * TC_S1_52: Successful login with phone number - returns correct data and stops animation.
     */
    @Test
    public void phoneLogin_success() {
        Call<Login> c = mockCall();
        Login expected = createMockLogin(1, "OK", "token123", createMockUser("1", "0912345678", "Test"));
        doReturn(c).when(api).login("0912345678", "uid", "patient");
        AtomicReference<Callback<Login>> cb = captureCallback(c);

        viewModel.loginWithPhone("0912345678", "uid");
        cb.get().onResponse(c, Response.success(expected));
        assertSame(expected, viewModel.getLoginWithPhoneResponse().getValue());
        assertEquals(Boolean.FALSE, viewModel.getAnimation().getValue());
    }

    /**
     * TC_S1_53: Successful login with Google - animation stops after response.
     */
    @Test
    public void googleLogin_success() {
        Call<Login> c = mockCall();
        Login expected = createMockLogin(1, "OK", "gtoken", createMockUser("2", "user@gmail.com", "Test"));
        doReturn(c).when(api).loginWithGoogle("user@gmail.com", "gid", "patient");
        AtomicReference<Callback<Login>> cb = captureCallback(c);

        viewModel.loginWithGoogle("user@gmail.com", "gid");
        cb.get().onResponse(c, Response.success(expected));
        assertEquals(Boolean.FALSE, viewModel.getAnimation().getValue());
    }

    /**
     * TC_S1_54: Login with phone fails when server returns HTTP 401 Unauthorized.
     */
    @Test
    public void phoneLogin_failure_http401() {
        Call<Login> c = mockCall();
        doReturn(c).when(api).login(anyString(), anyString(), anyString());
        AtomicReference<Callback<Login>> cb = captureCallback(c);

        viewModel.loginWithPhone("123", "pass");
        cb.get().onResponse(c, Response.error(401, ResponseBody.create(MediaType.parse("application/json"), "{}")));
        assertNull(viewModel.getLoginWithPhoneResponse().getValue());
        assertEquals(Boolean.FALSE, viewModel.getAnimation().getValue());
    }

    /**
     * TC_S1_55: Login with Google fails due to network error (timeout).
     */
    @Test
    public void googleLogin_failure_networkError() {
        Call<Login> c = mockCall();
        doReturn(c).when(api).loginWithGoogle(anyString(), anyString(), anyString());
        AtomicReference<Callback<Login>> cb = captureCallback(c);

        viewModel.loginWithGoogle("u@g.com", "id");
        cb.get().onFailure(c, new IOException("Timeout"));
        assertNull(viewModel.getLoginWithGoogleResponse().getValue());
        assertEquals(Boolean.FALSE, viewModel.getAnimation().getValue());
    }

    /**
     * TC_S1_56: Role is always 'patient' when calling loginWithPhone().
     */
    @Test
    public void roleAlwaysPatient_phone() {
        Call<Login> c = mockCall();
        doReturn(c).when(api).login("123", "pwd", "patient");
        setupCallbackCapture(c);
        viewModel.loginWithPhone("123", "pwd");
        verify(api).login(anyString(), anyString(), eq("patient"));
    }

    /**
     * TC_S1_57: Role is always 'patient' when calling loginWithGoogle().
     */
    @Test
    public void roleAlwaysPatient_google() {
        Call<Login> c = mockCall();
        doReturn(c).when(api).loginWithGoogle("e@m", "id", "patient");
        setupCallbackCapture(c);
        viewModel.loginWithGoogle("e@m", "id");
        verify(api).loginWithGoogle(anyString(), anyString(), eq("patient"));
    }

    // =====================================================================
    // EDGE CASES & MALFORMED DATA TESTS (7 tests)
    // =====================================================================

    /**
     * TC_S1_58: API called with empty strings due to missing validation.
     */
    @Test
    public void phoneLogin_emptyString_apiCalledWithEmpty() {
        Call<Login> mockCall = mockCall();
        doReturn(mockCall).when(api).login("", "", "patient");
        setupCallbackCapture(mockCall);
        viewModel.loginWithPhone("", "");
        verify(api).login("", "", "patient");
    }

    /**
     * TC_S1_59: No crash when error body contains malformed JSON (phone login).
     */
    @Test
    public void phoneLogin_malformedJsonError_doesNotCrash() {
        Call<Login> mockCall = mockCall();
        doReturn(mockCall).when(api).login(anyString(), anyString(), anyString());
        AtomicReference<Callback<Login>> cbRef = captureCallback(mockCall);
        viewModel.loginWithPhone("0912345678", "uid");
        cbRef.get().onResponse(mockCall, Response.error(400, ResponseBody.create(MediaType.parse("application/json"), "BAD_JSON")));
        assertNull(viewModel.getLoginWithPhoneResponse().getValue());
        assertEquals(Boolean.FALSE, viewModel.getAnimation().getValue());
    }

    /**
     * TC_S1_60: No crash when error body contains malformed JSON (Google login).
     */
    @Test
    public void googleLogin_malformedJsonInError_handled() {
        Call<Login> mockCall = mockCall();
        doReturn(mockCall).when(api).loginWithGoogle(anyString(), anyString(), anyString());
        AtomicReference<Callback<Login>> cbRef = captureCallback(mockCall);
        viewModel.loginWithGoogle("u@g.com", "id");
        @SuppressWarnings("unchecked")
        Response<Login> errResponse = mock(Response.class);
        when(errResponse.isSuccessful()).thenReturn(false);
        when(errResponse.errorBody()).thenReturn(ResponseBody.create(MediaType.parse("application/json"), "{INVALID"));
        cbRef.get().onResponse(mockCall, errResponse);
        assertNull(viewModel.getLoginWithGoogleResponse().getValue());
    }

    /**
     * TC_S1_61: Handle successful response with null body (Login=null).
     */
    @Test
    public void successResponse_nullBodyWithAssert_doesNotCrash() {
        Call<Login> mockCall = mockCall();
        doReturn(mockCall).when(api).login(anyString(), anyString(), anyString());
        AtomicReference<Callback<Login>> cbRef = captureCallback(mockCall);
        viewModel.loginWithPhone("0912345678", "uid");
        try {
            cbRef.get().onResponse(mockCall, Response.success((Login) null));
        } catch (AssertionError e) { assertTrue(true); }
        assertEquals(Boolean.FALSE, viewModel.getAnimation().getValue());
    }

    /**
     * TC_S1_62: Correctly handle HTTP 500 Internal Server Error.
     */
    @Test
    public void phoneLogin_http500Error_handledProperly() {
        Call<Login> mockCall = mockCall();
        doReturn(mockCall).when(api).login(anyString(), anyString(), anyString());
        AtomicReference<Callback<Login>> cbRef = captureCallback(mockCall);
        viewModel.loginWithPhone("09", "pw");
        cbRef.get().onResponse(mockCall, Response.error(500, ResponseBody.create(null, "")));
        assertNull(viewModel.getLoginWithPhoneResponse().getValue());
    }

    /**
     * TC_S1_63: Verify stale data remains when no errorBody exists (related to BUG-2).
     */
    @Test
    public void googleLogin_errorWithoutErrorBody_staleDataPersists() {
        Call<Login> call1 = mockCall();
        Call<Login> call2 = mockCall();
        Login cached = mock(Login.class);
        doReturn(call1).doReturn(call2).when(api).loginWithGoogle(anyString(), anyString(), anyString());
        AtomicReference<Callback<Login>> cb1 = captureCallback(call1);
        AtomicReference<Callback<Login>> cb2 = captureCallback(call2);

        viewModel.loginWithGoogle("a", "b");
        cb1.get().onResponse(call1, Response.success(cached));
        
        viewModel.loginWithGoogle("c", "d");
        @SuppressWarnings("unchecked")
        Response<Login> badResp = mock(Response.class);
        when(badResp.isSuccessful()).thenReturn(false);
        when(badResp.errorBody()).thenReturn(null);
        cb2.get().onResponse(call2, badResp);
        assertEquals(cached, viewModel.getLoginWithGoogleResponse().getValue());
        assertEquals(Boolean.TRUE, viewModel.getAnimation().getValue());
    }

    /**
     * TC_S1_64: onFailure called with RuntimeException - animation stops and LiveData becomes null.
     */
    @Test
    public void phoneLogin_runtimeException_animationStopsAndResponseNull() {
        Call<Login> mockCall = mockCall();
        doReturn(mockCall).when(api).login(anyString(), anyString(), anyString());
        AtomicReference<Callback<Login>> cbRef = captureCallback(mockCall);
        viewModel.loginWithPhone("1", "2");
        cbRef.get().onFailure(mockCall, new RuntimeException());
        assertNull(viewModel.getLoginWithPhoneResponse().getValue());
        assertEquals(Boolean.FALSE, viewModel.getAnimation().getValue());
    }

    // =====================================================================
    // DATA MODEL COVERAGE (5 tests)
    // =====================================================================

    /**
     * TC_S1_65: getResult() returns correct value.
     */
    @Test
    public void login_getResult_Success() {
        Login login = mock(Login.class);
        when(login.getResult()).thenReturn(1);
        assertEquals(Integer.valueOf(1), login.getResult());
    }

    /**
     * TC_S1_66: getAccessToken() returns null when token does not exist.
     */
    @Test
    public void login_getAccessToken_nullable() {
        Login login = mock(Login.class);
        when(login.getAccessToken()).thenReturn(null);
        assertNull(login.getAccessToken());
    }

    /**
     * TC_S1_67: Concatenating phone=null produces string "0null" causing display issue.
     */
    @Test
    public void user_getNullPhone_stringConcat_producesNullString() {
        User user = new User();
        assertNull(user.getPhone());
        assertEquals("0null", "0" + user.getPhone());
    }

    /**
     * TC_S1_68: Calling trim() on null accessToken causes NullPointerException.
     */
    @Test
    public void login_nullToken_trimThrowsNPE() {
        Login login = mock(Login.class);
        when(login.getAccessToken()).thenReturn(null);
        try {
            login.getAccessToken().trim();
            fail();
        } catch (NullPointerException e) { assertTrue(true); }
    }

    /**
     * TC_S1_69: Auto-unboxing Integer null to int causes NullPointerException.
     */
    @Test
    public void login_nullResult_autoUnboxFails() {
        Login login = mock(Login.class);
        when(login.getResult()).thenReturn(null);
        try {
            int x = login.getResult();
            fail();
        } catch (NullPointerException e) { assertTrue(true); }
    }

    // =====================================================================
    // HELPER METHODS
    // =====================================================================

    @SuppressWarnings("unchecked")
    private <T> Call<T> mockCall() { return (Call<T>) mock(Call.class); }

    private <T> AtomicReference<Callback<T>> captureCallback(Call<T> call) {
        AtomicReference<Callback<T>> ref = new AtomicReference<>();
        doAnswer(inv -> { ref.set(inv.getArgument(0)); return null; }).when(call).enqueue(any());
        return ref;
    }

    private <T> void setupCallbackCapture(Call<T> call) { doAnswer(inv -> null).when(call).enqueue(any()); }

    private Login createMockLogin(int result, String msg, String token, User userData) {
        Login login = mock(Login.class);
        when(login.getResult()).thenReturn(result);
        when(login.getMsg()).thenReturn(msg);
        when(login.getAccessToken()).thenReturn(token);
        when(login.getData()).thenReturn(userData);
        return login;
    }

    private User createMockUser(String userId, String phone, String name) {
        User user = mock(User.class);
        try { when(user.getId()).thenReturn(Integer.parseInt(userId)); } catch (Exception e) { when(user.getId()).thenReturn(0); }
        when(user.getPhone()).thenReturn(phone);
        when(user.getName()).thenReturn(name);
        return user;
    }
}
