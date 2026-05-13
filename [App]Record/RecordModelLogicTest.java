package com.example.do_an_tot_nghiep.Model;

import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertFalse;
import static org.junit.Assert.assertNotEquals;
import static org.junit.Assert.assertNotNull;
import static org.junit.Assert.assertTrue;

import com.google.gson.Gson;
import com.google.gson.GsonBuilder;

import org.junit.Before;
import org.junit.Test;

/**
 * Unit tests for Record model class.
 *
 * Strategy: Record has no setters (immutable via Gson). All tests use
 * Gson.fromJson() to construct objects from JSON strings — matching how
 * Retrofit deserializes API responses in the real app.
 *
 * NOTE:
 * - PASS tests validate current implemented behavior.
 * - FAIL tests highlight real missing validation/business-rule guards.
 */
public class RecordModelLogicTest {

    private Gson gson;

    @Before
    public void setUp() {
        gson = new GsonBuilder().setLenient().create();
    }

    // =============================================================================
    // RECORD MODEL - PASS tests (current behavior is correct)
    // =============================================================================

    // Test Case ID: TC-MODEL-REC-001
    // Valid record with all required fields parses correctly.
    @Test
    public void validRecordParsing() {
        String json = "{\n" +
                "  \"id\": 11,\n" +
                "  \"reason\": \"Checkup\",\n" +
                "  \"description\": \"Patient shows symptoms of fatigue\",\n" +
                "  \"status_before\": \"new\",\n" +
                "  \"status_after\": \"done\",\n" +
                "  \"create_at\": \"2026-05-11 08:00:00\",\n" +
                "  \"update_at\": \"2026-05-11 08:30:00\"\n" +
                "}";

        Record record = gson.fromJson(json, Record.class);

        assertEquals(Integer.valueOf(11), Integer.valueOf(record.getId()));
        assertEquals("Checkup", record.getReason());
        assertEquals("Patient shows symptoms of fatigue", record.getDescription());
        assertEquals("new", record.getStatusBefore());
        assertEquals("done", record.getStatusAfter());
    }

    // Test Case ID: TC-MODEL-REC-002
    // Nested objects (appointment, doctor, speciality) parse correctly.
    @Test
    public void recordNestedObjectsParsing() {
        String json = "{\n" +
                "  \"id\": 11,\n" +
                "  \"appointment\": { \"id\": 10, \"patient_name\": \"Nguyen A\" },\n" +
                "  \"doctor\": { \"id\": 5, \"name\": \"Dr. Cuong\", \"email\": \"cuong@test.com\" },\n" +
                "  \"speciality\": { \"id\": 2, \"name\": \"General\" }\n" +
                "}";

        Record record = gson.fromJson(json, Record.class);

        assertNotNull(record.getAppointment());
        assertEquals(Integer.valueOf(10), Integer.valueOf(record.getAppointment().getId()));

        assertNotNull(record.getDoctor());
        assertEquals("Dr. Cuong", record.getDoctor().getName());

        assertNotNull(record.getSpeciality());
        assertEquals("General", record.getSpeciality().getName());
    }

    // =============================================================================
    // RECORD MODEL - FAIL tests (real bugs / missing validation)
    // =============================================================================

    // Test Case ID: TC-MODEL-REC-003
    // Real issue: null description is accepted silently. Null description propagated to UI/WebView
    // will cause unexpected behavior. Should be normalized to empty string.
    @Test
    public void nullDescription() {
        String json = "{\"description\": null}";
        Record record = gson.fromJson(json, Record.class);

        // Fails: Gson maps null JSON field to Java null; normalization is missing.
        assertNotNull(record.getDescription());
        assertFalse("Description should not be null or empty",
                record.getDescription().trim().isEmpty());
    }

    // Test Case ID: TC-MODEL-REC-004
    // Real issue: null reason is accepted. Reason is a key field and should not be null.
    @Test
    public void nullReason() {
        String json = "{\"reason\": null}";
        Record record = gson.fromJson(json, Record.class);

        // Fails: null reason is accepted; validation is missing.
        assertNotNull(record.getReason());
        assertFalse("Reason should not be null or empty",
                record.getReason().trim().isEmpty());
    }

