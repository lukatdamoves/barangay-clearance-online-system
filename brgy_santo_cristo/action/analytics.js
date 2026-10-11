document.addEventListener('DOMContentLoaded', function () {
    const multiselects = document.querySelectorAll('.custom-multiselect');
    const monthPicker = document.getElementById('monthPicker');
    const timePeriodTexts = document.querySelectorAll('.time-period-text');
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    
    const registrationElement = document.getElementById('totalRegistrations');
    const clearanceElement = document.getElementById('totalClearances');
    const businessClearanceElement = document.getElementById('totalBusinessClearances');
    
    const analyticsTableBody = document.getElementById('analyticsTableBody');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const errorMessage = document.getElementById('errorMessage');
    const paginationContainer = document.getElementById('paginationContainer');
    const paginationInfo = document.getElementById('paginationInfo');
    const prevPageBtn = document.getElementById('prevPageBtn');
    const nextPageBtn = document.getElementById('nextPageBtn');
    const pageNumbers = document.getElementById('pageNumbers');
    const recordsPerPage = document.getElementById('recordsPerPage');
    
    const exportPDFBtn = document.getElementById('exportPDFBtn');

    let currentPage = 1;
    let totalPages = 1;
    let totalRecords = 0;
    let currentSearchTerm = '';
    let showingAllTimeData = true;
    
    let isRequestInProgress = false;
    let requestAbortController = null;
    let debounceTimer = null;

    const now = new Date();
    monthPicker.value = '';
    timePeriodTexts.forEach(el => el.textContent = '(All Time)');

    function updateTimePeriodDisplay(monthValue) {
        if (monthValue) {
            const [year, month] = monthValue.split('-');
            const monthName = new Date(monthValue + '-01').toLocaleString('default', { month: 'long' });
            timePeriodTexts.forEach(el => el.textContent = `(${monthName} ${year})`);
            showingAllTimeData = false;
        } else {
            timePeriodTexts.forEach(el => el.textContent = '(All Time)');
            showingAllTimeData = true;
        }
    }

    monthPicker.addEventListener('change', function() {
        currentPage = 1;
        updateTimePeriodDisplay(this.value);
        fetchAnalyticsData(1, currentSearchTerm);
    });

    monthPicker.addEventListener('click', function(e) {
        if (typeof this.showPicker === 'function') this.showPicker();
        e.stopPropagation();
    });

    multiselects.forEach(multiselect => {
        const toggleButton = multiselect.querySelector('button:first-child');
        const dropdownMenu = multiselect.querySelector('.dropdown-menu');
        if (toggleButton && dropdownMenu) {
            toggleButton.addEventListener('click', function(e) {
                e.preventDefault();
                dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
            });
        }
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.custom-multiselect')) {
            multiselects.forEach(multiselect => {
                const dropdownMenu = multiselect.querySelector('.dropdown-menu');
                if (dropdownMenu) dropdownMenu.style.display = 'none';
            });
        }
    });

    multiselects.forEach(multiselect => {
        const checkboxes = multiselect.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(cb => cb.checked = true);
    });

    function setupCheckboxListeners() {
        document.querySelectorAll('.custom-multiselect input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const multiselect = this.closest('.custom-multiselect');
                const toggleAllBtn = multiselect?.querySelector('.select-toggle-btn');
                if (toggleAllBtn) {
                    const checkboxes = multiselect.querySelectorAll('input[type="checkbox"]');
                    const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
                    toggleAllBtn.textContent = checkedCount === checkboxes.length ? 'Deselect All' : 'Select All';
                }
                if (debounceTimer) clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    currentPage = 1;
                    fetchAnalyticsData(1, currentSearchTerm);
                }, 300);
            });
        });
    }

    multiselects.forEach(multiselect => {
        const toggleAllBtn = multiselect.querySelector('.select-toggle-btn');
        const checkboxes = multiselect.querySelectorAll('input[type="checkbox"]');
        if (toggleAllBtn) {
            toggleAllBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const allChecked = Array.from(checkboxes).some(cb => !cb.checked);
                checkboxes.forEach(cb => cb.checked = allChecked);
                this.textContent = allChecked ? 'Deselect All' : 'Select All';
                currentPage = 1;
                fetchAnalyticsData(1, currentSearchTerm);
            });
        }
    });

    setupCheckboxListeners();

    recordsPerPage.addEventListener('change', function() {
        currentPage = 1;
        fetchAnalyticsData(1, currentSearchTerm);
    });

    function showLoading() {
        loadingIndicator.style.display = 'block';
        errorMessage.style.display = 'none';
        analyticsTableBody.innerHTML = '';
    }

    function hideLoading() {
        loadingIndicator.style.display = 'none';
    }

    function showError(message = 'Error loading data. Please try again.') {
        errorMessage.style.display = 'block';
        errorMessage.querySelector('p').textContent = message;
        hideLoading();
    }

    function fetchAnalyticsData(page = 1, search = '') {
        if (isRequestInProgress) return;
        if (requestAbortController) requestAbortController.abort();

        requestAbortController = new AbortController();
        isRequestInProgress = true;
        showLoading();

        const month = showingAllTimeData ? '' : monthPicker.value;
        const recordsLimit = recordsPerPage.value;

        const clearanceCheckboxes = document.querySelectorAll('#clearanceMultiselect input[type="checkbox"]:checked');
        const clearanceGroups = Array.from(clearanceCheckboxes).map(cb => cb.value);

        const businessCheckboxes = document.querySelectorAll('#businessMultiselect input[type="checkbox"]:checked');
        const businessGroups = Array.from(businessCheckboxes).map(cb => cb.value);

        const formData = new FormData();
        formData.append('month', month);
        formData.append('page', page);
        formData.append('limit', recordsLimit);
        formData.append('search', search);
        formData.append('showAllTime', showingAllTimeData ? '1' : '0');
        clearanceGroups.forEach(group => formData.append('clearanceGroups[]', group));
        businessGroups.forEach(group => formData.append('businessGroups[]', group));

        fetch('../fetch_analyticsdata.php', {
            method: 'POST',
            body: formData,
            signal: requestAbortController.signal
        })
        .then(response => response.json())
        .then(data => {
            isRequestInProgress = false;
            hideLoading();

            if (data.status === 'success') {
                registrationElement.textContent = data.registration || 0;
                clearanceElement.textContent = data.clearance || 0;
                businessClearanceElement.textContent = data.business_clearance || 0;
                updateTable(data.tableData || []);
                currentPage = page;
                totalPages = data.totalPages || 1;
                totalRecords = data.totalRecords || 0;
                updatePagination();
            } else {
                showError(data.message || 'Error loading data');
            }
        })
        .catch(error => {
            isRequestInProgress = false;
            if (error.name === 'AbortError') return;
            console.error('Fetch error:', error);
            showError('Network error. Please check your connection.');
        });
    }

    function updateTable(data) {
        analyticsTableBody.innerHTML = '';
        if (data.length === 0) {
            analyticsTableBody.innerHTML = '<tr><td colspan="8" style="text-align: center;">No data found</td></tr>';
            return;
        }

        data.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><img src="${row.photo ? `../uploads/image.php?file=${encodeURIComponent(row.photo)}` :'../image/default-user. png'}" alt="User Photo" width="40" height="40" style="border-radius: 50%; object-fit: cover;" onerror="this.src='../image/default-user.png'"></td>
                <td>${escapeHtml(row.surname || '')}</td>
                <td>${escapeHtml(row.firstname || '')}</td>
                <td>${escapeHtml(row.middlename || '')}</td>
                <td>${escapeHtml(row.type || '')}</td>
                <td>${escapeHtml(row.category || '')}</td>
                <td>${formatDate(row.date_applied)}</td>
                <td><span class="status-badge status-${row.status}">${escapeHtml(row.status || '')}</span></td>
            `;
            analyticsTableBody.appendChild(tr);
        });
    }

    function updatePagination() {
        const start = (currentPage - 1) * parseInt(recordsPerPage.value) + 1;
        const end = Math.min(currentPage * parseInt(recordsPerPage.value), totalRecords);
        paginationInfo.textContent = `Showing ${start} - ${end} of ${totalRecords} records`;
        prevPageBtn.disabled = currentPage <= 1;
        nextPageBtn.disabled = currentPage >= totalPages;
        updatePageNumbers();
    }

    function updatePageNumbers() {
        pageNumbers.innerHTML = '';
        const maxVisible = 5;
        let start = Math.max(1, currentPage - Math.floor(maxVisible / 2));
        let end = Math.min(totalPages, start + maxVisible - 1);
        if (end - start < maxVisible - 1) start = Math.max(1, end - maxVisible + 1);
        for (let i = start; i <= end; i++) {
            const btn = document.createElement('button');
            btn.className = `page-btn ${i === currentPage ? 'active' : ''}`;
            btn.textContent = i;
            btn.addEventListener('click', () => fetchAnalyticsData(i, currentSearchTerm));
            pageNumbers.appendChild(btn);
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    searchForm.addEventListener('submit', e => {
        e.preventDefault();
        currentSearchTerm = searchInput.value.trim();
        currentPage = 1;
        fetchAnalyticsData(1, currentSearchTerm);
    });

    clearSearchBtn.addEventListener('click', () => {
        searchInput.value = '';
        currentSearchTerm = '';
        currentPage = 1;
        fetchAnalyticsData(1, '');
    });

    prevPageBtn.addEventListener('click', () => {
        if (currentPage > 1) fetchAnalyticsData(currentPage - 1, currentSearchTerm);
    });

    nextPageBtn.addEventListener('click', () => {
        if (currentPage < totalPages) fetchAnalyticsData(currentPage + 1, currentSearchTerm);
    });


    exportPDFBtn.addEventListener('click', () => {
        const month = monthPicker.value;
        const clearanceGroups = Array.from(document.querySelectorAll('#clearanceMultiselect input[type="checkbox"]:checked')).map(cb => cb.value);
        const businessGroups = Array.from(document.querySelectorAll('#businessMultiselect input[type="checkbox"]:checked')).map(cb => cb.value);
        const recordsLimit = recordsPerPage.value;

        const params = new URLSearchParams();
        
        // Handle month and year separately
        if (month) {
            const [year, monthNum] = month.split('-');
            params.append('month', monthNum);
            params.append('year', year);
        }
        
        // Use the correct parameter names that PHP expects
        clearanceGroups.forEach(group => params.append('clearanceFilters[]', group));
        businessGroups.forEach(group => params.append('businessFilters[]', group));
        
        // Add current registration count from the page
        const currentRegistrationCount = registrationElement.textContent || '0';
        params.append('registrationCount', currentRegistrationCount);
        
        params.append('search', currentSearchTerm);
        params.append('limit', recordsLimit);
        params.append('showAllTime', showingAllTimeData ? '1' : '0');

        window.open(`../export_pdf.php?${params.toString()}`, '_blank');
    });
    

    fetchAnalyticsData(1, '');
});