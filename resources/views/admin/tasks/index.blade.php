@extends('layouts.admin')

@section('title', 'Задачи — AI-management')

@section('content')
    @php
        $sortUrl = function (string $column) use ($sort, $direction) {
            $next = ($sort === $column && $direction === 'asc') ? 'desc' : 'asc';

            return request()->fullUrlWithQuery(['sort' => $column, 'direction' => $next]);
        };
        $resetUrl = route('tasks.index');
    @endphp

    <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:16px; flex-wrap:wrap;">
        <div>
            <h1 style="margin:0 0 8px 0;">Задачи</h1>
            <p style="color: var(--muted); margin:0; max-width: 900px;">
                Синхронизация: <span class="pill">php artisan redmine:sync-tasks</span>.
                По умолчанию выше — без исполнителя. Сортировка по столбцам — по клику на заголовок.
                <a href="{{ $resetUrl }}">Сбросить сортировку</a>
            </p>
        </div>
        <label class="toggle" style="margin-bottom:4px;">
            <div>
                <div style="font-weight:700;">Автоназначение</div>
                <div style="color: var(--muted); font-size: 12px;">При «Подобрать исполнителя» сразу назначить лучшего</div>
            </div>
            <div id="autoAssignSwitch" class="switch" role="switch" aria-checked="false" tabindex="0" title="Переключить"></div>
        </label>
    </div>

    <div class="card" style="margin-top: 16px; overflow:auto;">
        <table>
            <thead>
            <tr>
                <th><a href="{{ $sortUrl('project_name') }}">Проект</a></th>
                <th style="min-width: 220px;"><a href="{{ $sortUrl('subject') }}">Название</a></th>
                <th><a href="{{ $sortUrl('assigned_to_login') }}">Исполнитель</a></th>
                <th>Описание</th>
                <th><a href="{{ $sortUrl('priority_name') }}">Приоритет</a></th>
                <th><a href="{{ $sortUrl('due_date') }}">Дедлайн</a></th>
                <th><a href="{{ $sortUrl('labels') }}">Метки</a></th>
                <th><a href="{{ $sortUrl('estimated_hours') }}">Часы</a></th>
                <th><a href="{{ $sortUrl('tracker_name') }}">Тип</a></th>
            </tr>
            </thead>
            <tbody>
            @foreach($issues as $issue)
                <tr>
                    <td>{{ $issue->project_name }}</td>
                    <td>{{ $issue->subject }}</td>
                    <td>
                        @if($issue->isUnassigned())
                            <button type="button"
                                    class="btn btn-primary pick-btn"
                                    data-issue-id="{{ $issue->id }}">
                                Подобрать исполнителя
                            </button>
                        @else
                            {{ $issue->assigneeDisplay() ?? '—' }}
                        @endif
                    </td>
                    <td style="max-width: 360px;">
                        <details>
                            <summary>Раскрыть</summary>
                            <div style="white-space: pre-wrap; margin-top:8px; color: var(--muted);">
                                {{ $issue->description ?: '—' }}
                            </div>
                        </details>
                    </td>
                    <td>{{ $issue->priority_name ?? '—' }}</td>
                    <td>{{ $issue->due_date?->format('Y-m-d') ?? '—' }}</td>
                    <td>
                        @forelse($issue->labelsList() as $label)
                            <span class="pill" style="margin: 2px 4px 2px 0; display:inline-block;">{{ $label }}</span>
                        @empty
                            —
                        @endforelse
                    </td>
                    <td>{{ $issue->estimated_hours !== null ? number_format((float) $issue->estimated_hours, 2, ',', ' ') : '—' }}</td>
                    <td>{{ $issue->tracker_name ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div style="margin-top: 16px;">
        {{ $issues->links() }}
    </div>

    <div id="modalBackdrop" class="modal-backdrop" aria-hidden="true"></div>
    <div id="recModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="recModalTitle">
        <header>
            <div class="wrap modal-head">
                <div id="recModalTitle" style="font-weight:800;">Рекомендации исполнителей</div>
                <button type="button" class="x" id="modalCloseX" aria-label="Закрыть">×</button>
            </div>
        </header>
        <div class="wrap" style="padding-bottom: 16px;">
            <div id="recModalBody" style="display:grid; gap:12px;"></div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top: 14px;">
                <button type="button" class="btn" id="modalCancelBtn">Отмена</button>
                <button type="button" class="btn btn-primary" id="modalSaveBtn">Сохранить</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const base = @json(rtrim(url('/tasks'), '/'));

            const autoSwitch = document.getElementById('autoAssignSwitch');
            const backdrop = document.getElementById('modalBackdrop');
            const modal = document.getElementById('recModal');
            const modalBody = document.getElementById('recModalBody');
            const modalCloseX = document.getElementById('modalCloseX');
            const modalCancelBtn = document.getElementById('modalCancelBtn');
            const modalSaveBtn = document.getElementById('modalSaveBtn');

            const AUTO_ASSIGN_KEY = 'ai-management.tasks.autoAssign';
            let autoAssign = localStorage.getItem(AUTO_ASSIGN_KEY) === '1';
            const syncSwitch = () => {
                autoSwitch.classList.toggle('on', autoAssign);
                autoSwitch.setAttribute('aria-checked', autoAssign ? 'true' : 'false');
            };
            const persistAutoAssign = () => {
                localStorage.setItem(AUTO_ASSIGN_KEY, autoAssign ? '1' : '0');
            };
            syncSwitch();
            const toggleAuto = () => {
                autoAssign = !autoAssign;
                syncSwitch();
                persistAutoAssign();
            };
            autoSwitch.addEventListener('click', toggleAuto);
            autoSwitch.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggleAuto();
                }
            });

            let activeIssueId = null;
            let lastRecommendations = [];

            const closeModal = () => {
                backdrop.classList.remove('show');
                modal.classList.remove('show');
                modalBody.innerHTML = '';
                activeIssueId = null;
                lastRecommendations = [];
            };

            const openModal = (issueId, recommendations) => {
                activeIssueId = issueId;
                lastRecommendations = recommendations;
                modalBody.innerHTML = '';

                recommendations.forEach((rec, idx) => {
                    const row = document.createElement('label');
                    row.style.border = '1px solid rgba(148,163,184,0.25)';
                    row.style.borderRadius = '10px';
                    row.style.padding = '10px 12px';
                    row.style.display = 'grid';
                    row.style.gap = '6px';
                    row.style.cursor = 'pointer';

                    const head = document.createElement('div');
                    head.style.display = 'flex';
                    head.style.justifyContent = 'space-between';
                    head.style.gap = '10px';
                    head.innerHTML = `<div><strong>${rec.login}</strong></div><div class="pill">${Number(rec.match_percent).toFixed(1)}%</div>`;

                    const reasons = document.createElement('ul');
                    reasons.className = 'compact';
                    (rec.reasons || []).forEach((r) => {
                        const li = document.createElement('li');
                        li.textContent = r;
                        reasons.appendChild(li);
                    });

                    const cb = document.createElement('input');
                    cb.type = 'checkbox';
                    cb.name = 'rec_pick';
                    cb.dataset.login = rec.login;
                    cb.checked = idx === 0;
                    cb.addEventListener('change', () => {
                        if (!cb.checked) {
                            cb.checked = true;
                            return;
                        }
                        modalBody.querySelectorAll('input[name="rec_pick"]').forEach((x) => {
                            if (x !== cb) x.checked = false;
                        });
                    });

                    const top = document.createElement('div');
                    top.style.display = 'flex';
                    top.style.gap = '10px';
                    top.appendChild(cb);
                    const right = document.createElement('div');
                    right.style.flex = '1';
                    right.appendChild(head);
                    right.appendChild(reasons);
                    top.appendChild(right);
                    row.appendChild(top);

                    modalBody.appendChild(row);
                });

                backdrop.classList.add('show');
                modal.classList.add('show');
            };

            backdrop.addEventListener('click', closeModal);
            modalCloseX.addEventListener('click', closeModal);
            modalCancelBtn.addEventListener('click', closeModal);

            modalSaveBtn.addEventListener('click', async () => {
                if (!activeIssueId) return;
                const picked = modalBody.querySelector('input[name="rec_pick"]:checked');
                if (!picked) return;
                window.setGlobalLoading(true);
                try {
                    const res = await fetch(`${base}/${activeIssueId}/assign`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({login: picked.dataset.login}),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) throw new Error(data.message || 'Ошибка назначения');
                    window.location.reload();
                } catch (e) {
                    window.setGlobalLoading(false);
                    alert(e.message || String(e));
                }
            });

            async function postJson(url, body) {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: body ? JSON.stringify(body) : '{}',
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || `HTTP ${res.status}`);
                return data;
            }

            document.querySelectorAll('.pick-btn').forEach((btn) => {
                btn.addEventListener('click', async () => {
                    const issueId = btn.getAttribute('data-issue-id');
                    const urlRec = `${base}/${issueId}/recommendations`;

                    try {
                        window.setGlobalLoading(true);
                        const data = await postJson(urlRec, {});
                        const recs = data.recommendations || [];
                        if (!recs.length) {
                            window.setGlobalLoading(false);
                            alert('Пустой список рекомендаций.');
                            return;
                        }

                        if (autoAssign) {
                            persistAutoAssign();
                            const urlAs = `${base}/${issueId}/assign`;
                            await postJson(urlAs, {login: recs[0].login});
                            window.location.reload();
                            return;
                        }

                        window.setGlobalLoading(false);
                        openModal(issueId, recs.slice(0, 5));
                    } catch (e) {
                        window.setGlobalLoading(false);
                        alert(e.message || String(e));
                    }
                });
            });
        })();
    </script>
@endpush
