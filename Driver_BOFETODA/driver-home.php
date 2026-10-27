<?php

require 'driver-database.php';
require 'driver-template.php';
require_once __DIR__ . '/../Admin_BOFETODA/r2-storage.php';

$driver = require_driver();

// Get latest general announcements
$generalAnnouncements = get_latest_announcements(3);

$currentAppNo = get_current_application($driver['driver_id']);
$currentAppStatus = null;
$currentDetail = null;
if ($currentAppNo) {
    $currentDetail = get_application_detail($currentAppNo);
    $currentAppStatus = $currentDetail['status'] ?? null;
}

if ($currentAppStatus
    && in_array($currentAppStatus, ['Approved', 'Rejected'], true)
    && !application_is_current_cycle($currentDetail)
) {
    $currentAppNo = null;
    $currentAppStatus = null;
    $currentDetail = null;
}

$openAnnouncement = null;
foreach (get_open_announcement() ?? [] as $a) {
    $openAnnouncement = $a;
    break;
}

$upcomingAnnouncement = null;
if (!$openAnnouncement) {
    foreach (get_upcoming_announcement() ?? [] as $a) {
        $upcomingAnnouncement = $a;
        break;
    }
}

if ($currentAppStatus && !in_array($currentAppStatus, ['Approved', 'Rejected'], true)) {
    $state = 'submitted';
} elseif ($currentAppStatus === 'Rejected') {
    $state = 'rejected';
} elseif ($currentAppStatus === 'Approved') {
    $state = 'approved';
} elseif ($openAnnouncement) {
    $state = 'open';
} elseif ($upcomingAnnouncement) {
    $state = 'upcoming';
} else {
    $state = 'none';
}

$driverInitial = strtoupper(substr($driver['name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" name="viewport">
  <title>Bocaue TODA Member Portal</title>
  
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            toda: {
              yellow: '#F59E0B',
              'yellow-hover': '#D97706',
              'yellow-light': '#FEF3C7',
              dark: '#0A0F1D',
              card: '#FFFFFF',
              border: '#E2E8F0',
              text: '#0F172A',
              muted: '#64748B',
              blue: '#2563EB',
              'blue-soft': '#EFF6FF',
            }
          },
          boxShadow: {
            'card': '0 2px 10px -2px rgba(15, 23, 42, 0.05), 0 1px 3px -1px rgba(15, 23, 42, 0.03)',
            'floating': '0 10px 25px -5px rgba(245, 158, 11, 0.35)',
            'nav': '0 -4px 20px -2px rgba(0, 0, 0, 0.15)'
          }
        }
      }
    }
  </script>
  <style>
    body {
      -webkit-font-smoothing: antialiased;
      -webkit-tap-highlight-color: transparent;
      padding-top: env(safe-area-inset-top);
      padding-bottom: env(safe-area-inset-bottom);
    }
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
  </style>
</head>
<body class="bg-slate-100 text-toda-text min-h-screen flex justify-center">

