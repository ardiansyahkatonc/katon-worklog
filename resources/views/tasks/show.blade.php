@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">

    <h2>Detail Task</h2>

    <div class="d-flex gap-2">

        <a
            href="{{ route('tasks.edit', $task) }}"
            class="btn btn-dark"
        >
            Edit
        </a>

        <a
            href="{{ route('tasks.index') }}"
            class="btn btn-outline-secondary"
        >
            Kembali
        </a>

    </div>

</div>

<div class="card">

    <div class="card-body">

        <h3>{{ $task->title }}</h3>

        <hr>

        <p>
            <strong>Deskripsi</strong>
        </p>

        <p>
            {{ $task->description ?: '-' }}
        </p>

        <div class="row mt-4">

            <div class="col-md-3">
                <strong>Kategori</strong>
                <p>{{ $task->category->name }}</p>
            </div>

            <div class="col-md-3">
                <strong>Prioritas</strong>
                <p>{{ ucfirst($task->priority) }}</p>
            </div>

            <div class="col-md-3">
                <strong>Status</strong>
                <p>{{ ucfirst($task->status) }}</p>
            </div>

            <div class="col-md-3">
                <strong>Due Date</strong>
                <p>
                    {{ $task->due_date?->format('d/m/Y') ?? '-' }}
                </p>
            </div>

        </div>

    </div>

</div>

@endsection
