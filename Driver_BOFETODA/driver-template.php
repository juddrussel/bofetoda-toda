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
  border-radius: 16px;
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

.forgot-password-link {
  text-align: center;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid var(--color-border-light);
}

.forgot-password-link a {
  color: var(--color-text);
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
}

.forgot-password-link a:active {
  opacity: 0.7;
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

.detail-label {
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--color-text-muted);
  font-weight: 700;
  margin-bottom: 6px;
}

.detail-value {
  font-size: 14px;
  color: var(--color-text);
  font-weight: 600;
}

.notif-card {
  display: flex;
  gap: 14px;
  background: var(--color-white);
  border-radius: 16px;
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
  border-radius: 16px;
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

/* Floating Chat Box */
.floating-chat-btn {
  position: fixed;
  bottom: 100px;
  right: 20px;
  width: 56px;
  height: 56px;
  background: var(--color-primary);
  border-radius: 50%;
  border: none;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 50;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.floating-chat-btn:active {
  transform: scale(0.95);
}

.floating-chat-btn svg {
  width: 26px;
  height: 26px;
  color: var(--color-black);
}

.floating-chat-btn .chat-badge {
  position: absolute;
  top: 4px;
  right: 4px;
  min-width: 18px;
  height: 18px;
  background: #e53935;
  color: #fff;
  font-size: 10px;
  font-weight: 800;
  border-radius: 999px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
  border: 2px solid var(--color-bg);
}

.chat-window {
  position: fixed;
  bottom: 100px;
  right: 20px;
  width: calc(100% - 40px);
  max-width: 380px;
  height: 500px;
  max-height: calc(100vh - 180px);
  background: var(--color-white);
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25);
  display: none;
  flex-direction: column;
  z-index: 51;
  opacity: 0;
  transform: translateY(20px) scale(0.95);
  transition: opacity 0.25s ease, transform 0.25s ease;
}

.chat-window.is-open {
  display: flex;
}

.chat-window.is-visible {
  opacity: 1;
  transform: translateY(0) scale(1);
}

.chat-header {
  background: var(--color-primary);
  padding: 16px 20px;
  border-radius: 16px 16px 0 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.chat-header-title {
  font-size: 16px;
  font-weight: 700;
  color: var(--color-black);
}

.chat-header-sub {
  font-size: 11px;
  color: rgba(0, 0, 0, 0.65);
  margin-top: 2px;
}

.chat-close-btn {
  background: none;
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.15s ease;
}

.chat-close-btn:hover {
  background: rgba(0, 0, 0, 0.08);
}

.chat-close-btn svg {
  width: 20px;
  height: 20px;
  color: var(--color-black);
}

.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  background: #f5f5f5;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.chat-message {
  display: flex;
  gap: 10px;
  max-width: 85%;
}

.chat-message.me {
  align-self: flex-end;
  flex-direction: row-reverse;
}

.chat-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 700;
  color: var(--color-black);
  flex-shrink: 0;
}

.chat-message.admin .chat-avatar {
  background: #000;
  color: var(--color-primary);
}

.chat-message-content {
  flex: 1;
  min-width: 0;
}

.chat-sender-name {
  font-size: 11px;
  font-weight: 600;
  color: var(--color-text-muted);
  margin-bottom: 4px;
  padding: 0 4px;
}

.chat-message.me .chat-sender-name {
  text-align: right;
}

.chat-bubble {
  background: var(--color-white);
  border-radius: 12px;
  padding: 10px 14px;
  font-size: 13px;
  line-height: 1.5;
  word-wrap: break-word;
}

.chat-message.me .chat-bubble {
  background: var(--color-primary);
  color: var(--color-black);
}

.chat-message.admin .chat-bubble {
  background: #000;
  color: #fff;
}

.chat-bubble-image {
  max-width: 200px;
  border-radius: 8px;
  margin-top: 6px;
  cursor: pointer;
  display: block;
}

