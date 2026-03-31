<?php
require_once '../php/auth.php';
requireLogin();
$pageTitle  = 'Messages';
$currentPage = 'chat';
$openConvId  = (int)($_GET['conv'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?> – ADBU MentorConnect</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../css/styles.css">
  <style>
    .page-content { padding: 0 !important; }
    body { overflow: hidden; }

    /* File preview bar */
    .file-preview-bar {
      display: none; align-items: center; gap: 10px;
      padding: 8px 16px; background: var(--accent-light);
      border-top: 1px solid var(--border); flex-wrap: wrap;
    }
    .file-preview-bar.show { display: flex; }
    .file-chip {
      display: flex; align-items: center; gap: 8px;
      background: var(--card); border: 1px solid var(--border);
      border-radius: 20px; padding: 5px 12px; font-size: 13px;
    }
    .file-chip .remove-file { cursor: pointer; color: var(--danger); font-size: 14px; }

    /* File message bubble */
    .file-bubble {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 14px; border-radius: 14px;
      background: var(--card); border: 1px solid var(--border);
      max-width: 280px; cursor: pointer; transition: var(--transition);
      text-decoration: none;
    }
    .file-bubble:hover { border-color: var(--accent); background: var(--accent-light); }
    .file-bubble.mine { background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.3); }
    .file-bubble.mine:hover { background: rgba(255,255,255,0.25); }
    .file-icon-bubble {
      width: 38px; height: 38px; border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 18px; flex-shrink: 0;
    }
    .file-meta { min-width: 0; flex: 1; }
    .file-meta .fname { font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .file-meta .fsize { font-size: 11px; opacity: 0.7; }

    /* Upload progress */
    .upload-progress {
      display: none; align-items: center; gap: 10px;
      padding: 8px 16px; background: var(--accent-light);
      border-top: 1px solid var(--border);
    }
    .upload-progress.show { display: flex; }
    .progress-bar-wrap { flex: 1; height: 6px; background: var(--border); border-radius: 99px; overflow: hidden; }
    .progress-bar-fill { height: 100%; background: var(--accent); border-radius: 99px; transition: width 0.3s; }

    @media (max-width: 991px) {
      body { overflow: auto; }
      .chat-layout { height: auto; flex-direction: column; }
      .chat-list-panel { width: 100%; height: auto; max-height: none; border-right: none; border-bottom: 1px solid var(--border); }
      .chat-window { display: none; }
      .chat-window.mobile-active { display: flex; position: fixed; inset: 0; top: 0; z-index: 300; flex-direction: column; }
    }
  </style>
</head>
<body>
<div class="app-layout">
  <?php include 'sidebar_nav.php'; ?>

  <div class="chat-layout">

    <!-- LEFT: Conversation List -->
    <div class="chat-list-panel" id="chatListPanel">
      <div class="chat-list-header">
        <h2><i class="bi bi-chat-dots me-2" style="color:var(--accent)"></i>Messages</h2>
        <div class="chat-list-search-wrap">
          <i class="bi bi-search"></i>
          <input type="text" class="chat-list-search" id="convSearch" placeholder="Search conversations...">
        </div>
      </div>
      <div class="chat-list" id="chatList">
        <div style="padding:20px;text-align:center;color:var(--text-muted)">
          <div class="loading-spinner" style="border-color:var(--border);border-top-color:var(--accent);width:24px;height:24px;margin:0 auto"></div>
        </div>
      </div>
    </div>

    <!-- RIGHT: Chat Window -->
    <div class="chat-window" id="chatWindow">
      <!-- Empty state -->
      <div class="chat-window-empty" id="chatEmpty">
        <i class="bi bi-chat-heart"></i>
        <h3 style="font-size:16px;font-weight:700;color:var(--text)">Select a conversation</h3>
        <p style="font-size:14px">Choose a conversation from the left panel to start messaging.</p>
      </div>

      <!-- Active chat -->
      <div id="chatActive" style="display:none;flex-direction:column;height:100%">
        <!-- Header -->
        <div class="chat-header">
          <button class="chat-back-btn btn-icon me-2" id="chatBackBtn"><i class="bi bi-arrow-left"></i></button>
          <div style="position:relative">
            <img id="chatPartnerAvatar" src="" class="avatar avatar-md" alt="">
            <span id="chatOnlineDot" class="online-dot offline-dot" style="position:absolute;bottom:2px;right:2px;width:10px;height:10px;border:2px solid var(--card)"></span>
          </div>
          <div class="chat-header-info ms-2">
            <div class="chat-header-name" id="chatPartnerName">—</div>
            <div class="chat-header-status">
              <span id="chatOnlineStatus" class="online-dot offline-dot"></span>
              <span id="chatStatusText">Offline</span>
            </div>
          </div>
        </div>

        <!-- Messages Area -->
        <div class="chat-messages" id="chatMessages"></div>

        <!-- File preview bar (shown when file selected) -->
        <div class="file-preview-bar" id="filePreviewBar">
          <i class="bi bi-paperclip" style="color:var(--accent)"></i>
          <div class="file-chip" id="fileChip">
            <i class="bi bi-file-earmark" style="color:var(--accent)"></i>
            <span id="fileChipName">filename.pdf</span>
            <span id="fileChipSize" style="color:var(--text-muted);font-size:11px"></span>
            <i class="bi bi-x-circle-fill remove-file" id="clearFileBtn"></i>
          </div>
          <span style="font-size:12px;color:var(--text-muted)">Press Send to upload</span>
        </div>

        <!-- Upload progress bar -->
        <div class="upload-progress" id="uploadProgress">
          <i class="bi bi-cloud-upload" style="color:var(--accent)"></i>
          <span style="font-size:13px;color:var(--text-muted)" id="uploadLabel">Uploading...</span>
          <div class="progress-bar-wrap">
            <div class="progress-bar-fill" id="progressFill" style="width:0%"></div>
          </div>
          <span style="font-size:12px;color:var(--text-muted)" id="uploadPct">0%</span>
        </div>

        <!-- Input Bar -->
        <div class="chat-input-area">
          <label class="btn-icon" for="fileInput" title="Attach file" style="cursor:pointer;margin:0">
            <i class="bi bi-paperclip"></i>
          </label>
          <input type="file" id="fileInput" style="display:none" accept="*/*">
          <textarea class="chat-input" id="chatInput" placeholder="Type a message... (Enter to send, Shift+Enter for new line)" rows="1"></textarea>
          <button class="chat-send-btn" id="chatSendBtn" title="Send">
            <i class="bi bi-send-fill"></i>
          </button>
        </div>
      </div>
    </div>

  </div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/app.js"></script>
<script>
let conversations = [];
let activeConvId  = <?= $openConvId ?>;
let pollInterval  = null;
let selectedFile  = null;

// ── File type helpers 
const fileIcons = {
  pdf:  { icon: 'bi-file-earmark-pdf', color: '#EF4444', bg: '#FEE2E2' },
  doc:  { icon: 'bi-file-earmark-word', color: '#2563EB', bg: '#DBEAFE' },
  docx: { icon: 'bi-file-earmark-word', color: '#2563EB', bg: '#DBEAFE' },
  xls:  { icon: 'bi-file-earmark-excel', color: '#059669', bg: '#D1FAE5' },
  xlsx: { icon: 'bi-file-earmark-excel', color: '#059669', bg: '#D1FAE5' },
  ppt:  { icon: 'bi-file-earmark-slides', color: '#D97706', bg: '#FEF3C7' },
  pptx: { icon: 'bi-file-earmark-slides', color: '#D97706', bg: '#FEF3C7' },
  jpg:  { icon: 'bi-file-earmark-image', color: '#7C3AED', bg: '#EDE9FE' },
  jpeg: { icon: 'bi-file-earmark-image', color: '#7C3AED', bg: '#EDE9FE' },
  png:  { icon: 'bi-file-earmark-image', color: '#7C3AED', bg: '#EDE9FE' },
  gif:  { icon: 'bi-file-earmark-image', color: '#7C3AED', bg: '#EDE9FE' },
  zip:  { icon: 'bi-file-earmark-zip', color: '#92400E', bg: '#FEF3C7' },
  txt:  { icon: 'bi-file-earmark-text', color: '#6B7280', bg: '#F3F4F6' },
};
function getFileIcon(name) {
  const ext = (name || '').split('.').pop().toLowerCase();
  return fileIcons[ext] || { icon: 'bi-file-earmark', color: '#6B7280', bg: '#F3F4F6' };
}
function formatBytes(bytes) {
  if (!bytes) return '';
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024*1024) return (bytes/1024).toFixed(1) + ' KB';
  return (bytes/1024/1024).toFixed(1) + ' MB';
}

// ── Conversation list 
async function loadConversations() {
  const res = await API.get('../php/messages.php?action=get_conversations');
  if (res.success) {
    conversations = res.conversations;
    renderConversationList(conversations);
    if (activeConvId) openConversation(activeConvId);
  }
}

function renderConversationList(convs) {
  const list = document.getElementById('chatList');
  if (!convs.length) {
    list.innerHTML = `<div class="empty-state" style="padding:40px 20px"><i class="bi bi-chat-x"></i><p>No conversations yet.</p></div>`;
    return;
  }
  list.innerHTML = convs.map(c => `
    <div class="chat-list-item ${c.conversation_id == activeConvId ? 'active' : ''}"
         data-conv-id="${c.conversation_id}" onclick="openConversation(${c.conversation_id})">
      <div style="position:relative;flex-shrink:0">
        <img src="${c.avatar}" class="avatar avatar-md" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(c.partner_name)}&background=4F46E5&color=fff'">
        <span class="${c.is_online ? 'online-dot' : 'online-dot offline-dot'}" style="position:absolute;bottom:0;right:0;width:10px;height:10px;border:2px solid var(--card)"></span>
      </div>
      <div class="chat-item-info">
        <div class="chat-item-name">${c.partner_name}</div>
        <div class="chat-item-preview">${c.last_message ? c.last_message.substring(0,40) + (c.last_message.length>40?'…':'') : 'No messages yet'}</div>
      </div>
      <div class="chat-item-meta">
        <span class="chat-time">${c.last_message_time ? formatTime(c.last_message_time) : ''}</span>
        ${c.unread_count > 0 ? `<span class="unread-badge">${c.unread_count}</span>` : ''}
      </div>
    </div>`).join('');
}

async function openConversation(convId) {
  activeConvId = convId;
  const conv = conversations.find(c => c.conversation_id == convId);
  document.querySelectorAll('.chat-list-item').forEach(el => el.classList.toggle('active', el.dataset.convId == convId));

  if (conv) {
    document.getElementById('chatPartnerAvatar').src = conv.avatar;
    document.getElementById('chatPartnerName').textContent = conv.partner_name;
    const on = !!conv.is_online;
    document.getElementById('chatOnlineDot').className     = `online-dot ${on ? '' : 'offline-dot'}`;
    document.getElementById('chatOnlineStatus').className  = `online-dot ${on ? '' : 'offline-dot'}`;
    document.getElementById('chatStatusText').textContent  = on ? 'Online' : 'Offline';
  }

  document.getElementById('chatEmpty').style.display  = 'none';
  document.getElementById('chatActive').style.display = 'flex';

  if (window.innerWidth < 992) {
    document.getElementById('chatWindow').classList.add('mobile-active');
    document.getElementById('chatListPanel').style.display = 'none';
  }

  fetch('../php/notifications.php?action=mark_messages_read', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({conversation_id: convId})
  });

  await loadMessages(convId);
  if (pollInterval) clearInterval(pollInterval);
  pollInterval = setInterval(() => loadMessages(convId, true), 3000);
}

