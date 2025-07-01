@extends('layouts.app')

@section('content')
    <h1>{{ $item->name }}</h1>
    <p>{{ $item->description }}</p>
    <a href="/items/{{ $item->id }}/edit" class="btn btn-warning">Edit</a>
    <form action="/items/{{ $item->id }}" method="POST" style="display:inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">Delete</button>
    </form>
    <a href="/items" class="btn btn-secondary">Back</a>
@endsection