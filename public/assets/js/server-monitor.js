(function () {
    'use strict';

    const root = document.getElementById('server-monitor');
    if (!root) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const urls = {
        live: root.dataset.liveUrl,
        processes: root.dataset.processUrl,
        services: root.dataset.servicesUrl,
        processAction: root.dataset.processActionUrl,
        serviceAction: root.dataset.serviceActionUrl,
    };
    const canControl = root.dataset.canControl === '1';
    const pollMs = Math.max(5000, Math.min(60000, Number(root.dataset.pollSeconds || 10) * 1000));
    let processes = [];
    let processBusy = false;
    let metricsBusy = false;

    const stateEl = document.getElementById('live-state');
    const updatedEl = document.getElementById('last-updated');
    const filterEl = document.getElementById('process-filter');
    const processBody = document.querySelector('#process-table tbody');
    const servicesBody = document.querySelector('#services-table tbody');

    function textMetric(name, value) {
        root.querySelectorAll('[data-metric="' + name + '"]').forEach((el) => {
            el.textContent = value;
        });
    }

    function setProgress(name, value) {
        const safe = Math.max(0, Math.min(100, Number(value || 0)));
        root.querySelectorAll('[data-progress="' + name + '"]').forEach((el) => {
            el.style.width = safe + '%';
        });
    }

    function bytes(value) {
        let n = Number(value || 0);
        if (!Number.isFinite(n) || n <= 0) return '—';
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        let i = 0;
        while (n >= 1024 && i < units.length - 1) {
            n /= 1024;
            i++;
        }
        return n.toFixed(i >= 3 ? 1 : 0) + ' ' + units[i];
    }

    function uptime(seconds) {
        let n = Math.max(0, Number(seconds || 0));
        const days = Math.floor(n / 86400);
        n %= 86400;
        const hours = Math.floor(n / 3600);
        const minutes = Math.floor((n % 3600) / 60);
        return (days ? days + 'd ' : '') + hours + 'h ' + minutes + 'm';
    }

    async function getJson(url, options) {
        const response = await fetch(url, Object.assign({
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        }, options || {}));
        let payload = {};
        try { payload = await response.json(); } catch (_) {}
        if (!response.ok || payload.ok === false) {
            throw new Error(payload.message || ('Request failed (' + response.status + ')'));
        }
        return payload;
    }

    async function postJson(url, body) {
        return getJson(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
        });
    }

    async function refreshMetrics() {
        if (metricsBusy) return;
        metricsBusy = true;
        try {
            const payload = await getJson(urls.live);
            const m = payload.metrics || {};
            stateEl.textContent = 'ONLINE';
            stateEl.className = 'badge active';
            updatedEl.textContent = 'Live update: ' + new Date(m.captured_at || Date.now()).toLocaleString();
            textMetric('cpu_percent', Number(m.cpu_percent || 0).toFixed(1) + '%');
            textMetric('memory_percent', Number(m.memory_percent || 0).toFixed(1) + '%');
            textMetric('disk_percent', Number(m.disk_percent || 0).toFixed(1) + '%');
            textMetric('load_1', Number(m.load_1 || 0).toFixed(2));
            textMetric('load_5', Number(m.load_5 || 0).toFixed(2));
            textMetric('load_15', Number(m.load_15 || 0).toFixed(2));
            textMetric('process_count', String(m.process_count ?? '—'));
            textMetric('uptime', uptime(m.uptime_seconds));
            textMetric('os', m.os || '—');
            textMetric('hostname', m.hostname || '—');
            textMetric('kernel', m.kernel || '—');
            textMetric('memory_text', bytes(m.memory_used_bytes) + ' / ' + bytes(m.memory_total_bytes));
            textMetric('disk_text', bytes(m.disk_used_bytes) + ' / ' + bytes(m.disk_total_bytes));
            setProgress('cpu_percent', m.cpu_percent);
            setProgress('memory_percent', m.memory_percent);
            setProgress('disk_percent', m.disk_percent);
        } catch (error) {
            stateEl.textContent = 'UNREACHABLE';
            stateEl.className = 'badge critical';
            updatedEl.textContent = error.message;
        } finally {
            metricsBusy = false;
        }
    }

    function cell(row, value, className) {
        const td = document.createElement('td');
        td.textContent = value == null ? '—' : String(value);
        if (className) td.className = className;
        row.appendChild(td);
        return td;
    }

    function actionButton(label, className, handler, disabled) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-sm ' + className;
        button.textContent = label;
        button.disabled = !!disabled;
        button.addEventListener('click', handler);
        return button;
    }

    function renderProcesses() {
        if (!processBody) return;
        processBody.replaceChildren();
        const needle = (filterEl?.value || '').trim().toLowerCase();
        const filtered = processes.filter((p) => !needle || [p.pid, p.user, p.command, p.args].join(' ').toLowerCase().includes(needle));

        if (!filtered.length) {
            const tr = document.createElement('tr');
            const td = cell(tr, needle ? 'No processes match the filter.' : 'No process data returned.');
            td.colSpan = 8;
            td.className = 'empty';
            processBody.appendChild(tr);
            return;
        }

        filtered.forEach((p) => {
            const tr = document.createElement('tr');
            cell(tr, p.pid, 'mono');
            cell(tr, p.user);
            cell(tr, p.state);
            cell(tr, Number(p.cpu || 0).toFixed(1) + '%');
            cell(tr, Number(p.memory || 0).toFixed(1) + '%');
            cell(tr, p.elapsed, 'mono');
            const cmdCell = cell(tr, p.args || p.command, 'mono process-command');
            cmdCell.title = p.args || p.command || '';
            const action = document.createElement('td');
            action.className = 'no-print';
            if (canControl && !p.protected) {
                const wrap = document.createElement('div');
                wrap.className = 'toolbar';
                wrap.style.margin = '0';
                wrap.appendChild(actionButton('Terminate', 'btn-light', () => processAction(p.pid, 'TERM'), false));
                wrap.appendChild(actionButton('Force stop', 'btn-danger', () => processAction(p.pid, 'KILL'), false));
                action.appendChild(wrap);
            } else {
                const badge = document.createElement('span');
                badge.className = 'badge';
                badge.textContent = p.protected ? 'PROTECTED' : 'VIEW ONLY';
                action.appendChild(badge);
            }
            tr.appendChild(action);
            processBody.appendChild(tr);
        });
    }

    async function refreshProcesses() {
        if (processBusy) return;
        processBusy = true;
        try {
            const payload = await getJson(urls.processes + '?limit=150');
            processes = payload.processes || [];
            renderProcesses();
        } catch (error) {
            processBody.replaceChildren();
            const tr = document.createElement('tr');
            const td = cell(tr, error.message);
            td.colSpan = 8;
            td.className = 'empty';
            processBody.appendChild(tr);
        } finally {
            processBusy = false;
        }
    }

    async function processAction(pid, signal) {
        const verb = signal === 'KILL' ? 'force stop' : 'terminate';
        if (!confirm('Are you sure you want to ' + verb + ' PID ' + pid + '?')) return;
        const reason = prompt('Enter the operational reason for this process action (minimum 8 characters):');
        if (!reason || reason.trim().length < 8) {
            alert('A meaningful operational reason is required.');
            return;
        }
        try {
            const payload = await postJson(urls.processAction, { pid: pid, signal: signal, reason: reason.trim() });
            alert(payload.message || 'Action completed.');
            await refreshProcesses();
            await refreshMetrics();
        } catch (error) {
            alert(error.message);
        }
    }

    async function refreshServices() {
        if (!servicesBody) return;
        try {
            const payload = await getJson(urls.services);
            const services = payload.services || [];
            servicesBody.replaceChildren();
            if (!services.length) {
                const tr = document.createElement('tr');
                const td = cell(tr, 'No services are allow-listed in server configuration.');
                td.colSpan = 3;
                td.className = 'empty';
                servicesBody.appendChild(tr);
                return;
            }
            services.forEach((s) => {
                const tr = document.createElement('tr');
                const name = cell(tr, s.label || s.name);
                name.title = s.name;
                const status = document.createElement('td');
                const badge = document.createElement('span');
                badge.className = 'badge ' + (s.state === 'active' ? 'active' : (s.state === 'failed' ? 'critical' : 'warning'));
                badge.textContent = String(s.state || 'unknown').toUpperCase();
                status.appendChild(badge);
                tr.appendChild(status);
                const controls = document.createElement('td');
                controls.className = 'no-print';
                if (canControl) {
                    const wrap = document.createElement('div');
                    wrap.className = 'toolbar';
                    wrap.style.margin = '0';
                    ['start', 'restart', 'stop'].forEach((op) => {
                        wrap.appendChild(actionButton(op.charAt(0).toUpperCase() + op.slice(1), op === 'stop' ? 'btn-danger' : 'btn-light', () => serviceAction(s.name, op), false));
                    });
                    controls.appendChild(wrap);
                } else {
                    controls.textContent = 'View only';
                }
                tr.appendChild(controls);
                servicesBody.appendChild(tr);
            });
        } catch (error) {
            servicesBody.replaceChildren();
            const tr = document.createElement('tr');
            const td = cell(tr, error.message);
            td.colSpan = 3;
            td.className = 'empty';
            servicesBody.appendChild(tr);
        }
    }

    async function serviceAction(service, operation) {
        if (!confirm('Are you sure you want to ' + operation + ' ' + service + '?')) return;
        const reason = prompt('Enter the operational reason for this service action (minimum 8 characters):');
        if (!reason || reason.trim().length < 8) {
            alert('A meaningful operational reason is required.');
            return;
        }
        try {
            const payload = await postJson(urls.serviceAction, { service: service, operation: operation, reason: reason.trim() });
            alert(payload.message || 'Action completed.');
            await refreshServices();
            await refreshProcesses();
            await refreshMetrics();
        } catch (error) {
            alert(error.message);
        }
    }

    document.getElementById('refresh-processes')?.addEventListener('click', refreshProcesses);
    filterEl?.addEventListener('input', renderProcesses);

    refreshMetrics();
    refreshProcesses();
    refreshServices();
    window.setInterval(refreshMetrics, pollMs);
    window.setInterval(refreshProcesses, Math.max(pollMs, 10000));
    window.setInterval(refreshServices, Math.max(pollMs * 2, 20000));
})();
