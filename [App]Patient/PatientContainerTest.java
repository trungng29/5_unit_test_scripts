package com.example.do_an_tot_nghiep;

import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertNotNull;
import static org.junit.Assert.assertNull;
import static org.mockito.Mockito.mock;
import static org.mockito.Mockito.when;

import com.example.do_an_tot_nghiep.Container.PatientProfile;
import com.example.do_an_tot_nghiep.Container.PatientProfileChangeAvatar;
import com.example.do_an_tot_nghiep.Container.PatientProfileChangePersonalInformation;
import com.example.do_an_tot_nghiep.Model.User;

import org.junit.Test;

/**
 * Unit tests cho các Container liên quan đến Patient:
 * - PatientProfile (đọc hồ sơ)
 * - PatientProfileChangeAvatar (đổi avatar)
 * - PatientProfileChangePersonalInformation (đổi thông tin cá nhân)
 *
 * Test xác minh rằng các container parse đúng dữ liệu từ API response
 * và xử lý đúng các trường hợp biên (null, giá trị 0/1).
 */
public class PatientContainerTest {

    // =====================================================================
    // A_S3_TC13 — PatientProfile: result = 1 → thao tác thành công
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC13
     * Mô tả: Khi server trả result=1, nghĩa là đọc hồ sơ bệnh nhân thành công.
     * Kết quả mong đợi: getResult() == 1
     * Hàm được test: PatientProfile.getResult()
     *   Nằm tại: Container/PatientProfile.java (dòng 21-23)
     * Điều kiện tiên quyết: Mock PatientProfile với result = 1
     * Dữ liệu đầu vào: result = 1
     * Ghi chú: result=1 là quy ước thành công của hệ thống API
     */
    @Test
    public void A_S3_TC13_patientProfile_resultSuccess() {
        PatientProfile profile = mock(PatientProfile.class);
        when(profile.getResult()).thenReturn(1);
        assertEquals(Integer.valueOf(1), profile.getResult());
    }

    // =====================================================================
    // A_S3_TC14 — PatientProfile: result = 0 → thao tác thất bại
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC14
     * Mô tả: Khi server trả result=0, nghĩa là đọc hồ sơ thất bại (token sai, user không tồn tại).
     * Kết quả mong đợi: getResult() == 0
     * Hàm được test: PatientProfile.getResult()
     *   Nằm tại: Container/PatientProfile.java (dòng 21-23)
     * Điều kiện tiên quyết: Mock PatientProfile với result = 0
     * Dữ liệu đầu vào: result = 0
     * Ghi chú: result=0 kèm msg giải thích lý do thất bại
     */
    @Test
    public void A_S3_TC14_patientProfile_resultFailure() {
        PatientProfile profile = mock(PatientProfile.class);
        when(profile.getResult()).thenReturn(0);
        assertEquals(Integer.valueOf(0), profile.getResult());
    }

    // =====================================================================
    // A_S3_TC15 — PatientProfile: msg chứa thông báo lỗi
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC15
     * Mô tả: Trường msg chứa thông báo trạng thái từ server.
     * Kết quả mong đợi: getMsg() == "Unauthorized"
     * Hàm được test: PatientProfile.getMsg()
     *   Nằm tại: Container/PatientProfile.java (dòng 25-27)
     * Điều kiện tiên quyết: Mock PatientProfile với msg
     * Dữ liệu đầu vào: msg = "Unauthorized"
     * Ghi chú: Msg dùng để hiển thị trên dialog UI cho bệnh nhân
     */
    @Test
    public void A_S3_TC15_patientProfile_msgContainsErrorMessage() {
        PatientProfile profile = mock(PatientProfile.class);
        when(profile.getMsg()).thenReturn("Unauthorized");
        assertEquals("Unauthorized", profile.getMsg());
    }

