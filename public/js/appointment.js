document.addEventListener('DOMContentLoaded', function() { 
  const serviceSel = document.getElementById('appointment_service'); 
  const doctorSel = document.getElementById('appointment_doctor'); 
  const loading = document.getElementById('doctor_loading_indicator'); 
 
  if (serviceSel) {
    serviceSel.addEventListener('change', function() { 
      const servId = this.value; 
      doctorSel.innerHTML = '<option>Loading...</option>'; 
      doctorSel.disabled = true; 
      if (loading) loading.style.display = servId ? 'block' : 'none'; 
   
      if (!servId) { 
        doctorSel.innerHTML = '<option>Select Service first</option>'; 
        return; 
      } 
   
      fetch(`../../public/ajax-admin/gdoc-by-service.php?serv_id=${servId}`) 
        .then(r => r.ok ? r.json() : Promise.reject('Network error')) 
        .then(data => { 
          doctorSel.innerHTML = '<option value="">Select Doctor</option>'; 
          if (data.success && data.doctors.length) { 
            data.doctors.forEach(d => { 
              const opt = new Option(`Dr. ${d.DOC_FIRST_NAME} ${d.DOC_LAST_NAME}`, d.DOC_ID); 
              doctorSel.add(opt); 
            }); 
            doctorSel.disabled = false; 
          } else { 
            doctorSel.innerHTML = `<option>${data.message || 'No doctors'}</option>`; 
          } 
        }) 
        .catch(() => { 
          doctorSel.innerHTML = '<option>Error loading</option>'; 
        }) 
        .finally(() => {
          if (loading) loading.style.display = 'none';
        }); 
    });
  }
 
  // Handle delete modal
  document.querySelectorAll('[data-bs-target="#deleteApptModal"]').forEach(btn => { 
    btn.addEventListener('click', function() { 
      const id = this.getAttribute('data-appt-id'); 
      const modalApptIdDisplay = document.getElementById('modalApptIdDisplay');
      const deleteApptIdInput = document.getElementById('deleteApptIdInput');
      if (modalApptIdDisplay) modalApptIdDisplay.textContent = id; 
      if (deleteApptIdInput) deleteApptIdInput.value = id; 
    }); 
  }); 
});