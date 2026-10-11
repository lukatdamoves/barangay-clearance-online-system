document.addEventListener('DOMContentLoaded', () => {
    // Get the tab buttons and request list container
    const clearanceTab = document.getElementById('clearance-tab');
    const businessTab = document.getElementById('business-tab');
    const approvedTab = document.getElementById('approved-tab');
    const rejectedTab = document.getElementById('rejected-tab');    
    const requestList = document.getElementById('request-list');
    const paginationContainer = document.getElementById('paginationContainer');
    const paginationInfo = document.getElementById('paginationInfo');
    
    
    let currentTab = localStorage.getItem('currentTab') || 'clearance';
    let currentSortCriterion = localStorage.getItem('sortCriterion') || 'date';
    let sortDirection = JSON.parse(localStorage.getItem('sortDirection')) || {
        name: 'desc',
        date: 'desc',
        status: 'asc'
    };

    let adminCurrentPage = 1;
    let adminTotalPages = 1;
    let adminTotalRecords = 0;
    const recordsPerPageDropdown = document.getElementById('recordsPerPage');

    function setupPagination({
        containerInfo,
        containerNumbers,
        btnPrev,
        btnNext,
        totalRecords,
        recordsPerPage,
        currentPage,
        totalPages,
        onPageChange
    }) {
        // Update info text
        const start = (currentPage - 1) * recordsPerPage + 1;
        const end = Math.min(currentPage * recordsPerPage, totalRecords);
        containerInfo.textContent = totalRecords === 0
            ? 'No records to show'
            : `Showing ${start} - ${end} of ${totalRecords} records`;
    
        // Enable/disable buttons
        btnPrev.disabled = currentPage <= 1;
        btnNext.disabled = currentPage >= totalPages;
    
        // Page number buttons
        containerNumbers.innerHTML = '';
        const maxVisible = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
        let endPage = Math.min(totalPages, startPage + maxVisible - 1);
        if (endPage - startPage < maxVisible - 1) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }
    
        for (let i = startPage; i <= endPage; i++) {
            const btn = document.createElement('button');
            btn.className = `page-btn ${i === currentPage ? 'active' : ''}`;
            btn.textContent = i;
            btn.addEventListener('click', () => onPageChange(i));
            containerNumbers.appendChild(btn);
        }
    
        // Prev/Next buttons
        btnPrev.onclick = () => {
            if (currentPage > 1) onPageChange(currentPage - 1);
        };
        btnNext.onclick = () => {
            if (currentPage < totalPages) onPageChange(currentPage + 1);
        };
    }

    recordsPerPageDropdown.addEventListener('change', () => {
        adminCurrentPage = 1;
        if (currentTab === 'clearance') {
            loadClearanceRequests(currentSortCriterion, sortDirection[currentSortCriterion]);
        } else if (currentTab === 'business') {
            loadBusinessRequests(currentSortCriterion, sortDirection[currentSortCriterion]);
        } else if (currentTab === 'approved') {
            loadApprovedClearances(currentSortCriterion, sortDirection[currentSortCriterion]);
        }
        else if (currentTab === 'rejected') {
            loadRejectedClearances(currentSortCriterion, sortDirection[currentSortCriterion]);
        }
        
    });


    const savePreferences = () => {
        localStorage.setItem('currentTab', currentTab);
        localStorage.setItem('sortCriterion', currentSortCriterion);
        localStorage.setItem('sortDirection', JSON.stringify(sortDirection));
    };

    // Function to save sort direction to localStorage
    const saveSortDirection = () => {
        localStorage.setItem('sortDirection', JSON.stringify(sortDirection));
    };

    updateArrow('name', sortDirection.name);
    updateArrow('date', sortDirection.date);
    updateArrow('status', sortDirection.status);
    
     const sortRequests = (requests, criterion, direction = 'asc') => {
        return requests.sort((a, b) => {
            if (criterion === 'name') {
                return direction === 'asc'
                    ? a.name.localeCompare(b.name)
                    : b.name.localeCompare(a.name);
            } else if (criterion === 'date') {
                return direction === 'asc'
                    ? new Date(a.date_submitted) - new Date(b.date_submitted)
                    : new Date(b.date_submitted) - new Date(a.date_submitted);
            } else if (criterion === 'status') {
                // Define the order of statuses for sorting
                const statusOrder = {
                    'For Signature': 1,
                    'Approved': 2,
                    'Rejected': 3,
                    '': 0  // For empty/null statuses
                };
                
                const aStatus = a.status || '';
                const bStatus = b.status || '';
                
                return direction === 'asc'
                    ? statusOrder[aStatus] - statusOrder[bStatus]
                    : statusOrder[bStatus] - statusOrder[aStatus];
            }
            return 0;
        });
    };

    const searchInput = document.getElementById('search-input');

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        const rows = document.querySelectorAll('#request-list tr');
        
        rows.forEach(row => {
            const nameCell = row.cells[0]; // First column (name)
            const emailCell = row.cells[1]; // Second column (email)
            
            if (nameCell && emailCell) {
                const name = nameCell.textContent.toLowerCase();
                const email = emailCell.textContent.toLowerCase();
                
                // Show row if search term matches name or email
                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
    })



    document.getElementById('sort-status').addEventListener('click', () => {
        sortDirection.status = sortDirection.status === 'asc' ? 'desc' : 'asc';
        currentSortCriterion = 'status';
        updateArrow('status', sortDirection.status);
        savePreferences();
        
        if (currentTab === 'clearance') {
            loadClearanceRequests('status', sortDirection.status);
        } else if (currentTab === 'business') {
            loadBusinessRequests('status', sortDirection.status);
        } else if (currentTab === 'approved') {
            loadApprovedClearances('status', sortDirection.status);
        } else if (currentTab === 'rejected') { 
            loadRejectedClearances('status', sortDirection.status);
        }
    });

    document.getElementById('sort-date').addEventListener('click', () => {
        sortDirection.date = sortDirection.date === 'asc' ? 'desc' : 'asc';
        currentSortCriterion = 'date';
        updateArrow('date', sortDirection.date);
        savePreferences();
        
        if (currentTab === 'clearance') {
            loadClearanceRequests('date', sortDirection.date);
        } else if (currentTab === 'business') {
            loadBusinessRequests('date', sortDirection.date);
        } else if (currentTab === 'approved') {
            loadApprovedClearances('date', sortDirection.date);
        }
    });

    document.getElementById('sort-name').addEventListener('click', () => {
        sortDirection.name = sortDirection.name === 'asc' ? 'desc' : 'asc';
        currentSortCriterion = 'name';
        updateArrow('name', sortDirection.name);
        savePreferences();
        
        if (currentTab === 'clearance') {
            loadClearanceRequests('name', sortDirection.name);
        } else if (currentTab === 'business') {
            loadBusinessRequests('name', sortDirection.name);
        } else if (currentTab === 'approved') {
            loadApprovedClearances('name', sortDirection.name);
        }
    });
    
    // Function to update the arrow direction
    function updateArrow(criterion, direction) {
        const arrowElement = document.getElementById(criterion + '-arrow');
        
        if (criterion === 'name') {
            // For name: down arrow = Z-A (desc), up arrow = A-Z (asc)
            arrowElement.innerHTML = direction === 'desc' ? '↓' : '↑';
        } else if (criterion === 'date') {
            // For date: down arrow = newest first (desc), up arrow = oldest first (asc)
            arrowElement.innerHTML = direction === 'desc' ? '↓' : '↑';
        } else if (criterion === 'status') {
            // For status: up arrow = For Signature → Approved → Rejected (asc)
            // down arrow = Rejected → Approved → For Signature (desc)
            arrowElement.innerHTML = direction === 'asc' ? '↑' : '↓';
        }
    }

    // Function to load clearance requests via AJAX
    const loadClearanceRequests = (sortBy = currentSortCriterion, direction = sortDirection[currentSortCriterion]) => {
        fetch('../get_clearance_requests.php')
            .then(response => response.json())
            .then(data => {
                if (data.status !== "success" || !Array.isArray(data.data)) return;
    
                let requests = data.data;
                requests = requests.filter(request => request.status !== 'Approved' && request.status !== 'Rejected');
                requests = sortRequests(requests, sortBy, direction);
    
                // ---- Pagination logic starts here ----
                adminTotalRecords = requests.length;
                const perPage = parseInt(recordsPerPageDropdown.value);
                adminTotalPages = Math.ceil(adminTotalRecords / perPage);
    
                // Slice data for current page
                const paginatedRequests = requests.slice(
                    (adminCurrentPage - 1) * perPage,
                    adminCurrentPage * perPage
                );
    
                // Clear list and render only paginated data
                requestList.innerHTML = '';
                paginatedRequests.forEach(request => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>
                            <img src="${request.applicant_photo 
                                ? `../uploads/image.php?file=${encodeURIComponent(request.applicant_photo)}` 
                                : '../image/default-user.png'}"
                                alt="User Photo"
                                width="40" height="40"
                                style="border-radius: 50%; object-fit: cover;"
                                onerror="this.src='../image/default-user.png'">
                        </td>
                        <td>${request.name}</td>
                        <td>${request.email}</td>
                        <td>${request.purpose ? request.purpose : 'Clearance'}</td>
                        <td>${request.date_submitted}</td>
                        <td>
                            <button class="view-btn" 
                                data-user-id="${request.user_id}" 
                                data-form-id="${request.id}" 
                                data-type="${request.type === 'business' ? 'business' : 'clearance'}">View</button>
                            <button class="download-docx-btn" data-user-id="${request.user_id}" data-form-id="${request.id}" data-file-path="${request.file_path}" data-type="clearance">
                                ${request.file_path ? 'Download' : 'Generate'}
                            </button>
                            <button class="approve-btn" data-user-id="${request.user_id}" data-form-id="${request.id}" data-type="clearance">Approve</button>
                            <button class="reject-btn" data-user-id="${request.user_id}" data-form-id="${request.id}" data-type="clearance">Delete</button>
                        </td>
                        <td>
                            <select class="status-dropdown" data-id="${request.id}" data-type="clearance" data-status="${request.status}">
                                <option value="" disabled ${!request.status ? 'selected' : ''}>Change Status</option>
                                <option value="For Signature" ${request.status === 'For Signature' ? 'selected' : ''}>For Signature</option>
                                <option value="Rejected" ${request.status === 'Rejected' ? 'selected' : ''}>Rejected</option>
                            </select>
                        </td>
                    `;
                    requestList.appendChild(row);
                });
    
                // ---- Call setupPagination ----
                setupPagination({
                    containerInfo: paginationInfo,
                    containerNumbers: pageNumbers,
                    btnPrev: prevPageBtn,
                    btnNext: nextPageBtn,
                    totalRecords: adminTotalRecords,
                    recordsPerPage: perPage,
                    currentPage: adminCurrentPage,
                    totalPages: adminTotalPages,
                    onPageChange: (page) => {
                        adminCurrentPage = page;
                        loadClearanceRequests(currentSortCriterion, sortDirection[currentSortCriterion]);
                    }
                });
            })
            .catch(error => console.error('Error loading clearance requests:', error));
    };
    
        // Function to load approved clearances
        const loadApprovedClearances = async (sortBy = currentSortCriterion, direction = sortDirection[currentSortCriterion]) => {
            try {
                const [clearanceRes, businessRes] = await Promise.all([
                    fetch('../get_clearance_requests.php'),
                    fetch('../get_business_requests.php')
                ]);
        
                const clearanceData = await clearanceRes.json();
                const businessData = await businessRes.json();
        
                let requests = [];
        
                // Filter and map clearance requests (only approved)
                if (clearanceData.status === 'success' && Array.isArray(clearanceData.data)) {
                    requests = requests.concat(
                        clearanceData.data
                            .filter(req => req.status === 'Approved' && req.user_id)
                            .map(req => ({ ...req, type: 'clearance' }))
                    );
                }
        
                // Filter and map business requests (only approved)
                if (businessData.status === 'success' && Array.isArray(businessData.data)) {
                    requests = requests.concat(
                        businessData.data
                            .filter(req => req.status === 'Approved' && req.user_id)
                            .map(req => ({ ...req, type: 'business' }))
                    );
                }
        
                // Sort requests
                requests = sortRequests(requests, sortBy, direction);
        
                // ---- Pagination logic ----
                adminTotalRecords = requests.length;
                const perPage = parseInt(recordsPerPageDropdown.value);
                adminTotalPages = Math.ceil(adminTotalRecords / perPage);
        
                // Slice data for current page
                const paginatedRequests = requests.slice(
                    (adminCurrentPage - 1) * perPage,
                    adminCurrentPage * perPage
                );
        
                // Render rows
                requestList.innerHTML = '';
                paginatedRequests.forEach(request => {
                    const row = document.createElement('tr');
                    const dataType = request.type;
                    row.innerHTML = `
                        <td>
                            <img src="${dataType === 'clearance'
                                ? (request.applicant_photo
                                    ? `../uploads/image.php?file=${encodeURIComponent(request.applicant_photo)}`
                                    : '../image/default-user.png')
                                : (request.owner_photo
                                    ? `../uploads/image.php?file=${encodeURIComponent(request.owner_photo)}`
                                    : '../image/default-user.png')}"
                                alt="User Photo"
                                width="40" height="40"
                                style="border-radius: 50%; object-fit: cover;"
                                onerror="this.src='../image/default-user.png'">
                        </td>
                        <td>${request.name}</td>
                        <td>${request.email}</td>
                        <td>${dataType === 'clearance' 
                                ? (request.purpose ? request.purpose : 'Clearance') 
                                : (request.application_type ? request.application_type : 'Business')}</td>
                        <td>${request.date_submitted}</td>
                        <td>
                            <button class="view-btn"
                                data-user-id="${request.user_id}"
                                data-form-id="${request.form_id || request.id}"
                                data-type="${dataType}">
                                View
                            </button>
                            <button class="download-docx-btn"
                                data-user-id="${request.user_id}"
                                data-form-id="${dataType === 'business' ? request.form_id : request.id}"
                                data-file-path="${request.file_path ? request.file_path : 'null'}"
                                data-type="${dataType}">
                                ${request.file_path ? 'Download' : 'Generate'}
                            </button>
                        </td>
                        <td>
                            <span class="status-label approved">Approved</span>
                        </td>
                    `;
                    requestList.appendChild(row);
                });
        
                // ---- Setup pagination controls ----
                setupPagination({
                    containerInfo: paginationInfo,
                    containerNumbers: pageNumbers,
                    btnPrev: prevPageBtn,
                    btnNext: nextPageBtn,
                    totalRecords: adminTotalRecords,
                    recordsPerPage: perPage,
                    currentPage: adminCurrentPage,
                    totalPages: adminTotalPages,
                    onPageChange: (page) => {
                        adminCurrentPage = page;
                        loadApprovedClearances(currentSortCriterion, sortDirection[currentSortCriterion]);
                    }
                });
            } catch (error) {
                console.error('Error loading approved clearances:', error);
            }
        };

        const loadRejectedClearances = async (sortBy = currentSortCriterion, direction = sortDirection[currentSortCriterion]) => {
            try {
                const [clearanceRes, businessRes] = await Promise.all([
                    fetch('../get_clearance_requests.php'),
                    fetch('../get_business_requests.php')
                ]);
        
                const clearanceData = await clearanceRes.json();
                const businessData = await businessRes.json();
        
                let requests = [];
        
                // Filter and map clearance requests (only rejected)
                if (clearanceData.status === 'success' && Array.isArray(clearanceData.data)) {
                    requests = requests.concat(
                        clearanceData.data
                            .filter(req => req.status === 'Rejected' && req.user_id)
                            .map(req => ({ ...req, type: 'clearance' }))
                    );
                }
        
                // Filter and map business requests (only rejected)
                if (businessData.status === 'success' && Array.isArray(businessData.data)) {
                    requests = requests.concat(
                        businessData.data
                            .filter(req => req.status === 'Rejected' && req.user_id)
                            .map(req => ({ ...req, type: 'business' }))
                    );
                }
        
                // Sort requests
                requests = sortRequests(requests, sortBy, direction);
        
                // ---- Pagination logic ----
                adminTotalRecords = requests.length;
                const perPage = parseInt(recordsPerPageDropdown.value);
                adminTotalPages = Math.ceil(adminTotalRecords / perPage);
        
                // Slice data for current page
                const paginatedRequests = requests.slice(
                    (adminCurrentPage - 1) * perPage,
                    adminCurrentPage * perPage
                );
        
                // Render rows
                requestList.innerHTML = '';
                paginatedRequests.forEach(request => {
                    const row = document.createElement('tr');
                    const dataType = request.type;
                    row.innerHTML = `
                        <td>
                            <img src="${dataType === 'clearance'
                                ? (request.applicant_photo
                                    ? `../uploads/image.php?file=${encodeURIComponent(request.applicant_photo)}`
                                    : '../image/default-user.png')
                                : (request.owner_photo
                                    ? `../uploads/image.php?file=${encodeURIComponent(request.owner_photo)}`
                                    : '../image/default-user.png')}"
                                alt="User Photo"
                                width="40" height="40"
                                style="border-radius: 50%; object-fit: cover;"
                                onerror="this.src='../image/default-user.png'">
                        </td>
                        <td>${request.name}</td>
                        <td>${request.email}</td>
                        <td>${dataType === 'clearance' 
                                ? (request.purpose ? request.purpose : 'Clearance') 
                                : (request.application_type ? request.application_type : 'Business')}</td>
                        <td>${request.date_submitted}</td>
                        <td>
                            <button class="view-btn"
                                data-user-id="${request.user_id}"
                                data-form-id="${request.form_id || request.id}"
                                data-type="${dataType}">
                                View
                            </button>
                            <button class="download-docx-btn"
                                data-user-id="${request.user_id}"
                                data-form-id="${request.form_id || request.id}"
                                data-file-path="${request.file_path || ''}"
                                data-type="${dataType}">
                                ${request.file_path ? 'Download' : 'Generate'}
                            </button>
                        </td>
                        <td>
                            <span class="status-label rejected">Rejected</span>
                        </td>
                    `;
                    requestList.appendChild(row);
                });
        
                // ---- Setup pagination controls ----
                setupPagination({
                    containerInfo: paginationInfo,
                    containerNumbers: pageNumbers,
                    btnPrev: prevPageBtn,
                    btnNext: nextPageBtn,
                    totalRecords: adminTotalRecords,
                    recordsPerPage: perPage,
                    currentPage: adminCurrentPage,
                    totalPages: adminTotalPages,
                    onPageChange: (page) => {
                        adminCurrentPage = page;
                        loadRejectedClearances(currentSortCriterion, sortDirection[currentSortCriterion]);
                    }
                });
            } catch (error) {
                console.error('Error loading rejected clearances:', error);
            }
        };
            
        

    // Function to load business requests via AJAX
    const loadBusinessRequests = (sortBy = currentSortCriterion, direction = sortDirection[currentSortCriterion]) => {
        fetch('../get_business_requests.php')
            .then(response => response.json())
            .then(data => {
                if (data.status !== "success" || !Array.isArray(data.data)) {
                    console.error("Unexpected response format:", data);
                    return;
                }
    
                let requests = data.data;
                requests = requests.filter(request => request.status !== 'Approved' && request.status !== 'Rejected');
                requests = sortRequests(requests, sortBy, direction);
    
                // ---- Pagination logic ----
                adminTotalRecords = requests.length;
                const perPage = parseInt(recordsPerPageDropdown.value);
                adminTotalPages = Math.ceil(adminTotalRecords / perPage);
    
                // Slice data for current page
                const paginatedRequests = requests.slice(
                    (adminCurrentPage - 1) * perPage,
                    adminCurrentPage * perPage
                );
    
                // Clear table and render paginated data
                requestList.innerHTML = '';
                paginatedRequests.forEach(request => {
                    const row = document.createElement('tr');
                    row.classList.add('business-request');
                    row.innerHTML = `
                        <td>
                            <img src="${request.owner_photo 
                                ? `../uploads/image.php?file=${encodeURIComponent(request.owner_photo)}` 
                                : '../image/default-user.png'}"
                                alt="User Photo"
                                width="40" height="40"
                                style="border-radius: 50%; object-fit: cover;"
                                onerror="this.src='../image/default-user.png'">
                        </td>
                        <td>${request.name}</td>
                        <td>${request.email}</td>
                        <td>${request.application_type ? request.application_type : 'Business'}</td>
                        <td>${request.date_submitted}</td>
                        <td>
                            <button class="view-btn" 
                                data-user-id="${request.user_id}" 
                                data-form-id="${request.form_id}" 
                                data-type="business">View</button>
                            <button class="download-docx-btn" data-user-id="${request.user_id}" data-form-id="${request.form_id}" data-file-path="${request.file_path}" data-type="business">
                                ${request.file_path ? 'Download' : 'Generate'}
                            </button>
                            <button class="approve-btn" data-user-id="${request.user_id}" data-form-id="${request.form_id}" data-type="business">Approve</button>
                            <button class="reject-btn" data-user-id="${request.user_id}" data-form-id="${request.form_id}" data-type="business">Delete</button>
                        </td>
                        <td>
                            <select class="status-dropdown" data-id="${request.form_id}" data-type="business" data-status="${request.status}">
                                <option value="" disabled ${!request.status ? 'selected' : ''}>Change Status</option>
                                <option value="For Signature" ${request.status === 'For Signature' ? 'selected' : ''}>For Signature</option>
                                <option value="Rejected" ${request.status === 'Rejected' ? 'selected' : ''}>Rejected</option>
                            </select>
                        </td>
                    `;
                    requestList.appendChild(row);
                });
    
                // ---- Setup pagination controls ----
                setupPagination({
                    containerInfo: paginationInfo,
                    containerNumbers: pageNumbers,
                    btnPrev: prevPageBtn,
                    btnNext: nextPageBtn,
                    totalRecords: adminTotalRecords,
                    recordsPerPage: perPage,
                    currentPage: adminCurrentPage,
                    totalPages: adminTotalPages,
                    onPageChange: (page) => {
                        adminCurrentPage = page;
                        loadBusinessRequests(currentSortCriterion, sortDirection[currentSortCriterion]);
                    }
                });
            })
            .catch(error => console.error('Error loading business requests:', error));
    };
    

    const toggleTab = (tab) => {
        clearanceTab.classList.remove('active');
        businessTab.classList.remove('active');
        approvedTab.classList.remove('active');
        rejectedTab.classList.remove('active'); 
    
        tab.classList.add('active');
        currentTab = tab.dataset.tab;
        const searchInput = document.getElementById('search-input');
        searchInput.value = '';
    
        // Save the current tab preference
        savePreferences();
    
        // Load the appropriate tab with the current sort settings
        if (currentTab === 'clearance') {
            loadClearanceRequests(currentSortCriterion, sortDirection[currentSortCriterion]);
        } else if (currentTab === 'business') {
            loadBusinessRequests(currentSortCriterion, sortDirection[currentSortCriterion]);
        } else if (currentTab === 'approved') {
            loadApprovedClearances(currentSortCriterion, sortDirection[currentSortCriterion]);
        } else if (currentTab === 'rejected') { // <-- Add this
            loadRejectedClearances(currentSortCriterion, sortDirection[currentSortCriterion]);
        }
    };
    
    // Initialize the correct tab on page load
    if (currentTab === 'clearance') {
        toggleTab(clearanceTab);
    } else if (currentTab === 'business') {
        toggleTab(businessTab);
    } else if (currentTab === 'approved') {
        toggleTab(approvedTab);
    } else if (currentTab === 'rejected') { // <-- Add this
        toggleTab(rejectedTab);
    }
    
    
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('download-docx-btn')) {
            const formId = e.target.getAttribute('data-form-id');
            const userId = e.target.getAttribute('data-user-id');
            let filePath = e.target.getAttribute('data-file-path');

            // Check if formId or userId is missing
            if (!formId || !userId) {
                showToast('Form ID or User ID is missing.', true);
                return; // Stop execution if formId or userId is missing
            }

            if (filePath && filePath !== 'null') {
                const link = document.createElement('a');
                link.href = `../${filePath}`;
                link.download = '';
                link.click();
                showToast('Document downloaded successfully!');
            } else {
                const type = e.target.getAttribute('data-type');

                fetch('../generate_docx.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `form_id=${encodeURIComponent(formId)}&user_id=${encodeURIComponent(userId)}&type=${encodeURIComponent(type)}`
                })
                .then(response => response.text())
                .then(data => {
                    try {
                        const jsonData = JSON.parse(data);
                        if (jsonData.status === "success") {
                            const link = document.createElement('a');
                            link.href = `../${jsonData.file_path}`;
                            link.download = '';
                            link.click();

                            e.target.setAttribute('data-file-path', jsonData.file_path);
                            e.target.textContent = 'Download';

                            showToast('Document generated and downloaded successfully!');
                        } else {
                            showToast('Failed to generate document: ' + (jsonData.message || 'Unknown error'), true);
                        }
                    } catch (error) {
                        console.error('Error parsing JSON:', error);
                        showToast('Error parsing response: ' + data, true);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred while generating the document.', true);
                });
            }
        }
    });


    function showToast(message, isError = false) {
        const toast = document.getElementById('docx-toast');
        toast.textContent = message;
        toast.style.backgroundColor = isError ? '#e74c3c' : '#2ecc71';  // Red for error, green for success
        toast.style.display = 'block';
    
        setTimeout(() => {
            toast.style.display = 'none';
        }, 1500); // Hide after 3 seconds
    }

    // Set up event listeners for the tabs
    clearanceTab.addEventListener('click', () => toggleTab(clearanceTab));
    businessTab.addEventListener('click', () => toggleTab(businessTab));
    approvedTab.addEventListener('click', () => toggleTab(approvedTab));
    rejectedTab.addEventListener('click', () => toggleTab(rejectedTab)); 


    document.addEventListener('click', (event) => {
        if (event.target && event.target.classList.contains('view-btn')) {
            const userId = event.target.dataset.userId;
            const formId = event.target.dataset.formId;
            const requestType = event.target.dataset.type;
    
            // Build preview page URL (same as links you already use)
            if (requestType === 'clearance') {
                previewUrl = `../structure/previewpage.php?id=${formId}&type=clearance&embed=1`;
            } else {
                previewUrl = `../structure/previewbusiness.php?form_id=${formId}&type=business&embed=1`;
            }
        
            // Inject iframe into modal
            const previewContent = document.getElementById('previewContent');
            previewContent.innerHTML = `
                <iframe src="${previewUrl}" style="width:100%; height:80vh; border:none;"></iframe>
            `;
    
            // Show modal
            document.getElementById('previewModal').style.display = 'block';
        }
    });
    
    
    
    const imagePreview = document.getElementById('imagePreview');
    let isZoomed = false;

    imagePreview.addEventListener('click', (e) => {
        const rect = imagePreview.getBoundingClientRect();
        const offsetX = e.clientX - rect.left;
        const offsetY = e.clientY - rect.top;

        const xPercent = (offsetX / rect.width) * 100;
        const yPercent = (offsetY / rect.height) * 100;

        if (!isZoomed) {
            imagePreview.style.transformOrigin = `${xPercent}% ${yPercent}%`;
            imagePreview.style.transform = 'scale(3)';
            imagePreview.style.cursor = 'zoom-out';
            isZoomed = true;
        } else {
            imagePreview.style.transform = 'scale(1)';
            imagePreview.style.transformOrigin = 'center center';
            imagePreview.style.cursor = 'zoom-in';
            isZoomed = false;
        }
    });
    
    document.getElementById('closeImagePreview').addEventListener('click', () => {
        document.getElementById('imagePreviewModal').style.display = 'none';
    });
    
    // Optional close if clicked outside the image modal
    window.addEventListener('click', (event) => {
        if (event.target === document.getElementById('imagePreviewModal')) {
            document.getElementById('imagePreviewModal').style.display = 'none';
        }
    });
    
    document.getElementById('closePreview').addEventListener('click', () => {
        document.getElementById('previewModal').style.display = 'none';
    });
    
    // Optional close if clicked outside the preview modal
    window.addEventListener('click', (event) => {
        if (event.target === document.getElementById('previewModal')) {
            document.getElementById('previewModal').style.display = 'none';
        }
    });
             
    // Handle status dropdown change
    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('status-dropdown')) {
            const dropdown = e.target;
            const newStatus = dropdown.value;
            const formId = dropdown.getAttribute('data-id');
            const type = dropdown.getAttribute('data-type');
            let reason = ''; // Initialize reason here
    
            if (newStatus === 'Rejected') {
                // Prompt for rejection reason
                reason = prompt('Enter the reason for rejection:');
                if (reason === null || reason.trim() === '') {
                    showToast('Rejection cancelled or reason empty.', true);
                    dropdown.value = dropdown.getAttribute('data-status'); // Reset to previous value
                    return;
                }
            }
    
            const bodyData = `status=${encodeURIComponent(newStatus)}&form_id=${encodeURIComponent(formId)}&type=${encodeURIComponent(type)}${newStatus === 'Rejected' ? '&reason=' + encodeURIComponent(reason) : ''}`;
    
            fetch('../update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: bodyData
            })
            .then(response => response.text())
            .then(data => {
                if (data.includes('Status updated successfully.') || data.includes('sent') || data.includes('updated')) {
                    showToast('Status updated successfully.');
                    dropdown.setAttribute('data-status', newStatus);
                    
                    // If rejected, reload the list to remove the item
                    if (newStatus === 'Rejected') {
                        if (currentTab === 'clearance') {
                            loadClearanceRequests();
                        } else if (currentTab === 'business') {
                            loadBusinessRequests();
                        }
                    }
                } else {
                    showToast('There was an error updating the status.', true);
                    dropdown.value = dropdown.getAttribute('data-status'); // Revert
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while updating the status.', true);
                dropdown.value = dropdown.getAttribute('data-status');
            });
        }
    });
    
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('approve-btn')) {
            const button = e.target;
            const formId = button.getAttribute('data-form-id');
            const userId = button.getAttribute('data-user-id');
            const type = button.getAttribute('data-type'); // Dynamically get type (clearance or business)

            if (!formId || !userId || !type) {
                showToast('Missing form data.', true);
                return;
            }

            // Confirm approval
            if (!confirm('Are you sure you want to approve this request?')) {
                showToast('Approval cancelled.', true);
                return;
            }

            const bodyData = `status=Approved&form_id=${encodeURIComponent(formId)}&type=${encodeURIComponent(type)}&user_id=${encodeURIComponent(userId)}`;

            fetch('../approve_clearance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: bodyData
            })
            .then(response => response.text())
            .then(data => {
                if (data.includes('sent') || data.includes('updated') || data.includes('success')) {
                    showToast('Request approved successfully.');

                    // Update the dropdown value
                    const dropdown = document.querySelector(`select.status-dropdown[data-id="${formId}"][data-type="${type}"]`);
                    if (dropdown) {
                        dropdown.value = 'Approved';
                        dropdown.setAttribute('data-status', 'Approved');
                    } else {
                        console.error('Dropdown not found:', `select.status-dropdown[data-id="${formId}"][data-type="${type}"]`);
                    }
                } else {
                    showToast('Approval failed: ' + data, true);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while approving the request.', true);
            });
        }
    });


    // Handle reject button click 
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('reject-btn')) {
            const button = e.target;
            const formId = button.getAttribute('data-form-id');
            const type = button.getAttribute('data-type');
            
            // Confirm rejection (this could be for both clearance or business)
            if (!confirm('Are you sure you want to reject this request?')) {
                showToast('Rejection cancelled.', true);
                return;
            }

            // Prepare data to send in the POST request
            const data = new URLSearchParams();
            data.append('form_id', formId);
            data.append('type', type);

            // Send the rejection request to the server
            fetch('../delete_clearance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: data.toString() // Send the data in the request body
            })
            .then(response => response.json())  // Expect a JSON response
            .then(data => {
                if (data.status === 'success') {
                    showToast('Request rejected successfully.');
                    
                    // Reload the current tab
                    if (currentTab === 'clearance') {
                        loadClearanceRequests();
                    } else if (currentTab === 'business') {
                        loadBusinessRequests();
                    }
                } else {
                    showToast('Rejection failed: ' + data.message, true);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while rejecting the request.', true);
            });
        }
    });
    
    // Initial load of clearance requests
    if (currentTab === 'clearance') {
        loadClearanceRequests(currentSortCriterion, sortDirection[currentSortCriterion]);
    } else if (currentTab === 'business') {
        loadBusinessRequests(currentSortCriterion, sortDirection[currentSortCriterion]);
    } else if (currentTab === 'approved') {
        loadApprovedClearances(currentSortCriterion, sortDirection[currentSortCriterion]);
    } else if (currentTab === 'rejected') {
        loadRejectedClearances(currentSortCriterion, sortDirection[currentSortCriterion]);
    }
    
    // Auto-refresh with saved preferences
    setInterval(() => {
        const searchInput = document.getElementById('search-input');
        const searchTerm = searchInput.value.toLowerCase().trim();
        
        // Only auto-refresh if search input is empty
        if (searchTerm === '') {
            if (currentTab === 'clearance') {
                loadClearanceRequests(currentSortCriterion, sortDirection[currentSortCriterion]);
            } else if (currentTab === 'business') {
                loadBusinessRequests(currentSortCriterion, sortDirection[currentSortCriterion]);
            } else if (currentTab === 'approved') {
                loadApprovedClearances(currentSortCriterion, sortDirection[currentSortCriterion]);
            } else if (currentTab === 'rejected') {
                loadRejectedClearances(currentSortCriterion, sortDirection[currentSortCriterion]);
            }
        }
    }, 10000);
});
