package com.example.do_an_tot_nghiep.Appointmentpage;

import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertNotNull;
import static org.junit.Assert.assertNull;
import static org.junit.Assert.assertSame;
import static org.mockito.ArgumentMatchers.any;
import static org.mockito.ArgumentMatchers.isNull;
import static org.mockito.Mockito.doAnswer;
import static org.mockito.Mockito.mock;
import static org.mockito.Mockito.never;
import static org.mockito.Mockito.verify;
import static org.mockito.Mockito.when;

import androidx.lifecycle.MutableLiveData;

import com.example.do_an_tot_nghiep.Configuration.HTTPRequest;
import com.example.do_an_tot_nghiep.Configuration.HTTPService;
import com.example.do_an_tot_nghiep.Container.AppointmentQueue;
import com.example.do_an_tot_nghiep.Container.AppointmentReadAll;
import com.example.do_an_tot_nghiep.Container.AppointmentReadByID;
import com.example.do_an_tot_nghiep.Repository.AppointmentQueueRepository;
import com.example.do_an_tot_nghiep.Repository.AppointmentRepository;
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
 * Unit tests for AppointmentpageViewModel.
 *
 * Test strategy:
 * - Mock static HTTPService factory so repositories use mocked Retrofit + HTTPRequest.
 * - Capture Retrofit callback passed to enqueue(), then trigger success/error/failure manually.
 * - Verify ViewModel LiveData outputs and animation state.
 */
public class AppointmentpageViewModelTest {

    @Rule
    public SynchronousTaskExecutorRule synchronousTaskExecutorRule = new SynchronousTaskExecutorRule();

    private AutoCloseable mocks;
    private Retrofit retrofit;
    private HTTPRequest api;
    private MockedStatic<HTTPService> httpServiceMock;
    private AppointmentpageViewModel viewModelUnderTest;
    private Map<String, String> headers;
    private Map<String, String> params;

    @Before
    public void setUp() {
        mocks = MockitoAnnotations.openMocks(this);
        viewModelUnderTest = new AppointmentpageViewModel();
        viewModelUnderTest.instantiate();

        retrofit = mock(Retrofit.class);
        api = mock(HTTPRequest.class);
        when(retrofit.create(HTTPRequest.class)).thenReturn(api);

        httpServiceMock = org.mockito.Mockito.mockStatic(HTTPService.class);
        httpServiceMock.when(HTTPService::getInstance).thenReturn(retrofit);

        headers = new HashMap<>();
        headers.put("Authorization", "Bearer token");
        headers.put("Type", "patient");

        params = new HashMap<>();
        params.put("date", "2026-05-11");
        params.put("page", "1");
        params.put("limit", "10");
    }

    @After
    public void tearDown() throws Exception {
        httpServiceMock.close();
        mocks.close();
    }

    // =============================================================
    // READ ALL - Happy path
    // =============================================================

    // Test Case ID: TC-VM-APPOINT-001
    // Verify readAll() calls AppointmentRepository.readAll() with correct parameters.
    @Test
    public void readAll_validParams() {
        Call<AppointmentReadAll> mockApiCall = mockCall();
        when(api.appointmentReadAll(headers, params)).thenReturn(mockApiCall);
        captureCallback(mockApiCall);

        viewModelUnderTest.readAll(headers, params);

        verify(api).appointmentReadAll(headers, params);
    }

    // Test Case ID: TC-VM-APPOINT-002
    // Verify readAll() success updates readAllResponse LiveData and stops animation.
    @Test
    public void readAll_success() {
        Call<AppointmentReadAll> mockApiCall = mockCall();
        AppointmentReadAll expectedResponse = mock(AppointmentReadAll.class);

        when(api.appointmentReadAll(headers, params)).thenReturn(mockApiCall);
        AtomicReference<Callback<AppointmentReadAll>> callbackRef = captureCallback(mockApiCall);

        viewModelUnderTest.readAll(headers, params);
        callbackRef.get().onResponse(mockApiCall, Response.success(expectedResponse));

        assertSame(expectedResponse, viewModelUnderTest.getReadAllResponse().getValue());
        assertEquals(Boolean.FALSE, viewModelUnderTest.getAnimation().getValue());
    }

