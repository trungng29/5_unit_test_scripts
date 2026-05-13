package com.example.do_an_tot_nghiep.Model;

import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertFalse;
import static org.junit.Assert.assertNotEquals;
import static org.junit.Assert.assertNotNull;
import static org.junit.Assert.assertNull;
import static org.junit.Assert.assertTrue;

import com.example.do_an_tot_nghiep.Model.Doctor;
import com.example.do_an_tot_nghiep.Model.Room;
import com.example.do_an_tot_nghiep.Model.Speciality;
import com.google.gson.Gson;
import com.google.gson.GsonBuilder;

import org.junit.Before;
import org.junit.Test;

/**
 * Unit tests for Appointment and Queue model classes.
 *
 * Strategy: models have no setters (immutable via Gson). All tests use
 * Gson.fromJson() to construct objects from JSON strings — matching how
 * Retrofit deserializes API responses in the real app.
 *
 * NOTE:
 * - PASS tests validate current implemented behavior.
 * - FAIL tests highlight real missing validation/business-rule guards.
 */

public class AppointmentpageModelLogicTest {

    private Gson gson;

    @Before
    public void setUp() {
        gson = new GsonBuilder().setLenient().create();
    }

    // =============================================================================
    // APPOINTMENT MODEL - PASS tests (current behavior is correct)
    // =============================================================================

    // Test Case ID: TC-MODEL-APPOINT-001
    // Valid appointment with all required fields parses correctly.
    @Test
    public void validAppointmentParsing() {
        String json = "{\n" +
                "  \"id\": 10,\n" +
                "  \"date\": \"2026-05-11\",\n" +
                "  \"numerical_order\": 3,\n" +
                "  \"position\": 5,\n" +
                "  \"patient_id\": 99,\n" +
                "  \"patient_name\": \"Nguyen Van A\",\n" +
                "  \"patient_phone\": \"0123456789\",\n" +
                "  \"patient_birthday\": \"2000-01-01\",\n" +
                "  \"patient_reason\": \"Headache\",\n" +
                "  \"appointment_time\": \"08:30\",\n" +
                "  \"status\": \"processing\"\n" +
                "}";

        Appointment appointment = gson.fromJson(json, Appointment.class);

        assertEquals(Integer.valueOf(10), appointment.getId());
        assertEquals("2026-05-11", appointment.getDate());
        assertEquals(Integer.valueOf(3), appointment.getNumericalOrder());
        assertEquals(Integer.valueOf(5), appointment.getPosition());
        assertEquals("Nguyen Van A", appointment.getPatientName());
        assertEquals("0123456789", appointment.getPatientPhone());
        assertEquals("processing", appointment.getStatus());
    }

    // Test Case ID: TC-MODEL-APPOINT-002
    // Nested objects (doctor, speciality, room) parse correctly.
    @Test
    public void appointmentNestedObjectsParsing() {
        String json = "{\n" +
                "  \"id\": 1,\n" +
                "  \"doctor\": { \"id\": 5, \"name\": \"Dr. An\", \"email\": \"an@test.com\" },\n" +
                "  \"speciality\": { \"id\": 2, \"name\": \"Cardiology\" },\n" +
                "  \"room\": { \"id\": 3, \"name\": \"Room 101\", \"location\": \"Floor 2\" }\n" +
                "}";

        Appointment appointment = gson.fromJson(json, Appointment.class);

        assertNotNull(appointment.getDoctor());
        assertEquals("Dr. An", appointment.getDoctor().getName());

        assertNotNull(appointment.getSpeciality());
        assertEquals("Cardiology", appointment.getSpeciality().getName());

        assertNotNull(appointment.getRoom());
        assertEquals("Room 101", appointment.getRoom().getName());
        assertEquals("Floor 2", appointment.getRoom().getLocation());
    }

    // =============================================================================
    // APPOINTMENT MODEL - FAIL tests (real bugs / missing validation)
    // =============================================================================

