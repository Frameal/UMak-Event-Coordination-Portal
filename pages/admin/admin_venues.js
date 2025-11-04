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

async function loadVenues() {
    const container = document.getElementById('venues-list');
    container.innerHTML = '<p style="color: #666; text-align: center; padding: 20px;">Loading venues...</p>';
    
    try {
        const venues = await apiCall('venues.php');
        container.innerHTML = !venues?.length ? 
            '<p style="text-align: center; padding: 20px;">No venues found. Add your first venue!</p>' : 
            renderVenuesTable(venues);
    } catch (error) {
        container.innerHTML = `<div style="text-align: center; padding: 40px;">
            <p style="color: #666; margin-bottom: 20px;">Database connection failed. Make sure XAMPP is running.</p>
            <button onclick="loadVenues()" class="btn">Connect to Database</button></div>`;
    }
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

async function deleteVenue(id) {
    if (!confirm('Delete this venue from the database?')) return;
    try {
        const result = await apiCall(`venues.php?id=${id}`, 'DELETE');
        if (result.message?.includes('success')) {
            showAlert('Venue deleted successfully!', 'success');
            await loadVenues();
        } else {
            showAlert(result.message || 'Delete failed', 'error');
        }
    } catch (error) {
        showAlert('Database connection failed.', 'error');
    }
}

function toggleVenueForm() {
    const form = document.getElementById('venueFormContainer');
    const addButton = document.querySelector('button[onclick="toggleVenueForm()"]');
    const listContainer = document.getElementById('venues-list');
    const isHidden = form.style.display === 'none';
    
    listContainer.style.display = isHidden ? 'none' : 'block';
    form.style.display = isHidden ? 'block' : 'none';
    addButton.style.display = isHidden ? 'none' : 'block';
    
    if (isHidden) form.scrollIntoView({ behavior: 'smooth' });
    else loadVenues();
}

async function handleVenueFormSubmit(e) {
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
        const result = await apiCall('venues.php', 'POST', data);
        
        if (result.message?.includes('success')) {
            showAlert('Venue added successfully!', 'success');
            form.reset();
            toggleVenueForm();
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
    console.log('Venues Management Initialized');
    
    const venueForm = document.getElementById('venueForm');
    if (venueForm) venueForm.addEventListener('submit', handleVenueFormSubmit);
    
    try {
        await loadVenues();
        console.log('Venues page initialized and connected to database');
    } catch (error) {
        console.error('Database connection error:', error);
    }
});
