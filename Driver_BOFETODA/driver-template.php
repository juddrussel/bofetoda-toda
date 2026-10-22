<?php

const APP_CSS = <<<'CSS'
:root {
  --color-primary: #ffd02c;
  --color-bg: #f8fafc;
  --color-black: #000000;
  --color-white: #ffffff;
  --color-text: #000000;
  --color-text-muted: #585e65;
  --color-border: #d5d5d5;
  --color-border-light: #dcdcdc;
  --color-success: #00d015;
  --color-danger: #f41919;
  --color-warning: #ffb800;
  --radius-sm: 6px;
  --radius-md: 10px;
  --radius-lg: 50px;
  --shadow-card: 0px 1px 4px 0px rgba(0, 0, 0, 0.25);
  --shadow-nav: 0px 2px 4px 0px rgba(0, 0, 0, 0.25);
  --max-width: 100%;
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

html,
body {
  height: 100%;
}

body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  background: var(--color-bg);
  color: var(--color-text);
  -webkit-font-smoothing: antialiased;
}

@media (min-width:600px) {
  :root {
 
    --max-width: clamp(600px, 90vw, 720px);
  }

  body {
    background: #eef1f5;
  }
}

a {
  text-decoration: none;
  color: inherit;
}

button {
  font-family: inherit;
  cursor: pointer;
}

img {
  max-width: 100%;
  display: block;
}

.app-shell {
  max-width: var(--max-width);
  margin: 0 auto;
  min-height: 100vh;
  background: var(--color-bg);
  position: relative;
  padding-bottom: 100px;
}

.app-shell.no-scroll-pad {
  padding-bottom: 0;
}


.header-bar {
  height: 54px;
  background: var(--color-white);
  border-bottom: 1px solid var(--color-border-light);
  position: sticky;
  top: 0;
  z-index: 20;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: 0 20px;
}

.header-bar__meta {
  font-size: 12px;
  color: #9aa1b4;
  white-space: nowrap;
}

.topbar {
  padding: 23px 20px 14px;
}

.topbar .greeting {
  font-size: 15px;
  font-weight: 600;
  letter-spacing: 0.15px;
  margin-bottom: 4px;
}

.topbar .username {
  font-size: clamp(20px, 6vw, 26px);
  font-weight: 700;
  letter-spacing: 0.24px;
}

.topbar .page-title {
  font-size: 15px;
  font-weight: 600;
  letter-spacing: 0.15px;
  margin-bottom: 4px;
  color: var(--color-text-muted);
}

.topbar .app-number {
  font-size: 20px;
  font-weight: 700;
}

.topbar-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.topbar-action {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-muted);
  flex-shrink: 0;
}

.topbar-action:active {
  opacity: 0.7;
}

.icon-btn {
  background: none;
  border: none;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-text);
}

.content {
  padding: clamp(16px, 4.5vw, 24px);
}

.card {
  background: var(--color-white);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-card);
  padding: 20px;
  margin-bottom: 20px;
}

.card--flush {
  padding: 0;
  overflow: hidden;
}

.card--flush .card-body {
  padding: 20px;
}

.card-title {
  font-size: clamp(15px, 4.2vw, 17px);
  font-weight: 700;
  letter-spacing: 0.16px;
  margin-bottom: 14px;
}

.announcement-title {
  font-size: clamp(19px, 5.5vw, 24px);
  font-weight: 800;
  letter-spacing: 0.25px;
  line-height: 1.3;
  margin-bottom: 10px;
}

.announcement-text {
  font-size: 12px;
  line-height: 1.5;
  color: var(--color-text);
  margin-bottom: 14px;
  text-align: justify;
}

.announcement-meta {
  font-size: 12px;
  margin-bottom: 4px;
}

.announcement-meta strong {
  font-weight: 500;
  display: inline-block;
  width: 78px;
}

.announcement-meta span {
  font-weight: 300;
  color: var(--color-text-muted);
}