    // =============================================================
    // READ ALL - Error / Failure path
    // =============================================================

    // Test Case ID: TC-VM-APPOINT-003
    // Verify readAll() error response sets LiveData to null and stops animation.
    @Test
    public void readAll_errorResponse() {
        Call<AppointmentReadAll> mockApiCall = mockCall();

        when(api.appointmentReadAll(headers, params)).thenReturn(mockApiCall);
        AtomicReference<Callback<AppointmentReadAll>> callbackRef = captureCallback(mockApiCall);

        viewModelUnderTest.readAll(headers, params);
        callbackRef.get().onResponse(mockApiCall, errorResponse());

        assertNull(viewModelUnderTest.getReadAllResponse().getValue());
        assertEquals(Boolean.FALSE, viewModelUnderTest.getAnimation().getValue());
    }

    // Test Case ID: TC-VM-APPOINT-004
    // Verify readAll() failure callback sets LiveData to null and stops animation.
    @Test
    public void readAll_failure() {
        Call<AppointmentReadAll> mockApiCall = mockCall();

        when(api.appointmentReadAll(headers, params)).thenReturn(mockApiCall);
        AtomicReference<Callback<AppointmentReadAll>> callbackRef = captureCallback(mockApiCall);

        viewModelUnderTest.readAll(headers, params);
        callbackRef.get().onFailure(mockApiCall, new RuntimeException("network unavailable"));

        assertNull(viewModelUnderTest.getReadAllResponse().getValue());
        assertEquals(Boolean.FALSE, viewModelUnderTest.getAnimation().getValue());
    }

    // =============================================================
    // READ ALL - Bug detection: null headers not validated
    // =============================================================

    // Test Case ID: TC-VM-APPOINT-005
    // Real bug: readAll() accepts null headers and still calls the API without validation.
    // Expected behavior: should reject null headers before making the API call.
    @Test
    public void nullHeaders_notCallApi() {
        Call<AppointmentReadAll> mockApiCall = mockCall();
        when(api.appointmentReadAll(isNull(), any())).thenReturn(mockApiCall);

        viewModelUnderTest.readAll(null, params);

        // Fails: current code does not validate null headers.
        verify(api, never()).appointmentReadAll(isNull(), any());
    }

    // =============================================================
    // READ BY ID - Happy path
    // =============================================================

    // Test Case ID: TC-VM-APPOINT-006
    // Verify readByID() calls AppointmentRepository.readByID() with correct appointment ID.
    @Test
    public void readById_validParams() {
        Call<AppointmentReadByID> mockApiCall = mockCall();
        String appointmentId = "A99";

        when(api.appointmentReadByID(headers, appointmentId)).thenReturn(mockApiCall);
        captureCallback(mockApiCall);

        viewModelUnderTest.readByID(headers, appointmentId);

        verify(api).appointmentReadByID(headers, appointmentId);
    }

    // Test Case ID: TC-VM-APPOINT-007
    // Verify readByID() success updates readByIDResponse LiveData.
    @Test
    public void readById_success() {
        Call<AppointmentReadByID> mockApiCall = mockCall();
        AppointmentReadByID expectedResponse = mock(AppointmentReadByID.class);

        when(api.appointmentReadByID(headers, "A99")).thenReturn(mockApiCall);
        AtomicReference<Callback<AppointmentReadByID>> callbackRef = captureCallback(mockApiCall);

        viewModelUnderTest.readByID(headers, "A99");
        callbackRef.get().onResponse(mockApiCall, Response.success(expectedResponse));

        assertSame(expectedResponse, viewModelUnderTest.getReadByIDResponse().getValue());
    }

    // =============================================================
    // READ BY ID - Error / Failure path
    // =============================================================

