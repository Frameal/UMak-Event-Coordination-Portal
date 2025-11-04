const API_BASE = 'http://localhost/API';

async function apiCall(endpoint, method = 'GET', data = null) {
    const options = { method, headers: { 'Content-Type': 'application/json' } };
    if (data && method !== 'GET') options.body = JSON.stringify(data);
    
    try {
        console.log(`API Call: ${method} ${API_BASE}/${endpoint}`, data || '');
        const response = await fetch(`${API_BASE}/${endpoint}`, options);
        if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

const showAlert = (message, type) => {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    const container = document.querySelector('.tab-content.active');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        setTimeout(() => alertDiv.remove(), 5000);
    }
};

async function loadRegistrations() {
    const container = document.getElementById('registrations-list');
    container.innerHTML = '<p style="color: #666; text-align: center; padding: 20px;">Loading registrations...</p>';
    
    try {
        const registrations = await apiCall('admin-registrations.php');
        container.innerHTML = !registrations?.length ? 
            '<p style="text-align: center; padding: 20px;">No registrations found. Register students for events!</p>' : 
            renderRegistrationsTable(registrations);
    } catch (error) {
        container.innerHTML = `<div style="text-align: center; padding: 40px;">
            <p style="color: #666; margin-bottom: 20px;">Database connection failed. Make sure XAMPP is running.</p>
            <button onclick="loadRegistrations()" class="btn">Connect to Database</button></div>`;
    }
}

function renderRegistrationsTable(registrations) {
    return `<table class="table"><thead><tr>
        <th>Student</th><th>Event</th><th>Registration Date</th><th>QR Code</th><th>Status</th><th>Actions</th>
    </tr></thead><tbody>${registrations.map(r => `<tr>
        <td><strong>${r.lastname}, ${r.firstname}</strong><br><small style="color: #666;">${r.student_number}</small></td>
        <td><strong>${r.event_name}</strong><br><small style="color: #666;">${formatDate(r.event_date)}</small></td>
        <td>${formatDate(r.registration_date)}</td>
        <td><code style="background: #f5f5f5; padding: 2px 6px; border-radius: 4px;">${r.qr_code}</code></td>
        <td><span class="status-badge status-${(r.status || 'registered').toLowerCase()}">${r.status || 'Registered'}</span></td>
        <td><button class="btn btn-danger" onclick="deleteRegistration(${r.registration_id})">Cancel</button></td>
    </tr>`).join('')}</tbody></table>`;
}

async function loadStudentsDropdown() {
    const select = document.getElementById('student-select');
    if (!select) return;
    try {
        const students = await apiCall('admin-students.php');
        select.innerHTML = '<option value="">Select Student</option>' + 
            students.map(s => `<option value="${s.student_id}">${s.lastname}, ${s.firstname} (${s.student_number})</option>`).join('');
    } catch (error) {
        select.innerHTML = '<option value="">Error loading students</option>';
    }
}

async function loadEventsDropdown() {
    const select = document.getElementById('event-select');
    if (!select) return;
    try {
        const events = await apiCall('admin-events.php');
        const upcoming = events.filter(e => new Date(e.event_date) >= new Date().setHours(0,0,0,0));
        select.innerHTML = '<option value="">Select Event</option>' + 
            upcoming.map(e => `<option value="${e.event_id}">${e.event_name} (${formatDate(e.event_date)})</option>`).join('');
    } catch (error) {
        select.innerHTML = '<option value="">Error loading events</option>';
    }
}

async function deleteRegistration(id) {
    if (!confirm('Cancel this registration?')) return;
    try {
        const result = await apiCall(`admin-registrations.php?id=${id}`, 'DELETE');
        if (result.message?.includes('success')) {
            showAlert('Registration cancelled successfully!', 'success');
            await loadRegistrations();
        } else {
            showAlert(result.message || 'Delete failed', 'error');
        }
    } catch (error) {
        showAlert('Database connection failed.', 'error');
    }
}

const formatDate = (dateString) => dateString ? 
    new Date(dateString).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : 'N/A';

function toggleRegistrationForm() {
    const form = document.getElementById('registrationFormContainer');
    const addButton = document.querySelector('button[onclick="toggleRegistrationForm()"]');
    const listContainer = document.getElementById('registrations-list');
    const isHidden = form.style.display === 'none';
    
    listContainer.style.display = isHidden ? 'none' : 'block';
    form.style.display = isHidden ? 'block' : 'none';
    addButton.style.display = isHidden ? 'none' : 'block';
    
    if (isHidden) {
        form.scrollIntoView({ behavior: 'smooth' });
        loadStudentsDropdown();
        loadEventsDropdown();
    } else {
        loadRegistrations();
    }
}

async function handleRegistrationFormSubmit(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const form = e.target;
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner"></span>Submitting...';
    
    try {
        const data = Object.fromEntries(new FormData(form).entries());
        Object.keys(data).forEach(k => data[k] = data[k] || null);
        const result = await apiCall('admin-registrations.php', 'POST', data);
        
        if (result.message?.includes('success')) {
            showAlert('Student registered successfully!', 'success');
            form.reset();
            toggleRegistrationForm();
        } else {
            showAlert(result.message || 'Operation failed', 'error');
        }
    } catch (error) {
        console.error('Form submission error:', error);
        showAlert('Database connection failed. Check XAMPP.', 'error');
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    console.log('Registrations Management Initialized');
    
    const registrationForm = document.getElementById('registrationForm');
    if (registrationForm) registrationForm.addEventListener('submit', handleRegistrationFormSubmit);
    
    try {
        await Promise.all([loadRegistrations(), loadStudentsDropdown(), loadEventsDropdown()]);
        console.log('Registrations page initialized and connected to database');
    } catch (error) {
        console.error('Database connection error:', error);
    }
});
