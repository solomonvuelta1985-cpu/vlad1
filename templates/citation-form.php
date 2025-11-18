<?php
/**
 * Citation Form Template
 * Renders the traffic citation ticket form
 *
 * Required variables:
 * - $next_ticket: Next ticket number
 * - $driver_data: Driver information (optional)
 * - $offense_counts: Offense counts for driver (optional)
 * - $violation_types: Array of active violation types
 * - $apprehending_officers: Array of active officers
 */
?>
<form id="citationForm" action="../api/insert_citation.php" method="POST">
    <div class="ticket-container">
        <div class="header">
            <h4>REPUBLIC OF THE PHILIPPINES</h4>
            <h4>PROVINCE OF CAGAYAN • MUNICIPALITY OF BAGGAO</h4>
            <h1>TRAFFIC CITATION TICKET</h1>
            <input type="hidden" name="ticket_number" value="<?php echo htmlspecialchars($next_ticket); ?>">
            <input type="hidden" name="csrf_token" id="csrfToken" value="<?php echo generate_token(); ?>">
            <div class="ticket-number"><?php echo htmlspecialchars($next_ticket); ?></div>
        </div>

        <!-- Driver Info -->
        <div class="section">
            <h5><i class="fas fa-id-card me-2"></i>Driver Information</h5>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Last Name *</label>
                    <input type="text" name="last_name" class="form-control" placeholder="Enter last name" value="<?php echo htmlspecialchars($driver_data['last_name'] ?? ''); ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">First Name *</label>
                    <input type="text" name="first_name" class="form-control" placeholder="Enter first name" value="<?php echo htmlspecialchars($driver_data['first_name'] ?? ''); ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">M.I.</label>
                    <input type="text" name="middle_initial" class="form-control" placeholder="M.I." value="<?php echo htmlspecialchars($driver_data['middle_initial'] ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Suffix</label>
                    <input type="text" name="suffix" class="form-control" placeholder="e.g., Jr." value="<?php echo htmlspecialchars($driver_data['suffix'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" id="dateOfBirth" value="<?php echo htmlspecialchars($driver_data['date_of_birth'] ?? ''); ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Age</label>
                    <input type="number" name="age" class="form-control" id="ageField" placeholder="Auto" value="<?php echo htmlspecialchars($driver_data['age'] ?? ''); ?>" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Zone</label>
                    <input type="text" name="zone" class="form-control" placeholder="Enter zone" value="<?php echo htmlspecialchars($driver_data['zone'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Barangay *</label>
                    <select name="barangay" class="form-select" id="barangaySelect" required>
                        <option value="" disabled <?php echo (!isset($driver_data['barangay']) || $driver_data['barangay'] == '') ? 'selected' : ''; ?>>Select Barangay</option>
                        <?php
                        $barangays = [
                            'Adag', 'Agaman', 'Agaman Norte', 'Agaman Sur', 'Alaguia', 'Alba', 'Annayatan', 'Asassi',
                            'Asinga-Via', 'Awallan', 'Bacagan', 'Bagunot', 'Barsat East', 'Barsat West', 'Bitag Grande',
                            'Bitag Pequeño', 'Bungel', 'Canagatan', 'Carupian', 'Catayauan', 'Dabburab', 'Dalin', 'Dallang',
                            'Furagui', 'Hacienda Intal', 'Immurung', 'Jomlo', 'Mabangguc', 'Masical', 'Mission', 'Mocag',
                            'Nangalinan', 'Pallagao', 'Paragat', 'Piggatan', 'Poblacion', 'Remus', 'San Antonio',
                            'San Francisco', 'San Isidro', 'San Jose', 'San Vicente', 'Santa Margarita', 'Santor',
                            'Taguing', 'Taguntungan', 'Tallang', 'Taytay', 'Other'
                        ];
                        foreach ($barangays as $barangay) {
                            $selected = (isset($driver_data['barangay']) && $driver_data['barangay'] == $barangay) ? 'selected' : '';
                            echo "<option value=\"$barangay\" $selected>$barangay</option>";
                        }
                        ?>
                    </select>
                    <input type="text" name="other_barangay" class="form-control" id="otherBarangayInput" placeholder="Enter other barangay" value="<?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] == 'Other') ? htmlspecialchars($driver_data['barangay']) : ''; ?>">
                </div>
                <div class="col-md-3" id="municipalityDiv" style="display: <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] != 'Other' && $driver_data['barangay'] != '') ? 'block' : 'none'; ?>;">
                    <label class="form-label">Municipality</label>
                    <input type="text" name="municipality" class="form-control" id="municipalityInput" value="<?php echo htmlspecialchars($driver_data['municipality'] ?? 'Baggao'); ?>" readonly>
                </div>
                <div class="col-md-3" id="provinceDiv" style="display: <?php echo (isset($driver_data['barangay']) && $driver_data['barangay'] != 'Other' && $driver_data['barangay'] != '') ? 'block' : 'none'; ?>;">
                    <label class="form-label">Province</label>
                    <input type="text" name="province" class="form-control" id="provinceInput" value="<?php echo htmlspecialchars($driver_data['province'] ?? 'Cagayan'); ?>" readonly>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="has_license" id="hasLicense" <?php echo (isset($driver_data['license_number']) && !empty($driver_data['license_number'])) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="hasLicense">Has License</label>
                    </div>
                </div>
                <div class="col-md-4 license-field" style="display: <?php echo (isset($driver_data['license_number']) && !empty($driver_data['license_number'])) ? 'block' : 'none'; ?>;">
                    <label class="form-label">License Number *</label>
                    <input type="text" name="license_number" class="form-control" placeholder="Enter license number" value="<?php echo htmlspecialchars($driver_data['license_number'] ?? ''); ?>" <?php echo (isset($driver_data['license_number']) && !empty($driver_data['license_number'])) ? 'required' : ''; ?>>
                </div>
                <div class="col-md-2 license-field" style="display: <?php echo (isset($driver_data['license_number']) && !empty($driver_data['license_number'])) ? 'block' : 'none'; ?>;">
                    <label class="form-label d-block">License Type *</label>
                    <div class="form-check">
                        <input type="radio" class="form-check-input" name="license_type" value="nonProf" id="nonProf" <?php echo (!isset($driver_data['license_type']) || $driver_data['license_type'] == 'Non-Professional') ? 'checked' : ''; ?> <?php echo (isset($driver_data['license_number']) && !empty($driver_data['license_number'])) ? 'required' : ''; ?>>
                        <label class="form-check-label" for="nonProf">Non-Prof</label>
                    </div>
                </div>
                <div class="col-md-2 license-field" style="display: <?php echo (isset($driver_data['license_number']) && !empty($driver_data['license_number'])) ? 'block' : 'none'; ?>;">
                    <label class="form-label d-block"> </label>
                    <div class="form-check">
                        <input type="radio" class="form-check-input" name="license_type" value="prof" id="prof" <?php echo (isset($driver_data['license_type']) && $driver_data['license_type'] == 'Professional') ? 'checked' : ''; ?> <?php echo (isset($driver_data['license_number']) && !empty($driver_data['license_number'])) ? 'required' : ''; ?>>
                        <label class="form-check-label" for="prof">Prof</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vehicle Info -->
        <div class="section">
            <h5><i class="fas fa-car me-2"></i>Vehicle Information</h5>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Plate / MV File / Engine / Chassis No. *</label>
                    <input type="text" name="plate_mv_engine_chassis_no" class="form-control" placeholder="Enter plate or other number" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Vehicle Type *</label>
                    <div class="d-flex flex-wrap gap-3">
                        <?php
                        $vehicleTypes = ['Motorcycle', 'Tricycle', 'SUV', 'Van', 'Jeep', 'Truck', 'Kulong Kulong', 'Other'];
                        foreach ($vehicleTypes as $index => $type) {
                            $id = strtolower(str_replace(' ', '', $type));
                            $required = ($index === 0) ? 'required' : '';
                            echo "<div class='form-check'>";
                            echo "<input type='radio' class='form-check-input' name='vehicle_type' value='$type' id='$id' $required onchange='toggleOtherVehicle(this.value)'>";
                            echo "<label class='form-check-label' for='$id'>$type</label>";
                            echo "</div>";
                        }
                        ?>
                    </div>
                    <input type="text" name="other_vehicle_input" class="form-control mt-2" id="otherVehicleInput" placeholder="Specify other vehicle type" style="display: none;">
                </div>
                <div class="col-12">
                    <label class="form-label">Vehicle Description</label>
                    <input type="text" name="vehicle_description" class="form-control" placeholder="Brand, Model, CC, Color, etc.">
                </div>
                <div class="col-12">
                    <label class="form-label">Apprehension Date & Time *</label>
                    <div class="input-group">
                        <input type="datetime-local" name="apprehension_datetime" class="form-control" id="apprehensionDateTime" required>
                        <button class="btn btn-outline-secondary" type="button" id="toggleDateTime" title="Set/Clear"><i class="fas fa-calendar-alt"></i></button>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Place of Apprehension *</label>
                    <input type="text" name="place_of_apprehension" class="form-control" placeholder="Enter place of apprehension" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Apprehension Officer *</label>
                    <select name="apprehension_officer" class="form-select" id="apprehensionOfficer" required>
                        <option value="" disabled selected>Select Apprehension Officer</option>
                        <?php if (!empty($apprehending_officers)): ?>
                            <?php foreach ($apprehending_officers as $officer): ?>
                                <option value="<?php echo htmlspecialchars($officer['officer_name']); ?>">
                                    <?php echo htmlspecialchars($officer['officer_name']); ?>
                                    <?php if (!empty($officer['badge_number'])): ?>
                                        (<?php echo htmlspecialchars($officer['badge_number']); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Violations (Accordion) -->
        <div class="section">
            <h5 class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Violation(s) *</h5>
            <div class="accordion violation-list" id="violationsAccordion">
                <?php
                // Display all violations from database, grouped by keyword matching
                $categories = [
                    'Helmet Violations' => ['HELMET'],
                    'License / Registration' => ['LICENSE', 'REGISTRATION', 'OPLAN VISA', 'E-OV MATCH'],
                    'Vehicle Condition' => ['DEFECTIVE', 'MUFFLER', 'MODIFICATION', 'PARTS'],
                    'Reckless / Improper Driving' => ['RECKLESS', 'DRAG RACING', 'DRUNK', 'DRIVING IN SHORT', 'ARROGANT'],
                    'Traffic Rules' => ['TRAFFIC SIGN', 'PARKING', 'OBSTRUCTION', 'PEDESTRIAN', 'LOADING', 'PASSENGER ON TOP'],
                    'Miscellaneous' => ['COLORUM', 'TRASHBIN', 'OVERLOADED', 'CHARGING', 'REFUSAL']
                ];

                $displayed_violations = [];

                foreach ($categories as $category => $keywords) {
                    $category_id = htmlspecialchars(strtolower(str_replace([' ', '/', '(', ')'], '', $category)));
                    echo "<div class='accordion-item'>";
                    echo "<h2 class='accordion-header' id='heading-$category_id'>";
                    echo "<button class='accordion-button collapsed' type='button' data-bs-toggle='collapse' data-bs-target='#collapse-$category_id' aria-expanded='false' aria-controls='collapse-$category_id'>$category</button>";
                    echo "</h2>";
                    echo "<div id='collapse-$category_id' class='accordion-collapse collapse' aria-labelledby='heading-$category_id' data-bs-parent='#violationsAccordion'>";
                    echo "<div class='accordion-body p-3'>";
                    $violations_found = false;
                    foreach ($violation_types as $v) {
                        $matches_category = false;
                        foreach ($keywords as $keyword) {
                            if (stripos($v['violation_type'], $keyword) !== false) {
                                $matches_category = true;
                                break;
                            }
                        }
                        if ($matches_category && !in_array($v['violation_type_id'], $displayed_violations)) {
                            $violations_found = true;
                            $displayed_violations[] = $v['violation_type_id'];
                            $offense_count = isset($offense_counts[$v['violation_type_id']]) ? min((int)$offense_counts[$v['violation_type_id']] + 1, 3) : 1;
                            $fine_key = "fine_amount_$offense_count";
                            $offense_suffix = $offense_count == 1 ? 'st' : ($offense_count == 2 ? 'nd' : 'rd');
                            $label = $v['violation_type'] . " - {$offense_count}{$offense_suffix} Offense (₱" . number_format($v[$fine_key], 2) . ")";
                            $input_id = 'violation_' . $v['violation_type_id'];
                            echo "<div class='form-check mb-2'>";
                            echo "<input type='checkbox' class='form-check-input violation-checkbox' name='violations[]' value='" . (int)$v['violation_type_id'] . "' id='$input_id' data-offense='$offense_count'>";
                            echo "<label class='form-check-label' for='$input_id'>" . htmlspecialchars($label) . "</label>";
                            echo "</div>";
                        }
                    }
                    if (!$violations_found) {
                        echo "<p class='text-muted'>No violations available in this category.</p>";
                    }
                    echo "</div></div></div>";
                }

                // Display uncategorized violations
                $uncategorized = [];
                foreach ($violation_types as $v) {
                    if (!in_array($v['violation_type_id'], $displayed_violations)) {
                        $uncategorized[] = $v;
                    }
                }

                if (!empty($uncategorized)) {
                    echo "<div class='accordion-item'>";
                    echo "<h2 class='accordion-header' id='heading-uncategorized'>";
                    echo "<button class='accordion-button collapsed' type='button' data-bs-toggle='collapse' data-bs-target='#collapse-uncategorized' aria-expanded='false' aria-controls='collapse-uncategorized'>Other Violations</button>";
                    echo "</h2>";
                    echo "<div id='collapse-uncategorized' class='accordion-collapse collapse' aria-labelledby='heading-uncategorized' data-bs-parent='#violationsAccordion'>";
                    echo "<div class='accordion-body p-3'>";
                    foreach ($uncategorized as $v) {
                        $offense_count = isset($offense_counts[$v['violation_type_id']]) ? min((int)$offense_counts[$v['violation_type_id']] + 1, 3) : 1;
                        $fine_key = "fine_amount_$offense_count";
                        $offense_suffix = $offense_count == 1 ? 'st' : ($offense_count == 2 ? 'nd' : 'rd');
                        $label = $v['violation_type'] . " - {$offense_count}{$offense_suffix} Offense (₱" . number_format($v[$fine_key], 2) . ")";
                        $input_id = 'violation_' . $v['violation_type_id'];
                        echo "<div class='form-check mb-2'>";
                        echo "<input type='checkbox' class='form-check-input violation-checkbox' name='violations[]' value='" . (int)$v['violation_type_id'] . "' id='$input_id' data-offense='$offense_count'>";
                        echo "<label class='form-check-label' for='$input_id'>" . htmlspecialchars($label) . "</label>";
                        echo "</div>";
                    }
                    echo "</div></div></div>";
                }
                ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading-other">
                        <button class="accordion-button collapsed" type='button' data-bs-toggle='collapse' data-bs-target='#collapse-other' aria-expanded='false' aria-controls='collapse-other'>
                            Other
                        </button>
                    </h2>
                    <div id="collapse-other" class="accordion-collapse collapse" aria-labelledby="heading-other" data-bs-parent="#violationsAccordion">
                        <div class="accordion-body p-3">
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" name="other_violation" id="other_violation">
                                <label class="form-check-label" for="other_violation">Other Violation</label>
                            </div>
                            <input type="text" name="other_violation_input" class="form-control" id="otherViolationInput" placeholder="Specify other violation">
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-3 remarks">
                <label class="form-label">Remarks</label>
                <textarea name="remarks" class="form-control" rows="4" placeholder="Enter additional remarks"></textarea>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>
                All apprehensions are deemed admitted unless contested by filing a written contest at the Traffic Management Office within five (5) working days from date of issuance.
                Failure to pay the corresponding penalty at the Municipal Treasury Office within fifteen (15) days from date of apprehension, shall be the ground for filing a formal complaint against you.
                Likewise, a copy of this ticket shall be forwarded to concerned agencies for proper action/disposition.
            </p>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-custom mt-3"><i class="fas fa-paper-plane me-2"></i>Submit Citation</button>
    </div>
</form>
