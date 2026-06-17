@extends('layouts.admin')

@section('title', 'Сотрудники — AI-management')

@push('head')
    <style>
        .employees-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        table.employees-table {
            table-layout: fixed;
        }
        table.employees-table .col-login { width: 88px; }
        table.employees-table .col-tasks { width: 16%; min-width: 160px; }
        table.employees-table .col-text { width: 26%; min-width: 200px; }
        table.employees-table .col-hours {
            width: 64px;
            text-align: right;
            white-space: nowrap;
        }
        table.employees-table .col-ignore {
            width: 88px;
            text-align: center;
            vertical-align: middle;
        }
        table.employees-table .col-ignore label {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            min-height: 24px;
        }
        table.employees-table .col-ignore input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--accent);
        }
        table.employees-table textarea {
            min-height: 96px;
            font-size: 13px;
            line-height: 1.35;
        }
        table.employees-table .col-login strong {
            word-break: break-word;
            font-size: 13px;
        }
        table.employees-table .col-hours .hours-value {
            padding-top: 6px;
            font-weight: 700;
            font-size: 13px;
        }
        table.employees-table th {
            vertical-align: middle;
        }
        table.employees-table td {
            vertical-align: middle;
        }
        table.employees-table .col-text textarea {
            vertical-align: top;
        }
    </style>
@endpush

@section('content')
    <h1 style="margin-top:0;">Сотрудники</h1>
    <p style="color: var(--muted); max-width: 900px;">
        Поля должностей, квалификаций компетенций и опыта сохраняются в рамках модуля и участвуют в подборе исполнителя через нейросеть.
        Отмеченные «Игнорировать» не участвуют в рекомендациях и не могут быть назначены.
    </p>

    @if ($errors->any())
        <div class="error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="post" action="{{ route('employees.bulk-update', ['direction' => $sortDirection]) }}">
        @csrf
        @method('PUT')

        <div class="employees-toolbar">
            <span style="color: var(--muted); font-size: 14px;">Изменения применяются ко всем сотрудникам на странице.</span>
            <button class="btn btn-primary" type="submit">Сохранить</button>
        </div>

        <div class="card" style="overflow:auto;">
            <table class="employees-table">
                <thead>
                <tr>
                    <th class="col-login">
                        <a href="{{ route('employees.index', ['direction' => $sortDirection === 'asc' ? 'desc' : 'asc']) }}">Логин</a>
                        @if($sortDirection === 'asc') ↑ @else ↓ @endif
                    </th>
                    <th class="col-tasks">Текущие задачи (in progress)</th>
                    <th class="col-text">Должность, квалификации</th>
                    <th class="col-text">Компетенции</th>
                    <th class="col-text">Опыт и достижения</th>
                    <th class="col-hours">Заплан. ч.</th>
                    <th class="col-ignore">Игнорировать</th>
                </tr>
                </thead>
                <tbody>
                @foreach($employees as $employee)
                    <tr>
                        <td class="col-login">
                            <strong>{{ $employee->login }}</strong>
                        </td>
                        <td class="col-tasks">
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
                        <td class="col-text">
                            <textarea name="employees[{{ $employee->id }}][grades]" rows="4">{{ old('employees.'.$employee->id.'.grades', $employee->grades) }}</textarea>
                        </td>
                        <td class="col-text">
                            <textarea name="employees[{{ $employee->id }}][competencies]" rows="4">{{ old('employees.'.$employee->id.'.competencies', $employee->competencies) }}</textarea>
                        </td>
                        <td class="col-text">
                            <textarea name="employees[{{ $employee->id }}][experience_achievements]" rows="4">{{ old('employees.'.$employee->id.'.experience_achievements', $employee->experience_achievements) }}</textarea>
                        </td>
                        <td class="col-hours">
                            <div class="hours-value">
                                {{ number_format($employee->plannedHoursOnIncomplete(), 1, ',', ' ') }}
                            </div>
                        </td>
                        <td class="col-ignore">
                            <input type="hidden" name="employees[{{ $employee->id }}][ignored]" value="0">
                            <label title="Не предлагать в рекомендациях">
                                <input type="checkbox"
                                       name="employees[{{ $employee->id }}][ignored]"
                                       value="1"
                                       @checked(filter_var(old('employees.'.$employee->id.'.ignored', $employee->ignored), FILTER_VALIDATE_BOOLEAN))>
                            </label>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </form>
@endsection