// ── Messages 
async function loadMessages(convId, silent = false) {
  const res = await API.get(`../php/messages.php?action=get_messages&conversation_id=${convId}`);
  if (!res.success) return;
  const container = document.getElementById('chatMessages');
  const wasAtBottom = container.scrollHeight - container.clientHeight <= container.scrollTop + 60;
  if (!silent) {
    container.innerHTML = res.messages.length
      ? res.messages.map(m => renderMessage(m)).join('')
      : `<div class="empty-state" style="flex:1"><i class="bi bi-chat"></i><p>No messages yet. Say hello! 👋</p></div>`;
  } else {
   
    const existing = container.querySelectorAll('[data-msg-id]');
    const existingIds = new Set([...existing].map(el => el.dataset.msgId));
    const newMsgs = res.messages.filter(m => !existingIds.has(String(m.id)));
    if (newMsgs.length) newMsgs.forEach(m => container.insertAdjacentHTML('beforeend', renderMessage(m)));
  }
  if (!silent || wasAtBottom) container.scrollTop = container.scrollHeight;
  document.querySelector(`[data-conv-id="${convId}"] .unread-badge`)?.remove();
}

function renderMessage(m) {
  const isMine = m.is_mine;
  const avatar = `<img src="${m.avatar}" class="avatar avatar-sm" style="align-self:flex-end;flex-shrink:0" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(m.sender_name)}&background=4F46E5&color=fff'">`;
  let content;
  if (m.message_type === 'file' || m.attachment_path) {
    const fi = getFileIcon(m.attachment_name || '');
    content = `
      <a href="../php/messages.php?action=download&msg_id=${m.id}" class="file-bubble ${isMine ? 'mine' : ''}" style="color:inherit;text-decoration:none" download>
        <div class="file-icon-bubble" style="background:${fi.bg};color:${fi.color}">
          <i class="bi ${fi.icon}"></i>
        </div>
        <div class="file-meta">
          <div class="fname" style="color:${isMine ? 'white' : 'var(--text)'}">${escapeHtml(m.attachment_name || 'File')}</div>
          <div class="fsize">${formatBytes(m.attachment_size)} • Click to download</div>
        </div>
        <i class="bi bi-download" style="font-size:14px;opacity:0.6;flex-shrink:0"></i>
      </a>`;
  } else {
    content = `<div class="message-bubble ${isMine ? 'mine' : ''}">${escapeHtml(m.message_text || '')}</div>`;
  }
  return `
    <div class="message-group ${isMine ? 'mine' : ''}" data-msg-id="${m.id}" style="max-width:72%;display:flex;gap:8px;align-self:${isMine ? 'flex-end' : 'flex-start'}">
      ${!isMine ? avatar : ''}
      <div style="max-width:100%">
        ${!isMine ? `<div class="message-sender-name">${m.sender_name}</div>` : ''}
        ${content}
        <div class="message-time">${formatDateTime(m.sent_at)}</div>
      </div>
      ${isMine ? avatar : ''}
    </div>`;
}

