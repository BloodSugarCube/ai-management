@extends('layouts.admin')

@section('title', 'Добавить задачу — AI-management')

@push('head')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" rel="stylesheet">
    <style>
        .task-create-wrap { max-width: 600px; }
        .task-form-card {
            padding: 20px;
            overflow: visible;
        }
        .select2-container--default .select2-selection--single,
        .select2-container--default .select2-selection--multiple {
            background: #0b1220;
            border: 1px solid var(--border);
            border-radius: 8px;
            min-height: 38px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: var(--text);
            line-height: 36px;
            padding-left: 10px;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__rendered {
            color: var(--text);
            line-height: normal;
            padding: 6px 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
        .select2-container--default .select2-selection--multiple {
            min-height: 42px;
            padding-bottom: 2px;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background: #1e293b;
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 6px;
            padding: 4px 8px 4px 4px;
            margin: 0;
            font-size: 13px;
            line-height: 1.3;
            max-width: 100%;
            white-space: normal;
            word-break: break-word;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: var(--muted);
            margin-right: 6px;
            border: none;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #fecaca;
            background: transparent;
        }
        .select2-container--default .select2-search--inline .select2-search__field {
            color: var(--text);
            margin-top: 2px;
        }
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
        .task-form { display: grid; gap: 16px; }
        .task-form label > span { display: block; margin-bottom: 6px; color: var(--muted); font-size: 13px; }
        .hint { color: var(--muted); font-size: 12px; margin-top: 4px; }
        .date-input-wrap { position: relative; }
        .date-input-wrap input.flatpickr-input,
        .date-input-wrap input[type="text"]#due_date_display {
            width: 100%;
            background: #0b1220;
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 8px;
            padding: 8px 40px 8px 10px;
            cursor: pointer;
        }
        .date-input-wrap .date-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: var(--muted);
            pointer-events: none;
        }
        .flatpickr-calendar {
            background: #0b1220;
            border-color: var(--border);
            box-shadow: 0 12px 40px rgba(0,0,0,0.5);
        }
        .flatpickr-months .flatpickr-month,
        .flatpickr-current-month .flatpickr-monthDropdown-months,
        .flatpickr-weekdays,
        span.flatpickr-weekday {
            background: #0b1220;
            color: var(--text);
        }
        .flatpickr-day { color: var(--text); }
        .flatpickr-day:hover,
        .flatpickr-day:focus { background: rgba(56, 189, 248, 0.15); border-color: var(--border); }
        .flatpickr-day.selected { background: #0ea5e9; border-color: #0ea5e9; color: #0b1220; }
        .file-dropzone {
            border: 1px dashed rgba(148, 163, 184, 0.45);
            border-radius: 10px;
            background: rgba(11, 18, 32, 0.6);
            transition: border-color 0.15s, background 0.15s;
        }
        .file-dropzone.dragover {
            border-color: var(--accent);
            background: rgba(56, 189, 248, 0.06);
        }
        .file-dropzone-inner {
            padding: 20px 16px;
            text-align: center;
            cursor: pointer;
        }
        .file-dropzone-inner svg {
            width: 32px; height: 32px; color: var(--muted); margin-bottom: 8px;
        }
        .file-dropzone-inner strong { display: block; margin-bottom: 4px; }
        .file-dropzone-inner span { color: var(--muted); font-size: 13px; }
        .file-list {
            list-style: none;
            margin: 0;
            padding: 0 12px 12px;
            display: grid;
            gap: 8px;
        }
        .file-list:empty { display: none; }
        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 8px 10px;
            background: #111827;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 13px;
        }
        .file-item-name {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .file-item-size { color: var(--muted); flex-shrink: 0; font-size: 12px; }
        .file-item-remove {
            border: none; background: transparent; color: var(--muted);
            cursor: pointer; font-size: 18px; line-height: 1; padding: 0 4px;
        }
        .file-item-remove:hover { color: #fecaca; }
    </style>
@endpush

@section('content')
    <div class="task-create-wrap">
        <h1 style="margin-top:0;">Добавить задачу</h1>

        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="card task-form-card">
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
                    <div class="date-input-wrap">
                        <input type="text" id="due_date_display" placeholder="Выберите дату…" readonly>
                        <input type="hidden" name="due_date" id="due_date" value="{{ old('due_date') }}">
                        <svg class="date-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
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

                <div>
                    <span style="display:block; margin-bottom:6px; color: var(--muted); font-size: 13px;">Прикреплённые файлы</span>
                    <div class="file-dropzone" id="fileDropzone">
                        <input type="file" name="attachments[]" multiple id="attachmentsInput" style="display:none">
                        <div class="file-dropzone-inner" id="fileDropzoneTrigger">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            <strong>Перетащите файлы сюда</strong>
                            <span>или нажмите для выбора · до 5 МБ на файл</span>
                        </div>
                        <ul class="file-list" id="fileList"></ul>
                    </div>
                </div>

                <div style="display:flex; gap:10px;">
                    <button class="btn btn-primary" type="submit">Создать задачу</button>
                    <a href="{{ route('tasks.index') }}" class="btn">Отмена</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/ru.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js"></script>
    <script>
        (function () {
            const categoriesUrl = @json(route('tasks.ajax.categories'));
            const issuesUrl = @json(route('tasks.ajax.issues'));
            const oldCategoryId = @json(old('category_id'));
            const oldRelated = @json(old('related_issue_ids', []));
            const oldDueDate = @json(old('due_date'));

            $('.select2').not('#related_issue_ids').select2({ width: '100%', placeholder: 'Выберите…', allowClear: true });

            flatpickr('#due_date_display', {
                locale: 'ru',
                dateFormat: 'Y-m-d',
                altInput: false,
                defaultDate: oldDueDate || null,
                disableMobile: true,
                onChange: function (selectedDates, dateStr) {
                    document.getElementById('due_date').value = dateStr;
                },
            });

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
                closeOnSelect: false,
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

            const fileInput = document.getElementById('attachmentsInput');
            const fileList = document.getElementById('fileList');
            const dropzone = document.getElementById('fileDropzone');
            const dropzoneTrigger = document.getElementById('fileDropzoneTrigger');
            let selectedFiles = [];

            function formatSize(bytes) {
                if (bytes < 1024) return bytes + ' Б';
                if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' КБ';
                return (bytes / (1024 * 1024)).toFixed(1) + ' МБ';
            }

            function syncInputFiles() {
                const dt = new DataTransfer();
                selectedFiles.forEach((f) => dt.items.add(f));
                fileInput.files = dt.files;
            }

            function renderFileList() {
                fileList.innerHTML = '';
                selectedFiles.forEach((file, index) => {
                    const li = document.createElement('li');
                    li.className = 'file-item';
                    li.innerHTML = `
                        <span class="file-item-name" title="${file.name}">${file.name}</span>
                        <span class="file-item-size">${formatSize(file.size)}</span>
                        <button type="button" class="file-item-remove" aria-label="Удалить">&times;</button>
                    `;
                    li.querySelector('.file-item-remove').addEventListener('click', () => {
                        selectedFiles.splice(index, 1);
                        syncInputFiles();
                        renderFileList();
                    });
                    fileList.appendChild(li);
                });
            }

            function addFiles(files) {
                Array.from(files).forEach((file) => {
                    if (file.size > 5 * 1024 * 1024) {
                        alert('Файл «' + file.name + '» превышает 5 МБ.');
                        return;
                    }
                    const duplicate = selectedFiles.some((f) => f.name === file.name && f.size === file.size);
                    if (!duplicate) selectedFiles.push(file);
                });
                syncInputFiles();
                renderFileList();
            }

            dropzoneTrigger.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', () => addFiles(fileInput.files));

            ['dragenter', 'dragover'].forEach((evt) => {
                dropzone.addEventListener(evt, (e) => {
                    e.preventDefault();
                    dropzone.classList.add('dragover');
                });
            });
            ['dragleave', 'drop'].forEach((evt) => {
                dropzone.addEventListener(evt, (e) => {
                    e.preventDefault();
                    dropzone.classList.remove('dragover');
                });
            });
            dropzone.addEventListener('drop', (e) => addFiles(e.dataTransfer.files));

            document.getElementById('taskForm').addEventListener('submit', () => {
                if (tinymce.get('description')) {
                    tinymce.get('description').save();
                }
            });
        })();
    </script>
@endpush
