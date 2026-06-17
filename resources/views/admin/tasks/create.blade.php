@extends('layouts.admin')

@section('title', 'Добавить задачу — AI-management')

@push('head')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
    <style>
        .select2-container--default .select2-selection--single,
        .select2-container--default .select2-selection--multiple {
            background: #0b1220;
            border: 1px solid var(--border);
            border-radius: 8px;
            min-height: 38px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered,
        .select2-container--default .select2-selection--multiple .select2-selection__rendered {
            color: var(--text);
            line-height: 36px;
            padding-left: 10px;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__rendered {
            line-height: normal;
            padding: 4px 8px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
        .select2-dropdown {
            background: #0b1220;
            border-color: var(--border);
            color: var(--text);
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background: #0ea5e9;
        }
        .select2-container--default .select2-search--dropdown .select2-search__field {
            background: #111827;
            border: 1px solid var(--border);
            color: var(--text);
        }
        .select2-container { width: 100% !important; }
        .task-form { display: grid; gap: 16px; max-width: 760px; }
        .task-form label span { display: block; margin-bottom: 6px; color: var(--muted); font-size: 13px; }
        .hint { color: var(--muted); font-size: 12px; margin-top: 4px; }
    </style>
@endpush

@section('content')
    <h1 style="margin-top:0;">Добавить задачу</h1>

    @if ($errors->any())
        <div class="error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="card" style="padding: 20px;">
        <form method="post" action="{{ route('tasks.store') }}" enctype="multipart/form-data" class="task-form" id="taskForm">
            @csrf

            <label>
                <span>Проект</span>
                <select name="project_id" id="project_id" class="select2" required>
                    <option value=""></option>
                    @foreach($projects as $project)
                        <option value="{{ $project['id'] }}" @selected((int) old('project_id') === $project['id'])>{{ $project['name'] }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                <span>Название задачи</span>
                <input type="text" name="subject" value="{{ old('subject') }}" required maxlength="500">
            </label>

            <label>
                <span>Описание</span>
                <textarea name="description" id="description" rows="8">{{ old('description') }}</textarea>
            </label>

            <label>
                <span>Статус</span>
                <select name="status_id" class="select2" required>
                    <option value=""></option>
                    @foreach($statuses as $status)
                        <option value="{{ $status['id'] }}" @selected((int) old('status_id') === $status['id'])>{{ $status['name'] }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                <span>Приоритет</span>
                <select name="priority_id" class="select2" required>
                    <option value=""></option>
                    @foreach($priorities as $priority)
                        <option value="{{ $priority['id'] }}" @selected((int) old('priority_id') === $priority['id'])>{{ $priority['name'] }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                <span>Тип задачи</span>
                <select name="category_id" id="category_id" class="select2">
                    <option value=""></option>
                </select>
                <div class="hint">Категории загружаются после выбора проекта</div>
            </label>

            <label>
                <span>Срок завершения</span>
                <input type="date" name="due_date" value="{{ old('due_date') }}">
            </label>

            <label>
                <span>Оценка времени (часы)</span>
                <input type="number" name="estimated_hours" value="{{ old('estimated_hours') }}" min="0" step="1">
            </label>

            <label>
                <span>Трекер</span>
                <select name="tracker_id" class="select2" required>
                    <option value=""></option>
                    @foreach($trackers as $tracker)
                        <option value="{{ $tracker['id'] }}" @selected((int) old('tracker_id') === $tracker['id'])>{{ $tracker['name'] }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                <span>Связанные задачи</span>
                <select name="related_issue_ids[]" id="related_issue_ids" class="select2" multiple></select>
            </label>

            <label>
                <span>Прикреплённые файлы</span>
                <input type="file" name="attachments[]" multiple>
                <div class="hint">До 10 МБ на файл</div>
            </label>

            <div style="display:flex; gap:10px;">
                <button class="btn btn-primary" type="submit">Создать задачу</button>
                <a href="{{ route('tasks.index') }}" class="btn">Отмена</a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js"></script>
    <script>
        (function () {
            const categoriesUrl = @json(route('tasks.ajax.categories'));
            const issuesUrl = @json(route('tasks.ajax.issues'));
            const oldCategoryId = @json(old('category_id'));
            const oldRelated = @json(old('related_issue_ids', []));

            $('.select2').select2({ width: '100%', placeholder: 'Выберите…', allowClear: true });

            tinymce.init({
                selector: '#description',
                height: 280,
                menubar: false,
                plugins: 'lists link code',
                toolbar: 'undo redo | bold italic underline | bullist numlist | link | code',
                skin: 'oxide-dark',
                content_css: 'dark',
                branding: false,
            });

            const $category = $('#category_id');

            function loadCategories(projectId, selectedId) {
                $category.empty().append(new Option('', '', false, false));
                if (!projectId) {
                    $category.trigger('change');
                    return;
                }
                fetch(categoriesUrl + '?project_id=' + encodeURIComponent(projectId))
                    .then((r) => r.json())
                    .then((data) => {
                        (data.categories || []).forEach((c) => {
                            const opt = new Option(c.name, c.id, false, String(c.id) === String(selectedId));
                            $category.append(opt);
                        });
                        $category.trigger('change');
                    })
                    .catch(() => alert('Не удалось загрузить типы задач для проекта.'));
            }

            $('#project_id').on('change', function () {
                loadCategories(this.value, null);
            });

            if ($('#project_id').val()) {
                loadCategories($('#project_id').val(), oldCategoryId);
            }

            $('#related_issue_ids').select2({
                width: '100%',
                placeholder: 'Начните вводить название…',
                allowClear: true,
                multiple: true,
                ajax: {
                    url: issuesUrl,
                    dataType: 'json',
                    delay: 250,
                    data: (params) => ({ q: params.term || '' }),
                    processResults: (data) => ({
                        results: (data.results || []).map((item) => ({ id: item.id, text: item.text })),
                    }),
                },
            });

            if (oldRelated.length) {
                oldRelated.forEach((id) => {
                    const opt = new Option('#' + id, id, true, true);
                    $('#related_issue_ids').append(opt);
                });
                $('#related_issue_ids').trigger('change');
            }

            document.getElementById('taskForm').addEventListener('submit', () => {
                if (tinymce.get('description')) {
                    tinymce.get('description').save();
                }
            });
        })();
    </script>
@endpush