    // Test Case ID: TC-VM-APPOINT-008
    // Verify readByID() error response sets LiveData to null.
    @Test
    public void readById_error() {
        Call<AppointmentReadByID> mockApiCall = mockCall();

        when(api.appointmentReadByID(headers, "A99")).thenReturn(mockApiCall);
        AtomicReference<Callback<AppointmentReadByID>> callbackRef = captureCallback(mockApiCall);

        viewModelUnderTest.readByID(headers, "A99");
        callbackRef.get().onResponse(mockApiCall, errorResponse());

        assertNull(viewModelUnderTest.getReadByIDResponse().getValue());
    }

    // Test Case ID: TC-VM-APPOINT-009
    // Verify readByID() failure callback sets LiveData to null.
    @Test
    public void readById_failure() {
        Call<AppointmentReadByID> mockApiCall = mockCall();

        when(api.appointmentReadByID(headers, "A99")).thenReturn(mockApiCall);
        AtomicReference<Callback<AppointmentReadByID>> callbackRef = captureCallback(mockApiCall);

        viewModelUnderTest.readByID(headers, "A99");
        callbackRef.get().onFailure(mockApiCall, new RuntimeException("timeout"));

        assertNull(viewModelUnderTest.getReadByIDResponse().getValue());
    }

    // =============================================================
    // GET QUEUE - Happy path
    // =============================================================

    // Test Case ID: TC-VM-APPOINT-010
    // Verify getQueue() calls AppointmentQueueRepository.getAppointmentQueue() with correct parameters.
    @Test
    public void getQueue_validParams() {
        Call<AppointmentQueue> mockApiCall = mockCall();
        Map<String, String> queueParams = new HashMap<>();
        queueParams.put("doctor_id", "D5");
        queueParams.put("date", "2026-05-11");
        queueParams.put("status", "processing");

        when(api.appointmentQueue(headers, queueParams)).thenReturn(mockApiCall);
        captureCallback(mockApiCall);

        viewModelUnderTest.getQueue(headers, queueParams);

        verify(api).appointmentQueue(headers, queueParams);
    }

    // Test Case ID: TC-VM-APPOINT-011
    // Verify getQueue() success updates appointmentQueueResponse LiveData.
    @Test
    public void getQueue_successWithData() {
        Call<AppointmentQueue> mockApiCall = mockCall();
        AppointmentQueue expectedResponse = mock(AppointmentQueue.class);

        when(api.appointmentQueue(headers, params)).thenReturn(mockApiCall);
        AtomicReference<Callback<AppointmentQueue>> callbackRef = captureCallback(mockApiCall);

        viewModelUnderTest.getQueue(headers, params);
        callbackRef.get().onResponse(mockApiCall, Response.success(expectedResponse));

        assertSame(expectedResponse, viewModelUnderTest.getAppointmentQueueResponse().getValue());
    }

    // =============================================================
    // GET QUEUE - Error / Failure path
    // =============================================================

    // Test Case ID: TC-VM-APPOINT-012
    // Verify getQueue() error response sets LiveData to null.
    @Test
    public void getQueue_error() {
        Call<AppointmentQueue> mockApiCall = mockCall();

        when(api.appointmentQueue(headers, params)).thenReturn(mockApiCall);
        AtomicReference<Callback<AppointmentQueue>> callbackRef = captureCallback(mockApiCall);

        viewModelUnderTest.getQueue(headers, params);
        callbackRef.get().onResponse(mockApiCall, errorResponse());

        assertNull(viewModelUnderTest.getAppointmentQueueResponse().getValue());
    }

    // Test Case ID: TC-VM-APPOINT-013
    // Verify getQueue() failure callback sets LiveData to null.
    @Test
    public void getQueue_failure() {
        Call<AppointmentQueue> mockApiCall = mockCall();

        when(api.appointmentQueue(headers, params)).thenReturn(mockApiCall);
        AtomicReference<Callback<AppointmentQueue>> callbackRef = captureCallback(mockApiCall);

        viewModelUnderTest.getQueue(headers, params);
        callbackRef.get().onFailure(mockApiCall, new RuntimeException("offline"));

        assertNull(viewModelUnderTest.getAppointmentQueueResponse().getValue());
    }

