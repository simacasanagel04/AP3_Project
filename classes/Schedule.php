<?php
// /classes/Schedule.php
class Schedule {
    private $conn;
    private $table = "schedule";
    private $table_doctor = "doctor";

    public function __construct($db) {
        $this->conn = $db;
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // Get available dates for a specialization (checks all doctors in that specialization)
    public function getAvailableDatesBySpecialization($spec_id, $days = 30) {
        try {
            $sql = "SELECT DISTINCT s.SCHED_DAYS, s.SCHED_START_TIME, s.SCHED_END_TIME 
                    FROM {$this->table} s 
                    INNER JOIN doctor d ON s.DOC_ID = d.DOC_ID 
                    WHERE d.SPEC_ID = :spec_id 
                      AND s.SCHED_DAYS >= CURDATE() 
                      AND s.SCHED_DAYS <= DATE_ADD(CURDATE(), INTERVAL :days DAY) 
                    ORDER BY s.SCHED_DAYS";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':spec_id' => $spec_id,
                ':days' => $days
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAvailableDatesBySpecialization(): " . $e->getMessage());
            return [];
        }
    }

    // Get available doctors for a specific date and specialization
    public function getAvailableDoctors($spec_id, $date) {
        try {
            $sql = "SELECT 
                        d.DOC_ID,
                        d.DOC_FIRST_NAME,
                        d.DOC_LAST_NAME,
                        d.DOC_MIDDLE_INIT,
                        s.SCHED_START_TIME,
                        s.SCHED_END_TIME,
                        CONCAT(d.DOC_LAST_NAME, ', ', d.DOC_FIRST_NAME, ' ', COALESCE(d.DOC_MIDDLE_INIT, '')) as doctor_name
                    FROM doctor d
                    INNER JOIN {$this->table} s ON d.DOC_ID = s.DOC_ID
                    WHERE d.SPEC_ID = :spec_id 
                      AND s.SCHED_DAYS = :date
                    ORDER BY d.DOC_LAST_NAME";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':spec_id' => $spec_id,
                ':date' => $date
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAvailableDoctors(): " . $e->getMessage());
            return [];
        }
    }

    // Get time slots for a specific doctor on a specific date
    public function getAvailableTimeSlots($doc_id, $date) {
        try {
            // Get doctor's schedule for the date
            $sql = "SELECT SCHED_START_TIME, SCHED_END_TIME 
                    FROM {$this->table} 
                    WHERE DOC_ID = :doc_id AND SCHED_DAYS = :date";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':doc_id' => $doc_id,
                ':date' => $date
            ]);
            $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$schedule) {
                return [];
            }

            // Get booked appointments for this doctor on this date
            $sqlBooked = "SELECT APPT_TIME 
                          FROM appointment 
                          WHERE DOC_ID = :doc_id 
                            AND APPT_DATE = :date 
                            AND STAT_ID != 3"; // Exclude cancelled
            $stmtBooked = $this->conn->prepare($sqlBooked);
            $stmtBooked->execute([
                ':doc_id' => $doc_id,
                ':date' => $date
            ]);
            $bookedTimes = $stmtBooked->fetchAll(PDO::FETCH_COLUMN);

            // Generate 30-minute time slots
            $slots = [];
            $start = strtotime($schedule['SCHED_START_TIME']);
            $end = strtotime($schedule['SCHED_END_TIME']);

