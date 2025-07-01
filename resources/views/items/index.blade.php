@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Items</h1>
        <div>

            <a href="/items/create" class="btn btn-primary">Create New</a>
        </div>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->description }}</td>
                    <td>
                        <a href="/items/{{ $item->id }}" class="btn btn-info">View</a>
                        <a href="/items/{{ $item->id }}/edit" class="btn btn-warning">Edit</a>
                        <form action="/items/{{ $item->id }}" method="POST" style="display:inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    
@endsection