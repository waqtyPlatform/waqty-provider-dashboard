<?php

declare(strict_types=1);

namespace App\Services\Waqty;

/**
 * HR / org-structure endpoints for the Employees module (src/lib/api.ts:
 * departmentApi, positionApi, roleApi, employeeTransferApi, availabilityApi,
 * payrollApi, commissionApi, deductionApi, targetApi, performanceApi,
 * scheduleApi, timeTrackingApi, attendanceApi, fingerprintApi).
 *
 * Read endpoints return raw associative-array rows (each screen shapes its own
 * columns); screens fall back to local Arabic sample data when the API is down.
 * Kept separate from EmployeeService (core CRUD) to isolate the HR surface.
 */
class EmployeeHrService
{
    public function __construct(private readonly WaqtyApiClient $api) {}

    // -- Departments ------------------------------------------------------
    /** @return array<int, mixed> */
    public function departments(): array
    {
        return $this->rows($this->api->get('/api/provider/departments'));
    }

    /** @param array<string, mixed> $d */
    public function createDepartment(array $d): mixed
    {
        return $this->api->post('/api/provider/departments', $d);
    }

    /** @param array<string, mixed> $d */
    public function updateDepartment(string $uuid, array $d): mixed
    {
        return $this->api->put("/api/provider/departments/{$uuid}", $d);
    }

    public function deleteDepartment(string $uuid): mixed
    {
        return $this->api->delete("/api/provider/departments/{$uuid}");
    }

    // -- Positions --------------------------------------------------------
    /** @return array<int, mixed> */
    public function positions(): array
    {
        return $this->rows($this->api->get('/api/provider/positions'));
    }

    /** @param array<string, mixed> $d */
    public function createPosition(array $d): mixed
    {
        return $this->api->post('/api/provider/positions', $d);
    }

    /** @param array<string, mixed> $d */
    public function updatePosition(string $uuid, array $d): mixed
    {
        return $this->api->put("/api/provider/positions/{$uuid}", $d);
    }

    public function deletePosition(string $uuid): mixed
    {
        return $this->api->delete("/api/provider/positions/{$uuid}");
    }

    // -- Roles (permission matrix) ---------------------------------------
    /** @return array<int, mixed> */
    public function roles(): array
    {
        return $this->rows($this->api->get('/api/provider/roles'));
    }

    /** @param array<string, mixed> $d */
    public function createRole(array $d): mixed
    {
        return $this->api->post('/api/provider/roles', $d);
    }

    /** @param array<string, mixed> $d */
    public function updateRole(string $uuid, array $d): mixed
    {
        return $this->api->put("/api/provider/roles/{$uuid}", $d);
    }

    public function deleteRole(string $uuid): mixed
    {
        return $this->api->delete("/api/provider/roles/{$uuid}");
    }

    // -- Transfers --------------------------------------------------------
    /** @param array<string, mixed> $filters @return array<int, mixed> */
    public function transfers(array $filters = []): array
    {
        return $this->rows($this->api->get('/api/provider/employee-transfers', $filters));
    }

    /** @param array<string, mixed> $d */
    public function createTransfer(array $d): mixed
    {
        return $this->api->post('/api/provider/employee-transfers', $d);
    }

    public function approveTransfer(string $uuid): mixed
    {
        return $this->api->patch("/api/provider/employee-transfers/{$uuid}/approve");
    }

    public function rejectTransfer(string $uuid, string $reason = ''): mixed
    {
        return $this->api->patch("/api/provider/employee-transfers/{$uuid}/reject", ['reason' => $reason]);
    }

    // -- Availability -----------------------------------------------------
    /** @param array<string, mixed> $filters @return array<int, mixed> */
    public function availability(array $filters = []): array
    {
        return $this->rows($this->api->get('/api/provider/availability', $filters));
    }

    // -- Payroll ----------------------------------------------------------
    /** @param array<string, mixed> $filters @return array<int, mixed> */
    public function payroll(array $filters = []): array
    {
        return $this->rows($this->api->get('/api/provider/payroll', $filters));
    }

    /** @param array<string, mixed> $d */
    public function generatePayroll(array $d): mixed
    {
        return $this->api->post('/api/provider/payroll/generate', $d);
    }

    public function approvePayroll(string $uuid): mixed
    {
        return $this->api->patch("/api/provider/payroll/{$uuid}/approve");
    }

    /** @param array<string, mixed> $d */
    public function payPayroll(string $uuid, array $d): mixed
    {
        return $this->api->patch("/api/provider/payroll/{$uuid}/pay", $d);
    }

    // -- Commissions ------------------------------------------------------
    /** @param array<string, mixed> $filters @return array<int, mixed> */
    public function commissions(array $filters = []): array
    {
        return $this->rows($this->api->get('/api/provider/commissions', $filters));
    }

    /** @param array<string, mixed> $d */
    public function calculateCommissions(array $d): mixed
    {
        return $this->api->post('/api/provider/commissions/calculate', $d);
    }

    /** @return array<int, mixed> */
    public function commissionRules(): array
    {
        return $this->rows($this->api->get('/api/provider/commission-rules'));
    }

    /** @param array<string, mixed> $d */
    public function createCommissionRule(array $d): mixed
    {
        return $this->api->post('/api/provider/commission-rules', $d);
    }

    /** @param array<string, mixed> $d */
    public function updateCommissionRule(string $uuid, array $d): mixed
    {
        return $this->api->put("/api/provider/commission-rules/{$uuid}", $d);
    }

    public function deleteCommissionRule(string $uuid): mixed
    {
        return $this->api->delete("/api/provider/commission-rules/{$uuid}");
    }