            while ($start < $end) {
                $timeSlot = date('H:i:s', $start);
                if (!in_array($timeSlot, $bookedTimes)) {
                    $slots[] = [
                        'time' => $timeSlot,
                        'formatted' => date('h:i A', $start)
                    ];
                }
                $start += 1800; // Add 30 minutes
            }
            return $slots;
        } catch (PDOException $e) {
            error_log("Error in getAvailableTimeSlots(): " . $e->getMessage());
            return [];
        }
    }

    // CREATE: Add a new schedule for a doctor
    public function create($data) {
        $sql = "INSERT INTO {$this->table} 
                (DOC_ID, SCHED_DAYS, SCHED_START_TIME, SCHED_END_TIME, SCHED_CREATED_AT) 
                VALUES (:doc_id, :days, :start_time, :end_time, NOW())";
        // Removed SCHED_UPDATED_AT from INSERT
        $stmt = $this->conn->prepare($sql);
        try {
            return $stmt->execute([
                ':doc_id' => $data['DOC_ID'],
                ':days' => $data['SCHED_DAYS'],
                ':start_time' => $data['SCHED_START_TIME'],
                ':end_time' => $data['SCHED_END_TIME']
            ]);
        } catch (PDOException $e) {
            error_log("Schedule Creation Error: " . $e->getMessage());
            return "Database Error: " . $e->getMessage();
        }
    }

    // Base query for reading schedules with doctor name
    private function getBaseScheduleQuery() {
        return "SELECT 
                    s.SCHED_ID,
                    s.DOC_ID,
                    s.SCHED_DAYS,
                    s.SCHED_START_TIME,
                    s.SCHED_END_TIME,
                    CONCAT(d.DOC_FIRST_NAME, ' ', d.DOC_LAST_NAME) AS doctor_name,
                    s.SCHED_CREATED_AT,
                    s.SCHED_UPDATED_AT
                FROM {$this->table} s
                JOIN {$this->table_doctor} d ON s.DOC_ID = d.DOC_ID";
    }

    // Process rows to add formatted fields
    private function formatScheduleRows($rows) {
        foreach ($rows as &$row) {
            // Use date() for formatting in PHP, keeping raw time fields for <input type="time">
            $row['formatted_start_time'] = date('h:i A', strtotime($row['SCHED_START_TIME']));
            $row['formatted_end_time'] = date('h:i A', strtotime($row['SCHED_END_TIME']));

            // Format Created/Updated time
            $created = date('M d, Y', strtotime($row['SCHED_CREATED_AT']));
            $updated = $row['SCHED_UPDATED_AT'] ? date('H:i:s', strtotime($row['SCHED_UPDATED_AT'])) : '—';
            $row['formatted_created_at'] = $created . "<br>" . $updated;

            // Ensure SCHED_START_TIME and SCHED_END_TIME are raw HH:MM:SS for inputs
            $row['SCHED_START_TIME'] = date('H:i:s', strtotime($row['SCHED_START_TIME']));
            $row['SCHED_END_TIME'] = date('H:i:s', strtotime($row['SCHED_END_TIME']));
        }
        return $rows;
    }

    // Read all schedules
    public function all() {
        $sql = $this->getBaseScheduleQuery() . " ORDER BY s.SCHED_DAYS, s.SCHED_START_TIME ASC";
        $stmt = $this->conn->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->formatScheduleRows($rows);
    }

    // Read schedules for a specific doctor
    public function getByDoctorId($doc_id) {
        $sql = $this->getBaseScheduleQuery() . " WHERE s.DOC_ID = :doc_id ORDER BY s.SCHED_DAYS, s.SCHED_START_TIME ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':doc_id' => $doc_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->formatScheduleRows($rows);
    }

    // READ TODAY'S SCHEDULES
    public function todaySchedule() {
        date_default_timezone_set('Asia/Manila');
        $today = date('l'); // e.g., Monday
        $sql = $this->getBaseScheduleQuery() . " WHERE s.SCHED_DAYS = :today ORDER BY s.SCHED_START_TIME ASC";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':today', $today, PDO::PARAM_STR);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $this->formatScheduleRows($rows);
        } catch (PDOException $e) {
            error_log("Schedule->todaySchedule(): " . $e->getMessage());
            return [];
        }
    }

    // UPDATE: Edit a schedule
    public function update($data) {
        $sql = "UPDATE {$this->table} 
                SET DOC_ID = :doc_id, 
                    SCHED_DAYS = :days, 
                    SCHED_START_TIME = :start_time, 
                    SCHED_END_TIME = :end_time, 
                    SCHED_UPDATED_AT = NOW() 
                WHERE SCHED_ID = :sched_id";
        $stmt = $this->conn->prepare($sql);
        try {
            return $stmt->execute([
                ':sched_id' => $data['SCHED_ID'],
                ':doc_id' => $data['DOC_ID'],
                ':days' => $data['SCHED_DAYS'],
                ':start_time' => $data['SCHED_START_TIME'],
                ':end_time' => $data['SCHED_END_TIME']
            ]);
        } catch (PDOException $e) {
            error_log("Schedule Update Error: " . $e->getMessage());
            return false;
        }
    }

    // DELETE: Delete a schedule
    public function delete($sched_id) {
        $sql = "DELETE FROM {$this->table} WHERE SCHED_ID = :sched_id";
        $stmt = $this->conn->prepare($sql);
        try {
            return $stmt->execute([':sched_id' => $sched_id]);
        } catch (PDOException $e) {
            error_log("Schedule Deletion Error: " . $e->getMessage());
            return false;
        }
    }

    // Helper to get all doctors (needed for the form dropdown)
    public function getAllDoctors() {
        $sql = "SELECT 
                    DOC_ID, 
                    CONCAT(DOC_FIRST_NAME, ' ', DOC_LAST_NAME) AS doctor_name 
                FROM {$this->table_doctor} 
                ORDER BY DOC_FIRST_NAME";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>