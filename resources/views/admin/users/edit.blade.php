@extends('admin.layout')
@section('content')
<div class="px-4 py-6"><h1 class="text-3xl font-bold mb-6">Edit User</h1>
<div class="bg-white p-6 rounded-lg shadow max-w-2xl"><form method="POST" action="{{ route('admin.users.update', $user) }}">@csrf @method('PUT')
<div class="mb-4"><label class="block text-gray-700 mb-2">Name</label><input type="text" name="name" required class="w-full px-3 py-2 border rounded" value="{{ $user->name }}"></div>
<div class="mb-4"><label class="block text-gray-700 mb-2">Email</label><input type="email" name="email" required class="w-full px-3 py-2 border rounded" value="{{ $user->email }}"></div>
<div class="mb-4"><label class="block text-gray-700 mb-2">Password (leave empty to keep current)</label><input type="password" name="password" class="w-full px-3 py-2 border rounded"></div>
<div class="mb-4"><label class="block text-gray-700 mb-2">Role</label><select name="role" required class="w-full px-3 py-2 border rounded"><option value="tenant_user" {{$user->role=='tenant_user'?'selected':''}}>Tenant User</option><option value="tenant_admin" {{$user->role=='tenant_admin'?'selected':''}}>Tenant Admin</option><option value="super_admin" {{$user->role=='super_admin'?'selected':''}}>Super Admin</option></select></div>
<div class="mb-4"><label class="inline-flex items-center"><input type="checkbox" name="is_active" {{$user->is_active?'checked':''}} class="mr-2"><span>Active</span></label></div>
<div class="flex gap-2"><button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Update</button><a href="{{ route('admin.users') }}" class="bg-gray-200 px-4 py-2 rounded">Cancel</a></div>
</form></div></div>
@endsection