    // Test Case ID: TC-MODEL-APPOINT-003
    // Real issue: negative position is accepted silently. Queue position should be non-negative.
    @Test
    public void negativePosition() {
        String json = "{\"id\": 1, \"position\": -5, \"numerical_order\": 1}";
        Appointment appointment = gson.fromJson(json, Appointment.class);

        // Fails: Gson accepts -5 without complaint; business rule requires position >= 0.
        assertTrue("Position should be non-negative", appointment.getPosition() >= 0);
    }

    // Test Case ID: TC-MODEL-APPOINT-004
    // Real issue: empty patient_name is accepted. Empty patient name is invalid data.
    @Test
    public void emptyPatientName() {
        String json = "{\"patient_name\": \"\"}";
        Appointment appointment = gson.fromJson(json, Appointment.class);

        // Fails: Gson stores empty string; validation is missing.
        assertNotNull(appointment.getPatientName());
        assertFalse("Patient name should not be empty",
                appointment.getPatientName().trim().isEmpty());
    }

    // Test Case ID: TC-MODEL-APPOINT-005
    // Real issue: invalid phone format is accepted. Should validate phone format (digits, 10-11 chars).
    @Test
    public void invalidPhoneFormats() {
        String json1 = "{\"patient_phone\": \"abc123\"}";
        String json2 = "{\"patient_phone\": \"0123456\"}";      // too short
        String json3 = "{\"patient_phone\": \"01234567890123\"}"; // too long

        Appointment a1 = gson.fromJson(json1, Appointment.class);
        Appointment a2 = gson.fromJson(json2, Appointment.class);
        Appointment a3 = gson.fromJson(json3, Appointment.class);

        // Fails: Gson accepts any string; phone format validation is missing.
        String phone1 = a1.getPatientPhone();
        String phone2 = a2.getPatientPhone();
        String phone3 = a3.getPatientPhone();

        assertTrue("phone1 should be 10-11 digits", phone1 == null || phone1.matches("\\d{10,11}"));
        assertTrue("phone2 should be 10-11 digits", phone2 == null || phone2.matches("\\d{10,11}"));
        assertTrue("phone3 should be 10-11 digits", phone3 == null || phone3.matches("\\d{10,11}"));
    }

    // Test Case ID: TC-MODEL-APPOINT-006
    // Real issue: arbitrary status string is accepted. Status should be constrained
    // to known values: "pending", "processing", "done", "cancelled".
    @Test
    public void invalidAppointmentStatus() {
        String json1 = "{\"status\": \"invalid_status\"}";
        String json2 = "{\"status\": \"PENDING\"}";    // wrong case
        String json3 = "{\"status\": \"\"}";           // empty

        Appointment a1 = gson.fromJson(json1, Appointment.class);
        Appointment a2 = gson.fromJson(json2, Appointment.class);
        Appointment a3 = gson.fromJson(json3, Appointment.class);

        // Fails: Gson accepts any string; status validation is missing.
        for (Appointment a : new Appointment[]{a1, a2, a3}) {
            String status = a.getStatus();
            assertTrue("Status should be one of: pending, processing, done, cancelled",
                    "pending".equals(status) ||
                    "processing".equals(status) ||
                    "done".equals(status) ||
                    "cancelled".equals(status));
        }
    }

    // Test Case ID: TC-MODEL-APPOINT-007
    // Real issue: negative or zero ID is accepted. Database IDs should be positive.
    @Test
    public void negativeAppointmentId() {
        String json1 = "{\"id\": -1}";
        String json2 = "{\"id\": 0}";

        Appointment a1 = gson.fromJson(json1, Appointment.class);
        Appointment a2 = gson.fromJson(json2, Appointment.class);

        // Fails: Gson accepts -1 and 0; ID validation is missing.
        assertTrue("Negative ID should be rejected", a1.getId() == null || a1.getId() > 0);
        assertTrue("Zero ID should be rejected", a2.getId() == null || a2.getId() > 0);
    }