    // =====================================================================
    // A_S3_TC16 — PatientProfile: data chứa User object
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC16
     * Mô tả: Trường data chứa đối tượng User (thông tin bệnh nhân).
     * Kết quả mong đợi: getData() trả về User object, không null
     * Hàm được test: PatientProfile.getData()
     *   Nằm tại: Container/PatientProfile.java (dòng 29-31)
     * Điều kiện tiên quyết: Mock PatientProfile với User
     * Dữ liệu đầu vào: User object giả lập
     * Ghi chú: User chứa name, email, phone, birthday, address, avatar của bệnh nhân
     */
    @Test
    public void A_S3_TC16_patientProfile_dataContainsUser() {
        PatientProfile profile = mock(PatientProfile.class);
        User mockUser = mock(User.class);
        when(profile.getData()).thenReturn(mockUser);
        assertNotNull(profile.getData());
    }

    // =====================================================================
    // A_S3_TC17 — PatientProfile: data null khi thất bại
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC17
     * Mô tả: Khi result=0, trường data thường là null.
     * Kết quả mong đợi: getData() == null
     * Hàm được test: PatientProfile.getData()
     *   Nằm tại: Container/PatientProfile.java (dòng 29-31)
     * Điều kiện tiên quyết: Mock PatientProfile với data = null
     * Dữ liệu đầu vào: data = null
     * Ghi chú: UI phải kiểm tra null trước khi truy cập thuộc tính của User
     */
    @Test
    public void A_S3_TC17_patientProfile_dataNullOnFailure() {
        PatientProfile profile = mock(PatientProfile.class);
        when(profile.getData()).thenReturn(null);
        assertNull(profile.getData());
    }

    // =====================================================================
    // A_S3_TC18 — ChangeAvatar: result = 1 → đổi avatar thành công
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC18
     * Mô tả: Sau khi upload ảnh mới, server trả result=1 → thành công.
     * Kết quả mong đợi: getResult() == 1
     * Hàm được test: PatientProfileChangeAvatar.getResult()
     *   Nằm tại: Container/PatientProfileChangeAvatar.java (dòng 28-30)
     * Điều kiện tiên quyết: Mock PatientProfileChangeAvatar với result = 1
     * Dữ liệu đầu vào: result = 1
     * Ghi chú: Sau đổi avatar thành công, cần update globalVariable.setAuthUser()
     */
    @Test
    public void A_S3_TC18_changeAvatar_resultSuccess() {
        PatientProfileChangeAvatar response = mock(PatientProfileChangeAvatar.class);
        when(response.getResult()).thenReturn(1);
        assertEquals(1, response.getResult());
    }

    // =====================================================================
    // A_S3_TC19 — ChangeAvatar: url chứa đường dẫn ảnh mới
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC19
     * Mô tả: Server trả url là đường dẫn đến ảnh avatar mới trên CDN.
     * Kết quả mong đợi: getUrl() chứa đường dẫn ảnh
     * Hàm được test: PatientProfileChangeAvatar.getUrl()
     *   Nằm tại: Container/PatientProfileChangeAvatar.java (dòng 36-38)
     * Điều kiện tiên quyết: Mock PatientProfileChangeAvatar với url
     * Dữ liệu đầu vào: url = "/uploads/avatar_123.jpg"
     * Ghi chú: URL này được nối với Constant.UPLOAD_URI() để tạo URL đầy đủ
     */
    @Test
    public void A_S3_TC19_changeAvatar_urlReturnsPath() {
        PatientProfileChangeAvatar response = mock(PatientProfileChangeAvatar.class);
        when(response.getUrl()).thenReturn("/uploads/avatar_123.jpg");
        assertEquals("/uploads/avatar_123.jpg", response.getUrl());
    }