function escapeHtml(text) {
  const d = document.createElement('div');
  d.textContent = text;
  return d.innerHTML;
}

// ── File selection 
document.getElementById('fileInput').addEventListener('change', e => {
  const file = e.target.files[0];
  if (!file) return;
  if (file.size > 20 * 1024 * 1024) { Toast.error('File too large. Max 20MB.'); e.target.value = ''; return; }
  selectedFile = file;
  document.getElementById('fileChipName').textContent = file.name;
  document.getElementById('fileChipSize').textContent = formatBytes(file.size);
  const fi = getFileIcon(file.name);
  document.getElementById('fileChip').querySelector('i').className = `bi ${fi.icon}`;
  document.getElementById('fileChip').querySelector('i').style.color = fi.color;
  document.getElementById('filePreviewBar').classList.add('show');
  document.getElementById('chatInput').placeholder = 'Add a message with your file (optional)...';
});

document.getElementById('clearFileBtn').addEventListener('click', () => {
  selectedFile = null;
  document.getElementById('fileInput').value = '';
  document.getElementById('filePreviewBar').classList.remove('show');
  document.getElementById('chatInput').placeholder = 'Type a message...';
});

// ── Send (text or file) 
async function sendMessage() {
  if (!activeConvId) return;
  const input = document.getElementById('chatInput');
  const text  = input.value.trim();

  if (selectedFile) {
    await sendFile(text);
  } else {
    if (!text) return;
    input.value = '';
    input.style.height = 'auto';
    const res = await API.post('../php/messages.php?action=send_message', { conversation_id: activeConvId, message: text });
    if (res.success) {
      appendMessage(res.message);
      updateConvPreview(text);
    } else Toast.error(res.message || 'Failed to send');
  }
}

