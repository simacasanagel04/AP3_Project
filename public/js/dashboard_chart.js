document.addEventListener('DOMContentLoaded', function () {

    if (typeof dashboardChartData === 'undefined') return;

    const { appointmentsData, patientsData } = dashboardChartData;

    // Appointments Chart
    const appointmentsCanvas = document.getElementById("appointmentsChart");
    if (appointmentsCanvas) {
        new Chart(appointmentsCanvas.getContext("2d"), {
            type: "bar",
            data: {
                labels: appointmentsData.labels,
                datasets: [{
                    label: "Appointments",
                    data: appointmentsData.values,
                    backgroundColor: "rgba(7, 24, 80, 0.7)",
                    borderColor: "rgba(0, 0, 0, 1)",
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }

    // Patients Chart
    const patientsCanvas = document.getElementById("patientsChart");
    if (patientsCanvas) {
        new Chart(patientsCanvas.getContext("2d"), {
            type: "bar",
            data: {
                labels: patientsData.labels,
                datasets: [{
                    label: "New Patients",
                    data: patientsData.values,
                    backgroundColor: "rgba(7, 24, 80, 0.7)",
                    borderColor: "rgba(0, 0, 0, 1)",
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }
});
