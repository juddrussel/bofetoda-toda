<?php

require 'driver-database.php';
require 'driver-template.php';
require_once __DIR__ . '/../Admin_BOFETODA/r2-storage.php';

$driver = require_driver();

// Get latest general announcements (for display above renewal section)
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

// Don't render the old template
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" name="viewport"/>
<title>Bocaue TODA Member Portal</title>
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
      },
      fontFamily: {
        sans: ['-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
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

<div class="w-full max-w-[420px] bg-slate-50 min-h-screen flex flex-col relative shadow-2xl border-x border-slate-200">

<!-- Top Brand Bar -->
<header class="pt-3 px-5 pb-1 flex items-center justify-between text-[11px] text-slate-400 font-medium tracking-wide">
<div class="flex items-center space-x-1.5">
<span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
<span class="text-slate-600 font-semibold">TODA Link Mobile</span>
</div>
<span>© <?= date('Y') ?> Bocaue TODA Inc.</span>
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
<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
Franchise ID: <?= h($driver['franchise_id'] ?? 'N/A') ?>
</span>
<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-100 text-emerald-700">
Active Member
</span>
</div>
</div>
<!-- Profile Avatar -->
<div class="relative flex-shrink-0 ml-3">
<div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-400 to-amber-200 p-0.5 shadow-sm">
<div class="w-full h-full bg-white rounded-[14px] flex items-center justify-center font-bold text-amber-600 text-lg">
<?php 
$nameParts = explode(' ', $driver['name']);
echo h(strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : '')));
?>
</div>
</div>
<div class="absolute -bottom-1 -right-1 bg-emerald-500 w-3.5 h-3.5 rounded-full border-2 border-white"></div>
</div>
</div>
</section>

<!-- Main Content Area -->
<main class="flex-1 px-4 space-y-4 pb-28">

<!-- Announcements Section -->
<?php if (!empty($generalAnnouncements)): ?>
<section>
<div class="flex items-center justify-between px-1 mb-2.5">
<div class="flex items-center space-x-2">
<span class="p-1 rounded-md bg-rose-50 text-rose-500">
<svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
<path d="M20 3H4c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h3l-1 4h2l1-4h8l1 4h2l-1-4h3c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 13H4V5h16v11z"></path>
</svg>
</span>
<h2 class="text-base font-bold text-slate-800 tracking-tight">Announcements</h2>
</div>
<a class="text-xs font-semibold text-slate-600 hover:text-slate-900 inline-flex items-center gap-1 transition-colors" href="driver-announcements.php">
View All
<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path d="M14 5l7 7m0 0l-7 7m7-7H3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"></path>
</svg>
</a>
</div>

<?php 
$firstAnn = $generalAnnouncements[0];
$truncated = truncate_text($firstAnn['message'], 150);
$catColor = get_category_color($firstAnn['category']);
$isUrgent = $firstAnn['priority'] === 'urgent';
$images = $firstAnn['image_count'] > 0 ? get_announcement_images($firstAnn['id']) : [];
?>

<article class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-card transition-all hover:shadow-md">
<div class="flex items-center justify-between mb-2">
<span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-extrabold tracking-wide uppercase <?= $isUrgent ? 'bg-red-600' : 'bg-blue-600' ?> text-white shadow-sm">
<?= h($firstAnn['category']) ?>
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
<span class="hidden" id="full-<?= (int)$firstAnn['id'] ?>">
<?= nl2br(h($firstAnn['message'])) ?>
</span>
<a class="inline-block text-xs font-bold text-blue-600 hover:underline mt-1 cursor-pointer" onclick="toggleMessage(<?= (int)$firstAnn['id'] ?>)" id="toggle-<?= (int)$firstAnn['id'] ?>">
Read more...
</a>
<?php endif; ?>

<?php if (!empty($images)): 
$firstImage = $images[0];
?>
<div class="mt-3 flex items-start space-x-3">
<div class="relative w-24 h-20 rounded-xl overflow-hidden bg-slate-100 flex-shrink-0 border border-slate-200 shadow-sm">
<img alt="Announcement" class="w-full h-full object-cover object-center transform hover:scale-105 transition-transform duration-300" src="<?= h(get_announcement_image_url($firstImage['image_path'])) ?>" loading="lazy"/>
<span class="absolute bottom-1 right-1 bg-black/60 backdrop-blur-sm text-white text-[9px] px-1.5 py-0.5 rounded font-mono font-medium">
Photo
</span>
</div>

