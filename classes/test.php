<?php
include "../config/Database.php";
include "Patient.php";

$db = (new Database())->connect();

echo "<h2>TEST</h2>";
echo "<hr>";

// ==========================================
// FOR PATIENT
// ==========================================
echo "<h3>PATIENT TEST</h3>";

$patient = new Patient($db);


// Find Patient by ID
// $result = $patient->findById(100002);
// echo "Find Patient 100002: ";
// print_r($result);
// echo "<br>";

// Get Patient Appointments
// $appointments = $patient->getPatientAppointments(100002);
// echo "Patient 100002 Appointments: ";
// print_r($appointments);
// echo "<br>";

// Get All Patients
// $patients = $patient->all();
// echo "All Patients Count: " . count($patients) . "<br>";
// print_r($patients);
// echo "<br>";

// Get Total Patient Count
// $totalPatients = $patient->count();
// echo "Total Patients Count: " . $totalPatients . "<br>";

// Get Paginated Patients 
// $paginatedPatients = $patient->allPaginated(5, 0);
// echo "Paginated Patients (5 per page, page 1) Count: " . count($paginatedPatients) . "<br>";
// print_r($paginatedPatients);
// echo "<br>";

// // Get All Patients for Dropdown
// $dropdownPatients = $patient->getAllForDropdown();
// echo "Patients for Dropdown Count: " . count($dropdownPatients) . "<br>";
// print_r($dropdownPatients);
// echo "<br>";

// Search Patients with Appointments
// $searchResults = $patient->searchWithAppointments("Cruz");
// echo "Search Results for 'Cruz' Count: " . count($searchResults) . "<br>";
// print_r($searchResults);
// echo "<br>";

// // CREATE Patient TEST
// $newPatient = [
//     'pat_id' => 100004,
//     'pat_first_name' => "xyz",
//     'pat_middle_init' => "M",
//     'pat_last_name' => "ABC",
//     'pat_dob' => "1999-09-09",
//     'pat_gender' => "Male",
//     'pat_contact_num' => "09333123890",
//     'pat_email' => "xyz_abc@example.com",
//     'pat_address' => "Cebu City"
// ];
// $result = $patient->create($newPatient);
// echo "Create Patient Result: " . ($result ? "SUCCESS" : "FAILED") . "<br>";

// // UPDATE Patient
// $updatedPatient = [
//     'pat_id' => 100001,
//     'pat_first_name' => "Maria Mae",
//     'pat_middle_init' => "L",
//     'pat_last_name' => "Cruz",
//     'pat_dob' => "1990-05-15",
//     'pat_gender' => "Female",
//     'pat_contact_num' => "09171234567",
//     'pat_email' => "maria.updated@example.com",
//     'pat_address' => "124 Manila St."
// ];
// $result = $patient->update($updatedPatient);
// echo "Update Patient Result: " . ($result ? "SUCCESS" : "FAILED") . "<br>";

// // Find Updated Patient
// $result = $patient->findById(100001);
// echo "Find Updated Patient 100001: ";
// print_r($result);
// echo "<br>";

// // DELETE Patient
// $result = $patient->delete(100001);
// echo "Delete Patient Result: " . ($result ? "SUCCESS" : "FAILED") . "<br>";

// // Verify Deletion
// $result = $patient->findById(100001);
// echo "Find Deleted Patient 100001: ";
// print_r($result);
// echo "<br>";


// ==========================================
// FOR DOCTOR
// ==========================================
// echo "<h3>DOCTOR TEST</h3>";

$doctor = new Doctor($db);

// // Find Doctor by ID
// $result = $doctor->findById(1);
// echo "Find Doctor 1: ";
// print_r($result);
// echo "<br>";

// // Get Doctor Appointments
// $appointments = $doctor->getDoctorAppointments(1);
// echo "Doctor 1 Appointments: ";
// print_r($appointments);
// echo "<br>";

// // Get All Doctors
// $doctors = $doctor->all();
// echo "All Doctors Count: " . count($doctors) . "<br>";
// print_r($doctors);
// echo "<br>";

// // Get Total Doctor Count
// $totalDoctors = $doctor->count();
// echo "Total Doctors Count: " . $totalDoctors . "<br>";

// // Get Paginated Doctors
// $paginatedDoctors = $doctor->allPaginated(5, 0);
// echo "Paginated Doctors (5 per page, page 1) Count: " . count($paginatedDoctors) . "<br>";
// print_r($paginatedDoctors);
// echo "<br>";

// // Get All Doctors for Dropdown
// $dropdownDoctors = $doctor->getAllForDropdown();
// echo "Doctors for Dropdown Count: " . count($dropdownDoctors) . "<br>";
// print_r($dropdownDoctors);
// echo "<br>";

// // Search Doctors with Appointments
// $searchResults = $doctor->searchWithAppointments("Smith");
// echo "Search Results for 'Smith' Count: " . count($searchResults) . "<br>";
// print_r($searchResults);
// echo "<br>";

// // CREATE Doctor TEST
// $newDoctor = [
//     'doc_first_name'   => "John",
//     'doc_middle_init'  => "A",
//     'doc_last_name'    => "Doe",
//     'doc_contact_num'  => "09171234567",
//     'doc_email'        => "john.doe@example.com",
//     'spec_id'          => 1
// ];
// $result = $doctor->create($newDoctor);
// echo "Create Doctor Result: " . ($result ? "SUCCESS" : "FAILED") . "<br>";

// // UPDATE Doctor
// $updatedDoctor = [
//     'doc_id'           => 1, 
//     'doc_first_name'   => "Jane",
//     'doc_middle_init'  => "B",
//     'doc_last_name'    => "Flordeliza",
//     'doc_contact_num'  => "09179876543",
//     'doc_email'        => "jane.flordeliza@example.com",
//     'spec_id'          => 2 
// ];
// $result = $doctor->update($updatedDoctor);
// echo "Update Doctor Result: " . ($result ? "SUCCESS" : "FAILED") . "<br>";

// // Find Updated Doctor
// $result = $doctor->findById(1);
// echo "Find Updated Doctor 1: ";
// print_r($result);
// echo "<br>";

// // DELETE Doctor
// $result = $doctor->delete(1);
// echo "Delete Doctor Result: " . ($result ? "SUCCESS" : "FAILED") . "<br>";

// // Verify Deletion
// $result = $doctor->findById(1);
// echo "Find Deleted Doctor 1: ";
// print_r($result);
// echo "<br>";



?>