    // =============================================================
    // ANIMATION state verification
    // =============================================================

    // Test Case ID: TC-VM-APPOINT-014
    // Verify animation is set to true immediately when readAll() is called.
    @Test
    public void readAll_animationEnabled() {
        Call<AppointmentReadAll> mockApiCall = mockCall();
        when(api.appointmentReadAll(headers, params)).thenReturn(mockApiCall);
        captureCallback(mockApiCall);

        viewModelUnderTest.readAll(headers, params);

        // Animation should be true right after call (before response).
        // This test checks the repository layer sets animation=true.
        assertEquals(Boolean.TRUE, viewModelUnderTest.getAnimation().getValue());
    }

    // Test Case ID: TC-VM-APPOINT-015
    // Verify animation is set to true immediately when getQueue() is called.
    @Test
    public void getQueue_animationEnabled() {
        Call<AppointmentQueue> mockApiCall = mockCall();
        when(api.appointmentQueue(headers, params)).thenReturn(mockApiCall);
        captureCallback(mockApiCall);

        viewModelUnderTest.getQueue(headers, params);

        assertEquals(Boolean.TRUE, viewModelUnderTest.getAnimation().getValue());
    }

    // Test Case ID: TC-VM-APPOINT-016
    // Edge case: readAll() returns null data (API success but empty payload).
    // ViewModel should not post null into readAllResponse (guards against null).
    @Test
    public void readAll_nullData() {
        Call<AppointmentReadAll> mockApiCall = mockCall();
        when(api.appointmentReadAll(headers, params)).thenReturn(mockApiCall);
        AtomicReference<Callback<AppointmentReadAll>> callbackRef = captureCallback(mockApiCall);

        viewModelUnderTest.readAll(headers, params);
        // Simulate API success with null body — repository sets LiveData to null.
        callbackRef.get().onResponse(mockApiCall, Response.success((AppointmentReadAll) null));

        assertNull(viewModelUnderTest.getReadAllResponse().getValue());
    }

    // Test Case ID: TC-VM-APPOINT-017
    // Edge case: getQueue() returns null data (API success but empty payload).
    // ViewModel should not post null into appointmentQueueResponse (guards against null).
    @Test
    public void getQueue_nullData() {
        Call<AppointmentQueue> mockApiCall = mockCall();
        when(api.appointmentQueue(headers, params)).thenReturn(mockApiCall);
        AtomicReference<Callback<AppointmentQueue>> callbackRef = captureCallback(mockApiCall);

        viewModelUnderTest.getQueue(headers, params);
        // Simulate API success with null body — repository sets LiveData to null.
        callbackRef.get().onResponse(mockApiCall, Response.success((AppointmentQueue) null));

        assertNull(viewModelUnderTest.getAppointmentQueueResponse().getValue());
    }

    // Captures callback instance passed into Retrofit enqueue() so tests can trigger it manually.
    private <T> AtomicReference<Callback<T>> captureCallback(Call<T> call) {
        AtomicReference<Callback<T>> callbackRef = new AtomicReference<>();
        doAnswer(invocation -> {
            callbackRef.set(invocation.getArgument(0));
            return null;
        }).when(call).enqueue(any());
        return callbackRef;
    }

    @SuppressWarnings("unchecked")
    private <T> Call<T> mockCall() {
        return (Call<T>) mock(Call.class);
    }

    // Creates a generic HTTP 400 Retrofit response for error-path testing.
    private <T> Response<T> errorResponse() {
        return Response.error(
                400,
                okhttp3.ResponseBody.create(
                        okhttp3.MediaType.parse("application/json"),
                        "{\"message\":\"error\"}"
                )
        );
    }
}