.card-cta {
  display: block;
  width: 100%;
  background: var(--color-primary);
  border: none;
  border-bottom-left-radius: var(--radius-md);
  border-bottom-right-radius: var(--radius-md);
  padding: 16px;
  font-size: 16px;
  font-weight: 700;
  letter-spacing: 0.16px;
  text-align: center;
  margin-top: 16px;
  margin-left: -20px;
  margin-right: -20px;
  margin-bottom: -20px;
  width: calc(100% + 40px);
}

.badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.08px;
  padding: 4px 14px;
}

.badge--open {
  background: var(--color-white);
  border: 0.8px solid #bdbdbd;
  color: var(--color-text);
}

.badge--approved {
  background: var(--color-success);
  color: var(--color-white);
  box-shadow: var(--shadow-card);
}

.badge--rejected {
  background: var(--color-danger);
  color: var(--color-white);
  box-shadow: var(--shadow-card);
}

.badge--pending {
  background: #9e9e9e;
  color: var(--color-white);
  box-shadow: var(--shadow-card);
}

.status-tag {
  position: absolute;
  top: 18px;
  right: 20px;
}

.history-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 0;
  border-bottom: 1px solid var(--color-border-light);
  font-size: 10px;
}

.history-item:last-child {
  border-bottom: none;
}

.history-year {
  font-weight: 500;
  width: 30px;
}

.history-app {
  font-weight: 500;
  flex: 1;
}

.history-date {
  font-weight: 500;
  color: var(--color-text);
  margin-right: 10px;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: var(--radius-sm);
  font-size: 16px;
  font-weight: 700;
  padding: 14px 20px;
  width: 100%;
  transition: opacity 0.15s ease;
}

.btn:active {
  opacity: 0.8;
}

.btn-primary {
  background: var(--color-black);
  color: var(--color-white);
  box-shadow: 1px 1px 1px 0px rgba(0, 0, 0, 0.25);
}

.btn-accent {
  background: var(--color-primary);
  color: var(--color-black);
}

.btn-outline {
  background: var(--color-white);
  color: var(--color-black);
  border: 1px solid var(--color-border);
}

.btn-danger {
  background: var(--color-black);
  color: var(--color-white);
}

.btn-sm {
  font-size: 14px;
  padding: 10px 16px;
  width: auto;
}

.btn-row {
  display: flex;
  gap: 12px;
}

.btn-row .btn {
  flex: 1;
}

.form-group {
  margin-bottom: 18px;
}

.form-label {
  display: block;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  margin-bottom: 8px;
  letter-spacing: 0.3px;
}

.input-wrap {
  position: relative;
}

.input-wrap .input-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--color-text-muted);
  display: flex;
  align-items: center;
}

.input-wrap .toggle-visibility {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: var(--color-text-muted);
  display: flex;
  align-items: center;
}

.form-control {
  width: 100%;
  background: var(--color-bg);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  padding: 14px 40px;
  font-size: 14px;
  color: var(--color-text);
  font-family: inherit;
}

.form-control::placeholder {
  color: var(--color-text-muted);
  font-weight: 300;
}

.form-control:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(255, 208, 44, 0.25);
}

.form-error {
  font-size: 12px;
  color: var(--color-danger);
  margin-top: -8px;
  margin-bottom: 16px;
}

.form-success {
  font-size: 12px;
  color: var(--color-success);
  margin-top: -8px;
  margin-bottom: 16px;
}

.form-control--plain {
  padding: 14px;
}

.form-group .field-error {
  display: none;
  color: var(--color-danger);
  font-size: 11px;
  font-weight: 600;
  margin-top: 6px;
}

.form-group.has-error .field-error {
  display: block;
}

.form-group.has-error .form-control {
  border-color: var(--color-danger);
  background: #fff5f5;
}

.field-readonly {
  background: var(--color-bg);
}

.field-readonly .form-control {
  color: var(--color-text-muted);
}

.upload-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.upload-tile {
  background: var(--color-bg);
  border-radius: var(--radius-sm);
  padding: 14px;
  text-align: center;
}

.upload-tile-label {
  font-size: 12px;
  font-weight: 600;
  margin-bottom: 10px;
}

.upload-tile-box {
  background: var(--color-white);
  border: 1.5px dashed var(--color-border);
  border-radius: var(--radius-sm);
  height: 100px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  cursor: pointer;
}