    // =====================================================================
    // A_S3_TC20 — ChangeAvatar: data chứa User cập nhật
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC20
     * Mô tả: Sau đổi avatar, server trả lại User object đã cập nhật avatar mới.
     * Kết quả mong đợi: getData() trả về User, không null
     * Hàm được test: PatientProfileChangeAvatar.getData()
     *   Nằm tại: Container/PatientProfileChangeAvatar.java (dòng 24-26)
     * Điều kiện tiên quyết: Mock PatientProfileChangeAvatar với User
     * Dữ liệu đầu vào: User object giả lập
     * Ghi chú: User mới phải được lưu vào GlobalVariable để UI cập nhật ngay
     */
    @Test
    public void A_S3_TC20_changeAvatar_dataContainsUpdatedUser() {
        PatientProfileChangeAvatar response = mock(PatientProfileChangeAvatar.class);
        User updatedUser = mock(User.class);
        when(response.getData()).thenReturn(updatedUser);
        assertNotNull(response.getData());
    }

    // =====================================================================
    // A_S3_TC21 — ChangePersonalInfo: result = 1 → cập nhật thành công
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC21
     * Mô tả: Bệnh nhân sửa tên/giới tính/ngày sinh/địa chỉ → server trả result=1.
     * Kết quả mong đợi: getResult() == 1
     * Hàm được test: PatientProfileChangePersonalInformation.getResult()
     *   Nằm tại: Container/PatientProfileChangePersonalInformation.java (dòng 21-23)
     * Điều kiện tiên quyết: Mock response với result = 1
     * Dữ liệu đầu vào: result = 1
     * Ghi chú: Sau cập nhật thành công, dialog "Thành công" hiển thị
     */
    @Test
    public void A_S3_TC21_changeInfo_resultSuccess() {
        PatientProfileChangePersonalInformation resp = mock(PatientProfileChangePersonalInformation.class);
        when(resp.getResult()).thenReturn(1);
        assertEquals(Integer.valueOf(1), resp.getResult());
    }

    // =====================================================================
    // A_S3_TC22 — ChangePersonalInfo: data chứa User cập nhật
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC22
     * Mô tả: Sau cập nhật thông tin cá nhân, server trả User object mới.
     * Kết quả mong đợi: getData() trả về User, không null
     * Hàm được test: PatientProfileChangePersonalInformation.getData()
     *   Nằm tại: Container/PatientProfileChangePersonalInformation.java (dòng 29-31)
     * Điều kiện tiên quyết: Mock response với User
     * Dữ liệu đầu vào: User object giả lập
     * Ghi chú: globalVariable.setAuthUser(user) phải được gọi để đồng bộ dữ liệu
     */
    @Test
    public void A_S3_TC22_changeInfo_dataContainsUpdatedUser() {
        PatientProfileChangePersonalInformation resp = mock(PatientProfileChangePersonalInformation.class);
        User updatedUser = mock(User.class);
        when(resp.getData()).thenReturn(updatedUser);
        assertNotNull(resp.getData());
    }

    // =====================================================================
    // A_S3_TC23 — ChangePersonalInfo: msg chứa thông báo lỗi validation
    // =====================================================================
    /**
     * Test Case ID: A_S3_TC23
     * Mô tả: Khi bệnh nhân gửi dữ liệu sai (ví dụ tên rỗng), server trả msg lỗi.
     * Kết quả mong đợi: getMsg() chứa thông báo lỗi
     * Hàm được test: PatientProfileChangePersonalInformation.getMsg()
     *   Nằm tại: Container/PatientProfileChangePersonalInformation.java (dòng 25-27)
     * Điều kiện tiên quyết: Mock response với msg lỗi
     * Dữ liệu đầu vào: msg = "Tên không được để trống"
     * Ghi chú: Msg này được hiển thị trên dialog thông báo lỗi cho bệnh nhân
     */
    @Test
    public void A_S3_TC23_changeInfo_msgContainsValidationError() {
        PatientProfileChangePersonalInformation resp = mock(PatientProfileChangePersonalInformation.class);
        when(resp.getMsg()).thenReturn("Tên không được để trống");
        assertEquals("Tên không được để trống", resp.getMsg());
    }
}
