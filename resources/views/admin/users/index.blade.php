@extends('layouts.admin')

@section('title', 'Пользователи системы — AI-management')

@section('content')
    <div class="page-toolbar">
        <h1 style="margin:0;">Пользователи системы</h1>
        <a href="{{ route('users.create') }}" class="btn btn-primary">Добавить пользователя</a>
    </div>

    @if ($errors->has('delete'))
        <div class="error">{{ $errors->first('delete') }}</div>
    @endif

    <div class="card" style="overflow:auto;">
        <table>
            <thead>
            <tr>
                <th style="width: 64px;">ID</th>
                <th>ФИО</th>
                <th>Роль</th>
                <th>Логин/Email</th>
                <th>Дата добавления</th>
                <th style="width: 56px;"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->roleLabel() }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->created_at?->format('d.m.Y H:i') }}</td>
                    <td>
                        <div class="row-actions" data-actions>
                            <button type="button" class="row-actions-btn" aria-label="Действия">⋯</button>
                            <div class="row-actions-menu">
                                <a href="{{ route('users.edit', $user) }}">Редактировать</a>
                                <button type="button" class="danger" data-delete-user data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}">Удалить</button>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div id="deleteBackdrop" class="modal-backdrop" aria-hidden="true"></div>
    <div id="deleteModal" class="modal" role="dialog" aria-modal="true">
        <header>
            <div class="wrap modal-head">
                <div style="font-weight:800;">Удалить пользователя?</div>
                <button type="button" class="x" id="deleteCloseX" aria-label="Закрыть">×</button>
            </div>
        </header>
        <div class="wrap" style="padding-bottom: 16px;">
            <p id="deleteModalText" style="color: var(--muted); margin-top: 0;"></p>
            <form id="deleteForm" method="post" style="display:flex; justify-content:flex-end; gap:10px; margin-top: 14px;">
                @csrf
                @method('DELETE')
                <button type="button" class="btn" id="deleteCancelBtn">Отмена</button>
                <button type="submit" class="btn btn-primary" style="background: linear-gradient(180deg, #f87171, #ef4444); border-color: #fca5a5; color: #fff;">Да, удалить</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            document.querySelectorAll('[data-actions]').forEach((wrap) => {
                const btn = wrap.querySelector('.row-actions-btn');
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    document.querySelectorAll('[data-actions].open').forEach((el) => {
                        if (el !== wrap) el.classList.remove('open');
                    });
                    wrap.classList.toggle('open');
                });
            });
            document.addEventListener('click', () => {
                document.querySelectorAll('[data-actions].open').forEach((el) => el.classList.remove('open'));
            });

            const backdrop = document.getElementById('deleteBackdrop');
            const modal = document.getElementById('deleteModal');
            const form = document.getElementById('deleteForm');
            const text = document.getElementById('deleteModalText');
            const close = () => {
                backdrop.classList.remove('show');
                modal.classList.remove('show');
            };

            document.getElementById('deleteCloseX').addEventListener('click', close);
            document.getElementById('deleteCancelBtn').addEventListener('click', close);
            backdrop.addEventListener('click', close);

            document.querySelectorAll('[data-delete-user]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const id = btn.getAttribute('data-user-id');
                    const name = btn.getAttribute('data-user-name');
                    form.action = @json(url('/users')) + '/' + id;
                    text.textContent = 'Пользователь «' + name + '» будет удалён без возможности восстановления.';
                    backdrop.classList.add('show');
                    modal.classList.add('show');
                    document.querySelectorAll('[data-actions].open').forEach((el) => el.classList.remove('open'));
                });
            });
        })();
    </script>
@endpush