    // -- Deductions -------------------------------------------------------
    /** @param array<string, mixed> $filters @return array<int, mixed> */
    public function deductions(array $filters = []): array
    {
        return $this->rows($this->api->get('/api/provider/deductions', $filters));
    }

    /** @param array<string, mixed> $d */
    public function createDeduction(array $d): mixed
    {
        return $this->api->post('/api/provider/deductions', $d);
    }

    /** @param array<string, mixed> $d */
    public function updateDeduction(string $uuid, array $d): mixed
    {
        return $this->api->put("/api/provider/deductions/{$uuid}", $d);
    }

    public function deleteDeduction(string $uuid): mixed
    {
        return $this->api->delete("/api/provider/deductions/{$uuid}");
    }

    // -- Targets ----------------------------------------------------------
    /** @return array<int, mixed> */
    public function targets(): array
    {
        return $this->rows($this->api->get('/api/provider/employee-targets'));
    }

    /** @param array<string, mixed> $d */
    public function createTarget(array $d): mixed
    {
        return $this->api->post('/api/provider/employee-targets', $d);
    }

    /** @param array<string, mixed> $d */
    public function updateTarget(string $uuid, array $d): mixed
    {
        return $this->api->put("/api/provider/employee-targets/{$uuid}", $d);
    }

    // -- Performance ------------------------------------------------------
    /** @param array<string, mixed> $filters @return array<int, mixed> */
    public function performance(array $filters = []): array
    {
        return $this->rows($this->api->get('/api/provider/employee-performance', $filters));
    }

    // -- Branch management (rosters) -------------------------------------
    /** @return array<int, mixed> */
    public function branches(): array
    {
        return $this->rows($this->api->get('/api/provider/branches'));
    }

    // -- Schedule / shifts ------------------------------------------------
    /** @param array<string, mixed> $filters @return array<int, mixed> */
    public function shifts(array $filters = []): array
    {
        return $this->rows($this->api->get('/api/provider/shifts', $filters));
    }

    /** @param array<string, mixed> $d */
    public function createShift(array $d): mixed
    {
        return $this->api->post('/api/provider/shifts', $d);
    }

    /** @param array<string, mixed> $d */
    public function updateShift(string $uuid, array $d): mixed
    {
        return $this->api->put("/api/provider/shifts/{$uuid}", $d);
    }

    // -- Time tracking ----------------------------------------------------
    /** @param array<string, mixed> $filters @return array<int, mixed> */
    public function timeTracking(array $filters = []): array
    {
        return $this->rows($this->api->get('/api/provider/time-tracking', $filters));
    }

    /** @param array<string, mixed> $d */
    public function createTimeEntry(array $d): mixed
    {
        return $this->api->post('/api/provider/time-tracking', $d);
    }

    /** @param array<string, mixed> $d */
    public function updateTimeEntry(string $uuid, array $d): mixed
    {
        return $this->api->put("/api/provider/time-tracking/{$uuid}", $d);
    }

    // -- Attendance -------------------------------------------------------
    /** @param array<string, mixed> $filters @return array<int, mixed> */
    public function attendance(array $filters = []): array
    {
        return $this->rows($this->api->get('/api/provider/attendance', $filters));
    }

    /** @param array<string, mixed> $d */
    public function addManualAttendance(array $d): mixed
    {
        return $this->api->post('/api/provider/attendance/add-manual', $d);
    }

    /** @param array<string, mixed> $d */
    public function updateAttendance(string $uuid, array $d): mixed
    {
        return $this->api->put("/api/provider/attendance/{$uuid}", $d);
    }

    public function deleteAttendance(string $uuid): mixed
    {
        return $this->api->delete("/api/provider/attendance/{$uuid}");
    }

    // -- Attendance methods & settings -----------------------------------
    /** @return array<int, mixed> */
    public function attendanceMethods(): array
    {
        return $this->rows($this->api->get('/api/provider/attendance-methods'));
    }

    public function toggleAttendanceMethod(string $uuid): mixed
    {
        return $this->api->patch("/api/provider/attendance-methods/{$uuid}");
    }

    /** @param array<string, mixed> $d */
    public function updateAttendanceMethod(string $uuid, array $d): mixed
    {
        return $this->api->put("/api/provider/attendance-methods/{$uuid}", $d);
    }

    /** @return array<string, mixed> */
    public function attendanceSettings(): array
    {
        $data = $this->api->get('/api/provider/settings/attendance');

        return is_array($data) ? $data : [];
    }

    /** @param array<string, mixed> $d */
    public function updateAttendanceSettings(array $d): mixed
    {
        return $this->api->patch('/api/provider/settings/attendance', $d);
    }

    // -- Fingerprints -----------------------------------------------------
    /** @return array<int, mixed> */
    public function fingerprints(): array
    {
        return $this->rows($this->api->get('/api/provider/fingerprints'));
    }

    /** @param array<string, mixed> $d */
    public function enrollFingerprint(array $d): mixed
    {
        return $this->api->post('/api/provider/fingerprints/enroll', $d);
    }

    /** @param array<string, mixed> $d */
    public function reenrollFingerprint(string $uuid, array $d): mixed
    {
        return $this->api->put("/api/provider/fingerprints/{$uuid}", $d);
    }

    public function clearFingerprint(string $uuid): mixed
    {
        return $this->api->delete("/api/provider/fingerprints/{$uuid}");
    }

    // -- Single employee (detail) ----------------------------------------
    /** @return array<string, mixed> */
    public function employee(string $uuid): array
    {
        $data = $this->api->get("/api/provider/employees/{$uuid}");

        return is_array($data) ? $data : [];
    }

    /** @return array<int, mixed> */
    private function rows(mixed $data): array
    {
        if (is_array($data) && isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }

        return is_array($data) ? $data : [];
    }
}
