document.addEventListener('DOMContentLoaded', function() {
    const subDepartmentSelect = document.getElementById('sub_department');
    const issueTypeSelect = document.getElementById('issue_type');
    const submitBtn = document.getElementById('submitBtn');
    
    // Fetch sub-departments dynamically from the database
    fetch('get_sub_departments.php')
        .then(response => response.json())
        .then(data => {
            data.sub_departments.forEach(sub_department => {
                const option = document.createElement('option');
                option.value = sub_department.id;
                option.text = sub_department.name;
                subDepartmentSelect.add(option);
            });
        });

    // Populate issue types based on selected sub-department
    subDepartmentSelect.addEventListener('change', function() {
        const subDepartmentId = subDepartmentSelect.value;
        fetch(`get_issue_types.php?sub_department_id=${subDepartmentId}`)
            .then(response => response.json())
            .then(data => {
                issueTypeSelect.innerHTML = '<option value="">Select Issue Type</option>';
                data.issue_types.forEach(issue => {
                    const option = document.createElement('option');
                    option.value = issue.id;
                    option.text = issue.issue_type;
                    issueTypeSelect.add(option);
                });
            });
    });

    // Enable submit button once HOU approval is granted
    function checkApprovalStatus() {
        // Simulate fetching approval status
        const isApproved = true; // Placeholder logic
        submitBtn.disabled = !isApproved;
    }

    // Run approval check periodically
    setInterval(checkApprovalStatus, 5000); // Poll every 5 seconds
});