<div class="space-y-1.5 pt-0.5 flex-1">
<?php if ($firstAnn['event_date']): ?>
<div class="flex items-center text-xs text-slate-700 font-medium">
<span class="w-4 h-4 mr-1.5 text-blue-500 inline-flex items-center justify-center flex-shrink-0">📅</span>
<span class="truncate"><strong>Event:</strong> <?= h(format_event_date($firstAnn['event_date'])) ?></span>
</div>
<?php endif; ?>

<?php if ($firstAnn['event_location']): ?>
<div class="flex items-center text-xs text-slate-700 font-medium">
<span class="w-4 h-4 mr-1.5 text-rose-500 inline-flex items-center justify-center flex-shrink-0">📍</span>
<span><strong>Location:</strong> <?= h($firstAnn['event_location']) ?></span>
</div>
<?php endif; ?>

<div class="flex items-center text-[11px] text-slate-400">
<span class="w-4 h-4 mr-1.5 inline-flex items-center justify-center flex-shrink-0">🕒</span>
<span>Posted: <?= h(date('M j, Y', strtotime($firstAnn['created_at']))) ?></span>
</div>
</div>
</div>
<?php endif; ?>
</article>
</section>
<?php endif; ?>

<!-- Current Status Section -->
<?php if ($state === 'submitted'): ?>
<section class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-card">
<div class="flex justify-between items-start mb-4">
<div>
<h2 class="text-base font-black text-slate-900 tracking-tight">Current Status</h2>
<p class="text-xs font-semibold text-slate-500 mt-0.5 flex items-center gap-1 font-mono">
Application No. <span class="text-slate-800 font-bold bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200"><?= h($currentAppNo) ?></span>
</p>
</div>
<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200">
In Progress
</span>
</div>

<div class="py-2 px-1">
<div class="relative flex items-center justify-between">
<div class="absolute left-4 right-4 top-4 h-[2px] bg-slate-200 -z-0"></div>
<?php 
$stepCount = 4;
$currentStep = 1;
if ($currentAppStatus === 'Validated') $currentStep = 2;
elseif ($currentAppStatus === 'Approved') $currentStep = 4;
$progressWidth = (($currentStep - 1) / ($stepCount - 1)) * 100;
?>
<div class="absolute left-4 right-4 top-4 h-[2px] bg-amber-500 -z-0" style="width: <?= $progressWidth ?>%;"></div>

<!-- Step 1: Submitted -->
<div class="flex flex-col items-center relative z-10">
<div class="w-8 h-8 rounded-full bg-amber-500 text-white flex items-center justify-center shadow-md font-bold text-xs ring-4 ring-amber-100">
<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="3"></path>
</svg>
</div>
<span class="text-[11px] font-bold text-slate-800 mt-1.5">Submitted</span>
<span class="text-[9px] font-medium text-slate-400"><?= !empty($currentDetail['submitted_at']) ? h(date('M j, Y', strtotime($currentDetail['submitted_at']))) : '-' ?></span>
</div>

<!-- Step 2: Validated -->
<div class="flex flex-col items-center relative z-10">
<div class="w-8 h-8 rounded-full <?= $currentStep >= 2 ? 'bg-amber-500 text-white ring-4 ring-amber-100' : 'bg-slate-100 text-slate-500 border border-slate-300' ?> flex items-center justify-center font-bold text-xs">
<?= $currentStep >= 2 ? '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="3"></path></svg>' : '2' ?>
</div>
<span class="text-[11px] font-<?= $currentStep >= 2 ? 'bold' : 'medium' ?> text-slate-<?= $currentStep >= 2 ? '800' : '500' ?> mt-1.5">Validated</span>
<span class="text-[9px] font-normal text-<?= $currentStep >= 2 ? 'slate-400' : 'transparent' ?>"><?= !empty($currentDetail['validated_at']) ? h(date('M j, Y', strtotime($currentDetail['validated_at']))) : '-' ?></span>
</div>

