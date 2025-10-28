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
    } catch (error) {
        showManualConnectButton('students-list', loadStudents);
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

function showManualConnectButton(containerId, loadFunction) {
    const container = document.getElementById(containerId);
    container.innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <p style="color: #666; margin-bottom: 20px;">Database connection failed. Make sure XAMPP is running.</p>
            <button onclick="${loadFunction.name}()" class="btn">Connect to Database</button>
        </div>
    `;
}

async function deleteStudent(id) {
    if (!confirm('Delete this student from the database?')) return;
    
    try {
        const result = await apiCall(`students.php?id=${id}`, 'DELETE');
        if (result.message && result.message.includes('successfully')) {
            showAlert('Student deleted successfully!', 'success');
            await loadStudents();
        } else {
            showAlert(result.message || 'Delete failed', 'error');
        }
    } catch (error) {
        showAlert('Database connection failed.', 'error');
    }
}

function getOrdinalSuffix(num) {
    const j = num % 10;
    const k = num % 100;
    if (j == 1 && k != 11) return "st";
    if (j == 2 && k != 12) return "nd";
    if (j == 3 && k != 13) return "rd";
    return "th";
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
    if (!form) return;

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

function toggleStudentForm() {
    const form = document.getElementById('studentFormContainer');
    const addButton = document.querySelector('button[onclick="toggleStudentForm()"]');
    const listContainer = document.getElementById('students-list');
    
    if (form.style.display === 'none') {
        listContainer.style.display = 'none';
        form.style.display = 'block';
        addButton.style.display = 'none';
        form.scrollIntoView({ behavior: 'smooth' });
    } else {
        form.style.display = 'none';
        addButton.style.display = 'block';
        listContainer.style.display = 'block';
        loadStudents();
    }
}

async function handleStudentFormSubmit(e) {
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
        
        const result = await apiCall('students.php', 'POST', data);
        
        if (result.message && result.message.includes('successfully')) {
            showAlert('Student added successfully!', 'success');
            form.reset();
            toggleStudentForm(); 
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
    console.log('Students Management Initialized');
    
    setupContactNumberValidation();
    setupEmailAutoGeneration();
    
    const studentForm = document.getElementById('studentForm');
    if (studentForm) {
        studentForm.addEventListener('submit', handleStudentFormSubmit);
    }
    
    try {
        await loadStudents();
        console.log('Students page initialized and connected to database');
    } catch (error) {
        console.error('Database connection error:', error);
    }
});