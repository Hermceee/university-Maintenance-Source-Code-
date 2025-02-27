document.addEventListener('DOMContentLoaded', function () {
    // Fetch and display users based on filters
    function fetchUsers() {
        const searchInput = document.querySelector('#searchInput').value;
        const roleFilter = document.querySelector('#roleFilter').value;
        const unitFilter = document.querySelector('#unitFilter').value;

        const url = new URL('../php/get_filtered_users.php', window.location.href);
        const params = {
            search: searchInput,
            role: roleFilter,
            unit: unitFilter
        };

        // Append parameters only if they have a value
        Object.keys(params).forEach(key => {
            if (params[key]) {
                url.searchParams.append(key, params[key]);
            }
        });

        fetch(url)
            .then(response => response.json())
            .then(data => {
                const tableBody = document.querySelector('#usersTable tbody');
                tableBody.innerHTML = ''; // Clear existing data
                
                if (data.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="9">No users found</td></tr>';
                } else {
                    data.forEach((user, index) => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${index + 1}</td>
                            <td>${user.matric_or_staff_id}</td>
                            <td>${user.name}</td>
                            <td>${user.phone_number}</td>
                            <td>${user.department}</td>
                            <td>${user.faculty}</td>
                            <td>${user.email}</td>
                            <td>${user.role}</td>
                            <td>${user.unit_id}</td>
                        `;
                        tableBody.appendChild(row);
                    });
                }
            })
            .catch(error => console.error('Error fetching data:', error));
    }

    // Fetch and populate role and unit dropdowns
    function fetchDropdownData() {
        // Fetch roles
        fetch('../php/roles.php')  // Correct PHP file for roles
            .then(response => response.json())
            .then(data => {
                const roleFilter = document.querySelector('#roleFilter');
                roleFilter.innerHTML = '<option value="">All Roles</option>'; // Reset options
                
                data.forEach(role => {
                    const option = document.createElement('option');
                    option.value = role.id; // Adjust based on your roles.php structure
                    option.textContent = role.name; // Adjust based on your roles.php structure
                    roleFilter.appendChild(option);
                });
            })
            .catch(error => console.error('Error fetching roles:', error));

        // Fetch units
        fetch('../php/units.php')  // Correct PHP file for units
            .then(response => response.json())
            .then(data => {
                const unitFilter = document.querySelector('#unitFilter');
                unitFilter.innerHTML = '<option value="">All Units</option>'; // Reset options
                
                data.forEach(unit => {
                    const option = document.createElement('option');
                    option.value = unit.id; // Adjust based on your units.php structure
                    option.textContent = unit.name; // Adjust based on your units.php structure
                    unitFilter.appendChild(option);
                });
            })
            .catch(error => console.error('Error fetching units:', error));
    }

    // Event listeners
    document.querySelector('#searchButton').addEventListener('click', fetchUsers);
    document.querySelector('#filterButton').addEventListener('click', fetchUsers);

    // Initial data fetch
    fetchDropdownData(); // Populate role and unit dropdowns
    fetchUsers(); // Fetch initial user data
});