<!-- Step 3: Approved -->
<div class="flex flex-col items-center relative z-10">
<div class="w-8 h-8 rounded-full <?= $currentStep >= 3 ? 'bg-amber-500 text-white ring-4 ring-amber-100' : 'bg-slate-100 text-slate-500 border border-slate-300' ?> flex items-center justify-center font-bold text-xs">
<?= $currentStep >= 3 ? '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="3"></path></svg>' : '3' ?>
</div>
<span class="text-[11px] font-<?= $currentStep >= 3 ? 'bold' : 'medium' ?> text-slate-<?= $currentStep >= 3 ? '800' : '500' ?> mt-1.5">Approved</span>
<span class="text-[9px] font-normal text-<?= $currentStep >= 3 ? 'slate-400' : 'transparent' ?>"><?= (!empty($currentDetail['approved_at']) && $currentAppStatus === 'Approved') ? h(date('M j, Y', strtotime($currentDetail['approved_at']))) : '-' ?></span>
</div>

<!-- Step 4: Done -->
<div class="flex flex-col items-center relative z-10">
<div class="w-8 h-8 rounded-full <?= $currentStep >= 4 ? 'bg-amber-500 text-white ring-4 ring-amber-100' : 'bg-slate-100 text-slate-500 border border-slate-300' ?> flex items-center justify-center font-bold text-xs">
4
</div>
<span class="text-[11px] font-<?= $currentStep >= 4 ? 'bold' : 'medium' ?> text-slate-<?= $currentStep >= 4 ? '800' : '500' ?> mt-1.5">Done</span>
<span class="text-[9px] font-normal text-transparent select-none">-</span>
</div>
</div>
</div>

<a href="driver-view-application.php" class="block w-full mt-4 py-3 px-4 bg-amber-400 hover:bg-amber-500 active:scale-[0.98] text-slate-900 font-extrabold text-sm rounded-xl shadow-sm hover:shadow transition-all flex items-center justify-center space-x-2">
<span>View Details</span>
<svg class="w-4 h-4 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"></path>
</svg>
</a>
</section>

<?php elseif ($state === 'open'): ?>
<section class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-card">
<div class="flex justify-between items-start mb-4">
<h2 class="text-base font-black text-slate-900 tracking-tight"><?= h($openAnnouncement['title']) ?></h2>
<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
OPEN
</span>
</div>
<p class="text-sm text-slate-600 mb-3 leading-relaxed"><?= h($openAnnouncement['message']) ?></p>
<div class="space-y-1.5 mb-4 text-xs">
<div class="flex items-center text-slate-700"><strong class="mr-2">Date Open:</strong> <?= h($openAnnouncement['opened']) ?></div>
<div class="flex items-center text-slate-700"><strong class="mr-2">Deadline:</strong> <?= h($openAnnouncement['deadline']) ?></div>
</div>
<a href="driver-renewal.php" class="block w-full py-3 px-4 bg-amber-400 hover:bg-amber-500 text-slate-900 font-extrabold text-sm rounded-xl shadow-sm text-center">Submit now!</a>
</section>

<?php elseif ($state === 'upcoming'): ?>
<section class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-card">
<div class="flex justify-between items-start mb-4">
<h2 class="text-base font-black text-slate-900 tracking-tight"><?= h($upcomingAnnouncement['title']) ?></h2>
<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200">
UPCOMING
</span>
</div>
<p class="text-sm text-slate-600 mb-3 leading-relaxed"><?= h($upcomingAnnouncement['message']) ?></p>
<div class="space-y-1.5 mb-3 text-xs">
<div class="flex items-center text-slate-700"><strong class="mr-2">Opens:</strong> <?= h($upcomingAnnouncement['opened']) ?></div>
<div class="flex items-center text-slate-700"><strong class="mr-2">Deadline:</strong> <?= h($upcomingAnnouncement['deadline']) ?></div>
</div>
<p class="text-xs text-slate-500 italic">Submission will open on the date above. Come back then to apply.</p>
</section>

<?php elseif ($state === 'approved'): ?>
<section class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-card">
<div class="flex justify-between items-start mb-4">
<div>
<h2 class="text-base font-black text-slate-900 tracking-tight">Current Status</h2>
<p class="text-xs font-semibold text-slate-500 mt-0.5 font-mono">Application No. <?= h($currentAppNo) ?></p>
</div>
<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
APPROVED
</span>
</div>
<p class="text-sm text-slate-600 mb-4">Your renewal for this cycle has been approved. There's nothing else to submit right now.</p>
<a href="driver-view-application.php" class="block w-full py-3 px-4 bg-amber-400 hover:bg-amber-500 text-slate-900 font-extrabold text-sm rounded-xl shadow-sm text-center">View Details</a>
</section>

