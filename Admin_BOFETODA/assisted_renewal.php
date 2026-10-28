<?php
/**
 * Assisted Renewal - Staff Upload on Behalf of Members
 * Allows secretary/president to submit renewal applications for drivers
 * who are not comfortable with technology
 */

require_once __DIR__ . '/config.php';
require_role(['secretary', 'president']);
require_once __DIR__ . '/../Driver_BOFETODA/driver-database.php';
require_once __DIR__ . '/r2-storage.php';

$officer = current_officer();
$error = null;
$success = null;

// Check if there's an open announcement
$openAnnouncement = null;
foreach (d('announcements') as $a) {
    if ($a['status'] === 'OPEN') {
        $openAnnouncement = $a;
        break;
    }
}

if (!$openAnnouncement) {
    // No open renewal period
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assisted Renewal · <?= e(APP_NAME) ?></title>
<?php render_shared_styles(); ?>
</head>
<body>
<?php render_sidebar('assisted'); ?>
<div class="main-col">
  <?php render_topbar('Administration Portal', 'Assisted Renewal', 'Submit renewal on behalf of members'); ?>
  <div class="page">
    <div class="card">
      <div class="card__body">
        <p style="color:var(--text-muted); font-size:14px;">There is no active renewal period at this time. Assisted renewals can only be submitted when a renewal cycle is open.</p>
        <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-outline" style="margin-top:16px;">Back to Dashboard</a>
      </div>
    </div>
  </div>
</div>
<?php render_shared_script(); ?>
</body>
</html>
    <?php
    exit;
}

// Handle form submission (Step 4 - combined upload and consent)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_assisted_renewal'])) {
    $driver_id = trim($_POST['driver_id'] ?? '');
    $consent = isset($_POST['consent']) && $_POST['consent'] === '1';
    
    if (!$consent) {
        $error = 'You must confirm that the driver has given consent.';
    } elseif (!can_submit_assisted_renewal($driver_id)) {
        $error = 'This driver is not eligible for assisted renewal at this time.';
    } else {
        try {
            // Get driver details
            $driver = get_driver_for_assisted_renewal($driver_id);
            if (!$driver) {
                throw new Exception('Driver not found.');
            }
            
            // Validate all files are present
            $requiredDocs = ['license', 'or_cr', 'receipt', 'photo'];
            foreach ($requiredDocs as $docType) {
                if (!isset($_FILES[$docType]) || $_FILES[$docType]['error'] === UPLOAD_ERR_NO_FILE) {
                    throw new Exception('All 4 documents are required.');
                }
            }
            
            // Upload documents to R2 and get paths
            $uploadResult = batch_upload_driver_documents($driver_id, [
                'drivers_license' => $_FILES['license'],
                'or_cr'          => $_FILES['or_cr'],
                'payment_receipt' => $_FILES['receipt'],
                'picture_1x1'    => $_FILES['photo'],
            ]);
            
            if (!$uploadResult['success']) {
                throw new Exception('Failed to upload documents: ' . implode(', ', $uploadResult['errors']));
            }
            
            // Submit renewal application with officer attribution
            $app_no = submit_renewal_application($driver, $uploadResult['paths'], $officer['id'], $consent);
            
            if ($app_no) {
                // Log the assisted renewal
                log_assisted_renewal($officer['id'], $driver_id, $app_no, 'Assistance');
                
                // Clear session
                unset($_SESSION['assisted_renewal_driver']);
                
                // Redirect to success page
                header('Location: ' . BASE_URL . '/assisted_renewal.php?success=1&app_no=' . urlencode($app_no));
                exit;
            } else {
                throw new Exception('Failed to submit application.');
            }
            
        } catch (Exception $e) {
            error_log('Assisted renewal submission error: ' . $e->getMessage());
            $error = $e->getMessage();
        }
    }
}

// Get step from URL
$step = (int) ($_GET['step'] ?? 1);
if ($step < 1 || $step > 5) {
    $step = 1;
}