    // Test Case ID: TC-MODEL-APPOINT-008
    // Real issue: zero or negative numerical_order is accepted.
    // Numerical order in a queue should start from 1.
    @Test
    public void invalidNumericalOrder() {
        String json1 = "{\"numerical_order\": 0}";
        String json2 = "{\"numerical_order\": -3}";

        Appointment a1 = gson.fromJson(json1, Appointment.class);
        Appointment a2 = gson.fromJson(json2, Appointment.class);

        // Fails: Gson accepts 0 and negative; validation is missing.
        assertTrue("numericalOrder should be >= 1", a1.getNumericalOrder() == null || a1.getNumericalOrder() >= 1);
        assertTrue("numericalOrder should be >= 1", a2.getNumericalOrder() == null || a2.getNumericalOrder() >= 1);
    }

    // Test Case ID: TC-MODEL-APPOINT-009
    // Real issue: nested objects (doctor, speciality, room) can be null in JSON.
    // Accessing these null fields in UI code will cause NullPointerException.
    @Test
    public void nullNestedObjects() {
        String json = "{\"id\": 1, \"doctor\": null, \"speciality\": null, \"room\": null}";
        Appointment appointment = gson.fromJson(json, Appointment.class);

        // Fails: Gson leaves fields as null; null guards are missing in the UI layer.
        assertNotNull("Doctor should not be null", appointment.getDoctor());
        assertNotNull("Speciality should not be null", appointment.getSpeciality());
        assertNotNull("Room should not be null", appointment.getRoom());
    }

    // =============================================================================
    // QUEUE MODEL - PASS tests (current behavior is correct)
    // =============================================================================

    // Test Case ID: TC-MODEL-APPOINT-010
    // Valid queue entry with all required fields parses correctly.
    @Test
    public void validQueueParsing() {
        String json = "{\n" +
                "  \"id\": 1,\n" +
                "  \"position\": 3,\n" +
                "  \"numerical_order\": 3,\n" +
                "  \"patient_id\": 99,\n" +
                "  \"patient_name\": \"Tran Thi B\",\n" +
                "  \"doctor_id\": 7,\n" +
                "  \"appointment_time\": \"09:00\",\n" +
                "  \"status\": \"processing\"\n" +
                "}";

        Queue queue = gson.fromJson(json, Queue.class);

        assertEquals(1, queue.getId());
        assertEquals(3, queue.getPosition());
        assertEquals("Tran Thi B", queue.getPatientName());
        assertEquals("processing", queue.getStatus());
    }

    // =============================================================================
    // QUEUE MODEL - FAIL tests (real bugs / missing validation)
    // =============================================================================

    // Test Case ID: TC-MODEL-APPOINT-011
    // Design issue: Queue.position is primitive int (defaults to 0).
    // When JSON omits "position", Gson leaves it at 0 — indistinguishable from a legitimate
    // position 0. Should use Integer (wrapper) so null can be distinguished from 0.
    @Test
    public void queueMissingPosition() {
        // JSON intentionally omits "position" field.
        String json = "{\"id\": 1, \"numerical_order\": 3}";
        Queue queue = gson.fromJson(json, Queue.class);

        // Current behavior: primitive int defaults to 0.
        assertEquals(0, queue.getPosition());

        // Design issue: a position of 0 is ambiguous — is it missing or valid?
        // Fix: change Queue.position from "int" to "Integer" to detect missing field.
        // This test documents the ambiguity.
    }

    // Test Case ID: TC-MODEL-APPOINT-012
    // Design issue: Queue.numericalOrder is primitive int. Missing JSON field defaults to 0,
    // which is indistinguishable from a valid numericalOrder of 0.
    @Test
    public void queueMissingNumericalOrder() {
        String json = "{\"id\": 1, \"position\": 3}";  // numericalOrder omitted
        Queue queue = gson.fromJson(json, Queue.class);

        // Primitive int defaults to 0 when missing — same ambiguity as position.
        assertEquals(0, queue.getNumericalOrder());
    }

