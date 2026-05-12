## Redesign expected output theo nghiệp vụ (S2 / S5 / S6)

Mục tiêu: **expected output phải căn cứ vào nghiệp vụ & ngữ nghĩa**, không “copy ngược” từ source code.
Nếu expected bám theo code, test sẽ pass 100% dù code sai nghiệp vụ.

### Nguyên tắc chung

- **Không chỉ assert `result`**: với các thao tác ghi (create/update/delete) phải kiểm **DB side-effect**.
- **Không chỉ assert substring mơ hồ**: msg phải phản ánh **lý do nghiệp vụ** (ví dụ “min price 100.000”).
- **Bảo toàn integrity**: kiểm tra không tạo orphan/cascade delete sai (appointments/booking, doctor/service).
- **Phân quyền**: nếu bị từ chối, **DB không được thay đổi** (tránh bug “check quyền sau khi ghi DB”).
- **Boundary**: giờ làm việc 07:00–20:00 phải có test boundary (06:59, 07:00, 20:00, 20:01).
- **Pagination semantic**:
  - `data`: số dòng đúng theo `length`.
  - `quantity`: **tổng số records trước LIMIT** để frontend tính số trang.

### S2 — Doctor Management

- **Update doctor (admin)**: assert response + **assert DB** (name/phone/price/role/update_at…).
- **Validation fail (price < 100k)**: assert msg phản ánh min-price + **DB không đổi**.
- **Delete doctor**:
  - Có appointment → **deactivate** (active=0) + **appointments không bị xóa**.
  - Không có appointment → **hard delete** (row biến mất).
- **Phân quyền update/delete**: nếu AuthUser role != admin → result=0 và **DB không đổi**.

### S5 — Booking Management

- **Admin/Supporter tạo booking**:
  - expected phải kiểm **booking tồn tại DB** + status đúng theo nghiệp vụ luồng admin/supporter.
  - Khuyến nghị kiểm thêm appointment_date/time ghi đúng.
- **Giờ làm việc**:
  - 06:59 → reject, 07:00 → accept, 20:00 → accept, 20:01 → reject.
- **Patient getAll**: data isolation (chỉ trả booking của patient) + **count khớp DB**.
- **Patient cancel**:
  - status=processing → cancelled + update_at cập nhật + **không tạo appointment**.
  - status=verified → **không được tự hủy** + DB status không đổi.
- **Fail “có giá trị” (demo)**: double-booking bác sĩ cùng slot phải bị chặn (nếu API chưa chặn thì testcase này sẽ fail thật).

### S6 — Service / Speciality / Room / Clinic

- Các testcase delete blocked (default id=1, có booking/doctor link):
  - Không chỉ check msg/result, mà cần đảm bảo **DB không đổi** và **không sinh orphan**.
- **Pagination semantic**: `quantity` = total_records (trước LIMIT), không phải page_size.

