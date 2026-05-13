package com.example.do_an_tot_nghiep.Recordpage;

import static org.junit.Assert.assertEquals;
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
import com.example.do_an_tot_nghiep.Container.RecordReadByID;
import com.example.do_an_tot_nghiep.Repository.RecordRepository;
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
 * Unit tests for RecordpageViewModel.
 *
 * Test strategy:
 * - Mock static HTTPService factory so RecordRepository uses mocked Retrofit + HTTPRequest.
 * - Capture Retrofit callback passed to enqueue(), then trigger success/error/failure manually.
 * - Verify ViewModel LiveData outputs (readByIDResponse) and animation state.
 */
public class RecordpageViewModelTest {

    @Rule
    public SynchronousTaskExecutorRule synchronousTaskExecutorRule = new SynchronousTaskExecutorRule();

    private AutoCloseable mocks;
    private Retrofit retrofit;
    private HTTPRequest api;
    private MockedStatic<HTTPService> httpServiceMock;
    private RecordpageViewModel viewModelUnderTest;
    private Map<String, String> headers;

    @Before
    public void setUp() {
        mocks = MockitoAnnotations.openMocks(this);
        viewModelUnderTest = new RecordpageViewModel();
        viewModelUnderTest.instantiate();

        retrofit = mock(Retrofit.class);
        api = mock(HTTPRequest.class);
        when(retrofit.create(HTTPRequest.class)).thenReturn(api);

        httpServiceMock = org.mockito.Mockito.mockStatic(HTTPService.class);
        httpServiceMock.when(HTTPService::getInstance).thenReturn(retrofit);

        headers = new HashMap<>();
        headers.put("Authorization", "Bearer token");
        headers.put("Type", "patient");
    }

    @After
    public void tearDown() throws Exception {
        httpServiceMock.close();
        mocks.close();
    }

    // =============================================================
    // READ BY ID - Happy path
    // =============================================================

    // Test Case ID: TC-VM-REC-001
    // Verify readByID() calls RecordRepository.readByID() with correct appointment ID.
    @Test
    public void readById_validParams() {
        Call<RecordReadByID> mockApiCall = mockCall();
        String appointmentId = "A10";

        when(api.recordReadById(headers, appointmentId)).thenReturn(mockApiCall);
        captureCallback(mockApiCall);

        viewModelUnderTest.readByID(headers, appointmentId);

        verify(api).recordReadById(headers, appointmentId);
    }

    // Test Case ID: TC-VM-REC-002
    // Verify readByID() success updates readByIDResponse LiveData and stops animation.
    @Test
    public void readById_success() {
        Call<RecordReadByID> mockApiCall = mockCall();
        RecordReadByID expectedResponse = mock(RecordReadByID.class);

        when(api.recordReadById(headers, "A10")).thenReturn(mockApiCall);
        AtomicReference<Callback<RecordReadByID>> callbackRef = captureCallback(mockApiCall);

        viewModelUnderTest.readByID(headers, "A10");
        callbackRef.get().onResponse(mockApiCall, Response.success(expectedResponse));

        assertSame(expectedResponse, viewModelUnderTest.getReadByIDResponse().getValue());
        assertEquals(Boolean.FALSE, viewModelUnderTest.getAnimation().getValue());
    }

    // =============================================================
    // READ BY ID - Error / Failure path
    // =============================================================

    // Test Case ID: TC-VM-REC-003
    // Verify readByID() error response sets LiveData to null and stops animation.
    @Test
    public void readById_errorResponse() {
        Call<RecordReadByID> mockApiCall = mockCall();

        when(api.recordReadById(headers, "A10")).thenReturn(mockApiCall);
        AtomicReference<Callback<RecordReadByID>> callbackRef = captureCallback(mockApiCall);

        viewModelUnderTest.readByID(headers, "A10");
        callbackRef.get().onResponse(mockApiCall, errorResponse());

        assertNull(viewModelUnderTest.getReadByIDResponse().getValue());
        assertEquals(Boolean.FALSE, viewModelUnderTest.getAnimation().getValue());
    }

    // Test Case ID: TC-VM-REC-004
    // Verify readByID() failure callback sets LiveData to null and stops animation.
    @Test
    public void readById_failure() {
        Call<RecordReadByID> mockApiCall = mockCall();

        when(api.recordReadById(headers, "A10")).thenReturn(mockApiCall);
        AtomicReference<Callback<RecordReadByID>> callbackRef = captureCallback(mockApiCall);

        viewModelUnderTest.readByID(headers, "A10");
        callbackRef.get().onFailure(mockApiCall, new RuntimeException("no internet"));

        assertNull(viewModelUnderTest.getReadByIDResponse().getValue());
        assertEquals(Boolean.FALSE, viewModelUnderTest.getAnimation().getValue());
    }

