@extends('admin.layout')
@section('title', 'Create User')
@section('content')
<div class="px-4 py-6">
    <h1 class="text-3xl font-bold mb-6">Create User</h1>
    <div class="bg-white p-6 rounded-lg shadow max-w-2xl">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Name</label>
                <input type="text" name="name" required class="w-full px-3 py-2 border rounded" value="{{ old('name') }}">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Email</label>
                <input type="email" name="email" required class="w-full px-3 py-2 border rounded" value="{{ old('email') }}">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Password</label>
                <input type="password" name="password" required class="w-full px-3 py-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Tenant</label>
                <select name="tenant_id" class="w-full px-3 py-2 border rounded">
                    <option value="">None</option>
                    @foreach($tenants as $tenant)
                    <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Role</label>
                <select name="role" required class="w-full px-3 py-2 border rounded">
                    <option value="tenant_user">Tenant User</option>
                    <option value="tenant_admin">Tenant Admin</option>
                    <option value="super_admin">Super Admin</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="is_super_admin" class="mr-2">
                    <span class="text-gray-700">Super Admin</span>
                </label>
            </div>
            <div class="mb-4">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="is_active" checked class="mr-2">
                    <span class="text-gray-700">Active</span>
                </label>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Create</button>
                <a href="{{ route('admin.users') }}" class="bg-gray-200 px-4 py-2 rounded">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
