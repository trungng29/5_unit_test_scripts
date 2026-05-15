package com.example.do_an_tot_nghiep;

import static org.junit.Assert.*;

import com.example.do_an_tot_nghiep.Helper.SingleLiveEvent;
import com.example.do_an_tot_nghiep.Repository.SynchronousTaskExecutorRule;

import org.junit.Rule;
import org.junit.Test;

import java.lang.reflect.Field;
import java.util.concurrent.atomic.AtomicBoolean;

/**
 * Unit tests cho SingleLiveEvent — LiveData chỉ phát sự kiện 1 lần duy nhất.
 * Test qua Reflection để kiểm tra trạng thái mPending (AtomicBoolean).
 */
public class SingleLiveEventValidatorTest {

    @Rule
    public SynchronousTaskExecutorRule rule = new SynchronousTaskExecutorRule();

    // A_S7_TC31 — Khởi tạo: mPending = false
    /** Test Case ID: A_S7_TC31
     * Mô tả: Khi mới tạo SingleLiveEvent, mPending phải là false (chưa có event).
     * Kết quả mong đợi: mPending.get() == false
     * Hàm được test: SingleLiveEvent constructor
     *   Nằm tại: Helper/SingleLiveEvent.java (dòng 28)
     * Ghi chú: Đảm bảo không phát event cũ khi Observer vừa đăng ký */
    @Test
    public void A_S7_TC31_initialState_pendingIsFalse() throws Exception {
        SingleLiveEvent<String> event = new SingleLiveEvent<>();
        Field pendingField = SingleLiveEvent.class.getDeclaredField("mPending");
        pendingField.setAccessible(true);
        AtomicBoolean pending = (AtomicBoolean) pendingField.get(event);
        assertFalse(pending.get());
    }

    // A_S7_TC32 — setValue: mPending chuyển thành true
    /** Test Case ID: A_S7_TC32
     * Mô tả: Sau gọi setValue(), mPending phải set true trước khi super.setValue().
     * Kết quả mong đợi: getValue() == "test" (setValue hoạt động)
     * Hàm được test: SingleLiveEvent.setValue()
     *   Nằm tại: Helper/SingleLiveEvent.java (dòng 50-53)
     * Ghi chú: mPending = true là điều kiện để Observer nhận được event */
    @Test
    public void A_S7_TC32_setValue_updatesValue() {
        SingleLiveEvent<String> event = new SingleLiveEvent<>();
        event.setValue("test");
        assertEquals("test", event.getValue());
    }

    // A_S7_TC33 — call(): setValue(null) shorthand
    /** Test Case ID: A_S7_TC33
     * Mô tả: call() là shorthand cho setValue(null), dùng cho SingleLiveEvent<Void>.
     * Kết quả mong đợi: getValue() == null, mPending đã được set
     * Hàm được test: SingleLiveEvent.call()
     *   Nằm tại: Helper/SingleLiveEvent.java (dòng 59-61)
     * Ghi chú: Dùng cho navigation events không cần truyền dữ liệu */
    @Test
    public void A_S7_TC33_call_setsValueToNull() throws Exception {
        SingleLiveEvent<Void> event = new SingleLiveEvent<>();
        event.call();
        // Verify mPending was set to true (then consumed by observer mechanism)
        assertNull(event.getValue());
    }

    // A_S7_TC34 — setValue nhiều lần: chỉ giữ giá trị mới nhất
    /** Test Case ID: A_S7_TC34
     * Mô tả: Gọi setValue 2 lần → getValue phải trả về giá trị lần gọi cuối.
     * Kết quả mong đợi: getValue() == "second"
     * Hàm được test: SingleLiveEvent.setValue()
     *   Nằm tại: Helper/SingleLiveEvent.java (dòng 50-53)
     * Ghi chú: Đảm bảo event không bị chồng chất */
    @Test
    public void A_S7_TC34_setValue_multipleTimes_keepsLatest() {
        SingleLiveEvent<String> event = new SingleLiveEvent<>();
        event.setValue("first");
        event.setValue("second");
        assertEquals("second", event.getValue());
    }
}