async function sendFile(caption = '') {
  const file    = selectedFile;
  const convId  = activeConvId;
  const progress = document.getElementById('uploadProgress');
  const fill    = document.getElementById('progressFill');
  const label   = document.getElementById('uploadLabel');
  const pct     = document.getElementById('uploadPct');

  document.getElementById('filePreviewBar').classList.remove('show');
  progress.classList.add('show');
  label.textContent = `Uploading ${file.name}…`;

  const formData = new FormData();
  formData.append('file', file);
  formData.append('conversation_id', convId);

  return new Promise(resolve => {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../php/messages.php?action=send_file');
    xhr.upload.onprogress = e => {
      if (e.lengthComputable) {
        const p = Math.round(e.loaded / e.total * 100);
        fill.style.width = p + '%';
        pct.textContent  = p + '%';
      }
    };
    xhr.onload = () => {
      progress.classList.remove('show');
      fill.style.width = '0%';
      try {
        const res = JSON.parse(xhr.responseText);
        if (res.success) {
          appendMessage(res.message);
          updateConvPreview('📎 ' + file.name);
          if (caption) {
           
            API.post('../php/messages.php?action=send_message', { conversation_id: convId, message: caption })
              .then(r => { if (r.success) appendMessage(r.message); });
          }
        } else Toast.error(res.message || 'Upload failed');
      } catch { Toast.error('Upload failed'); }
      selectedFile = null;
      document.getElementById('fileInput').value = '';
      document.getElementById('chatInput').value = '';
      document.getElementById('chatInput').style.height = 'auto';
      document.getElementById('chatInput').placeholder = 'Type a message...';
      resolve();
    };
    xhr.onerror = () => { progress.classList.remove('show'); Toast.error('Upload failed'); resolve(); };
    xhr.send(formData);
  });
}

