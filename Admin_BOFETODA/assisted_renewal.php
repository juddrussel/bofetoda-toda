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
        
        <!-- Selected Driver Banner -->
        <div style="margin-bottom:20px; border-radius:12px; border:1px solid #bae6fd; background:#f0f9ff; padding:16px; box-shadow:0 1px 2px rgba(0,0,0,0.02);">
          <div style="display:flex; align-items:center; justify-content:space-between;">
            <div style="flex:1;">
              <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:#0284c7; margin-bottom:2px;">Selected Driver:</div>
              <div style="display:flex; align-items:center; gap:8px; margin-top:2px;">
                <span style="font-size:16px; font-weight:700; color:#0f172a;"><?= e($selected_driver['name']) ?></span>
                <span style="display:inline-flex; align-items:center; padding:4px 8px; border-radius:4px; font-size:12px; font-weight:600; background:#dbeafe; color:#1e40af; border:1px solid #93c5fd;"><?= e($selected_driver['driver_id']) ?></span>
              </div>
              <div style="margin-top:8px;">
                <a href="<?= BASE_URL ?>/assisted_renewal.php?step=1" style="display:inline-flex; align-items:center; gap:4px; font-size:12px; font-weight:600; color:#2563eb; text-decoration:underline; text-underline-offset:2px; transition:color 0.2s;">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" stroke-linecap="round" stroke-linejoin="round"></path>
                  </svg>
                  Change Driver
                </a>
              </div>
            </div>
            <div class="hidden sm:flex" style="display:flex; align-items:center; gap:8px; font-size:12px; font-weight:500; color:#64748b; background:rgba(255,255,255,0.7); padding:8px 12px; border-radius:8px; border:1px solid rgba(186,230,253,0.7);">
              <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="color:#10b981;">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
              </svg>
              <span>Verified <?= e($selected_driver['barangay']) ?> TODA Member</span>
            </div>
          </div>
        </div>

        <!-- Driver Profile Card -->
        <div style="background:white; border-radius:12px; border:1px solid #e5e7eb; box-shadow:0 1px 2px rgba(0,0,0,0.02); overflow:hidden; margin-bottom:24px;">
          <!-- Card Header -->
          <div style="padding:16px 24px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between;">
            <div style="display:flex; align-items:center; gap:8px;">
              <h3 style="font-size:14px; font-weight:700; color:#0f172a; letter-spacing:-0.01em; margin:0;">Driver's Profile</h3>
              <span style="font-size:11px; background:#f1f5f9; color:#64748b; padding:4px 8px; border-radius:4px; font-weight:500;">Read-Only</span>
            </div>
            <span style="font-size:11px; color:#94a3b8; font-weight:500;">Synced with Member Records</span>
          </div>

          <div style="padding:24px;">
            <!-- Information Notice Banner -->
            <div style="border-radius:8px; background:#f0f9ff; border:1px solid#bae6fd; padding:12px 16px; font-size:12px; color:#0c4a6e; display:flex; align-items:start; gap:10px; margin-bottom:24px;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0; margin-top:2px; color:#0284c7;">
                <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"></path>
              </svg>
              <p style="line-height:1.5; margin:0;">This information is on file and shown here for review only — it can't be edited on this page.</p>
            </div>

            <!-- Form Fields -->
            <div style="display:flex; flex-direction:column; gap:16px;">
              <!-- Driver ID -->
              <div>
                <label style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin-bottom:6px;">DRIVER ID</label>
                <input type="text" readonly value="<?= e($selected_driver['driver_id']) ?>" style="width:100%; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px; font-size:12px; font-weight:600; color:#334155; cursor:not-allowed;" />
              </div>

              <!-- Name -->
              <div>
                <label style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin-bottom:6px;">NAME</label>
                <input type="text" readonly value="<?= e($selected_driver['name']) ?>" style="width:100%; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px; font-size:12px; font-weight:500; color:#334155; cursor:not-allowed;" />
              </div>

              <!-- Age & Birthday -->
              <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div>
                  <label style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin-bottom:6px;">AGE</label>
                  <input type="text" readonly value="<?= e($selected_driver['age']) ?>" style="width:100%; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px; font-size:12px; font-weight:500; color:#334155; cursor:not-allowed;" />
                </div>
                <div>
                  <label style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin-bottom:6px;">BIRTHDAY</label>
                  <div style="position:relative;">
                    <input type="text" readonly value="<?= e(date('M j, Y', strtotime($selected_driver['birthday']))) ?>" style="width:100%; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px; padding-right:36px; font-size:12px; font-weight:500; color:#334155; cursor:not-allowed;" />
                    <div style="position:absolute; right:0; top:0; bottom:0; display:flex; align-items:center; padding-right:12px; pointer-events:none; color:#94a3b8;">
                      <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                      </svg>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Contact Number -->
              <div>
                <label style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin-bottom:6px;">CONTACT NUMBER</label>
                <div style="position:relative;">
                  <input type="text" readonly value="<?= e($selected_driver['contact_no']) ?>" style="width:100%; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px; padding-right:36px; font-size:12px; font-weight:500; color:#334155; cursor:not-allowed;" />
                  <div style="position:absolute; right:0; top:0; bottom:0; display:flex; align-items:center; padding-right:12px; pointer-events:none; color:#94a3b8;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                  </div>
                </div>
              </div>

              <!-- Barangay -->
              <div>
                <label style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin-bottom:6px;">BARANGAY</label>
                <input type="text" readonly value="<?= e($selected_driver['barangay']) ?>" style="width:100%; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px; font-size:12px; font-weight:500; color:#334155; cursor:not-allowed;" />
              </div>

              <!-- Address -->
              <div>
                <label style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin-bottom:6px;">ADDRESS</label>
                <input type="text" readonly value="<?= e($selected_driver['address']) ?>" style="width:100%; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px; font-size:12px; font-weight:500; color:#334155; cursor:not-allowed;" />
              </div>
            </div>
          </div>
        </div>

        <!-- Bottom Navigation -->
        <div style="display:flex; align-items:center; justify-content:space-between; padding-top:8px; padding-bottom:48px;">
          <a href="<?= BASE_URL ?>/assisted_renewal.php?step=1" style="display:inline-flex; align-items:center; gap:6px; padding:10px 20px; border-radius:8px; border:1px solid #cbd5e1; background:white; font-size:12px; font-weight:600; color:#475569; text-decoration:none; box-shadow:0 1px 2px rgba(0,0,0,0.05); transition:all 0.2s;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M15 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
            </svg>
            Back
          </a>
          <a href="<?= BASE_URL ?>/assisted_renewal.php?step=3" style="display:inline-flex; align-items:center; gap:6px; padding:10px 24px; border-radius:8px; background:#2563eb; font-size:12px; font-weight:600; color:white; text-decoration:none; box-shadow:0 4px 6px rgba(37,99,235,0.2); transition:all 0.2s;">
            <span>Next: Vehicle Information</span>
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
            </svg>
          </a>
        </div>

      <?php elseif ($step === 3 && $selected_driver): ?>
        <!-- Step 3: Vehicle Information (Read-only) -->
        
        <!-- Selected Driver Banner -->
        <div style="margin-bottom:20px; border-radius:12px; border:1px solid #bae6fd; background:#f0f9ff; padding:16px; box-shadow:0 1px 2px rgba(0,0,0,0.02);">
          <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:#0284c7; margin-bottom:2px;">Selected Driver:</div>
          <div style="display:flex; align-items:center; gap:8px; margin-top:2px;">
            <span style="font-size:16px; font-weight:700; color:#0f172a;"><?= e($selected_driver['name']) ?></span>
            <span style="display:inline-flex; align-items:center; padding:4px 8px; border-radius:4px; font-size:12px; font-weight:600; background:#dbeafe; color:#1e40af; border:1px solid #93c5fd;"><?= e($selected_driver['driver_id']) ?></span>
          </div>
        </div>

        <!-- Vehicle Information Card -->
        <div style="background:white; border-radius:12px; border:1px solid #e5e7eb; box-shadow:0 1px 2px rgba(0,0,0,0.02); overflow:hidden; margin-bottom:24px;">
          <!-- Card Header -->
          <div style="padding:16px 24px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between;">
            <div style="display:flex; align-items:center; gap:8px;">
              <h3 style="font-size:14px; font-weight:700; color:#0f172a; letter-spacing:-0.01em; margin:0;">Vehicle Information</h3>
              <span style="font-size:11px; background:#f1f5f9; color:#64748b; padding:4px 8px; border-radius:4px; font-weight:500;">Read-Only</span>
            </div>
            <span style="font-size:11px; color:#94a3b8; font-weight:500;">Synced with Member Records</span>
          </div>

          <div style="padding:24px;">
            <!-- Information Notice Banner -->
            <div style="border-radius:8px; background:#f0f9ff; border:1px solid #bae6fd; padding:12px 16px; font-size:12px; color:#0c4a6e; display:flex; align-items:start; gap:10px; margin-bottom:24px;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0; margin-top:2px; color:#0284c7;">
                <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"></path>
              </svg>
              <p style="line-height:1.5; margin:0;">Vehicle information on file, shown for confirmation.</p>
            </div>

            <!-- Form Fields -->
            <div style="display:flex; flex-direction:column; gap:16px;">
              <!-- Body Number -->
              <div>
                <label style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin-bottom:6px;">BODY NUMBER</label>
                <input type="text" readonly value="<?= e($selected_driver['body_no']) ?>" style="width:100%; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px; font-size:12px; font-weight:600; color:#334155; cursor:not-allowed;" />
              </div>

              <!-- Tricycle Color -->
              <div>
                <label style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin-bottom:6px;">TRICYCLE COLOR</label>
                <input type="text" readonly value="<?= e($selected_driver['color'] ?? '—') ?>" style="width:100%; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px; font-size:12px; font-weight:500; color:#334155; cursor:not-allowed;" />
              </div>
            </div>
          </div>
        </div>

        <!-- Bottom Navigation -->
        <div style="display:flex; align-items:center; justify-content:space-between; padding-top:8px; padding-bottom:48px;">
          <a href="<?= BASE_URL ?>/assisted_renewal.php?step=2" style="display:inline-flex; align-items:center; gap:6px; padding:10px 20px; border-radius:8px; border:1px solid #cbd5e1; background:white; font-size:12px; font-weight:600; color:#475569; text-decoration:none; box-shadow:0 1px 2px rgba(0,0,0,0.05); transition:all 0.2s;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M15 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
            </svg>
            Back
          </a>
          <a href="<?= BASE_URL ?>/assisted_renewal.php?step=4" style="display:inline-flex; align-items:center; gap:6px; padding:10px 24px; border-radius:8px; background:#2563eb; font-size:12px; font-weight:600; color:white; text-decoration:none; box-shadow:0 4px 6px rgba(37,99,235,0.2); transition:all 0.2s;">
            <span>Next: Upload Documents</span>
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
            </svg>
          </a>
        </div>

      <?php elseif ($step === 4 && $selected_driver): ?>
        <!-- Step 4: Upload Documents -->
        
        <!-- Selected Driver Banner -->
        <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:12px; padding:20px; box-shadow:0 1px 2px rgba(0,0,0,0.02); margin-bottom:24px;">
          <div style="display:flex; flex-direction:column; gap:16px;">
            <div style="flex:1;">
              <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:#2563eb; margin-bottom:4px;">SELECTED DRIVER:</div>
              <div style="display:flex; flex-wrap:wrap; align-items:center; gap:12px;">
                <span style="font-size:20px; font-weight:700; color:#0f172a;"><?= e($selected_driver['name']) ?></span>
                <span style="display:inline-flex; align-items:center; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:600; background:rgba(219,234,254,0.8); color:#1e40af; border:1px solid #93c5fd; font-family:'Courier New',monospace;">
                  <?= e($selected_driver['driver_id']) ?>
                </span>
              </div>
              <a href="<?= BASE_URL ?>/assisted_renewal.php?step=1" style="display:inline-flex; align-items:center; gap:4px; font-size:12px; font-weight:600; color:#2563eb; text-decoration:none; margin-top:8px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
                Change Driver
              </a>
            </div>
            <div>
              <div style="display:inline-flex; align-items:center; padding:6px 14px; background:white; border:1px solid #a7f3d0; border-radius:8px; color:#059669; font-size:12px; font-weight:500; box-shadow:0 1px 2px rgba(0,0,0,0.02);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px; color:#10b981;">
                  <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
                Verified <?= e($selected_driver['barangay']) ?> TODA Member
              </div>
            </div>
          </div>
        </div>

        <form id="uploadForm" method="post" action="<?= BASE_URL ?>/assisted_renewal.php" enctype="multipart/form-data">
          <input type="hidden" name="driver_id" value="<?= e($selected_driver['driver_id']) ?>">
          <input type="hidden" name="submit_assisted_renewal" value="1">
          
          <!-- Upload Documents Card -->
          <div style="background:white; border:1px solid rgba(226,232,240,0.9); border-radius:16px; box-shadow:0 1px 2px rgba(0,0,0,0.02); overflow:hidden; margin-bottom:24px;">
            <!-- Card Header -->
            <div style="padding:20px 28px; border-bottom:1px solid #f1f5f9; display:flex; flex-direction:column; gap:8px;">
              <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:8px;">
                <div style="display:flex; align-items:center; gap:12px;">
                  <h3 style="font-size:16px; font-weight:700; color:#0f172a; letter-spacing:-0.01em; margin:0;">Upload Documents</h3>
                  <span style="display:inline-flex; align-items:center; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:600; background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0;">
                    Mandatory Verification
                  </span>
                </div>
                <span style="font-size:12px; color:#94a3b8; font-weight:500;">Clear scanned copies or camera photos</span>
              </div>
              <p style="font-size:12px; color:#64748b; margin:0;">Upload all 4 required documents. Accepted formats: JPG, PNG, PDF (max 4MB each)</p>
            </div>

            <!-- Document Upload Grid -->
            <div style="padding:28px;">
              <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:20px;">
                
                <!-- Driver's License -->
                <div style="position:relative; cursor:pointer; border:2px dashed #e2e8f0; border-radius:12px; padding:32px; transition:all 0.2s; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; min-height:180px;" class="doc-upload-card" id="box-license" onmouseover="this.style.borderColor='#3b82f6'; this.style.background='rgba(59,130,246,0.02)';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.background='transparent';">
                  <input type="file" name="license" id="file-license" accept="image/*,.pdf" required style="position:absolute; width:100%; height:100%; opacity:0; cursor:pointer;">
                  <div style="width:48px; height:48px; border-radius:50%; background:#f8fafc; display:flex; align-items:center; justify-content:center; color:#94a3b8; margin-bottom:12px; transition:all 0.2s;" class="upload-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                  </div>
                  <h4 style="font-size:14px; font-weight:700; color:#0f172a; margin:0 0 4px 0;">Driver's License</h4>
                  <p style="font-size:12px; color:#94a3b8; font-weight:500; margin:0 0 12px 0;">Valid government-issued PRC/LTO license</p>
                  <div style="display:flex; align-items:center; gap:8px; font-size:11px;">
                    <span style="color:#3b82f6; font-weight:600;">Click to upload</span>
                    <span style="color:#cbd5e1;">•</span>
                    <span style="color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em; font-family:monospace; font-size:10px;">Max 4MB</span>
                  </div>
                  <div id="preview-license" style="margin-top:12px; width:100%;"></div>
                </div>

                <!-- OR/CR -->
                <div style="position:relative; cursor:pointer; border:2px dashed #e2e8f0; border-radius:12px; padding:32px; transition:all 0.2s; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; min-height:180px;" class="doc-upload-card" id="box-or_cr" onmouseover="this.style.borderColor='#3b82f6'; this.style.background='rgba(59,130,246,0.02)';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.background='transparent';">
                  <input type="file" name="or_cr" id="file-or_cr" accept="image/*,.pdf" required style="position:absolute; width:100%; height:100%; opacity:0; cursor:pointer;">
                  <div style="width:48px; height:48px; border-radius:50%; background:#f8fafc; display:flex; align-items:center; justify-content:center; color:#94a3b8; margin-bottom:12px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                  </div>
                  <h4 style="font-size:14px; font-weight:700; color:#0f172a; margin:0 0 4px 0;">OR/CR</h4>
                  <p style="font-size:12px; color:#94a3b8; font-weight:500; margin:0 0 12px 0;">Updated LGU / LTO registration receipt</p>
                  <div style="display:flex; align-items:center; gap:8px; font-size:11px;">
                    <span style="color:#3b82f6; font-weight:600;">Click to upload</span>
                    <span style="color:#cbd5e1;">•</span>
                    <span style="color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em; font-family:monospace; font-size:10px;">Max 4MB</span>
                  </div>
                  <div id="preview-or_cr" style="margin-top:12px; width:100%;"></div>
                </div>

                <!-- Payment Receipt -->
                <div style="position:relative; cursor:pointer; border:2px dashed #e2e8f0; border-radius:12px; padding:32px; transition:all 0.2s; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; min-height:180px;" class="doc-upload-card" id="box-receipt" onmouseover="this.style.borderColor='#3b82f6'; this.style.background='rgba(59,130,246,0.02)';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.background='transparent';">
                  <input type="file" name="receipt" id="file-receipt" accept="image/*,.pdf" required style="position:absolute; width:100%; height:100%; opacity:0; cursor:pointer;">
                  <div style="width:48px; height:48px; border-radius:50%; background:#f8fafc; display:flex; align-items:center; justify-content:center; color:#94a3b8; margin-bottom:12px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                  </div>
                  <h4 style="font-size:14px; font-weight:700; color:#0f172a; margin:0 0 4px 0;">Payment Receipt</h4>
                  <p style="font-size:12px; color:#94a3b8; font-weight:500; margin:0 0 12px 0;">Official TODA renewal fee or treasury receipt</p>
                  <div style="display:flex; align-items:center; gap:8px; font-size:11px;">
                    <span style="color:#3b82f6; font-weight:600;">Click to upload</span>
                    <span style="color:#cbd5e1;">•</span>
                    <span style="color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em; font-family:monospace; font-size:10px;">Max 4MB</span>
                  </div>
                  <div id="preview-receipt" style="margin-top:12px; width:100%;"></div>
                </div>

                <!-- 1x1 Picture -->
                <div style="position:relative; cursor:pointer; border:2px dashed #e2e8f0; border-radius:12px; padding:32px; transition:all 0.2s; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; min-height:180px;" class="doc-upload-card" id="box-photo" onmouseover="this.style.borderColor='#3b82f6'; this.style.background='rgba(59,130,246,0.02)';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.background='transparent';">
                  <input type="file" name="photo" id="file-photo" accept="image/*" required style="position:absolute; width:100%; height:100%; opacity:0; cursor:pointer;">
                  <div style="width:48px; height:48px; border-radius:50%; background:#f8fafc; display:flex; align-items:center; justify-content:center; color:#94a3b8; margin-bottom:12px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                  </div>
                  <h4 style="font-size:14px; font-weight:700; color:#0f172a; margin:0 0 4px 0;">1x1 Picture</h4>
                  <p style="font-size:12px; color:#94a3b8; font-weight:500; margin:0 0 12px 0;">Recent formal ID photo with white background</p>
                  <div style="display:flex; align-items:center; gap:8px; font-size:11px;">
                    <span style="color:#3b82f6; font-weight:600;">Click to upload</span>
                    <span style="color:#cbd5e1;">•</span>
                    <span style="color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em; font-family:monospace; font-size:10px;">Max 4MB</span>
                  </div>
                  <div id="preview-photo" style="margin-top:12px; width:100%;"></div>
                </div>

              </div>
            </div>
          </div>

          <!-- Verbal Consent Box -->
          <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:12px; padding:20px; box-shadow:0 1px 2px rgba(0,0,0,0.02); margin-bottom:24px;">
            <label style="display:flex; align-items:start; gap:14px; cursor:pointer; user-select:none;">
              <input type="checkbox" name="consent" id="consentCheck" value="1" required style="margin-top:4px; width:16px; height:16px; border-radius:4px; color:#2563eb; border:1px solid #d97706; cursor:pointer; flex-shrink:0;" />
              <div style="font-size:14px;">
                <span style="font-weight:700; color:#0f172a; display:block; margin-bottom:4px;">
                  I confirm that <?= e($selected_driver['name']) ?> has provided verbal consent for me to submit these renewal documents on their behalf.
                </span>
                <p style="font-size:12px; color:#64748b; margin:0; line-height:1.6;">
                  By checking this box, I acknowledge that I have personally verified the driver's identity and obtained their permission to process this assisted renewal.
                </p>
              </div>
            </label>
          </div>

          <!-- Bottom Navigation -->
          <div style="padding-top:8px; display:flex; align-items:center; justify-content:space-between; padding-bottom:48px;">
            <a href="<?= BASE_URL ?>/assisted_renewal.php?step=3" style="display:inline-flex; align-items:center; gap:8px; padding:10px 20px; background:white; border:1px solid #cbd5e1; color:#475569; border-radius:12px; font-size:14px; font-weight:600; text-decoration:none; box-shadow:0 1px 2px rgba(0,0,0,0.05); transition:all 0.2s;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:#64748b;">
                <path d="M15 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"></path>
              </svg>
              Back
            </a>
            <button type="submit" id="btnSubmit" disabled style="display:inline-flex; align-items:center; gap:8px; padding:10px 24px; background:#2563eb; color:white; border:none; border-radius:12px; font-size:14px; font-weight:600; box-shadow:0 4px 6px rgba(37,99,235,0.3); transition:all 0.15s; cursor:not-allowed; opacity:0.5;">
              <span>Next: Review & Submit</span>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"></path>
              </svg>
            </button>
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
                preview.innerHTML = '<img src="' + e.target.result + '" style="max-width:100%; max-height:100px; border-radius:8px; margin-top:8px;"><div style="font-size:11px; color:#10b981; margin-top:6px; font-weight:600;">✓ ' + file.name + '</div>';
              };
              reader.readAsDataURL(file);
            } else {
              preview.innerHTML = '<div style="font-size:11px; color:#10b981; margin-top:6px; font-weight:600;">✓ ' + file.name + '</div>';
            }

            box.style.borderColor = '#10b981';
            box.style.background = 'rgba(16,185,129,0.05)';

            // Check if can submit
            checkCanSubmit();
          });
        });

        consentCheck.addEventListener('change', checkCanSubmit);

        function checkCanSubmit() {
          const allUploaded = fileInputs.every(docType => uploadedFiles[docType]);
          const consentGiven = consentCheck.checked;
          const canSubmit = allUploaded && consentGiven;
          
          btnSubmit.disabled = !canSubmit;
          btnSubmit.style.cursor = canSubmit ? 'pointer' : 'not-allowed';
          btnSubmit.style.opacity = canSubmit ? '1' : '0.5';
        }

        document.getElementById('uploadForm').addEventListener('submit', function() {
          btnSubmit.disabled = true;
          btnSubmit.innerHTML = '<span>Submitting...</span>';
        });
        </script>

      <?php endif; ?>

    <?php endif; ?>

  </div>
</div>

<?php render_shared_script(); ?>
</body>
</html>