    // Test Case ID: TC-MODEL-APPOINT-013
    // Real issue: Queue.status accepts arbitrary strings. Should be constrained.
    @Test
    public void invalidQueueStatus() {
        String json1 = "{\"status\": \"INVALID\"}";
        String json2 = "{\"status\": \"\"}";
        // Note: Gson does not set null for missing field, but JSON with explicit null is possible
        // if server sends {"status": null}

        Queue q1 = gson.fromJson(json1, Queue.class);
        Queue q2 = gson.fromJson(json2, Queue.class);

        // Fails: arbitrary strings accepted; status validation is missing.
        for (Queue q : new Queue[]{q1, q2}) {
            String status = q.getStatus();
            assertNotNull("Status should not be null", status);
            assertTrue("Status should be one of: pending, processing, done, cancelled",
                    "pending".equals(status) ||
                    "processing".equals(status) ||
                    "done".equals(status) ||
                    "cancelled".equals(status));
        }
    }

    // Test Case ID: TC-MODEL-APPOINT-014
    // Real issue: Queue.appointmentTime accepts invalid time formats without validation.
    @Test
    public void invalidTimeFormat() {
        String json1 = "{\"appointment_time\": \"25:99\"}";     // invalid hour and minute
        String json2 = "{\"appointment_time\": \"8:30\"}";    // single digit hour
        String json3 = "{\"appointment_time\": \"abc\"}";       // non-numeric
        String json4 = "{\"appointment_time\": \"\"}";         // empty

        Queue q1 = gson.fromJson(json1, Queue.class);
        Queue q2 = gson.fromJson(json2, Queue.class);
        Queue q3 = gson.fromJson(json3, Queue.class);
        Queue q4 = gson.fromJson(json4, Queue.class);

        // Fails: all formats accepted; time validation is missing.
        for (Queue q : new Queue[]{q1, q2, q3, q4}) {
            String time = q.getAppointmentTime();
            assertNotNull("Appointment time should not be null", time);
            assertTrue("Time should match HH:mm format (HH 00-23, mm 00-59)",
                    time.matches("([01]\\d|2[0-3]):([0-5]\\d)"));
        }
    }

    // =============================================================================
    // BOUNDARY value tests
    // =============================================================================

    // Test Case ID: TC-MODEL-APPOINT-015
    // Boundary value: very large position value is preserved.
    @Test
    public void largePosition() {
        String json = "{\"position\": 99999}";
        Appointment appointment = gson.fromJson(json, Appointment.class);

        assertEquals(Integer.valueOf(99999), appointment.getPosition());
    }

    // Test Case ID: TC-MODEL-APPOINT-016
    // Boundary value: null status should be normalized.
    @Test
    public void nullStatus() {
        // Gson does not map a missing "status" field to null — it stays as the Java field
        // default (null for String). This test documents that null status is possible.
        String json = "{\"status\": null}";
        Appointment appointment = gson.fromJson(json, Appointment.class);

        // Fails: null status is accepted; normalization is missing.
        assertNotNull("Status should not be null", appointment.getStatus());
    }

    // Test Case ID: TC-MODEL-APPOINT-017
    // Boundary value: null date should be normalized.
    @Test
    public void nullDate() {
        String json = "{\"date\": null}";
        Appointment appointment = gson.fromJson(json, Appointment.class);

        // Fails: null date is accepted; normalization is missing.
        assertNotNull("Date should not be null", appointment.getDate());
    }

    // =============================================================================
    // APPOINTMENT MODEL - SETTER COVERAGE TESTS
    // Each setter/getter pair is tested as a unit to cover the mutator branches.
    // =============================================================================

    // Test Case ID: TC-MODEL-APPOINT-018
    @Test
    public void appointmentIdSetter() {
        Appointment appointment = new Appointment();
        appointment.setId(42);
        assertEquals(Integer.valueOf(42), appointment.getId());
    }

    // Test Case ID: TC-MODEL-APPOINT-019
    @Test
    public void appointmentDateSetter() {
        Appointment appointment = new Appointment();
        appointment.setDate("2026-06-15");
        assertEquals("2026-06-15", appointment.getDate());
    }

