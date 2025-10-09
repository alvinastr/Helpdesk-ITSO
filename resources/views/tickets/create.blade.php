@extends(layouts.app)

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Buat Ticket Baru</h4>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Belum ada ticket. 
            <a href="{{ route('tickets.create') }}">Buat ticket pertama Anda</a>
        </div>
    @endif
</div>
@endsection