.chat-bubble-image:first-child {
  margin-top: 0;
}

.chat-bubble-text + .chat-bubble-image {
  margin-top: 8px;
}

.chat-time {
  font-size: 10px;
  color: var(--color-text-muted);
  margin-top: 4px;
  padding: 0 4px;
}

.chat-input-area {
  padding: 12px 16px;
  background: var(--color-white);
  border-radius: 0 0 16px 16px;
  border-top: 1px solid var(--color-border-light);
}

.chat-input-row {
  display: flex;
  gap: 8px;
  align-items: flex-end;
}

.chat-attach-btn {
  width: 40px;
  height: 40px;
  background: var(--color-bg);
  border: 1px solid var(--color-border);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
  transition: background 0.15s ease;
  position: relative;
}

.chat-attach-btn:hover {
  background: #e5e7eb;
}

.chat-attach-btn input[type="file"] {
  position: absolute;
  width: 100%;
  height: 100%;
  opacity: 0;
  cursor: pointer;
}

.chat-attach-btn svg {
  width: 20px;
  height: 20px;
  color: var(--color-text-muted);
}

.chat-attachment-preview {
  display: flex;
  gap: 8px;
  padding: 8px 0;
  margin-bottom: 8px;
  flex-wrap: wrap;
}

.chat-attachment-item {
  position: relative;
  width: 60px;
  height: 60px;
  border-radius: 8px;
  overflow: hidden;
  background: var(--color-bg);
  border: 1px solid var(--color-border);
}

.chat-attachment-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.chat-attachment-item-file {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.chat-attachment-remove {
  position: absolute;
  top: -6px;
  right: -6px;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #e53935;
  color: #fff;
  border: 2px solid #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 12px;
  font-weight: bold;
}

.chat-input {
  flex: 1;
  background: var(--color-bg);
  border: 1px solid var(--color-border);
  border-radius: 20px;
  padding: 10px 16px;
  font-size: 13px;
  font-family: inherit;
  resize: none;
  max-height: 100px;
  min-height: 40px;
}

.chat-input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(255, 208, 44, 0.2);
}

.chat-send-btn {
  width: 40px;
  height: 40px;
  background: var(--color-primary);
  border: none;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
  transition: transform 0.15s ease;
}

.chat-send-btn:active {
  transform: scale(0.95);
}

.chat-send-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.chat-send-btn svg {
  width: 20px;
  height: 20px;
  color: var(--color-black);
}

.chat-empty {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  text-align: center;
  color: var(--color-text-muted);
}

.chat-empty svg {
  width: 48px;
  height: 48px;
  margin-bottom: 12px;
  opacity: 0.4;
}

.chat-empty-text {
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 6px;
}

.chat-empty-sub {
  font-size: 12px;
}

@media (min-width: 600px) {
  .floating-chat-btn {
    right: calc((100% - var(--max-width)) / 2 + 20px);
  }
  
  .chat-window {
    right: calc((100% - var(--max-width)) / 2 + 20px);
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

  // Badge counter polling for messages
  function updateMessageBadge() {
    fetch('api-message-count.php')
      .then(function(response) { return response.json(); })
      .then(function(data) {
        if (data.success) {
          var badge = document.querySelector('.nav-item[href="driver-messages.php"] .notif-badge');
          if (data.count > 0) {
            var displayCount = data.count > 9 ? '9+' : data.count;
            if (badge) {
              badge.textContent = displayCount;
            } else {
              // Create badge if it doesn't exist
              var messagesLink = document.querySelector('.nav-item[href="driver-messages.php"]');
              if (messagesLink) {
                var newBadge = document.createElement('span');
                newBadge.className = 'notif-badge';
                newBadge.textContent = displayCount;
                messagesLink.insertBefore(newBadge, messagesLink.querySelector('svg'));
              }
            }
          } else if (badge) {
            // Remove badge if count is 0
            badge.remove();
          }
        }
      })
      .catch(function(error) {
        console.error('Error updating message badge:', error);
      });
  }

  // Update badge every 30 seconds
  setInterval(updateMessageBadge, 30000);
});
JS;

