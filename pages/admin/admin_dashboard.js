const API_BASE = 'http://localhost/API';

async function apiCall(endpoint, method = 'GET', data = null) {
    const options = {
        method,
        headers: { 'Content-Type': 'application/json' }
    };
    
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

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    const container = document.querySelector('.tab-content.active');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        setTimeout(() => alertDiv.remove(), 5000);
    }
}

async function testConnection() {
    try {
        const result = await apiCall('admin-dashboard.php');
        if (result) {
            showAlert('Successfully connected to MySQL!', 'success');
            await loadDashboard();
            return true;
        }
    } catch (error) {
        showAlert('Connection failed. Check XAMPP and database.', 'error');
        return false;
    }
}

async function loadDashboard() {
    try {
        const stats = await apiCall('admin-dashboard.php');
        ['students', 'events', 'venues', 'registrations'].forEach(key => {
            const el = document.getElementById(`total-${key}`);
            if (el) el.textContent = stats[`total_${key}`] || '0';
        });
    } catch (error) {
        console.error('Error loading dashboard:', error);
    }
}

async function loadStudents() {
    return loadData('students-list', 'admin-students.php', renderStudentsTable, 
        'No students found. Add your first student!', loadStudents, loadStudentsDropdown);
}

async function loadEvents() {
    return loadData('events-list', 'admin-events.php', renderEventsTable, 
        'No events found. Create your first event!', loadEvents, loadVenuesDropdown, loadEventsDropdown);
}

async function loadVenues() {
    return loadData('venues-list', 'admin-venues.php', renderVenuesTable, 
        'No venues found. Add your first venue!', loadVenues, loadVenuesDropdown);
}

async function loadRegistrations() {
    return loadData('registrations-list', 'admin-registrations.php', renderRegistrationsTable, 
        'No registrations found. Register students for events!', loadRegistrations);
}

