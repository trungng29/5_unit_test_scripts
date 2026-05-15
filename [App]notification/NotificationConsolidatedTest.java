package com.example.do_an_tot_nghiep.notification;

import static org.junit.Assert.*;
import static org.mockito.ArgumentMatchers.anyMap;
import static org.mockito.Mockito.*;

import androidx.arch.core.executor.testing.InstantTaskExecutorRule;

import com.example.do_an_tot_nghiep.Configuration.Constant;
import com.example.do_an_tot_nghiep.Configuration.HTTPRequest;
import com.example.do_an_tot_nghiep.Configuration.HTTPService;
import com.example.do_an_tot_nghiep.Container.NotificationReadAll;
import com.example.do_an_tot_nghiep.Notificationpage.NotificationViewModel;
import com.example.do_an_tot_nghiep.Repository.NotificationRepository;

import org.junit.After;
import org.junit.Before;
import org.junit.Rule;
import org.junit.Test;
import org.junit.runner.RunWith;
import org.mockito.Mock;
import org.mockito.MockedStatic;
import org.mockito.Mockito;
import org.mockito.junit.MockitoJUnitRunner;

import java.lang.reflect.Field;
import java.util.HashMap;
import java.util.Map;

import okhttp3.MediaType;
import okhttp3.ResponseBody;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;
import retrofit2.Retrofit;

/**
 * Consolidated Notification tests (JUnit4 + Mockito)
 * - Merges repository & viewmodel tests
 * - Focuses on negative/error branches
 */
@RunWith(MockitoJUnitRunner.class)
public class NotificationConsolidatedTest {

    @Rule
    public InstantTaskExecutorRule instantTaskExecutorRule = new InstantTaskExecutorRule();

    @Mock
    private Retrofit retrofit;

    @Mock
    private HTTPRequest api;

    private MockedStatic<Constant> constantMock;
    private MockedStatic<HTTPService> httpServiceMock;

    @Before
    public void setUp() {
        // Fix: Mock Constant BEFORE HTTPService initialization to prevent NPE from android.os.Build
        constantMock = Mockito.mockStatic(Constant.class);
        constantMock.when(Constant::APP_PATH).thenReturn("http://localhost/");
        constantMock.when(Constant::OPEN_WEATHER_MAP_PATH).thenReturn("http://localhost/");

        httpServiceMock = Mockito.mockStatic(HTTPService.class);
        httpServiceMock.when(HTTPService::getInstance).thenReturn(retrofit);
        doReturn(api).when(retrofit).create(HTTPRequest.class);
    }

    @After
    public void tearDown() {
        if (httpServiceMock != null) httpServiceMock.close();
        if (constantMock != null) constantMock.close();
    }

    // ------------------ Repository negative tests ------------------

    /**
     * TC_S9_J_01: repo_readAll_errorBody_setsNullAndStopsAnimation
     * HTTP 401 -> animation stops, LiveData=null
     */
    @Test
    public void repo_readAll_errorBody_setsNullAndStopsAnimation() {
        NotificationRepository repo = new NotificationRepository();
        Call<NotificationReadAll> mockCall = mock(Call.class);
        ResponseBody errorBody = ResponseBody.create(MediaType.parse("application/json"), "{\"error\":\"unauthorized\"}");
        Response<NotificationReadAll> errorResponse = Response.error(401, errorBody);

        doReturn(mockCall).when(api).notificationReadAll(anyMap());

        doAnswer(inv -> {
            Callback<NotificationReadAll> cb = inv.getArgument(0);
            cb.onResponse(mockCall, errorResponse);
            return null;
        }).when(mockCall).enqueue(any());

        repo.readAll(new HashMap<>());

        assertFalse(Boolean.TRUE.equals(repo.getAnimation().getValue()));
        assertNull(repo.getReadAllResponse().getValue());
    }

    /**
     * TC_S9_J_02: repo_readAll_onFailure_keepsNullAndStopsAnimation
     * onFailure -> animation stops, LiveData=null
     */
    @Test
    public void repo_readAll_onFailure_keepsNullAndStopsAnimation() {
        NotificationRepository repo = new NotificationRepository();
        Call<NotificationReadAll> mockCall = mock(Call.class);
        doReturn(mockCall).when(api).notificationReadAll(anyMap());

        doAnswer(inv -> {
            Callback<NotificationReadAll> cb = inv.getArgument(0);
            cb.onFailure(mockCall, new RuntimeException("connection timeout"));
            return null;
        }).when(mockCall).enqueue(any());

        repo.readAll(new HashMap<>());

        assertFalse(Boolean.TRUE.equals(repo.getAnimation().getValue()));
        assertNull(repo.getReadAllResponse().getValue());
    }