// Get selected driver from session
// (session already started by config.php)
$selected_driver_id = $_SESSION['assisted_renewal_driver'] ?? null;
$selected_driver = null;

if ($selected_driver_id && $step > 1) {
    $selected_driver = get_driver_for_assisted_renewal($selected_driver_id);
    if (!$selected_driver) {
        // Driver not found, reset to step 1
        unset($_SESSION['assisted_renewal_driver']);
        $step = 1;
        $error = 'Driver not found. Please search again.';
    }
}

// Handle driver selection (AJAX will set session)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_driver'])) {
    $driver_id = trim($_POST['driver_id'] ?? '');
    
    if (can_submit_assisted_renewal($driver_id)) {
        $_SESSION['assisted_renewal_driver'] = $driver_id;
        header('Location: ' . BASE_URL . '/assisted_renewal.php?step=2');
        exit;
    } else {
        $error = 'This driver cannot submit a renewal at this time.';
        $step = 1;
    }
}

// Handle success message
if (isset($_GET['success']) && isset($_GET['app_no'])) {
    $success_app_no = $_GET['app_no'];
    unset($_SESSION['assisted_renewal_driver']); // Clear session
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assisted Renewal · <?= e(APP_NAME) ?></title>
<?php render_shared_styles(); ?>
<style>
.search-box {
    position: relative;
}
.search-input {
    width: 100%;
    padding: 16px 20px 16px 50px;
    font-size: 16px;
    border: 2px solid var(--border);
    border-radius: 12px;
    transition: all 0.2s;
}
.search-input:focus {
    outline: none;
    border-color: var(--navy);
    box-shadow: 0 0 0 4px rgba(22,35,61,0.08);
}
.search-icon {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
}
.search-results {
    margin-top: 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.member-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    background: #f8f9fb;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    transition: all 0.2s;
    width: 100%;
    cursor: pointer;
    text-align: left;
}
.member-card:hover {
    background: #f1f3f9;
    border-color: #d1d5db;
}
.member-card.disabled {
    opacity: 0.65;
    cursor: not-allowed;
}
.member-card.disabled:hover {
    background: #f8f9fb;
    border-color: #e5e7eb;
}
.member-avatar {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 700;
    color: white;
    flex-shrink: 0;
}
.member-info {
    flex: 1;
    min-width: 0;
}
.member-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
    flex-wrap: wrap;
}
.driver-id-badge {
    display: inline-block;
    padding: 2px 8px;
    background: #374151;
    color: white;
    font-size: 11px;
    font-weight: 600;
    font-family: 'Courier New', monospace;
    border-radius: 4px;
    letter-spacing: 0.3px;
}
.member-name {
    font-size: 16px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 4px;
}
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    font-size: 10px;
    font-weight: 600;
    border-radius: 4px;
}
.status-badge.eligible {
    background: #d1fae5;
    color: #065f46;
}
.status-badge.pending {
    background: #fef3c7;
    color: #92400e;
}
.status-badge.incomplete {
    background: #fee2e2;
    color: #991b1b;
}
.member-meta {
    font-size: 13px;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.member-meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
}
.member-meta-separator {
    width: 3px;
    height: 3px;
    background: #9ca3af;
    border-radius: 50%;
}
.select-btn {
    padding: 8px 18px;
    background: #4f46e5;
    color: white;
    font-size: 13px;
    font-weight: 600;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.select-btn:hover {
    background: #4338ca;
}
.member-card.disabled .select-btn {
    display: none;
}
@media (max-width: 640px) {
    .member-card {
        flex-wrap: wrap;
    }
    .select-btn {
        width: 100%;
        justify-content: center;
        margin-left: 58px;
    }
}
.selected-driver-card {
    background: #e3f2fd;
    border: 2px solid #2196f3;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 24px;
}
.readonly-field {
    margin-bottom: 16px;
}
.readonly-label {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-muted);
    margin-bottom: 6px;
}
.readonly-value {
    font-size: 15px;
    font-weight: 600;
    color: var(--text);
    padding: 12px;
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 8px;
}
.consent-box {
    background: #fff3cd;
    border: 2px solid #ffc107;
    padding: 20px;
    border-radius: 12px;
    margin: 24px 0;
}
.consent-checkbox {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}
.consent-checkbox input[type="checkbox"] {
    width: 24px;
    height: 24px;
    margin-top: 2px;
    cursor: pointer;
    accent-color: var(--navy);
}
.consent-checkbox label {
    font-size: 15px;
    line-height: 1.5;
    cursor: pointer;
}
.doc-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-top: 16px;
}
.doc-upload-box {
    border: 2px dashed var(--border);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    transition: all 0.2s;
}
.doc-upload-box:hover {
    border-color: var(--navy);
    background: var(--bg);
}
.doc-upload-box input[type="file"] {
    display: none;
}
.doc-upload-label {
    cursor: pointer;
    display: block;
}
.doc-preview {
    max-width: 100%;
    max-height: 150px;
    margin-top: 12px;
    border-radius: 8px;
}
.btn-row {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}
.info-note {
    background: #e3f2fd;
    padding: 16px;
    border-radius: 8px;
    font-size: 13px;
    color: #1976d2;
    margin-bottom: 20px;
}
</style>
</head>
<body>
<?php render_sidebar('assisted'); ?>

