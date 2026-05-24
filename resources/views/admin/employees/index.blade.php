@extends('layouts.admin')

@section('title', 'Сотрудники — AI-management')

@section('content')
    <h1 style="margin-top:0;">Сотрудники</h1>
    <p style="color: var(--muted); max-width: 900px;">
        Данные синхронизируются командой <span class="pill">php artisan redmine:sync-employees</span>.
        Поля грейдов, компетенций и опыта сохраняются локально и участвуют в подборе исполнителя через GenAPI.
    </p>

    <div class="card" style="overflow:auto;">
        <table>
            <thead>
            <tr>
                <th style="width: 140px;">
                    <a href="{{ route('employees.index', ['direction' => $sortDirection === 'asc' ? 'desc' : 'asc']) }}">Логин</a>
                    @if($sortDirection === 'asc') ↑ @else ↓ @endif
                </th>
                <th style="min-width: 220px;">Текущие задачи (in progress)</th>
                <th style="min-width: 140px;">Грейды</th>
                <th style="min-width: 160px;">Компетенции</th>
                <th style="min-width: 220px;">Опыт и достижения</th>
                <th style="width: 120px;">Запланировано ч.</th>
                <th style="width: 120px;"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($employees as $employee)
                @php($fid = 'employee-form-'.$employee->id)
                <tr>
                    <td>
                        <form id="{{ $fid }}" method="post" action="{{ route('employees.update', $employee) }}?direction={{ $sortDirection }}">
                            @csrf
                            @method('PUT')
                        </form>
                        <strong>{{ $employee->login }}</strong>
                    </td>
                    <td>
                        @php($tasks = $employee->inProgressTaskSubjects())
                        @if($tasks === [])
                            <span class="pill">нет</span>
                        @else
                            <ul class="compact">
                                @foreach($tasks as $t)
                                    <li>{{ $t }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </td>
                    <td>
                        <textarea name="grades" rows="3" form="{{ $fid }}">{{ old('grades.'.$employee->id, $employee->grades) }}</textarea>
                    </td>
                    <td>
                        <textarea name="competencies" rows="3" form="{{ $fid }}">{{ old('competencies.'.$employee->id, $employee->competencies) }}</textarea>
                    </td>
                    <td>
                        <textarea name="experience_achievements" rows="3" form="{{ $fid }}">{{ old('exp.'.$employee->id, $employee->experience_achievements) }}</textarea>
                    </td>
                    <td>
                        <div style="padding-top:6px;font-weight:700;">
                            {{ number_format($employee->plannedHoursOnIncomplete(), 1, ',', ' ') }}
                        </div>
                    </td>
                    <td>
                        <button class="btn btn-primary" type="submit" form="{{ $fid }}" style="width:100%;">Сохранить</button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
