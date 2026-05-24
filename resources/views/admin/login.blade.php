@extends('layouts.admin')

@section('title', 'Вход — AI-management')

@section('content')
    <div class="card" style="max-width: 420px; margin: 40px auto; padding: 20px;">
        <h1 style="margin-top:0;font-size:20px;">Вход в админку</h1>
        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif
        <form method="post" action="{{ route('login') }}" style="display:grid; gap:12px;">
            @csrf
            <label>
                <div class="pill" style="margin-bottom:6px;">Логин</div>
                <input type="text" name="username" value="{{ old('username') }}" autocomplete="username" required>
            </label>
            <label>
                <div class="pill" style="margin-bottom:6px;">Пароль</div>
                <input type="password" name="password" autocomplete="current-password" required>
            </label>
            <button class="btn btn-primary" type="submit">Войти</button>
        </form>
    </div>
@endsection
