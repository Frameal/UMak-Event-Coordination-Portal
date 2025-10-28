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
    } catch (error) {
        showManualConnectButton('events-list', loadEvents);
    }
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

function showManualConnectButton(containerId, loadFunction) {
    const container = document.getElementById(containerId);
    container.innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <p style="color: #666; margin-bottom: 20px;">Database connection failed. Make sure XAMPP is running.</p>
            <button onclick="${loadFunction.name}()" class="btn">Connect to Database</button>
        </div>
    `;
}

async function loadVenuesDropdown() {
    const select = document.getElementById('venue-select');
    if (!select) return;
    
    try {
        const venues = await apiCall('venues.php');
        select.innerHTML = '<option value="">Select Venue</option>' +
            venues.map(v => `<option value="${v.venue_id}">${v.venue_name} (Cap: ${v.capacity})</option>`).join('');
    } catch (error) {
        select.innerHTML = '<option value="">Error loading venues</option>';
    }
}

async function deleteEvent(id) {
    if (!confirm('Delete this event from the database?')) return;
    
    try {
        const result = await apiCall(`events.php?id=${id}`, 'DELETE');
        if (result.message && result.message.includes('successfully')) {
            showAlert('Event deleted successfully!', 'success');
            await loadEvents();
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

async function handleEventFormSubmit(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const form = e.target;
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
        
        data.status = 'Published';
        
        const result = await apiCall('events.php', 'POST', data);
        
        if (result.message && result.message.includes('successfully')) {
            showAlert('Event created successfully!', 'success');
            form.reset();
            toggleEventForm(); 
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

function toggleEventForm() {
    const form = document.getElementById('eventFormContainer');
    const addButton = document.querySelector('button[onclick="toggleEventForm()"]');
    const listContainer = document.getElementById('events-list');
    
    if (form.style.display === 'none') {
        listContainer.style.display = 'none';
        form.style.display = 'block';
        addButton.style.display = 'none';
        form.scrollIntoView({ behavior: 'smooth' });
    } else {
        form.style.display = 'none';
        addButton.style.display = 'block';
        listContainer.style.display = 'block';
        loadEvents();
    }
}

document.addEventListener('DOMContentLoaded', async function() {
    console.log('Events Management Initialized');
    
    const eventForm = document.getElementById('eventForm');
    if (eventForm) {
        eventForm.addEventListener('submit', handleEventFormSubmit);
    }
    
    try {
        await loadEvents();
        console.log('Events page initialized and connected to database');
    } catch (error) {
        console.error('Database connection error:', error);
    }
});