    // Test Case ID: TC-MODEL-APPOINT-020
    @Test
    public void appointmentNumericalOrderSetter() {
        Appointment appointment = new Appointment();
        appointment.setNumericalOrder(7);
        assertEquals(Integer.valueOf(7), appointment.getNumericalOrder());
    }

    // Test Case ID: TC-MODEL-APPOINT-021
    @Test
    public void appointmentPositionSetter() {
        Appointment appointment = new Appointment();
        appointment.setPosition(12);
        assertEquals(Integer.valueOf(12), appointment.getPosition());
    }

    // Test Case ID: TC-MODEL-APPOINT-022
    @Test
    public void patientIdSetter() {
        Appointment appointment = new Appointment();
        appointment.setPatientId(55);
        assertEquals(Integer.valueOf(55), appointment.getPatientId());
    }

    // Test Case ID: TC-MODEL-APPOINT-023
    @Test
    public void patientNameSetter() {
        Appointment appointment = new Appointment();
        appointment.setPatientName("Le Thi C");
        assertEquals("Le Thi C", appointment.getPatientName());
    }

    // Test Case ID: TC-MODEL-APPOINT-024
    @Test
    public void patientPhoneSetter() {
        Appointment appointment = new Appointment();
        appointment.setPatientPhone("0987654321");
        assertEquals("0987654321", appointment.getPatientPhone());
    }

    // Test Case ID: TC-MODEL-APPOINT-025
    @Test
    public void patientBirthdaySetter() {
        Appointment appointment = new Appointment();
        appointment.setPatientBirthday("1995-03-20");
        assertEquals("1995-03-20", appointment.getPatientBirthday());
    }

    // Test Case ID: TC-MODEL-APPOINT-026
    @Test
    public void patientReasonSetter() {
        Appointment appointment = new Appointment();
        appointment.setPatientReason("Fever and cough");
        assertEquals("Fever and cough", appointment.getPatientReason());
    }

    // Test Case ID: TC-MODEL-APPOINT-027
    @Test
    public void appointmentTimeSetter() {
        Appointment appointment = new Appointment();
        appointment.setAppointmentTime("14:30");
        assertEquals("14:30", appointment.getAppointmentTime());
    }

    // Test Case ID: TC-MODEL-APPOINT-028
    @Test
    public void statusSetter() {
        Appointment appointment = new Appointment();
        appointment.setStatus("pending");
        assertEquals("pending", appointment.getStatus());
    }

    // Test Case ID: TC-MODEL-APPOINT-029
    @Test
    public void createAtSetter() {
        Appointment appointment = new Appointment();
        appointment.setCreateAt("2026-01-01T10:00:00Z");
        assertEquals("2026-01-01T10:00:00Z", appointment.getCreateAt());
    }

    // Test Case ID: TC-MODEL-APPOINT-030
    @Test
    public void updateAtSetter() {
        Appointment appointment = new Appointment();
        appointment.setUpdateAt("2026-05-11T15:30:00Z");
        assertEquals("2026-05-11T15:30:00Z", appointment.getUpdateAt());
    }

    // Test Case ID: TC-MODEL-APPOINT-031
    @Test
    public void doctorSetterViaGson() {
        String json = "{\"doctor\": {\"id\": 8, \"name\": \"Dr. Minh\", \"email\": \"minh@test.com\"}}";
        Appointment appointment = gson.fromJson(json, Appointment.class);
        assertNotNull(appointment.getDoctor());
        assertEquals("Dr. Minh", appointment.getDoctor().getName());
    }

    // Test Case ID: TC-MODEL-APPOINT-032
    @Test
    public void specialitySetterViaGson() {
        String json = "{\"speciality\": {\"id\": 3, \"name\": \"Neurology\"}}";
        Appointment appointment = gson.fromJson(json, Appointment.class);
        assertNotNull(appointment.getSpeciality());
        assertEquals("Neurology", appointment.getSpeciality().getName());
    }