<!-- Mobile Screen Container -->
<div class="w-full max-w-[420px] bg-slate-50 min-h-screen flex flex-col relative shadow-2xl border-x border-slate-200">

  <!-- Top Brand Bar -->
  <header class="pt-3 px-5 pb-1 flex items-center justify-between text-[11px] text-slate-400 font-medium tracking-wide">
    <div class="flex items-center space-x-1.5">
      <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
      <span class="text-slate-600 font-semibold">TODA Link Mobile</span>
    </div>
    <span>© 2026 Bocaue TODA Inc.</span>
  </header>

  <!-- User Greeting Header -->
  <section class="px-5 pt-3 pb-4">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Good Day!</p>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-tight mt-0.5">
          <?= h($driver['name']) ?>
        </h1>
        <div class="flex items-center gap-2 mt-1">
          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-100 text-emerald-700">
            Active Member
          </span>
        </div>
      </div>
      
      <!-- Profile Avatar -->
      <div class="relative flex-shrink-0 ml-3">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-400 to-amber-200 p-0.5 shadow-sm">
          <div class="w-full h-full bg-white rounded-[14px] flex items-center justify-center font-bold text-amber-600 text-lg">
            <?= h($driverInitial) ?>
          </div>
        </div>
        <div class="absolute -bottom-1 -right-1 bg-emerald-500 w-3.5 h-3.5 rounded-full border-2 border-white"></div>
      </div>
    </div>
  </section>

  <!-- Main Content Area -->
  <main class="flex-1 px-4 space-y-4 pb-28 hide-scrollbar overflow-y-auto">

    <!-- Announcements Section -->
    <?php if (!empty($generalAnnouncements)): ?>
    <section>
      <div class="flex items-center justify-between px-1 mb-2.5">
        <div class="flex items-center space-x-2">
          <span class="p-1 rounded-md bg-rose-50 text-rose-500">
            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path d="M20 3H4c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h3l-1 4h2l1-4h8l1 4h2l-1-4h3c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 13H4V5h16v11z"/>
            </svg>
          </span>
          <h2 class="text-base font-bold text-slate-800 tracking-tight">Announcements</h2>
        </div>
        <a href="driver-notifications.php" class="text-xs font-semibold text-slate-600 hover:text-slate-900 inline-flex items-center gap-1 transition-colors">
          View All
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M14 5l7 7m0 0l-7 7m7-7H3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"/>
          </svg>
        </a>
      </div>

      <?php 
      $firstAnn = $generalAnnouncements[0];
      $truncated = truncate_text($firstAnn['message'], 150);
      $catColor = get_category_color($firstAnn['category']);
      $isUrgent = $firstAnn['priority'] === 'urgent';
      ?>
      <article class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-card transition-all hover:shadow-md">
        <div class="flex items-center justify-between mb-2">
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-extrabold tracking-wide uppercase <?= $isUrgent ? 'bg-red-600' : 'bg-blue-600' ?> text-white shadow-sm">
            <?= $isUrgent ? '🔥 URGENT' : h($firstAnn['category']) ?>
          </span>
          <span class="text-[11px] text-slate-400 font-medium"><?= h(date('M j, Y', strtotime($firstAnn['created_at']))) ?></span>
        </div>
        
        <h3 class="text-sm font-bold text-slate-900 leading-snug hover:text-blue-600 transition-colors">
          <?= h($firstAnn['title']) ?>
        </h3>
        
        <p class="text-xs text-slate-600 mt-1.5 leading-relaxed" id="msg-<?= (int)$firstAnn['id'] ?>">
          <?= nl2br(h($truncated['text'])) ?>
        </p>
        
        <?php if ($truncated['is_truncated']): ?>
        <span class="hidden text-xs text-slate-600 mt-1.5 leading-relaxed" id="full-<?= (int)$firstAnn['id'] ?>">
          <?= nl2br(h($firstAnn['message'])) ?>
        </span>
        <a href="javascript:void(0)" class="inline-block text-xs font-bold text-blue-600 hover:underline mt-1" onclick="toggleMessage(<?= (int)$firstAnn['id'] ?>)" id="toggle-<?= (int)$firstAnn['id'] ?>">
          Read more...
        </a>
        <?php endif; ?>

        <?php if ($firstAnn['image_count'] > 0): 
          $images = get_announcement_images($firstAnn['id']);
          if (!empty($images)):
        ?>
        <div class="mt-3 flex items-start space-x-3">
          <div class="relative w-24 h-20 rounded-xl overflow-hidden bg-slate-100 flex-shrink-0 border border-slate-200 shadow-sm">
            <img alt="Announcement" class="w-full h-full object-cover object-center transform hover:scale-105 transition-transform duration-300" src="<?= h(get_announcement_image_url($images[0]['image_path'])) ?>" loading="lazy">
            <span class="absolute bottom-1 right-1 bg-black/60 backdrop-blur-sm text-white text-[9px] px-1.5 py-0.5 rounded font-mono font-medium">
              Photo
            </span>
          </div>
          
          <div class="space-y-1.5 pt-0.5 flex-1">
            <?php if ($firstAnn['event_date']): ?>
            <div class="flex items-center text-xs text-slate-700 font-medium">
              <span class="w-4 h-4 mr-1.5 inline-flex items-center justify-center flex-shrink-0">📅</span>
              <span class="truncate"><strong>Event:</strong> <?= h(format_event_date($firstAnn['event_date'])) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($firstAnn['event_location']): ?>
            <div class="flex items-center text-xs text-slate-700 font-medium">
              <span class="w-4 h-4 mr-1.5 inline-flex items-center justify-center flex-shrink-0">📍</span>
              <span><strong>Location:</strong> <?= h($firstAnn['event_location']) ?></span>
            </div>
            <?php endif; ?>
            
            <div class="flex items-center text-[11px] text-slate-400">
              <span class="w-4 h-4 mr-1.5 inline-flex items-center justify-center flex-shrink-0">🕒</span>
              <span>Posted: <?= h(date('M j, Y', strtotime($firstAnn['created_at']))) ?></span>
            </div>
          </div>
        </div>
        <?php endif; endif; ?>
      </article>
    </section>
    <?php endif; ?>

    <!-- Current Status Section -->
    <?php if ($state === 'submitted' || $state === 'rejected' || $state === 'approved'): ?>
    <section class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-card">
      <div class="flex justify-between items-start mb-4">
        <div>
          <h2 class="text-base font-black text-slate-900 tracking-tight">Current Status</h2>
          <p class="text-xs font-semibold text-slate-500 mt-0.5 flex items-center gap-1 font-mono">
            Application No. <span class="text-slate-800 font-bold bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200"><?= h($currentAppNo) ?></span>
          </p>
        </div>
        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider 
          <?= $state === 'approved' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 
              ($state === 'rejected' ? 'bg-red-50 text-red-700 border border-red-200' : 
              'bg-amber-50 text-amber-700 border border-amber-200') ?>">
          <?= $state === 'approved' ? 'APPROVED' : ($state === 'rejected' ? 'REJECTED' : 'IN PROGRESS') ?>
        </span>
      </div>

      <?php if ($state === 'submitted'): ?>
      <!-- 4-Step Tracker -->
      <div class="py-2 px-1">
        <div class="relative flex items-center justify-between">
          <!-- Background Line -->
          <div class="absolute left-4 right-4 top-4 h-[2px] bg-slate-200 -z-0"></div>
          <!-- Progress Line -->
          <?php 
          $progress = 0;
          if ($currentAppStatus === 'Pending') $progress = 0;
          elseif ($currentAppStatus === 'Validated') $progress = 33;
          elseif ($currentAppStatus === 'Approved') $progress = 100;
          ?>
          <div class="absolute left-4 top-4 h-[2px] bg-amber-500 -z-0" style="width: <?= $progress ?>%"></div>

          <!-- Step 1: Submitted -->
          <div class="flex flex-col items-center relative z-10">
            <div class="w-8 h-8 rounded-full bg-amber-500 text-white flex items-center justify-center shadow-md font-bold text-xs ring-4 ring-amber-100">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="3"/>
              </svg>
            </div>
            <span class="text-[11px] font-bold text-slate-800 mt-1.5">Submitted</span>
            <span class="text-[9px] font-medium text-slate-400"><?= !empty($currentDetail['submitted_at']) ? h(date('M j, Y', strtotime($currentDetail['submitted_at']))) : '-' ?></span>
          </div>

          <!-- Step 2: Validated -->
          <div class="flex flex-col items-center relative z-10">
            <div class="w-8 h-8 rounded-full <?= $currentAppStatus !== 'Pending' ? 'bg-amber-500 text-white shadow-md ring-4 ring-amber-100' : 'bg-slate-100 text-slate-500 border border-slate-300' ?> flex items-center justify-center font-bold text-xs">
              <?= $currentAppStatus !== 'Pending' ? '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="3"/></svg>' : '2' ?>
            </div>
            <span class="text-[11px] <?= $currentAppStatus !== 'Pending' ? 'font-bold text-slate-800' : 'font-medium text-slate-500' ?> mt-1.5">Validated</span>
            <span class="text-[9px] font-medium <?= $currentAppStatus !== 'Pending' ? 'text-slate-400' : 'text-transparent' ?>"><?= !empty($currentDetail['validated_at']) ? h(date('M j, Y', strtotime($currentDetail['validated_at']))) : '-' ?></span>
          </div>

          <!-- Step 3: Approved -->
          <div class="flex flex-col items-center relative z-10">
            <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 border border-slate-300 flex items-center justify-center font-bold text-xs">
              3
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1.5">Approved</span>
            <span class="text-[9px] font-normal text-transparent">-</span>
          </div>

          <!-- Step 4: Done -->
          <div class="flex flex-col items-center relative z-10">
            <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 border border-slate-300 flex items-center justify-center font-bold text-xs">
              4
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1.5">Done</span>
            <span class="text-[9px] font-normal text-transparent">-</span>
          </div>
        </div>
      </div>
      <?php elseif ($state === 'rejected'): ?>
      <div class="py-2 px-3 rounded-xl bg-red-50 border border-red-200 mt-3">
        <p class="text-sm font-bold text-red-900 mb-1"><?= h($currentDetail['reject_reason'] ?? 'Application Rejected') ?></p>
        <p class="text-xs text-red-700"><?= h($currentDetail['reject_note'] ?: 'Please review and resubmit your application.') ?></p>
      </div>
      <?php elseif ($state === 'approved'): ?>
      <div class="py-2 px-3 rounded-xl bg-emerald-50 border border-emerald-200 mt-3">
        <p class="text-sm font-semibold text-emerald-900">✅ Your renewal for this cycle has been approved.</p>
        <p class="text-xs text-emerald-700 mt-1">There's nothing else to submit right now.</p>
      </div>
      <?php endif; ?>

      <button onclick="window.location.href='driver-view-application.php'" class="w-full mt-4 py-3 px-4 bg-amber-400 hover:bg-amber-500 active:scale-[0.98] text-slate-900 font-extrabold text-sm rounded-xl shadow-sm hover:shadow transition-all flex items-center justify-center space-x-2 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2">
        <span>View Details</span>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"/>
        </svg>
      </button>
    </section>
    <?php elseif ($state === 'open'): ?>
    <section class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-card">
      <div class="flex justify-between items-start mb-3">
        <h2 class="text-base font-black text-slate-900 tracking-tight">Renewal Period</h2>
        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
          OPEN
        </span>
      </div>
      <h3 class="text-sm font-bold text-slate-900 mb-2"><?= h($openAnnouncement['title']) ?></h3>
      <p class="text-xs text-slate-600 leading-relaxed mb-3"><?= h($openAnnouncement['message']) ?></p>
      <div class="space-y-1.5 mb-4">
        <div class="flex items-center text-xs text-slate-700 font-medium">
          <span class="w-4 h-4 mr-1.5">📅</span>
          <span><strong>Date Open:</strong> <?= h($openAnnouncement['opened']) ?></span>
        </div>
        <div class="flex items-center text-xs text-slate-700 font-medium">
          <span class="w-4 h-4 mr-1.5">⏰</span>
          <span><strong>Deadline:</strong> <?= h($openAnnouncement['deadline']) ?></span>
        </div>
      </div>
      <button onclick="window.location.href='driver-renewal.php'" class="w-full py-3 px-4 bg-amber-400 hover:bg-amber-500 active:scale-[0.98] text-slate-900 font-extrabold text-sm rounded-xl shadow-sm transition-all">
        Submit Now!
      </button>
    </section>
    <?php elseif ($state === 'upcoming'): ?>
    <section class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-card">
      <div class="flex justify-between items-start mb-3">
        <h2 class="text-base font-black text-slate-900 tracking-tight">Renewal Period</h2>
        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200">
          UPCOMING
        </span>
      </div>
      <h3 class="text-sm font-bold text-slate-900 mb-2"><?= h($upcomingAnnouncement['title']) ?></h3>
      <p class="text-xs text-slate-600 leading-relaxed mb-3"><?= h($upcomingAnnouncement['message']) ?></p>
      <div class="space-y-1.5 mb-3">
        <div class="flex items-center text-xs text-slate-700 font-medium">
          <span class="w-4 h-4 mr-1.5">📅</span>
          <span><strong>Opens:</strong> <?= h($upcomingAnnouncement['opened']) ?></span>
        </div>
        <div class="flex items-center text-xs text-slate-700 font-medium">
          <span class="w-4 h-4 mr-1.5">⏰</span>
          <span><strong>Deadline:</strong> <?= h($upcomingAnnouncement['deadline']) ?></span>
        </div>
      </div>
      <p class="text-xs text-slate-500">Submission will open on the date above. Come back then to apply.</p>
    </section>
    <?php endif; ?>

    <!-- Renewal History Section -->
    <section class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-card">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-base font-black text-slate-900 tracking-tight">Renewal History</h2>
        <span class="text-xs text-slate-400 font-medium">Archive</span>
      </div>
      
      <?php $history = get_renewal_history($driver['driver_id']); ?>
      <?php if (empty($history)): ?>
      <div class="py-5 px-3 rounded-xl bg-slate-50/70 border border-dashed border-slate-200 flex flex-col items-center justify-center text-center">
        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
          </svg>
        </div>
        <p class="text-xs font-semibold text-slate-500">No renewal history yet.</p>
        <p class="text-[11px] text-slate-400 mt-0.5">Prior records and expired permits will show here once completed.</p>
      </div>
      <?php else: ?>
      <div class="space-y-2">
        <?php foreach ($history as $item): ?>
        <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-slate-50 border border-slate-200">
          <div>
            <p class="text-xs font-bold text-slate-900"><?= h($item['year']) ?></p>
            <p class="text-[11px] text-slate-500 font-mono"><?= h($item['app_no']) ?></p>
          </div>
          <div class="text-right">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $item['status'] === 'APPROVED' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' ?>">
              <?= h($item['status']) ?>
            </span>
            <p class="text-[10px] text-slate-400 mt-0.5"><?= h($item['date']) ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </section>

  </main>

  <?php
  // Get unread message count for badge
  require_once 'driver-database.php';
  $unrepliedCount = get_unreplied_message_count($driver['driver_id']);
  ?>
  
  <!-- Floating Chat Button -->
  <button type="button" class="fixed bottom-20 right-4 md:right-[calc(50%-190px)] w-14 h-14 bg-amber-400 hover:bg-amber-500 active:scale-95 text-slate-900 rounded-full shadow-floating flex items-center justify-center transition-all duration-200 z-40" id="chatToggleBtn" aria-label="Open chat">
    <?php if ($unrepliedCount > 0): ?>
    <span class="absolute -top-1 -right-1 min-w-[20px] h-5 bg-red-600 text-white text-[10px] font-extrabold rounded-full flex items-center justify-center px-1.5 border-2 border-white"><?= $unrepliedCount > 9 ? '9+' : $unrepliedCount ?></span>
    <?php endif; ?>
    <svg class="w-6 h-6 fill-slate-950" viewBox="0 0 24 24">
      <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
    </svg>
  </button>

  <!-- Chat Window -->
  <div class="fixed bottom-20 right-4 md:right-[calc(50%-190px)] w-[calc(100%-2rem)] max-w-[380px] h-[500px] max-h-[calc(100vh-180px)] bg-white rounded-2xl shadow-2xl hidden flex-col z-50 opacity-0 transform translate-y-5 scale-95 transition-all duration-250" id="chatWindow">
    <div class="bg-amber-400 px-5 py-4 rounded-t-2xl flex items-center justify-between">
      <div>
        <div class="text-base font-bold text-slate-900">Message Admin</div>
        <div class="text-[11px] text-slate-700">BOFETODA Support</div>
      </div>
      <button type="button" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-black/10 transition-colors" id="chatCloseBtn" aria-label="Close chat">
        <svg class="w-5 h-5 text-slate-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>
    
    <div class="flex-1 overflow-y-auto p-4 bg-slate-50 flex flex-col gap-3 hide-scrollbar" id="chatMessages">
      <div class="flex-1 flex flex-col items-center justify-center text-center text-slate-500 py-10">
        <svg class="w-12 h-12 mb-3 opacity-40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
        <div class="text-sm font-semibold">Loading messages...</div>
      </div>
    </div>
    
    <div class="p-3 bg-white rounded-b-2xl border-t border-slate-200">
      <div class="hidden gap-2 pb-2 flex-wrap" id="chatAttachmentPreview"></div>
      <div class="flex gap-2 items-end">
        <button type="button" class="w-10 h-10 bg-slate-100 border border-slate-300 rounded-full flex items-center justify-center hover:bg-slate-200 transition-colors relative" title="Attach file">
          <input type="file" id="chatFileInput" accept="image/*,.pdf,.doc,.docx" multiple class="absolute w-full h-full opacity-0 cursor-pointer">
          <svg class="w-5 h-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
          </svg>
        </button>
        <textarea class="flex-1 bg-slate-100 border border-slate-300 rounded-2xl px-4 py-2.5 text-sm resize-none max-h-24 min-h-[40px] focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400" id="chatInput" placeholder="Type your message..." rows="1"></textarea>
        <button type="button" class="w-10 h-10 bg-amber-400 rounded-full flex items-center justify-center hover:bg-amber-500 active:scale-95 transition-all" id="chatSendBtn">
          <svg class="w-5 h-5 text-slate-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="22" y1="2" x2="11" y2="13"/>
            <polygon points="22 2 15 22 11 13 2 9 22 2"/>
          </svg>
        </button>
      </div>
    </div>
  </div>

  <!-- Bottom Navigation Bar -->
  <nav class="fixed bottom-0 left-0 right-0 max-w-[420px] mx-auto bg-[#070b14] text-slate-400 border-t border-slate-800 z-50 px-2 py-2 shadow-nav">
    <div class="grid grid-cols-4 items-center">
      <a class="flex flex-col items-center justify-center py-1 text-amber-400" href="driver-home.php">
        <svg class="w-5 h-5 stroke-current stroke-2" fill="none" viewBox="0 0 24 24">
          <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="text-[11px] font-bold mt-1">Home</span>
      </a>
      
      <a class="flex flex-col items-center justify-center py-1 hover:text-slate-200 transition-colors" href="driver-renewal.php">
        <svg class="w-5 h-5 stroke-current stroke-2" fill="none" viewBox="0 0 24 24">
          <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="text-[11px] font-medium mt-1">Submit</span>
      </a>
      
      <a class="flex flex-col items-center justify-center py-1 hover:text-slate-200 transition-colors relative" href="driver-notifications.php">
        <div class="relative">
          <svg class="w-5 h-5 stroke-current stroke-2" fill="none" viewBox="0 0 24 24">
            <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <?php 
          $unreadCount = get_unread_notification_count($driver['driver_id']);
          if ($unreadCount > 0): 
          ?>
          <span class="absolute -top-1 -right-1 w-2 h-2 rounded-full bg-amber-400 ring-2 ring-slate-900"></span>
          <?php endif; ?>
        </div>
        <span class="text-[11px] font-medium mt-1">Notifications</span>
      </a>
      
      <a class="flex flex-col items-center justify-center py-1 hover:text-slate-200 transition-colors" href="driver-profile.php">
        <svg class="w-5 h-5 stroke-current stroke-2" fill="none" viewBox="0 0 24 24">
          <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="text-[11px] font-medium mt-1">Profile</span>
      </a>
    </div>
  </nav>