function appendMessage(msg) {
  const container = document.getElementById('chatMessages');
  const emptyState = container.querySelector('.empty-state');
  if (emptyState) emptyState.remove();
  container.insertAdjacentHTML('beforeend', renderMessage(msg));
  container.scrollTop = container.scrollHeight;
}

function updateConvPreview(text) {
  const preview = document.querySelector(`[data-conv-id="${activeConvId}"] .chat-item-preview`);
  if (preview) preview.textContent = text.substring(0, 40);
  const timeEl = document.querySelector(`[data-conv-id="${activeConvId}"] .chat-time`);
  if (timeEl) timeEl.textContent = 'Just now';
}

document.getElementById('chatSendBtn').addEventListener('click', sendMessage);
document.getElementById('chatInput').addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});
document.getElementById('chatInput').addEventListener('input', function () {
  this.style.height = 'auto';
  this.style.height = Math.min(this.scrollHeight, 100) + 'px';
});

// Drag-and-drop files onto chat
document.getElementById('chatMessages').addEventListener('dragover', e => { e.preventDefault(); e.currentTarget.style.background = 'var(--accent-light)'; });
document.getElementById('chatMessages').addEventListener('dragleave', e => { e.currentTarget.style.background = ''; });
document.getElementById('chatMessages').addEventListener('drop', e => {
  e.preventDefault(); e.currentTarget.style.background = '';
  if (!activeConvId) return;
  const file = e.dataTransfer.files[0];
  if (!file) return;
  const dt = new DataTransfer();
  dt.items.add(file);
  document.getElementById('fileInput').files = dt.files;
  document.getElementById('fileInput').dispatchEvent(new Event('change'));
});

document.getElementById('chatBackBtn').addEventListener('click', () => {
  if (pollInterval) clearInterval(pollInterval);
  document.getElementById('chatWindow').classList.remove('mobile-active');
  document.getElementById('chatListPanel').style.display = '';
});

document.getElementById('convSearch').addEventListener('input', e => {
  const q = e.target.value.toLowerCase();
  renderConversationList(conversations.filter(c => c.partner_name.toLowerCase().includes(q)));
});

loadConversations();
</script>
<?php include 'footer.php'; ?>
</body>
</html>