.upload-tile-box input[type="file"] {
  display: none;
}

.upload-tile-box span {
  font-size: 9px;
  color: var(--color-text-muted);
  width: 100%;
  padding: 0 8px;
  box-sizing: border-box;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.35;
  word-break: break-all;
}

.login-screen {
  min-height: 100vh;
  background: var(--color-primary);
  max-width: var(--max-width);
  margin: 0 auto;
  display: flex;
  flex-direction: column;
}

.login-hero {
  padding: 40px 20px 55px;
  text-align: center;
}

.login-hero h1 {
  font-size: clamp(22px, 6.5vw, 27px);
  font-weight: 900;
  letter-spacing: 0.25px;
  margin-bottom: 8px;
}

.login-hero p {
  font-size: 14px;
  font-weight: 600;
  line-height: 1.3;
   color: rgba(0, 0, 0, 0.72)
}

.login-panel {
  background: var(--color-white);
  border-top-left-radius: var(--radius-lg);
  border-top-right-radius: var(--radius-lg);
  flex: 1;
  padding: clamp(32px, 8vw, 45px) clamp(24px, 9vw, 39px);
  position: relative;
}

.login-logos {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 12px;
  margin-bottom: 18px;
}

.login-logo {
  width: 92px;
  height: 92px;
  object-fit: contain;
}

.login-panel-header {
  text-align: center;
  margin-bottom: 40px;
}

.login-panel-header h2 {
  font-size: 22px;
  font-weight: 700;
  letter-spacing: 0.22px;
  margin-bottom: 8px;
}

.login-panel-header p {
  font-size: 11px;
  font-weight: 400;
}

.bottom-nav {
  position: fixed;
  bottom: 18px;
  left: 50%;
  transform: translateX(-50%);
  width: calc(100% - 40px);
  max-width: calc(var(--max-width) - 40px);
  background: var(--color-black);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-nav);
  display: flex;
  align-items: center;
  justify-content: space-around;
  padding: 12px 0;
  z-index: 30;
}

.nav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  color: var(--color-white);
  font-size: 13px;
  font-weight: 500;
  letter-spacing: 0.26px;
  flex: 1;
  min-width: 0;
}

.nav-item svg {
  width: 20px;
  height: 20px;
}

.nav-item.active {
  color: var(--color-primary);
}

.progress-track {
  display: flex;
  align-items: center;
  gap: 6px;
  margin: 0 auto 4px;
  width: 94px;
}

.progress-track-bar {
  flex: 1;
  height: 3px;
  border-radius: 2px;
  background: var(--color-border);
  overflow: hidden;
}

.progress-track-bar span {
  display: block;
  height: 100%;
  background: var(--color-primary);
}

.progress-label {
  text-align: center;
  font-size: 12px;
  color: var(--color-text-muted);
  margin-bottom: 20px;
}

.stepper {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 6px 0;
  margin-bottom: 20px;
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  position: relative;
  flex: 1;
}

.step-circle {
  width: 30px;
  height: 30px;
  border-radius: 50%;
 
  background: #e0e0e0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  color: rgba(0, 0, 0, 0.55);
  z-index: 2;
}

.step.done .step-circle,
.step.current .step-circle {
  background: var(--color-primary);
  color: var(--color-black);
}

.step-line {
  position: absolute;
  top: 15px;
  left: -50%;
  width: 100%;
  height: 1px;
  background: rgba(0, 0, 0, 0.15);
  z-index: 1;
}

.step.done .step-line,
.step.current .step-line {
  background: var(--color-primary);
}

.step:first-child .step-line {
  display: none;
}

.step-label {
  font-size: 8px;
}

.step.current .step-label {
  color: var(--color-warning);
  font-weight: 600;
}

.step-label {
  font-size: 12px;
  color: var(--color-text);
}

.step-date {
  font-size: 9px;
  color: var(--color-text-muted);
  text-align: center;
  line-height: 1.3;
}

.timeline {
  padding: 0 4px;
}

.timeline-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 14px 0;
  border-bottom: 1px solid var(--color-border-light);
  font-size: 12px;
}

