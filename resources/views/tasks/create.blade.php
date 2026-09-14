@extends('layouts.app')

@section('content')

<div class="row justify-content-center">
    <div class="col-md-8">

        <h2 class="mb-4">Tambah Task</h2>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('tasks.store') }}">

            @csrf

            <div class="card">
                <div class="card-body">

                    <div class="mb-3">
                        <label class="form-label">Judul</label>
                        <input
                            type="text"
                            name="title"
                            class="form-control"
                            value="{{ old('title') }}"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea
                            name="description"
                            class="form-control"
                            rows="4"
                        >{{ old('description') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kategori</label>

                        <select name="category_id" class="form-select" required>
                            <option value="">Pilih kategori</option>

                            @foreach ($categories as $category)
                                <option
                                    value="{{ $category->id }}"
                                    @selected(old('category_id') == $category->id)
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
                                <option value="">Pilih prioritas</option>
                                <option value="rendah">Rendah</option>
                                <option value="sedang">Sedang</option>
                                <option value="tinggi">Tinggi</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>

                            <select name="status" class="form-select" required>
                                <option value="">Pilih status</option>
                                <option value="belum dimulai">Belum Dimulai</option>
                                <option value="dikerjakan">Dikerjakan</option>
                                <option value="selesai">Selesai</option>
                            </select>
                        </div>

                    </div>

                    <div class="mb-3">
                        <label class="form-label">Due Date</label>

                        <input
                            type="date"
                            name="due_date"
                            class="form-control"
                            value="{{ old('due_date') }}"
                        >
                    </div>

                    <div class="d-flex gap-2">

                        <button type="submit" class="btn btn-dark">
                            Simpan Task
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
