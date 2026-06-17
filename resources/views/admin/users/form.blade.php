@extends('layouts.admin')

@section('title', ($isEdit ? 'Редактирование' : 'Добавление') . ' пользователя — AI-management')

@section('content')
    <h1 style="margin-top:0;">{{ $isEdit ? 'Редактирование пользователя' : 'Добавление пользователя' }}</h1>

    @if ($errors->any())
        <div class="error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="card" style="padding: 20px; max-width: 640px;">
        <form method="post" action="{{ $isEdit ? route('users.update', $user) : route('users.store') }}" class="form-grid">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <label>
                <span>ФИО</span>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
            </label>

            <label>
                <span>Роль</span>
                <select name="role" required>
                    @foreach(\App\Models\User::roleOptions() as $value => $label)
                        <option value="{{ $value }}" @selected(old('role', $user->role ?: \App\Models\User::ROLE_MANAGER) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                <span>Логин/Email</span>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username">
            </label>

            <label>
                <span>Пароль{{ $isEdit ? ' (оставьте пустым, чтобы не менять)' : '' }}</span>
                <input type="password" name="password" autocomplete="new-password" {{ $isEdit ? '' : 'required' }}>
            </label>

            <label>
                <span>Повторить пароль</span>
                <input type="password" name="password_confirmation" autocomplete="new-password" {{ $isEdit ? '' : 'required' }}>
            </label>

            <div style="display:flex; gap:10px; margin-top: 8px;">
                <button class="btn btn-primary" type="submit">{{ $isEdit ? 'Сохранить' : 'Добавить' }}</button>
                <a href="{{ route('users.index') }}" class="btn">Отмена</a>
            </div>
        </form>
    </div>
@endsection
