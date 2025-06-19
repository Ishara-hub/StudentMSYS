<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Get active batches
$batches = [];
$batchQuery = $conn->query("SELECT b.batch_id, b.batch_name, c.course_name, c.total_fee
                          FROM batches b 
                          JOIN courses c ON b.course_id = c.course_id
                          WHERE b.is_active = TRUE");
while ($row = $batchQuery->fetch_assoc()) {
    $batches[] = $row;
}

// Get active agents
$agents = [];
$agentQuery = $conn->query("SELECT agent_id, agent_name, contact_number FROM agents WHERE status = 'active' ORDER BY agent_name");
while ($row = $agentQuery->fetch_assoc()) {
    $agents[] = $row;
}
?>

<?php include '../includes/header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Member</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css1/components/member-form.css">
    <style>
        .error-message {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
        .form-section {
            border-right: 1px solid #dee2e6;
            padding-right: 20px;
        }
        #spouseSection {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }
        .agent-info {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: -5px;
        }
    </style>
    <script>
        // 🔹 Generate Auto NIC
        function generateNIC() {
            let randomNIC = "9000" + Math.floor(100000 + Math.random() * 900000) + "v";
            document.getElementById("nic").value = randomNIC;
            autoFillDOBGender(randomNIC);
        }

        // 🔹 Generate Initials from Full Name
        function generateInitials() {
            let fullName = document.getElementById("full_name").value.trim();
            if (fullName === "") return;

            let words = fullName.split(" ");
            let lastName = words.pop();
            let initials = words.map(word => word.charAt(0).toUpperCase()).join(".") + ". " + lastName;
            document.getElementById("initials").value = initials;
        }

        // 🔹 Auto-Fill DOB & Gender from NIC
        function autoFillDOBGender(nic) {
            let year, days, gender;
            
            if (nic.length === 10 && (nic.endsWith("V") || nic.endsWith("v") || nic.endsWith("X") || nic.endsWith("x"))) {
                year = "19" + nic.substring(0, 2);
                days = parseInt(nic.substring(2, 5));
            } else if (nic.length === 12) {
                year = nic.substring(0, 4);
                days = parseInt(nic.substring(4, 7));
            } else {
                document.getElementById("dob").value = "";
                document.getElementById("gender").value = "";
                return;
            }

            if (days > 500) {
                gender = "Female";
                days -= 500;
            } else {
                gender = "Male";
            }

            let birthDate = new Date(year, 0, days);
            let formattedDOB = birthDate.toISOString().split("T")[0];

            document.getElementById("dob").value = formattedDOB;
            document.getElementById("gender").value = gender;
        }
        
        // Toggle NIC field
        function toggleNICField() {
            const nicField = document.getElementById("nic");
            const noNICCheckbox = document.getElementById("no_nic");
            
            if (noNICCheckbox.checked) {
                nicField.disabled = true;
                nicField.required = false;
                nicField.value = "N/A";
                document.getElementById("dob").value = "";
                document.getElementById("gender").value = "";
            } else {
                nicField.disabled = false;
                nicField.required = true;
                nicField.value = "";
            }
        }

        // Form validation
        function validateForm() {
            let isValid = true;
            let errorMessages = [];

            // Clear previous errors
            document.querySelectorAll('.error-message').forEach(el => el.remove());

            // Phone Validation
            const phone = document.querySelector("[name='contact_number']").value;
            if (!/^(0|94|\+94)?[1-9][0-9]{8}$/.test(phone)) {
                showError("contact_number", "Invalid phone number (e.g., 0712345678)");
                isValid = false;
            }

            // NIC Validation (if not "N/A")
            const nic = document.getElementById("nic").value;
            if (nic !== "N/A" && !/^([0-9]{9}[VXvx]|[0-9]{12})$/.test(nic)) {
                showError("nic", "Invalid NIC (Use 123456789V or 199012345678)");
                isValid = false;
            }

            // Full Name Validation
            const fullName = document.getElementById("full_name").value;
            if (!/^[a-zA-Z .'-]+$/.test(fullName)) {
                showError("full_name", "Name can only contain letters, spaces, and hyphens");
                isValid = false;
            }

            // Spouse validation if married
            const civilStatus = document.querySelector("[name='civil_status']").value;
            if (civilStatus === "Married") {
                const spouseNic = document.querySelector("[name='spouse_nic']").value;
                const spouseName = document.querySelector("[name='spouse_name']").value;
                
                if (!spouseNic) {
                    showError("spouse_nic", "Spouse NIC is required");
                    isValid = false;
                }
                if (!spouseName) {
                    showError("spouse_name", "Spouse name is required");
                    isValid = false;
                }
            }

            if (!isValid) {
                window.scrollTo(0, 0);
            }

            return isValid;
        }

        function showError(fieldName, message) {
            const field = document.querySelector(`[name="${fieldName}"]`);
            const errorElement = document.createElement('div');
            errorElement.className = 'error-message';
            errorElement.textContent = message;
            field.parentNode.appendChild(errorElement);
            field.classList.add('is-invalid');
        }

        // Show/hide spouse section
        function toggleSpouseSection() {
            const civilStatus = document.querySelector("[name='civil_status']").value;
            const spouseSection = document.getElementById("spouseSection");
            spouseSection.style.display = (civilStatus === 'Married') ? 'block' : 'none';
        }

        // Show agent contact info when selected
        function showAgentInfo(selectElement) {
            const agentInfoDiv = document.getElementById('agentInfo');
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const contactNumber = selectedOption.getAttribute('data-contact');
            
            if (contactNumber) {
                agentInfoDiv.textContent = `Contact: ${contactNumber}`;
                agentInfoDiv.style.display = 'block';
            } else {
                agentInfoDiv.style.display = 'none';
            }
        }
    </script>
</head>
<body>
    <div class="container mt-4">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <div class="form-card">
            <h2 class="mb-4">Add New Member</h2>
            <form method="POST" action="../processes/student_registration.php" onsubmit="return validateForm()">
                <div class="row">
                    <!-- Left Column (Core Details) -->
                    <div class="col-md-6 form-section">
                        <div class="mb-3">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <select name="title" class="form-select" required>
                                <option value="">-- Select --</option>
                                <option value="Mr">Mr</option>
                                <option value="Mrs">Mrs</option>
                                <option value="Miss">Miss</option>
                                <option value="Dr">Dr</option>
                            </select>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="no_nic" onclick="toggleNICField()">
                            <label class="form-check-label">No NIC</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" id="full_name" class="form-control" onkeyup="generateInitials()" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Initials</label>
                            <input type="text" name="initials" id="initials" class="form-control" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">NIC <span class="text-danger">*</span></label>
                            <input type="text" name="nic" id="nic" class="form-control" onkeyup="autoFillDOBGender(this.value)" required>
                            <button type="button" class="btn btn-warning mt-2" onclick="generateNIC()">Generate NIC</button>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="dob" id="dob" class="form-control" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gender</label>
                            <input type="text" name="gender" id="gender" class="form-control" readonly>
                        </div>
                    </div>  
                    
                    <!-- Right Column (Additional Details) -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" name="contact_number" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Civil Status</label>
                            <select name="civil_status" class="form-select" onchange="toggleSpouseSection()">
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Divorced">Divorced</option>
                            </select>
                        </div>
                        <div id="spouseSection" style="display:none;">
                            <h5>Spouse Details</h5>
                            <div class="mb-3">
                                <label class="form-label">Spouse NIC</label>
                                <input type="text" name="spouse_nic" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Spouse Name</label>
                                <input type="text" name="spouse_name" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mother's Name</label>
                            <input type="text" name="mother_name" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Father's Name</label>
                            <input type="text" name="father_name" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Agent</label>
                            <select name="agent_id" class="form-select" onchange="showAgentInfo(this)">
                                <option value="">-- No Agent --</option>
                                <?php foreach ($agents as $agent): ?>
                                    <option value="<?= $agent['agent_id'] ?>" data-contact="<?= htmlspecialchars($agent['contact_number']) ?>">
                                        <?= htmlspecialchars($agent['agent_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="agentInfo" class="agent-info" style="display:none;"></div>
                        </div>
                        <div class="mb-3">
                            <label for="batch_id" class="form-label">Select Batch <span class="text-danger">*</span></label>
                            <select class="form-select" id="batch_id" name="batch_id" required>
                                <option value="">-- Select Batch --</option>
                                <?php foreach ($batches as $batch): ?>
                                <option value="<?= $batch['batch_id'] ?>" data-fee="<?= $batch['total_fee'] ?>">
                                    <?= htmlspecialchars($batch['course_name']) ?> - <?= htmlspecialchars($batch['batch_name']) ?>
                                    (<?= DEFAULT_CURRENCY ?><?= number_format($batch['total_fee']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="initial_payment" class="form-label">Initial Payment</label>
                            <div class="input-group">
                                <span class="input-group-text"><?= DEFAULT_CURRENCY ?></span>
                                <input type="number" class="form-control" id="initial_payment" name="initial_payment" min="0" step="0.01">
                            </div>
                            <div class="form-text">Balance: <span id="balance_display"><?= DEFAULT_CURRENCY ?>0.00</span></div>
                        </div>
                    </div>
                </div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <button type="submit" class="btn btn-primary me-md-2">
                        <i class="fas fa-save"></i> Register Student
                    </button>
                    <a href="../student/dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>        
        </div>
    </div>  
    <script>
        // Update balance display
        document.getElementById('batch_id').addEventListener('change', updateBalance);
        document.getElementById('initial_payment').addEventListener('input', updateBalance);

        function updateBalance() {
            const batchSelect = document.getElementById('batch_id');
            const fee = batchSelect.options[batchSelect.selectedIndex]?.dataset.fee || 0;
            const payment = parseFloat(document.getElementById('initial_payment').value) || 0;
            const balance = fee - payment;
            document.getElementById('balance_display').textContent = 
                '<?= DEFAULT_CURRENCY ?>' + balance.toFixed(2);
            
            document.getElementById('initial_payment').max = fee;
        }

        // Initialize spouse section
        document.addEventListener('DOMContentLoaded', function() {
            toggleSpouseSection();
        });
    </script>
    <?php include '../includes/footer.php'; ?>
</body>
</html>