    // Test Case ID: TC-MODEL-REC-005
    // Real issue: statusAfter can be set before statusBefore in JSON order, creating illogical
    // state. Valid progression: new -> processing -> done. Regressing without intervention is invalid.
    @Test
    public void invalidStatusProgression() {
        // statusBefore = "done" (later stage), statusAfter = "new" (earlier stage) = regression
        String json = "{\"status_before\": \"done\", \"status_after\": \"new\"}";
        Record record = gson.fromJson(json, Record.class);

        // Fails: any status combination is accepted; validation is missing.
        boolean isValidProgression = isValidStatusProgression(
                record.getStatusBefore(), record.getStatusAfter());
        assertTrue("Status progression should be valid (before -> after)", isValidProgression);
    }

    // Test Case ID: TC-MODEL-REC-006
    // Real issue: statusBefore and statusAfter accept arbitrary strings.
    // Valid status values should be constrained to known medical statuses.
    @Test
    public void invalidRecordStatus() {
        String json1 = "{\"status_before\": \"invalid_before\", \"status_after\": \"invalid_after\"}";
        String json2 = "{\"status_before\": \"DONE\", \"status_after\": \"NEW\"}";  // wrong case

        Record r1 = gson.fromJson(json1, Record.class);
        Record r2 = gson.fromJson(json2, Record.class);

        // Fails: arbitrary strings accepted; validation is missing.
        assertTrue("statusBefore should be a valid status",
                isKnownStatus(r1.getStatusBefore()) || isKnownStatus(r2.getStatusBefore()));
        assertTrue("statusAfter should be a valid status",
                isKnownStatus(r1.getStatusAfter()) || isKnownStatus(r2.getStatusAfter()));
    }

    // Test Case ID: TC-MODEL-REC-007
    // Real issue: createAt in the future is accepted. A record createdAt in the future is impossible.
    @Test
    public void futureCreateAt() {
        String json1 = "{\"create_at\": \"2099-12-31 23:59:59\"}";  // far future
        String json2 = "{\"create_at\": \"2030-01-01 00:00:00\"}";  // near future

        Record r1 = gson.fromJson(json1, Record.class);
        Record r2 = gson.fromJson(json2, Record.class);

        // Fails: future dates are accepted; validation is missing.
        assertFalse("Far future createAt should be rejected", isFutureDate(r1.getCreateAt()));
        assertFalse("Near future createAt should be rejected", isFutureDate(r2.getCreateAt()));
    }

    // Test Case ID: TC-MODEL-REC-008
    // Real issue: updateAt before createAt is accepted — logically impossible.
    @Test
    public void updateAtBeforeCreateAt() {
        String json = "{\n" +
                "  \"create_at\": \"2026-05-11 10:00:00\",\n" +
                "  \"update_at\": \"2026-05-10 08:00:00\"\n" +  // one day before createAt
                "}";

        Record record = gson.fromJson(json, Record.class);

        // Fails: updateAt before createAt is accepted; validation is missing.
        assertNotNull(record.getCreateAt());
        assertNotNull(record.getUpdateAt());
        assertFalse("updateAt should not be before createAt",
                record.getUpdateAt().compareTo(record.getCreateAt()) < 0);
    }

    // Test Case ID: TC-MODEL-REC-009
    // Real issue: zero or negative ID is accepted. Record ID from database should always be positive.
    @Test
    public void invalidRecordId() {
        String json1 = "{\"id\": 0}";
        String json2 = "{\"id\": -1}";

        Record r1 = gson.fromJson(json1, Record.class);
        Record r2 = gson.fromJson(json2, Record.class);

        // Fails: invalid IDs are accepted; validation is missing.
        assertTrue("Record ID should be positive", r1.getId() > 0);
        assertTrue("Record ID should be positive", r2.getId() > 0);
    }