.timeline-row:last-child {
  border-bottom: none;
}

.timeline-left strong {
  display: block;
  font-weight: 500;
  margin-bottom: 2px;
}

.timeline-left span {
  color: var(--color-text-muted);
  font-size: 11px;
}

.timeline-right {
  text-align: right;
  color: var(--color-text-muted);
}

.notif-card {
  display: flex;
  gap: 14px;
  background: var(--color-white);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-card);
  padding: 20px;
  margin-bottom: 18px;
}

.nav-item { position: relative; }
.notif-badge {
  position: absolute;
  top: 2px;
  right: 14px;
  min-width: 16px;
  height: 16px;
  background: #e53935;
  color: #fff;
  font-size: 9px;
  font-weight: 800;
  border-radius: 999px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 3px;
  pointer-events: none;
  line-height: 1;
}

.notif-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--color-primary);
  margin-top: 6px;
  flex-shrink: 0;
}

.notif-title {
  font-size: 14px;
  font-weight: 700;
}

.notif-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 6px;
}

.notif-date {
  font-size: 11px;
  color: var(--color-text-muted);
  white-space: nowrap;
  flex-shrink: 0;
}

.notif-text {
  font-size: 12px;
  color: var(--color-text-muted);
  line-height: 1.5;
}

.profile-header {
  text-align: center;
  padding: 10px 10px;
}

.avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: var(--color-primary);
  margin: 0 auto 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-navy, #16233d);
  font-weight: 700;
  font-size: 22px;
  letter-spacing: .3px;
  border: 2px solid var(--color-white);
  box-shadow: var(--shadow-card);
  position: relative;
  cursor: pointer;
}

.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
}

.avatar-edit-badge {
  position: absolute;
  right: -3px;
  bottom: -3px;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: var(--color-black);
  color: var(--color-white);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid var(--color-white);
}

.profile-name {
  font-size: clamp(18px, 5vw, 21px);
  font-weight: 700;
  margin-bottom: 2px;
}

.profile-role {
  font-size: 13px;
  color: var(--color-text-muted);
}

.info-card {
  background: var(--color-white);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-card);
  padding: 20px;
  margin-bottom: 18px;
}

.info-card-title {
  font-size: 17px;
  font-weight: 700;
  margin-bottom: 16px;
}

.readonly-field {
  margin-bottom: 16px;
}

.readonly-field label {
  display: block;
  font-size: 11px;
  font-weight: 600;
  margin-bottom: 8px;
}

.readonly-field .value-box {
  background: var(--color-bg);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  padding: 10px 14px;
  font-size: 13px;
}

.readonly-field input.value-box {
  display: block;
  width: 100%;
  font-family: inherit;
  color: var(--color-text);
  outline: none;
  -webkit-appearance: none;
  appearance: none;
}

.readonly-field input.value-box:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(255, 208, 44, 0.25);
}

.readonly-field input.value-box[readonly] {
  color: var(--color-text-muted);
  background: #eef1f4;
}

.readonly-field label {
  cursor: pointer;
}

.readonly-field.has-error .value-box {
  border-color: var(--color-danger);
  background: #fff5f5;
}

.field-error {
  display: none;
  color: var(--color-danger);
  font-size: 11px;
  font-weight: 600;
  margin-top: 6px;
}

.readonly-field.has-error .field-error {
  display: block;
}

.upload-tile.has-error .upload-tile-box {
  border-color: var(--color-danger);
  background: #fff5f5;
}

.logout-link {
  display: block;
  text-align: center;
  color: var(--color-danger);
  font-weight: 600;
  font-size: 14px;
  margin-top: 24px;
  padding-bottom: 20px;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: none;
  align-items: flex-end;
  justify-content: center;
  z-index: 100;
  padding: 0;
  opacity: 0;
  transition: opacity .28s ease;
  pointer-events: none;
}

.modal-overlay.is-open {
  display: flex;
  pointer-events: auto;
}

.modal-overlay.is-visible {
  opacity: 1;
}

