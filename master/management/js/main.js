document.addEventListener('DOMContentLoaded', () => {
    fetchStats();

    const btnBackup = document.getElementById('btnBackup');
    if (btnBackup) {
        btnBackup.addEventListener('click', () => {
            const username = localStorage.getItem('sync_username') || 'Anonymous';
            window.location.href = `../tools/api/backup.php?u=${encodeURIComponent(username)}`;
        });
    }
});

async function fetchStats() {
    const t = Date.now();
    try {
        const [clientsRes, projectsRes, microappsRes] = await Promise.all([
            fetch(`clients.json?t=${t}`).then(r => r.json()).catch(() => []),
            fetch(`projects.json?t=${t}`).then(r => r.json()).catch(() => []),
            fetch(`../tools/api/microapps.json?t=${t}`).then(r => r.json()).catch(() => [])
        ]);

        displayRecentClients(clientsRes);
        displayRecentProjects(projectsRes, microappsRes);
    } catch (error) {
        console.error('Error fetching stats:', error);
    }
}

function displayRecentClients(clients) {
    const container = document.getElementById('recentClients');
    if (!container) return;

    if (!Array.isArray(clients) || clients.length === 0) {
        container.innerHTML = '<p style="font-size: 0.8rem; opacity: 0.5;">No clients found.</p>';
        return;
    }

    // Sort by created_at descending
    const sortedClients = [...clients].sort((a, b) => {
        let dateA = a.created_at ? new Date(a.created_at).getTime() : 0;
        let dateB = b.created_at ? new Date(b.created_at).getTime() : 0;
        if (isNaN(dateA)) dateA = 0;
        if (isNaN(dateB)) dateB = 0;
        return dateB - dateA;
    });
    const latest3 = sortedClients.slice(0, 3);

    container.innerHTML = latest3.map(client => `
        <div style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.05);">
            <div style="font-weight: 600; color: #fff;">${client.nominativo}</div>
            <div style="font-size: 0.75rem; opacity: 0.7;">${client.email}</div>
            <div style="font-size: 0.7rem; opacity: 0.5; margin-top: 2px;">Acquired: ${new Date(client.created_at).toLocaleDateString()}</div>
        </div>
    `).join('');
}

function displayRecentProjects(projects, microapps) {
    const container = document.getElementById('recentProjects');
    if (!container) return;

    const normalizedProjects = Array.isArray(projects) ? projects.map(p => ({ 
        name: p.nome_progetto, 
        type: 'Project', 
        date: p.created_at || p.data_inizio,
        status: p.stato,
        url: `../tools/project_workspace.html?id=${p.id}`
    })) : [];

    const normalizedMicroapps = Array.isArray(microapps) ? microapps.map(m => {
        let displayName = m.name || 'Unnamed Microapp';
        if (m.project_id && Array.isArray(projects)) {
            const linkedProject = projects.find(p => p.id === m.project_id);
            if (linkedProject) {
                displayName += ` (${linkedProject.nome_progetto})`;
            }
        }
        return { 
            name: displayName, 
            type: 'Microapp', 
            date: m.date,
            status: 'completed',
            url: m.url
        };
    }) : [];

    // Combine
    const combined = [...normalizedProjects, ...normalizedMicroapps];

    if (combined.length === 0) {
        container.innerHTML = '<p style="font-size: 0.8rem; opacity: 0.5;">No projects found.</p>';
        return;
    }

    // Sort by date descending
    const sorted = combined.sort((a, b) => {
        let dateA = a.date ? new Date(a.date).getTime() : 0;
        let dateB = b.date ? new Date(b.date).getTime() : 0;
        if (isNaN(dateA)) dateA = 0;
        if (isNaN(dateB)) dateB = 0;
        return dateB - dateA;
    });
    const latest5 = sorted.slice(0, 5);

    container.innerHTML = latest5.map(item => `
        <div style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <a href="${item.url}" target="_blank" style="font-weight: 600; color: #fff; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#00f2a0'" onmouseout="this.style.color='#fff'">${item.name}</a>
                <div style="font-size: 0.7rem; opacity: 0.5;">${item.type} • ${new Date(item.date).toLocaleDateString()}</div>
            </div>
            <span style="font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; background: rgba(255,255,255,0.1); text-transform: uppercase;">${item.status}</span>
        </div>
    `).join('');
}
