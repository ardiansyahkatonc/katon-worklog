@extends('layouts.app')

@section('content')

<div class="row justify-content-center">

    <div class="col-md-8">

        <h2 class="mb-4">Edit Task</h2>

        <form method="POST" action="{{ route('tasks.update', $task) }}">

            @csrf
            @method('PUT')

            <div class="card">
                <div class="card-body">

                    <div class="mb-3">
                        <label class="form-label">Judul</label>

                        <input
                            type="text"
                            name="title"
                            class="form-control"
                            value="{{ old('title', $task->title) }}"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>

                        <textarea
                            name="description"
                            class="form-control"
                            rows="4"
                        >{{ old('description', $task->description) }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kategori</label>

                        <select name="category_id" class="form-select" required>

                            @foreach($categories as $category)

                                <option
                                    value="{{ $category->id }}"
                                    @selected(
                                        old('category_id', $task->category_id)
                                        == $category->id
                                    )
                                >
                                    {{ $category->name }}
                                </option>

                            @endforeach

                        </select>
                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <label class="form-label">Prioritas</label>

                            <select name="priority" class="form-select" required>

                                <option
                                    value="rendah"
                                    @selected($task->priority === 'rendah')
                                >
                                    Rendah
                                </option>

                                <option
                                    value="sedang"
                                    @selected($task->priority === 'sedang')
                                >
                                    Sedang
                                </option>

                                <option
                                    value="tinggi"
                                    @selected($task->priority === 'tinggi')
                                >
                                    Tinggi
                                </option>

                            </select>

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">Status</label>

                            <select name="status" class="form-select" required>

                                <option
                                    value="belum dimulai"
                                    @selected($task->status === 'belum dimulai')
                                >
                                    Belum Dimulai
                                </option>

                                <option
                                    value="dikerjakan"
                                    @selected($task->status === 'dikerjakan')
                                >
                                    Dikerjakan
                                </option>

                                <option
                                    value="selesai"
                                    @selected($task->status === 'selesai')
                                >
                                    Selesai
                                </option>

                            </select>

                        </div>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">Due Date</label>

                        <input
                            type="date"
                            name="due_date"
                            class="form-control"
                            value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}"
                        >

                    </div>

                    <div class="d-flex gap-2">

                        <button class="btn btn-dark">
                            Update Task
                        </button>

                        <a
                            href="{{ route('tasks.index') }}"
                            class="btn btn-outline-secondary"
                        >
                            Batal
                        </a>

                    </div>

                </div>
            </div>

        </form>

    </div>

</div>

@endsection