.modal-box {
  background: var(--color-primary);
  border-radius: 36px 36px 0 0;
  box-shadow: 0 -6px 24px rgba(0, 0, 0, 0.25);
  width: 100%;
  max-width: 100%;
  min-height: 364px;
  overflow-y: auto;
  padding: 48px 28px calc(28px + env(safe-area-inset-bottom));
  display: flex;
  flex-direction: column;
  justify-content: center;
  text-align: center;
  transform: translateY(48px) scale(0.98);
  transition: transform .34s cubic-bezier(.22, 1, .36, 1);
}

.modal-overlay.is-visible .modal-box {
  transform: translateY(0) scale(1);
}

@media (prefers-reduced-motion:reduce) {

  .modal-overlay,
  .modal-box {
    transition: none;
  }
}

@media (min-width:600px) {
  .modal-box {
    max-width: var(--max-width);
    border-radius: 36px 36px 20px 20px;
  }
}

.modal-title {
  font-size: 17px;
  font-weight: 700;
  line-height: 1.4;
  margin-bottom: 10px;
}

.modal-text {
  font-size: 13px;
  font-weight: 500;
  line-height: 1.5;
  margin-bottom: 26px;
}

.modal-attest-row {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  text-align: left;
  margin: -10px 0 22px;
  font-size: 12.5px;
  font-weight: 500;
  line-height: 1.4;
}

.modal-attest-row input[type="checkbox"] {
  width: 18px;
  height: 18px;
  margin-top: 1px;
  accent-color: var(--color-black);
  cursor: pointer;
  flex-shrink: 0;
}

.modal-attest-row label {
  cursor: pointer;
}

.btn:disabled,
.btn[disabled] {
  opacity: 0.45;
  cursor: not-allowed;
}

.text-center {
  text-align: center;
}

.mt-0 {
  margin-top: 0 !important;
}

.mb-0 {
  margin-bottom: 0 !important;
}

.mb-1 {
  margin-bottom: 8px;
}

.mb-2 {
  margin-bottom: 16px;
}

.text-muted {
  color: var(--color-text-muted);
}

.hidden {
  display: none !important;
}

@media (min-width:600px) {

  .app-shell,
  .login-screen {
    margin-top: 24px;
    margin-bottom: 24px;
    min-height: calc(100vh - 48px);
    border-radius: var(--radius-md);
    box-shadow: 0 0 40px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }


  .content {
    padding: 28px;
  }

  .card,
  .info-card {
    padding: 26px;
  }

  .card-title,
  .info-card-title {
    font-size: 19px;
  }

  .announcement-title {
    font-size: 27px;
  }

  .login-hero {
    padding: 52px 32px 64px;
  }

  .login-panel {
    padding: 52px 48px;
  }

  .login-logo {
    width: 104px;
    height: 104px;
  }

  .upload-grid {
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
  }
}

@media (max-width:359px) {
  .content {
    padding: 16px;
  }

  .upload-grid {
    gap: 10px;
  }
}
CSS;

const APP_JS = <<<'JS'
var modalStackLevel = 0;

function openModal(id) {
  var modal = document.getElementById(id);
  if (!modal) return;
  modalStackLevel += 1;
  modal.style.zIndex = 100 + modalStackLevel * 2;
  modal.classList.add('is-open');
  requestAnimationFrame(function () {
    requestAnimationFrame(function () { modal.classList.add('is-visible'); });
  });
}

function closeModal(modal) {
  if (typeof modal === 'string') modal = document.getElementById(modal);
  if (!modal || !modal.classList.contains('is-open')) return;
  modal.classList.remove('is-visible');
  var finish = function () {
    modal.classList.remove('is-open');
    modal.style.zIndex = '';
    modal.removeEventListener('transitionend', finish);
  };
  modal.addEventListener('transitionend', finish);
  setTimeout(finish, 350); // fallback in case transitionend never fires
}


