(function() {
    const isManagement = window.location.pathname.includes('/management/');
    const role = isManagement ? 'manager' : 'tech';
    // Use a relative path that works from both /management/ and /tools/
    // If we are in /management/, it's ./message_handler.php
    // If we are in /tools/, it's ../management/message_handler.php
    const apiEndpoint = isManagement ? 'message_handler.php' : '../management/message_handler.php';
    
    let lastMessageCount = 0;
    let isOpen = false;

    // Create UI elements
    const container = document.createElement('div');
    container.className = 'msg-bubble-container';
    container.innerHTML = `
        <div class="msg-window" id="msgWindow">
            <div class="msg-header">
                <h3>Internal Chat (${role.charAt(0).toUpperCase() + role.slice(1)})</h3>
                <button id="closeChat" style="background:none; border:none; color:white; cursor:pointer; font-size:1.2rem;">&times;</button>
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

    function sendMessage() {
        const text = msgInput.value.trim();
        if (!text) return;

        fetch(apiEndpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ text, role })
        })
        .then(res => res.json())
        .then(() => {
            msgInput.value = '';
            fetchMessages();
        });
    }

    function fetchMessages() {
        fetch(apiEndpoint)
        .then(res => res.json())
        .then(messages => {
            if (!Array.isArray(messages)) messages = [];
            renderMessages(messages);
            updateBadge(messages);
        })
        .catch(err => console.error('Error fetching messages:', err));
    }

    function renderMessages(messages) {
        msgBody.innerHTML = '';
        messages.forEach(msg => {
            const div = document.createElement('div');
            div.className = `msg-item ${msg.role}`;
            
            const time = new Date(msg.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            div.innerHTML = `
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

        let unreadCount = 0;
        if (role === 'tech') {
            // Tech sees unanswered manager messages
            unreadCount = messages.filter(m => m.role === 'manager' && m.status === 'unanswered').length;
        } else {
            // Manager sees tech messages that arrived after their last message?
            // Or just any tech message if the last one is from tech?
            // Let's say if the last message is from tech and we haven't seen it.
            if (messages.length > 0 && messages[messages.length - 1].role === 'tech' && messages.length > lastMessageCount) {
                unreadCount = messages.length - lastMessageCount;
            }
        }

        if (unreadCount > 0) {
            msgBadge.innerText = unreadCount;
            msgBadge.style.display = 'flex';
        }
    }

    function scrollToBottom() {
        msgBody.scrollTop = msgBody.scrollHeight;
    }

    // Initial fetch and poll
    fetchMessages();
    setInterval(fetchMessages, 60000);
})();