async function loadData(containerId, endpoint, renderFunc, emptyMsg, reloadFunc, ...afterLoad) {
    const container = document.getElementById(containerId);
    container.innerHTML = '<p style="color: #666; text-align: center; padding: 20px;">Loading...</p>';
    
    try {
        const data = await apiCall(endpoint);
        container.innerHTML = !data?.length ? `<p style="text-align: center; padding: 20px;">${emptyMsg}</p>` : renderFunc(data);
        await Promise.all(afterLoad.map(fn => fn?.()));
    } catch (error) {
        container.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <p style="color: #666; margin-bottom: 20px;">Database connection failed. Make sure XAMPP is running.</p>
                <button onclick="${reloadFunc.name}()" class="btn">Connect to Database</button>
            </div>`;
    }
}

async function loadVenuesDropdown() {
    return loadDropdown('venue-select', 'admin-venues.php', 
        v => `<option value="${v.venue_id}">${v.venue_name} (Cap: ${v.capacity})</option>`);
}

async function loadStudentsDropdown() {
    return loadDropdown('student-select', 'admin-students.php', 
        s => `<option value="${s.student_id}">${s.lastname}, ${s.firstname} (${s.student_number})</option>`);
}

async function loadEventsDropdown() {
    const select = document.getElementById('event-select');
    if (!select) return;
    try {
        const events = await apiCall('admin-events.php');
        const upcoming = events.filter(e => new Date(e.event_date) >= new Date().setHours(0,0,0,0));
        select.innerHTML = '<option value="">Select Event</option>' + upcoming.map(e => 
            `<option value="${e.event_id}">${e.event_name} (${formatDate(e.event_date)})</option>`).join('');
    } catch (error) {
        select.innerHTML = '<option value="">Error loading events</option>';
    }
}

async function loadDropdown(selectId, endpoint, mapFunc) {
    const select = document.getElementById(selectId);
    if (!select) return;
    try {
        const data = await apiCall(endpoint);
        select.innerHTML = `<option value="">Select ${selectId.split('-')[0]}</option>${data.map(mapFunc).join('')}`;
    } catch (error) {
        select.innerHTML = `<option value="">Error loading ${selectId.split('-')[0]}s</option>`;
    }
}

function renderStudentsTable(students) {
    return `<table class="table"><thead><tr>
        <th>Student #</th><th>Name</th><th>Email</th><th>Course</th><th>Year</th><th>Contact</th><th>Actions</th>
    </tr></thead><tbody>${students.map(s => `<tr>
        <td><strong>${s.student_number}</strong></td>
        <td>${s.lastname}, ${s.firstname} ${s.middlename || ''}</td>
        <td>${s.email}</td>
        <td>${s.course || 'N/A'}</td>
        <td>${s.year_level ? s.year_level + getOrdinalSuffix(s.year_level) + ' Year' : 'N/A'}</td>
        <td>${s.contact_number || 'N/A'}</td>
        <td><button class="btn btn-danger" onclick="deleteStudent(${s.student_id})">Delete</button></td>
    </tr>`).join('')}</tbody></table>`;
}

function renderEventsTable(events) {
    return `<table class="table"><thead><tr>
        <th>Event Name</th><th>Date & Time</th><th>Venue</th><th>Capacity</th><th>Registered</th><th>Status</th><th>Actions</th>
    </tr></thead><tbody>${events.map(e => `<tr>
        <td><strong>${e.event_name}</strong>${e.description ? '<br><small style="color: #666;">' + e.description.substring(0, 50) + '...</small>' : ''}</td>
        <td>${formatDate(e.event_date)}<br><small>${formatTime(e.start_time)} - ${formatTime(e.end_time)}</small></td>
        <td>${e.venue_name || 'Unknown'}</td>
        <td>${e.attendees_capacity}</td>
        <td><strong>${e.registered_count || 0}</strong></td>
        <td><span class="status-badge status-${(e.status || 'draft').toLowerCase().replace(' ', '-')}">${e.status || 'Draft'}</span></td>
        <td><button class="btn btn-danger" onclick="deleteEvent(${e.event_id})">Delete</button></td>
    </tr>`).join('')}</tbody></table>`;
}

function renderVenuesTable(venues) {
    return `<table class="table"><thead><tr>
        <th>Venue Name</th><th>Capacity</th><th>Location</th><th>Facilities</th><th>Actions</th>
    </tr></thead><tbody>${venues.map(v => `<tr>
        <td><strong>${v.venue_name}</strong></td>
        <td>${v.capacity}</td>
        <td>${v.location || 'N/A'}</td>
        <td>${v.facilities || 'N/A'}</td>
        <td><button class="btn btn-danger" onclick="deleteVenue(${v.venue_id})">Delete</button></td>
    </tr>`).join('')}</tbody></table>`;
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

async function handleFormSubmit(formId, endpoint, successMessage, reloadFunctions) {
    const form = document.getElementById(formId);
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;
        
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner"></span>Submitting...';
        
        try {
            const data = Object.fromEntries(formData.entries());
            Object.keys(data).forEach(k => data[k] = data[k] || null);
            if (formId === 'eventForm') data.status = 'Published';
            
            const result = await apiCall(endpoint, 'POST', data);
            
            if (result.message?.includes('success')) {
                showAlert(successMessage, 'success');
                form.reset();
                await Promise.all(reloadFunctions.map(fn => fn()));
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
    });
}

async function deleteItem(type, id) {
    const config = {
        student: { endpoint: 'admin-students.php', message: 'Student', reload: [loadStudents, loadDashboard] },
        event: { endpoint: 'admin-events.php', message: 'Event', reload: [loadEvents, loadDashboard] },
        venue: { endpoint: 'admin-venues.php', message: 'Venue', reload: [loadVenues, loadDashboard] },
        registration: { endpoint: 'admin-registrations.php', message: 'Registration', reload: [loadRegistrations, loadDashboard], action: 'Cancel' }
    }[type];
    
    if (!confirm(`${config.action || 'Delete'} this ${config.message.toLowerCase()} ${config.action ? '' : 'from the database'}?`)) return;
    
    try {
        const result = await apiCall(`${config.endpoint}?id=${id}`, 'DELETE');
        if (result.message?.includes('success')) {
            showAlert(`${config.message} ${config.action || 'deleted'} successfully!`, 'success');
            await Promise.all(config.reload.map(fn => fn()));
        } else {
            showAlert(result.message || 'Operation failed', 'error');
        }
    } catch (error) {
        showAlert('Database connection failed.', 'error');
    }
}

const deleteStudent = (id) => deleteItem('student', id);
const deleteEvent = (id) => deleteItem('event', id);
const deleteVenue = (id) => deleteItem('venue', id);
const deleteRegistration = (id) => deleteItem('registration', id);

function formatDate(dateString) {
    return dateString ? new Date(dateString).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : 'N/A';
}

function formatTime(timeString) {
    return timeString ? new Date('2000-01-01 ' + timeString).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }) : 'N/A';
}

function getOrdinalSuffix(num) {
    const j = num % 10, k = num % 100;
    return (j == 1 && k != 11) ? "st" : (j == 2 && k != 12) ? "nd" : (j == 3 && k != 13) ? "rd" : "th";
}

function setupContactNumberValidation() {
    const contactInput = document.querySelector('input[name="contact_number"]');
    if (!contactInput) return;
    
    contactInput.addEventListener('input', (e) => {
        let value = e.target.value.replace(/\D/g, '').substring(0, 11);
        if (value.length > 0 && !value.startsWith('09')) {
            value = value.length === 1 && value === '0' ? '09' : '09' + value.substring(value.startsWith('0') ? 1 : 0);
        }
        e.target.value = value;
    });
}

function setupEmailAutoGeneration() {
    const form = document.getElementById('studentForm');
    if (!form) return;
    
    const fields = ['student_number', 'firstname', 'lastname', 'email'].reduce((acc, name) => {
        acc[name] = form.querySelector(`input[name="${name}"]`);
        return acc;
    }, {});
    
    const generateEmail = () => {
        const num = fields.student_number.value;
        const first = fields.firstname.value.toLowerCase();
        const last = fields.lastname.value.toLowerCase();
        if (num && first && last) fields.email.value = `${first.charAt(0)}${last}.${num.toLowerCase()}@umak.edu.ph`;
    };
    
    ['student_number', 'firstname', 'lastname'].forEach(field => 
        fields[field].addEventListener('blur', generateEmail));
}

async function initializePage() {
    const loaders = {
        'students-list': loadStudents,
        'events-list': loadEvents,
        'venues-list': loadVenues,
        'registrations-list': loadRegistrations
    };
    
    for (const [id, loader] of Object.entries(loaders)) {
        if (document.getElementById(id)) await loader();
    }
    
    const filename = window.location.pathname.split('/').pop();
    if (!filename || filename === 'admin_dashboard.html') await loadDashboard();
}

document.addEventListener('DOMContentLoaded', async () => {
    console.log('UMak ECP Admin Dashboard Initialized');
    
    setupContactNumberValidation();
    setupEmailAutoGeneration();
    
    const forms = [
        ['studentForm', 'students.php', 'Student added successfully!', [loadStudents, loadDashboard, loadStudentsDropdown]],
        ['eventForm', 'events.php', 'Event created successfully!', [loadEvents, loadDashboard, loadEventsDropdown]],
        ['venueForm', 'venues.php', 'Venue added successfully!', [loadVenues, loadDashboard, loadVenuesDropdown]],
        ['registrationForm', 'registrations.php', 'Student registered successfully!', [loadRegistrations, loadDashboard]]
    ];
    
    forms.forEach(([formId, endpoint, msg, reload]) => {
        if (document.getElementById(formId)) handleFormSubmit(formId, endpoint, msg, reload);
    });
    
    try {
        await initializePage();
        ['venue-select', 'student-select', 'event-select'].forEach(async (id) => {
            if (document.getElementById(id)) {
                const loader = { 'venue-select': loadVenuesDropdown, 'student-select': loadStudentsDropdown, 'event-select': loadEventsDropdown }[id];
                await loader();
            }
        });
        console.log('Page initialized and connected to database');
    } catch (error) {
        console.error('Database connection error:', error);
    }
});