<div class="main-col">
  <?php render_topbar('Administration Portal', 'Assisted Renewal', 'Submit renewal on behalf of members'); ?>

  <div class="page">

    <?php if (isset($_GET['success']) && isset($success_app_no)): ?>
      <!-- Success Message -->
      <div class="card" style="border-color:var(--green); background:var(--green-bg);">
        <div class="card__body">
          <h3 style="color:var(--green); margin-bottom:12px;">✓ Application Submitted Successfully</h3>
          <p style="color:var(--green); font-weight:600;">Application Number: <?= e($success_app_no) ?></p>
          <p style="color:var(--green); margin-top:8px;">The application has been submitted and will go through the standard validation and approval process.</p>
          <div style="margin-top:20px;">
            <a href="<?= BASE_URL ?>/validation_tools.php" class="btn btn-success">View in Validation Queue</a>
            <a href="<?= BASE_URL ?>/assisted_renewal.php" class="btn btn-outline" style="margin-left:12px;">Submit Another</a>
          </div>
        </div>
      </div>
    <?php else: ?>

      <?php if ($error): ?>
        <div class="card" style="border-color:var(--red); background:var(--red-bg); margin-bottom:20px;">
          <div class="card__body" style="color:var(--red); font-weight:600;"><?= e($error) ?></div>
        </div>
      <?php endif; ?>

      <!-- Page Header with Progress -->
      <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:24px;">
        <div>
          <h2 style="font-size:28px; font-weight:900; color:#111827; margin:0 0 8px 0; letter-spacing:-0.02em;">Assisted Renewal</h2>
          <p style="font-size:14px; color:#6b7280; margin:0;">
            Assisting walk-in or offline TODA members with <span style="color:#4f46e5; font-weight:600;">filing</span>, <span style="color:#4f46e5; font-weight:600;">verifying</span>, and <span style="color:#4f46e5; font-weight:600;">submitting</span> renewal applications.
          </p>
        </div>
        <div style="text-align:right;">
          <?php
          $stepLabels = [
            1 => 'Member Lookup',
            2 => 'Driver Profile',
            3 => 'Vehicle Info',
            4 => 'Upload Documents',
            5 => 'Review & Submit'
          ];
          ?>
          <div style="font-size:11px; color:#9ca3af; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:4px;">
            STEP <?= $step ?> OF 5 <?= $stepLabels[$step] ?? '' ?>
          </div>
          <div style="height:3px; width:120px; background:#e5e7eb; border-radius:999px; overflow:hidden; margin-left:auto;">
            <div style="height:100%; width:<?= ($step / 5) * 100 ?>%; background:#4f46e5;"></div>
          </div>
        </div>
      </div>

      <?php if ($step === 1): ?>
        <!-- Step 1: Search & Select Member -->
        <div style="background:white; border:1px solid #e5e7eb; border-radius:12px; padding:24px;">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px;">
            <div>
              <h3 style="font-size:18px; font-weight:700; color:#111827; margin:0 0 6px 0;">Search for Member</h3>
              <p style="font-size:13px; color:#6b7280; margin:0;">
                Search by driver name, ID number, body number, or assigned barangay to find the member you want to assist.
              </p>
            </div>
            <div id="foundCount" style="display:none; font-size:13px; color:#4f46e5; font-weight:600; white-space:nowrap; padding:4px 0;">
              Found 5 matching drivers
            </div>
          </div>
            
            <div class="search-box" style="margin-bottom:20px;">
              <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="M21 21l-4.35-4.35"></path>
              </svg>
              <input 
                type="text" 
                id="driverSearch" 
                class="search-input" 
                placeholder="Search by name, ID, or body number..."
                autocomplete="off"
                style="padding:12px 16px 12px 44px; font-size:15px;"
              />
              <button type="button" id="clearSearch" style="display:none; position:absolute; right:14px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#9ca3af; padding:4px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="18" y1="6" x2="6" y2="18"></line>
                  <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
              </button>
            </div>

            <div id="searchResults" class="search-results" style="display:none;"></div>
            <div id="searchLoading" style="display:none; text-align:center; padding:20px; color:var(--text-muted);">
              Searching...
            </div>
            <div id="searchEmpty" style="display:none; text-align:center; padding:20px; color:var(--text-muted);">
              No drivers found. Try a different search term.
            </div>
          </div>
        </div>

        <script>
        let searchTimeout;
        const searchInput = document.getElementById('driverSearch');
        const searchResults = document.getElementById('searchResults');
        const searchLoading = document.getElementById('searchLoading');
        const searchEmpty = document.getElementById('searchEmpty');
        const clearSearch = document.getElementById('clearSearch');
        const foundCount = document.getElementById('foundCount');

        searchInput.addEventListener('input', function() {
          clearTimeout(searchTimeout);
          const query = this.value.trim();
          
          // Show/hide clear button
          if (query.length > 0) {
            clearSearch.style.display = 'block';
          } else {
            clearSearch.style.display = 'none';
          }
          
          if (query.length < 2) {
            searchResults.style.display = 'none';
            searchLoading.style.display = 'none';
            searchEmpty.style.display = 'none';
            foundCount.style.display = 'none';
            return;
          }

          searchLoading.style.display = 'block';
          searchResults.style.display = 'none';
          searchEmpty.style.display = 'none';
          foundCount.style.display = 'none';

          searchTimeout = setTimeout(() => {
            fetch('<?= BASE_URL ?>/api-search-drivers.php?q=' + encodeURIComponent(query))
              .then(res => res.json())
              .then(data => {
                searchLoading.style.display = 'none';
                
                if (data.success && data.results.length > 0) {
                  renderResults(data.results);
                  searchResults.style.display = 'block';
                  foundCount.style.display = 'block';
                  foundCount.textContent = 'Found ' + data.results.length + ' matching driver' + (data.results.length !== 1 ? 's' : '');
                } else {
                  searchEmpty.style.display = 'block';
                  foundCount.style.display = 'none';
                }
              })
              .catch(err => {
                searchLoading.style.display = 'none';
                console.error('Search error:', err);
              });
          }, 300);
        });

        clearSearch.addEventListener('click', function() {
          searchInput.value = '';
          searchInput.focus();
          clearSearch.style.display = 'none';
          searchResults.style.display = 'none';
          searchLoading.style.display = 'none';
          searchEmpty.style.display = 'none';
          foundCount.style.display = 'none';
        });

        function renderResults(drivers) {
          const avatarColors = [
            '#3b82f6', // blue
            '#10b981', // green
            '#f59e0b', // amber
            '#8b5cf6', // purple
            '#ec4899', // pink
            '#14b8a6', // teal
            '#f97316', // orange
            '#6366f1', // indigo
          ];
          
          searchResults.innerHTML = drivers.map((driver, index) => {
            const disabled = !driver.can_submit;
            const initial = driver.name.charAt(0).toUpperCase();
            const avatarColor = avatarColors[index % avatarColors.length];
            
            // Determine status badge
            let statusBadge = '';
            let statusClass = '';
            if (disabled) {
              if (driver.status_message.toLowerCase().includes('pending')) {
                statusBadge = 'Pending Validation';
                statusClass = 'pending';
              } else if (driver.status_message.toLowerCase().includes('incomplete')) {
                statusBadge = 'Renewal Incomplete';
                statusClass = 'incomplete';
              } else {
                statusBadge = driver.status_message;
                statusClass = 'incomplete';
              }
            } else {
              statusBadge = 'Eligible for Renewal';
              statusClass = 'eligible';
            }
            
            return `
              <form method="post" style="margin:0;">
                <input type="hidden" name="driver_id" value="${driver.driver_id}">
                <input type="hidden" name="select_driver" value="1">
                <button type="submit" class="member-card ${disabled ? 'disabled' : ''}" ${disabled ? 'disabled' : ''}>
                  <div class="member-avatar" style="background: ${avatarColor};">
                    ${initial}
                  </div>
                  <div class="member-info">
                    <div class="member-header">
                      <span class="driver-id-badge">${driver.driver_id}</span>
                      <span class="status-badge ${statusClass}">${statusBadge}</span>
                    </div>
                    <h4 class="member-name">${driver.name}</h4>
                    <div class="member-meta">
                      <span class="member-meta-item">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity:0.5;">
                          <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                          <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        Body No. ${driver.body_no}
                      </span>
                      <span class="member-meta-separator"></span>
                      <span class="member-meta-item">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity:0.5;">
                          <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                          <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        ${driver.barangay}
                      </span>
                    </div>
                  </div>
                  ${!disabled ? '<span class="select-btn">Select Member <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg></span>' : ''}
                </button>
              </form>
            `;
          }).join('');
        }
        </script>

      <?php elseif ($step === 2 && $selected_driver): ?>
        <!-- Step 2: Driver's Profile (Read-only) -->
        <div class="selected-driver-card">
          <strong style="font-size:14px; color:#1976d2;">Selected Driver:</strong>
          <div style="font-size:18px; font-weight:700; color:var(--navy); margin-top:4px;">
            <?= e($selected_driver['name']) ?> (<?= e($selected_driver['driver_id']) ?>)
          </div>
          <a href="<?= BASE_URL ?>/assisted_renewal.php?step=1" style="font-size:13px; color:#1976d2; text-decoration:underline; margin-top:8px; display:inline-block;">Change Driver</a>
        </div>

        <div class="card">
          <div class="card__head">
            <h3>Driver's Profile</h3>
          </div>
          <div class="card__body">
            <div class="info-note">
              This information is on file and shown here for review only — it can't be edited on this page.
            </div>

            <div class="readonly-field">
              <div class="readonly-label">Driver ID</div>
              <div class="readonly-value"><?= e($selected_driver['driver_id']) ?></div>
            </div>

            <div class="readonly-field">
              <div class="readonly-label">Name</div>
              <div class="readonly-value"><?= e($selected_driver['name']) ?></div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
              <div class="readonly-field">
                <div class="readonly-label">Age</div>
                <div class="readonly-value"><?= e($selected_driver['age']) ?></div>
              </div>
              <div class="readonly-field">
                <div class="readonly-label">Birthday</div>
                <div class="readonly-value"><?= e(date('M j, Y', strtotime($selected_driver['birthday']))) ?></div>
              </div>
            </div>

            <div class="readonly-field">
              <div class="readonly-label">Contact Number</div>
              <div class="readonly-value"><?= e($selected_driver['contact_no']) ?></div>
            </div>

            <div class="readonly-field">
              <div class="readonly-label">Barangay</div>
              <div class="readonly-value"><?= e($selected_driver['barangay']) ?></div>
            </div>

            <div class="readonly-field">
              <div class="readonly-label">Address</div>
              <div class="readonly-value"><?= e($selected_driver['address']) ?></div>
            </div>
          </div>
        </div>

        <div class="btn-row">
          <a href="<?= BASE_URL ?>/assisted_renewal.php?step=1" class="btn btn-outline">Back</a>
          <a href="<?= BASE_URL ?>/assisted_renewal.php?step=3" class="btn btn-accent" style="flex:1;">Next</a>
        </div>

      <?php elseif ($step === 3 && $selected_driver): ?>
        <!-- Step 3: Vehicle Information (Read-only) -->
        <div class="selected-driver-card">
          <strong style="font-size:14px; color:#1976d2;">Selected Driver:</strong>
          <div style="font-size:18px; font-weight:700; color:var(--navy); margin-top:4px;">
            <?= e($selected_driver['name']) ?> (<?= e($selected_driver['driver_id']) ?>)
          </div>
        </div>

        <div class="card">
          <div class="card__head">
            <h3>Vehicle Information</h3>
          </div>
          <div class="card__body">
            <div class="info-note">
              Vehicle information on file, shown for confirmation.
            </div>

            <div class="readonly-field">
              <div class="readonly-label">Body Number</div>
              <div class="readonly-value"><?= e($selected_driver['body_no']) ?></div>
            </div>

            <div class="readonly-field">
              <div class="readonly-label">Tricycle Color</div>
              <div class="readonly-value"><?= e($selected_driver['color'] ?? '—') ?></div>
            </div>
          </div>
        </div>

        <div class="btn-row">
          <a href="<?= BASE_URL ?>/assisted_renewal.php?step=2" class="btn btn-outline">Back</a>
          <a href="<?= BASE_URL ?>/assisted_renewal.php?step=4" class="btn btn-accent" style="flex:1;">Next</a>
        </div>

      <?php elseif ($step === 4 && $selected_driver): ?>
        <!-- Step 4: Upload Documents -->
        <div class="selected-driver-card">
          <strong style="font-size:14px; color:#1976d2;">Selected Driver:</strong>
          <div style="font-size:18px; font-weight:700; color:var(--navy); margin-top:4px;">
            <?= e($selected_driver['name']) ?> (<?= e($selected_driver['driver_id']) ?>)
          </div>
        </div>

        <form id="uploadForm" method="post" action="<?= BASE_URL ?>/assisted_renewal.php" enctype="multipart/form-data">
          <input type="hidden" name="driver_id" value="<?= e($selected_driver['driver_id']) ?>">
          <input type="hidden" name="submit_assisted_renewal" value="1">
          
          <div class="card">
            <div class="card__head">
              <h3>Upload Documents</h3>
            </div>
            <div class="card__body">
              <p style="margin-bottom:20px; color:var(--text-muted);">Upload all 4 required documents. Accepted formats: JPG, PNG, PDF (max 4MB each)</p>

              <div class="doc-grid">
                <!-- Driver's License -->
                <div class="doc-upload-box" id="box-license">
                  <input type="file" name="license" id="file-license" accept="image/*,.pdf" required>
                  <label for="file-license" class="doc-upload-label">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin:0 auto 8px; color:var(--primary);">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                      <polyline points="17 8 12 3 7 8"></polyline>
                      <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <div style="font-weight:600; margin-bottom:4px;">Driver's License</div>
                    <div style="font-size:12px; color:var(--text-muted);">Click to upload</div>
                  </label>
                  <div id="preview-license" style="margin-top:12px;"></div>
                </div>

                <!-- OR/CR -->
                <div class="doc-upload-box" id="box-or_cr">
                  <input type="file" name="or_cr" id="file-or_cr" accept="image/*,.pdf" required>
                  <label for="file-or_cr" class="doc-upload-label">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin:0 auto 8px; color:var(--primary);">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                      <polyline points="17 8 12 3 7 8"></polyline>
                      <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <div style="font-weight:600; margin-bottom:4px;">OR/CR</div>
                    <div style="font-size:12px; color:var(--text-muted);">Click to upload</div>
                  </label>
                  <div id="preview-or_cr" style="margin-top:12px;"></div>
                </div>

                <!-- Payment Receipt -->
                <div class="doc-upload-box" id="box-receipt">
                  <input type="file" name="receipt" id="file-receipt" accept="image/*,.pdf" required>
                  <label for="file-receipt" class="doc-upload-label">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin:0 auto 8px; color:var(--primary);">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                      <polyline points="17 8 12 3 7 8"></polyline>
                      <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <div style="font-weight:600; margin-bottom:4px;">Payment Receipt</div>
                    <div style="font-size:12px; color:var(--text-muted);">Click to upload</div>
                  </label>
                  <div id="preview-receipt" style="margin-top:12px;"></div>
                </div>

                <!-- 1x1 Picture -->
                <div class="doc-upload-box" id="box-photo">
                  <input type="file" name="photo" id="file-photo" accept="image/*" required>
                  <label for="file-photo" class="doc-upload-label">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin:0 auto 8px; color:var(--primary);">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                      <polyline points="17 8 12 3 7 8"></polyline>
                      <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <div style="font-weight:600; margin-bottom:4px;">1x1 Picture</div>
                    <div style="font-size:12px; color:var(--text-muted);">Click to upload</div>
                  </label>
                  <div id="preview-photo" style="margin-top:12px;"></div>
                </div>
              </div>
            </div>
          </div>

          <div class="consent-box">
            <div class="consent-checkbox">
              <input type="checkbox" name="consent" id="consentCheck" value="1" required>
              <label for="consentCheck">
                <strong>I confirm that <?= e($selected_driver['name']) ?> has provided verbal consent for me to submit these renewal documents on their behalf.</strong>
                <br><br>
                By checking this box, I acknowledge that I have personally verified the driver's identity and obtained their permission to process this assisted renewal.
              </label>
            </div>
          </div>

          <div class="btn-row">
            <a href="<?= BASE_URL ?>/assisted_renewal.php?step=3" class="btn btn-outline">Back</a>
            <button type="submit" id="btnSubmit" class="btn btn-accent" style="flex:1;" disabled>Submit Application</button>
          </div>
        </form>

        <script>
        const fileInputs = ['license', 'or_cr', 'receipt', 'photo'];
        const uploadedFiles = {};
        const btnSubmit = document.getElementById('btnSubmit');
        const consentCheck = document.getElementById('consentCheck');

        fileInputs.forEach(docType => {
          const input = document.getElementById('file-' + docType);
          const preview = document.getElementById('preview-' + docType);
          const box = document.getElementById('box-' + docType);

          input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            // Validate file size (4MB)
            if (file.size > 4 * 1024 * 1024) {
              alert('File size must be less than 4MB');
              input.value = '';
              return;
            }

            uploadedFiles[docType] = file;
            
            // Show preview for images
            if (file.type.startsWith('image/')) {
              const reader = new FileReader();
              reader.onload = function(e) {
                preview.innerHTML = '<img src="' + e.target.result + '" class="doc-preview"><div style="font-size:12px; color:var(--green); margin-top:8px;">✓ ' + file.name + '</div>';
              };
              reader.readAsDataURL(file);
            } else {
              preview.innerHTML = '<div style="font-size:12px; color:var(--green); margin-top:8px;">✓ ' + file.name + '</div>';
            }

            box.style.borderColor = 'var(--green)';
            box.style.background = '#f0f9ff';

            // Check if can submit
            checkCanSubmit();
          });
        });

        consentCheck.addEventListener('change', checkCanSubmit);

        function checkCanSubmit() {
          const allUploaded = fileInputs.every(docType => uploadedFiles[docType]);
          const consentGiven = consentCheck.checked;
          btnSubmit.disabled = !(allUploaded && consentGiven);
        }

        document.getElementById('uploadForm').addEventListener('submit', function() {
          btnSubmit.disabled = true;
          btnSubmit.textContent = 'Submitting...';
        });
        </script>

      <?php endif; ?>

    <?php endif; ?>

  </div>
</div>

<?php render_shared_script(); ?>
</body>
</html>