    // =============================================================
    // READ BY ID - Bug detection: null headers not validated
    // =============================================================

    // Test Case ID: TC-VM-REC-005
    // Real bug: readByID() accepts null headers and still calls the API without validation.
    // Expected behavior: should reject null headers before making the API call.
    @Test
    public void nullHeaders_notCallApi() {
        Call<RecordReadByID> mockApiCall = mockCall();
        when(api.recordReadById(isNull(), any())).thenReturn(mockApiCall);

        viewModelUnderTest.readByID(null, "A10");

        // Fails: current code does not validate null headers.
        verify(api, never()).recordReadById(isNull(), any());
    }

    // =============================================================
    // ANIMATION state verification
    // =============================================================

    // Test Case ID: TC-VM-REC-006
    // Verify animation is set to true immediately when readByID() is called.
    @Test
    public void readById_animationEnabled() {
        Call<RecordReadByID> mockApiCall = mockCall();
        when(api.recordReadById(headers, "A10")).thenReturn(mockApiCall);
        captureCallback(mockApiCall);

        viewModelUnderTest.readByID(headers, "A10");

        // Animation should be true right after the API call is initiated.
        assertEquals(Boolean.TRUE, viewModelUnderTest.getAnimation().getValue());
    }

    // =============================================================
    // STALE DATA - Second call after success
    // =============================================================

    // Test Case ID: TC-VM-REC-007
    // Real bug: second call after success keeps old data if the second call's response is null.
    // Expected: LiveData should be cleared when second call fails, not keep stale data.
    @Test
    public void readById_staleDataOnFailure() {
        Call<RecordReadByID> successCall = mockCall();
        Call<RecordReadByID> failureCall = mockCall();
        RecordReadByID cachedResponse = mock(RecordReadByID.class);

        when(api.recordReadById(headers, "A10")).thenReturn(successCall).thenReturn(failureCall);
        AtomicReference<Callback<RecordReadByID>> successCallbackRef = captureCallback(successCall);
        AtomicReference<Callback<RecordReadByID>> failureCallbackRef = captureCallback(failureCall);

        // First call succeeds.
        viewModelUnderTest.readByID(headers, "A10");
        successCallbackRef.get().onResponse(successCall, Response.success(cachedResponse));
        assertSame(cachedResponse, viewModelUnderTest.getReadByIDResponse().getValue());

        // Second call fails with network error.
        viewModelUnderTest.readByID(headers, "A10");
        failureCallbackRef.get().onFailure(failureCall, new RuntimeException("offline"));

        // Fails: RecordRepository.onFailure() does NOT emit null for readByIDResponse,
        // so stale data remains in LiveData.
        assertNull(viewModelUnderTest.getReadByIDResponse().getValue());
    }

    // Test Case ID: TC-VM-REC-008
    // Real bug: second call after success with error response (non-2xx) also keeps stale data.
    // Expected: LiveData should be cleared when second call returns error response.
    @Test
    public void readById_staleDataOnError() {
        Call<RecordReadByID> successCall = mockCall();
        Call<RecordReadByID> errorCall = mockCall();
        RecordReadByID cachedResponse = mock(RecordReadByID.class);

        when(api.recordReadById(headers, "A10")).thenReturn(successCall).thenReturn(errorCall);
        AtomicReference<Callback<RecordReadByID>> successCallbackRef = captureCallback(successCall);
        AtomicReference<Callback<RecordReadByID>> errorCallbackRef = captureCallback(errorCall);

        // First call succeeds.
        viewModelUnderTest.readByID(headers, "A10");
        successCallbackRef.get().onResponse(successCall, Response.success(cachedResponse));
        assertSame(cachedResponse, viewModelUnderTest.getReadByIDResponse().getValue());

        // Second call returns HTTP error.
        viewModelUnderTest.readByID(headers, "A10");
        errorCallbackRef.get().onResponse(errorCall, errorResponse());

        // Fails: RecordRepository does not clear LiveData on unsuccessful response.
        assertNull(viewModelUnderTest.getReadByIDResponse().getValue());
    }

    // =============================================================
    // Helper methods
    // =============================================================

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
