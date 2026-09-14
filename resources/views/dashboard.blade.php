@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Dashboard</h2>
        <p class="text-muted mb-0">
            Ringkasan task Anda
        </p>
    </div>

    <a href="{{ route('tasks.create') }}" class="btn btn-dark">
        + Tambah Task
    </a>
</div>

<div class="row g-3">

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted">Total Task</small>
                <h2>{{ $total }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted">Belum Dimulai</small>
                <h2>{{ $notStarted }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted">Dikerjakan</small>
                <h2>{{ $inProgress }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted">Selesai</small>
                <h2>{{ $completed }}</h2>
            </div>
        </div>
    </div>

</div>

<div class="mt-4">
    <a href="{{ route('tasks.index') }}" class="btn btn-outline-dark">
        Lihat Semua Task
    </a>
</div>

@endsection
