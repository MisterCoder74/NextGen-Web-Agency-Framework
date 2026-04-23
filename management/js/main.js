document.addEventListener('DOMContentLoaded', () => {
    fetchStats();

    const btnBackup = document.getElementById('btnBackup');
    if (btnBackup) {
        btnBackup.addEventListener('click', () => {
            window.location.href = '../tools/api/backup.php';
        });
    }
});

async function fetchStats() {
    try {
        const [clientsRes, projectsRes, microappsRes] = await Promise.all([
            fetch('clients.json').then(r => r.json()).catch(() => []),
            fetch('projects.json').then(r => r.json()).catch(() => []),
            fetch('../tools/api/microapps.json').then(r => r.json()).catch(() => [])
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
    const sortedClients = [...clients].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
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
        status: p.stato 
    })) : [];

    const normalizedMicroapps = Array.isArray(microapps) ? microapps.map(m => ({ 
        name: m.name || 'Unnamed Microapp', 
        type: 'Microapp', 
        date: m.date,
        status: 'completed'
    })) : [];

    // Combine
    const combined = [...normalizedProjects, ...normalizedMicroapps];

    if (combined.length === 0) {
        container.innerHTML = '<p style="font-size: 0.8rem; opacity: 0.5;">No projects found.</p>';
        return;
    }

    // Sort by date descending
    const sorted = combined.sort((a, b) => new Date(b.date) - new Date(a.date));
    const latest5 = sorted.slice(0, 5);

    container.innerHTML = latest5.map(item => `
        <div style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="font-weight: 600; color: #fff;">${item.name}</div>
                <div style="font-size: 0.7rem; opacity: 0.5;">${item.type} • ${new Date(item.date).toLocaleDateString()}</div>
            </div>
            <span style="font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; background: rgba(255,255,255,0.1); text-transform: uppercase;">${item.status}</span>
        </div>
    `).join('');
}
