(function() {
    const isManagement = window.location.pathname.includes('/management/');
    const role = isManagement ? 'manager' : 'tech';
    // Use a relative path that works from both /management/ and /tools/
    // If we are in /management/, it's ./message_handler.php
    // If we are in /tools/, it's ../management/message_handler.php
    const apiEndpoint = isManagement ? 'message_handler.php' : '../management/message_handler.php';
    const projectApiEndpoint = isManagement ? 'project_handler.php' : '../management/project_handler.php';
    
    let lastMessageCount = 0;
    let isOpen = false;
    let selectedProjectId = 'general';
    let projects = [];

    // Create UI elements
    const container = document.createElement('div');
    container.className = 'msg-bubble-container';
    container.innerHTML = `
        <div class="msg-window" id="msgWindow">
            <div class="msg-header">
                <div class="msg-header-top">
                    <h3>Internal Chat (${role.charAt(0).toUpperCase() + role.slice(1)})</h3>
                    <button id="closeChat" style="background:none; border:none; color:white; cursor:pointer; font-size:1.2rem;">&times;</button>
                </div>
                <select id="msgProjectSelector" class="msg-project-selector">
                    <option value="general">General Chat</option>
                </select>
            </div>
            <div class="msg-body" id="msgBody"></div>
            <div class="msg-footer">
                <input type="text" id="msgInput" class="msg-input" placeholder="Type a message...">
                <button id="sendMsg" class="msg-send-btn">Send</button>
            </div>
        </div>
        <div class="msg-bubble" id="msgBubble">
            <span>💬</span>
            <div class="msg-badge" id="msgBadge">0</div>
        </div>
    `;
    document.body.appendChild(container);

    const msgBubble = document.getElementById('msgBubble');
    const msgWindow = document.getElementById('msgWindow');
    const closeChat = document.getElementById('closeChat');
    const msgInput = document.getElementById('msgInput');
    const sendMsg = document.getElementById('sendMsg');
    const msgBody = document.getElementById('msgBody');
    const msgBadge = document.getElementById('msgBadge');
    const projectSelector = document.getElementById('msgProjectSelector');

    projectSelector.onchange = (e) => {
        selectedProjectId = e.target.value;
        fetchMessages();
    };

    msgBubble.onclick = () => {
        isOpen = !isOpen;
        msgWindow.style.display = isOpen ? 'flex' : 'none';
        if (isOpen) {
            msgBadge.style.display = 'none';
            msgBadge.innerText = '0';
            scrollToBottom();
        }
    };

    closeChat.onclick = () => {
        isOpen = false;
        msgWindow.style.display = 'none';
    };

    sendMsg.onclick = sendMessage;
    msgInput.onkeypress = (e) => { if (e.key === 'Enter') sendMessage(); };

    function fetchProjects() {
        const username = localStorage.getItem('sync_username') || 'Anonymous';
        fetch(projectApiEndpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'list', username: username })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && Array.isArray(data.data)) {
                projects = data.data;
                updateProjectSelector();
            }
        })
        .catch(err => console.error('Error fetching projects:', err));
    }

    function updateProjectSelector() {
        // Keep General Chat
        projectSelector.innerHTML = '<option value="general">General Chat</option>';
        projects.forEach(project => {
            const option = document.createElement('option');
            option.value = project.id;
            option.textContent = project.nome_progetto || project.name || `Project ${project.id}`;
            projectSelector.appendChild(option);
        });
        projectSelector.value = selectedProjectId;
    }

    function sendMessage() {
        const text = msgInput.value.trim();
        if (!text) return;

        const username = localStorage.getItem('sync_username') || 'Anonymous';

        fetch(apiEndpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ text, role, project_id: selectedProjectId, username: username })
        })
        .then(res => res.json())
        .then(() => {
            msgInput.value = '';
            fetchMessages();
        });
    }

    function fetchMessages() {
        const username = localStorage.getItem('sync_username') || 'Anonymous';
        fetch(`${apiEndpoint}?u=${encodeURIComponent(username)}`)
        .then(res => res.json())
        .then(messages => {
            if (!Array.isArray(messages)) messages = [];
            
            // Filter messages based on selected project
            const filteredMessages = messages.filter(msg => {
                const msgProjectId = msg.project_id || 'general';
                return msgProjectId === selectedProjectId;
            });

            renderMessages(filteredMessages);
            updateBadge(messages); // Use all messages for badge
        })
        .catch(err => console.error('Error fetching messages:', err));
    }

    function renderMessages(messages) {
        msgBody.innerHTML = '';
        if (messages.length === 0) {
            msgBody.innerHTML = '<div style="text-align:center; color:rgba(255,255,255,0.3); margin-top:20px; font-size:0.8rem;">No messages yet.</div>';
        }
        messages.forEach(msg => {
            const div = document.createElement('div');
            div.className = `msg-item ${msg.role}`;
            
            const time = new Date(msg.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            const capitalizedRole = msg.role.charAt(0).toUpperCase() + msg.role.slice(1);
            const displayName = msg.username ? `${msg.username} (${capitalizedRole})` : capitalizedRole;
            
            div.innerHTML = `
                <div class="msg-sender" style="font-size: 0.7rem; opacity: 0.7; margin-bottom: 2px;">${displayName}</div>
                <div>${msg.text}</div>
                <span class="msg-time">${time}</span>
            `;
            msgBody.appendChild(div);
        });
        
        if (messages.length > lastMessageCount) {
            scrollToBottom();
        }
        lastMessageCount = messages.length;
    }

    function updateBadge(messages) {
        if (isOpen) return;

        const otherRole = (role === 'manager') ? 'tech' : 'manager';
        const unreadCount = messages.filter(m => m.role === otherRole && m.status === 'unanswered').length;

        if (unreadCount > 0) {
            msgBadge.innerText = unreadCount;
            msgBadge.style.display = 'flex';
        } else {
            msgBadge.style.display = 'none';
        }
    }

    function scrollToBottom() {
        msgBody.scrollTop = msgBody.scrollHeight;
    }

    // Initial fetch and poll
    fetchProjects();
    fetchMessages();
    setInterval(fetchMessages, 10000); // Reduced polling interval for better experience
})();
