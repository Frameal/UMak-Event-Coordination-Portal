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
    } catch (error) {
        showManualConnectButton('venues-list', loadVenues);
    }
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

function showManualConnectButton(containerId, loadFunction) {
    const container = document.getElementById(containerId);
    container.innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <p style="color: #666; margin-bottom: 20px;">Database connection failed. Make sure XAMPP is running.</p>
            <button onclick="${loadFunction.name}()" class="btn">Connect to Database</button>
        </div>
    `;
}

async function deleteVenue(id) {
    if (!confirm('Delete this venue from the database?')) return;
    
    try {
        const result = await apiCall(`venues.php?id=${id}`, 'DELETE');
        if (result.message && result.message.includes('successfully')) {
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
    
    if (form.style.display === 'none') {
        listContainer.style.display = 'none';
        form.style.display = 'block';
        addButton.style.display = 'none';
        form.scrollIntoView({ behavior: 'smooth' });
    } else {
        form.style.display = 'none';
        addButton.style.display = 'block';
        listContainer.style.display = 'block';
        loadVenues(); 
    }
}

async function handleVenueFormSubmit(e) {
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
        
        const result = await apiCall('venues.php', 'POST', data);
        
        if (result.message && result.message.includes('successfully')) {
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

document.addEventListener('DOMContentLoaded', async function() {
    console.log('Venues Management Initialized');
    
    const venueForm = document.getElementById('venueForm');
    if (venueForm) {
        venueForm.addEventListener('submit', handleVenueFormSubmit);
    }
    
    try {
        await loadVenues();
        console.log('Venues page initialized and connected to database');
    } catch (error) {
        console.error('Database connection error:', error);
    }
});