<?php elseif ($state === 'rejected'): ?>
<section class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-card">
<div class="flex justify-between items-start mb-4">
<h2 class="text-base font-black text-slate-900 tracking-tight">Admin Remarks</h2>
<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-red-50 text-red-700 border border-red-200">
REJECTED
</span>
</div>
<p class="text-sm font-bold text-slate-900 mb-2"><?= h($currentDetail['reject_reason'] ?? 'Application Rejected') ?></p>
<p class="text-sm text-slate-600 mb-4"><?= h($currentDetail['reject_note'] ?: 'Please review and resubmit your application.') ?></p>
<a href="driver-renewal.php?start=1" class="block w-full py-3 px-4 bg-amber-400 hover:bg-amber-500 text-slate-900 font-extrabold text-sm rounded-xl shadow-sm text-center">Re-Apply</a>
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
<path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"></path>
</svg>
</div>
<p class="text-xs font-semibold text-slate-500">No renewal history yet.</p>
<p class="text-[11px] text-slate-400 mt-0.5">Prior records and expired permits will show here once completed.</p>
</div>
<?php else: ?>
<div class="space-y-2">
<?php foreach ($history as $item): ?>
<div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 border border-slate-200">
<div class="flex-1">
<div class="font-bold text-sm text-slate-900"><?= h($item['year']) ?></div>
<div class="text-xs text-slate-500 font-mono mt-0.5"><?= h($item['app_no']) ?></div>
<div class="text-[11px] text-slate-400 mt-0.5"><?= h($item['date']) ?></div>
</div>
<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider <?= $item['status'] === 'APPROVED' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' ?>">
<?= h($item['status']) ?>
</span>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>

</main>

<!-- Floating Chat Button -->
<div class="fixed bottom-20 right-4 md:right-[calc(50%-190px)] z-40">
<button aria-label="Open support chat" class="w-13 h-13 p-3.5 bg-amber-400 hover:bg-amber-500 active:scale-95 text-slate-900 rounded-full shadow-floating flex items-center justify-center transition-all duration-200 ring-4 ring-white" type="button" onclick="toggleChat()">
<svg class="w-6 h-6 fill-slate-950" viewBox="0 0 24 24">
<path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"></path>
</svg>
</button>
</div>

<!-- Bottom Navigation Bar -->
<nav class="fixed bottom-0 left-0 right-0 max-w-[420px] mx-auto bg-[#070b14] text-slate-400 border-t border-slate-800 z-50 px-2 py-2 shadow-nav">
<div class="grid grid-cols-4 items-center">
<a class="flex flex-col items-center justify-center py-1 group text-amber-400" href="driver-home.php">
<svg class="w-5 h-5 stroke-current stroke-2" fill="none" viewBox="0 0 24 24">
<path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" stroke-linecap="round" stroke-linejoin="round"></path>
</svg>
<span class="text-[11px] font-bold mt-1 tracking-tight">Home</span>
</a>

<a class="flex flex-col items-center justify-center py-1 group hover:text-slate-200 transition-colors" href="driver-renewal.php">
<svg class="w-5 h-5 stroke-current stroke-2 text-slate-400 group-hover:text-slate-200" fill="none" viewBox="0 0 24 24">
<path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round"></path>
</svg>
<span class="text-[11px] font-medium mt-1 tracking-tight">Submit</span>
</a>

<a class="flex flex-col items-center justify-center py-1 group hover:text-slate-200 transition-colors relative" href="driver-notifications.php">
<svg class="w-5 h-5 stroke-current stroke-2 text-slate-400 group-hover:text-slate-200" fill="none" viewBox="0 0 24 24">
<path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" stroke-linecap="round" stroke-linejoin="round"></path>
</svg>
<span class="text-[11px] font-medium mt-1 tracking-tight">Notifications</span>
</a>

<a class="flex flex-col items-center justify-center py-1 group hover:text-slate-200 transition-colors" href="driver-profile.php">
<svg class="w-5 h-5 stroke-current stroke-2 text-slate-400 group-hover:text-slate-200" fill="none" viewBox="0 0 24 24">
<path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-linecap="round" stroke-linejoin="round"></path>
</svg>
<span class="text-[11px] font-medium mt-1 tracking-tight">Profile</span>
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

// Chat functionality - reuse existing floating chat if present
function toggleChat() {
    const chatBtn = document.querySelector('.floating-chat-btn');
    if (chatBtn) {
        chatBtn.click();
    } else {
        alert('Chat feature will be available soon.');
    }
}
</script>

</body>
</html>