</div>

<script>
function toggleMessage(annId) {
  const truncatedMsg = document.getElementById('msg-' + annId);
  const fullMsg = document.getElementById('full-' + annId);
  const toggleBtn = document.getElementById('toggle-' + annId);
  
  if (fullMsg.classList.contains('hidden')) {
    truncatedMsg.classList.add('hidden');
    fullMsg.classList.remove('hidden');
    toggleBtn.textContent = 'Show less';
  } else {
    truncatedMsg.classList.remove('hidden');
    fullMsg.classList.add('hidden');
    toggleBtn.textContent = 'Read more...';
  }
}

// Chat functionality
(function() {
  var chatToggleBtn = document.getElementById('chatToggleBtn');
  var chatWindow = document.getElementById('chatWindow');
  var chatCloseBtn = document.getElementById('chatCloseBtn');
  var chatMessages = document.getElementById('chatMessages');
  var chatInput = document.getElementById('chatInput');
  var chatSendBtn = document.getElementById('chatSendBtn');
  var chatFileInput = document.getElementById('chatFileInput');
  var chatAttachmentPreview = document.getElementById('chatAttachmentPreview');
  var driverId = <?= json_encode($driver['driver_id']) ?>;
  var selectedFiles = [];
  
  function openChat() {
    chatWindow.classList.remove('hidden');
    chatWindow.classList.add('flex');
    requestAnimationFrame(function() {
      requestAnimationFrame(function() {
        chatWindow.classList.remove('opacity-0', 'translate-y-5', 'scale-95');
        chatWindow.classList.add('opacity-100', 'translate-y-0', 'scale-100');
      });
    });
    loadMessages();
  }
  
  function closeChat() {
    chatWindow.classList.add('opacity-0', 'translate-y-5', 'scale-95');
    chatWindow.classList.remove('opacity-100', 'translate-y-0', 'scale-100');
    setTimeout(function() {
      chatWindow.classList.remove('flex');
      chatWindow.classList.add('hidden');
    }, 250);
  }
  
  function loadMessages() {
    fetch('api-message-count.php?get_messages=1')
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (data.success && data.messages) {
          renderMessages(data.messages);
        } else {
          showEmpty();
        }
      })
      .catch(function() {
        showEmpty('Failed to load messages');
      });
  }
  
  function renderMessages(messages) {
    if (messages.length === 0) {
      showEmpty();
      return;
    }
    
    var html = '';
    var driverName = <?= json_encode($driver['name']) ?>;
    var driverInitial = <?= json_encode($driverInitial) ?>;
    
    messages.forEach(function(msg) {
      var isMe = msg.sender_type === 'driver';
      var time = new Date(msg.sent_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
      var initials = isMe ? driverInitial : 'A';
      var senderName = isMe ? driverName : 'Admin';
      
      html += '<div class="flex gap-2.5 max-w-[85%] ' + (isMe ? 'self-end flex-row-reverse' : '') + '">';
      html += '  <div class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-sm font-bold ' + (isMe ? 'bg-amber-400 text-slate-900' : 'bg-slate-900 text-amber-400') + '">' + escapeHtml(initials) + '</div>';
      html += '  <div class="flex-1 min-w-0">';
      html += '    <div class="text-[11px] font-semibold text-slate-500 mb-1 px-1 ' + (isMe ? 'text-right' : '') + '">' + escapeHtml(senderName) + '</div>';
      html += '    <div class="rounded-xl px-3.5 py-2.5 text-sm leading-relaxed break-words ' + (isMe ? 'bg-amber-400 text-slate-900' : 'bg-white text-slate-900 border border-slate-200') + '">';
      
      if (msg.message && msg.message.trim()) {
        html += '<div>' + escapeHtml(msg.message).replace(/\n/g, '<br>') + '</div>';
      }
      
      if (msg.attachment_list && msg.attachment_list.length > 0) {
        msg.attachment_list.forEach(function(att) {
          if (att.type && att.type.startsWith('image/')) {
            html += '<img src="download-attachment.php?id=' + att.id + '" class="max-w-[200px] rounded-lg mt-2 cursor-pointer border border-slate-200" alt="' + escapeHtml(att.name) + '">';
          } else {
            html += '<a href="download-attachment.php?id=' + att.id + '" target="_blank" class="block mt-2 text-blue-600 hover:underline">📎 ' + escapeHtml(att.name) + '</a>';
          }
        });
      }
      
      html += '    </div>';
      html += '    <div class="text-[10px] text-slate-400 mt-1 px-1">' + time + '</div>';
      html += '  </div>';
      html += '</div>';
    });
    
    chatMessages.innerHTML = html;
    scrollToBottom();
  }
  
  function showEmpty(text) {
    chatMessages.innerHTML = '<div class="flex-1 flex flex-col items-center justify-center text-center text-slate-500 py-10">' +
      '<svg class="w-12 h-12 mb-3 opacity-40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
      '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>' +
      '</svg>' +
      '<div class="text-sm font-semibold">' + (text || 'No messages yet') + '</div>' +
      '<div class="text-xs text-slate-400 mt-1">Start a conversation with the admin</div>' +
      '</div>';
  }
  
  function updateAttachmentPreview() {
    if (selectedFiles.length === 0) {
      chatAttachmentPreview.classList.add('hidden');
      chatAttachmentPreview.innerHTML = '';
      return;
    }
    
    chatAttachmentPreview.classList.remove('hidden');
    var html = '';
    selectedFiles.forEach(function(file, index) {
      var isImage = file.type.startsWith('image/');
      html += '<div class="relative w-15 h-15 rounded-lg overflow-hidden bg-slate-100 border border-slate-300">';
      if (isImage) {
        html += '<img src="' + URL.createObjectURL(file) + '" class="w-full h-full object-cover" alt="' + escapeHtml(file.name) + '">';
      } else {
        html += '<div class="w-full h-full flex items-center justify-center text-2xl">📄</div>';
      }
      html += '<button type="button" class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-red-600 text-white flex items-center justify-center text-xs font-bold border-2 border-white hover:bg-red-700" data-index="' + index + '">×</button>';
      html += '</div>';
    });
    chatAttachmentPreview.innerHTML = html;
    
    chatAttachmentPreview.querySelectorAll('button').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var index = parseInt(btn.getAttribute('data-index'));
        selectedFiles.splice(index, 1);
        updateAttachmentPreview();
      });
    });
  }
  
  function sendMessage() {
    var message = chatInput.value.trim();
    if (!message && selectedFiles.length === 0) return;
    
    chatSendBtn.disabled = true;
    chatInput.disabled = true;
    chatFileInput.disabled = true;
    
    var formData = new FormData();
    formData.append('message', message);
    formData.append('send_message', '1');
    
    selectedFiles.forEach(function(file) {
      formData.append('attachments[]', file);
    });
    
    fetch('api-message-count.php', {
      method: 'POST',
      body: formData
    })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (data.success) {
          chatInput.value = '';
          selectedFiles = [];
          updateAttachmentPreview();
          loadMessages();
        } else {
          alert('Failed to send message: ' + (data.error || 'Unknown error'));
        }
      })
      .catch(function() {
        alert('Failed to send message');
      })
      .finally(function() {
        chatSendBtn.disabled = false;
        chatInput.disabled = false;
        chatFileInput.disabled = false;
        chatInput.focus();
      });
  }
  
  function scrollToBottom() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }
  
  function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
  
  chatToggleBtn.addEventListener('click', openChat);
  chatCloseBtn.addEventListener('click', closeChat);
  chatSendBtn.addEventListener('click', sendMessage);
  
  chatInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  });
  
  chatFileInput.addEventListener('change', function() {
    Array.from(chatFileInput.files).forEach(function(file) {
      selectedFiles.push(file);
    });
    chatFileInput.value = '';
    updateAttachmentPreview();
  });
  
  // Auto-resize textarea
  chatInput.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 96) + 'px';
  });
})();
</script>

</body>
</html>