    // Test Case ID: TC-MODEL-APPOINT-033
    @Test
    public void roomSetterViaGson() {
        String json = "{\"room\": {\"id\": 5, \"name\": \"Room 202\", \"location\": \"Floor 3\"}}";
        Appointment appointment = gson.fromJson(json, Appointment.class);
        assertNotNull(appointment.getRoom());
        assertEquals("Room 202", appointment.getRoom().getName());
        assertEquals("Floor 3", appointment.getRoom().getLocation());
    }

    // Test Case ID: TC-MODEL-APPOINT-034
    // Verify all patient-related fields can be set and retrieved correctly together.
    @Test
    public void allPatientFieldsSetter() {
        Appointment appointment = new Appointment();
        appointment.setPatientId(100);
        appointment.setPatientName("Pham Van D");
        appointment.setPatientPhone("0909123456");
        appointment.setPatientBirthday("1988-07-15");
        appointment.setPatientReason("Back pain");

        assertEquals(Integer.valueOf(100), appointment.getPatientId());
        assertEquals("Pham Van D", appointment.getPatientName());
        assertEquals("0909123456", appointment.getPatientPhone());
        assertEquals("1988-07-15", appointment.getPatientBirthday());
        assertEquals("Back pain", appointment.getPatientReason());
    }

    // Test Case ID: TC-MODEL-APPOINT-035
    // Verify all metadata fields can be set and retrieved correctly.
    @Test
    public void allMetadataFieldsSetter() {
        Appointment appointment = new Appointment();
        appointment.setId(5);
        appointment.setDate("2026-07-01");
        appointment.setNumericalOrder(2);
        appointment.setPosition(3);
        appointment.setAppointmentTime("11:00");
        appointment.setStatus("done");
        appointment.setCreateAt("2026-07-01T08:00:00Z");
        appointment.setUpdateAt("2026-07-01T12:00:00Z");

        assertEquals(Integer.valueOf(5), appointment.getId());
        assertEquals("2026-07-01", appointment.getDate());
        assertEquals(Integer.valueOf(2), appointment.getNumericalOrder());
        assertEquals(Integer.valueOf(3), appointment.getPosition());
        assertEquals("11:00", appointment.getAppointmentTime());
        assertEquals("done", appointment.getStatus());
        assertEquals("2026-07-01T08:00:00Z", appointment.getCreateAt());
        assertEquals("2026-07-01T12:00:00Z", appointment.getUpdateAt());
    }

    // =============================================================================
    // NESTED MODEL COVERAGE — Doctor, Room, Speciality getters via Appointment
    // =============================================================================

    // Test Case ID: TC-MODEL-APPOINT-036
    // Coverage for Room.getId() and all Doctor fields embedded in Appointment.
    @Test
    public void fullDoctorAndRoomFieldsParsing() {
        String json = "{\n" +
                "  \"room\": {\"id\": 7, \"name\": \"Room 303\", \"location\": \"Floor 4\"},\n" +
                "  \"doctor\": {\n" +
                "    \"id\": 12,\n" +
                "    \"email\": \"dr.hoa@hospital.com\",\n" +
                "    \"phone\": \"0901234567\",\n" +
                "    \"name\": \"Dr. Hoa\",\n" +
                "    \"description\": \"Expert in cardiology\",\n" +
                "    \"price\": \"250000\",\n" +
                "    \"role\": \"doctor\",\n" +
                "    \"avatar\": \"https://example.com/avatar.jpg\",\n" +
                "    \"active\": \"1\",\n" +
                "    \"create_at\": \"2025-01-01\",\n" +
                "    \"update_at\": \"2026-01-01\"\n" +
                "  }\n" +
                "}";

        Appointment appointment = gson.fromJson(json, Appointment.class);

        assertNotNull(appointment.getRoom());
        assertEquals(7, appointment.getRoom().getId());
        assertEquals("Room 303", appointment.getRoom().getName());
        assertEquals("Floor 4", appointment.getRoom().getLocation());

        assertNotNull(appointment.getDoctor());
        assertEquals(12, appointment.getDoctor().getId());
        assertEquals("dr.hoa@hospital.com", appointment.getDoctor().getEmail());
        assertEquals("0901234567", appointment.getDoctor().getPhone());
        assertEquals("Dr. Hoa", appointment.getDoctor().getName());
        assertEquals("Expert in cardiology", appointment.getDoctor().getDescription());
        assertEquals("250000", appointment.getDoctor().getPrice());
        assertEquals("doctor", appointment.getDoctor().getRole());
        assertEquals("https://example.com/avatar.jpg", appointment.getDoctor().getAvatar());
        assertEquals("1", appointment.getDoctor().getActive());
        assertEquals("2025-01-01", appointment.getDoctor().getCreateAt());
        assertEquals("2026-01-01", appointment.getDoctor().getUpdateAt());
    }