    // Test Case ID: TC-MODEL-REC-010
    // Real issue: null nested objects are accepted without normalization.
    // Accessing these in UI code will cause NullPointerException.
    @Test
    public void recordNullNestedObjects() {
        String json = "{\n" +
                "  \"appointment\": null,\n" +
                "  \"doctor\": null,\n" +
                "  \"speciality\": null\n" +
                "}";

        Record record = gson.fromJson(json, Record.class);

        // Fails: null objects are stored directly; normalization is missing.
        assertNotNull("Appointment should not be null", record.getAppointment());
        assertNotNull("Doctor should not be null", record.getDoctor());
        assertNotNull("Speciality should not be null", record.getSpeciality());
    }

    // =============================================================================
    // BOUNDARY value tests
    // =============================================================================

    // Test Case ID: TC-MODEL-REC-011
    // Boundary value: very long description text (10000 chars) is preserved.
    @Test
    public void largeDescription() {
        StringBuilder sb = new StringBuilder();
        for (int i = 0; i < 10000; i++) {
            sb.append("x");
        }
        String json = "{\"description\": \"" + sb + "\"}";
        Record record = gson.fromJson(json, Record.class);

        assertEquals(sb.toString(), record.getDescription());
    }

    // Test Case ID: TC-MODEL-REC-012
    // Boundary value: empty strings for reason and description should be normalized.
    @Test
    public void emptyReasonAndDescription() {
        String json = "{\"reason\": \"\", \"description\": \"\"}";
        Record record = gson.fromJson(json, Record.class);

        // Fails: empty strings are accepted; normalization is missing.
        assertNotNull(record.getReason());
        assertNotNull(record.getDescription());
        assertFalse("Reason should not be empty", record.getReason().trim().isEmpty());
        assertFalse("Description should not be empty", record.getDescription().trim().isEmpty());
    }

    // Test Case ID: TC-MODEL-REC-013
    // Boundary value: very long reason text (1000 repetitions) is preserved.
    @Test
    public void largeReason() {
        StringBuilder sb = new StringBuilder();
        for (int i = 0; i < 1000; i++) {
            sb.append("Reason-");
        }
        String json = "{\"reason\": \"" + sb + "\"}";
        Record record = gson.fromJson(json, Record.class);

        assertEquals(sb.toString(), record.getReason());
    }

    // =============================================================================
    // Helper methods for validation logic
    // =============================================================================

    /**
     * Checks if a status string is one of the known medical record statuses.
     */
    private boolean isKnownStatus(String status) {
        if (status == null) return false;
        return "new".equals(status) ||
               "pending".equals(status) ||
               "processing".equals(status) ||
               "done".equals(status) ||
               "cancelled".equals(status);
    }

    /**
     * Validates that the status progression from before to after is logical.
     * Valid progression: new -> processing -> done
     * Going backwards (done -> new) without specific reason is invalid.
     */
    private boolean isValidStatusProgression(String statusBefore, String statusAfter) {
        if (statusBefore == null || statusAfter == null) return false;

        int beforeOrder = statusOrder(statusBefore);
        int afterOrder = statusOrder(statusAfter);

        // Invalid if after comes before before (regression)
        if (afterOrder < beforeOrder) return false;

        // Also invalid if either status is unknown
        if (beforeOrder == -1 || afterOrder == -1) return false;

        return true;
    }

    /**
     * Returns numeric order for status values to enable comparison.
     * Higher number = later in the treatment lifecycle.
     */
    private int statusOrder(String status) {
        if (status == null) return -1;
        switch (status) {
            case "new":         return 1;
            case "pending":      return 2;
            case "processing":   return 3;
            case "done":         return 4;
            case "cancelled":    return 0;
            default:             return -1; // unknown
        }
    }

    /**
     * Checks if a datetime string represents a future date (year > 2026).
     */
    private boolean isFutureDate(String dateTime) {
        if (dateTime == null || dateTime.isEmpty()) return false;
        try {
            String yearStr = dateTime.substring(0, 4);
            int year = Integer.parseInt(yearStr);
            return year > 2026;
        } catch (Exception e) {
            return false;
        }
    }
}
