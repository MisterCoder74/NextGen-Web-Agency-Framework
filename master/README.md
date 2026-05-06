# NextGen Web Agency Framework

NextGen is a high-performance, AI-integrated ecosystem engineered for modern web agencies. It bridges the gap between strategic business management and rapid technical development, providing a unified **"Agency OS"** that eliminates cognitive leakage and operational overhead.

Designed with a multitenant, database-less architecture, NextGen offers unprecedented portability and scalability for agencies managing diverse client portfolios through next-generation generative AI.

---

## 🏗️ Architecture & Governance

NextGen is built on a foundation of robust governance and flexible operational modes to suit agencies of all sizes, from lean startups to structured enterprises.

### Role-Based Access Control (RBAC)
*   **Manager**: Full oversight of agency operations, financial data, client portfolios, and team-wide workload analytics.
*   **Technician**: Operates within isolated workspaces with access limited to assigned projects and technical tasks.

### Dual Operational Modes (SYNC vs. CONTROL)
*   **SYNC Mode (Agile Collaboration)**: Tailored for lean teams. Features an open workspace, P2P collaboration, and rapid prototyping capabilities with instant deployment.
*   **CONTROL Mode (Enterprise Rigor)**: Designed for structured agencies. Implements a **Gating Layer** for data isolation, hierarchical validation workflows, and advanced workload auditing.

---

## 💼 Management Tech Suite

A comprehensive suite of tools for high-level operations, client relationship management, and agency intelligence.

### Strategic Workflow & Analytics
*   **NextGen Kanban**: Dynamic task management with operator-based filtering, priority tagging, and real-time state persistence.
*   **Agency Planner**: High-performance calendar featuring differentiated permissions (Full Edit for Management / View-Only for Technicians).
*   **Live Audit Log**: Security-focused monitoring tracking all administrative actions and system-wide changes.

### Client & Project Operations
*   **Client Manager**: Centralized database for managing client records and technical profiles.
*   **Client Portfolio**: AI-enhanced profiles featuring **360° Strategic Intelligence**—automated analysis of client assets and history.
*   **Project Manager**: Advanced tracking of milestones, deliverables, and project-specific AI workspaces.
*   **Quotes Manager**: Rapid generation of professional, branded PDF quotes with automated VAT and discount calculations.
*   **Contract Manager**: Streamlined management of freelance agreements and service level clauses.
*   **Email Campaigner**: Integrated mass-mailing system with AI-optimized templates for client engagement.

### Agency Intelligence (CONTROL Mode Exclusive)
*   **Workload Auditor**: Real-time telemetry and predictive analysis of team productivity and resource allocation.
*   **Agency Intelligence**: Advanced analytical dashboard utilizing Chart.js to provide deep insights into portfolio distribution and efficiency trends.

---

## 🤖 AI Development Tech Suite

An ecosystem of multi-agent AI pipelines and technical utilities that automate the end-to-end development lifecycle.

### Multi-Agent Pipelines
*   **WebForge AI**: Flagship 4-agent pipeline (**Strategist, Frontend DevBot, Backend DevBot, Validator**) that generates production-ready full-stack applications from natural language.
*   **Vibe Coder**: Iterative development environment where AI decomposes complex objectives into micro-tasks for precise, sequential execution.
*   **Collabra**: High-speed dual-agent workflow for rapid frontend/backend synchronization and prototyping.

### Intelligent Technical Utilities
*   **Website Builder Plus**: Parametric generation of multi-page websites with specialized design presets (*Professional, Minimal, High-Tech, Creative*).
*   **Web Restyler**: Advanced modernization tool for "De-Wordpressing" and restyling legacy HTML/CSS codebases.
*   **Interface Analyzer**: Vision-powered UI/UX audit tool for analyzing accessibility, design patterns, and user experience.
*   **Image Rebuilder**: AI-driven vision-to-prompt engine that creates royalty-free alternatives to copyrighted imagery.

### Deployment & Analysis
*   **Repo Analyzer**: Semantic analysis of GitHub repositories for architectural insights and technology stack detection.
*   **Repo Publisher**: One-click deployment and repository creation for AI-generated micro-apps with automated token management.
*   **Markdown Converter**: Bidirectional, high-fidelity conversion between documentation (`README.md`) and styled HTML.

---

## 🛠️ Technical Stack

*   **Backend**: PHP 7.x/8.x (Secure API Proxying, File System Operations, ZIP Archiving).
*   **Frontend**: Vanilla JavaScript (ES6+), HTML5, CSS3 (Custom Variables & Responsive Design).
*   **AI Engine**: OpenAI GPT-4.1-nano, GPT-4.1-mini, and GPT-4o-mini models.
*   **Data Layer**: Database-less architecture using high-performance JSON storage.
*   **Design System**: High-contrast dark-mode aesthetic featuring **Syne**, **Instrument Serif**, and **JetBrains Mono**.

---

## 📂 Project Structure
```
├── management/        # Business operations & client management suite
│   ├── js/            # Dashboard analytics, messaging & Kanban logic
│   ├── css/           # Management-specific design system
│   └── *.php/*.html   # Administrative tools (Quotes, Portfolio, Kanban, etc.)
├── tools/             # AI development & technical suite
│   ├── api/           # OpenAI proxy, micro-app storage, & Backup system
│   ├── js/            # Multi-agent orchestration (WebForge, Collabra)
│   └── *.html         # Technical AI-powered tools
├── audit_log.json     # System-wide activity logs
├── setup.json         # Master configuration
├── tenants.json       # Multitenant configuration
├── users.json         # RBAC credentials & role definitions
├── index.php          # Main framework gateway
├── login.php          # Secure authentication portal
└── README.md          # Project documentation
```

---
*Created by Vivacity Design - Empowering the next generation of web agencies through AI-driven innovation.*