    /**
     * TC_S9_J_03: repo_readAll_successWithNullBody_setsNullAndStopsAnimation
     * HTTP 200 body=null -> LiveData=null
     */
    @Test
    public void repo_readAll_successWithNullBody_setsNullAndStopsAnimation() {
        NotificationRepository repo = new NotificationRepository();
        Call<NotificationReadAll> mockCall = mock(Call.class);
        Response<NotificationReadAll> successNull = Response.success((NotificationReadAll) null);
        doReturn(mockCall).when(api).notificationReadAll(anyMap());

        doAnswer(inv -> {
            Callback<NotificationReadAll> cb = inv.getArgument(0);
            cb.onResponse(mockCall, successNull);
            return null;
        }).when(mockCall).enqueue(any());

        repo.readAll(new HashMap<>());

        assertFalse(Boolean.TRUE.equals(repo.getAnimation().getValue()));
        assertNull(repo.getReadAllResponse().getValue());
    }

    /**
     * TC_S9_J_04: repo_readAll_setsAnimationTrueImmediately
     * readAll() immediately sets animation=true
     */
    @Test
    public void repo_readAll_setsAnimationTrueImmediately() {
        NotificationRepository repo = new NotificationRepository();
        Call<NotificationReadAll> mockCall = mock(Call.class);
        doReturn(mockCall).when(api).notificationReadAll(anyMap());
        doAnswer(inv -> null).when(mockCall).enqueue(any());

        repo.readAll(new HashMap<>());
        assertTrue(Boolean.TRUE.equals(repo.getAnimation().getValue()));
    }

    /**
     * TC_S9_J_05: repo_readAll_malformedErrorBody_doesNotCrash
     * Malformed JSON error body should not crash
     */
    @Test
    public void repo_readAll_malformedErrorBody_doesNotCrash() {
        NotificationRepository repo = new NotificationRepository();
        Call<NotificationReadAll> mockCall = mock(Call.class);
        ResponseBody bad = ResponseBody.create(MediaType.parse("application/json"), "{INVALID_JSON");
        Response<NotificationReadAll> err = Response.error(500, bad);

        doReturn(mockCall).when(api).notificationReadAll(anyMap());
        doAnswer(inv -> {
            Callback<NotificationReadAll> cb = inv.getArgument(0);
            cb.onResponse(mockCall, err);
            return null;
        }).when(mockCall).enqueue(any());

        repo.readAll(new HashMap<>());

        assertFalse(Boolean.TRUE.equals(repo.getAnimation().getValue()));
        assertNull(repo.getReadAllResponse().getValue());
    }

    // ------------------ ViewModel tests ------------------

    /**
     * TC_S9_J_06: vm_instantiate_createsRepositoryAndIdempotent
     * instantiate() creates repo, idempotent
     */
    @Test
    public void vm_instantiate_createsRepositoryAndIdempotent() throws Exception {
        NotificationViewModel vm = new NotificationViewModel();
        // nullify private field "repository"
        Field f = NotificationViewModel.class.getDeclaredField("repository");
        f.setAccessible(true);
        f.set(vm, null);

        vm.instantiate();
        Object repoVal = f.get(vm);
        assertNotNull(repoVal);

        // Call again - should not throw and repository should remain
        vm.instantiate();
        assertNotNull(f.get(vm));
    }

    /**
     * TC_S9_J_07: vm_readAll_delegatesAndBindsLiveData
     * readAll() delegates and binds LiveData
     */
    @Test
    public void vm_readAll_delegatesAndBindsLiveData() {
        NotificationRepository mockRepo = mock(NotificationRepository.class);
        NotificationViewModel vm = new NotificationViewModel();

        // inject mock repo into vm
        try {
            Field f = NotificationViewModel.class.getDeclaredField("repository");
            f.setAccessible(true);
            f.set(vm, mockRepo);
        } catch (Exception e) {
            fail("Reflection setup failed: " + e.getMessage());
        }

        NotificationReadAll container = mock(NotificationReadAll.class);
        androidx.lifecycle.MutableLiveData<NotificationReadAll> repoResp = new androidx.lifecycle.MutableLiveData<>();
        repoResp.setValue(container);
        androidx.lifecycle.MutableLiveData<Boolean> repoAnim = new androidx.lifecycle.MutableLiveData<>();
        repoAnim.setValue(false);

        doReturn(repoAnim).when(mockRepo).getAnimation();
        doReturn(repoResp).when(mockRepo).readAll(anyMap());

        Map<String, String> headers = new HashMap<>();
        vm.readAll(headers);

        assertSame(repoAnim, vm.getAnimation());
        assertSame(repoResp, vm.getReadAllResponse());
        verify(mockRepo).readAll(headers);
    }

}