function render_header(array $opts = []) {
    $title          = $opts['title']          ?? 'Driver Portal';
    $topbar_mode    = $opts['topbar_mode']    ?? 'simple';
    $topbar_heading = $opts['topbar_heading'] ?? '';
    $topbar_sub     = $opts['topbar_sub']     ?? '';
    $topbar_action  = $opts['topbar_action']  ?? null;
    
    // Get VAPID public key for push notifications
    require_once __DIR__ . '/../Admin_BOFETODA/config.php';
    $vapidConfig = get_vapid_config();
    $vapidPublicKey = $vapidConfig['publicKey'] ?? '';
    
    // Run reminder scheduler (executes once per day)
    try {
        require_once __DIR__ . '/../Admin_BOFETODA/reminder-scheduler.php';
        check_and_send_reminders();
    } catch (Exception $e) {
        error_log('Reminder scheduler error in driver template: ' . $e->getMessage());
    }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo h($title); ?> | BOFETODA</title>
<?php if ($vapidPublicKey): ?>
<meta name="vapid-public-key" content="<?php echo h($vapidPublicKey); ?>">
<?php endif; ?>
<link rel="stylesheet" href="/assets/css/toast-notifications.css">
<!-- Tailwind CSS CDN for navigation bar -->
<script src="https://cdn.tailwindcss.com"></script>
<style><?php echo APP_CSS; ?></style>
</head>
<body>
<div class="app-shell">
<!-- Top Brand Bar (Tailwind) -->
<div class="px-5 pt-3 pb-2 flex items-center justify-end text-[11px] text-slate-500 font-medium tracking-tight bg-white/80 backdrop-blur-md sticky top-0 z-30 border-b border-slate-100">
  <div>&copy; <?php echo date('Y'); ?> Bocaue TODA Inc.</div>
</div>
<?php if ($topbar_mode !== 'none'): ?>
    <?php if ($topbar_mode === 'home'): ?>
        <!-- User Greeting Header (Tailwind) -->
        <section class="px-5 pt-3 pb-4">
            <div>
                <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Good Day!</p>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-tight mt-0.5">
                    <?php echo h($topbar_heading); ?>
                </h1>
                <div class="flex items-center gap-2 mt-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-100 text-emerald-700">
                        Active Member
                    </span>
                </div>
            </div>
        </section>
    <?php elseif ($topbar_mode === 'page'): ?>
        <!-- Page Header with Subtitle (Tailwind) -->
        <header class="px-5 pt-4 pb-3 bg-white border-b border-slate-100">
            <div>
                <h1 class="text-lg font-bold text-slate-900 leading-tight"><?php echo h($topbar_heading); ?></h1>
                <?php if ($topbar_sub): ?>
                    <p class="text-xs text-slate-500 font-medium mt-0.5"><?php echo h($topbar_sub); ?></p>
                <?php endif; ?>
            </div>
        </header>
    <?php elseif ($topbar_mode === 'simple'): ?>
        <!-- Simple Page Header (Tailwind) -->
        <header class="px-5 pt-4 pb-3 bg-white border-b border-slate-100">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-bold text-slate-900 leading-tight"><?php echo h($topbar_heading); ?></h1>
                </div>
                <?php if ($topbar_action): ?>
                    <a href="#" class="text-xs font-semibold text-amber-600 hover:text-amber-700 transition" data-modal-open="<?php echo h($topbar_action['modal']); ?>"><?php echo h($topbar_action['label']); ?></a>
                <?php endif; ?>
            </div>
        </header>
    <?php endif; ?>
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
    // Get notification count
    $notifCount = 0;
    try {
        $driver = current_driver();
        if ($driver) {
            $pdo = get_pdo();
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE driver_id = :driver_id AND is_read = 0');
            $stmt->execute(['driver_id' => $driver['driver_id']]);
            $notifCount = (int)$stmt->fetchColumn();
        }
    } catch (Exception $e) {}
    ?>
<!-- Bottom Navigation Bar -->
<nav class="fixed bottom-0 left-0 right-0 max-w-[420px] mx-auto bg-[#070b14] text-slate-400 border-t border-slate-800 z-50 px-2 py-2 shadow-nav">
  <div class="grid grid-cols-4 items-center">
    <!-- Tab: Home -->
    <a class="flex flex-col items-center justify-center py-1 group <?php echo $active_page === 'home' ? 'text-amber-400' : 'hover:text-slate-200'; ?> transition-colors" href="driver-home.php">
      <svg class="w-5 h-5 stroke-current stroke-2" fill="none" viewBox="0 0 24 24">
        <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <span class="text-[11px] <?php echo $active_page === 'home' ? 'font-bold' : 'font-medium'; ?> mt-1 tracking-tight">Home</span>
    </a>
    
    <!-- Tab: Submit -->
    <a class="flex flex-col items-center justify-center py-1 group <?php echo $active_page === 'submit' ? 'text-amber-400' : 'hover:text-slate-200'; ?> transition-colors" href="driver-renewal.php">
      <svg class="w-5 h-5 stroke-current stroke-2" fill="none" viewBox="0 0 24 24">
        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <span class="text-[11px] <?php echo $active_page === 'submit' ? 'font-bold' : 'font-medium'; ?> mt-1 tracking-tight">Submit</span>
    </a>
    
    <!-- Tab: Notifications -->
    <a class="flex flex-col items-center justify-center py-1 group <?php echo $active_page === 'notif' ? 'text-amber-400' : 'hover:text-slate-200'; ?> transition-colors relative" href="driver-notifications.php">
      <div class="relative">
        <svg class="w-5 h-5 stroke-current stroke-2" fill="none" viewBox="0 0 24 24">
          <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <?php if ($notifCount > 0): ?>
        <span class="absolute -top-1 -right-1 w-2 h-2 rounded-full bg-amber-400 ring-2 ring-slate-900"></span>
        <?php endif; ?>
      </div>
      <span class="text-[11px] <?php echo $active_page === 'notif' ? 'font-bold' : 'font-medium'; ?> mt-1 tracking-tight">Notifications</span>
    </a>
    
    <!-- Tab: Profile -->
    <a class="flex flex-col items-center justify-center py-1 group <?php echo $active_page === 'profile' ? 'text-amber-400' : 'hover:text-slate-200'; ?> transition-colors" href="driver-profile.php">
      <svg class="w-5 h-5 stroke-current stroke-2" fill="none" viewBox="0 0 24 24">
        <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <span class="text-[11px] <?php echo $active_page === 'profile' ? 'font-bold' : 'font-medium'; ?> mt-1 tracking-tight">Profile</span>
    </a>
  </div>
</nav>
    <?php
}