    // Test Case ID: TC-MODEL-APPOINT-037
    // Coverage for Speciality.getDoctorQuantity() and Speciality.getImage() via Appointment.
    @Test
    public void fullSpecialityFieldsParsing() {
        String json = "{\n" +
                "  \"speciality\": {\n" +
                "    \"id\": 9,\n" +
                "    \"name\": \"Dermatology\",\n" +
                "    \"description\": \"Skin and cosmetic treatments\",\n" +
                "    \"doctor_quantity\": 5,\n" +
                "    \"image\": \"https://example.com/dermatology.jpg\"\n" +
                "  }\n" +
                "}";

        Appointment appointment = gson.fromJson(json, Appointment.class);

        assertNotNull(appointment.getSpeciality());
        assertEquals(9, appointment.getSpeciality().getId());
        assertEquals("Dermatology", appointment.getSpeciality().getName());
        assertEquals("Skin and cosmetic treatments", appointment.getSpeciality().getDescription());
        assertEquals("https://example.com/dermatology.jpg", appointment.getSpeciality().getImage());
    }

    // Test Case ID: TC-MODEL-APPOINT-038
    // Coverage: deeply nested speciality and room within Doctor object.
    @Test
    public void deeplyNestedSpecialityAndRoomParsing() {
        String json = "{\n" +
                "  \"doctor\": {\n" +
                "    \"id\": 3,\n" +
                "    \"name\": \"Dr. Nam\",\n" +
                "    \"speciality\": {\"id\": 4, \"name\": \"Orthopedics\"},\n" +
                "    \"room\": {\"id\": 2, \"name\": \"Room 102\", \"location\": \"Floor 1\"}\n" +
                "  }\n" +
                "}";

        Appointment appointment = gson.fromJson(json, Appointment.class);

        assertNotNull(appointment.getDoctor().getSpeciality());
        assertEquals(4, appointment.getDoctor().getSpeciality().getId());
        assertEquals("Orthopedics", appointment.getDoctor().getSpeciality().getName());

        assertNotNull(appointment.getDoctor().getRoom());
        assertEquals(2, appointment.getDoctor().getRoom().getId());
        assertEquals("Room 102", appointment.getDoctor().getRoom().getName());
        assertEquals("Floor 1", appointment.getDoctor().getRoom().getLocation());
    }

    // Test Case ID: TC-MODEL-APPOINT-039
    // Coverage: null nested speciality in Appointment returns null (no NPE).
    @Test
    public void nullSpeciality() {
        String json = "{\"speciality\": null}";
        Appointment appointment = gson.fromJson(json, Appointment.class);
        assertNull(appointment.getSpeciality());
    }

    // Test Case ID: TC-MODEL-APPOINT-040
    // Coverage: null nested doctor and room in Appointment returns null (no NPE).
    @Test
    public void nullDoctorAndRoom() {
        String json = "{\"doctor\": null, \"room\": null}";
        Appointment appointment = gson.fromJson(json, Appointment.class);
        assertNull(appointment.getDoctor());
        assertNull(appointment.getRoom());
    }

    // =============================================================================
    // DIRECT SETTER COVERAGE — setDoctor, setSpeciality, setRoom
    // TC-MODEL-APPOINT-031/032/033 only cover these via Gson deserialization.
    // These tests call the setters directly to cover the setter method bodies.
    // =============================================================================

