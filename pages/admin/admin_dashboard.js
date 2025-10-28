const API_BASE = 'http://localhost/API';

async function apiCall(endpoint, method = 'GET', data = null) {
    const url = `${API_BASE}/${endpoint}`;
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        }
    };
    
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    try {
        console.log(`API Call: ${method} ${url}`, data || '');
        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
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

async function loadDashboard() {
    try {
        const stats = await apiCall('dashboard.php');
        document.getElementById('total-students').textContent = stats.total_students || '0';
        document.getElementById('total-events').textContent = stats.total_events || '0';
        document.getElementById('total-venues').textContent = stats.total_venues || '0';
        document.getElementById('total-registrations').textContent = stats.total_registrations || '0';
    } catch (error) {
        console.error('Error loading dashboard:', error);
    }
}

async function testConnection() {
    try {
        const result = await apiCall('dashboard.php');
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

document.addEventListener('DOMContentLoaded', async function() {
    console.log('UMak ECP Admin Dashboard Initialized');
    
    try {
        await loadDashboard();
        console.log('Dashboard initialized and connected to database');
    } catch (error) {
        console.error('Database connection error:', error);
    }
});

async function initializePage() {
    const path = window.location.pathname;
    const filename = path.split('/').pop();
    
    // First try to load based on the current page's content
    if (document.getElementById('students-list')) {
        await loadStudents();
    }
    if (document.getElementById('events-list')) {
        await loadEvents();
    }
    if (document.getElementById('venues-list')) {
        await loadVenues();
    }
    if (document.getElementById('registrations-list')) {
        await loadRegistrations();
    }
    if (filename === 'admin_dashboard.html' || filename === '') {
        await loadDashboard();
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

function showManualConnectButton(containerId, loadFunction) {
    const container = document.getElementById(containerId);
    container.innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <p style="color: #666; margin-bottom: 20px;">Database connection failed. Make sure XAMPP is running.</p>
            <button onclick="${loadFunction.name}()" class="btn">Connect to Database</button>
        </div>
    `;
}

async function loadDashboard() {
    try {
        const stats = await apiCall('dashboard.php');
        document.getElementById('total-students').textContent = stats.total_students || '0';
        document.getElementById('total-events').textContent = stats.total_events || '0';
        document.getElementById('total-venues').textContent = stats.total_venues || '0';
        document.getElementById('total-registrations').textContent = stats.total_registrations || '0';
    } catch (error) {
        console.error('Error loading dashboard:', error);
    }
}

async function loadStudents() {
    const container = document.getElementById('students-list');
    container.innerHTML = '<p style="color: #666; text-align: center; padding: 20px;">Loading students...</p>';
    
    try {
        const students = await apiCall('students.php');
        
        if (!students || students.length === 0) {
            container.innerHTML = '<p style="text-align: center; padding: 20px;">No students found. Add your first student!</p>';
            return;
        }
        
        container.innerHTML = renderStudentsTable(students);
        await loadStudentsDropdown();
    } catch (error) {
        showManualConnectButton('students-list', loadStudents);
    }
}

async function loadEvents() {
    const container = document.getElementById('events-list');
    container.innerHTML = '<p style="color: #666; text-align: center; padding: 20px;">Loading events...</p>';
    
    try {
        const events = await apiCall('events.php');
        
        if (!events || events.length === 0) {
            container.innerHTML = '<p style="text-align: center; padding: 20px;">No events found. Create your first event!</p>';
            return;
        }
        
        container.innerHTML = renderEventsTable(events);
        await loadVenuesDropdown();
        await loadEventsDropdown();
    } catch (error) {
        showManualConnectButton('events-list', loadEvents);
    }
}

async function loadVenues() {
    const container = document.getElementById('venues-list');
    container.innerHTML = '<p style="color: #666; text-align: center; padding: 20px;">Loading venues...</p>';
    
    try {
        const venues = await apiCall('venues.php');
        
        if (!venues || venues.length === 0) {
            container.innerHTML = '<p style="text-align: center; padding: 20px;">No venues found. Add your first venue!</p>';
            return;
        }
        
        container.innerHTML = renderVenuesTable(venues);
        await loadVenuesDropdown();
    } catch (error) {
        showManualConnectButton('venues-list', loadVenues);
    }
}

async function loadRegistrations() {
    const container = document.getElementById('registrations-list');
    container.innerHTML = '<p style="color: #666; text-align: center; padding: 20px;">Loading registrations...</p>';
    
    try {
        const registrations = await apiCall('registrations.php');
        
        if (!registrations || registrations.length === 0) {
            container.innerHTML = '<p style="text-align: center; padding: 20px;">No registrations found. Register students for events!</p>';
            return;
        }
        
        container.innerHTML = renderRegistrationsTable(registrations);
    } catch (error) {
        showManualConnectButton('registrations-list', loadRegistrations);
    }
}

async function loadVenuesDropdown() {
    const select = document.getElementById('venue-select');
    try {
        const venues = await apiCall('venues.php');
        select.innerHTML = '<option value="">Select Venue</option>' +
            venues.map(v => `<option value="${v.venue_id}">${v.venue_name} (Cap: ${v.capacity})</option>`).join('');
    } catch (error) {
        select.innerHTML = '<option value="">Error loading venues</option>';
    }
}

async function loadStudentsDropdown() {
    const select = document.getElementById('student-select');
    try {
        const students = await apiCall('students.php');
        select.innerHTML = '<option value="">Select Student</option>' +
            students.map(s => `<option value="${s.student_id}">${s.lastname}, ${s.firstname} (${s.student_number})</option>`).join('');
    } catch (error) {
        select.innerHTML = '<option value="">Error loading students</option>';
    }
}

async function loadEventsDropdown() {
    const select = document.getElementById('event-select');
    try {
        const events = await apiCall('events.php');
        const upcoming = events.filter(e => new Date(e.event_date) >= new Date().setHours(0,0,0,0));
        select.innerHTML = '<option value="">Select Event</option>' +
            upcoming.map(e => `<option value="${e.event_id}">${e.event_name} (${formatDate(e.event_date)})</option>`).join('');
    } catch (error) {
        select.innerHTML = '<option value="">Error loading events</option>';
    }
}

function renderStudentsTable(students) {
    return `
        <table class="table">
            <thead>
                <tr>
                    <th>Student #</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Course</th>
                    <th>Year</th>
                    <th>Contact</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                ${students.map(s => `
                    <tr>
                        <td><strong>${s.student_number}</strong></td>
                        <td>${s.lastname}, ${s.firstname} ${s.middlename || ''}</td>
                        <td>${s.email}</td>
                        <td>${s.course || 'N/A'}</td>
                        <td>${s.year_level ? s.year_level + getOrdinalSuffix(s.year_level) + ' Year' : 'N/A'}</td>
                        <td>${s.contact_number || 'N/A'}</td>
                        <td>
                            <button class="btn btn-danger" onclick="deleteStudent(${s.student_id})">Delete</button>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

function renderEventsTable(events) {
    return `
        <table class="table">
            <thead>
                <tr>
                    <th>Event Name</th>
                    <th>Date & Time</th>
                    <th>Venue</th>
                    <th>Capacity</th>
                    <th>Registered</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                ${events.map(e => `
                    <tr>
                        <td>
                            <strong>${e.event_name}</strong>
                            ${e.description ? '<br><small style="color: #666;">' + e.description.substring(0, 50) + '...</small>' : ''}
                        </td>
                        <td>
                            ${formatDate(e.event_date)}<br>
                            <small>${formatTime(e.start_time)} - ${formatTime(e.end_time)}</small>
                        </td>
                        <td>${e.venue_name || 'Unknown'}</td>
                        <td>${e.attendees_capacity}</td>
                        <td><strong>${e.registered_count || 0}</strong></td>
                        <td><span class="status-badge status-${(e.status || 'draft').toLowerCase().replace(' ', '-')}">${e.status || 'Draft'}</span></td>
                        <td>
                            <button class="btn btn-danger" onclick="deleteEvent(${e.event_id})">Delete</button>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

function renderVenuesTable(venues) {
    return `
        <table class="table">
            <thead>
                <tr>
                    <th>Venue Name</th>
                    <th>Capacity</th>
                    <th>Location</th>
                    <th>Facilities</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                ${venues.map(v => `
                    <tr>
                        <td><strong>${v.venue_name}</strong></td>
                        <td>${v.capacity}</td>
                        <td>${v.location || 'N/A'}</td>
                        <td>${v.facilities || 'N/A'}</td>
                        <td>
                            <button class="btn btn-danger" onclick="deleteVenue(${v.venue_id})">Delete</button>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

function renderRegistrationsTable(registrations) {
    return `
        <table class="table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Event</th>
                    <th>Registration Date</th>
                    <th>QR Code</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                ${registrations.map(r => `
                    <tr>
                        <td>
                            <strong>${r.lastname}, ${r.firstname}</strong><br>
                            <small style="color: #666;">${r.student_number}</small>
                        </td>
                        <td>
                            <strong>${r.event_name}</strong><br>
                            <small style="color: #666;">${formatDate(r.event_date)}</small>
                        </td>
                        <td>${formatDate(r.registration_date)}</td>
                        <td><code style="background: #f5f5f5; padding: 2px 6px; border-radius: 4px;">${r.qr_code}</code></td>
                        <td><span class="status-badge status-${(r.status || 'registered').toLowerCase()}">${r.status || 'Registered'}</span></td>
                        <td>
                            <button class="btn btn-danger" onclick="deleteRegistration(${r.registration_id})">Cancel</button>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

async function handleFormSubmit(formId, endpoint, successMessage, reloadFunctions) {
    const form = document.getElementById(formId);
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;
        
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner"></span>Submitting...';
        
        try {
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value || null;
            });
            
            if (formId === 'eventForm') {
                data.status = 'Published';
            }
            
            const result = await apiCall(endpoint, 'POST', data);
            
            if (result.message && (result.message.includes('successfully') || result.message.includes('successful'))) {
                showAlert(successMessage, 'success');
                form.reset();
                
                for (const func of reloadFunctions) {
                    await func();
                }
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

async function deleteStudent(id) {
    if (!confirm('Delete this student from the database?')) return;
    
    try {
        const result = await apiCall(`students.php?id=${id}`, 'DELETE');
        if (result.message && result.message.includes('successfully')) {
            showAlert('Student deleted successfully!', 'success');
            await loadStudents();
            await loadDashboard();
        } else {
            showAlert(result.message || 'Delete failed', 'error');
        }
    } catch (error) {
        showAlert('Database connection failed.', 'error');
    }
}

async function deleteEvent(id) {
    if (!confirm('Delete this event from the database?')) return;
    
    try {
        const result = await apiCall(`events.php?id=${id}`, 'DELETE');
        if (result.message && result.message.includes('successfully')) {
            showAlert('Event deleted successfully!', 'success');
            await loadEvents();
            await loadDashboard();
        } else {
            showAlert(result.message || 'Delete failed', 'error');
        }
    } catch (error) {
        showAlert('Database connection failed.', 'error');
    }
}

async function deleteVenue(id) {
    if (!confirm('Delete this venue from the database?')) return;
    
    try {
        const result = await apiCall(`venues.php?id=${id}`, 'DELETE');
        if (result.message && result.message.includes('successfully')) {
            showAlert('Venue deleted successfully!', 'success');
            await loadVenues();
            await loadDashboard();
        } else {
            showAlert(result.message || 'Delete failed', 'error');
        }
    } catch (error) {
        showAlert('Database connection failed.', 'error');
    }
}

async function deleteRegistration(id) {
    if (!confirm('Cancel this registration?')) return;
    
    try {
        const result = await apiCall(`registrations.php?id=${id}`, 'DELETE');
        if (result.message && result.message.includes('successfully')) {
            showAlert('Registration cancelled successfully!', 'success');
            await loadRegistrations();
            await loadDashboard();
        } else {
            showAlert(result.message || 'Delete failed', 'error');
        }
    } catch (error) {
        showAlert('Database connection failed.', 'error');
    }
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatTime(timeString) {
    if (!timeString) return 'N/A';
    return new Date('2000-01-01 ' + timeString).toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}

function getOrdinalSuffix(num) {
    const j = num % 10;
    const k = num % 100;
    if (j == 1 && k != 11) return "st";
    if (j == 2 && k != 12) return "nd";
    if (j == 3 && k != 13) return "rd";
    return "th";
}

async function testConnection() {
    try {
        const result = await apiCall('dashboard.php');
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

function setupContactNumberValidation() {
    const contactInput = document.querySelector('input[name="contact_number"]');
    if (!contactInput) return;
    
    contactInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 11) value = value.substring(0, 11);
        if (value.length > 0 && !value.startsWith('09')) {
            if (value.length === 1 && value === '0') {
                value = '09';
            } else if (!value.startsWith('09')) {
                value = '09' + value.substring(value.startsWith('0') ? 1 : 0);
            }
        }
        e.target.value = value;
    });
}

function setupEmailAutoGeneration() {
    const form = document.getElementById('studentForm');
    const studentNumber = form.querySelector('input[name="student_number"]');
    const firstname = form.querySelector('input[name="firstname"]');
    const lastname = form.querySelector('input[name="lastname"]');
    const email = form.querySelector('input[name="email"]');
    
    function generateEmail() {
        const num = studentNumber.value;
        const first = firstname.value.toLowerCase();
        const last = lastname.value.toLowerCase();
        
        if (num && first && last) {
            email.value = `${first.charAt(0)}${last}.${num.toLowerCase()}@umak.edu.ph`;
        }
    }
    
    studentNumber.addEventListener('blur', generateEmail);
    firstname.addEventListener('blur', generateEmail);
    lastname.addEventListener('blur', generateEmail);
}

document.addEventListener('DOMContentLoaded', async function() {
    console.log('UMak ECP Admin Dashboard Initialized');
    
    setupContactNumberValidation();
    setupEmailAutoGeneration();
    
    const studentForm = document.getElementById('studentForm');
    if (studentForm) {
        handleFormSubmit('studentForm', 'students.php', 'Student added successfully!', 
            [loadStudents, loadDashboard, loadStudentsDropdown]);
    }
    
    const eventForm = document.getElementById('eventForm');
    if (eventForm) {
        handleFormSubmit('eventForm', 'events.php', 'Event created successfully!', 
            [loadEvents, loadDashboard, loadEventsDropdown]);
    }
    
    const venueForm = document.getElementById('venueForm');
    if (venueForm) {
        handleFormSubmit('venueForm', 'venues.php', 'Venue added successfully!', 
            [loadVenues, loadDashboard, loadVenuesDropdown]);
    }
    
    const registrationForm = document.getElementById('registrationForm');
    if (registrationForm) {
        handleFormSubmit('registrationForm', 'registrations.php', 'Student registered successfully!', 
            [loadRegistrations, loadDashboard]);
    }
    
    try {
        await initializePage();
        console.log('Page initialized and connected to database');
    } catch (error) {
        console.error('Database connection error:', error);
    }
    
    if (document.getElementById('venue-select')) {
        await loadVenuesDropdown();
    }
    if (document.getElementById('student-select')) {
        await loadStudentsDropdown();
    }
    if (document.getElementById('event-select')) {
        await loadEventsDropdown();
    }
});