function render_floating_chat() {
    $driver = current_driver();
    if (!$driver) return;
    
    $driver_id = $driver['driver_id'];
    $unrepliedCount = get_my_unread_reply_count($driver_id);
    ?>
<!-- Floating Chat Button -->
<button type="button" class="floating-chat-btn" id="chatToggleBtn" aria-label="Open chat">
    <?php if ($unrepliedCount > 0): ?>
        <span class="chat-badge"><?php echo $unrepliedCount > 9 ? '9+' : $unrepliedCount; ?></span>
    <?php endif; ?>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
    </svg>
</button>

<!-- Chat Window -->
<div class="chat-window" id="chatWindow">
    <div class="chat-header">
        <div>
            <div class="chat-header-title">Message Admin</div>
            <div class="chat-header-sub">Bocaue Toda Inc. Support</div>
        </div>
        <button type="button" class="chat-close-btn" id="chatCloseBtn" aria-label="Close chat">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>
    
    <div class="chat-messages" id="chatMessages">
        <div class="chat-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <div class="chat-empty-text">Loading messages...</div>
        </div>
    </div>
    
    <div class="chat-input-area">
        <div class="chat-attachment-preview" id="chatAttachmentPreview" style="display:none;"></div>
        <div class="chat-input-row">
            <button type="button" class="chat-attach-btn" title="Attach file">
                <input type="file" id="chatFileInput" accept="image/*,.pdf,.doc,.docx" multiple>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
                </svg>
            </button>
            <textarea class="chat-input" id="chatInput" placeholder="Type your message..." rows="1"></textarea>
            <button type="button" class="chat-send-btn" id="chatSendBtn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"/>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    var chatToggleBtn = document.getElementById('chatToggleBtn');
    var chatWindow = document.getElementById('chatWindow');
    var chatCloseBtn = document.getElementById('chatCloseBtn');
    var chatMessages = document.getElementById('chatMessages');
    var chatInput = document.getElementById('chatInput');
    var chatSendBtn = document.getElementById('chatSendBtn');
    var chatFileInput = document.getElementById('chatFileInput');
    var chatAttachmentPreview = document.getElementById('chatAttachmentPreview');
    var driverId = <?php echo json_encode($driver_id); ?>;
    var selectedFiles = [];
    
    function openChat() {
        chatWindow.classList.add('is-open');
        requestAnimationFrame(function() {
            requestAnimationFrame(function() {
                chatWindow.classList.add('is-visible');
            });
        });
        loadMessages();
    }
    
    function closeChat() {
        chatWindow.classList.remove('is-visible');
        setTimeout(function() {
            chatWindow.classList.remove('is-open');
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
        var driverName = '<?php echo addslashes($driver["name"]); ?>';
        
        messages.forEach(function(msg) {
            var isMe = msg.sender_type === 'driver';
            var time = new Date(msg.sent_at).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Manila' });
            var initials = isMe ? '<?php echo strtoupper(substr($driver["name"], 0, 1)); ?>' : 'A';
            var senderName = isMe ? driverName : 'Admin';
            
            html += '<div class="chat-message ' + (isMe ? 'me' : 'admin') + '">';
            html += '  <div class="chat-avatar">' + initials + '</div>';
            html += '  <div class="chat-message-content">';
            html += '    <div class="chat-sender-name">' + escapeHtml(senderName) + '</div>';
            html += '    <div class="chat-bubble">';
            
            // Add text if exists
            if (msg.message && msg.message.trim()) {
                html += '<div class="chat-bubble-text">' + escapeHtml(msg.message) + '</div>';
            }
            
            // Add attachments if they exist
            if (msg.attachment_list && msg.attachment_list.length > 0) {
                msg.attachment_list.forEach(function(att) {
                    if (att.type && att.type.startsWith('image/')) {
                        // Show image using attachment ID
                        html += '<img src="download-attachment.php?id=' + att.id + '" class="chat-bubble-image" alt="' + escapeHtml(att.name) + '">';
                    } else {
                        // Show file download link
                        html += '<a href="download-attachment.php?id=' + att.id + '" target="_blank" style="color:inherit;text-decoration:underline;display:block;margin-top:4px;">📎 ' + escapeHtml(att.name) + '</a>';
                    }
                });
            }
            
            html += '    </div>';
            html += '    <div class="chat-time">' + time + '</div>';
            html += '  </div>';
            html += '</div>';
        });
        
        chatMessages.innerHTML = html;
        scrollToBottom();
    }
    
    function showEmpty(text) {
        chatMessages.innerHTML = '<div class="chat-empty">' +
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
            '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>' +
            '</svg>' +
            '<div class="chat-empty-text">' + (text || 'No messages yet') + '</div>' +
            '<div class="chat-empty-sub">Start a conversation with the admin</div>' +
            '</div>';
    }
    
    function updateAttachmentPreview() {
        if (selectedFiles.length === 0) {
            chatAttachmentPreview.style.display = 'none';
            chatAttachmentPreview.innerHTML = '';
            return;
        }
        
        chatAttachmentPreview.style.display = 'flex';
        var html = '';
        selectedFiles.forEach(function(file, index) {
            var isImage = file.type.startsWith('image/');
            html += '<div class="chat-attachment-item">';
            if (isImage) {
                html += '<img src="' + URL.createObjectURL(file) + '" alt="' + escapeHtml(file.name) + '">';
            } else {
                html += '<div class="chat-attachment-item-file">📄</div>';
            }
            html += '<div class="chat-attachment-remove" data-index="' + index + '">×</div>';
            html += '</div>';
        });
        chatAttachmentPreview.innerHTML = html;
        
        // Add remove listeners
        chatAttachmentPreview.querySelectorAll('.chat-attachment-remove').forEach(function(btn) {
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
        
        // Add files
        selectedFiles.forEach(function(file, index) {
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
                alert(data.message || 'Failed to send message');
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
    
    // File input handler
    chatFileInput.addEventListener('change', function() {
        var files = Array.from(chatFileInput.files);
        selectedFiles = selectedFiles.concat(files);
        chatFileInput.value = ''; // Reset input
        updateAttachmentPreview();
    });
    
    // Auto-resize textarea
    chatInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 100) + 'px';
    });
    
    // Send on Enter (but allow Shift+Enter for new line)
    chatInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    
    chatToggleBtn.addEventListener('click', openChat);
    chatCloseBtn.addEventListener('click', closeChat);
    chatSendBtn.addEventListener('click', sendMessage);
    
    // Auto-refresh messages every 10 seconds when chat is open
    setInterval(function() {
        if (chatWindow.classList.contains('is-open')) {
            loadMessages();
        }
    }, 10000);
})();
</script>
    <?php
}

function render_footer($active_page = '') {
    $showChat = in_array($active_page, ['home', 'notif', 'profile'], true);
    
    if ($active_page !== '') {
        render_bottom_nav($active_page);
    }
    
    // Render new Tailwind chat widget on specific pages
    if ($showChat) {
        $driver = current_driver();
        if ($driver) {
            $unrepliedCount = get_my_unread_reply_count($driver['driver_id']);
            $driverInitial = strtoupper(substr($driver['name'], 0, 1));
            ?>
  <!-- Floating Chat Button -->
  <button type="button" class="fixed bottom-20 right-4 md:right-[calc(50%-190px)] w-14 h-14 bg-amber-400 hover:bg-amber-500 active:scale-95 text-slate-900 rounded-full shadow-floating flex items-center justify-center transition-all duration-200 z-40" id="chatToggleBtn" aria-label="Open chat" style="box-shadow: 0 10px 25px -5px rgba(245, 158, 11, 0.35);">
    <?php if ($unrepliedCount > 0): ?>
    <span class="absolute -top-1 -right-1 min-w-[20px] h-5 bg-red-600 text-white text-[10px] font-extrabold rounded-full flex items-center justify-center px-1.5 border-2 border-white"><?= $unrepliedCount > 9 ? '9+' : $unrepliedCount ?></span>
    <?php endif; ?>
    <svg class="w-6 h-6 fill-slate-950" viewBox="0 0 24 24">
      <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
    </svg>
  </button>

  <!-- Chat Window -->
  <div class="fixed bottom-20 right-4 md:right-[calc(50%-190px)] w-[calc(100%-2rem)] max-w-[380px] h-[500px] max-h-[calc(100vh-180px)] bg-white rounded-2xl shadow-2xl hidden flex-col z-50 opacity-0 translate-y-5 scale-95 transition-all duration-250" id="chatWindow">
    <div class="bg-amber-400 px-5 py-4 rounded-t-2xl flex items-center justify-between">
      <div>
        <div class="text-base font-bold text-slate-900">Message Admin</div>
        <div class="text-[11px] text-slate-700">Bocaue Toda Inc. Support</div>
      </div>
      <button type="button" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-black/10 transition-colors" id="chatCloseBtn" aria-label="Close chat">
        <svg class="w-5 h-5 text-slate-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>
    
    <div class="flex-1 overflow-y-auto p-4 bg-slate-50 flex flex-col gap-3" id="chatMessages" style="scrollbar-width: none; -ms-overflow-style: none;">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
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
  var driverName = <?= json_encode($driver['name']) ?>;
  var driverInitial = <?= json_encode($driverInitial) ?>;
  var selectedFiles = [];
  
  // Exit if elements not found
  if (!chatToggleBtn || !chatWindow) {
    console.error('Chat elements not found');
    return;
  }
  
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
    messages.forEach(function(msg) {
      var isMe = msg.sender_type === 'driver';
      var time = new Date(msg.sent_at).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Manila' });
      var initials, senderName;
      
      if (isMe) {
        initials = driverInitial;
        senderName = driverName;
      } else {
        if (msg.officer_name) {
          senderName = msg.officer_name;
          initials = msg.officer_name.substring(0, 1).toUpperCase();
        } else {
          senderName = 'Admin';
          initials = 'A';
        }
      }
      
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
            html += '<a href="download-attachment.php?id=' + att.id + '" target="_blank" class="block mt-2 text-blue-600 underline">📎 ' + escapeHtml(att.name) + '</a>';
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
      html += '<div class="relative w-[60px] h-[60px] rounded-lg overflow-hidden bg-slate-100 border border-slate-300">';
      if (isImage) {
        html += '<img src="' + URL.createObjectURL(file) + '" class="w-full h-full object-cover" alt="' + escapeHtml(file.name) + '">';
      } else {
        html += '<div class="w-full h-full flex items-center justify-center text-2xl">📄</div>';
      }
      html += '<button type="button" class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-red-600 text-white flex items-center justify-center text-xs font-bold border-2 border-white cursor-pointer" data-index="' + index + '">×</button>';
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
  
  if (chatToggleBtn) chatToggleBtn.addEventListener('click', openChat);
  if (chatCloseBtn) chatCloseBtn.addEventListener('click', closeChat);
  if (chatSendBtn) chatSendBtn.addEventListener('click', sendMessage);
  
  if (chatInput) {
    chatInput.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
      }
    });
    
    chatInput.addEventListener('input', function() {
      this.style.height = 'auto';
      this.style.height = Math.min(this.scrollHeight, 96) + 'px';
    });
  }
  
  if (chatFileInput) {
    chatFileInput.addEventListener('change', function() {
      Array.from(chatFileInput.files).forEach(function(file) {
        selectedFiles.push(file);
      });
      chatFileInput.value = '';
      updateAttachmentPreview();
    });
  }
})();
});
</script>
            <?php
        }
    }
    
    // Include service worker registration for push notifications
    require_once __DIR__ . '/../Admin_BOFETODA/config.php';
    $pushEnabled = is_push_enabled();
    
    // Debug output
    $vapidDebug = [
        'pushEnabled' => $pushEnabled,
        'publicKeyExists' => getenv('VAPID_PUBLIC_KEY') ? 'YES' : 'NO',
        'privateKeyExists' => getenv('VAPID_PRIVATE_KEY') ? 'YES' : 'NO',
        'subjectExists' => getenv('VAPID_SUBJECT') ? 'YES' : 'NO'
    ];
    ?>
</div><!-- /.app-shell -->
<script><?php echo APP_JS; ?></script>
<script src="/assets/js/toast-notifications.js"></script>
<script>
// VAPID Debug Info
console.log('[VAPID Debug]', <?php echo json_encode($vapidDebug); ?>);
</script>
<script src="/assets/js/notification-settings.js"></script>
<?php if ($pushEnabled): ?>
<script src="/register-sw.js"></script>
<script src="/assets/js/notification-banner.js"></script>
<?php endif; ?>
</body>
</html>
    <?php
}