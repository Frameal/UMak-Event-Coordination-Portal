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

async function loadStudents() {
    const container = document.getElementById('students-list');
    container.innerHTML = '<p style="color: #666; text-align: center; padding: 20px;">Loading students...</p>';
    
    try {
        const students = await apiCall('admin-students.php');
        container.innerHTML = !students?.length ? 
            '<p style="text-align: center; padding: 20px;">No students found. Add your first student!</p>' : 
            renderStudentsTable(students);
    } catch (error) {
        container.innerHTML = `<div style="text-align: center; padding: 40px;">
            <p style="color: #666; margin-bottom: 20px;">Database connection failed. Make sure XAMPP is running.</p>
            <button onclick="loadStudents()" class="btn">Connect to Database</button></div>`;
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

async function deleteStudent(id) {
    if (!confirm('Delete this student from the database?')) return;
    try {
        const result = await apiCall(`admin-students.php?id=${id}`, 'DELETE');
        if (result.message?.includes('success')) {
            showAlert('Student deleted successfully!', 'success');
            await loadStudents();
        } else {
            showAlert(result.message || 'Delete failed', 'error');
        }
    } catch (error) {
        showAlert('Database connection failed.', 'error');
    }
}

const getOrdinalSuffix = (num) => {
    const j = num % 10, k = num % 100;
    return (j == 1 && k != 11) ? "st" : (j == 2 && k != 12) ? "nd" : (j == 3 && k != 13) ? "rd" : "th";
};

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
    const fields = ['student_number', 'firstname', 'lastname', 'email'].reduce((acc, name) => 
        (acc[name] = form.querySelector(`input[name="${name}"]`), acc), {});
    
    const generateEmail = () => {
        const [num, first, last] = [fields.student_number.value, fields.firstname.value.toLowerCase(), fields.lastname.value.toLowerCase()];
        if (num && first && last) fields.email.value = `${first.charAt(0)}${last}.${num.toLowerCase()}@umak.edu.ph`;
    };
    
    ['student_number', 'firstname', 'lastname'].forEach(f => fields[f].addEventListener('blur', generateEmail));
}

function toggleStudentForm() {
    const form = document.getElementById('studentFormContainer');
    const addButton = document.querySelector('button[onclick="toggleStudentForm()"]');
    const listContainer = document.getElementById('students-list');
    const isHidden = form.style.display === 'none';
    
    listContainer.style.display = isHidden ? 'none' : 'block';
    form.style.display = isHidden ? 'block' : 'none';
    addButton.style.display = isHidden ? 'none' : 'block';
    
    if (isHidden) form.scrollIntoView({ behavior: 'smooth' });
    else loadStudents();
}

async function handleStudentFormSubmit(e) {
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
        const result = await apiCall('admin-students.php', 'POST', data);
        
        if (result.message?.includes('success')) {
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

document.addEventListener('DOMContentLoaded', async () => {
    console.log('Students Management Initialized');
    setupContactNumberValidation();
    setupEmailAutoGeneration();
    
    const studentForm = document.getElementById('studentForm');
    if (studentForm) studentForm.addEventListener('submit', handleStudentFormSubmit);
    
    try {
        await loadStudents();
        console.log('Students page initialized and connected to database');
    } catch (error) {
        console.error('Database connection error:', error);
    }
});
