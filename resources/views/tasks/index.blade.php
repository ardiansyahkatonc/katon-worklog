@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Daftar Task</h2>
        <p class="text-muted mb-0">Task milik Anda</p>
    </div>

    <a href="{{ route('tasks.create') }}" class="btn btn-dark">
        + Tambah Task
    </a>
</div>

<form method="GET" action="{{ route('tasks.index') }}" class="card card-body mb-4">

    <div class="row g-3">

        <div class="col-md-5">
            <label class="form-label">Cari</label>
            <input
                type="text"
                name="search"
                class="form-control"
                placeholder="Judul atau deskripsi..."
                value="{{ request('search') }}"
            >
        </div>

        <div class="col-md-3">
            <label class="form-label">Status</label>

            <select name="status" class="form-select">
                <option value="">Semua Status</option>
                <option value="belum dimulai"
                    @selected(request('status') === 'belum dimulai')>
                    Belum Dimulai
                </option>
                <option value="dikerjakan"
                    @selected(request('status') === 'dikerjakan')>
                    Dikerjakan
                </option>
                <option value="selesai"
                    @selected(request('status') === 'selesai')>
                    Selesai
                </option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">Prioritas</label>

            <select name="priority" class="form-select">
                <option value="">Semua Prioritas</option>
                <option value="rendah"
                    @selected(request('priority') === 'rendah')>
                    Rendah
                </option>
                <option value="sedang"
                    @selected(request('priority') === 'sedang')>
                    Sedang
                </option>
                <option value="tinggi"
                    @selected(request('priority') === 'tinggi')>
                    Tinggi
                </option>
            </select>
        </div>

        <div class="col-md-1 d-flex align-items-end">
            <button class="btn btn-dark w-100">
                Cari
            </button>
        </div>

    </div>

</form>

@if($tasks->count())

    <div class="table-responsive">
        <table class="table table-bordered bg-white align-middle">

            <thead class="table-dark">
                <tr>
                    <th>Judul</th>
                    <th>Kategori</th>
                    <th>Prioritas</th>
                    <th>Status</th>
                    <th>Deadline</th>
                    <th width="180">Aksi</th>
                </tr>
            </thead>

            <tbody>

                @foreach($tasks as $task)

                    <tr>

                        <td>
                            <strong>{{ $task->title }}</strong>

                            @if($task->description)
                                <br>
                                <small class="text-muted">
                                    {{ Str::limit($task->description, 60) }}
                                </small>
                            @endif
                        </td>

                        <td>
                            {{ $task->category->name }}
                        </td>

                        <td>
                            {{ ucfirst($task->priority) }}
                        </td>

                        <td>
                            {{ ucfirst($task->status) }}
                        </td>

                        <td>
                            {{ $task->due_date?->format('d/m/Y') ?? '-' }}
                        </td>

                        <td>

                            <a
                                href="{{ route('tasks.show', $task) }}"
                                class="btn btn-sm btn-outline-dark"
                            >
                                Detail
                            </a>

                            <a
                                href="{{ route('tasks.edit', $task) }}"
                                class="btn btn-sm btn-outline-secondary"
                            >
                                Edit
                            </a>

                            <form
                                method="POST"
                                action="{{ route('tasks.destroy', $task) }}"
                                class="d-inline"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Hapus task ini?')"
                                >
                                    Hapus
                                </button>
                            </form>

                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>
    </div>

@else

    <div class="alert alert-info">
        Belum ada task.
    </div>

@endif

@endsection