    // Test Case ID: TC-MODEL-APPOINT-041
    // Direct setter coverage: setDoctor(Doctor) body (line 181) is exercised.
    // Doctor has no setters — construct via Gson, then pass into Appointment.setDoctor().
    @Test
    public void doctorSetterDirectly() {
        String doctorJson = "{\"id\": 15, \"name\": \"Dr. Quan\", \"email\": \"quan@hospital.com\", \"phone\": \"0912345678\", \"role\": \"doctor\", \"active\": \"1\"}";
        Doctor doctor = gson.fromJson(doctorJson, Doctor.class);

        Appointment appointment = new Appointment();
        appointment.setDoctor(doctor);

        assertNotNull(appointment.getDoctor());
        assertEquals("Dr. Quan", appointment.getDoctor().getName());
        assertEquals("quan@hospital.com", appointment.getDoctor().getEmail());
        assertEquals("0912345678", appointment.getDoctor().getPhone());
    }

    // Test Case ID: TC-MODEL-APPOINT-042
    // Direct setter coverage: setSpeciality(Speciality) body (line 189) is exercised.
    // Speciality has no setters — construct via Gson, then pass into Appointment.setSpeciality().
    @Test
    public void specialitySetterDirectly() {
        String specialityJson = "{\"id\": 11, \"name\": \"Pediatrics\", \"description\": \"Child healthcare\", \"doctor_quantity\": 8, \"image\": \"https://example.com/pediatrics.jpg\"}";
        Speciality speciality = gson.fromJson(specialityJson, Speciality.class);

        Appointment appointment = new Appointment();
        appointment.setSpeciality(speciality);

        assertNotNull(appointment.getSpeciality());
        assertEquals("Pediatrics", appointment.getSpeciality().getName());
        assertEquals("Child healthcare", appointment.getSpeciality().getDescription());
        assertEquals("https://example.com/pediatrics.jpg", appointment.getSpeciality().getImage());
    }

    // Test Case ID: TC-MODEL-APPOINT-043
    // Direct setter coverage: setRoom(Room) body (line 197) is exercised.
    // Room has no setters — construct via Gson, then pass into Appointment.setRoom().
    @Test
    public void roomSetterDirectly() {
        String roomJson = "{\"id\": 20, \"name\": \"Room 501\", \"location\": \"Floor 5\"}";
        Room room = gson.fromJson(roomJson, Room.class);

        Appointment appointment = new Appointment();
        appointment.setRoom(room);

        assertNotNull(appointment.getRoom());
        assertEquals("Room 501", appointment.getRoom().getName());
        assertEquals("Floor 5", appointment.getRoom().getLocation());
    }

    // Test Case ID: TC-MODEL-APPOINT-044
    // Chain: set all three nested objects via setters directly.
    // Each object is constructed via Gson (no setters in Doctor/Room/Speciality),
    // then passed into the corresponding Appointment setter — covering lines 181, 189, 197.
    @Test
    public void allNestedObjectsSettersDirectly() {
        Doctor doctor = gson.fromJson("{\"id\": 7, \"name\": \"Dr. Lan\"}", Doctor.class);
        Speciality speciality = gson.fromJson("{\"id\": 3, \"name\": \"Oncology\"}", Speciality.class);
        Room room = gson.fromJson("{\"id\": 9, \"name\": \"Room 111\", \"location\": \"Floor 1\"}", Room.class);

        Appointment appointment = new Appointment();
        appointment.setDoctor(doctor);
        appointment.setSpeciality(speciality);
        appointment.setRoom(room);

        assertNotNull(appointment.getDoctor());
        assertEquals("Dr. Lan", appointment.getDoctor().getName());

        assertNotNull(appointment.getSpeciality());
        assertEquals("Oncology", appointment.getSpeciality().getName());

        assertNotNull(appointment.getRoom());
        assertEquals("Room 111", appointment.getRoom().getName());
        assertEquals("Floor 1", appointment.getRoom().getLocation());
    }
}
