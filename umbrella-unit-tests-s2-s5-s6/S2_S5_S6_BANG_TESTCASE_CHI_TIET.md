# Bảng đặc tả test case S2, S5, S6

Cột **Pass/Fail** và **Notes** (khi Fail) lấy từ `junit.xml` sau khi chạy PHPUnit.

```bash
php vendor/bin/phpunit --log-junit junit.xml
php tools/build-testcase-detail-md.php junit.xml
```

| Test Case ID | File/Class | Function Name | Test Objective | Precondition | Input | Expected Output | Pass/Fail | Notes |
|---|---|---|---|---|---|---|---|---|
| TC_S2_01 | DoctorController.php | getById() | Lấy doctor tồn tại | Admin | GET Route.params.id=2; AuthUser=admin (doctor id=1) | result=1; data.name = "Nguyen Van Hai" | Pass |  |
| TC_S2_02 | DoctorController.php | getById() | Thiếu id | Admin | GET không set Route.params.id; AuthUser=admin | result=0; msg chứa "required" | Pass |  |
| TC_S2_03 | DoctorController.php | getById() | Id không tồn tại | Admin | GET id=99999; AuthUser=admin | result=0 | Pass |  |
| TC_S2_04 | DoctorController.php | update() | Admin cập nhật doctor hợp lệ | Admin | PUT Route.params.id=2; _PUT: phone, name, role, price, description, active, speciality_id, room_id; AuthUser=admin | result=1; DB doctor id=2 đổi name/phone/price/role; update_at có giá trị | Pass |  |
| TC_S2_05 | DoctorController.php | update() | Member không được cập nhật doctor | Member | PUT id=2 + body hợp lệ; AuthUser=member (id=2) | result=0; msg chứa "not admin" (không phân quyền đúng nếu result=1) | Pass |  |
| TC_S2_06 | DoctorController.php | update() | Tên doctor không hợp lệ | Admin | PUT id=2; name="TenSai!@#"; các field khác hợp lệ | result=0; msg gợi ý chỉ chữ cái (letters) | Pass |  |
| TC_S2_07 | DoctorController.php | update() | Số điện thoại không hợp lệ | Admin | PUT id=2; phone="abcxyz" | result=0; msg nhắc phone | Pass |  |
| TC_S2_08 | DoctorController.php | update() | Role không thuộc whitelist | Admin | PUT id=2; role="superuser" | result=0 | Pass |  |
| TC_S2_09 | DoctorController.php | update() | Giá dưới mức tối thiểu | Admin | PUT id=2; price=50000 | result=0; msg nói tối thiểu ~100.000; DB price doctor id=2 không đổi (150000) | Pass |  |
| TC_S2_10 | DoctorController.php | delete() | Doctor có lịch: chỉ deactivate | Admin; DB có doctor id=3 + appointment processing | DELETE id=3; AuthUser=admin | result=1; type="deactivated"; doctor id=3 active=0; appointment vẫn còn | Pass |  |
| TC_S2_11 | DoctorController.php | delete() | Doctor không lịch: xóa cứng | Admin; DB thêm doctor id=4 không appointment | DELETE id=4 | result=1; type="delete"; row id=4 không còn trong tn_doctors | Pass |  |
| TC_S2_12 | DoctorController.php | delete() | Không cho admin xóa chính mình | Admin (id=1) | DELETE id=1 | result=0; msg chứa "yourself" | Pass |  |
| TC_S2_13 | DoctorController.php | delete() | Doctor đã inactive | Admin; doctor id=5 active=0 | DELETE id=5 | result=0; msg chứa "deactivated"; active vẫn 0 | Pass |  |
| TC_S2_14 | DoctorController.php | getById() | Payload getById có speciality/room/price | Admin | GET id=2 | result=1; data có speciality, room; price=150000 | Pass |  |
| TC_S2_15 | DoctorController.php | update() | Thiếu phone | Admin | PUT id=2; bỏ field phone trong _PUT | result=0; msg nhắc phone | Pass |  |
| TC_S2_16 | DoctorController.php | delete() | Xóa cứng có thông báo deleted | Admin; doctor id=6 không appointment | DELETE id=6 | result=1; msg chứa "deleted" | Pass |  |
| TC_S2_17 | DoctorController.php | getById() | Đúng tên seed doctor id=2 | Admin | GET id=2 | result=1; data.name = "Nguyen Van Hai" | Pass |  |
| TC_S2_18 | DoctorController.php | getById() | Đúng giá seed | Admin | GET id=2 | result=1; data.price = 150000 | Pass |  |
| TC_S2_19 | DoctorController.php | delete() | Member không được xóa doctor | Member | DELETE id=2; AuthUser=member | result=0; msg "not admin" | Pass |  |
| TC_S2_20 | DoctorController.php | delete() | Thiếu id route | Admin | DELETE không có Route.params.id | result=0; msg chứa "required" | Pass |  |
| TC_S2_21 | DoctorController.php | delete() | Doctor không tồn tại | Admin | DELETE id=88888 | result=0; msg chứa "not available" | Pass |  |
| TC_S2_22 | DoctorController.php | update() | Thiếu id route khi update | Admin | PUT không có Route.params.id | result=0; msg "required" | Pass |  |
| TC_S2_23 | DoctorController.php | update() | Update doctor id không tồn tại | Admin | PUT id=99999 + body hợp lệ | result=0; msg "not available" | Pass |  |
| TC_S2_24 | DoctorController.php | update() | speciality_id không tồn tại | Admin | PUT id=2; speciality_id=99999 | result=0; msg nhắc speciality | Pass |  |
| TC_S2_25 | DoctorController.php | update() | room_id không tồn tại | Admin | PUT id=2; room_id=99999 | result=0; msg nhắc room | Pass |  |
| TC_S2_26 | DoctorController.php | update() | Phone quá ngắn | Admin | PUT id=2; phone 9 số | result=0; msg nhắc độ dài (10) | Pass |  |
| TC_S2_27 | DoctorController.php | update() | Cho phép đổi role supporter | Admin | PUT id=2; role=supporter + field đủ | result=1; DB role=supporter | Pass |  |
| TC_S2_28 | DoctorController.php | update() | price không phải số | Admin | PUT id=2; price="khongphai_so" | result=0; msg nhắc price | Pass |  |
| TC_S2_29 | DoctorController.php | update() | Thiếu name | Admin | PUT id=2; không có name | result=0; msg nhắc name | Pass |  |
| TC_S2_30 | DoctorController.php | update() | Thiếu role | Admin | PUT id=2; không có role | result=0; msg nhắc role | Pass |  |
| TC_S2_31 | DoctorController.php | getById() | Admin xem profile chính mình | Admin | GET id=1 (trùng AuthUser) | result=1; data.name = "Admin Bac Si" | Pass |  |
| TC_S2_32 | DoctorController.php | getById() | Id không tồn tại (msg) | Admin | GET id=99999 | result=0; msg chứa "not available" | Pass |  |
| TC_S2_33 | DoctorController.php | getById() | Phân quyền: member không xem chi tiết doctor quản trị | Member | GET id=2; AuthUser=member | result=0 (chỉ admin/supporter được xem endpoint quản trị) | Fail | BUG NGHIỆP VỤ: member không được phép xem chi tiết doctor qua endpoint quản trị. Bằng chứng: test kỳ vọng giá trị 0 nhưng nhận được 1 (thường là result/msg/DB không khớp kỳ vọng nghiệp vụ). |
| TC_S2_34 | DoctorController.php | update() | Member update bị chặn + DB không đổi | Member | PUT id=2; AuthUser=member; body đổi name | result=0; msg "not admin"; DB name doctor id=2 không thành "Tran Van Nam" | Pass |  |
| TC_S2_35 | DoctorsController.php | getAll() | Admin lấy danh sách doctor | Admin | GET; AuthUser=admin | result=1; quantity≥1; data không rỗng | Pass |  |
| TC_S2_36 | DoctorsController.php | getAll() | Lọc theo prefix tên | Admin | GET _GET[search]=Nguyen | result=1; mọi phần tử data.name bắt đầu bằng "Nguyen" | Pass |  |
| TC_S2_37 | DoctorsController.php | save() | Admin tạo doctor mới | Admin | POST email/phone/name/role/price/speciality_id/room_id hợp lệ | result=1; data.email khớp; record tồn tại trong DB | Pass |  |
| TC_S2_38 | DoctorsController.php | save() | Member không tạo doctor qua endpoint quản trị | Member | POST body hợp lệ; AuthUser=member | result=0; msg permission | Pass |  |
| TC_S2_39 | DoctorsController.php | save() | Thiếu email | Admin | POST không có email | result=0; msg nhắc email | Pass |  |
| TC_S2_40 | DoctorsController.php | save() | Email sai format | Admin | POST email="invalid-email-format" | result=0; msg format | Pass |  |
| TC_S2_41 | DoctorsController.php | save() | Trùng email đã có | Admin | POST email=admin@test.com (đã seed) | result=0; msg nhắc email/trùng | Pass |  |
| TC_S2_42 | DoctorsController.php | getAll() | Lọc speciality_id | Admin | GET speciality_id=1 | result=1; mọi row speciality.id=1 | Pass |  |
| TC_S2_43 | DoctorsController.php | getAll() | Lọc room_id | Admin | GET room_id=1 | result=1; mọi row room.id=1 | Pass |  |
| TC_S2_44 | isDoctorBusyController.php | isDoctorBusy() | Thiếu doctor id route | Admin | GET không Route.params.id | result=0; msg required | Pass |  |
| TC_S2_45 | isDoctorBusyController.php | isDoctorBusy() | Kiểm tra bận doctor id có sẵn | Admin | GET id=2 | result ∈ {0,1}; msg không rỗng | Pass |  |
| TC_S2_46 | isDoctorBusyController.php | process() | Process GET điều hướng sang isDoctorBusy | Admin | GET id=2 gọi public process() | result ∈ {0,1}; msg không rỗng | Pass |  |
| TC_S2_47 | isDoctorBusyController.php | getCurrentAppointmentQuantityByDoctorId() | Đếm đúng số appointment processing trong ngày của doctor | Admin; seed 2 appointments processing cho doctor id=2 hôm nay | Reflection invoke private method với doctor_id=2 | Kết quả quantity=2 | Pass |  |
| TC_S2_48 | isDoctorBusyController.php | getAverageAppointmentWithSpecialityId() | Tính đúng trung bình ceil theo chuyên khoa | Admin; seed thêm doctor cùng speciality và appointments | Reflection invoke private method với speciality_id=1 | Kết quả average theo ceil đúng kỳ vọng | Pass |  |
| TC_S2_49 | DoctorController.php | process() | Process GET điều hướng sang getById | Admin | GET id=2 gọi public process() | result=1; data.name đúng theo doctor id=2 | Pass |  |
| TC_S2_50 | DoctorsController.php | process() | Process GET điều hướng sang getAll | Admin | GET gọi public process() | result=1; quantity>=1 | Pass |  |
| TC_S5_01 | BookingsController.php | save() | Admin tạo booking hợp lệ | Admin | POST đủ field; appointment_date=tomorrow; appointment_time=09:00; doctor_id=0 | result=1; có data.id; DB status=verified; date/time khớp | Pass |  |
| TC_S5_02 | BookingsController.php | save() | doctor_id không tồn tại | Admin | POST doctor_id=99999 | result=0 | Pass |  |
| TC_S5_03 | BookingsController.php | save() | Giờ trước 07:00 | Admin | POST appointment_time=06:00 | result=0; msg working hours | Pass |  |
| TC_S5_04 | BookingsController.php | save() | Biên 06:59 ngoài giờ | Admin | POST appointment_time=06:59 | result=0; msg working hours | Pass |  |
| TC_S5_05 | BookingsController.php | save() | Biên 07:00 được chấp nhận | Admin | POST appointment_time=07:00 | result=1 | Pass |  |
| TC_S5_06 | BookingsController.php | save() | Biên 20:00 được chấp nhận | Admin | POST appointment_time=20:00 | result=1 | Pass |  |
| TC_S5_07 | BookingsController.php | save() | 20:01 ngoài giờ | Admin | POST appointment_time=20:01 | result=0; msg working hours | Pass |  |
| TC_S5_08 | BookingsController.php | save() | Giờ trong ca (10:30) | Admin | POST appointment_time=10:30 | result=1 | Pass |  |
| TC_S5_09 | PatientBookingsController.php | getAll() | Patient chưa có booking | Patient | GET; AuthUser=patient id=1 | result=1; quantity=0; data=[] | Pass |  |
| TC_S5_10 | PatientBookingsController.php | getAll() | Chỉ booking của patient đăng nhập | Patient; seed booking patient 1 và 2 | GET patient id=1 | result=1; mọi row patient_id=1; count(data)=COUNT DB patient 1 | Pass |  |
| TC_S5_11 | PatientBookingController.php | delete() | Patient hủy booking processing | Patient; booking id=20 processing | DELETE id=20 | result=1; DB status=cancelled; không tạo appointment; update_at có | Pass |  |
| TC_S5_12 | BookingsController.php | save() | Member không POST booking quản trị | Member | POST payload hợp lệ; AuthUser=member | result=0 | Pass |  |
| TC_S5_13 | PatientBookingController.php | delete() | Không hủy booking verified | Patient; booking id=21 verified | DELETE id=21 | result=0; msg giải thích chỉ processing; DB vẫn verified | Pass |  |
| TC_S5_14 | BookingsController.php | save() | Thiếu appointment_time | Admin | POST bỏ appointment_time | result=0; msg appointment_time | Pass |  |
| TC_S5_15 | BookingsController.php | save() | Patient không gọi save quản trị | Patient | POST; AuthUser=patient | result=0 | Pass |  |
| TC_S5_16 | BookingsController.php | save() | Tạo booking tên bệnh nhân khác seed | Admin | POST name/booking_name Pham Thi Lan, 11:00 | result=1 | Pass |  |
| TC_S5_17 | PatientBookingsController.php | getAll() | Patient getAll khi không có row | Patient | GET | result=1 | Pass |  |
| TC_S5_18 | BookingsController.php | save() | Supporter tạo booking hợp lệ | Supporter | POST; AuthUser=supporter | result=1 | Pass |  |
| TC_S5_19 | BookingsController.php | save() | Thiếu service_id | Admin | POST không service_id | result=0; msg service_id | Pass |  |
| TC_S5_20 | BookingsController.php | save() | patient_id không tồn tại | Admin | POST patient_id=99999 | result=0; msg patient | Pass |  |
| TC_S5_21 | BookingsController.php | save() | service_id không tồn tại | Admin | POST service_id=88888 | result=0; msg service | Pass |  |
| TC_S5_22 | BookingsController.php | save() | booking_phone không hợp lệ | Admin | POST booking_phone có chữ | result=0; msg phone | Pass |  |
| TC_S5_23 | BookingsController.php | save() | booking_name có số | Admin | POST booking_name=John123 | result=0; msg Booking name | Pass |  |
| TC_S5_24 | BookingsController.php | save() | address ký tự đặc biệt | Admin | POST address=!!!@@@### | result=0; msg address | Pass |  |
| TC_S5_25 | BookingsController.php | save() | gender không hợp lệ | Admin | POST gender=2 | result=0; msg gender | Pass |  |
| TC_S5_26 | BookingsController.php | save() | 20:10 ngoài giờ | Admin | POST appointment_time=20:10 | result=0; msg working hours | Pass |  |
| TC_S5_27 | BookingsController.php | getAll() | Admin list booking | Admin | GET | result=1; data là mảng | Pass |  |
| TC_S5_28 | PatientBookingController.php | getById() | Patient xem booking của mình | Patient; booking id=40 patient 1 | GET id=40 | result=1; data.id=40 | Pass |  |
| TC_S5_29 | PatientBookingController.php | getById() | Patient không xem booking người khác | Patient; booking id=41 thuộc patient 3 | GET id=41; AuthUser patient 1 | result=0 | Pass |  |
| TC_S5_30 | PatientBookingController.php | delete() | Thiếu id | Patient | DELETE không Route.params.id | result=0; msg required | Pass |  |
| TC_S5_31 | PatientBookingController.php | delete() | Không hủy lại booking đã cancelled | Patient; booking id=42 cancelled | DELETE id=42 | result=0; msg chứa cancelled | Pass |  |
| TC_S5_32 | PatientBookingsController.php | getAll() | quantity = tổng bản ghi (phân trang) | Patient; 3 booking patient 1 | GET length=1 start=0 | result=1; count(data)=1; quantity=3 (tổng trước LIMIT) | Fail | BUG NGHIỆP VỤ: quantity phải là tổng booking của patient (3) để frontend tính số trang; không phải số dòng trên trang (1). Bằng chứng: test kỳ vọng giá trị 3 nhưng nhận được 1 (thường là result/msg/DB không khớp kỳ vọng nghiệp vụ). |
| TC_S5_33 | PatientBookingsController.php | getAll() | Tìm theo booking_name prefix | Patient; booking Nguyen… và Le… | GET search=Nguyen | result=1; mọi booking_name bắt đầu Nguyen | Fail | Search phải chạy được và trả result=1 (không được throw SQL/undefined var) Bằng chứng: test kỳ vọng giá trị 1 nhưng nhận được 0 (thường là result/msg/DB không khớp kỳ vọng nghiệp vụ). |
| TC_S5_34 | BookingsController.php | getAll() | Member không list booking quản trị | Member | GET; AuthUser=member | result=0 | Fail | BUG NGHIỆP VỤ: role=member không được phép list bookings (chỉ admin/supporter). Bằng chứng: test kỳ vọng giá trị 0 nhưng nhận được 1 (thường là result/msg/DB không khớp kỳ vọng nghiệp vụ). |
| TC_S5_35 | BookingsController.php | save() | Chặn trùng slot bác sĩ | Admin | POST 2 lần cùng doctor_id=2 + appointment_date/time | Lần 1 result=1; lần 2 result=0 (anti double-booking) | Fail | BUG NGHIỆP VỤ: Bác sĩ id=2 đã có booking cùng slot (date+time) nhưng API vẫn cho tạo thêm. Hệ thống đặt lịch y tế phải chặn double-booking bác sĩ. Bằng chứng: test kỳ vọng giá trị 0 nhưng nhận được 1 (thường là result/msg/DB không khớp kỳ vọng nghiệp vụ). |
| TC_S5_36 | PatientBookingController.php | delete() | Patient không hủy verified (đúng nghiệp vụ) | Patient; booking id=99 verified | DELETE id=99 | result=0; msg nhắc processing; DB vẫn verified | Pass |  |
| TC_S5_37 | BookingController.php | getById() | Admin xem chi tiết booking | Admin; booking id=500 | GET id=500 | result=1; data.id=500; có object service | Pass |  |
| TC_S5_38 | BookingController.php | getById() | Member không xem booking quản trị | Member; booking id=501 | GET id=501 | result=0; msg permission | Fail | Lỗi nghiệp vụ phân quyền: role member vẫn truy cập được endpoint/dữ liệu quản trị (đáng ra phải result=0 và từ chối). Bằng chứng: test kỳ vọng giá trị 0 nhưng nhận được 1 (thường là result/msg/DB không khớp kỳ vọng nghiệp vụ). |
| TC_S5_39 | BookingController.php | getById() | Thiếu id route | Admin | GET không Route.params.id | result=0; msg required | Pass |  |
| TC_S5_40 | PatientBookingsController.php | save() | Patient tao booking hop le | Patient | POST service_id/doctor_id/booking_name/booking_phone/name/date/time hop le | result=1; data.status=processing; DB co booking moi; co 1 notification booking | Pass |  |
| TC_S5_41 | PatientBookingsController.php | save() | Chan trung booking processing cung service cung ngay | Patient; DB da co booking processing service 1 cung ngay | POST them booking moi cung service_id=1 va appointment_date giong booking cu | result=0; msg thong bao da co lich hen | Pass |  |
| TC_S5_42 | BookingController.php | update() | Admin cap nhat booking processing | Admin; booking id=700 status=processing | PUT id=700; doi booking_name/booking_phone/date/time | result=1; DB cap nhat field moi; status van processing | Pass |  |
| TC_S5_43 | BookingController.php | update() | Khong cho sua booking da verified | Admin; booking id=701 status=verified | PUT id=701 voi payload hop le | result=0; msg nhac chi processing moi duoc sua | Pass |  |
| TC_S5_44 | BookingController.php | confirm() | Xac nhan booking verified phai tao appointment | Admin; booking id=702 status=processing | PATCH id=702; _PATCH[newStatus]=verified | result=1; DB booking status=verified; phai co 1 appointment moi theo booking_id=702 | Fail | BUG NGHIEP VU: xac nhan booking sang verified phai sinh appointment tuong ung de dua vao luong kham. Bằng chứng: test kỳ vọng giá trị 1 nhưng nhận được 0 (thường là result/msg/DB không khớp kỳ vọng nghiệp vụ). |
| TC_S5_45 | BookingController.php | confirm() | Xac nhan huy booking processing | Admin; booking id=703 status=processing | PATCH id=703; _PATCH[newStatus]=cancelled | result=1; DB booking status=cancelled | Pass |  |
| TC_S5_46 | BookingsController.php | process() | Process GET điều hướng sang getAll | Admin | GET gọi public process() | result=1; trả data dạng mảng | Pass |  |
| TC_S5_47 | BookingsController.php | process() | Process POST điều hướng sang save | Admin | POST payload booking hợp lệ gọi public process() | result=1; response có data.id | Pass |  |
| TC_S5_48 | BookingController.php | process() | Process PATCH điều hướng sang confirm | Admin; booking id=704 status=processing | PATCH id=704 newStatus=cancelled gọi public process() | result=1; DB status booking id=704=cancelled | Pass |  |
| TC_S5_49 | PatientBookingsController.php | process() | Process GET điều hướng sang getAll | Patient | GET gọi public process() | result=1; data là mảng | Pass |  |
| TC_S5_50 | PatientBookingsController.php | process() | Process POST điều hướng sang save | Patient | POST payload booking hợp lệ gọi public process() | result=1; data.status=processing | Pass |  |
| TC_S5_51 | PatientBookingController.php | process() | Process GET điều hướng sang getById | Patient; booking id=710 thuộc patient | GET id=710 gọi public process() | result=1; data.id=710 | Pass |  |
| TC_S5_52 | PatientBookingController.php | process() | Process DELETE điều hướng sang delete | Patient; booking id=711 status=processing | DELETE id=711 gọi public process() | result=1; DB status chuyển cancelled | Pass |  |
| TC_S6_01 | SpecialitiesController.php | save() | Tạo speciality mới | Admin | POST name+description | result=1 | Pass |  |
| TC_S6_02 | SpecialitiesController.php | save() | Tên speciality rỗng | Admin | POST name="" | result=0; msg Missing field + name | Pass |  |
| TC_S6_03 | SpecialityController.php | getById() | Lấy speciality id=1 | Admin | GET id=1 | result=1; data.name seed | Pass |  |
| TC_S6_04 | SpecialityController.php | update() | Admin cập nhật speciality | Admin | PUT id=2; name+description | result=1; data.name cập nhật | Pass |  |
| TC_S6_05 | SpecialityController.php | delete() | Không xóa speciality đang gắn doctor | Admin; doctor gắn speciality_id=2 | DELETE id=2 | result=0; msg doctors | Pass |  |
| TC_S6_06 | ServicesController.php | save() | Tạo service mới | Admin | POST name+description | result=1 | Pass |  |
| TC_S6_07 | ServiceController.php | getById() | Lấy service id=1 | Admin | GET id=1 | result=1 | Pass |  |
| TC_S6_08 | RoomsController.php | save() | Tạo phòng mới | Admin | POST name+location | result=1 | Pass |  |
| TC_S6_09 | RoomController.php | delete() | Xóa phòng không dùng | Admin | DELETE id=2 | result=1 | Pass |  |
| TC_S6_10 | ClinicsController.php | save() | Tạo clinic hợp lệ | Admin | POST name+address chữ cái | result=1 | Pass |  |
| TC_S6_11 | ClinicController.php | getById() | Lấy clinic id=1 | Admin | GET id=1 | result=1 | Pass |  |
| TC_S6_12 | ServiceController.php | getById() | Service không tồn tại | Admin | GET id=88888 | result=0; msg not available | Pass |  |
| TC_S6_13 | SpecialitiesController.php | save() | Member không tạo speciality | Member | POST; AuthUser=member | result=0; msg not admin | Pass |  |
| TC_S6_14 | SpecialitiesController.php | save() | Trùng tên speciality | Admin | POST name trùng seed | result=0 | Pass |  |
| TC_S6_15 | RoomController.php | delete() | Không xóa phòng mặc định id=1 | Admin | DELETE id=1 | result=0; msg default | Pass |  |
| TC_S6_16 | RoomController.php | getById() | Member không getById phòng quản trị | Member | GET id=1 | result=0 | Pass |  |
| TC_S6_17 | ClinicsController.php | save() | Tên bệnh viện ký tự đặc biệt | Admin | POST name=BV@@@ | result=0; msg letters | Pass |  |
| TC_S6_18 | SpecialitiesController.php | save() | Tạo speciality hợp lệ | Admin | POST Ho hap | result=1 | Pass |  |
| TC_S6_19 | ServicesController.php | save() | Trùng tên service | Admin | POST name trùng seed | result=0; msg exists | Pass |  |
| TC_S6_20 | ClinicController.php | getById() | Clinic không tồn tại | Admin | GET id=999 | result=0; msg not available | Pass |  |
| TC_S6_21 | ServicesController.php | getAll() | Admin list service có seed | Admin | GET | result=1; quantity≥1; có "Dich vu mac dinh" | Pass |  |
| TC_S6_22 | ServicesController.php | getAll() | Lọc search prefix | Admin | GET search=Dich | result=1; mọi name bắt đầu Dich | Pass |  |
| TC_S6_23 | ServicesController.php | getAll() | Phân trang length=1 | Admin | GET length=1 start=0 | result=1; count(data)≤1 | Pass |  |
| TC_S6_24 | ServicesController.php | save() | Thiếu name | Admin | POST chỉ description | result=0; msg name | Pass |  |
| TC_S6_25 | ServicesController.php | save() | Member không tạo service | Member | POST; AuthUser=member | result=0 | Pass |  |
| TC_S6_26 | ServicesController.php | save() | Payload sau tạo service | Admin | POST name mới | result=1; data.name khớp; có id | Pass |  |
| TC_S6_27 | ServiceController.php | getById() | Thiếu id route | Admin | GET không Route.params.id | result=0; msg required | Pass |  |
| TC_S6_28 | ServiceController.php | getById() | Payload có description + image | Admin | GET id=1 | result=1; data có description, image | Pass |  |
| TC_S6_29 | ServiceController.php | update() | Admin cập nhật service | Admin | PUT id=1 name+description | result=1; msg updated | Pass |  |
| TC_S6_30 | ServiceController.php | update() | Member không update service | Member | PUT id=1 | result=0 | Pass |  |
| TC_S6_31 | ServiceController.php | update() | Thiếu description | Admin | PUT id=1 chỉ name | result=0; msg description | Pass |  |
| TC_S6_32 | ServiceController.php | update() | Service id không tồn tại | Admin | PUT id=99999 | result=0 | Pass |  |
| TC_S6_33 | ServiceController.php | delete() | Không xóa service mặc định | Admin | DELETE id=1 | result=0; msg default | Pass |  |
| TC_S6_34 | ServiceController.php | delete() | Member không xóa service | Member | DELETE id=50 (seed test) | result=0 | Pass |  |
| TC_S6_35 | ServiceController.php | delete() | Không xóa service đang có booking | Admin; service 51 có booking | DELETE id=51 | result=0; msg booking | Pass |  |
| TC_S6_36 | ServiceController.php | delete() | Không xóa service gắn bác sĩ | Admin; service 52 gắn doctor | DELETE id=52 | result=0; msg doctor | Pass |  |
| TC_S6_37 | ServiceController.php | delete() | Xóa service không ràng buộc | Admin | DELETE id=53 | result=1; record không còn available | Pass |  |
| TC_S6_38 | ServiceController.php | delete() | Xóa id không tồn tại | Admin | DELETE id=77777 | result=0 | Pass |  |
| TC_S6_39 | ServicesController.php | getAll() | Search không khớp | Admin | GET search=ZZZNOMATCH999 | result=1; quantity=0; data=[] | Pass |  |
| TC_S6_40 | ServicesController.php | save() | name rỗng = thiếu | Admin | POST name="" | result=0 | Pass |  |
| TC_S6_41 | ServicesController.php | getAll() | quantity phải là tổng trước LIMIT | Admin; DB thêm service id 60,61 | GET length=1 start=0 | result=1; quantity=3 (tổng service); data có đúng 1 phần tử | Pass |  |
| TC_S6_42 | ServicesController.php | getAll() | Member không list service quản trị | Member | GET; AuthUser=member | result=0 | Fail | BUG NGHIỆP VỤ: member không được phép list services qua endpoint quản trị. Bằng chứng: test kỳ vọng giá trị 0 nhưng nhận được 1 (thường là result/msg/DB không khớp kỳ vọng nghiệp vụ). |
| TC_S6_43 | SpecialitiesController.php | getAll() | Member không list speciality quản trị | Member | GET; AuthUser=member | result=0 | Fail | BUG NGHIỆP VỤ: member không được phép list specialities qua endpoint quản trị. Bằng chứng: test kỳ vọng giá trị 0 nhưng nhận được 1 (thường là result/msg/DB không khớp kỳ vọng nghiệp vụ). |
| TC_S6_44 | SpecialitiesController.php | getAll() | Admin list speciality co doctor_quantity | Admin | GET | result=1; quantity>=2; data[0] co doctor_quantity | Pass |  |
| TC_S6_45 | SpecialityController.php | delete() | Khong xoa speciality mac dinh | Admin | DELETE id=1 | result=0; msg default | Pass |  |
| TC_S6_46 | SpecialityController.php | delete() | Xoa speciality id khong ton tai | Admin | DELETE id=9999 | result=0; msg not available | Pass |  |
| TC_S6_47 | RoomsController.php | getAll() | Admin list room theo speciality | Admin | GET speciality_id=1 | result=1; quantity>=1; moi row co doctor_quantity | Pass |  |
| TC_S6_48 | RoomController.php | update() | Admin cap nhat room hop le | Admin | PUT id=2; name/location moi | result=1; DB room id=2 doi name/location | Pass |  |
| TC_S6_49 | RoomController.php | delete() | Khong xoa room dang co doctor | Admin; doctor id=2 gan room_id=2 | DELETE id=2 | result=0; msg doctors | Pass |  |
| TC_S6_50 | ClinicsController.php | getAll() | quantity clinic phai la tong truoc LIMIT | Admin; DB co them clinic id 2,3 | GET length=1 start=0 | result=1; count(data)=1; quantity=3 | Fail | BUG NGHIEP VU: quantity phai la tong clinic truoc LIMIT de frontend phan trang dung. Bằng chứng: test kỳ vọng giá trị 3 nhưng nhận được 1 (thường là result/msg/DB không khớp kỳ vọng nghiệp vụ). |
| TC_S6_51 | ClinicController.php | update() | Admin cap nhat clinic hop le | Admin | PUT id=1; name/address moi | result=1; DB clinic id=1 doi name/address | Pass |  |
| TC_S6_52 | ClinicController.php | delete() | Xoa clinic khong rang buoc | Admin; DB them clinic id=4 khong doctor lien ket | DELETE id=4 | result=1; row id=4 khong con trong DB | Fail | BUG NGHIEP VU: clinic khong co rang buoc doctor thi phai xoa duoc, nhung controller dang loi schema/query: SQLSTATE[HY000]: General error: 1 no such column: tn_doctors.Clinic_id |
| TC_S6_53 | ClinicsController.php | getAll() | Member khong list clinic quan tri | Member | GET; AuthUser=member | result=0; msg not admin | Pass |  |
| TC_S6_54 | SpecialityController.php | process() | Process GET dispatch dung sang getById | Admin | GET id=1 qua public process() | result=1; data.name = "Chuyen khoa mac dinh" | Pass |  |
| TC_S6_55 | RoomController.php | process() | Process GET dispatch dung sang getById | Admin | GET id=1 qua public process() | result=1; data.name = "Phong 1" | Pass |  |
| TC_S6_56 | ClinicController.php | process() | Process GET dispatch dung sang getById | Admin | GET id=1 qua public process() | result=1; data.name = "Phong kham mac dinh" | Pass |  |
| TC_S6_57 | ServiceController.php | process() | Process GET dispatch dung sang getById | Admin | GET id=1 qua public process() | result=1; data.name = "Dich vu mac dinh" | Pass |  |
| TC_S6_58 | SpecialityController.php | process() | Upload avatar speciality khi thiếu file | Admin | POST id=1; action=avatar; không gửi file | result=0; msg chứa "Photo is not received" | Pass |  |
| TC_S6_59 | ServiceController.php | process() | Upload avatar service khi thiếu file | Admin | POST id=1; action=avatar; không gửi file | result=0; msg chứa "Photo is not received" | Pass |  |
| TC_S6_60 | helpers/common.helper.php | htmlchars() | Encode ký tự HTML đặc biệt | N/A | Input chuỗi có thẻ HTML và dấu ngoặc kép | Chuỗi được encode đúng theo ENT_QUOTES | Pass |  |
| TC_S6_61 | helpers/common.helper.php | truncate_string() | Rút gọn chuỗi và thêm ellipsis | N/A | Input "abcdef", max_length=5, ellipsis="..." | Output "ab..." | Pass |  |
| TC_S6_62 | helpers/common.helper.php | url_slug() | Chuyển text thành slug SEO | N/A | Input " Xin Chao 2026!! " | Output "xin-chao-2026" | Pass |  |
| TC_S6_63 | helpers/common.helper.php | format_price() | Format giá ở 2 chế độ thập phân/zero-decimal | N/A | Gọi format_price với 12.5 và 12.6,zdc=true | Output đúng định dạng số và làm tròn | Pass |  |
| TC_S6_64 | helpers/common.helper.php | getTimezones() | Trả danh sách timezone chuẩn | N/A | Gọi getTimezones() | Mảng chứa key UTC và Asia/Ho_Chi_Minh | Pass |  |
| TC_S6_65 | helpers/common.helper.php | isValidDate() | Xác thực date-time hợp lệ/không hợp lệ | N/A | Input ngày hợp lệ và ngày không tồn tại | true cho ngày hợp lệ, false cho ngày sai | Pass |  |
| TC_S6_66 | helpers/common.helper.php | textInitials() | Lấy ký tự viết tắt từ chuỗi tên | N/A | Input "Nguyen Van A", length=2 | Output initials đúng | Pass |  |
| TC_S6_67 | helpers/common.helper.php | readableRandomString()/generateRandomString()/readableNumber() | Sinh chuỗi random và format số readable | N/A | Gọi các helper random/number với input cố định | Độ dài/chuẩn output đúng format kỳ vọng | Pass |  |
| TC_S6_68 | helpers/common.helper.php | readableFileSize() | Đổi bytes sang đơn vị dễ đọc | N/A | Input 1024 và 2048 bytes | Output lần lượt 1kB và 2kB | Pass |  |
| TC_S6_69 | helpers/common.helper.php | isNumber()/isVietnameseName()/isAddress()/isVietnameseHospital() | Validate số/tên/địa chỉ/tên bệnh viện | N/A | Input cả case hợp lệ và không hợp lệ | Kết quả regex trả 1/0 đúng theo từng case | Pass |  |
| TC_S6_70 | helpers/common.helper.php | isValidJSON() | Phân biệt JSON hợp lệ và sai cú pháp | N/A | Input JSON đúng và thiếu dấu đóng | true cho JSON đúng, false cho JSON sai | Pass |  |
| TC_S6_71 | helpers/common.helper.php | isZeroDecimalCurrency() | Kiểm tra mã tiền tệ zero-decimal | N/A | Input VND và USD | VND=true, USD=false | Pass |  |
| TC_S6_72 | helpers/common.helper.php | isBirthdayValid() | Không chấp nhận ngày sinh trong tương lai | N/A | Input ngày mai | Trả message lỗi khác rỗng | Pass |  |
| TC_S6_73 | helpers/common.helper.php | isAppointmentDateValid() | Validate ngày hẹn tồn tại và không ở quá khứ | N/A | Input ngày không tồn tại + ngày hôm qua | Đều trả message lỗi | Pass |  |
| TC_S6_74 | helpers/common.helper.php | isAppointmentHourValid() | Từ chối giờ khám ngoài khung 07:00-20:00 | N/A | Input giờ 20:10 với ngày mai | Trả message chứa working hours | Pass |  |
| TC_S6_75 | helpers/common.helper.php | isAppointmentTimeValid() | Chấp nhận mốc biên 07:00 cho ngày hợp lệ | N/A | Input "{tomorrow} 07:00" | Trả chuỗi rỗng (hợp lệ) | Pass |  |
| TC_S6_76 | ServicesController.php | process() | Process GET điều hướng sang getAll | Admin | GET gọi public process() | result=1; data là mảng | Pass |  |
| TC_S6_77 | SpecialitiesController.php | process() | Process GET điều hướng sang getAll | Admin | GET gọi public process() | result=1; data là mảng | Pass |  |
| TC_S6_78 | RoomsController.php | process() | Process GET điều hướng sang getAll | Admin | GET gọi public process() | result=1; data là mảng | Pass |  |
| TC_S6_79 | ClinicsController.php | process() | Process GET điều hướng sang getAll | Admin | GET gọi public process() | result=1; data là mảng | Pass |  |