window.Modal = { open: openModal, close: closeModal };

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.toggle-visibility').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var targetId = btn.getAttribute('data-target');
      var input = document.getElementById(targetId);
      if (!input) return;
      var isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      btn.classList.toggle('is-visible', isPassword);
    });
  });

  document.querySelectorAll('[data-modal-open]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      // Close the modal this button lives inside so it animates
      // out while the next one animates in on top of it 
      var parentModal = btn.closest('.modal-overlay');
      if (parentModal) closeModal(parentModal);
      openModal(btn.getAttribute('data-modal-open'));
    });
  });
  document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      closeModal(btn.closest('.modal-overlay'));
    });
  });
  document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) closeModal(overlay);
    });
  });
  document.querySelectorAll('.upload-tile-box input[type="file"]').forEach(function (input) {
    input.addEventListener('change', function () {
      var label = input.closest('.upload-tile-box').querySelector('span');
      if (label) {
        label.textContent = input.files.length ? input.files[0].name : 'Choose File';
      }
    });
  });
});
JS;

function render_header(array $opts = []) {
    $title          = $opts['title']          ?? 'Driver Portal';
    $topbar_mode    = $opts['topbar_mode']    ?? 'simple';
    $topbar_heading = $opts['topbar_heading'] ?? '';
    $topbar_sub     = $opts['topbar_sub']     ?? '';
    $topbar_action  = $opts['topbar_action']  ?? null;
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo h($title); ?> | BOFETODA</title>
<style><?php echo APP_CSS; ?></style>
</head>
<body>
<div class="app-shell">
<div class="header-bar">
  <span class="header-bar__meta">&copy; <?php echo date('Y'); ?> Bocaue TODA Inc.</span>
</div>
<?php if ($topbar_mode !== 'none'): ?>
<header class="topbar">
    <?php if ($topbar_mode === 'home'): ?>
        <div class="greeting">Good Day!</div>
        <div class="username"><?php echo h($topbar_heading); ?></div>
    <?php elseif ($topbar_mode === 'page'): ?>
        <div class="page-title"><?php echo h($topbar_heading); ?></div>
        <div class="app-number"><?php echo h($topbar_sub); ?></div>
    <?php elseif ($topbar_mode === 'simple'): ?>
        <div class="topbar-row">
            <div class="page-title" style="font-size:20px;font-weight:700;color:var(--color-text);margin-bottom:0;"><?php echo h($topbar_heading); ?></div>
            <?php if ($topbar_action): ?>
                <a href="#" class="topbar-action" data-modal-open="<?php echo h($topbar_action['modal']); ?>"><?php echo h($topbar_action['label']); ?></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</header>
<?php endif;
}

/**
 * Count unread notifications for the logged-in driver.
 * A notification is "unread" if its is_read column is 0/false.
 * Falls back to 0 if the column doesn't exist (older installs).
 */
function get_unread_notification_count(): int {
    $driver_id = $_SESSION['driver_id'] ?? null;
    if (!$driver_id) return 0;
    try {
        $pdo = get_pdo();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM notifications WHERE driver_id = :id AND is_read = 0'
        );
        $stmt->execute(['id' => $driver_id]);
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0; // graceful fallback if is_read column isn't there yet
    }
}

function render_bottom_nav($active_page) {
    ?>
<nav class="bottom-nav">
    <a href="driver-home.php" class="nav-item <?php echo $active_page === 'home' ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>
        <span>Home</span>
    </a>
    <a href="driver-renewal.php" class="nav-item <?php echo $active_page === 'submit' ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 15h6"/><path d="M9 11h2"/></svg>
        <span>Submit</span>
    </a>
    <a href="driver-notifications.php" class="nav-item <?php echo $active_page === 'notif' ? 'active' : ''; ?>">
        <?php $unreadCount = get_unread_notification_count(); ?>
        <?php if ($unreadCount > 0): ?>
            <span class="notif-badge"><?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?></span>
        <?php endif; ?>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        <span>Notifications</span>
    </a>
    <a href="driver-profile.php" class="nav-item <?php echo $active_page === 'profile' ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <span>Profile</span>
    </a>
</nav>
    <?php
}

function render_footer($active_page = '') {
    if ($active_page !== '') {
        render_bottom_nav($active_page);
    }
    ?>
</div><!-- /.app-shell -->
<script><?php echo APP_JS; ?></script>
</body>
</html>
    <?php
}